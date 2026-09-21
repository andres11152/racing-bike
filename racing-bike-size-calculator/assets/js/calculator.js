// Estima la entrepierna a partir de la estatura (aprox. 45.7% de la
// estatura) cuando el usuario no midió la suya con el ajuste biomecánico
// avanzado. Vive en el ámbito del módulo (no dentro de initRbSizeCalculator)
// para que cualquier otra página del sitio que necesite mostrar una talla
// de referencia use exactamente esta misma cuenta, no una copia a mano.
function rbEstimateInseamFromHeight(height) {
    return Math.round(height * 0.457);
}

// Calcula talla biomecánica para una disciplina específica. Única fuente de
// verdad de la relación estatura/entrepierna -> talla de marco: antes de
// esto, la página /encuentra-tu-talla/ tenía su propia tabla de estatura
// escrita a mano (XS 160-168cm, S 168-175cm...) que nunca se actualizó
// cuando este cálculo cambió a basarse en entrepierna — dos lugares
// calculando lo mismo con números distintos. Ahora vive en el ámbito del
// módulo y se expone en window.RBSizeCalculator para que esa página (o
// cualquier otra) construya su tabla de referencia llamando a esta misma
// función, nunca duplicándola.
function rbCalculateDisciplineSize(discipline, inseam) {
    let letter = 'M';
    let frameSizeDesc = '';
    let longDesc = '';
    let numericSize = null;

    if (discipline === 'road') {
        const cm = Math.round(inseam * 0.67);
        frameSizeDesc = `${cm} cm`;
        numericSize = cm;

        if (cm < 50) {
            letter = 'XXS';
            longDesc = 'Talla XXS (44-49 cm). La más compacta del catálogo, para ciclistas de baja estatura en ruta.';
        } else if (cm < 52) {
            letter = 'XS';
            longDesc = 'Talla XS (50-51 cm). Diseñada para máxima agilidad y reactividad en carretera.';
        } else if (cm < 54) {
            letter = 'S';
            longDesc = 'Talla S (52-53 cm). Equilibrio perfecto entre aerodinámica y reactividad en carretera.';
        } else if (cm < 56) {
            letter = 'M';
            longDesc = 'Talla M (54-55 cm). El estándar de oro: balance óptimo de rigidez, confort y velocidad.';
        } else if (cm < 58) {
            letter = 'L';
            longDesc = 'Talla L (56-57 cm). Máxima estabilidad y potencia de palanca en planos y descensos rápidos.';
        } else {
            letter = 'XL';
            longDesc = 'Talla XL (58 cm en adelante). Mayor alcance y estabilidad para ciclistas de estatura alta.';
        }
    } else if (discipline === 'mtb') {
        const inches = Math.round(((inseam * 0.67 * 0.3937) - 4) * 2) / 2;
        frameSizeDesc = `${inches}"`;
        numericSize = inches;

        if (inches < 13) {
            letter = 'XXS';
            longDesc = 'Talla XXS (menos de 13"). Máxima altura libre y control para senderos técnicos.';
        } else if (inches < 15) {
            letter = 'XS';
            longDesc = 'Talla XS (13-14"). Excelente maniobrabilidad en descensos y senderos estrechos.';
        } else if (inches < 17) {
            letter = 'S';
            longDesc = 'Talla S (15-16"). Geometría juguetona y reactiva en terrenos de montaña.';
        } else if (inches < 19) {
            letter = 'M';
            longDesc = 'Talla M (17-18"). Control preciso y estabilidad óptima en ascensos y descensos.';
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
            longDesc = 'Talla XXS (menos de 47 cm). Gran maniobrabilidad en terrenos mixtos.';
        } else if (cm < 50) {
            letter = 'XS';
            longDesc = 'Talla XS (47-49 cm). Diseñada para comodidad y respuesta en caminos destapados.';
        } else if (cm < 53) {
            letter = 'S';
            longDesc = 'Talla S (50-52 cm). Óptimo confort en aventuras de gravel y asfalto rugoso.';
        } else if (cm < 56) {
            letter = 'M';
            longDesc = 'Talla M (53-55 cm). Balance ideal entre postura erguida de fondo y velocidad.';
        } else if (cm < 59) {
            letter = 'L';
            longDesc = 'Talla L (56-58 cm). Gran estabilidad de rodadura para largas travesías.';
        } else {
            letter = 'XL';
            longDesc = 'Talla XL (59 cm en adelante). Mayor alcance para ciclistas de estatura alta en gravel.';
        }
    }

    return { letter, frameSizeDesc, longDesc, numericSize };
}

