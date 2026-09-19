/* Variant Swatches: colour picker and media picker on attribute term screens. */
( function ( $ ) {
	'use strict';

	function initColor( $scope ) {
		$scope.find( '.vsw-color-field' ).not( '.wp-color-picker' ).wpColorPicker();
	}

	$( function () {
		initColor( $( document ) );

		$( document ).on( 'click', '.vsw-image-select', function ( e ) {
			e.preventDefault();
			var $control = $( this ).closest( '.vsw-image-control' );
			var frame = wp.media( {
				title: vswAdmin.chooseImage,
				button: { text: vswAdmin.useImage },
				library: { type: 'image' },
				multiple: false
			} );
			frame.on( 'select', function () {
				var image = frame.state().get( 'selection' ).first().toJSON();
				var url = image.sizes && image.sizes.thumbnail ? image.sizes.thumbnail.url : image.url;
				$control.find( '.vsw-image-id' ).val( image.id );
				$control.find( '.vsw-image-preview' ).html( $( '<img>', { src: url, width: 48, height: 48, alt: '' } ) );
				$control.find( '.vsw-image-remove' ).prop( 'hidden', false );
			} );
			frame.open();
		} );

		$( document ).on( 'click', '.vsw-image-remove', function ( e ) {
			e.preventDefault();
			var $control = $( this ).closest( '.vsw-image-control' );
			$control.find( '.vsw-image-id' ).val( '' );
			$control.find( '.vsw-image-preview' ).empty();
			$( this ).prop( 'hidden', true );
		} );

		// The "Add new term" form is submitted with AJAX and not reloaded: reset our fields afterwards.
		$( document ).ajaxComplete( function ( event, xhr, settings ) {
			if ( settings && typeof settings.data === 'string' && settings.data.indexOf( 'action=add-tag' ) !== -1 && ! $( '#ajax-response .error' ).length ) {
				$( '#addtag .vsw-color-field' ).wpColorPicker( 'color', '' ).val( '' );
				$( '#addtag .vsw-image-remove' ).trigger( 'click' );
			}
		} );
	} );
} )( jQuery );
