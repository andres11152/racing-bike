function initRbReviews() {
    const section = document.querySelector('[data-rb-reviews]');
    if (!section || typeof rbReviews === 'undefined') return;

    const productId = section.getAttribute('data-product-id');
    const modal = section.querySelector('[data-rb-reviews-modal]');
    const openBtn = section.querySelector('[data-rb-open-form]');
    const closeBtn = section.querySelector('[data-rb-close-form]');
    const form = section.querySelector('[data-rb-review-form]');
    const statusEl = section.querySelector('[data-rb-form-status]');
    const submitBtn = section.querySelector('[data-rb-submit-btn]');
    const list = section.querySelector('[data-rb-reviews-list]');
    const carouselRoot = section.querySelector('[data-rb-reviews-carousel]');
    const loadMoreBtn = section.querySelector('[data-rb-load-more]');
    const filterBtn = section.querySelector('[data-rb-filter-photos]');
    const ratingInput = section.querySelector('[data-rb-rating-input]');
    const photoInput = section.querySelector('[data-rb-photo-input]');
    const photoPreview = section.querySelector('[data-rb-photo-preview]');
    const dropzone = section.querySelector('[data-rb-dropzone]');
    const dropzoneText = section.querySelector('[data-rb-dropzone-text]');
    const lightboxOverlay = section.querySelector('[data-rb-lightbox-overlay]');
    const lightboxImg = section.querySelector('[data-rb-lightbox-img]');
    const lightboxClose = section.querySelector('[data-rb-lightbox-close]');

    let photosOnly = false;

    // --- Modal open/close ---
    const openModal = () => {
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    };
    const closeModal = () => {
        modal.hidden = true;
        document.body.style.overflow = '';
    };

    if (openBtn) openBtn.addEventListener('click', openModal);

    // Llegada desde el email de solicitud post-compra: abrir directo el formulario.
    if (rbReviews.autoOpen) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        openModal();

        if (window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('rb_write_review');
            window.history.replaceState({}, '', url.toString());
        }
    }
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }

    // --- Rating stars input ---
    if (ratingInput) {
        const stars = Array.from(ratingInput.querySelectorAll('[data-value]'));
        const hidden = ratingInput.querySelector('input[type="hidden"]');

        const paint = (value) => {
            stars.forEach((star) => {
                const v = parseInt(star.getAttribute('data-value'), 10);
                star.classList.toggle('is-active', v <= value);
            });
        };

        stars.forEach((star) => {
            star.addEventListener('click', () => {
                const value = parseInt(star.getAttribute('data-value'), 10);
                hidden.value = value;
                paint(value);
            });
            star.addEventListener('mouseenter', () => {
                paint(parseInt(star.getAttribute('data-value'), 10));
            });
        });

        ratingInput.addEventListener('mouseleave', () => {
            paint(parseInt(hidden.value, 10) || 0);
        });
    }

    // --- Zona de fotos: vista previa, límite, y texto propio en español
    // (nada de esto depende del "Choose Files" nativo del navegador) ---
    const defaultDropzoneText = dropzoneText ? dropzoneText.innerHTML : '';

    const updateDropzoneText = (count) => {
        if (!dropzoneText) return;

        if (count > 0) {
            dropzoneText.textContent = count === 1
                ? '1 foto seleccionada'
                : `${count} fotos seleccionadas`;
        } else {
            dropzoneText.innerHTML = defaultDropzoneText;
        }
    };

    const handlePhotoFiles = (fileList) => {
        const files = Array.from(fileList || []);

        if (files.length > 3) {
            statusEl.textContent = rbReviews.i18n.maxPhotos;
            statusEl.setAttribute('data-state', 'error');
            photoInput.value = '';
            photoPreview.innerHTML = '';
            updateDropzoneText(0);
            return;
        }

        photoPreview.innerHTML = '';
        updateDropzoneText(files.length);

        files.forEach((file) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.createElement('img');
                img.src = e.target.result;
                photoPreview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    };

    if (photoInput) {
        photoInput.addEventListener('change', () => handlePhotoFiles(photoInput.files));
    }

    if (dropzone && photoInput) {
        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend'].forEach((eventName) => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('is-dragover');

            const dropped = e.dataTransfer && e.dataTransfer.files;
            if (!dropped || !dropped.length) return;

            photoInput.files = dropped;
            handlePhotoFiles(dropped);
        });
    }

    // --- Form submit ---
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const ratingValue = parseInt(form.querySelector('input[name="rating"]').value, 10) || 0;
            if (ratingValue < 1) {
                statusEl.textContent = rbReviews.i18n.error;
                statusEl.setAttribute('data-state', 'error');
                return;
            }

            const formData = new FormData(form);
            formData.append('action', 'rb_submit_review');
            formData.append('nonce', rbReviews.nonce);

            // Del enlace del correo de solicitud post-compra: si están
            // presentes, el backend los valida contra el pedido real y sólo
            // entonces marca la reseña como "compra verificada" de forma
            // que no se puede falsificar escribiendo cualquier correo.
            if (rbReviews.reviewOrder) {
                formData.append('rb_order', rbReviews.reviewOrder);
                formData.append('rb_token', rbReviews.reviewToken);
            }

            submitBtn.disabled = true;
            statusEl.removeAttribute('data-state');
            statusEl.textContent = rbReviews.i18n.sending;

            fetch(rbReviews.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
                .then((res) => res.json())
                .then((data) => {
                    submitBtn.disabled = false;

                    if (data && data.success) {
                        statusEl.textContent = data.data.message || rbReviews.i18n.sent;
                        statusEl.setAttribute('data-state', 'success');
                        form.reset();
                        photoPreview.innerHTML = '';
                        updateDropzoneText(0);

                        const stars = ratingInput ? ratingInput.querySelectorAll('[data-value]') : [];
                        stars.forEach((star) => star.classList.remove('is-active'));

                        setTimeout(closeModal, 2000);
                    } else {
                        statusEl.textContent = (data && data.data && data.data.message) || rbReviews.i18n.error;
                        statusEl.setAttribute('data-state', 'error');
                    }
                })
                .catch(() => {
                    submitBtn.disabled = false;
                    statusEl.textContent = rbReviews.i18n.error;
                    statusEl.setAttribute('data-state', 'error');
                });
        });
    }

    // Reconstruye diapositivas y puntos del carrusel tras insertar reseñas
    // por AJAX — el motor de app.js (initCarousel) es idempotente: si ya
    // estaba inicializado, esto sólo refresca en vez de duplicar listeners.
    const refreshCarousel = () => {
        if (carouselRoot && typeof window.rbInitCarousel === 'function') {
            window.rbInitCarousel(carouselRoot);
        }
    };

    // --- Load more ---
    const bindLoadMore = (btn) => {
        if (!btn) return;

        btn.addEventListener('click', () => {
            const offset = parseInt(btn.getAttribute('data-offset'), 10) || 0;
            btn.disabled = true;
            btn.textContent = rbReviews.i18n.loadingMore;

            const formData = new FormData();
            formData.append('action', 'rb_load_reviews');
            formData.append('nonce', rbReviews.nonce);
            formData.append('product_id', productId);
            formData.append('offset', offset);
            formData.append('photos_only', photosOnly ? '1' : '');

            fetch(rbReviews.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
                .then((res) => res.json())
                .then((data) => {
                    if (data && data.success) {
                        list.insertAdjacentHTML('beforeend', data.data.html);
                        refreshCarousel();
                        const newOffset = offset + (data.data.html.match(/rb-review-item/g) || []).length;
                        btn.setAttribute('data-offset', newOffset);

                        if (!data.data.hasMore) {
                            btn.remove();
                        } else {
                            btn.disabled = false;
                        }
                    }
                })
                .finally(() => {
                    btn.textContent = btn.isConnected ? btn.textContent : '';
                    if (btn.isConnected) {
                        btn.disabled = false;
                    }
                });
        });
    };

    bindLoadMore(loadMoreBtn);

    // --- Filter: only reviews with photos ---
    if (filterBtn) {
        filterBtn.addEventListener('click', () => {
            photosOnly = !photosOnly;
            filterBtn.setAttribute('aria-pressed', photosOnly ? 'true' : 'false');

            const formData = new FormData();
            formData.append('action', 'rb_load_reviews');
            formData.append('nonce', rbReviews.nonce);
            formData.append('product_id', productId);
            formData.append('offset', 0);
            formData.append('photos_only', photosOnly ? '1' : '');

            fetch(rbReviews.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
                .then((res) => res.json())
                .then((data) => {
                    if (data && data.success) {
                        list.innerHTML = data.data.html;
                        refreshCarousel();

                        const oldBtn = section.querySelector('[data-rb-load-more]');
                        if (oldBtn) oldBtn.remove();

                        if (data.data.hasMore) {
                            const anchor = carouselRoot || list;
                            const wrap = section.querySelector('.rb-reviews__load-more-wrap') || (() => {
                                const w = document.createElement('div');
                                w.className = 'rb-reviews__load-more-wrap';
                                anchor.insertAdjacentElement('afterend', w);
                                return w;
                            })();

                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'rb-reviews__load-more';
                            btn.setAttribute('data-rb-load-more', '');
                            btn.setAttribute('data-offset', '5');
                            btn.textContent = 'Cargar más reseñas';
                            wrap.innerHTML = '';
                            wrap.appendChild(btn);
                            bindLoadMore(btn);
                        }
                    }
                });
        });
    }

    // --- "Leer más": expande el texto recortado a 4 líneas ---
    section.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-rb-read-more]');
        if (!btn) return;

        const text = btn.previousElementSibling;
        if (!text || !text.hasAttribute('data-rb-review-text')) return;

        const expanded = text.classList.toggle('is-expanded');
        btn.textContent = expanded
            ? (btn.getAttribute('data-less-label') || 'Leer menos')
            : (btn.getAttribute('data-more-label') || 'Leer más');
    });

    // --- Lightbox, con flechas entre las fotos de la MISMA reseña ---
    const lightboxPrev = section.querySelector('[data-rb-lightbox-prev]');
    const lightboxNext = section.querySelector('[data-rb-lightbox-next]');
    let lightboxGroup = [];
    let lightboxIndex = 0;

    const showLightboxIndex = (index) => {
        if (!lightboxGroup.length) return;
        lightboxIndex = (index + lightboxGroup.length) % lightboxGroup.length;
        lightboxImg.src = lightboxGroup[lightboxIndex];
        const multiple = lightboxGroup.length > 1;
        if (lightboxPrev) lightboxPrev.hidden = !multiple;
        if (lightboxNext) lightboxNext.hidden = !multiple;
    };

    const closeLightbox = () => {
        lightboxOverlay.hidden = true;
        lightboxImg.src = '';
        lightboxGroup = [];
    };

    section.addEventListener('click', (e) => {
        const trigger = e.target.closest('[data-rb-lightbox]');
        if (!trigger) return;

        // Todas las fotos dentro del mismo contenedor de la reseña forman el
        // grupo que se navega con las flechas del lightbox.
        const photosWrap = trigger.closest('.rb-review-item__photos') || trigger.parentElement;
        const siblings = photosWrap ? Array.from(photosWrap.querySelectorAll('[data-rb-lightbox]')) : [trigger];

        lightboxGroup = siblings.map((el) => el.getAttribute('data-rb-lightbox'));
        showLightboxIndex(siblings.indexOf(trigger));
        lightboxOverlay.hidden = false;
    });

    if (lightboxPrev) lightboxPrev.addEventListener('click', () => showLightboxIndex(lightboxIndex - 1));
    if (lightboxNext) lightboxNext.addEventListener('click', () => showLightboxIndex(lightboxIndex + 1));

    document.addEventListener('keydown', (e) => {
        if (lightboxOverlay.hidden) return;
        if (e.key === 'ArrowLeft') showLightboxIndex(lightboxIndex - 1);
        if (e.key === 'ArrowRight') showLightboxIndex(lightboxIndex + 1);
        if (e.key === 'Escape') closeLightbox();
    });

    if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
    }

    if (lightboxOverlay) {
        lightboxOverlay.addEventListener('click', (e) => {
            if (e.target === lightboxOverlay) {
                closeLightbox();
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRbReviews);
} else {
    initRbReviews();
}