// API pública del plugin: cualquier vista del theme que necesite mostrar
// una talla o una tabla de referencia debe llamar a esto, nunca reescribir
// el cálculo. Se expone ANTES de initRbSizeCalculator (que corre en
// DOMContentLoaded) para que esté disponible tan pronto el script carga.
window.RBSizeCalculator = {
    calculateDisciplineSize: rbCalculateDisciplineSize,
    estimateInseamFromHeight: rbEstimateInseamFromHeight,
};

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
    //
    // Un producto puede tener varios atributos con swatches a la vez (Talla
    // Y Color, p.ej. "Casco RC" con L/M/S de talla y Blanco/Gris/Mate de
    // color) — todos comparten la misma clase .rb-swatch sin distinción,
    // así que sin filtrar por atributo esto también recogía los colores
    // ("MATE" contiene la letra "M" y se colaba como si fuera talla M).
    // initSwatches (app.js) marca cada swatch con su atributo real de
    // WooCommerce en data-attribute; solo se usan los que sean de talla.
    function isSizeAttributeName(name) {
        const n = (name || '').toLowerCase();
        return n.includes('talla') || n.includes('size');
    }

    function getRealProductSizeOptions() {
        const swatches = [...document.querySelectorAll('.rb-swatch')].filter((s) => isSizeAttributeName(s.dataset.attribute));
        if (swatches.length) {
            return { kind: 'swatch', items: swatches, getText: (s) => s.textContent || '' };
        }

        const selects = [...document.querySelectorAll('.variations select')].filter((s) => isSizeAttributeName(s.name || s.id));
        for (const select of selects) {
            const options = [...select.options].filter((opt) => opt.value !== '');
            if (options.length) {
                return { kind: 'select', select, items: options, getText: (opt) => opt.textContent || opt.value };
            }
        }

        return null;
    }

    // Detecta inteligentemente la disciplina del producto que se está viendo en la página
    function detectPageDiscipline() {
        // 1. Atributo explícito pasado por el tema
        const explicitEl = document.querySelector('[data-bike-discipline]');
        const disc = explicitEl?.dataset?.bikeDiscipline?.toLowerCase();
        if (disc && ['road', 'mtb', 'gravel'].includes(disc)) {
            return disc;
        }

        // 2. Heurística por textos de la página (título, migas de pan, categoría)
        const texts = [
            document.querySelector('h1')?.textContent || '',
            ...[...document.querySelectorAll('.breadcrumbs, nav[aria-label="Breadcrumb"], .rb-breadcrumbs, [data-rb-track-view]')].map((el) => el.textContent || el.dataset?.rbTrackView || '')
        ].join(' ').toLowerCase();

        if (/\bgravel\b/i.test(texts)) return 'gravel';
        if (/\b(mtb|mountain|monta[nñ]a)\b/i.test(texts)) return 'mtb';
        if (/\b(ruta|road|carretera)\b/i.test(texts)) return 'road';

        // 3. Heurística por rango de tallas numéricas en los swatches
        const swatches = [...document.querySelectorAll('.rb-swatch')].filter((s) => isSizeAttributeName(s.dataset.attribute));
        const nums = swatches
            .map((s) => parseFloat(s.textContent.trim()))
            .filter((n) => !Number.isNaN(n));

        if (nums.length > 0) {
            const avg = nums.reduce((a, b) => a + b, 0) / nums.length;
            if (avg < 30) return 'mtb'; // Pulgadas de MTB (13 a 21)
            if (avg >= 40) return 'road'; // Centímetros de Ruta (44 a 62)
        }

        return null;
    }

    // Bloquea el scroll de fondo mientras el modal está abierto y autodetecta la disciplina
    function openModal() {
        modal.style.display = 'flex';
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        const detected = detectPageDiscipline();
        const badge = document.getElementById('rb-detected-discipline-badge');

        if (detected && ['road', 'mtb', 'gravel'].includes(detected)) {
            selectedDiscipline = detected;
            disciplineBtns.forEach((b) => {
                const disc = b.getAttribute('data-discipline');
                if (disc === detected) {
                    b.classList.add('active');
                } else {
                    b.classList.remove('active');
                }
            });

            if (badge) {
                const discNames = { road: 'Ruta / Road', mtb: 'Montaña / MTB', gravel: 'Gravel' };
                badge.textContent = `⚡ Detectada: ${discNames[detected] || detected.toUpperCase()}`;
                badge.style.display = 'inline-block';
            }
        } else {
            if (badge) badge.style.display = 'none';
        }

        calculateSize();
    }

    function closeModal() {
        modal.classList.remove('active');
        modal.style.display = 'none';
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

    // El cálculo real (estatura/entrepierna -> talla) vive en
    // rbCalculateDisciplineSize, en el ámbito del módulo — ver el
    // comentario junto a su definición al inicio del archivo. Alias local
    // para no tener que cambiar cada llamada de abajo.
    const calculateDisciplineSize = rbCalculateDisciplineSize;

    // Main calculation logic
    function calculateSize() {
        if (!heightInput || !recSizeEl || !recDescEl) return;

        const height = parseInt(heightInput.value, 10);
        let inseam = parseInt(inseamInput.value, 10);

        // If advanced biomechanics is off, estimate inseam (approx 45.7% of height)
        if (advancedToggle && !advancedToggle.checked) {
            inseam = rbEstimateInseamFromHeight(height);
            if (inseamInput) {
                inseamInput.value = inseam;
                if (inseamDisplay) inseamDisplay.textContent = inseam;
            }
        }

        const currentResult = calculateDisciplineSize(selectedDiscipline, inseam);
        const letter = currentResult.letter;
        const frameSizeDesc = currentResult.frameSizeDesc;
        const longDesc = currentResult.longDesc;
        const numericSize = currentResult.numericSize;

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

        // Persistir la talla recomendada y el perfil multidisciplina en localStorage y cookies.
        const roadResult = calculateDisciplineSize('road', inseam);
        const mtbResult = calculateDisciplineSize('mtb', inseam);
        const gravelResult = calculateDisciplineSize('gravel', inseam);

        localStorage.setItem('rb_user_bike_size', JSON.stringify({
            discipline: selectedDiscipline,
            size: letter,
            numeric: numericSize,
            desc: frameSizeDesc,
            height,
            inseam,
            disciplines: {
                road: { size: roadResult.letter, numeric: roadResult.numericSize, desc: roadResult.frameSizeDesc },
                mtb: { size: mtbResult.letter, numeric: mtbResult.numericSize, desc: mtbResult.frameSizeDesc },
                gravel: { size: gravelResult.letter, numeric: gravelResult.numericSize, desc: gravelResult.frameSizeDesc },
            },
            timestamp: Date.now()
        }));
        document.cookie = `rb_user_bike_size=${letter};path=/;max-age=31536000;SameSite=Lax`;

        // Lanzar un evento global para actualizar componentes reactivos sin
        // refrescar la página — esto es lo que hace que el banner de la
        // ficha de producto (app.js) seleccione en vivo el swatch real
        // mientras se mueve el slider, no solo al pulsar "Aplicar".
        window.dispatchEvent(new CustomEvent('rb_size_calculated', {
            detail: {
                size: letter,
                numeric: numericSize,
                discipline: selectedDiscipline,
                desc: frameSizeDesc,
                height,
                inseam,
                disciplines: {
                    road: roadResult,
                    mtb: mtbResult,
                    gravel: gravelResult,
                }
            }
        }));

        if (applyBtn) {
            applyBtn.textContent = `Seleccionar Talla ${displaySizeLabel} e ir a Comprar`;
        }
    }

    // Apply button click
    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            closeModal();

            // Mismo criterio que el resultado del modal: solo swatches/select
            // del atributo de Talla, nunca de Color u otro atributo (ver
            // getRealProductSizeOptions más arriba).
            const pageOptions = getRealProductSizeOptions();
            const matched = pageOptions ? pickBestSizeOption(pageOptions.items, pageOptions.getText) : null;

            if (matched && pageOptions.kind === 'swatch') {
                matched.click();
            } else if (matched && pageOptions.kind === 'select') {
                pageOptions.select.value = matched.value;
                pageOptions.select.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                // If no product option found, redirect to shop with filter.
                // El filtro real del catálogo es "filter_talla" (taxonomía
                // pa_talla) — "filter_talla-cuadro" no tiene ningún producto
                // asignado (pa_talla-cuadro quedó sin uso tras la migración
                // de tallas a pa_talla) y nunca filtraba nada.
                const filterValue = currentSizeNumeric != null ? currentSizeNumeric : currentSizeLetter.toLowerCase();
                window.location.href = `/tienda/?filter_talla=${filterValue}`;
            }
        });
    }
}

