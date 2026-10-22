/**
 * YS Loop Quantity Steps — Interactivity API module.
 *
 * @module ys/loop-quantity
 */

import { store, getContext } from '@wordpress/interactivity';

function syncToWooCommerce( value ) {
	const wcCtx = getContext( 'woocommerce/product-button' );
	if ( wcCtx ) {
		wcCtx.quantityToAdd = value;
	}
}

function snap( value, min, max, step ) {
	let v = Math.round( value / step ) * step;
	if ( v < min ) {
		v = min;
	}
	if ( v > max ) {
		v = max;
	}
	return v;
}

store( 'ys/loop-quantity', {
	state: {
		get isMinDisabled() {
			const { value, min } = getContext();
			return value <= min;
		},
		get isMaxDisabled() {
			const { value, max } = getContext();
			return value >= max;
		},
	},
	actions: {
		decrease() {
			const ctx = getContext();
			const v = Math.max( ctx.value - ctx.step, ctx.min );
			ctx.value = v;
			ctx.announcement = '';
			syncToWooCommerce( v );
		},
		increase() {
			const ctx = getContext();
			const v = Math.min( ctx.value + ctx.step, ctx.max );
			ctx.value = v;
			ctx.announcement = '';
			syncToWooCommerce( v );
		},
		handleInput( event ) {
			const ctx = getContext();
			const raw = parseInt( event.target.value, 10 );

			if ( isNaN( raw ) || raw < 1 ) {
				ctx.value = ctx.min;
				event.target.value = ctx.min;
				ctx.announcement = '';
				syncToWooCommerce( ctx.min );
				return;
			}

			const v = snap( raw, ctx.min, ctx.max, ctx.step );
			if ( v === ctx.value ) {
				return;
			}

			event.target.value = v;
			ctx.value = v;

			if ( v !== raw ) {
				ctx.announcement = ctx.adjustedTpl
					.replace( '{qty}', v.toString() )
					.replace( '{step}', ctx.step.toString() );
			} else {
				ctx.announcement = '';
			}

			syncToWooCommerce( v );
		},
	},
	callbacks: {
		init() {
			syncToWooCommerce( getContext().value );
		},
	},
} );
