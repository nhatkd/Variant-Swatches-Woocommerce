<?php
/**
 * Admin: colour and image fields on WooCommerce attribute terms (Products → Attributes → Configure terms).
 * A term with a colour gets a colour swatch, a term with an image an image swatch; otherwise a text button.
 */

defined( 'ABSPATH' ) || exit;

class VSW_Admin {

	public function init() {
		add_action( 'admin_init', array( $this, 'register_term_hooks' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function register_term_hooks() {
		foreach ( wc_get_attribute_taxonomy_names() as $taxonomy ) {
			add_action( "{$taxonomy}_add_form_fields", array( $this, 'add_fields' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'edit_fields' ) );
			add_action( "created_{$taxonomy}", array( $this, 'save' ) );
			add_action( "edited_{$taxonomy}", array( $this, 'save' ) );
			add_filter( "manage_edit-{$taxonomy}_columns", array( $this, 'add_column' ) );
			add_filter( "manage_{$taxonomy}_custom_column", array( $this, 'render_column' ), 10, 3 );
		}
	}

	public function enqueue( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) || 0 !== strpos( (string) $screen->taxonomy, 'pa_' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'variant-swatches-admin', VSW_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker' ), VSW_VERSION, true );
		wp_localize_script(
			'variant-swatches-admin',
			'vswAdmin',
			array(
				'chooseImage' => __( 'Choose swatch image', 'variant-swatches' ),
				'useImage'    => __( 'Use image', 'variant-swatches' ),
			)
		);
	}

	public function add_fields() {
		wp_nonce_field( 'vsw_save_term', 'vsw_nonce' );
		?>
		<div class="form-field">
			<label for="vsw-color"><?php esc_html_e( 'Swatch colour', 'variant-swatches' ); ?></label>
			<input type="text" id="vsw-color" class="vsw-color-field" name="vsw_color" value="">
			<p class="description"><?php esc_html_e( 'Optional. Shows a colour dot next to the name on the product page.', 'variant-swatches' ); ?></p>
		</div>
		<div class="form-field">
			<label><?php esc_html_e( 'Swatch image', 'variant-swatches' ); ?></label>
			<?php $this->image_control( 0 ); ?>
			<p class="description"><?php esc_html_e( 'Optional. Takes priority over the colour.', 'variant-swatches' ); ?></p>
		</div>
		<?php
	}

	public function edit_fields( $term ) {
		$color = sanitize_hex_color( (string) get_term_meta( $term->term_id, VSW_META_COLOR, true ) );
		$image = absint( get_term_meta( $term->term_id, VSW_META_IMAGE, true ) );
		?>
		<tr class="form-field">
			<th scope="row"><label for="vsw-color"><?php esc_html_e( 'Swatch colour', 'variant-swatches' ); ?></label></th>
			<td>
				<?php wp_nonce_field( 'vsw_save_term', 'vsw_nonce' ); ?>
				<input type="text" id="vsw-color" class="vsw-color-field" name="vsw_color" value="<?php echo esc_attr( $color ); ?>">
				<p class="description"><?php esc_html_e( 'Optional. Shows a colour dot next to the name on the product page.', 'variant-swatches' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Swatch image', 'variant-swatches' ); ?></th>
			<td>
				<?php $this->image_control( $image ); ?>
				<p class="description"><?php esc_html_e( 'Optional. Takes priority over the colour.', 'variant-swatches' ); ?></p>
			</td>
		</tr>
		<?php
	}

	private function image_control( $image_id ) {
		?>
		<div class="vsw-image-control">
			<input type="hidden" class="vsw-image-id" name="vsw_image" value="<?php echo esc_attr( $image_id ?: '' ); ?>">
			<span class="vsw-image-preview"><?php echo $image_id ? wp_get_attachment_image( $image_id, array( 48, 48 ) ) : ''; ?></span>
			<button type="button" class="button vsw-image-select"><?php esc_html_e( 'Choose image', 'variant-swatches' ); ?></button>
			<button type="button" class="button-link vsw-image-remove"<?php echo $image_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'variant-swatches' ); ?></button>
		</div>
		<?php
	}

	public function save( $term_id ) {
		if ( ! isset( $_POST['vsw_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vsw_nonce'] ) ), 'vsw_save_term' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}

		$color = isset( $_POST['vsw_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['vsw_color'] ) ) : '';
		$image = isset( $_POST['vsw_image'] ) ? absint( $_POST['vsw_image'] ) : 0;

		$color ? update_term_meta( $term_id, VSW_META_COLOR, $color ) : delete_term_meta( $term_id, VSW_META_COLOR );
		$image ? update_term_meta( $term_id, VSW_META_IMAGE, $image ) : delete_term_meta( $term_id, VSW_META_IMAGE );
	}

	public function add_column( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'name' === $key ) {
				$new['vsw_swatch'] = __( 'Swatch', 'variant-swatches' );
			}
			$new[ $key ] = $label;
		}
		return $new;
	}

	public function render_column( $content, $column, $term_id ) {
		if ( 'vsw_swatch' !== $column ) {
			return $content;
		}
		$image = absint( get_term_meta( $term_id, VSW_META_IMAGE, true ) );
		if ( $image ) {
			return wp_get_attachment_image( $image, array( 28, 28 ) );
		}
		$color = sanitize_hex_color( (string) get_term_meta( $term_id, VSW_META_COLOR, true ) );
		if ( $color ) {
			return '<span style="display:inline-block;width:20px;height:20px;border-radius:50%;background:' . esc_attr( $color ) . ';box-shadow:inset 0 0 0 1px rgba(0,0,0,.15)"></span>';
		}
		return '<span aria-hidden="true">–</span>';
	}
}
