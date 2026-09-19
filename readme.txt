=== Variant Swatches ===
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPL-2.0-or-later

Replaces WooCommerce variation dropdowns with buttons, colour swatches and image swatches.

== How it works ==

* Hooks into `woocommerce_dropdown_variation_attribute_options_html`, so it works with any theme and with
  Elementor's and JetWooBuilder's add-to-cart widgets. No template overrides or child-theme edits.
* WooCommerce's own <select> stays in the form (visually hidden). Its variation script still handles price,
  images, stock and validation; the swatches only set and mirror the select.
* Combinations that don't exist, or are sold out given the other choices, are shown crossed out and can't be picked.
* Native radio buttons: keyboard (arrow keys) and screen readers work without extra scripting.
* The product's main price (the first `.price` outside the form and outside product loops) shows the chosen
  variation's price and returns to the range when the choice is cleared. WooCommerce's duplicate price above
  "Add to cart" is hidden meanwhile. Put `data-vsw-price` on another element to target that instead.

== Setup ==

Products → Attributes → Configure terms: give a term a colour or an image.
* Image set → image swatch. Colour set → colour dot + name. Neither → text button (e.g. ring sizes).
* In a group where some terms have an image, terms with only a colour show a colour square of the same size.

== Styling ==

Follows the Elementor kit's primary/secondary colours when present. Override per site with CSS custom properties:

    .vsw { --vsw-ink: #111; --vsw-bg: #fff; --vsw-line: #e7e7e5; --vsw-muted: #666; --vsw-gap: 6px; --vsw-height: 44px; }

== Filters ==

* `variant_swatches_enabled` (bool, $attribute, $product): return false to keep the dropdown.
* `variant_swatches_type` ('button'|'color'|'image', $attribute, $product): force a display type.

== Translations ==

Danish (da_DK) is bundled in languages/. Regenerate after changing strings:

    wp i18n make-pot . languages/variant-swatches.pot --exclude=languages
    wp i18n update-po languages/variant-swatches.pot languages
    wp i18n make-mo languages && wp i18n make-php languages

== Changelog ==

= 1.1.0 =
* Main product price follows the chosen variation.
* Terms with only a colour keep their colour in a group that also has image swatches.
* Admin: a failed "Add new term" no longer clears the colour and image fields.
* Danish translation; translations load from languages/.

= 1.0.0 =
* First version.
