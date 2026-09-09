function initRbSizeCalculator() {
    const modal = document.getElementById('rb-size-finder-modal');
    if (!modal) return;

    // Elements
    const heightInput = document.getElementById('rb-height-range');
    const heightDisplay = document.getElementById('rb-height-display');
    const inseamInput = document.getElementById('rb-inseam-range');
    const inseamDisplay = document.getElementById('rb-inseam-display');
    const advancedToggle = document.getElementById('rb-advanced-toggle');
    const advancedFields = document.getElementById('rb-advanced-fields');

    const disciplineBtns = modal.querySelectorAll('.rb-discipline-btn');
    const recSizeEl = document.getElementById('rb-modal-recommended-size');
    const recDescEl = document.getElementById('rb-modal-recommended-desc');
    const applyBtn = document.getElementById('rb-modal-apply-btn');

    // Current state
    let selectedDiscipline = 'road';
    let currentSizeLetter = 'M';

    // Bloquea el scroll de fondo mientras el modal está abierto: en móvil,
    // sin esto la página detrás se movía junto con el gesto de scroll dentro
    // del modal (o directamente en vez de él).
    function openModal() {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        calculateSize();
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Show/hide Modal listeners
    const openTriggers = document.querySelectorAll('[data-open-size-finder]');
    openTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
        });
    });

    const closeTriggers = modal.querySelectorAll('[data-close-size-finder]');
    closeTriggers.forEach(trigger => {
        trigger.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });

    // Update displays & recalculate
    if (heightInput && heightDisplay) {
        heightInput.addEventListener('input', (e) => {
            heightDisplay.textContent = e.target.value;
            calculateSize();
        });
    }

    if (inseamInput && inseamDisplay) {
        inseamInput.addEventListener('input', (e) => {
            inseamDisplay.textContent = e.target.value;
            calculateSize();
        });
    }

    // Toggle advanced fields
    if (advancedToggle && advancedFields) {
        advancedToggle.addEventListener('change', (e) => {
            if (e.target.checked) {
                advancedFields.style.display = 'block';
            } else {
                advancedFields.style.display = 'none';
            }
            calculateSize();
        });
    }

    // Discipline button clicks
    disciplineBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            disciplineBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedDiscipline = btn.getAttribute('data-discipline');
            calculateSize();
        });
    });

    // Main calculation logic
    function calculateSize() {
        if (!heightInput || !recSizeEl || !recDescEl) return;

        const height = parseInt(heightInput.value, 10);
        let inseam = parseInt(inseamInput.value, 10);

        // If advanced biomechanics is off, estimate inseam (approx 45.7% of height)
        if (advancedToggle && !advancedToggle.checked) {
            inseam = Math.round(height * 0.457);
            if (inseamInput) {
                inseamInput.value = inseam;
                if (inseamDisplay) inseamDisplay.textContent = inseam;
            }
        }

        let letter = 'M';
        let frameSizeDesc = '';
        let longDesc = '';

        // El catálogo en Colombia sólo se fabrica/importa de XS a L — no hay
        // 2XS, XL ni 2XL, así que el resultado se acota a ese rango en vez
        // de recomendar una talla que no existe para comprar.
        if (selectedDiscipline === 'road') {
            const cm = Math.round(inseam * 0.67);
            frameSizeDesc = `${cm} cm`;

            if (cm < 52) {
                letter = 'XS';
                longDesc = 'Talla XS (47-51 cm). Recomendado para una conducción ágil y compacta en asfalto.';
            } else if (cm < 54) {
                letter = 'S';
                longDesc = 'Talla S (52-53 cm). Equilibrio perfecto entre aerodinámica y reactividad en carretera.';
            } else if (cm < 56) {
                letter = 'M';
                longDesc = 'Talla M (54-55 cm). El estándar de oro: balance óptimo de rigidez, comodidad y velocidad.';
            } else {
                letter = 'L';
                longDesc = 'Talla L (56 cm en adelante). Máxima estabilidad y potencia de palanca en planos y descensos rápidos.';
            }
        } else if (selectedDiscipline === 'mtb') {
            const inches = Math.round(((inseam * 0.67 * 0.3937) - 4) * 2) / 2;
            frameSizeDesc = `${inches}"`;

            if (inches < 15) {
                letter = 'XS';
                longDesc = 'Talla XS (13-14"). Excelente altura libre para descensos técnicos y senderos sinuosos.';
            } else if (inches < 17) {
                letter = 'S';
                longDesc = 'Talla S (15-16"). Geometría juguetona y reactiva en terrenos de montaña intermedios.';
            } else if (inches < 19) {
                letter = 'M';
                longDesc = 'Talla M (17-18"). Control preciso y estabilidad óptima en ascensos y senderos.';
            } else {
                letter = 'L';
                longDesc = 'Talla L (19" en adelante). Mayor tracción y estabilidad en altas velocidades campo traviesa.';
            }
        } else { // Gravel
            const cm = Math.round(inseam * 0.63);
            frameSizeDesc = `${cm} cm`;

            if (cm < 50) {
                letter = 'XS';
                longDesc = 'Talla XS (47-49 cm). Diseñada para comodidad y respuesta en terrenos mixtos.';
            } else if (cm < 53) {
                letter = 'S';
                longDesc = 'Talla S (50-52 cm). Óptimo confort en aventuras de gravel y asfalto rugoso.';
            } else if (cm < 56) {
                letter = 'M';
                longDesc = 'Talla M (53-55 cm). Balance ideal entre postura erguida de fondo y eficiencia.';
            } else {
                letter = 'L';
                longDesc = 'Talla L (56 cm en adelante). Diseñada para largas distancias con gran estabilidad de rodadura.';
            }
        }

        currentSizeLetter = letter;
        recSizeEl.textContent = `TALLA ${letter} (${frameSizeDesc})`;
        recDescEl.textContent = longDesc;

        // Persistir la talla recomendada en localStorage y cookies
        localStorage.setItem('rb_user_bike_size', JSON.stringify({
            discipline: selectedDiscipline,
            size: letter,
            desc: frameSizeDesc,
            timestamp: Date.now()
        }));
        document.cookie = `rb_user_bike_size=${letter};path=/;max-age=31536000;SameSite=Lax`;

        // Lanzar un evento global para actualizar componentes reactivos sin refrescar la página
        window.dispatchEvent(new CustomEvent('rb_size_calculated', { 
            detail: { size: letter, discipline: selectedDiscipline, desc: frameSizeDesc } 
        }));

        if (applyBtn) {
            applyBtn.textContent = `Seleccionar Talla ${letter} e ir a Comprar`;
        }
    }

    // Apply button click
    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            closeModal();

            // Find swatch and click it
            const swatches = [...document.querySelectorAll('.rb-swatch')];
            let matchedSwatch = swatches.find(s => {
                const txt = (s.textContent || '').trim().toUpperCase();
                return txt === currentSizeLetter || txt.includes(currentSizeLetter);
            });

            if (matchedSwatch) {
                matchedSwatch.click();
            } else {
                // WooCommerce standard select option fallback
                const selects = document.querySelectorAll('.variations select');
                let foundOption = false;
                selects.forEach(select => {
                    const option = [...select.options].find(opt => opt.value.toUpperCase().includes(currentSizeLetter));
                    if (option) {
                        select.value = option.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        foundOption = true;
                    }
                });

                // If no product option found, redirect to shop with filter
                if (!foundOption) {
                    // El filtro real del catálogo es "filter_talla-cuadro" (taxonomía
                    // pa_talla-cuadro); "filter_size" no existe y no filtraba nada.
                    window.location.href = `/tienda/?filter_talla-cuadro=${currentSizeLetter.toLowerCase()}`;
                }
            }
        });
    }
}

// Execute setup
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRbSizeCalculator);
} else {
    initRbSizeCalculator();
}
