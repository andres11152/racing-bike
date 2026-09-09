( function ( $ ) {
    'use strict';

    $( function () {
        // Selector de color nativo, sin dependencias externas.
        $( '.skc-mm-color-picker' ).each( function () {
            var $text = $( this );
            var $color = $( '<input type="color">' ).val( $text.val() || '#000000' );
            $color.on( 'input', function () {
                $text.val( $( this ).val() );
            } );
            $text.after( $color );
        } );

        // Media uploader para el logo.
        var frame;
        $( '#skc-mm-logo-select' ).on( 'click', function ( e ) {
            e.preventDefault();
            if ( frame ) {
                frame.open();
                return;
            }
            frame = wp.media( {
                title: skcMmAdmin.selectLogoTitle,
                multiple: false,
                library: { type: 'image' }
            } );
            frame.on( 'select', function () {
                var attachment = frame.state().get( 'selection' ).first().toJSON();
                $( '#skc-mm-logo-id' ).val( attachment.id );
                $( '#skc-mm-logo-preview' ).attr( 'src', attachment.url ).show();
            } );
            frame.open();
        } );

        $( '#skc-mm-logo-remove' ).on( 'click', function ( e ) {
            e.preventDefault();
            $( '#skc-mm-logo-id' ).val( '' );
            $( '#skc-mm-logo-preview' ).hide();
        } );

        // Mostrar/ocultar ayuda y campos de fondo según el tipo elegido.
        function toggleBgHint() {
            var type = $( '#skc-mm-bg-type' ).val();
            var label = skcMmAdmin.bgColorLabel;
            if ( 'image' === type ) {
                label = skcMmAdmin.bgImageLabel;
            } else if ( 'video' === type ) {
                label = skcMmAdmin.bgVideoLabel;
            }
            $( '#skc-mm-bg-value-wrap span' ).text( label );

            var isVideo = 'video' === type;
            $( '#skc-mm-bg-value-mobile-wrap, #skc-mm-bg-poster-wrap' ).toggle( isVideo );
        }
        $( '#skc-mm-bg-type' ).on( 'change', toggleBgHint );
        toggleBgHint();
    } );
} )( jQuery );