// Tabla de referencia por estatura de /encuentra-tu-talla/ (y de
// cualquier otra página que traiga el mismo contenedor). Construida
// recorriendo estaturas cm a cm y llamando a rbCalculateDisciplineSize —
// la MISMA función que usa el modal — en vez de mantener una tabla
// aparte escrita a mano que inevitablemente se desalinea cuando cambia
// el cálculo real (ver el comentario en el @php del template).
function initRbSizeReferenceTable() {
    const tbody = document.querySelector('[data-rb-size-reference-table]');
    if (!tbody) return;

    const discipline = tbody.dataset.discipline || 'road';
    const MIN_HEIGHT = 150;
    const MAX_HEIGHT = 205;

    const buckets = [];
    for (let height = MIN_HEIGHT; height <= MAX_HEIGHT; height++) {
        const inseam = rbEstimateInseamFromHeight(height);
        const { letter, frameSizeDesc } = rbCalculateDisciplineSize(discipline, inseam);
        const current = buckets[buckets.length - 1];

        if (current && current.letter === letter) {
            current.maxHeight = height;
            current.maxFrame = frameSizeDesc;
        } else {
            buckets.push({ letter, minHeight: height, maxHeight: height, minFrame: frameSizeDesc, maxFrame: frameSizeDesc });
        }
    }

    tbody.innerHTML = buckets.map((bucket) => {
        const heightRange = bucket.minHeight === bucket.maxHeight
            ? `${bucket.minHeight} cm`
            : `${bucket.minHeight} – ${bucket.maxHeight} cm`;
        const frameRange = bucket.minFrame === bucket.maxFrame
            ? `Marco ${bucket.minFrame}`
            : `Marco ${bucket.minFrame} – ${bucket.maxFrame}`;

        return `
          <tr class="transition-colors hover:bg-surface-raised">
            <td class="px-4 py-3 font-bold text-ink">${bucket.letter}</td>
            <td class="px-4 py-3 font-medium text-ink">${heightRange}</td>
            <td class="px-4 py-3 text-xs">${frameRange}</td>
          </tr>
        `;
    }).join('');
}

// Execute setup
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initRbSizeCalculator();
        initRbSizeReferenceTable();
    });
} else {
    initRbSizeCalculator();
    initRbSizeReferenceTable();
}
