<?php
/**
 * Plugin Name:       YS | Quantités par paliers dans les listes de produits
 * Plugin URI:        https://github.com/ysaintlary/ys-loop-quantity-steps
 * Description:       Ajoute un sélecteur de quantité (−/+) respectant les paliers et quantités minimales sur les listes de produits WooCommerce en thème à blocs.
 * Version: 1.0.5
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Yves Saint-Lary
 * Author URI:        https://ysaintlary.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ys-loop-quantity-steps
 * Domain Path:       /languages
 *
 * WC requires at least: 9.0
 * WC tested up to:      11.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YS_LQS_VERSION', '1.0.0' );
define( 'YS_LQS_FILE', __FILE__ );
define( 'YS_LQS_PATH', plugin_dir_path( __FILE__ ) );
define( 'YS_LQS_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/lib/wp-plugin-base/wp-plugin-base-runtime-updater.php';

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__ );
	}
} );

add_action( 'plugins_loaded', 'ys_lqs_init' );

function ys_lqs_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( ! class_exists( 'Barn2\\Plugin\\WC_Quantity_Manager\\Handlers\\Quantity_Input' ) ) {
		return;
	}

	add_action( 'wp_enqueue_scripts', 'ys_lqs_register_assets' );
	add_action( 'wp_enqueue_scripts', 'ys_lqs_enqueue_single_product_styles' );
	add_filter( 'woocommerce_loop_add_to_cart_link', 'ys_lqs_filter_link', 20, 3 );
	add_filter( 'woocommerce_loop_add_to_cart_link', 'ys_lqs_out_of_stock_link', 10, 2 );
}

function ys_lqs_register_assets() {
	wp_register_style(
		'ys-loop-quantity-steps',
		YS_LQS_URL . 'assets/css/style.css',
		array(),
		YS_LQS_VERSION
	);

	wp_register_script_module(
		'ys-loop-quantity-steps-view',
		YS_LQS_URL . 'assets/js/view.js',
		array( '@wordpress/interactivity' ),
		YS_LQS_VERSION
	);
}

function ys_lqs_enqueue_single_product_styles() {
	if ( is_product() ) {
		wp_enqueue_style( 'ys-loop-quantity-steps' );
		wp_add_inline_script( 'jquery-core', ys_lqs_get_single_product_js() );
	}
}

function ys_lqs_get_single_product_js() {
	return <<<'JS'
document.addEventListener("DOMContentLoaded",function(){
	document.querySelectorAll(".single-product .quantity").forEach(function(wrap){
		var input=wrap.querySelector(".qty");
		if(!input||wrap.querySelector(".ys-lqs-btn"))return;
		var min=parseFloat(input.min)||1,max=parseFloat(input.max)||9999,step=parseFloat(input.step)||1;
		var btnM=document.createElement("button");
		btnM.type="button";btnM.className="ys-lqs-btn ys-lqs-btn--minus";
		btnM.innerHTML='<span aria-hidden="true">−</span>';
		btnM.setAttribute("aria-label","Diminuer la quantité");
		var btnP=document.createElement("button");
		btnP.type="button";btnP.className="ys-lqs-btn ys-lqs-btn--plus";
		btnP.innerHTML='<span aria-hidden="true">+</span>';
		btnP.setAttribute("aria-label","Augmenter la quantité");
		wrap.insertBefore(btnM,input);
		input.parentNode.insertBefore(btnP,input.nextSibling);
		function upd(){
			var v=parseFloat(input.value)||min;
			btnM.disabled=v<=min;btnP.disabled=v>=max;
		}
		btnM.addEventListener("click",function(){
			var v=parseFloat(input.value)||min;
			v=Math.max(min,v-step);input.value=v;
			input.dispatchEvent(new Event("change",{bubbles:true}));upd();
		});
		btnP.addEventListener("click",function(){
			var v=parseFloat(input.value)||min;
			v=Math.min(max,v+step);input.value=v;
			input.dispatchEvent(new Event("change",{bubbles:true}));upd();
		});
		input.addEventListener("change",upd);
		upd();
	});
});
JS;
}

function ys_lqs_out_of_stock_link( $html, $product ) {
	if ( $product->is_in_stock() ) {
		return $html;
	}

	$text = esc_html__( 'Rupture de stock', 'ys-loop-quantity-steps' );

	$html = preg_replace(
		'/>([^<]*Lire la suite[^<]*)</',
		'>' . $text . '<',
		$html
	);
	$html = preg_replace(
		'/>([^<]*Read more[^<]*)</',
		'>' . $text . '<',
		$html
	);

	return $html;
}

/**
 * @param \WC_Product $product
 * @return array{minimum: int, maximum: int, multiple_of: int}
 */
function ys_lqs_get_product_limits( $product ) {
	if ( class_exists( 'Automattic\\WooCommerce\\StoreApi\\Utilities\\QuantityLimits' ) ) {
		$qty_limits = new \Automattic\WooCommerce\StoreApi\Utilities\QuantityLimits();
		$limits     = $qty_limits->get_add_to_cart_limits( $product );
	} else {
		$args = wc_get_quantity_input_args( array(), $product );
		$step = max( 1, (int) ( $args['step'] ?? 1 ) );
		$min  = max( 1, (int) ( $args['min_value'] ?? 1 ) );
		$max  = ( '' === ( $args['max_value'] ?? '' ) ) ? 9999 : (int) $args['max_value'];
		$min  = (int) ( ceil( $min / $step ) * $step );
		$max  = (int) ( floor( $max / $step ) * $step );
		$max  = max( $min, $max );

		$limits = array(
			'minimum'     => $min,
			'maximum'     => $max,
			'multiple_of' => $step,
		);
	}

	if ( $product->managing_stock() ) {
		$stock = (int) $product->get_stock_quantity();
		if ( $stock > 0 && $stock < $limits['multiple_of'] ) {
			$limits['minimum']     = $stock;
			$limits['maximum']     = $stock;
			$limits['multiple_of'] = $stock;
		}
	}

	return apply_filters( 'ys_lqs_product_limits', $limits, $product );
}

