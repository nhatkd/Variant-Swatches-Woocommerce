<?php
/**
 * Plugin Name:       Variant Swatches
 * Description:       Replaces WooCommerce variation dropdowns with accessible buttons, colour swatches and image swatches. Works with any theme or page builder (Elementor, JetWooBuilder) because it hooks into WooCommerce itself.
 * Version:           1.1.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Nhat
 * License:           GPL-2.0-or-later
 * Text Domain:       variant-swatches
 * Domain Path:       /languages
 *
 * WC requires at least: 8.0
 * WC tested up to:      11.1
 */

defined( 'ABSPATH' ) || exit;

define( 'VSW_VERSION', '1.1.1' );
define( 'VSW_FILE', __FILE__ );
define( 'VSW_URL', plugin_dir_url( __FILE__ ) );
define( 'VSW_PATH', plugin_dir_path( __FILE__ ) );

// Term meta keys for attribute terms (e.g. pa_metal → "Sterlingsølv").
define( 'VSW_META_COLOR', 'vsw_color' );
define( 'VSW_META_IMAGE', 'vsw_image' );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// Bundled translations (languages/); wp-content/languages/plugins still takes priority.
add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'variant-swatches', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once VSW_PATH . 'includes/class-vsw-frontend.php';
		require_once VSW_PATH . 'includes/class-vsw-admin.php';

		( new VSW_Frontend() )->init();
		if ( is_admin() ) {
			( new VSW_Admin() )->init();
		}
	}
);
