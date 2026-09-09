(function initBikeBuilder() {
  const builder = document.getElementById('rb-bike-builder');
  if (!builder) return;

  const product_id = builder.getAttribute('data-product-id');
  
  // Estado de la configuración
  let config = {
    marco: 'Ruta (Carbono Aero)',
    talla: 'M',
    grupo: 'Shimano 105 Mechanical (2x11)',
    ruedas: 'Aluminio Ligero Tubeless Ready',
    contacto: 'Sillín Comfort Standard',
  };

  // Precios de las opciones
  let prices = {
    marco: 1500000,
    grupo: 0,
    ruedas: 0,
    contacto: 0,
  };

  const totalDisplay = document.getElementById('bb-total-display');
  const summaryType = document.getElementById('bb-summary-type');
  const summarySpecs = document.getElementById('bb-summary-specs');
  const addToCartBtn = document.getElementById('bb-add-to-cart-btn');
  const loadingSpinner = document.getElementById('bb-loading-spinner');
  const btnText = document.getElementById('bb-btn-text');

  // Botones de las opciones
  const optionBtns = builder.querySelectorAll('.bb-option-btn');
  const rowBtns = builder.querySelectorAll('.bb-row-btn');
  const tallaBtns = builder.querySelectorAll('.bb-talla-btn');

  function formatPrice(value) {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(value);
  }

  function filterCompatibility(discipline) {
    const compatibilityElements = builder.querySelectorAll('[data-compatibility]');
    compatibilityElements.forEach(el => {
      if (el.getAttribute('data-compatibility') === discipline) {
        el.classList.remove('hidden');
      } else {
        el.classList.add('hidden');
        el.classList.remove('active');
      }
    });

    // Auto-seleccionar la primera opción compatible si el componente activo queda oculto
    const steps = ['grupo', 'ruedas'];
    steps.forEach(step => {
      const activeBtn = builder.querySelector(`.bb-row-btn[data-step="${step}"].active`);
      if (!activeBtn || activeBtn.classList.contains('hidden')) {
        const firstVisible = builder.querySelector(`.bb-row-btn[data-step="${step}"][data-compatibility="${discipline}"]`);
        if (firstVisible) {
          firstVisible.click();
        }
      }
    });
  }

  function updateTotals() {
    let total = Object.values(prices).reduce((a, b) => a + b, 0);
    totalDisplay.textContent = formatPrice(total);

    // Actualizar texto del resumen debajo del visualizador
    const grupoSimple = config.grupo.split(' (')[0];
    const ruedasSimple = config.ruedas.split(' Aerodinámicas')[0].split(' Tubeless')[0];
    summarySpecs.textContent = `${grupoSimple} • ${ruedasSimple}`;
  }

  // Clic en opciones de marco (Paso 1)
  optionBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      optionBtns.forEach(b => {
        if (b.getAttribute('data-step') === 'marco') {
          b.classList.remove('active');
        }
      });
      btn.classList.add('active');

      const val = btn.getAttribute('data-value');
      const price = parseFloat(btn.getAttribute('data-price') || '0');
      const targetImg = btn.getAttribute('data-target-img');
      const label = btn.getAttribute('data-label');

      config.marco = val;
      prices.marco = price;
      summaryType.textContent = label;

      // Filtrar compatibilidad según la disciplina elegida
      const discipline = targetImg.replace('bb-preview-', '');
      filterCompatibility(discipline);

      // Alternar visualización del renderizado de bicicleta en el panel izquierdo
      const previewImgs = builder.querySelectorAll('img[id^="bb-preview-"]');
      previewImgs.forEach(img => {
        if (img.id === targetImg) {
          img.classList.remove('opacity-0', 'pointer-events-none', 'scale-95');
          img.classList.add('opacity-100', 'scale-100');
        } else {
          img.classList.remove('opacity-100', 'scale-100');
          img.classList.add('opacity-0', 'pointer-events-none', 'scale-95');
        }
      });

      updateTotals();
    });
  });

  // Inicializar compatibilidad por defecto (Ruta)
  filterCompatibility('road');

  // Clic en filas de componentes (Pasos 2, 3, 4)
  rowBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const step = btn.getAttribute('data-step');
      rowBtns.forEach(b => {
        if (b.getAttribute('data-step') === step) {
          b.classList.remove('active');
        }
      });
      btn.classList.add('active');

      const val = btn.getAttribute('data-value');
      const price = parseFloat(btn.getAttribute('data-price') || '0');

      config[step] = val;
      prices[step] = price;

      updateTotals();
    });
  });

  // Botones de Talla del marco
  tallaBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tallaBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      config.talla = btn.getAttribute('data-talla');
    });
  });

  // Agregar al carrito vía AJAX
  if (addToCartBtn) {
    addToCartBtn.addEventListener('click', () => {
      addToCartBtn.disabled = true;
      loadingSpinner.classList.remove('hidden');
      btnText.textContent = 'Alistando tu Bici...';

      // El extra del precio se calcula restando el marco base (para sumarlo en WooCommerce)
      const priceExtra = Object.entries(prices)
        .filter(([key]) => key !== 'marco')
        .reduce((sum, [, val]) => sum + val, 0);

      const data = new URLSearchParams();
      data.append('action', 'rb_add_bike_to_cart');
      data.append('product_id', product_id);
      data.append('is_bike_builder', '1');
      data.append('bb_marco', config.marco);
      data.append('bb_talla', config.talla);
      data.append('bb_grupo', config.grupo);
      data.append('bb_ruedas', config.ruedas);
      data.append('bb_contacto', config.contacto);
      data.append('bb_price_extra', priceExtra.toString());

      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: data.toString()
      })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          window.location.href = res.data.cart_url || '/carrito/';
        } else {
          alert(res.data.message || 'Hubo un error al guardar tu configuración.');
          resetBtn();
        }
      })
      .catch(err => {
        console.error(err);
        alert('Ocurrió un error inesperado al procesar la configuración.');
        resetBtn();
      });
    });
  }

  function resetBtn() {
    addToCartBtn.disabled = false;
    loadingSpinner.classList.add('hidden');
    btnText.textContent = '¡Coronar Bici & Empezar a Rodar!';
  }

  // Inicializar vista con los totales correctos
  updateTotals();
})();
