<?php
/**
 * Frontend: renders swatches next to WooCommerce's (visually hidden) variation select.
 *
 * The original <select> stays in the form, so WooCommerce's own variation script keeps handling
 * prices, images, stock and validation. The swatches only mirror and drive that select.
 */

defined( 'ABSPATH' ) || exit;

class VSW_Frontend {

	public function init() {
		add_filter( 'woocommerce_dropdown_variation_attribute_options_html', array( $this, 'render' ), 20, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets() {
		wp_register_style( 'variant-swatches', VSW_URL . 'assets/swatches.css', array(), VSW_VERSION );
		wp_register_script( 'variant-swatches', VSW_URL . 'assets/swatches.js', array( 'jquery', 'wc-add-to-cart-variation' ), VSW_VERSION, true );

		// Load early on product pages to avoid a flash of the dropdown; other pages load on demand in render().
		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_style( 'variant-swatches' );
		}
	}

	/**
	 * @param string $html Select markup from wc_dropdown_variation_attribute_options().
	 * @param array  $args Arguments passed to wc_dropdown_variation_attribute_options().
	 */
	public function render( $html, $args ) {
		$product   = $args['product'] ?? null;
		$attribute = $args['attribute'] ?? '';
		$options   = $args['options'] ?? array();

		if ( ! $product instanceof WC_Product || ! $attribute || empty( $options ) ) {
			return $html;
		}

		/**
		 * Opt out per attribute or product, e.g. to keep a dropdown for a very long list.
		 */
		if ( ! apply_filters( 'variant_swatches_enabled', true, $attribute, $product ) ) {
			return $html;
		}

		$items = $this->get_items( $product, $attribute, $options );
		if ( ! $items ) {
			return $html;
		}

		$type = 'button';
		foreach ( $items as $item ) {
			if ( $item['image'] ) {
				$type = 'image';
				break;
			}
			if ( $item['color'] ) {
				$type = 'color';
			}
		}
		$type = apply_filters( 'variant_swatches_type', $type, $attribute, $product );

		wp_enqueue_style( 'variant-swatches' );
		wp_enqueue_script( 'variant-swatches' );

		$field_name = $args['name'] ?: 'attribute_' . sanitize_title( $attribute );
		$group_name = 'vsw_' . $field_name . '_' . $product->get_id();
		$selected   = (string) ( $args['selected'] ?? '' );

		// Hide the select visually and from assistive tech; the radio group below replaces it.
		$html = preg_replace( '/<select\b/', '<select tabindex="-1" aria-hidden="true" data-vsw="1"', $html, 1 );
		$html = preg_replace( '/(<select\b[^>]*\bclass=")/', '$1vsw-select ', $html, 1 );

		ob_start();
		?>
		<div class="vsw vsw--<?php echo esc_attr( $type ); ?>" role="radiogroup" aria-label="<?php echo esc_attr( wc_attribute_label( $attribute, $product ) ); ?>">
			<?php foreach ( $items as $item ) : ?>
				<label class="vsw__item<?php echo $selected === $item['value'] ? ' is-selected' : ''; ?>">
					<input class="vsw__input" type="radio" name="<?php echo esc_attr( $group_name ); ?>" value="<?php echo esc_attr( $item['value'] ); ?>" <?php checked( $selected, $item['value'] ); ?>>
					<?php if ( 'image' === $type && $item['image'] ) : ?>
						<?php echo wp_get_attachment_image( $item['image'], 'thumbnail', false, array( 'class' => 'vsw__image', 'alt' => '' ) ); ?>
					<?php elseif ( 'color' === $type ) : ?>
						<span class="vsw__swatch" aria-hidden="true" style="--vsw-color: <?php echo esc_attr( $item['color'] ?: 'transparent' ); ?>"></span>
					<?php endif; ?>
					<span class="vsw__label"><?php echo esc_html( $item['label'] ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
		<?php
		return '<div class="vsw-field">' . $html . ob_get_clean() . '</div>';
	}

	/**
	 * Options in the same order and with the same values as WooCommerce's select.
	 */
	private function get_items( WC_Product $product, $attribute, array $options ) {
		$items = array();

		if ( taxonomy_exists( $attribute ) ) {
			$terms = wc_get_product_terms( $product->get_id(), $attribute, array( 'fields' => 'all' ) );
			foreach ( $terms as $term ) {
				if ( ! in_array( $term->slug, $options, true ) ) {
					continue;
				}
				$items[] = array(
					'value' => $term->slug,
					'label' => apply_filters( 'woocommerce_variation_option_name', $term->name, $term, $attribute, $product ),
					'color' => sanitize_hex_color( (string) get_term_meta( $term->term_id, VSW_META_COLOR, true ) ),
					'image' => absint( get_term_meta( $term->term_id, VSW_META_IMAGE, true ) ),
				);
			}
			return $items;
		}

		foreach ( $options as $option ) {
			$items[] = array(
				'value' => $option,
				'label' => apply_filters( 'woocommerce_variation_option_name', $option, null, $attribute, $product ),
				'color' => '',
				'image' => 0,
			);
		}
		return $items;
	}
}
