( function ( $ ) {
	'use strict';

	$( function () {
		// Inserta la etiqueta en el campo de asunto activo, en la posición del cursor.
		$( '.rb-cr-tag-btn' ).on( 'click', function () {
			var tag   = $( this ).data( 'tag' );
			var $field = $( this ).closest( 'form' ).find( 'input[name="subject"]' );
			var input  = $field.get( 0 );

			if ( ! input ) {
				return;
			}

			var start = input.selectionStart || input.value.length;
			var end   = input.selectionEnd || input.value.length;
			var value = input.value;

			input.value = value.slice( 0, start ) + tag + value.slice( end );
			input.focus();
			input.selectionStart = input.selectionEnd = start + tag.length;
		} );

		// Ordenar los pasos de la secuencia arrastrando.
		if ( $.fn.sortable ) {
			$( '.rb-cr-steps' ).sortable( {
				handle: '.rb-cr-step-head',
				axis: 'y',
				placeholder: 'rb-cr-step-placeholder'
			} );
		}
	} );

} )( jQuery );