/**
 * @param \WC_Product $product
 * @param array       $limits
 * @return int
 */
function ys_lqs_get_start_quantity( $product, $limits ) {
	$qty  = apply_filters( 'woocommerce_add_to_cart_quantity', 1, $product->get_id(), 0 );
	$step = $limits['multiple_of'];
	$qty  = (int) ( ceil( $qty / $step ) * $step );
	$qty  = max( $qty, $limits['minimum'] );
	$qty  = min( $qty, $limits['maximum'] );

	return $qty;
}

/**
 * @param string      $html
 * @param \WC_Product $product
 * @param array       $args
 * @return string
 */
function ys_lqs_filter_link( $html, $product, $args ) {
	if ( ! $product->is_type( 'simple' ) ) {
		return $html;
	}
	if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return $html;
	}
	if ( $product->is_sold_individually() ) {
		return $html;
	}
	if ( false === strpos( $html, 'data-wp-interactive="woocommerce/product-button"' ) ) {
		return $html;
	}

	$limits = ys_lqs_get_product_limits( $product );
	$start  = ys_lqs_get_start_quantity( $product, $limits );

	$processor = new \WP_HTML_Tag_Processor( $html );
	$found     = false;
	while ( $processor->next_tag() ) {
		if ( 'woocommerce/product-button' === $processor->get_attribute( 'data-wp-interactive' ) ) {
			$raw = $processor->get_attribute( 'data-wp-context' );
			if ( $raw ) {
				$ctx = json_decode( $raw, true );
				if ( is_array( $ctx ) ) {
					$ctx['quantityToAdd'] = $start;
					$processor->set_attribute( 'data-wp-context', wp_json_encode( $ctx, JSON_NUMERIC_CHECK ) );
				}
			}
			$found = true;
			break;
		}
	}
	if ( ! $found ) {
		return $html;
	}
	$html = $processor->get_updated_html();

	$product_name = $product->get_name();
	$input_id     = 'ys-lqs-qty-' . $product->get_id();

	$adjusted_tpl = sprintf(
		/* translators: {qty}: corrected quantity, {step}: step value */
		__( "Quantité ajustée à {qty}\u{00A0}: ce produit se vend par {step}", 'ys-loop-quantity-steps' )
	);

	$selector_context = wp_interactivity_data_wp_context(
		array(
			'min'          => $limits['minimum'],
			'max'          => $limits['maximum'],
			'step'         => $limits['multiple_of'],
			'value'        => $start,
			'productName'  => $product_name,
			'adjustedTpl'  => $adjusted_tpl,
			'announcement' => '',
		)
	);

	$selector_html = sprintf(
		'<div class="ys-lqs-selector" data-wp-interactive="ys/loop-quantity" %s data-wp-init="callbacks.init">'
		. '<label for="%s" class="ys-lqs-sr-only">%s</label>'
		. '<div class="ys-lqs-controls">'
		. '<button type="button" class="ys-lqs-btn ys-lqs-btn--minus" '
		. 'data-wp-on--click="actions.decrease" '
		. 'data-wp-bind--disabled="state.isMinDisabled" '
		. 'aria-label="%s">'
		. '<span aria-hidden="true">&#x2212;</span>'
		. '</button>'
		. '<input type="number" id="%s" class="ys-lqs-input" '
		. 'min="%d" max="%d" step="%d" inputmode="numeric" value="%d" '
		. 'data-wp-bind--value="context.value" '
		. 'data-wp-on--change="actions.handleInput" '
		. 'data-wp-on--blur="actions.handleInput">'
		. '<button type="button" class="ys-lqs-btn ys-lqs-btn--plus" '
		. 'data-wp-on--click="actions.increase" '
		. 'data-wp-bind--disabled="state.isMaxDisabled" '
		. 'aria-label="%s">'
		. '<span aria-hidden="true">+</span>'
		. '</button>'
		. '</div>'
		. '<div class="ys-lqs-sr-only" aria-live="polite" data-wp-text="context.announcement"></div>'
		. '</div>',
		$selector_context,
		esc_attr( $input_id ),
		esc_html(
			sprintf(
				/* translators: %s: product name */
				__( 'Quantité pour %s', 'ys-loop-quantity-steps' ),
				$product_name
			)
		),
		esc_attr(
			sprintf(
				/* translators: %s: product name */
				__( 'Diminuer la quantité de %s', 'ys-loop-quantity-steps' ),
				$product_name
			)
		),
		esc_attr( $input_id ),
		$limits['minimum'],
		$limits['maximum'],
		$limits['multiple_of'],
		$start,
		esc_attr(
			sprintf(
				/* translators: %s: product name */
				__( 'Augmenter la quantité de %s', 'ys-loop-quantity-steps' ),
				$product_name
			)
		)
	);

	$button_pos = strpos( $html, '<button' );
	if ( false === $button_pos ) {
		return $html;
	}

	$html = substr( $html, 0, $button_pos ) . $selector_html . substr( $html, $button_pos );

	wp_enqueue_style( 'ys-loop-quantity-steps' );
	wp_enqueue_script_module( 'ys-loop-quantity-steps-view' );

	return $html;
}
