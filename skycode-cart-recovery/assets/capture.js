( function ( $ ) {
	'use strict';

	if ( typeof rbCrCapture === 'undefined' ) {
		return;
	}

	function sendCapture() {
		var email = $( '#billing_email' ).val();
		if ( ! email || email.indexOf( '@' ) === -1 ) {
			return;
		}

		$.post( rbCrCapture.ajaxUrl, {
			nonce: rbCrCapture.nonce,
			email: email,
			phone: $( '#billing_phone' ).val() || '',
			first_name: $( '#billing_first_name' ).val() || ''
		} );
	}

	$( document.body ).on( 'blur', '#billing_email', sendCapture );
	$( document.body ).on( 'blur', '#billing_phone', sendCapture );

} )( jQuery );
