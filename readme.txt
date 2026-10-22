=== YS | Quantités par paliers dans les listes ===
Contributors: ysaintlary
Tags: woocommerce, quantity, step, product-collection, blocks
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ajoute un sélecteur de quantité (−/+) respectant les paliers et quantités minimales sur les listes de produits WooCommerce en thème à blocs.

== Description ==

Sur les listes de produits (bloc Collection de produits), remplace le simple bouton « Ajouter au panier » par un sélecteur de quantité (bouton −, champ, bouton +) suivi du bouton d'ajout, en respectant la quantité minimale et le palier de chaque produit.

Les règles de quantité sont lues depuis WooCommerce (via QuantityLimits) et prennent en compte les plugins tiers comme WooCommerce Quantity Manager de Barn2.

== Changelog ==

= 1.0.4 =
* Add - feat: harmonize single product page quantity input with loop selector.


= 1.0.3 =
* Fix - fix: match add-to-cart button height with quantity selector (44px).


= 1.0.2 =
* Fix - fix: override WooCommerce flex-direction column on product button.


= 1.0.1 =
* Add - feat: add runtime updater and rename plugin.
* Fix - fix: declare HPOS and cart/checkout blocks compatibility.
* Fix - fix: selector and button on same line with centered wrap.


= 1.0.0 =
* Version initiale.
