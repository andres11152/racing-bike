(function ($) {
    'use strict';

    $(function () {
        // ---------------------------------------------------------
        // Sub-pestañas del editor
        // ---------------------------------------------------------
        $('.skc-md-subtabs').on('click', 'a[data-skc-md-tab]', function (e) {
            e.preventDefault();
            var target = $(this).data('skc-md-tab');

            $('.skc-md-subtabs a').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.skc-md-tab-panel').attr('hidden', true);
            $('#' + target).attr('hidden', false);
        });

        // ---------------------------------------------------------
        // Selector de color
        // ---------------------------------------------------------
        if ($.fn.wpColorPicker) {
            $('.skc-md-color').wpColorPicker({
                change: function () {
                    setTimeout(updatePreview, 10);
                },
            });
        }

        // ---------------------------------------------------------
        // Media uploader para la imagen de la campaña
        // ---------------------------------------------------------
        // El logo de marca vive dentro del mismo contenedor que la imagen,
        // así que lo guardamos para volver a insertarlo al cambiarla.
        var watermarkHtml = $('#skc-md-preview-image').find('.skc-md-watermark').prop('outerHTML') || '';

        function setPreviewImage(url) {
            $('#skc-md-preview-image').html(url ? '<img src="' + url + '" />' : '');
            if (url && watermarkHtml) {
                $('#skc-md-preview-image').append(watermarkHtml);
            }
        }

        var mediaFrame;
        $('#skc-md-select-image').on('click', function (e) {
            e.preventDefault();
            if (mediaFrame) {
                mediaFrame.open();
                return;
            }
            mediaFrame = wp.media({
                title: (window.skcMdAdmin && skcMdAdmin.selectImageTitle) || 'Selecciona una imagen',
                multiple: false,
            });
            mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                $('#image_id').val(attachment.id);
                var thumbUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                $('#skc-md-image-preview').html('<img src="' + thumbUrl + '" style="max-width:120px;" />');
                setPreviewImage(attachment.url);
            });
            mediaFrame.open();
        });

        // Selector de logo (Ajustes): acepta imágenes, incluidos SVG.
        var watermarkFrame;
        $('#skc-md-select-watermark').on('click', function (e) {
            e.preventDefault();
            if (watermarkFrame) {
                watermarkFrame.open();
                return;
            }
            watermarkFrame = wp.media({
                title: (window.skcMdAdmin && skcMdAdmin.selectImageTitle) || 'Selecciona una imagen',
                multiple: false,
                library: { type: 'image' },
            });
            watermarkFrame.on('select', function () {
                var attachment = watermarkFrame.state().get('selection').first().toJSON();
                $('#watermark_logo').val(attachment.url);
            });
            watermarkFrame.open();
        });

        $('#skc-md-clear-watermark').on('click', function (e) {
            e.preventDefault();
            $('#watermark_logo').val('');
        });

        $('#skc-md-remove-image').on('click', function (e) {
            e.preventDefault();
            $('#image_id').val('');
            $('#skc-md-image-preview').empty();
            setPreviewImage('');
        });

        // ---------------------------------------------------------
        // Vista previa en vivo
        // ---------------------------------------------------------
        var SKINS = ['center', 'slide-in', 'bottom-bar', 'fullscreen', 'sidebar'];

        function updatePreview() {
            var $preview = $('#skc-md-live-preview');
            if (!$preview.length) {
                return;
            }

            $('#skc-md-preview-headline').text($('#headline').val());
            $('#skc-md-preview-subtext').text($('#subtext').val());
            $('#skc-md-preview-button').text($('#button_text').val());

            // Cambia el skin de la vista previa en vivo: quita cualquier
            // clase skc-md-skin-* anterior y pone la seleccionada, así se ve
            // realmente cómo se comporta cada skin (centro, lateral, etc.).
            var skin = $('#skin').val();
            SKINS.forEach(function (s) {
                $preview.removeClass('skc-md-skin-' + s);
            });
            $preview.addClass('skc-md-skin-' + skin);

            var accent = $('#accent').val();
            var bg = $('#bg_color').val();
            var text = $('#text_color').val();

            $preview.css({
                '--skc-md-accent': accent,
                '--skc-md-bg': bg,
                '--skc-md-text': text,
            });

            // Además de la variable CSS, fijamos el color inline directamente:
            // wp-admin trae sus propias reglas para h2 (ej. ".wrap h2") con más
            // especificidad que una clase nuestra, así que sin esto el titular
            // de la vista previa se queda con el color gris de wp-admin aunque
            // la variable ya esté actualizada. El estilo inline siempre gana.
            $('#skc-md-preview-panel').css({ background: bg, color: text });
            $('#skc-md-preview-headline, #skc-md-preview-subtext').css('color', text);
            $('#skc-md-preview-button').css('background', accent);
        }

        $(document).on('input change', '.skc-md-preview-field', updatePreview);
        updatePreview();

        // ---------------------------------------------------------
        // Editor de variantes A/B
        // ---------------------------------------------------------
        var $variantsWrap = $('#skc-md-variants');
        var $variantsInput = $('#variants_json');

        function loadVariants() {
            var variants = [];
            try {
                variants = JSON.parse($variantsInput.val() || '[]');
            } catch (e) {
                variants = [];
            }
            $variantsWrap.empty();
            variants.forEach(renderVariantRow);
        }

        function renderVariantRow(variant) {
            variant = variant || { key: 'v' + Date.now().toString(36), label: '', weight: 1, headline: '', subtext: '', button_text: '' };

            var row = $('<div class="skc-md-variant-row"></div>');
            row.append($('<input type="text" placeholder="Etiqueta (ej: Variante B)" />').val(variant.label).data('field', 'label'));
            row.append($('<input type="number" min="1" placeholder="Peso" />').val(variant.weight).data('field', 'weight'));
            row.append($('<button type="button" class="button skc-md-remove-variant">&times;</button>'));
            row.append($('<input type="text" placeholder="Titular alternativo" />').val(variant.headline).data('field', 'headline'));
            row.append($('<input type="text" placeholder="Subtexto alternativo" />').val(variant.subtext).data('field', 'subtext'));
            row.append($('<input type="text" placeholder="Texto de botón alternativo" />').val(variant.button_text).data('field', 'button_text'));
            row.data('key', variant.key);

            row.find('.skc-md-remove-variant').on('click', function () {
                row.remove();
                syncVariants();
            });
            row.find('input').on('input', syncVariants);

            $variantsWrap.append(row);
        }

        function syncVariants() {
            var variants = [];
            $variantsWrap.find('.skc-md-variant-row').each(function () {
                var $row = $(this);
                variants.push({
                    key: $row.data('key'),
                    label: $row.find('[data-field="label"]').val(),
                    weight: parseInt($row.find('[data-field="weight"]').val(), 10) || 1,
                    headline: $row.find('[data-field="headline"]').val(),
                    subtext: $row.find('[data-field="subtext"]').val(),
                    button_text: $row.find('[data-field="button_text"]').val(),
                });
            });
            $variantsInput.val(JSON.stringify(variants));
        }

        $('#skc-md-add-variant').on('click', function () {
            renderVariantRow();
            syncVariants();
        });

        if ($variantsInput.length) {
            loadVariants();
        }

        // Sincroniza el JSON justo antes de enviar, por si algún campo no disparó "input".
        $('#skc-md-editor-form').on('submit', syncVariants);
    });
})(jQuery);
