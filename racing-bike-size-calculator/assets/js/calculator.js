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
    let currentSizeNumeric = null;

    // Algunos productos (bicicletas de ruta Trek/Orbea, MTB Zebra/Alligator/
    // Monkey) no usan tallas por letra (XS/S/M/L) sino por número: cm de
    // cuadro en ruta/gravel (44-62) o pulgadas en MTB (13-19). El cálculo ya
    // arma ese número (variable `cm`/`inches` más abajo); antes solo se
    // usaba para el texto mostrado y se descartaba para elegir/aplicar la
    // talla, así que en esos productos nunca se encontraba una opción que
    // coincidiera con la letra. Esta función intenta primero una
    // coincidencia numérica exacta o más cercana entre las opciones
    // disponibles, y solo si ninguna opción es numérica cae a comparar por
    // letra como antes.
    function pickBestSizeOption(items, getText) {
        if (!items.length) return null;

        const numericItems = items
            .map((item) => ({ item, num: parseFloat(String(getText(item)).trim().replace(',', '.')) }))
            .filter(({ num }) => !Number.isNaN(num));

        // Solo se confía en la coincidencia numérica si TODAS las opciones
        // del producto son números — si hay una mezcla (no debería pasar,
        // pero por seguridad) se prefiere el criterio de letra de siempre.
        if (currentSizeNumeric != null && numericItems.length === items.length) {
            let best = numericItems[0];
            let bestDiff = Math.abs(best.num - currentSizeNumeric);
            numericItems.forEach((candidate) => {
                const diff = Math.abs(candidate.num - currentSizeNumeric);
                if (diff < bestDiff) {
                    best = candidate;
                    bestDiff = diff;
                }
            });
            return best.item;
        }

        return items.find((item) => {
            const txt = String(getText(item)).trim().toUpperCase();
            return txt === currentSizeLetter || txt.includes(currentSizeLetter);
        }) || null;
    }

    // Si el modal se abre desde la ficha de un producto, ese producto ya
    // tiene sus propias tallas reales en pantalla (los swatches .rb-swatch,
    // o si el navegador no corrió ese script, el <select> nativo de
    // WooCommerce). Antes el resultado del modal siempre mostraba una talla
    // "genérica" (la letra/número calculado en el vacío) sin relación con
    // lo que el producto realmente vende — en un producto de 47 a 60 cm
    // seguía hablando de XXS-XL. Esto lee esas opciones reales para que el
    // resultado muestre y seleccione una talla que de verdad existe ahí.
    function getRealProductSizeOptions() {
        const swatches = [...document.querySelectorAll('.rb-swatch')];
        if (swatches.length) {
            return { items: swatches, getText: (s) => s.textContent || '' };
        }

        const selects = [...document.querySelectorAll('.variations select')];
        for (const select of selects) {
            const options = [...select.options].filter((opt) => opt.value !== '');
            if (options.length) {
                return { items: options, getText: (opt) => opt.textContent || opt.value };
            }
        }

        return null;
    }

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

    // Show/hide Modal listeners.
    //
    // Delegado en document en vez de enganchar cada [data-open-size-finder]
    // por separado: el banner de recomendación de app.js reemplaza su botón
    // con banner.innerHTML cada vez que se recalcula una talla (para pasar
    // de "Calcular talla" a "Recalcular"), y ese botón nuevo nunca había
    // tenido el listener — por eso el modal abría la primera vez y dejaba
    // de abrir después. Con delegación no importa cuándo se creó el botón.
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-open-size-finder]')) {
            e.preventDefault();
            openModal();
        }
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal || e.target.closest('[data-close-size-finder]')) {
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
        let numericSize = null;

        // El catálogo real va de XXS a XL en tallas por letra (confirmado en
        // WooCommerce > Atributos > Talla: hay productos con XXS y con XL,
        // no solo XS-L como asumía la versión anterior de este archivo) y de
        // 13" a 62 cm en tallas numéricas (bicicletas importadas: Trek/Orbea
        // en ruta, algunas MTB). Los 6 tramos de letra de cada disciplina
        // cubren ese rango completo; el valor numérico (`cm`/`inches`) se
        // calcula siempre de forma continua, sin acotar, para que la
        // selección en productos de talla numérica (más abajo, en
        // pickBestSizeOption) pueda buscar la opción más cercana en todo el
        // rango real del catálogo, no solo dentro de un tramo de letra.
        if (selectedDiscipline === 'road') {
            const cm = Math.round(inseam * 0.67);
            frameSizeDesc = `${cm} cm`;
            numericSize = cm;

            if (cm < 50) {
                letter = 'XXS';
                longDesc = 'Talla XXS (44-49 cm). La más compacta del catálogo, para máxima maniobrabilidad en ciclistas de baja estatura.';
            } else if (cm < 52) {
                letter = 'XS';
                longDesc = 'Talla XS (50-51 cm). Recomendado para una conducción ágil y compacta en asfalto.';
            } else if (cm < 54) {
                letter = 'S';
                longDesc = 'Talla S (52-53 cm). Equilibrio perfecto entre aerodinámica y reactividad en carretera.';
            } else if (cm < 56) {
                letter = 'M';
                longDesc = 'Talla M (54-55 cm). El estándar de oro: balance óptimo de rigidez, comodidad y velocidad.';
            } else if (cm < 58) {
                letter = 'L';
                longDesc = 'Talla L (56-57 cm). Máxima estabilidad y potencia de palanca en planos y descensos rápidos.';
            } else {
                letter = 'XL';
                longDesc = 'Talla XL (58 cm en adelante). Mayor alcance y estabilidad para ciclistas de estatura alta.';
            }
        } else if (selectedDiscipline === 'mtb') {
            const inches = Math.round(((inseam * 0.67 * 0.3937) - 4) * 2) / 2;
            frameSizeDesc = `${inches}"`;
            numericSize = inches;

            if (inches < 13) {
                letter = 'XXS';
                longDesc = 'Talla XXS (menos de 13"). Máxima altura libre y agilidad para ciclistas de baja estatura.';
            } else if (inches < 15) {
                letter = 'XS';
                longDesc = 'Talla XS (13-14"). Excelente altura libre para descensos técnicos y senderos sinuosos.';
            } else if (inches < 17) {
                letter = 'S';
                longDesc = 'Talla S (15-16"). Geometría juguetona y reactiva en terrenos de montaña intermedios.';
            } else if (inches < 19) {
                letter = 'M';
                longDesc = 'Talla M (17-18"). Control preciso y estabilidad óptima en ascensos y senderos.';
            } else if (inches < 21) {
                letter = 'L';
                longDesc = 'Talla L (19-20"). Mayor tracción y estabilidad en altas velocidades campo traviesa.';
            } else {
                letter = 'XL';
                longDesc = 'Talla XL (21" en adelante). Máximo alcance para ciclistas de estatura alta en montaña.';
            }
        } else { // Gravel
            const cm = Math.round(inseam * 0.63);
            frameSizeDesc = `${cm} cm`;
            numericSize = cm;

            if (cm < 47) {
                letter = 'XXS';
                longDesc = 'Talla XXS (menos de 47 cm). La más compacta, para máxima maniobrabilidad en terrenos mixtos.';
            } else if (cm < 50) {
                letter = 'XS';
                longDesc = 'Talla XS (47-49 cm). Diseñada para comodidad y respuesta en terrenos mixtos.';
            } else if (cm < 53) {
                letter = 'S';
                longDesc = 'Talla S (50-52 cm). Óptimo confort en aventuras de gravel y asfalto rugoso.';
            } else if (cm < 56) {
                letter = 'M';
                longDesc = 'Talla M (53-55 cm). Balance ideal entre postura erguida de fondo y eficiencia.';
            } else if (cm < 59) {
                letter = 'L';
                longDesc = 'Talla L (56-58 cm). Diseñada para largas distancias con gran estabilidad de rodadura.';
            } else {
                letter = 'XL';
                longDesc = 'Talla XL (59 cm en adelante). Mayor alcance para ciclistas de estatura alta en gravel.';
            }
        }

        currentSizeLetter = letter;
        currentSizeNumeric = numericSize;

        // Si estamos en la ficha de un producto, mostrar y usar la talla
        // REAL que ese producto vende (p.ej. "52 cm" en un Trek/Orbea de
        // 47 a 60), no la talla genérica XXS-XL calculada en el vacío —
        // antes el resultado del modal ignoraba por completo qué tallas
        // tenía disponibles la página en la que se abrió.
        let displaySizeLabel = `${letter} (${frameSizeDesc})`;
        let matchedRealText = null;
        let availableSizesText = '';

        const pageOptions = getRealProductSizeOptions();
        if (pageOptions) {
            const matched = pickBestSizeOption(pageOptions.items, pageOptions.getText);
            if (matched) {
                matchedRealText = String(pageOptions.getText(matched)).trim();
                const isNumericLabel = /^\d+([.,]\d+)?$/.test(matchedRealText);
                const unit = selectedDiscipline === 'mtb' ? '"' : ' cm';
                displaySizeLabel = isNumericLabel ? `${matchedRealText}${unit}` : matchedRealText;

                if (isNumericLabel) {
                    availableSizesText = pageOptions.items
                        .map((item) => String(pageOptions.getText(item)).trim())
                        .join(`${unit}, `) + unit;
                }
            }
        }

        recSizeEl.textContent = `TALLA ${displaySizeLabel}`;
        recDescEl.textContent = matchedRealText
            ? `Esta es la talla disponible más cercana a tu medida en este producto${availableSizesText ? ` (opciones: ${availableSizesText})` : ''}. ${longDesc}`
            : longDesc;

        // Persistir la talla recomendada en localStorage y cookies. Se
        // guarda también el número (cm/pulgadas) además de la letra: los
        // productos con tallas numéricas (Trek/Orbea en ruta, algunas MTB)
        // se seleccionan por ese número, no por la letra.
        localStorage.setItem('rb_user_bike_size', JSON.stringify({
            discipline: selectedDiscipline,
            size: letter,
            numeric: numericSize,
            desc: frameSizeDesc,
            timestamp: Date.now()
        }));
        document.cookie = `rb_user_bike_size=${letter};path=/;max-age=31536000;SameSite=Lax`;

        // Lanzar un evento global para actualizar componentes reactivos sin
        // refrescar la página — esto es lo que hace que el banner de la
        // ficha de producto (app.js) seleccione en vivo el swatch real
        // mientras se mueve el slider, no solo al pulsar "Aplicar".
        window.dispatchEvent(new CustomEvent('rb_size_calculated', {
            detail: { size: letter, numeric: numericSize, discipline: selectedDiscipline, desc: frameSizeDesc }
        }));

        if (applyBtn) {
            applyBtn.textContent = `Seleccionar Talla ${displaySizeLabel} e ir a Comprar`;
        }
    }

    // Apply button click
    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            closeModal();

            // Buscar swatch: coincidencia numérica más cercana si el
            // producto usa tallas por número (Trek/Orbea en ruta, algunas
            // MTB), o por letra si no.
            const swatches = [...document.querySelectorAll('.rb-swatch')];
            const matchedSwatch = pickBestSizeOption(swatches, (s) => s.textContent || '');

            if (matchedSwatch) {
                matchedSwatch.click();
            } else {
                // WooCommerce standard select option fallback
                const selects = document.querySelectorAll('.variations select');
                let foundOption = false;
                selects.forEach(select => {
                    const options = [...select.options].filter((opt) => opt.value !== '');
                    const option = pickBestSizeOption(options, (opt) => opt.textContent || opt.value);
                    if (option) {
                        select.value = option.value;
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        foundOption = true;
                    }
                });

                // If no product option found, redirect to shop with filter.
                // El filtro real del catálogo es "filter_talla" (taxonomía
                // pa_talla) — "filter_talla-cuadro" no tiene ningún producto
                // asignado (pa_talla-cuadro quedó sin uso tras la migración
                // de tallas a pa_talla) y nunca filtraba nada.
                if (!foundOption) {
                    const filterValue = currentSizeNumeric != null ? currentSizeNumeric : currentSizeLetter.toLowerCase();
                    window.location.href = `/tienda/?filter_talla=${filterValue}`;
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
