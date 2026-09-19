/**
 * Variant Swatches: keeps the radio swatches and WooCommerce's hidden variation select in sync.
 * WooCommerce's add-to-cart-variation.js stays in charge; we only set the select value and read its state.
 */
( function ( $ ) {
	'use strict';

	function selectFor( $group ) {
		return $group.closest( '.vsw-field' ).find( 'select.vsw-select' );
	}

	function attributeName( $select ) {
		return $select.data( 'attribute_name' ) || $select.attr( 'name' );
	}

	/**
	 * Values of one attribute where every matching variation (given the other current choices) is out of stock.
	 * Only possible when WooCommerce printed the variation data inline (not in its AJAX mode for large products).
	 */
	function outOfStockValues( $form, $select ) {
		var variations = $form.data( 'product_variations' );
		if ( ! Array.isArray( variations ) ) {
			return {};
		}

		var name = attributeName( $select );
		var current = {};
		$form.find( '.variations select' ).each( function () {
			var $s = $( this );
			current[ attributeName( $s ) ] = $s.val() || '';
		} );

		var seen = {}, inStock = {}, anySeen = false, anyInStock = false;

		variations.forEach( function ( variation ) {
			var attrs = variation.attributes || {};
			for ( var key in current ) {
				if ( key !== name && current[ key ] && attrs[ key ] && attrs[ key ] !== current[ key ] ) {
					return;
				}
			}
			var value = attrs[ name ];
			if ( value === '' || value === undefined ) {
				anySeen = true;
				anyInStock = anyInStock || variation.is_in_stock;
				return;
			}
			seen[ value ] = true;
			inStock[ value ] = inStock[ value ] || variation.is_in_stock;
		} );

		var result = {};
		$select.find( 'option' ).each( function () {
			var v = this.value;
			if ( v && ( seen[ v ] || anySeen ) ) {
				result[ v ] = ! ( inStock[ v ] || anyInStock );
			}
		} );
		return result;
	}

	function sync( $form ) {
		$form.find( '.vsw' ).each( function () {
			var $group = $( this );
			var $select = selectFor( $group );
			if ( ! $select.length ) {
				return;
			}

			var value = $select.val() || '';
			var available = {};
			$select.find( 'option' ).each( function () {
				if ( this.value ) {
					available[ this.value ] = ! this.disabled;
				}
			} );
			var soldOut = outOfStockValues( $form, $select );

			$group.find( '.vsw__input' ).each( function () {
				var usable = available[ this.value ] === true && soldOut[ this.value ] !== true;
				this.checked = this.value === value;
				this.disabled = ! usable && ! this.checked;
				$( this )
					.closest( '.vsw__item' )
					.toggleClass( 'is-selected', this.checked )
					.toggleClass( 'is-unavailable', ! usable );
			} );

			// "Metal: Sterlingsølv" next to WooCommerce's own label.
			var $label = $form.find( 'label[for="' + $select.attr( 'id' ) + '"]' );
			var $current = $label.find( '.vsw-current' );
			if ( ! $current.length ) {
				$current = $( '<span class="vsw-current" />' ).appendTo( $label );
			}
			var text = value ? $group.find( '.vsw__input:checked' ).siblings( '.vsw__label' ).text() : '';
			$current.text( text ? ': ' + text : '' );
		} );
	}

	/**
	 * The product's main price (e.g. "549 kr. – 649 kr." for a variable product), outside the form and not in a
	 * related/upsell loop. Mark another element with data-vsw-price to target it instead.
	 */
	function mainPrice( $form ) {
		var $scope = $form.closest( '.product, [id^="product-"]' );
		if ( ! $scope.length ) {
			$scope = $( document.body );
		}
		var $marked = $scope.find( '[data-vsw-price]' ).first();
		if ( $marked.length ) {
			return $marked;
		}
		return $scope
			.find( '.price' )
			.filter( function () {
				return ! $form[ 0 ].contains( this ) &&
					! $( this ).parent().closest( '.price, .products, .related, .upsells, .cross-sells, .elementor-loop-container, .jet-listing-grid, .jet-woo-products' ).length;
			} )
			.first();
	}

	// Show the chosen variation's price in the main price; restore the range when the choice is cleared.
	function showVariationPrice( $form, variation ) {
		var $price = mainPrice( $form );
		if ( ! $price.length ) {
			return;
		}
		if ( undefined === $price.data( 'vsw-original' ) ) {
			$price.data( 'vsw-original', $price.html() );
		}
		var html = variation && variation.price_html ? $( '<div>' ).html( variation.price_html ).find( '.price' ).html() : '';
		$price.html( html || $price.data( 'vsw-original' ) );
		// WooCommerce repeats the price above "Add to cart"; hide that copy while the main price shows it.
		$form.toggleClass( 'vsw-price-synced', !! html );
	}

	$( document )
		.on( 'found_variation', '.variations_form', function ( event, variation ) {
			showVariationPrice( $( this ), variation );
		} )
		.on( 'reset_data', '.variations_form', function () {
			showVariationPrice( $( this ), null );
		} )
		.on( 'change', '.vsw__input', function () {
			var $select = selectFor( $( this ).closest( '.vsw' ) );
			if ( $select.val() !== this.value ) {
				$select.val( this.value ).trigger( 'change' );
			}
		} )
		.on( 'wc_variation_form woocommerce_update_variation_values reset_data found_variation', '.variations_form', function () {
			sync( $( this ) );
		} );

	$( function () {
		$( '.variations_form' ).each( function () {
			sync( $( this ) );
		} );
	} );
} )( jQuery );
