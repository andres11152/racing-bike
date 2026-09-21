const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

/* -------------------------------------------------------------------------
 | Vídeos del hero: sólo se descarga el que corresponde al viewport actual.
 | Los <video> del carrusel llevan data-video-src en vez de src para que el
 | navegador no los precargue al parsear el HTML (display:none no evita la
 | descarga). Aquí se activa uno solo según matchMedia, y si el viewport
 | cambia de banda (girar el móvil, redimensionar) se activa el otro bajo
 | demanda — nunca los dos a la vez.
 * ---------------------------------------------------------------------- */

function initHeroVideos() {
  const carousel = document.querySelector('[data-carousel]');
  const track = carousel ? carousel.querySelector('[data-carousel-track]') : null;
  const videos = document.querySelectorAll('video[data-video-src]');
  if (!videos.length) return;

  const desktopQuery = window.matchMedia('(min-width: 768px)');
  const variantFor = () => (desktopQuery.matches ? 'desktop' : 'mobile');

  const activateVideo = (video) => {
    const variant = variantFor();
    if (video.dataset.videoVariant !== variant || video.dataset.videoActivated) return;
    video.dataset.videoActivated = 'true';
    video.src = video.dataset.videoSrc;
    video.load();
    video.play().catch(() => {});
  };

  const checkVisibleVideos = () => {
    if (!track) return;
    const trackLeft = track.scrollLeft;
    const trackWidth = track.clientWidth;

    videos.forEach((video) => {
      const slide = video.closest('[data-carousel-slide]');
      if (!slide) return;
      const slideLeft = slide.offsetLeft;
      const slideWidth = slide.offsetWidth;

      if (slideLeft < trackLeft + trackWidth + 10 && slideLeft + slideWidth > trackLeft - 10) {
        activateVideo(video);
      }
    });
  };

  checkVisibleVideos();

  if (track) {
    track.addEventListener('scroll', checkVisibleVideos, { passive: true });
  }

  desktopQuery.addEventListener('change', () => {
    checkVisibleVideos();
  });
}

initHeroVideos();

/* -------------------------------------------------------------------------
 | Paneles off-canvas: menú móvil, carrito, filtros, quick view
 * ---------------------------------------------------------------------- */

const FOCUSABLE_SELECTOR = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

function getFocusable(container) {
  return [...container.querySelectorAll(FOCUSABLE_SELECTOR)].filter(
    (el) => el.offsetWidth || el.offsetHeight || el.getClientRects().length
  );
}

function initDrawer({ drawerSelector, openSelector, closeSelector, expandedTargetSelector }) {
  const drawer = document.querySelector(drawerSelector);
  if (!drawer) return;

  const openTriggers = document.querySelectorAll(openSelector);
  const closeTriggers = drawer.querySelectorAll(closeSelector);
  const expandedTarget = expandedTargetSelector ? document.querySelector(expandedTargetSelector) : null;

  // Cerrado por defecto: fuera del árbol de accesibilidad y del orden de
  // tabulación. Sin esto, los enlaces del menú/carrito son alcanzables con
  // Tab aunque estén invisibles (opacity no basta — ver app.css `.drawer`).
  drawer.inert = true;

  let lastFocused = null;

  const setOpen = (isOpen) => {
    drawer.dataset.open = String(isOpen);
    drawer.inert = !isOpen;
    document.body.classList.toggle('overflow-hidden', isOpen);
    expandedTarget?.setAttribute('aria-expanded', String(isOpen));

    if (isOpen) {
      lastFocused = document.activeElement;
      // Al panel, no al overlay: el primer control real dentro del drawer.
      drawer.querySelector('[data-drawer-panel] button, .drawer-panel button, .drawer-panel a')?.focus();
    } else {
      lastFocused?.focus();
    }
  };

  openTriggers.forEach((trigger) => trigger.addEventListener('click', () => setOpen(true)));
  closeTriggers.forEach((trigger) => trigger.addEventListener('click', () => setOpen(false)));

  document.addEventListener('keydown', (event) => {
    if (drawer.dataset.open !== 'true') return;

    if (event.key === 'Escape') {
      setOpen(false);
      return;
    }

    // Trampa de foco: con el panel abierto, Tab no debe poder escapar hacia
    // la página de detrás (que aria-modal="true" ya declara inaccesible a
    // lectores de pantalla, pero sin esto seguía siendo operable con teclado).
    if (event.key === 'Tab') {
      const focusable = getFocusable(drawer);
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
  });
}

initDrawer({
  drawerSelector: '[data-nav-drawer]',
  openSelector: '[data-nav-open]',
  closeSelector: '[data-nav-close]',
  expandedTargetSelector: '[data-nav-open]',
});

initDrawer({
  drawerSelector: '[data-cart-drawer]',
  openSelector: '[data-cart-open]',
  closeSelector: '[data-cart-close]',
});

initDrawer({
  drawerSelector: '[data-size-guide-drawer]',
  openSelector: '[data-size-guide-open]',
  closeSelector: '[data-size-guide-close]',
});

initDrawer({
  drawerSelector: '[data-filter-drawer]',
  openSelector: '[data-filter-open]',
  closeSelector: '[data-filter-close]',
});

/* -------------------------------------------------------------------------
 | Acordeón de la navegación móvil
 * ---------------------------------------------------------------------- */

document.querySelectorAll('[data-accordion-toggle]').forEach((toggle) => {
  toggle.addEventListener('click', () => {
    const panel = document.getElementById(toggle.getAttribute('aria-controls'));
    const isOpen = toggle.getAttribute('aria-expanded') === 'true';

    toggle.setAttribute('aria-expanded', String(!isOpen));
    panel?.classList.toggle('hidden', isOpen);
    toggle.querySelector('[data-accordion-icon]')?.classList.toggle('rotate-180', !isOpen);
  });
});

/* -------------------------------------------------------------------------
 | Mega menú: el hover y el foco los resuelve CSS; aquí sólo Escape.
 * ---------------------------------------------------------------------- */

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;

  const openPanel = document.querySelector('[data-mega-panel]:hover, [data-mega-menu] li:focus-within [data-mega-panel]');
  if (openPanel) document.activeElement?.blur();
});

/* -------------------------------------------------------------------------
 | Barra de anuncios: rotación y descarte persistente
 * ---------------------------------------------------------------------- */

(function initAnnouncementBar() {
  const bar = document.querySelector('[data-announcement-bar]');
  if (!bar) return;

  // El comentario de arriba ya decía "descarte persistente", pero sólo
  // ocultaba el nodo en memoria: recargar la página, o navegar a otra,
  // la volvía a mostrar siempre. localStorage es lo mínimo para que
  // "cerrar" signifique cerrar.
  const STORAGE_KEY = 'rb_announcement_dismissed';

  try {
    if (localStorage.getItem(STORAGE_KEY) === 'true') {
      bar.style.display = 'none';
      return;
    }
  } catch (e) {
    // localStorage no disponible (navegación privada, cuota llena, etc.): degradar en silencio.
  }

  bar.querySelector('[data-announcement-dismiss]')?.addEventListener('click', () => {
    bar.style.display = 'none';
    try {
      localStorage.setItem(STORAGE_KEY, 'true');
    } catch (e) {
      // Sin persistencia disponible: el cierre sigue funcionando para esta carga de página.
    }
  });
})();



/* -------------------------------------------------------------------------
 | Carrusel del hero
 |
 | El desplazamiento y el snap los hace CSS. Aquí sólo se añade autoplay,
 | los puntos y la sincronización del estado al hacer scroll manual.
 * ---------------------------------------------------------------------- */

// Función nombrada (no sólo el forEach de abajo) para poder inicializar
// también pasarelas que se llenan después, por JS — como "Vistos
// recientemente" — una vez que sus tarjetas ya están en el DOM.
function initCarousel(carousel) {
  const track = carousel.querySelector('[data-carousel-track]');
  const dotsContainer = carousel.querySelector('[data-carousel-dots]');
  const pauseButton = carousel.querySelector('[data-carousel-pause]');
  const liveRegion = carousel.querySelector('[data-carousel-live]');
  const contextId = carousel.dataset.ga4ContextId || '';

  let slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
  let dots = dotsContainer ? [] : [...carousel.querySelectorAll('[data-carousel-dot]')];

  if (!track || slides.length < 2) return;

  const alreadyReady = carousel.dataset.carouselReady === 'true';
  carousel.dataset.carouselReady = 'true';

  let current = 0;
  let timer = null;
  let resumeTimer = null;

  // Pausa DEFINITIVA (botón de pausa): nada la levanta salvo pulsar de
  // nuevo. Requisito de WCAG 2.2.2 — el contenido en movimiento debe poder
  // pararse, y quedarse parado.
  let paused = false;

  // Pausa TEMPORAL (flecha, punto o swipe): el usuario está mirando otra
  // diapositiva a propósito, no pidió apagar el carrusel — se reanuda sola
  // tras un rato sin más interacción, para no competir con quien lee.
  let manuallyPaused = false;
  const RESUME_DELAY = 15000;

  const syncDots = () => {
    dots.forEach((dot, index) => {
      dot.dataset.active = String(index === current);
    });
  };

  const announce = () => {
    if (liveRegion) {
      liveRegion.textContent = `Diapositiva ${current + 1} de ${slides.length}`;
    }
  };

  const goTo = (index) => {
    current = (index + slides.length) % slides.length;
    track.scrollTo({ left: slides[current].offsetLeft, behavior: 'smooth' });
    syncDots();
  };

  const start = () => {
    if (prefersReducedMotion.matches || paused || manuallyPaused || carousel.dataset.autoplay !== 'true') return;
    stop();
    timer = setInterval(() => goTo(current + 1), Number(carousel.dataset.interval) || 8000);
  };

  const stop = () => {
    if (timer) clearInterval(timer);
    timer = null;
  };

  const stopForGood = () => {
    paused = true;
    manuallyPaused = false;
    clearTimeout(resumeTimer);
    stop();
  };

  const pauseTemporarily = () => {
    if (paused) return;
    manuallyPaused = true;
    stop();
    clearTimeout(resumeTimer);
    resumeTimer = setTimeout(() => {
      manuallyPaused = false;
      start();
    }, RESUME_DELAY);
  };

  const trackInteraction = (direction) => {
    document.dispatchEvent(new CustomEvent('rb:carousel-interact', {
      detail: { contextId, direction, fromSlot: current },
    }));
  };

  const attachDotHandler = (dot, index) => {
    dot.addEventListener('click', () => {
      pauseTemporarily();
      goTo(index);
      announce();
      trackInteraction('dot');
    });
  };

  // Los puntos siempre los construye el JS a partir de las diapositivas
  // reales (nunca Blade contando de antemano): así refresh() los mantiene
  // en sincronía cuando el contenido cambia después de pintar la página.
  const buildDots = () => {
    if (!dotsContainer) return;
    dotsContainer.innerHTML = '';
    dots = slides.map((_, index) => {
      const dot = document.createElement('button');
      dot.type = 'button';
      // El punto visual mide 2px de alto; el botón es de 24px×24px — un
      // área táctil de 2px es casi imposible de tocar con el dedo.
      dot.className = 'group flex h-6 w-8 items-center justify-center';
      dot.setAttribute('data-carousel-dot', '');
      dot.dataset.active = String(index === current);
      dot.setAttribute('aria-label', `Ir a la diapositiva ${index + 1}`);

      const bar = document.createElement('span');
      bar.className = 'block h-0.5 w-full bg-ink-faint transition-colors group-data-[active=true]:bg-ink';
      dot.appendChild(bar);

      attachDotHandler(dot, index);
      dotsContainer.appendChild(dot);
      return dot;
    });
  };

  if (!alreadyReady) {
    if (dotsContainer) {
      buildDots();
    } else {
      dots.forEach((dot, index) => attachDotHandler(dot, index));
    }

    carousel.querySelector('[data-carousel-next]')?.addEventListener('click', () => {
      pauseTemporarily();
      goTo(current + 1);
      announce();
      trackInteraction('next');
    });

    carousel.querySelector('[data-carousel-prev]')?.addEventListener('click', () => {
      pauseTemporarily();
      goTo(current - 1);
      announce();
      trackInteraction('prev');
    });

    pauseButton?.addEventListener('click', () => {
      if (paused) {
        paused = false;
        start();
        pauseButton.setAttribute('aria-label', pauseButton.dataset.pauseLabel || 'Pausar carrusel');
        pauseButton.querySelector('[data-carousel-pause-icon]')?.classList.remove('border-r-0');
      } else {
        stopForGood();
        pauseButton.setAttribute('aria-label', pauseButton.dataset.resumeLabel || 'Reanudar carrusel');
        pauseButton.querySelector('[data-carousel-pause-icon]')?.classList.add('border-r-0');
      }
    });

    // El usuario puede deslizar: derivar la diapositiva activa del scroll
    // real, y contar el arrastre manual como pausa temporal.
    let scrollTimeout = null;
    track.addEventListener('pointerdown', () => {
      pauseTemporarily();
      trackInteraction('swipe');
    }, { passive: true });

    track.addEventListener('scroll', () => {
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(() => {
        current = slides.findIndex((slide) => Math.abs(slide.offsetLeft - track.scrollLeft) < slide.offsetWidth / 2);
        if (current < 0) current = 0;
        syncDots();
      }, 100);
    }, { passive: true });

    // No hacer girar un carrusel que nadie está viendo. start()/stop() ya
    // respetan `paused`/`manuallyPaused`, así que esto nunca reanuda uno
    // que el usuario detuvo.
    const observer = new IntersectionObserver(
      ([entry]) => (entry.isIntersecting ? start() : stop()),
      { threshold: 0.4 }
    );
    observer.observe(carousel);

    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', start);

    // Permite recalcular diapositivas y puntos cuando el contenido cambia
    // después de pintar la página — p. ej. "Cargar más reseñas" o el
    // filtro "solo con foto" del carrusel de reseñas.
    carousel.rbCarouselRefresh = () => {
      slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
      if (current >= slides.length) current = 0;
      buildDots();
      syncDots();
    };
  } else if (typeof carousel.rbCarouselRefresh === 'function') {
    carousel.rbCarouselRefresh();
  }
}

document.querySelectorAll('[data-carousel]').forEach(initCarousel);
window.rbInitCarousel = initCarousel;

/* -------------------------------------------------------------------------
 | Vistos recientemente — 100% client-side (localStorage, sin cookies ni PII)
 | porque con un catálogo de un puñado de productos es la única señal de
 | personalización real: la relevancia la da lo que el propio visitante miró,
 | no el catálogo. Se registra en cada ficha de producto y se pinta sólo en
 | las páginas que traen el contenedor de la pasarela (hoy, la home).
 * ---------------------------------------------------------------------- */

(function initRecentlyViewed() {
  const STORAGE_KEY = 'rb_recently_viewed';
  const MAX_ITEMS = 12;

  const readList = () => {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (e) {
      return [];
    }
  };

  const writeList = (list) => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    } catch (e) {
      // localStorage no disponible (navegación privada, cuota llena, etc.): degradar en silencio.
    }
  };

  const viewEl = document.querySelector('[data-rb-track-view]');
  let currentProduct = null;

  if (viewEl) {
    try {
      currentProduct = JSON.parse(viewEl.getAttribute('data-rb-track-view'));
    } catch (e) {
      currentProduct = null;
    }
  }

  // Registrar la vista actual del producto, si la página trae sus datos.
  if (currentProduct && currentProduct.id) {
    const list = readList().filter((p) => p.id !== currentProduct.id);
    list.unshift(currentProduct);
    writeList(list.slice(0, MAX_ITEMS));
  }

  // Pintar la pasarela, si esta página tiene un contenedor para ella.
  const shelf = document.querySelector('[data-recently-viewed-shelf]');
  const track = document.querySelector('[data-recently-viewed-track]');
  if (!shelf || !track) return;

  const excludeId = currentProduct ? currentProduct.id : null;
  const items = readList().filter((p) => p.id !== excludeId).slice(0, 8);

  if (items.length < 2) return; // Nada real que mostrar todavía: mejor no dejar una pasarela vacía.

  const currency = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

  items.forEach((item, index) => {
    const card = document.createElement('a');
    card.href = item.url || '#';
    card.className = 'group block w-[46%] shrink-0 snap-start sm:w-[31%] lg:w-[22%]';
    card.setAttribute('data-carousel-slide', '');
    card.setAttribute('data-ga4-item', '');
    card.setAttribute('data-ga4-list-id', 'home_recently_viewed');
    card.setAttribute('data-ga4-list-name', 'Vistos recientemente');
    card.setAttribute('data-ga4-item-id', String(item.id));
    card.setAttribute('data-ga4-item-name', item.name || '');
    card.setAttribute('data-ga4-index', String(index));
    if (item.price) card.setAttribute('data-ga4-item-price', String(item.price));

    const imgWrap = document.createElement('div');
    imgWrap.className = 'aspect-4/5 overflow-hidden rounded-lg bg-surface-muted';

    const img = document.createElement('img');
    img.src = item.image || '';
    img.alt = '';
    img.loading = 'lazy';
    img.decoding = 'async';
    img.className = 'size-full object-cover transition-transform duration-500 group-hover:scale-105';
    imgWrap.appendChild(img);

    const name = document.createElement('p');
    name.className = 'mt-3 text-sm font-semibold text-ink line-clamp-1';
    name.textContent = item.name || '';

    const price = document.createElement('p');
    price.className = 'mt-1 text-sm text-ink-muted';
    price.textContent = item.price ? currency.format(item.price) : '';

    card.append(imgWrap, name, price);
    track.appendChild(card);
  });

  shelf.hidden = false;

  const shelfCarousel = shelf.querySelector('[data-carousel]');
  if (shelfCarousel && typeof window.rbInitCarousel === 'function') {
    window.rbInitCarousel(shelfCarousel);
  }
})();

/* -------------------------------------------------------------------------
 | Medición de listas de producto y promociones: view_item_list, select_item,
 | view_promotion, select_promotion, carousel_interact.
 |
 | Sin esto es imposible saber si una pasarela o el hero convierten o no —
 | se lee de data-attributes que el theme ya renderiza en product-card y
 | hero-carousel, para no duplicar lógica de item/promoción en JS.
 * ---------------------------------------------------------------------- */

(function initListAndPromotionTracking() {
  if (typeof window.rbTrack !== 'function') return;

  const seenLists = new Set();
  const seenPromotions = new Set();

  const itemFromCard = (card) => {
    const id = card.dataset.ga4ItemId;
    if (!id) return null;

    const item = {
      item_id: id,
      item_name: card.dataset.ga4ItemName || '',
      item_list_id: card.dataset.ga4ListId || undefined,
      item_list_name: card.dataset.ga4ListName || undefined,
    };

    if (card.dataset.ga4ItemPrice) item.price = Number(card.dataset.ga4ItemPrice);
    if (card.dataset.ga4Index !== undefined) item.index = Number(card.dataset.ga4Index);

    return item;
  };

  // view_item_list: una vez por lista, cuando al menos una tarjeta entra en
  // viewport. Extraído a función nombrada y expuesto en window: el catálogo
  // reemplaza sus tarjetas por AJAX al filtrar (ver initCatalogAjaxFilters),
  // y ese nuevo set de productos es una lista distinta que merece su propio
  // view_item_list — de ahí que también se borre de seenLists antes.
  const wireListImpressionObservers = (root = document) => {
    const listGroups = new Map();
    root.querySelectorAll('[data-ga4-item][data-ga4-list-id]').forEach((card) => {
      const listId = card.dataset.ga4ListId;
      if (!listGroups.has(listId)) listGroups.set(listId, []);
      listGroups.get(listId).push(card);
    });

    listGroups.forEach((cards, listId) => {
      seenLists.delete(listId);

      const listObserver = new IntersectionObserver((entries) => {
        if (seenLists.has(listId) || !entries.some((entry) => entry.isIntersecting)) return;
        seenLists.add(listId);

        window.rbTrack('view_item_list', {
          item_list_id: listId,
          item_list_name: cards[0].dataset.ga4ListName || listId,
          items: cards.map(itemFromCard).filter(Boolean),
        });

        listObserver.disconnect();
      }, { threshold: 0.3 });

      cards.forEach((card) => listObserver.observe(card));
    });
  };

  wireListImpressionObservers();
  window.rbInitGa4ListImpressions = wireListImpressionObservers;

  // select_item: click en cualquier parte de una tarjeta con datos de lista.
  document.addEventListener('click', (e) => {
    const card = e.target.closest('[data-ga4-item][data-ga4-list-id]');
    if (!card) return;

    const item = itemFromCard(card);
    if (!item) return;

    window.rbTrack('select_item', {
      item_list_id: item.item_list_id,
      item_list_name: item.item_list_name,
      items: [item],
    });
  });

  // view_promotion: cada diapositiva del hero, una vez.
  document.querySelectorAll('[data-ga4-promotion-id]').forEach((slide) => {
    const promotionId = slide.dataset.ga4PromotionId;

    const promoObserver = new IntersectionObserver((entries) => {
      if (seenPromotions.has(promotionId) || !entries.some((entry) => entry.isIntersecting)) return;
      seenPromotions.add(promotionId);

      window.rbTrack('view_promotion', {
        promotion_id: promotionId,
        promotion_name: slide.dataset.ga4PromotionName || '',
        creative_slot: slide.dataset.ga4Slot || '',
      });

      promoObserver.disconnect();
    }, { threshold: 0.5 });

    promoObserver.observe(slide);
  });

  // select_promotion: click en el CTA de una diapositiva.
  document.addEventListener('click', (e) => {
    if (!e.target.closest('a')) return;

    const slide = e.target.closest('[data-ga4-promotion-id]');
    if (!slide) return;

    window.rbTrack('select_promotion', {
      promotion_id: slide.dataset.ga4PromotionId,
      promotion_name: slide.dataset.ga4PromotionName || '',
      creative_slot: slide.dataset.ga4Slot || '',
    });
  });

  // carousel_interact: flechas, puntos y swipe — el propio carrusel lo despacha.
  document.addEventListener('rb:carousel-interact', (e) => {
    window.rbTrack('carousel_interact', {
      list_id: (e.detail && e.detail.contextId) || '',
      direction: (e.detail && e.detail.direction) || '',
      from_slot: (e.detail && e.detail.fromSlot) || 0,
    });
  });
})();

/* -------------------------------------------------------------------------
 | Swatches de talla del marco
 |
 | WooCommerce necesita su <select> para calcular precio y disponibilidad, así
 | que no se sustituye: se oculta y se conduce desde los botones. Si este script
 | no corre, `.no-js` deja el select visible y la ficha sigue siendo comprable.
 * ---------------------------------------------------------------------- */
const SIZE_ORDER_MAP = {
  '3xs': 0, 'xxs': 1, '2xs': 1, 'xs': 2,
  's': 3, 's-m': 4, 'sm': 4, 'm': 5,
  'ml': 6, 'm-l': 6, 'l': 7, 'l-xl': 8, 'lxl': 8,
  'xl': 9, 'xxl': 10, '2xl': 10, 'xxxl': 11, '3xl': 11,
  '4xl': 12, 'u': 90, 'tu': 90, 'unica': 90, 'one-size': 90,
};

function getRbSizeRank(val) {
  const str = String(val || '').trim().toLowerCase().replace(/[\s_/]+/g, '-');
  if (!str) return [2, 0, ''];
  const num = parseFloat(str);
  if (!Number.isNaN(num) && /^\d+(\.\d+)?/.test(str)) {
    return [0, num, str];
  }
  if (Object.prototype.hasOwnProperty.call(SIZE_ORDER_MAP, str)) {
    return [1, SIZE_ORDER_MAP[str], str];
  }
  return [2, 0, str];
}

function compareRbSizeValues(a, b) {
  const [typeA, valA, strA] = getRbSizeRank(a);
  const [typeB, valB, strB] = getRbSizeRank(b);
  if (typeA !== typeB) return typeA - typeB;
  if (valA !== valB) return valA - valB;
  return strA.localeCompare(strB);
}

// Variable y fallbacks tempranos para la imagen de variación si la galería aún no se ha inicializado
window.pendingVariationImage = null;
if (!window.rbApplyVariationImage) {
  window.rbApplyVariationImage = function(url, alt) {
    window.pendingVariationImage = { url, alt };
  };
}
if (!window.rbClearVariationImage) {
  window.rbClearVariationImage = function() {
    window.pendingVariationImage = null;
  };
}

function syncVariationImage(form) {
  if (!form) return;
  const isQuickView = !! form.closest('[data-quick-view-modal], [data-quick-view-target]');

  // Detectar selects de opciones
  const selects = [...form.querySelectorAll('.variations select')];
  const colorSelect = selects.find((s) => /color/i.test(s.name || s.id || s.dataset.attribute_name || ''));
  if (!colorSelect) return;

  const selectedColor = colorSelect.value;
  if (!selectedColor) {
    if (!isQuickView) {
      window.rbClearVariationImage?.();
    }
    return;
  }

  // Obtener array de variaciones
  let variations = null;
  if (window.jQuery) {
    variations = window.jQuery(form).data('product_variations');
  }
  if (!variations || variations === 'false' || variations === false) {
    const raw = form.dataset.product_variations || form.getAttribute('data-product_variations');
    if (raw && raw !== 'false') {
      try {
        variations = JSON.parse(raw);
      } catch {
        variations = null;
      }
    }
  }

  if (!Array.isArray(variations) || variations.length === 0) return;

  const tallaSelect = selects.find((s) => /talla|size/i.test(s.name || s.id || s.dataset.attribute_name || ''));
  const selectedTalla = tallaSelect?.value || '';

  const cleanStr = (v) => String(v || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]/g, '');
  const targetColor = cleanStr(selectedColor);
  const targetTalla = cleanStr(selectedTalla);

  // 1. Intentar coincidir color y talla a la vez con variación que tenga imagen válida
  let match = null;
  if (selectedTalla) {
    match = variations.find((v) => {
      if (!v || !v.attributes || !v.image || (!v.image.full_src && !v.image.src && !v.image.url)) return false;
      const matchColor = Object.entries(v.attributes).some(([k, val]) => /color/i.test(k) && (cleanStr(val) === targetColor || val === ''));
      const matchTalla = Object.entries(v.attributes).some(([k, val]) => (/talla/i.test(k) || /size/i.test(k)) && (cleanStr(val) === targetTalla || val === ''));
      return matchColor && matchTalla;
    });
  }

  // 2. Si no hay match con talla o no hay talla seleccionada, coincidir por color
  if (!match) {
    match = variations.find((v) => {
      if (!v || !v.attributes || !v.image || (!v.image.full_src && !v.image.src && !v.image.url)) return false;
      return Object.entries(v.attributes).some(([k, val]) => /color/i.test(k) && (cleanStr(val) === targetColor || val === ''));
    });
  }

  if (match && match.image) {
    const fullUrl = match.image.full_src || match.image.src || match.image.url;
    if (fullUrl) {
      if (!isQuickView) {
        window.rbApplyVariationImage?.(fullUrl, match.image.alt || '');
      } else {
        const qvMain = form.closest('[data-quick-view-modal]')?.querySelector('#qv-main-image');
        if (qvMain) {
          qvMain.src = fullUrl;
        }
      }
    }
  }
}
window.rbSyncVariationImage = syncVariationImage;

window.initSwatches = function() {
  document.querySelectorAll('.variations_form').forEach((form) => {
    const priceContainer = form.closest('.grid')?.querySelector('.rb-woo-price') || document.querySelector('.rb-woo-price');
    if (priceContainer && !priceContainer.dataset.originalPrice) {
      priceContainer.dataset.originalPrice = priceContainer.innerHTML;
    }

    form.querySelectorAll('.variations select').forEach((select) => {
      const row = select.closest('td') || select.parentElement;
      if (!row || row.querySelector('.rb-swatches')) return;

      const labelEl = form.querySelector(`label[for="${select.id}"]`) || row.closest('tr')?.querySelector('th label');
      const baseLabelText = labelEl ? (labelEl.dataset.baseText || labelEl.textContent.trim().replace(/:.*/, '')) : 'Talla del marco';
      if (labelEl && !labelEl.dataset.baseText) labelEl.dataset.baseText = baseLabelText;

      // WooCommerce nombra el select "attribute_pa_talla", "attribute_pa_color",
      // etc. — se guarda ese nombre en el propio swatch para que otro script
      // (la calculadora de talla) pueda distinguir "L" de talla de "Lila" de
      // color en vez de leer TODOS los .rb-swatch de la página sin filtrar.
      const attributeName = (select.name || select.id || '').replace(/^attribute_/, '');

      const list = document.createElement('div');
      list.className = 'rb-swatches';
      list.dataset.attribute = attributeName;
      list.setAttribute('role', 'group');
      list.setAttribute('aria-label', baseLabelText || 'Opciones');

      const options = [...select.options].filter((option) => option.value !== '');

      // Mostrar siempre las tallas ordenadas de menor a mayor (XXS -> XXL, 13 -> 19)
      if (/talla|size/i.test(attributeName)) {
        options.sort((a, b) => compareRbSizeValues(a.value || a.textContent, b.value || b.textContent));
        options.forEach((opt) => select.appendChild(opt));
      }

      options.forEach((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'rb-swatch';
        button.textContent = option.textContent;
        button.dataset.value = option.value;
        button.dataset.attribute = attributeName;
        button.dataset.selected = String(select.value === option.value);

        button.addEventListener('click', () => {
          // Volver a pulsar la talla/color activa la deselecciona o selecciona
          const newValue = select.value === option.value ? '' : option.value;
          select.value = newValue;
          select.dispatchEvent(new Event('change', { bubbles: true }));

          if (window.jQuery) {
            window.jQuery(select).val(newValue).trigger('change');
            window.jQuery(form).trigger('check_variations');
          }

          // Sincronizar estado visual de los botones inmediatamente
          list.querySelectorAll('.rb-swatch').forEach((b) => {
            b.dataset.selected = String(b.dataset.value === newValue);
          });

          // Actualizar etiqueta del encabezado
          if (labelEl) {
            if (newValue) {
              labelEl.innerHTML = `${baseLabelText}: <span class="text-emerald-400 font-bold ml-1">${button.textContent}</span>`;
            } else {
              labelEl.textContent = baseLabelText;
            }
          }

          // Actualizar imagen inmediatamente si se seleccionó o cambió color
          syncVariationImage(form);
        });

        list.appendChild(button);
      });

      row.appendChild(list);

      // Ocultar select accesiblemente
      select.classList.add('sr-only');

      const sync = () => {
        list.querySelectorAll('.rb-swatch').forEach((button) => {
          const option = [...select.options].find((o) => o.value === button.dataset.value);

          button.dataset.selected = String(select.value === button.dataset.value);
          button.dataset.available = String(!!option && !option.disabled);
          button.disabled = !option || option.disabled;
        });

        if (labelEl) {
          const activeBtn = list.querySelector('.rb-swatch[data-selected="true"]');
          if (activeBtn) {
            labelEl.innerHTML = `${baseLabelText}: <span class="text-emerald-400 font-bold ml-1">${activeBtn.textContent}</span>`;
          } else {
            labelEl.textContent = baseLabelText;
          }
        }

        syncVariationImage(form);
      };

      select.addEventListener('change', sync);

      if (window.jQuery) {
        window.jQuery(form).on('woocommerce_update_variation_values reset_data show_variation hide_variation', sync);
      }

      sync();
    });

    if (window.jQuery && !form.dataset.rbJqueryInitialized) {
      form.dataset.rbJqueryInitialized = 'true';

      window.jQuery(form).on('show_variation', (event, variation) => {
        if (variation && variation.price_html && priceContainer) {
          priceContainer.innerHTML = variation.price_html;
        }

        const isQuickView = !! form.closest('[data-quick-view-modal], [data-quick-view-target]');

        if (! isQuickView && variation && variation.image && (variation.image.full_src || variation.image.src)) {
          window.rbApplyVariationImage?.(variation.image.full_src || variation.image.src, variation.image.alt);
        } else if (isQuickView && variation && variation.image && (variation.image.full_src || variation.image.src)) {
          const qvMain = form.closest('[data-quick-view-modal]')?.querySelector('#qv-main-image');
          if (qvMain) {
            qvMain.src = variation.image.full_src || variation.image.src;
          }
        }
      });

      window.jQuery(form).on('reset_data', () => {
        if (priceContainer && priceContainer.dataset.originalPrice) {
          priceContainer.innerHTML = priceContainer.dataset.originalPrice;
        }
        if (! form.closest('[data-quick-view-modal], [data-quick-view-target]')) {
          window.rbClearVariationImage?.();
        }
      });

      window.jQuery(form).on('hide_variation', () => {
        syncVariationImage(form);
      });
    }
  });
};

// Inicializar en la carga inicial de la página
document.addEventListener('DOMContentLoaded', () => {
  if (typeof window.initSwatches === 'function') {
    window.initSwatches();
  }
});
window.initSwatches();
/* -------------------------------------------------------------------------
 | Selector de cantidad Enterprise (Ficha de producto y Carrito)
 * ---------------------------------------------------------------------- */

document.addEventListener('click', (e) => {
  const btn = e.target.closest('.rb-qty-btn, [data-quantity-decrement], [data-quantity-increment]');
  if (!btn) return;

  const wrapper = btn.closest('.quantity, [data-quantity-input], .rb-quantity-pill');
  if (!wrapper) return;

  const input = wrapper.querySelector('input[type="number"], input.qty, [data-quantity-value]');
  if (!input) return;

  const isMinus = btn.classList.contains('rb-qty-minus') || btn.hasAttribute('data-quantity-decrement');
  const isPlus = btn.classList.contains('rb-qty-plus') || btn.hasAttribute('data-quantity-increment');
  if (!isMinus && !isPlus) return;

  const currentVal = parseFloat(input.value) || 1;
  const min = input.min !== '' ? parseFloat(input.min) : 1;
  const max = input.max !== '' ? parseFloat(input.max) : Infinity;
  const step = parseFloat(input.step) || 1;

  if (isMinus) {
    const newVal = Math.max(min, currentVal - step);
    input.value = newVal;
  } else if (isPlus) {
    const newVal = Math.min(max, currentVal + step);
    input.value = newVal;
  }

  input.dispatchEvent(new Event('input', { bubbles: true }));
  input.dispatchEvent(new Event('change', { bubbles: true }));
});

/* -------------------------------------------------------------------------
 | Barra flotante de compra (Sticky Buy Bar) en Ficha de Producto
 * ---------------------------------------------------------------------- */

(function initStickyBuyBar() {
  const stickyBar = document.querySelector('[data-sticky-buy-bar]');
  const trigger = document.querySelector('.rb-woo-add-to-cart');
  if (!stickyBar || !trigger) return;

  const waBtn = document.querySelector('[data-whatsapp-button]');
  const scrollBtn = document.querySelector('[data-scroll-up]');
  const cookieBanner = document.getElementById('cookie-banner');

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        const isHidden = entry.isIntersecting;
        stickyBar.classList.toggle('translate-y-full', isHidden);
        stickyBar.classList.toggle('opacity-0', isHidden);

        if (waBtn) {
          if (isHidden) {
            waBtn.style.bottom = '';
          } else {
            waBtn.style.bottom = '96px';
          }
        }

        if (scrollBtn) {
          if (isHidden) {
            scrollBtn.style.bottom = '';
          } else {
            scrollBtn.style.bottom = '96px';
          }
        }

        // El banner de cookies (z-[200], por encima de la barra) le ganaba
        // el toque a "Comprar" en móvil apenas aparecía: mismo tratamiento
        // que el botón de WhatsApp para que nunca se solapen.
        if (cookieBanner) {
          if (isHidden) {
            cookieBanner.style.bottom = '';
          } else {
            cookieBanner.style.bottom = '96px';
          }
        }
      });
    },
    { threshold: 0.1 }
  );

  observer.observe(trigger);

  // El botón dice "Comprar", así que debe comprar — antes sólo hacía scroll y
  // enfocaba el botón real sin agregar nada. Un usuario que no ve el carrito
  // reaccionar hace clic otra vez en el botón real ya enfocado, y termina con
  // dos unidades por una sola intención de compra.
  const actionBtn = stickyBar.querySelector('[data-sticky-action]');
  actionBtn?.addEventListener('click', () => {
    const formBtn = trigger.querySelector('button[type="submit"], .single_add_to_cart_button');
    if (!formBtn) return;

    const form = formBtn.closest('form.cart');
    const needsVariation = form?.classList.contains('variations_form');
    const variationInput = form?.querySelector('input.variation_id, input[name="variation_id"]');
    const hasVariation = !needsVariation || (variationInput && variationInput.value && variationInput.value !== '0');

    if (hasVariation) {
      // Todo listo para comprar: el clic en "Comprar" completa la compra de
      // una vez, reutilizando el mismo botón (y su guardia anti-doble-envío)
      // en vez de duplicar la lógica de envío aquí.
      formBtn.click();
    } else {
      // Falta elegir talla/opción: llevar al usuario a elegirla, no se puede
      // comprar todavía.
      formBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
      formBtn.focus();
    }
  });
})();

/* -------------------------------------------------------------------------
 | Cabecera transparente en Scroll (Sticky Header Transparency)
 |
 | Fusionado con `initHeaderScrollEffect` (antes en la sección de sombra):
 | dos listeners de scroll independientes escribían clases de fondo/blur
 | contradictorias sobre el mismo header en el rango 20–40px, ganando la
 | que quedara última en el CSS en vez de la que el diseño pedía. Ahora
 | hay un único estado por rango de scroll y las escrituras se agrupan en
 | `requestAnimationFrame`.
 * ---------------------------------------------------------------------- */

/* -------------------------------------------------------------------------
 | Quick-Add, Quick-View y Carrito AJAX Global
 * ---------------------------------------------------------------------- */

function openCartDrawer() {
  const cartDrawer = document.querySelector('[data-cart-drawer]');
  if (cartDrawer) {
    cartDrawer.dataset.open = 'true';
    cartDrawer.inert = false;
    document.body.classList.add('overflow-hidden');
    const openTrigger = document.querySelector('[data-cart-open]');
    if (openTrigger) {
      openTrigger.setAttribute('aria-expanded', 'true');
    }
    cartDrawer.querySelector('[data-drawer-panel] button, .drawer-panel button, .drawer-panel a')?.focus();
  }
}

// Quick Add para productos simples y add-ons con protección estricta contra doble clic
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-quick-add], [data-add-addon]');
  if (!btn) return;

  // Si ya está procesando una petición o está deshabilitado, abortar inmediatamente
  if (btn.dataset.adding === 'true' || btn.disabled) {
    e.preventDefault();
    e.stopPropagation();
    return;
  }

  e.preventDefault();
  e.stopPropagation();

  // Bloqueo inmediato en vuelo
  btn.dataset.adding = 'true';
  btn.disabled = true;
  btn.style.pointerEvents = 'none';
  btn.classList.add('opacity-50');

  const productId = btn.dataset.quickAdd || btn.dataset.addAddon;

  const formData = new FormData();
  formData.append('action', 'rb_quick_add');
  formData.append('product_id', productId);
  formData.append('quantity', '1');
  if (window.rbAjax?.nonce) {
    formData.append('nonce', window.rbAjax.nonce);
  }

  fetch(window.rbAjax?.url ?? '/wp-admin/admin-ajax.php', {
    method: 'POST',
    body: formData,
  })
    .then(async (res) => {
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch {
        return { success: false, message: 'No se pudo agregar al carrito.' };
      }
    })
    .then((data) => {
      if (data.fragments) {
        Object.entries(data.fragments).forEach(([selector, html]) => {
          const el = document.querySelector(selector);
          if (el) el.outerHTML = html;
        });
      }

      if (data.success || data.fragments) {
        openCartDrawer();

        if (data.ga4_item && window.rbTrack) {
          const lineValue = data.ga4_item.price * data.ga4_item.quantity;
          window.rbTrack('add_to_cart', {
            currency: 'COP',
            value: lineValue,
            items: [data.ga4_item],
          }, 'AddToCart', {
            content_ids: [String(data.ga4_item.item_id)],
            content_type: 'product',
            value: lineValue,
            currency: 'COP',
          });
        }
      } else {
        const qvBtn = document.querySelector(`[data-quick-view="${productId}"]`);
        if (qvBtn) {
          qvBtn.click();
        } else {
          alert(data.data?.message || data.message || 'Error al agregar el producto.');
        }
      }
    })
    .catch((err) => {
      console.error('Error en quick add:', err);
    })
    .finally(() => {
      btn.dataset.adding = 'false';
      btn.disabled = false;
      btn.style.pointerEvents = '';
      btn.classList.remove('opacity-50');
    });
}, true);

// Quick View para productos variables y simples
(function initQuickView() {
  const modal = document.querySelector('[data-quick-view-modal]');
  const target = modal?.querySelector('[data-quick-view-target]');
  if (!modal || !target) return;

  modal.inert = true;

  // Mismo tratamiento de foco que initDrawer (carrito, filtros, guía de
  // tallas) — este modal no pasaba por ahí y se quedaba sin restaurar el
  // foco al cerrar ni ciclar Tab dentro de él.
  let lastFocused = null;

  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.inert = true;
    document.body.classList.remove('overflow-hidden');
    lastFocused?.focus();
    target.innerHTML = `
      <div class="flex flex-col items-center justify-center py-20 text-ink-subtle gap-4">
        <svg class="size-8 animate-spin text-emerald-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-xs uppercase tracking-widest text-ink-muted">Cargando especificaciones...</span>
      </div>
    `;
  }

  // Apertura del modal
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-quick-view]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    const productId = btn.dataset.quickView;
    lastFocused = document.activeElement;
    modal.inert = false;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    modal.querySelector('[data-quick-view-close], [data-close-quick-view]')?.focus();

    fetch(`${window.rbAjax?.url ?? '/wp-admin/admin-ajax.php'}?action=rb_quick_view&product_id=${productId}&nonce=${window.rbAjax?.nonce ?? ''}`)
      .then((res) => res.text())
      .then((html) => {
        target.innerHTML = html;
        // Con el contenido real ya en el DOM, el primer control útil
        // (no el botón de cerrar, que solo existía como ancla temporal).
        getFocusable(modal)[0]?.focus();

        // Inicializar swatches de WooCommerce
        if (typeof window.initSwatches === 'function') {
          window.initSwatches();
        }

        // Re-inicializar plugin nativo wc_variation_form si existe jQuery
        if (window.jQuery) {
          const $form = window.jQuery(target).find('.variations_form');
          if ($form.length > 0) {
            $form.wc_variation_form();
          }
        }

        // Miniaturas de galería interactivas dentro del modal
        const mainImg = target.querySelector('#qv-main-image');
        const thumbs = target.querySelectorAll('[data-qv-thumb]');
        thumbs.forEach((thumbBtn) => {
          thumbBtn.addEventListener('click', () => {
            const newSrc = thumbBtn.getAttribute('data-qv-thumb');
            if (mainImg && newSrc) {
              mainImg.src = newSrc;
              thumbs.forEach((t) => {
                t.classList.remove('border-white', 'ring-2', 'ring-white/20', 'opacity-100');
                t.classList.add('border-line/60', 'opacity-60');
              });
              thumbBtn.classList.remove('border-line/60', 'opacity-60');
              thumbBtn.classList.add('border-white', 'ring-2', 'ring-white/20', 'opacity-100');
            }
          });
        });
      })
      .catch((err) => {
        console.error(err);
        target.innerHTML = `<p class="text-sm text-red-400 py-8 text-center">Error al cargar la vista rápida del producto.</p>`;
      });
  }, true);

  // Cerrar modal al hacer clic/tap en cualquier disparador de cierre o backdrop
  document.addEventListener('click', (e) => {
    if (modal.classList.contains('hidden')) return;
    if (e.target === modal || e.target.closest('[data-quick-view-close], [data-close-quick-view]')) {
      e.preventDefault();
      closeModal();
    }
  });

  // Cerrar modal con tecla Escape
  document.addEventListener('keydown', (e) => {
    if (modal.classList.contains('hidden')) return;

    if (e.key === 'Escape') {
      closeModal();
      return;
    }

    if (e.key === 'Tab') {
      const focusable = getFocusable(modal);
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  });
})();

// Interceptar formularios de añadir al carrito GLOBALMENTE (Single Product + Quick View Modal)
document.addEventListener('submit', (e) => {
  const form = e.target.closest('form.cart');
  if (!form) return;

  // No interceptar si ya estamos en la página final de checkout
  if (document.body.classList.contains('woocommerce-checkout')) {
    return;
  }

  // Prevenir envíos dobles o múltiples peticiones simultáneas
  if (form.dataset.submitting === 'true') {
    e.preventDefault();
    e.stopImmediatePropagation();
    return;
  }

  e.preventDefault();
  form.dataset.submitting = 'true';

  const submitBtn = e.submitter || form.querySelector('button[type="submit"], .single_add_to_cart_button');
  const originalText = submitBtn ? submitBtn.innerHTML : 'Añadir al carrito';

  // Si es producto variable y no se ha seleccionado talla, seleccionar automáticamente la primera disponible o alertar
  if (form.classList.contains('variations_form')) {
    const varInput = form.querySelector('input.variation_id, input[name="variation_id"]');
    const selects = [...form.querySelectorAll('.variations select')];

    // Verificar si hay selects sin valor seleccionado
    const emptySelects = selects.filter((sel) => !sel.value);
    if (emptySelects.length > 0) {
      emptySelects.forEach((sel) => {
        const row = sel.closest('tr') || sel.parentElement;
        const firstSwatch = row ? row.querySelector('.rb-swatch:not([disabled])') : form.querySelector('.rb-swatch:not([disabled])');
        if (firstSwatch) {
          firstSwatch.click();
        }
      });
    }

    const stillEmpty = selects.filter((sel) => !sel.value);
    if (stillEmpty.length > 0) {
      alert('Por favor selecciona una talla antes de añadir al carrito.');
      form.dataset.submitting = 'false';
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.style.pointerEvents = '';
        submitBtn.innerHTML = originalText;
      }
      return;
    }

    // Sincronizar variation_id si viene en 0 o vacío
    if (varInput && (!varInput.value || varInput.value === '0')) {
      try {
        const rawVars = form.dataset.product_variations || form.getAttribute('data-product_variations');
        if (rawVars) {
          const parsed = JSON.parse(rawVars);
          if (Array.isArray(parsed) && parsed.length > 0) {
            const matching = parsed.find((v) => {
              if (!v.attributes) return false;
              return Object.entries(v.attributes).every(([attrName, attrVal]) => {
                if (!attrVal) return true;
                const field = form.querySelector(`[name="${attrName}"]`);
                return field && field.value.toLowerCase() === attrVal.toLowerCase();
              });
            }) || parsed[0];
            if (matching && matching.variation_id) {
              varInput.value = String(matching.variation_id);
            }
          }
        }
      } catch (err) {
        console.warn('No se pudo resolver la variación localmente:', err);
      }
    }
  }

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.style.pointerEvents = 'none';
    submitBtn.innerHTML = `
      <svg class="size-4 animate-spin inline-block mr-2" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      Añadiendo...
    `;
  }

  const formData = new FormData(form);
  formData.append('action', 'rb_quick_add');
  if (window.rbAjax?.nonce) {
    formData.append('nonce', window.rbAjax.nonce);
  }

  // Asegurar que siempre viaje product_id
  if (!formData.has('product_id')) {
    const fallbackId = formData.get('add-to-cart') ||
      (submitBtn && submitBtn.name === 'add-to-cart' ? submitBtn.value : null) ||
      form.querySelector('button[name="add-to-cart"]')?.value ||
      form.querySelector('input[name="add-to-cart"]')?.value ||
      form.querySelector('input[name="product_id"]')?.value ||
      form.dataset.productId ||
      form.getAttribute('data-product_id');

    if (fallbackId) {
      formData.append('product_id', fallbackId);
    }
  }

  // "add-to-cart" es el disparador del formulario clásico de WooCommerce
  // (WC_Form_Handler::add_to_cart_action, en wp_loaded). Esa petición SIEMPRE
  // llega a wp_loaded aunque sea AJAX, así que enviarlo aquí hacía que
  // WooCommerce agregara el producto una vez y rb_quick_add lo agregara otra
  // — mismo click, el doble en el carrito. Ya no hace falta en el servidor:
  // se usó arriba sólo para derivar product_id si faltaba.
  formData.delete('add-to-cart');

  fetch(window.rbAjax?.url ?? '/wp-admin/admin-ajax.php', {
    method: 'POST',
    body: formData,
  })
    .then(async (res) => {
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch {
        return {
          success: false,
          message: 'Hubo un inconveniente al procesar tu solicitud. Por favor intenta de nuevo.',
        };
      }
    })
    .then((data) => {
      if (!data || typeof data !== 'object') {
        throw new Error('Respuesta inválida del servidor');
      }

      if (data.fragments) {
        Object.entries(data.fragments).forEach(([selector, html]) => {
          const el = document.querySelector(selector);
          if (el) el.outerHTML = html;
        });
      }

      if (data.success || data.fragments) {
        // Si el modal de Quick View estaba abierto, cerrarlo
        const modal = document.querySelector('[data-quick-view-modal]');
        if (modal && !modal.classList.contains('hidden')) {
          modal.classList.add('hidden');
          modal.classList.remove('flex');
          document.body.classList.remove('overflow-hidden');
        }

        // Abrir el drawer del carrito
        openCartDrawer();

        if (data.ga4_item && window.rbTrack) {
          const lineValue = data.ga4_item.price * data.ga4_item.quantity;
          window.rbTrack('add_to_cart', {
            currency: 'COP',
            value: lineValue,
            items: [data.ga4_item],
          }, 'AddToCart', {
            content_ids: [String(data.ga4_item.item_id)],
            content_type: 'product',
            value: lineValue,
            currency: 'COP',
          });
        }
      } else {
        alert(data.data?.message || data.message || 'Por favor selecciona las opciones requeridas antes de añadir al carrito.');
      }
    })
    .catch((err) => {
      console.error('Error al añadir al carrito:', err);
      alert('Hubo un inconveniente de conexión al añadir al carrito. Por favor intenta de nuevo.');
    })
    .finally(() => {
      form.dataset.submitting = 'false';
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.style.pointerEvents = '';
        submitBtn.innerHTML = originalText;
      }
    });
});

// Escuchar evento nativo de WooCommerce 'added_to_cart'
if (window.jQuery) {
  window.jQuery(document.body).on('added_to_cart', (event, fragments) => {
    if (fragments) {
      Object.entries(fragments).forEach(([selector, html]) => {
        const el = document.querySelector(selector);
        if (el) el.outerHTML = html;
      });
    }
    openCartDrawer();
  });
}

// Eliminar ítem del carrito desde el Drawer
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-remove-cart-item]');
  if (!btn) return;
  e.preventDefault();

  const cartItemKey = btn.dataset.removeCartItem;
  const formData = new FormData();
  formData.append('action', 'rb_remove_cart_item');
  formData.append('cart_item_key', cartItemKey);
  formData.append('nonce', window.rbAjax?.nonce ?? '');

  fetch(window.rbAjax?.url ?? '/wp-admin/admin-ajax.php', {
    method: 'POST',
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.fragments) {
        Object.entries(data.fragments).forEach(([selector, html]) => {
          const el = document.querySelector(selector);
          if (el) el.outerHTML = html;
        });
      }
    });
});

// Cambiar cantidad (+ / -) desde el Mini-Carrito en tiempo real
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-change-cart-qty]');
  if (!btn) return;
  e.preventDefault();

  const cartItemKey = btn.dataset.changeCartQty;
  const quantity = Number(btn.dataset.qty);

  btn.disabled = true;
  btn.classList.add('opacity-50');

  const formData = new FormData();
  formData.append('action', 'rb_change_cart_qty');
  formData.append('cart_item_key', cartItemKey);
  formData.append('quantity', quantity);
  formData.append('nonce', window.rbAjax?.nonce ?? '');

  fetch(window.rbAjax?.url ?? '/wp-admin/admin-ajax.php', {
    method: 'POST',
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.fragments) {
        Object.entries(data.fragments).forEach(([selector, html]) => {
          const el = document.querySelector(selector);
          if (el) el.outerHTML = html;
        });
      }
    });
});

// Sincronización reactiva del badge del carrito (esquina superior derecha, solo visible cuando count >= 1)
function syncCartBadge() {
  document.querySelectorAll('[data-cart-count]').forEach((badge) => {
    const count = parseInt(badge.textContent.trim(), 10) || 0;
    if (count > 0) {
      badge.classList.remove('hidden', 'opacity-0', 'scale-75');
      badge.classList.add('scale-100', 'opacity-100');
      badge.style.display = '';
    } else {
      badge.classList.add('hidden', 'opacity-0', 'scale-75');
      badge.classList.remove('scale-100', 'opacity-100');
      badge.style.display = 'none';
    }
  });
}

document.addEventListener('DOMContentLoaded', syncCartBadge);
if (window.jQuery) {
  window.jQuery(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed wc_fragments_loaded', () => {
    setTimeout(syncCartBadge, 30);
  });
}

/* -------------------------------------------------------------------------
 | Scroll Reveal Observer para micro-animaciones al desplazarse
 * ---------------------------------------------------------------------- */

(function initScrollReveal() {
  const elements = document.querySelectorAll('[data-reveal]');
  if (!elements.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.dataset.reveal = 'true';
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 }
  );

  elements.forEach((el) => observer.observe(el));
})();

/* -------------------------------------------------------------------------
 | Footer 21st.dev Text Hover Effect Observer (Puntero interactivo sobre SVG)
 * ---------------------------------------------------------------------- */

(function initFooterTextHover() {
  const svg = document.querySelector('[data-footer-text-effect]');
  if (!svg) return;

  const mask = svg.querySelector('#rbRevealMask');

  svg.addEventListener('mousemove', (e) => {
    const rect = svg.getBoundingClientRect();
    const cx = ((e.clientX - rect.left) / rect.width) * 100;
    const cy = ((e.clientY - rect.top) / rect.height) * 100;
    if (mask) {
      mask.setAttribute('cx', `${cx}%`);
      mask.setAttribute('cy', `${cy}%`);
    }
  });
})();

/* -------------------------------------------------------------------------
 | Galería de Producto Enterprise con Zoom Magnificador y Caja de Luz Modal
 * ---------------------------------------------------------------------- */

(function initEnterpriseGallery() {
  const container = document.querySelector('[data-product-gallery]');
  if (!container) return;

  const mainContainer = container.querySelector('[data-gallery-main-container]');
  const mainImg = container.querySelector('[data-gallery-main-img]');

  // Fuente de verdad de las URLs: el Blade sólo pinta botones de miniatura
  // cuando hay más de 1 imagen, así que con una sola imagen no existe ningún
  // `data-gallery-thumb` del que leer la URL. Sin esta lista, abrir la caja
  // de luz con un único producto (el caso más común) dejaba `<img>` en blanco.
  let imageUrls = [];
  try {
    imageUrls = JSON.parse(container.dataset.images || '[]');
  } catch {
    imageUrls = [];
  }
  const totalImages = imageUrls.length;

  // El Blade pinta dos tiras de miniaturas (escritorio vertical + móvil
  // horizontal) para el mismo conjunto de imágenes: son vistas duplicadas
  // del mismo índice, no diapositivas distintas. Se agrupan por data-index
  // para que el estado activo se sincronice en ambas tiras a la vez.
  const allThumbs = [...container.querySelectorAll('[data-gallery-thumb]')];
  const thumbsByIndex = new Map();
  allThumbs.forEach((thumb) => {
    const idx = Number(thumb.dataset.index) || 0;
    if (!thumbsByIndex.has(idx)) thumbsByIndex.set(idx, []);
    thumbsByIndex.get(idx).push(thumb);
  });

  const lightboxModal = container.querySelector('[data-lightbox-modal]');
  const lightboxImg = container.querySelector('[data-lightbox-img]');
  const lightboxCounter = container.querySelector('[data-lightbox-counter]');
  const openLightboxBtn = container.querySelector('[data-open-lightbox]');
  const closeLightboxBtn = container.querySelector('[data-close-lightbox]');
  const prevBtn = container.querySelector('[data-lightbox-prev]');
  const nextBtn = container.querySelector('[data-lightbox-next]');

  let currentIndex = 0;

  // Foto de la variación seleccionada (talla del marco/color), cuando
  // difiere de las miniaturas de la galería del producto: WooCommerce
  // dispara `show_variation` con la imagen de la variación (ver
  // initSwatches, más abajo), pero esa imagen no tiene un índice dentro
  // de `imageUrls` — es una foto aparte, no una miniatura más. Se guarda
  // separado para que abrir la caja de luz mientras hay una variación
  // seleccionada muestre esa foto y no la que tocaría por índice.
  let variationOverrideUrl = null;

  function applyVariationImage(url, alt) {
    if (!url) return;
    variationOverrideUrl = url;

    // Normalizar URLs para encontrar la miniatura correspondiente en la galería
    const getCleanPath = (u) => {
      try {
        const parsed = new URL(u, window.location.href);
        return parsed.pathname;
      } catch {
        return String(u || '').split('?')[0];
      }
    };

    const targetPath = getCleanPath(url);
    const matchedThumbIndex = imageUrls.findIndex((u) => {
      if (!u) return false;
      const cleanU = getCleanPath(u);
      return cleanU === targetPath || cleanU.endsWith(targetPath) || targetPath.endsWith(cleanU);
    });

    if (matchedThumbIndex !== -1) {
      currentIndex = matchedThumbIndex;
      thumbsByIndex.forEach((thumbsAtIndex, idx) => {
        thumbsAtIndex.forEach((thumb) => {
          const isActive = idx === matchedThumbIndex;
          thumb.dataset.active = isActive ? 'true' : 'false';
          if (isActive) {
            thumb.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
          }
        });
      });
      if (lightboxCounter) {
        lightboxCounter.textContent = `${matchedThumbIndex + 1} / ${totalImages}`;
      }
    } else {
      thumbsByIndex.forEach((thumbsAtIndex) => {
        thumbsAtIndex.forEach((thumb) => {
          thumb.dataset.active = 'false';
        });
      });
    }

    if (mainImg) {
      mainImg.removeAttribute('srcset');
      mainImg.src = url;
      if (alt) mainImg.alt = alt;
      mainImg.dataset.full = url;
      // Si el puntero estaba sobre la foto cuando se cambió de talla/color,
      // la imagen nueva siempre entra sin zoom.
      mainImg.style.transform = 'scale(1)';
      mainImg.style.transformOrigin = 'center center';
    }

    if (lightboxImg && lightboxModal && !lightboxModal.classList.contains('hidden')) {
      lightboxImg.src = url;
    }
  }

  function clearVariationImage() {
    if (!variationOverrideUrl) return;
    variationOverrideUrl = null;
    setActiveImage(0);
  }

  window.rbApplyVariationImage = applyVariationImage;
  window.rbClearVariationImage = clearVariationImage;

  if (window.pendingVariationImage) {
    applyVariationImage(window.pendingVariationImage.url, window.pendingVariationImage.alt);
    window.pendingVariationImage = null;
  }

  // Sincronizar inmediatamente si ya había un swatch de color seleccionado al cargar la galería
  const activeVarForm = document.querySelector('.variations_form');
  if (activeVarForm && typeof window.rbSyncVariationImage === 'function') {
    window.rbSyncVariationImage(activeVarForm);
  }

  function setActiveImage(index) {
    if (!totalImages || index < 0 || index >= totalImages) return;
    currentIndex = index;

    thumbsByIndex.forEach((thumbsAtIndex, idx) => {
      thumbsAtIndex.forEach((thumb) => {
        const isActive = idx === index;
        thumb.dataset.active = isActive ? 'true' : 'false';
        if (isActive) {
          thumb.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
        }
      });
    });

    const fullUrl = imageUrls[index];

    if (mainImg && fullUrl) {
      mainImg.removeAttribute('srcset');
      mainImg.src = fullUrl;
      mainImg.dataset.full = fullUrl;
    }

    if (lightboxImg && fullUrl) {
      lightboxImg.src = fullUrl;
    }

    if (lightboxCounter) {
      lightboxCounter.textContent = `${index + 1} / ${totalImages}`;
    }
  }

  // Evento clic en miniaturas (ambas tiras comparten el mismo manejador)
  allThumbs.forEach((thumb) => {
    thumb.addEventListener('click', () => {
      const idx = Number(thumb.dataset.index) || 0;
      variationOverrideUrl = null; // navegar la galería a mano gana sobre la foto de variación
      setActiveImage(idx);
    });
  });

  // Control de scroll y desvanecimiento inferior si hay muchas miniaturas
  const thumbsTrack = container.querySelector('[data-gallery-thumbs-track]');
  const fadeIndicator = container.querySelector('[data-gallery-fade-indicator]');
  if (thumbsTrack && fadeIndicator) {
    thumbsTrack.addEventListener('scroll', () => {
      const isAtBottom = thumbsTrack.scrollHeight - thumbsTrack.scrollTop - thumbsTrack.clientHeight < 24;
      fadeIndicator.style.opacity = isAtBottom ? '0' : '1';
    }, { passive: true });
  }

  // Zoom de lupa al pasar el cursor en escritorio
  if (mainContainer && mainImg) {
    mainContainer.addEventListener('mousemove', (e) => {
      if (window.innerWidth < 768) return;
      const rect = mainContainer.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * 100;
      const y = ((e.clientY - rect.top) / rect.height) * 100;

      // 1.8x, no 2.2x: con object-contain la foto ya se ve completa, así
      // que el zoom es para mirar un detalle, no para compensar un
      // recorte. A 2.2x se perdía la referencia de qué parte se estaba
      // mirando.
      mainImg.style.transformOrigin = `${x}% ${y}%`;
      mainImg.style.transform = 'scale(1.8)';
    });

    mainContainer.addEventListener('mouseleave', () => {
      mainImg.style.transform = 'scale(1)';
      mainImg.style.transformOrigin = 'center center';
    });

    // Clic en la imagen abre la caja de luz
    mainContainer.addEventListener('click', (e) => {
      if (e.target.closest('[data-open-lightbox]')) return;
      openLightbox();
    });
  }

  // Apertura / Cierre de la Caja de Luz Modal
  function openLightbox() {
    if (!lightboxModal) return;

    if (variationOverrideUrl && lightboxImg) {
      lightboxImg.src = variationOverrideUrl;
      if (lightboxCounter) lightboxCounter.textContent = `${currentIndex + 1} / ${totalImages}`;
    } else {
      setActiveImage(currentIndex);
    }

    lightboxModal.classList.remove('hidden');
    lightboxModal.classList.add('flex');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    if (!lightboxModal) return;
    lightboxModal.classList.add('hidden');
    lightboxModal.classList.remove('flex');
    document.body.style.overflow = '';
  }

  openLightboxBtn?.addEventListener('click', openLightbox);
  closeLightboxBtn?.addEventListener('click', closeLightbox);

  lightboxModal?.addEventListener('click', (e) => {
    if (e.target === lightboxModal) closeLightbox();
  });

  prevBtn?.addEventListener('click', () => {
    variationOverrideUrl = null;
    const newIdx = (currentIndex - 1 + totalImages) % totalImages;
    setActiveImage(newIdx);
  });

  nextBtn?.addEventListener('click', () => {
    variationOverrideUrl = null;
    const newIdx = (currentIndex + 1) % totalImages;
    setActiveImage(newIdx);
  });

  document.addEventListener('keydown', (e) => {
    if (!lightboxModal || lightboxModal.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') prevBtn?.click();
    if (e.key === 'ArrowRight') nextBtn?.click();
  });
})();

/* -------------------------------------------------------------------------
 | Animación de Contador Dinámico para Sección de Estadísticas (0 a N)
 * ---------------------------------------------------------------------- */

(function initStatCounters() {
  const counters = document.querySelectorAll('[data-stat-counter]');
  if (!counters.length) return;

  const animate = (counter) => {
    const target = Number(counter.dataset.target) || 0;
    const suffix = counter.dataset.suffix || '';
    const duration = 1600; // Duración en ms
    const startTime = performance.now();

    function update(now) {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      
      // Easing out cuadrático
      const ease = progress * (2 - progress);
      const current = Math.floor(ease * target);
      
      counter.textContent = `${current}${suffix}`;

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        counter.textContent = `${target}${suffix}`;
      }
    }

    requestAnimationFrame(update);
  };

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animate(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.2 }
  );

  counters.forEach((c) => observer.observe(c));
})();

(function initHeaderScroll() {
  const header = document.querySelector('[data-header]');
  const bottomBlur = document.querySelector('[data-bottom-blur]');
  if (!header && !bottomBlur) return;

  const SHADOW = 'shadow-[0_10px_30px_rgba(0,0,0,0.8)]';
  let scrollTimeout;
  let rafPending = false;

  function applyState() {
    rafPending = false;
    const y = window.scrollY;

    if (header) {
      if (y > 40) {
        // Scroll lejos: cabecera casi transparente sobre el contenido.
        header.classList.remove('bg-surface/95', 'bg-surface/90', 'backdrop-blur-md', 'border-line');
        header.classList.add('bg-black/20', 'backdrop-blur-lg', 'border-white/[0.06]', SHADOW);
      } else if (y > 20) {
        // Transición: ya se despegó del top, todavía con fondo sólido.
        header.classList.remove('bg-black/20', 'backdrop-blur-lg', 'border-white/[0.06]', 'bg-surface/95');
        header.classList.add('bg-surface/90', 'backdrop-blur-md', 'border-line', SHADOW);
      } else {
        // Reposo: arriba del todo.
        header.classList.remove('bg-black/20', 'backdrop-blur-lg', 'border-white/[0.06]', 'bg-surface/90', 'backdrop-blur-md', SHADOW);
        header.classList.add('bg-surface/95', 'border-line');
      }

      // Logo a h-24 (96px) en escritorio + cabecera sticky se comía buena
      // parte del viewport en cada scroll. `data-scrolled` encoge el logo
      // (ver regla en app.css) en cuanto el usuario se aleja del top.
      header.dataset.scrolled = String(y > 20);
    }

    if (bottomBlur) {
      if (y > 20) {
        bottomBlur.classList.remove('opacity-0');
      } else {
        bottomBlur.classList.add('opacity-0');
      }

      // Si el usuario deja de hacer scroll, desvanecer la sombra de fondo.
      clearTimeout(scrollTimeout);
      if (y > 20) {
        scrollTimeout = setTimeout(() => {
          bottomBlur.classList.add('opacity-0');
        }, 700); // Se oculta tras 700ms de inactividad de scroll
      }
    }
  }

  window.addEventListener('scroll', () => {
    if (rafPending) return;
    rafPending = true;
    requestAnimationFrame(applyState);
  }, { passive: true });

  applyState();
})();

/* -------------------------------------------------------------------------
 | Modal de Financiación Colombiana (ADDI, Sistecrédito, PSE)
 * ---------------------------------------------------------------------- */

(function initFinancingModal() {
  const modal = document.querySelector('[data-financing-modal]');
  if (!modal) return;

  // Mover el modal al final del body para romper el contexto de apilamiento (stacking context) y arreglar el eje Z
  document.body.appendChild(modal);

  const openBtns = document.querySelectorAll('[data-open-financing-modal]');
  const closeBtns = modal.querySelectorAll('[data-close-financing-modal]');

  function openModal() {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
  }

  openBtns.forEach((btn) => btn.addEventListener('click', openModal));
  closeBtns.forEach((btn) => btn.addEventListener('click', closeModal));

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });
})();

/* -------------------------------------------------------------------------
 | Fondo de haces de luz — verde fluorescente, ambiental en todo el sitio.
 |
 | PERFORMANCE: En móvil (< 768px) el canvas NO se inicializa — se usa un
 | fondo CSS estático. ctx.filter='blur()' a 60fps era la causa directa del
 | TBT de 29,620ms (20 long tasks de 489-774ms). En desktop se reduce a 10
 | haces, blur=20px, y se cede el hilo cada 5 frames con setTimeout para
 | no bloquear interacciones del usuario.
 * ---------------------------------------------------------------------- */

(function initBeamsBackground() {
  const canvas = document.querySelector('[data-beams-canvas]');
  const pulseEl = document.querySelector('[data-beams-pulse]');
  if (!canvas) return;

  // ── Mobile: fondo CSS estático, sin canvas, sin rAF, sin blur en GPU ──
  // En pantallas pequeñas el efecto es imperceptible bajo luz natural y
  // el costo de 18 haces + blur a 60fps es devastador para el TBT.
  if (window.innerWidth < 768) {
    canvas.style.display = 'none';
    // Pulso ambiental estático verde muy sutil vía CSS
    if (pulseEl) {
      pulseEl.style.background =
        'radial-gradient(ellipse 80% 50% at 50% 0%, hsla(160,84%,39%,0.07) 0%, transparent 70%)';
    }
    return;
  }

  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const HUE_MIN = 160;
  const HUE_RANGE = 0;
  const SATURATION = 84;
  const LIGHTNESS = 39;

  // PERFORMANCE: Reducido de 18 → 10 haces. El blur ya disuelve cualquier
  // detalle individual — más haces no aportan visualmente.
  const BEAM_COUNT = 10;

  // PERFORMANCE: Cada N frames cedemos el hilo principal vía setTimeout
  // para que los eventos de usuario (clic, scroll) no queden bloqueados.
  const YIELD_EVERY_FRAMES = 5;
  let frameCount = 0;

  let beams = [];
  let animationFrame = null;

  function createBeam(width, height) {
    return {
      x: Math.random() * width * 1.5 - width * 0.25,
      y: Math.random() * height * 1.5 - height * 0.25,
      width: 30 + Math.random() * 60,
      length: height * 2.5,
      angle: -35 + Math.random() * 10,
      speed: 0.5 + Math.random() * 0.9,
      opacity: 0.14 + Math.random() * 0.16,
      hue: HUE_MIN + Math.random() * HUE_RANGE,
      pulse: Math.random() * Math.PI * 2,
      pulseSpeed: 0.02 + Math.random() * 0.03,
    };
  }

  function resetBeam(beam, index, total) {
    const column = index % 3;
    const spacing = canvas.width / 3;
    beam.y = canvas.height + 100;
    beam.x = column * spacing + spacing / 2 + (Math.random() - 0.5) * spacing * 0.5;
    beam.width = 80 + Math.random() * 80;
    beam.speed = 0.4 + Math.random() * 0.4;
    beam.hue = HUE_MIN + (index * HUE_RANGE) / total;
    beam.opacity = 0.20 + Math.random() * 0.12;
  }

  function drawBeam(beam) {
    ctx.save();
    ctx.translate(beam.x, beam.y);
    ctx.rotate((beam.angle * Math.PI) / 180);

    const pulsingOpacity = beam.opacity * (0.8 + Math.sin(beam.pulse) * 0.2);
    const gradient = ctx.createLinearGradient(0, 0, 0, beam.length);
    gradient.addColorStop(0,   `hsla(${beam.hue}, ${SATURATION}%, ${LIGHTNESS}%, 0)`);
    gradient.addColorStop(0.1, `hsla(${beam.hue}, ${SATURATION}%, ${LIGHTNESS}%, ${pulsingOpacity * 0.5})`);
    gradient.addColorStop(0.4, `hsla(${beam.hue}, ${SATURATION}%, ${LIGHTNESS}%, ${pulsingOpacity})`);
    gradient.addColorStop(0.6, `hsla(${beam.hue}, ${SATURATION}%, ${LIGHTNESS}%, ${pulsingOpacity})`);
    gradient.addColorStop(0.9, `hsla(${beam.hue}, ${SATURATION}%, ${LIGHTNESS}%, ${pulsingOpacity * 0.5})`);
    gradient.addColorStop(1,   `hsla(${beam.hue}, ${SATURATION}%, ${LIGHTNESS}%, 0)`);

    ctx.fillStyle = gradient;
    ctx.fillRect(-beam.width / 2, 0, beam.width, beam.length);
    ctx.restore();
  }

  // PERFORMANCE: DPR máximo 1.5. A dpr=3 el blur procesa 9× más píxeles
  // sin ningún beneficio visual (ya lo disuelve el blur de 20px).
  const MAX_DPR = 1.5;

  function updateCanvasSize() {
    const dpr = Math.min(window.devicePixelRatio || 1, MAX_DPR);
    canvas.width  = window.innerWidth  * dpr;
    canvas.height = window.innerHeight * dpr;
    canvas.style.width  = `${window.innerWidth}px`;
    canvas.style.height = `${window.innerHeight}px`;
    ctx.scale(dpr, dpr);
    beams = Array.from({ length: BEAM_COUNT }, () => createBeam(canvas.width, canvas.height));
  }

  function drawFrame() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    // PERFORMANCE: blur reducido de 35px → 20px. El efecto ambiental es
    // idéntico visualmente — el blur ya aplana cualquier detalle individual.
    ctx.filter = 'blur(20px)';

    beams.forEach((beam, index) => {
      beam.y     -= beam.speed;
      beam.pulse += beam.pulseSpeed;
      if (beam.y + beam.length < -100) resetBeam(beam, index, beams.length);
      drawBeam(beam);
    });
  }

  function animate() {
    drawFrame();
    frameCount++;

    // PERFORMANCE: Cada YIELD_EVERY_FRAMES cedemos el hilo principal.
    // setTimeout(fn, 0) permite que el browser procese eventos de usuario
    // (clic, scroll, input) antes del siguiente frame de animación.
    // Resultado: en lugar de 20 long tasks de 700ms, hay micro-pauses
    // que reducen el TBT drásticamente sin afectar la fluidez visual.
    if (frameCount % YIELD_EVERY_FRAMES === 0) {
      animationFrame = null;
      setTimeout(() => {
        if (!document.hidden) animationFrame = requestAnimationFrame(animate);
      }, 0);
    } else {
      animationFrame = requestAnimationFrame(animate);
    }
  }

  updateCanvasSize();

  let resizeTimer = null;
  window.addEventListener('resize', () => {
    // En desktop, si el viewport baja de 768px (rotar a portrait en tablet),
    // detener la animación para no desperdiciar recursos.
    if (window.innerWidth < 768) {
      if (animationFrame) cancelAnimationFrame(animationFrame);
      animationFrame = null;
      canvas.style.display = 'none';
      return;
    }
    canvas.style.display = '';
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(updateCanvasSize, 150);
  });

  // Sin movimiento: fotograma estático.
  if (prefersReducedMotion.matches) {
    ctx.filter = 'blur(20px)';
    beams.forEach((beam) => drawBeam(beam));
    return;
  }

  animate();

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      if (animationFrame) cancelAnimationFrame(animationFrame);
      animationFrame = null;
    } else if (!animationFrame && window.innerWidth >= 768) {
      frameCount = 0;
      animate();
    }
  });

  window.addEventListener('pagehide', () => {
    if (animationFrame) cancelAnimationFrame(animationFrame);
  });
})();

/* -------------------------------------------------------------------------
 | Conmutador de Vistas del Catálogo y Secciones (Grid, Compact, List)
 * ---------------------------------------------------------------------- */
(function initViewSwitcher() {
  const switchers = document.querySelectorAll('[data-catalog-view-switcher]');
  switchers.forEach((switcher) => {
    const targetSelector = switcher.getAttribute('data-target-grid') || '#catalog-grid-container';
    const targetGrid = document.querySelector(targetSelector);
    if (!targetGrid) return;

    const buttons = switcher.querySelectorAll('[data-view-btn]');

    const setMode = (mode) => {
      buttons.forEach((b) => {
        const isActive = b.getAttribute('data-view-btn') === mode;
        b.setAttribute('data-active', String(isActive));
        b.dataset.active = String(isActive);
      });

      targetGrid.classList.remove('view-mode-grid', 'view-mode-compact', 'view-mode-list');
      if (mode === 'compact') {
        targetGrid.classList.add('view-mode-compact');
      } else if (mode === 'list') {
        targetGrid.classList.add('view-mode-list');
      } else {
        targetGrid.classList.add('view-mode-grid');
      }
      localStorage.setItem('rb_catalog_view_mode', mode);
    };

    const isMobile = window.innerWidth < 768;
    const defaultMode = isMobile ? 'compact' : 'grid';
    const savedMode = localStorage.getItem('rb_catalog_view_mode') || defaultMode;
    setMode(savedMode);

    buttons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const mode = btn.getAttribute('data-view-btn');
        setMode(mode);
      });
    });
  });
})();

/* -------------------------------------------------------------------------
 | Banner de Recomendación Personalizada de Talla
 * ---------------------------------------------------------------------- */
(function initSizeRecommendation() {
  const banner = document.getElementById('rb-size-recommendation-banner');
  if (!banner) return;

  function getPageDiscipline() {
    const el = document.querySelector('[data-bike-discipline]');
    const disc = el?.dataset?.bikeDiscipline?.toLowerCase();
    if (disc && ['road', 'mtb', 'gravel'].includes(disc)) {
      return disc;
    }

    const texts = [
      document.querySelector('h1')?.textContent || '',
      ...[...document.querySelectorAll('.breadcrumbs, nav[aria-label="Breadcrumb"], .rb-breadcrumbs, [data-rb-track-view]')].map((e) => e.textContent || e.dataset?.rbTrackView || '')
    ].join(' ').toLowerCase();

    if (/\bgravel\b/i.test(texts)) return 'gravel';
    if (/\b(mtb|mountain|monta[nñ]a)\b/i.test(texts)) return 'mtb';
    if (/\b(ruta|road|carretera)\b/i.test(texts)) return 'road';

    return null;
  }

  function getStoredSize() {
    try {
      const data = localStorage.getItem('rb_user_bike_size');
      if (data) return JSON.parse(data);
    } catch (e) {
      console.error(e);
    }
    // Fallback de cookie
    const match = document.cookie.match(/(?:^|; )rb_user_bike_size=([^;]*)/);
    if (match) {
      return { size: match[1] };
    }
    return null;
  }

  function getStoredSizeForCurrentProduct() {
    const raw = getStoredSize();
    if (!raw) return null;

    const currentDisc = getPageDiscipline();

    // Si tiene perfil multidisiciplinar
    if (raw.disciplines && currentDisc && raw.disciplines[currentDisc]) {
      const discData = raw.disciplines[currentDisc];
      return {
        size: discData.size,
        numeric: discData.numeric,
        desc: discData.desc,
        discipline: currentDisc,
      };
    }

    // Si tiene medidas corporales almacenadas, calcular la talla en vivo para esta disciplina
    if (raw.height || raw.inseam) {
      const height = parseInt(raw.height || 175, 10);
      const inseam = parseInt(raw.inseam || Math.round(height * 0.457), 10);
      const disc = currentDisc || raw.discipline || 'road';

      if (disc === 'mtb') {
        const inches = Math.round(((inseam * 0.67 * 0.3937) - 4) * 2) / 2;
        let letter = 'M';
        if (inches < 13) letter = 'XXS';
        else if (inches < 15) letter = 'XS';
        else if (inches < 17) letter = 'S';
        else if (inches < 19) letter = 'M';
        else if (inches < 21) letter = 'L';
        else letter = 'XL';
        return { size: letter, numeric: inches, desc: `${inches}"`, discipline: 'mtb' };
      } else if (disc === 'gravel') {
        const cm = Math.round(inseam * 0.63);
        let letter = 'M';
        if (cm < 47) letter = 'XXS';
        else if (cm < 50) letter = 'XS';
        else if (cm < 53) letter = 'S';
        else if (cm < 56) letter = 'M';
        else if (cm < 59) letter = 'L';
        else letter = 'XL';
        return { size: letter, numeric: cm, desc: `${cm} cm`, discipline: 'gravel' };
      } else {
        const cm = Math.round(inseam * 0.67);
        let letter = 'M';
        if (cm < 50) letter = 'XXS';
        else if (cm < 52) letter = 'XS';
        else if (cm < 54) letter = 'S';
        else if (cm < 56) letter = 'M';
        else if (cm < 58) letter = 'L';
        else letter = 'XL';
        return { size: letter, numeric: cm, desc: `${cm} cm`, discipline: 'road' };
      }
    }

    return raw;
  }

  // Algunos productos (bicicletas de ruta Trek/Orbea, MTB Zebra/Alligator/
  // Monkey) no usan tallas por letra sino por número (cm de cuadro o
  // pulgadas de MTB) — comparar solo contra sizeLetter nunca encontraba
  // nada ahí. Si todas las opciones disponibles son numéricas, se elige la
  // más cercana al número calculado; si no, se compara por letra como
  // antes.
  function pickBestSizeOption(items, getText, sizeLetter, sizeNumeric) {
    if (!items.length) return null;

    const numericItems = items
      .map((item) => ({ item, num: parseFloat(String(getText(item)).trim().replace(',', '.')) }))
      .filter(({ num }) => !Number.isNaN(num));

    if (sizeNumeric != null && numericItems.length === items.length) {
      let best = numericItems[0];
      let bestDiff = Math.abs(best.num - sizeNumeric);
      numericItems.forEach((candidate) => {
        const diff = Math.abs(candidate.num - sizeNumeric);
        if (diff < bestDiff) {
          best = candidate;
          bestDiff = diff;
        }
      });
      return best.item;
    }

    return items.find((item) => {
      const txt = String(getText(item)).trim().toUpperCase();
      return txt === sizeLetter || txt.includes(sizeLetter);
    }) || null;
  }

  // Un producto puede tener varios atributos con swatches (Talla Y Color,
  // p.ej. "Casco RC" con pa_talla L/M/S y pa_color Blanco/Gris/Mate) — los
  // botones .rb-swatch de TODOS los atributos se veían idénticos y sin
  // forma de distinguirlos, así que la calculadora de talla terminaba
  // comparando también contra colores ("MATE" contiene la letra "M" y
  // coincidía como si fuera la talla M). initSwatches ahora marca cada
  // swatch con el atributo real de WooCommerce del que viene
  // (button.dataset.attribute); esto filtra por ahí antes de comparar.
  function isSizeAttributeName(name) {
    const n = (name || '').toLowerCase();
    return n.includes('talla') || n.includes('size');
  }

  function autoSelectSize(sizeLetter, sizeNumeric) {
    if (!sizeLetter) return;

    // 1. Buscar en Swatches / Botones visuales si existen
    const swatches = [...document.querySelectorAll('.rb-swatch')].filter((s) => isSizeAttributeName(s.dataset.attribute));
    const matchedSwatch = pickBestSizeOption(swatches, (s) => s.textContent || '', sizeLetter, sizeNumeric);

    if (matchedSwatch) {
      if (matchedSwatch.dataset.selected !== 'true') {
        matchedSwatch.click();
      }
    } else {
      // 2. Fallback: Selector estándar de WooCommerce
      const selects = [...document.querySelectorAll('.variations select')].filter((s) => isSizeAttributeName(s.name || s.id));
      selects.forEach(select => {
        const options = [...select.options].filter((opt) => opt.value !== '');
        const option = pickBestSizeOption(options, (opt) => opt.textContent || opt.value, sizeLetter, sizeNumeric);
        if (option && select.value !== option.value) {
          select.value = option.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
    }
  }

  function updateBanner(shouldAutoSelect = false) {
    const data = getStoredSizeForCurrentProduct();
    if (data && data.size) {
      const discLabels = { mtb: 'Montaña / MTB', road: 'Ruta / Road', gravel: 'Gravel' };
      const currentDisc = getPageDiscipline() || data.discipline;
      const discTag = discLabels[currentDisc] ? ` para ${discLabels[currentDisc]}` : '';
      const displayDesc = data.desc ? ` (${data.desc})` : '';

      // Mostrar banner con talla recomendada
      banner.innerHTML = `
        <div class="flex items-center justify-between gap-3 p-4 rounded-xl border border-[#10b981]/30 bg-[#10b981]/5 animate-fade-in">
          <div class="flex items-center gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#10b981] text-black font-black text-lg shadow-inner">
              ${data.size}
            </div>
            <div>
              <p class="text-xs font-semibold text-[#10b981] uppercase tracking-wider">Talla Biomecánica Recomendada${discTag}</p>
              <p class="text-xs text-ink-muted leading-tight">Marco ideal: <strong class="text-white">${data.size}${displayDesc}</strong> según tu biomecánica.</p>
            </div>
          </div>
          <button type="button" data-open-size-finder class="text-xs font-bold text-ink hover:text-[#10b981] transition-colors underline cursor-pointer shrink-0">
            Recalcular
          </button>
        </div>
      `;
      
      if (shouldAutoSelect) {
        autoSelectSize(data.size, data.numeric);
      }
    } else {
      // Mostrar llamada a la acción para calcular talla
      banner.innerHTML = `
        <div class="flex items-center justify-between gap-3 p-4 rounded-xl border border-line bg-surface-raised">
          <div class="flex items-center gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-surface border border-line text-ink-subtle text-lg">
              📏
            </div>
            <div>
              <p class="text-xs font-semibold text-ink uppercase tracking-wider">¿No estás seguro de tu talla?</p>
              <p class="text-xs text-ink-muted leading-tight">Calcula tu tamaño de cuadro ideal en 1 minuto.</p>
            </div>
          </div>
          <button type="button" data-open-size-finder class="inline-flex items-center justify-center rounded-lg bg-white px-3.5 py-1.5 text-xs font-bold text-black transition-all hover:bg-white/90 active:scale-95 cursor-pointer shadow shrink-0">
            Calcular talla
          </button>
        </div>
      `;
    }
  }

  // Escuchar cálculos de talla en tiempo real (solo cuando el usuario usa el modal)
  window.addEventListener('rb_size_calculated', (e) => {
    updateBanner(true);
  });

  // Renderizar banner informativo al cargar (sin sobreescribir la interacción del usuario)
  updateBanner(false);
})();

/* -------------------------------------------------------------------------
 | Modal de Búsqueda Global Enterprise (Live AJAX Search & Shortcuts)
 * ---------------------------------------------------------------------- */
(function initSearchOverlay() {
  const overlay = document.querySelector('[data-search-overlay]');
  if (!overlay) return;

  const triggerBtns = document.querySelectorAll('[data-search-trigger]');
  const closeBtns = overlay.querySelectorAll('[data-search-close]');
  const form = overlay.querySelector('[data-search-form]');
  const input = overlay.querySelector('[data-search-input]');
  const spinner = overlay.querySelector('[data-search-spinner]');
  const clearBtn = overlay.querySelector('[data-search-clear]');
  
  const defaultView = overlay.querySelector('[data-search-default-view]');
  const resultsView = overlay.querySelector('[data-search-results-view]');

  const recentContainer = overlay.querySelector('[data-search-recent-container]');
  const recentList = overlay.querySelector('[data-search-recent-list]');
  const clearRecentBtn = overlay.querySelector('[data-search-clear-recent]');

  const popularTags = overlay.querySelectorAll('[data-search-popular-tag]');
  const taxonomiesContainer = overlay.querySelector('[data-search-taxonomies]');
  const categoriesList = overlay.querySelector('[data-search-categories-list]');
  const productsList = overlay.querySelector('[data-search-products-list]');
  const countLabel = overlay.querySelector('[data-search-count-label]');
  const totalCount = overlay.querySelector('[data-search-total-count]');
  const allContainer = overlay.querySelector('[data-search-all-container]');
  const allLink = overlay.querySelector('[data-search-all-link]');
  const allText = overlay.querySelector('[data-search-all-text]');
  const emptyState = overlay.querySelector('[data-search-empty-state]');
  const emptyTitle = overlay.querySelector('[data-search-empty-title]');

  overlay.inert = true;
  let lastFocused = null;
  let debounceTimer = null;
  let abortCtrl = null;
  let selectedIndex = -1;
  const searchCache = new Map();
  const STORAGE_KEY = 'rb_recent_searches';

  /* --- Funciones de Historial Reciente (localStorage) --- */
  function getRecentSearches() {
    try {
      const data = localStorage.getItem(STORAGE_KEY);
      return data ? JSON.parse(data) : [];
    } catch {
      return [];
    }
  }

  function saveRecentSearch(term) {
    if (!term || typeof term !== 'string') return;
    const clean = term.trim();
    if (clean.length < 2) return;

    let recents = getRecentSearches();
    recents = recents.filter(item => item.toLowerCase() !== clean.toLowerCase());
    recents.unshift(clean);
    recents = recents.slice(0, 6);

    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(recents));
    } catch {
      // Ignorar errores de almacenamiento
    }
    renderRecentSearches();
  }

  function removeRecentSearch(term) {
    let recents = getRecentSearches();
    recents = recents.filter(item => item.toLowerCase() !== term.toLowerCase());
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(recents));
    } catch {}
    renderRecentSearches();
  }

  function clearAllRecentSearches() {
    try {
      localStorage.removeItem(STORAGE_KEY);
    } catch {}
    renderRecentSearches();
  }

  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function highlightMatch(text, query) {
    if (!text || !query) return escapeHtml(text || '');
    const cleanQuery = query.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    if (!cleanQuery) return escapeHtml(text);
    const regex = new RegExp(`(${cleanQuery})`, 'gi');
    return escapeHtml(text).replace(regex, '<mark class="bg-transparent text-emerald-400 font-bold underline decoration-emerald-500/40">$1</mark>');
  }

  function renderRecentSearches() {
    if (!recentContainer || !recentList) return;
    const recents = getRecentSearches();

    if (recents.length === 0) {
      recentContainer.classList.add('hidden');
      recentList.innerHTML = '';
      return;
    }

    recentContainer.classList.remove('hidden');
    recentList.innerHTML = recents.map(item => {
      const escaped = escapeHtml(item);
      return `
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#14161E] border border-white/10 text-xs text-white group shadow-sm">
          <button
            type="button"
            class="flex items-center gap-1.5 hover:text-emerald-400 transition-colors cursor-pointer"
            data-recent-apply="${escaped}"
          >
            <svg class="size-3 text-ink-subtle group-hover:text-emerald-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>${escaped}</span>
          </button>
          <button
            type="button"
            class="text-ink-subtle hover:text-white p-0.5 ml-1 transition-colors cursor-pointer"
            data-recent-remove="${escaped}"
            aria-label="Eliminar ${escaped}"
          >
            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      `;
    }).join('');

    // Listeners de tags recientes
    recentList.querySelectorAll('[data-recent-apply]').forEach(btn => {
      btn.addEventListener('click', () => {
        const val = btn.getAttribute('data-recent-apply');
        if (input && val) {
          input.value = val;
          executeLiveSearch(val);
          input.focus();
        }
      });
    });

    recentList.querySelectorAll('[data-recent-remove]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const val = btn.getAttribute('data-recent-remove');
        if (val) removeRecentSearch(val);
      });
    });
  }

  if (clearRecentBtn) {
    clearRecentBtn.addEventListener('click', clearAllRecentSearches);
  }

  /* --- Control de Vistas y Renderizado de Búsqueda Predictiva --- */
  function showDefaultView() {
    if (resultsView) resultsView.classList.add('hidden');
    if (defaultView) defaultView.classList.remove('hidden');
    if (spinner) spinner.classList.add('hidden');
    selectedIndex = -1;
  }

  function showResultsView() {
    if (defaultView) defaultView.classList.add('hidden');
    if (resultsView) resultsView.classList.remove('hidden');
    selectedIndex = -1;
  }

  function renderSearchResults(data, query) {
    if (!data) return;

    showResultsView();
    const products = Array.isArray(data.products) ? data.products : [];
    const categories = Array.isArray(data.categories) ? data.categories : [];
    const brands = Array.isArray(data.brands) ? data.brands : [];

    // Categorías y Marcas
    if (taxonomiesContainer && categoriesList) {
      const taxonomyItems = [];
      categories.forEach(cat => {
        taxonomyItems.push(`
          <a
            href="${cat.url}"
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#14161F] hover:bg-emerald-500/20 border border-white/10 hover:border-emerald-500/30 text-[11px] text-white transition-all shadow-sm"
          >
            <span>📁 ${escapeHtml(cat.name)}</span>
            <span class="text-[9px] text-ink-subtle">(${cat.count})</span>
          </a>
        `);
      });

      brands.forEach(br => {
        taxonomyItems.push(`
          <a
            href="${br.url}"
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#14161F] hover:bg-emerald-500/20 border border-white/10 hover:border-emerald-500/30 text-[11px] text-white transition-all shadow-sm"
          >
            <span>🏷️ ${escapeHtml(br.name)}</span>
          </a>
        `);
      });

      if (taxonomyItems.length > 0) {
        categoriesList.innerHTML = taxonomyItems.join('');
        taxonomiesContainer.classList.remove('hidden');
        taxonomiesContainer.classList.add('flex');
      } else {
        taxonomiesContainer.classList.add('hidden');
        taxonomiesContainer.classList.remove('flex');
      }
    }

    // Lista de Productos o Estado Vacío
    if (products.length === 0) {
      if (productsList) productsList.innerHTML = '';
      if (allContainer) allContainer.classList.add('hidden');
      if (emptyState) {
        emptyState.classList.remove('hidden');
        if (emptyTitle) {
          emptyTitle.textContent = `No encontramos productos para "${query}"`;
        }
      }
      if (countLabel) countLabel.textContent = 'Sin resultados';
      if (totalCount) totalCount.textContent = '0 productos';
      return;
    }

    if (emptyState) emptyState.classList.add('hidden');
    if (countLabel) countLabel.textContent = `Productos sugeridos (${products.length})`;
    if (totalCount) totalCount.textContent = `${data.total || products.length} encontrados`;

    // Tarjetas de productos de alta fidelidad
    if (productsList) {
      productsList.innerHTML = products.map((p, idx) => {
        const titleHighlighted = highlightMatch(p.title, query);
        const imgSrc = p.image || '/wp-content/uploads/woocommerce-placeholder.png';
        const saleBadge = p.on_sale ? `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Oferta</span>` : '';
        const outOfStock = !p.in_stock ? `<span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-red-500/20 text-red-300 border border-red-500/30">Agotado</span>` : '';
        const categoryBadge = p.category ? `<span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 truncate max-w-[150px]">${escapeHtml(p.category)}</span>` : '';

        return `
          <a
            href="${p.url}"
            class="flex items-center gap-3.5 p-2.5 rounded-2xl bg-[#14161F]/70 hover:bg-white/[0.08] focus:bg-white/[0.08] border border-white/5 hover:border-emerald-500/40 focus:border-emerald-500/40 transition-all group cursor-pointer outline-none shadow-sm"
            data-search-result-item
            data-index="${idx}"
            tabindex="0"
            role="option"
          >
            <img
              src="${imgSrc}"
              alt="${escapeHtml(p.title)}"
              class="size-14 rounded-xl object-cover bg-black/40 border border-white/10 shrink-0 group-hover:scale-105 transition-transform"
              loading="lazy"
            >
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 mb-0.5">
                ${categoryBadge}
                ${saleBadge}
                ${outOfStock}
              </div>
              <h5 class="text-xs md:text-sm font-semibold text-white truncate group-hover:text-emerald-300 transition-colors">
                ${titleHighlighted}
              </h5>
              <div class="mt-0.5 text-xs text-ink-subtle font-medium">
                ${p.price_html}
              </div>
            </div>
            <div class="text-ink-subtle group-hover:text-emerald-400 group-focus:text-emerald-400 group-hover:translate-x-1 group-focus:translate-x-1 transition-all pr-1">
              <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="m8.25 5.25 6.75 6.75-6.75 6.75"/>
              </svg>
            </div>
          </a>
        `;
      }).join('');

      // Guardar en búsquedas recientes al hacer clic en un producto
      productsList.querySelectorAll('[data-search-result-item]').forEach(item => {
        item.addEventListener('click', () => {
          saveRecentSearch(query);
        });
      });
    }

    // Botón de ver todos los resultados
    if (allContainer && allLink) {
      const allUrl = data.all_url || `/?s=${encodeURIComponent(query)}&post_type=product`;
      allLink.href = allUrl;
      allLink.addEventListener('click', () => {
        saveRecentSearch(query);
      });
      if (allText) {
        allText.textContent = `Ver todos los resultados para "${query}" (${data.total || products.length} productos)`;
      }
      allContainer.classList.remove('hidden');
    }
  }

  /* --- Ejecución de Búsqueda Predictiva AJAX --- */
  function executeLiveSearch(query) {
    const clean = (query || '').trim();

    if (clearBtn) {
      if (clean.length > 0) {
        clearBtn.classList.remove('hidden');
        clearBtn.classList.add('flex');
      } else {
        clearBtn.classList.add('hidden');
        clearBtn.classList.remove('flex');
      }
    }

    if (clean.length < 2) {
      if (abortCtrl) abortCtrl.abort();
      showDefaultView();
      return;
    }

    const cacheKey = clean.toLowerCase();
    if (searchCache.has(cacheKey)) {
      if (spinner) spinner.classList.add('hidden');
      renderSearchResults(searchCache.get(cacheKey), clean);
      return;
    }

    if (spinner) spinner.classList.remove('hidden');

    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();

    const ajaxUrl = window.rbAjax?.url || '/wp-admin/admin-ajax.php';
    const targetUrl = `${ajaxUrl}?action=rb_live_search&q=${encodeURIComponent(clean)}`;

    fetch(targetUrl, {
      signal: abortCtrl.signal,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(res => {
        if (!res.ok) throw new Error('Network error');
        return res.json();
      })
      .then(json => {
        if (spinner) spinner.classList.add('hidden');
        if (json && json.success && json.data) {
          searchCache.set(cacheKey, json.data);
          renderSearchResults(json.data, clean);
        }
      })
      .catch(err => {
        if (err.name === 'AbortError') return;
        if (spinner) spinner.classList.add('hidden');
        console.warn('Error en búsqueda en vivo:', err);
      });
  }

  /* --- Input Listeners & Debounce --- */
  if (input) {
    input.addEventListener('input', (e) => {
      const val = e.target.value;
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        executeLiveSearch(val);
      }, 200);
    });

    // Tecla Enter en el formulario
    if (form) {
      form.addEventListener('submit', (e) => {
        const val = input.value.trim();
        if (val) {
          saveRecentSearch(val);
        }
      });
    }
  }

  // Botón Limpiar (X)
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      if (input) {
        input.value = '';
        input.focus();
      }
      clearBtn.classList.add('hidden');
      clearBtn.classList.remove('flex');
      showDefaultView();
    });
  }

  // Tags Populares / Tendencias (Click directo)
  popularTags.forEach(tag => {
    tag.addEventListener('click', () => {
      const term = tag.getAttribute('data-search-popular-tag');
      if (input && term) {
        input.value = term;
        saveRecentSearch(term);
        executeLiveSearch(term);
        input.focus();
      }
    });
  });

  /* --- Abrir y Cerrar Modal con Estados de Accesibilidad --- */
  function openSearch() {
    lastFocused = document.activeElement;
    overlay.inert = false;
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    document.body.style.overflow = 'hidden';

    renderRecentSearches();

    // Auto-detectar término si la URL ya tiene ?s=
    const currentParam = new URLSearchParams(window.location.search).get('s');
    if (input) {
      if (!input.value && currentParam) {
        input.value = currentParam;
        executeLiveSearch(currentParam);
      } else if (input.value) {
        executeLiveSearch(input.value);
      } else {
        showDefaultView();
      }
    }

    setTimeout(() => {
      if (input) {
        input.focus();
        input.select();
      }
    }, 100);
  }

  function closeSearch() {
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    overlay.inert = true;
    document.body.style.overflow = '';
    selectedIndex = -1;
    lastFocused?.focus();
  }

  triggerBtns.forEach(btn => btn.addEventListener('click', openSearch));
  closeBtns.forEach(btn => btn.addEventListener('click', closeSearch));

  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) {
      closeSearch();
    }
  });

  /* --- Navegación por Teclado y Atajos Globales (Cmd+K, /, Esc, Flechas) --- */
  document.addEventListener('keydown', (e) => {
    const isOverlayOpen = !overlay.classList.contains('hidden');

    // Atajo global Cmd+K o Ctrl+K para alternar el buscador
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      if (isOverlayOpen) {
        closeSearch();
      } else {
        openSearch();
      }
      return;
    }

    // Atajo global tecla '/' para abrir el buscador si no estamos escribiendo en otro input
    if (e.key === '/' && !isOverlayOpen) {
      const active = document.activeElement;
      const isInput = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable);
      if (!isInput) {
        e.preventDefault();
        openSearch();
        return;
      }
    }

    if (!isOverlayOpen) return;

    if (e.key === 'Escape') {
      e.preventDefault();
      closeSearch();
      return;
    }

    // Navegación con flechas arriba/abajo dentro de los resultados
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      const items = Array.from(overlay.querySelectorAll('[data-search-result-item], [data-search-all-link]'));
      if (items.length === 0) return;

      e.preventDefault();

      if (e.key === 'ArrowDown') {
        selectedIndex = (selectedIndex + 1) % items.length;
      } else if (e.key === 'ArrowUp') {
        selectedIndex = (selectedIndex - 1 + items.length) % items.length;
      }

      items.forEach((item, idx) => {
        if (idx === selectedIndex) {
          item.focus();
          item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
      });
      return;
    }

    // Ciclar Tab dentro del modal
    if (e.key === 'Tab') {
      const focusable = getFocusable(overlay);
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  });
})();

/* -------------------------------------------------------------------------
 | Progressive Web App (PWA) — Service Worker Nativo
 * ---------------------------------------------------------------------- */
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', { scope: '/' })
      .then((registration) => {
        console.log('[PWA] Service Worker activo con scope:', registration.scope);
      })
      .catch((error) => {
        console.warn('[PWA] Error en registro /sw.js, probando fallback:', error);
        navigator.serviceWorker.register('/wp-content/themes/racing-bike-theme/public/sw.js')
          .catch(() => {});
      });
  });
}

/* -------------------------------------------------------------------------
 | Wishlist (Skycode Wishlist)
 |
 | El plugin solo guarda datos; toda la UI vive aquí. Invitados persisten en
 | localStorage porque no hay cuenta a la que escribir; al iniciar sesión el
 | array local se fusiona una sola vez contra el servidor (rb_merge_wishlist)
 | y luego se limpia, para no reenviarlo en cada carga de página.
 * ---------------------------------------------------------------------- */
(function initWishlist() {
  const GUEST_KEY = 'rb_wishlist_guest';

  function getGuestIds() {
    try {
      const raw = JSON.parse(localStorage.getItem(GUEST_KEY) || '[]');
      return Array.isArray(raw) ? raw.map(Number).filter(Boolean) : [];
    } catch {
      return [];
    }
  }

  function setGuestIds(ids) {
    try {
      localStorage.setItem(GUEST_KEY, JSON.stringify(ids));
    } catch {
      // Almacenamiento no disponible (modo privado, cuota llena): la wishlist
      // de invitado simplemente no persiste entre recargas.
    }
  }

  function paintButtons(ids) {
    document.querySelectorAll('[data-wishlist-toggle]').forEach((btn) => {
      const active = ids.includes(Number(btn.dataset.wishlistToggle));
      btn.dataset.wishlistActive = active ? 'true' : 'false';
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
  }

  function paintCount(count) {
    document.querySelectorAll('[data-wishlist-count]').forEach((el) => {
      el.textContent = String(count);
    });
  }

  function render(ids) {
    paintButtons(ids);
    paintCount(ids.length);
  }

  function postAjax(action, extra) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('nonce', window.rbAjax?.nonce ?? '');
    Object.entries(extra || {}).forEach(([key, value]) => formData.append(key, value));

    return fetch(window.rbAjax?.url ?? '/wp-admin/admin-ajax.php', {
      method: 'POST',
      body: formData,
    }).then((res) => res.json());
  }

  const config = window.SkycodeWishlist || window.FiveAmWishlist || { isLoggedIn: false, items: [] };
  let currentIds = config.isLoggedIn ? (config.items || []).map(Number) : getGuestIds();

  render(currentIds);

  // El catálogo reemplaza tarjetas de producto por AJAX al filtrar (ver
  // initCatalogAjaxFilters): los botones de wishlist que llegan en esas
  // tarjetas nuevas necesitan pintarse con el estado actual sin esperar a
  // la próxima acción de wishlist.
  window.rbRepaintWishlistButtons = () => paintButtons(currentIds);

  // Fusión única al detectar sesión iniciada con datos pendientes de invitado.
  if (config.isLoggedIn && getGuestIds().length) {
    postAjax('rb_merge_wishlist', { ids: getGuestIds() })
      .then((data) => {
        if (data.success && Array.isArray(data.data?.items)) {
          currentIds = data.data.items.map(Number);
          setGuestIds([]);
          render(currentIds);
        }
      })
      .catch(() => {});
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-wishlist-toggle]');
    if (!btn) return;

    e.preventDefault();
    e.stopPropagation();

    if (btn.dataset.toggling === 'true') return;
    btn.dataset.toggling = 'true';

    const productId = Number(btn.dataset.wishlistToggle);
    const wasActive = currentIds.includes(productId);

    // Actualización optimista: refleja el cambio antes de que responda el servidor.
    currentIds = wasActive
      ? currentIds.filter((id) => id !== productId)
      : [...currentIds, productId];
    render(currentIds);

    if (!config.isLoggedIn) {
      setGuestIds(currentIds);
      btn.dataset.toggling = 'false';
      return;
    }

    postAjax('rb_toggle_wishlist', { product_id: productId })
      .then((data) => {
        if (!data.success) {
          // Revertir si el servidor rechazó el cambio (p. ej. nonce vencido).
          currentIds = wasActive
            ? [...currentIds, productId]
            : currentIds.filter((id) => id !== productId);
          render(currentIds);
        }
      })
      .catch(() => {
        currentIds = wasActive
          ? [...currentIds, productId]
          : currentIds.filter((id) => id !== productId);
        render(currentIds);
      })
      .finally(() => {
        btn.dataset.toggling = 'false';
      });
  }, true);
})();


/* -------------------------------------------------------------------------
 | Catálogo: alternancia de vista (cuadrícula / compacta) + filtros AJAX
 |
 | Vivía como un <script> inline en archive-product.blade.php; se mueve
 | aquí porque initCatalogAjaxFilters (más abajo) reemplaza la barra de
 | herramientas por AJAX y necesita poder re-ejecutar esta misma lógica
 | contra los botones nuevos que llegan en cada respuesta.
 * ---------------------------------------------------------------------- */

function initCatalogViewSwitcher() {
  const container = document.getElementById('catalog-grid-container');
  const switcher = document.querySelector('[data-catalog-view-switcher]');
  if (!container || !switcher) return;

  const gridBtn = switcher.querySelector('[data-view-btn="grid"]');
  const compactBtn = switcher.querySelector('[data-view-btn="compact"]');

  const setViewMode = (mode) => {
    if (gridBtn) {
      gridBtn.setAttribute('data-active', mode === 'grid' ? 'true' : 'false');
      gridBtn.dataset.active = mode === 'grid' ? 'true' : 'false';
    }
    if (compactBtn) {
      compactBtn.setAttribute('data-active', mode === 'compact' ? 'true' : 'false');
      compactBtn.dataset.active = mode === 'compact' ? 'true' : 'false';
    }

    container.classList.remove('view-mode-grid', 'view-mode-compact', 'view-mode-list');
    container.classList.add(mode === 'compact' ? 'view-mode-compact' : 'view-mode-grid');
    localStorage.setItem('rb_catalog_view_mode', mode);
  };

  // En mobile (< 768px), la 2da vista (compact) es la predeterminada obligatoria.
  const isMobile = window.innerWidth < 768;
  const defaultMode = isMobile ? 'compact' : 'grid';
  const savedMode = localStorage.getItem('rb_catalog_view_mode') || defaultMode;
  setViewMode(savedMode);

  if (gridBtn) gridBtn.addEventListener('click', () => setViewMode('grid'));
  if (compactBtn) compactBtn.addEventListener('click', () => setViewMode('compact'));
}

document.addEventListener('DOMContentLoaded', initCatalogViewSwitcher);

/* -------------------------------------------------------------------------
 | Catálogo: filtrado por AJAX
 |
 | Antes, marcar un filtro, quitar una chip o cambiar de página recargaba
 | la página completa — en móvil eso significa perder la posición de
 | scroll y ver un parpadeo en blanco por cada clic, y en el drawer de
 | filtros significa que aplicar 3 filtros seguidos exige abrirlo 3 veces.
 |
 | No hay un endpoint nuevo en el servidor: se pide la MISMA página que un
 | clic normal pediría (progressive enhancement — con JS desactivado, cada
 | enlace sigue funcionando exactamente igual, como navegación normal) y
 | del HTML que vuelve se toman solo los fragmentos que cambian, cada uno
 | por su propio id/selector estable:
 |
 |   #catalog-grid-container      tarjetas de producto
 |   #catalog-pagination          paginación
 |   [data-catalog-count]         "N productos" (solo texto, nunca se
 |                                 destruye el nodo — es la región aria-live)
 |   [data-catalog-toolbar-actions]  orden + selector de vista
 |   [data-catalog-chips]         chips de filtros activos
 |   #catalog-sidebar-desktop     sidebar de escritorio
 |   #catalog-sidebar-mobile      contenido del drawer de filtros (el
 |                                 encabezado y el botón de cerrar del
 |                                 drawer NO se tocan — conservan su
 |                                 listener de apertura/cierre)
 |
 | Los enlaces de filtro/paginación/chips no llevan un listener propio:
 | se detectan por delegación (¿el <a> que se clickeó está dentro de uno
 | de esos contenedores?), así que un enlace que llega en una respuesta
 | AJAX funciona igual que uno que vino en el HTML original, sin tener que
 | re-enganchar nada.
 * ---------------------------------------------------------------------- */

function initCatalogAjaxFilters() {
  const grid = document.getElementById('catalog-grid-container');
  if (!grid) return; // no estamos en /tienda/ ni en una categoría de producto

  const LINK_CONTAINERS = '#catalog-sidebar-desktop, #catalog-sidebar-mobile, [data-catalog-chips], #catalog-pagination';
  const REGIONS = [
    { selector: '#catalog-grid-container', mode: 'html' },
    { selector: '#catalog-pagination', mode: 'html' },
    { selector: '[data-catalog-toolbar-actions]', mode: 'html' },
    { selector: '[data-catalog-chips]', mode: 'html' },
    { selector: '#catalog-sidebar-desktop', mode: 'html' },
    { selector: '#catalog-sidebar-mobile', mode: 'html' },
    { selector: '[data-catalog-count]', mode: 'text' },
  ];

  let currentAbort = null;

  const setBusy = (busy) => {
    grid.setAttribute('aria-busy', String(busy));
    grid.classList.toggle('opacity-50', busy);
    grid.classList.toggle('pointer-events-none', busy);
  };

  const swapRegions = (doc) => {
    REGIONS.forEach(({ selector, mode }) => {
      const current = document.querySelector(selector);
      const incoming = doc.querySelector(selector);
      if (!current || !incoming) return;

      if (mode === 'text') {
        current.textContent = incoming.textContent;
      } else {
        current.innerHTML = incoming.innerHTML;
      }
    });
  };

  const navigate = (url, { push = true, moveFocus = true } = {}) => {
    currentAbort?.abort();
    currentAbort = new AbortController();

    setBusy(true);

    fetch(url, { signal: currentAbort.signal, headers: { 'X-Requested-With': 'rb-catalog-ajax' } })
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.text();
      })
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        swapRegions(doc);

        if (push) history.pushState({ rbCatalogUrl: url }, '', url);

        // Los nodos de #catalog-grid-container y del selector de vista se
        // acaban de recrear desde cero: sin esto, la vista compacta
        // elegida por el visitante se pierde en cada filtro, y los
        // botones nuevos del selector no tendrían su listener de clic.
        initCatalogViewSwitcher();
        window.rbInitGa4ListImpressions?.(grid);
        window.rbRepaintWishlistButtons?.();

        // Los sidebars (escritorio y drawer móvil) también se
        // reemplazaron enteros: el slider de precio que llega en el HTML
        // nuevo es igual de huérfano que el selector de vista de arriba.
        initPriceSlider(document.getElementById('catalog-sidebar-desktop') ?? document);
        initPriceSlider(document.getElementById('catalog-sidebar-mobile') ?? document);

        // Si el clic vino de dentro del drawer de filtros en móvil, el
        // drawer se queda abierto (tocar 3 filtros seguidos ya no exige
        // reabrirlo 3 veces) — y en ese caso NO se mueve el foco ni el
        // scroll de la página de fondo, que sigue tapada por el overlay
        // del drawer y el visitante ni la ve.
        if (moveFocus) {
          // Screen readers: mover el foco al contador (aria-live lo
          // anuncia) en vez de dejarlo en el <body> — el enlace que se
          // pulsó puede haber sido destruido por el propio swap.
          document.querySelector('[data-catalog-count]')?.focus({ preventScroll: true });

          const anchor = document.querySelector('[data-catalog-toolbar]') || grid;
          anchor.scrollIntoView({
            behavior: prefersReducedMotion.matches ? 'auto' : 'smooth',
            block: 'start',
          });
        }
      })
      .catch((error) => {
        if (error.name === 'AbortError') return;
        // Un filtro es una comodidad, no algo crítico: si el fetch falla
        // (red, timeout), degradar a una navegación normal en vez de
        // dejar al visitante con una rejilla congelada sin explicación.
        window.location.href = url;
      })
      .finally(() => setBusy(false));
  };

  document.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0) return;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return; // abrir en pestaña nueva, etc.

    const link = event.target.closest('a[href]');
    if (!link || !link.closest(LINK_CONTAINERS)) return;
    if (link.getAttribute('aria-disabled') === 'true') return;

    const url = new URL(link.href, window.location.origin);
    if (url.origin !== window.location.origin) return;

    event.preventDefault();
    navigate(url.toString(), { moveFocus: !link.closest('#catalog-sidebar-mobile') });
  });

  // Filtro de precio: el único control de la barra de filtros que es un
  // <form> en vez de un <a>, así que se intercepta por separado.
  document.addEventListener('submit', (event) => {
    if (!event.target.matches('#catalog-sidebar-desktop form, #catalog-sidebar-mobile form')) return;

    event.preventDefault();
    const form = event.target;
    const url = new URL(form.action, window.location.origin);
    url.search = new URLSearchParams(new FormData(form)).toString();
    navigate(url.toString(), { moveFocus: !form.closest('#catalog-sidebar-mobile') });
  });

  // Atrás/adelante del navegador: refrescar el contenido para que
  // coincida con la URL a la que se volvió, sin volver a apilar historial.
  window.addEventListener('popstate', () => {
    navigate(window.location.href, { push: false });
  });
}

document.addEventListener('DOMContentLoaded', initCatalogAjaxFilters);

/* -------------------------------------------------------------------------
 | Controles que existían en el markup pero no tenían quién los escuchara
 |
 | Auditoría del 2026-09-17: ambos son botones visibles, con estilos de
 | hover y cursor pointer, que al pulsarlos no hacían absolutamente nada.
 * ---------------------------------------------------------------------- */

// 1. "Ver plan" del widget de financiación (ficha de producto). El modal
//    completo con los planes de cuotas ya existía renderizado y oculto —
//    simplemente no había forma de abrirlo.
document.addEventListener('click', (event) => {
  const openBtn = event.target.closest('[data-open-financing-modal]');
  const closeBtn = event.target.closest('[data-close-financing-modal]');

  if (openBtn) {
    const modal = document.querySelector('[data-financing-modal]');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    modal.querySelector('[data-close-financing-modal]')?.focus();
    return;
  }

  const modal = document.querySelector('[data-financing-modal]');
  if (!modal || modal.classList.contains('hidden')) return;

  // Cierre por botón o por clic en el fondo oscuro.
  if (closeBtn || event.target === modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  const modal = document.querySelector('[data-financing-modal]');
  if (!modal || modal.classList.contains('hidden')) return;
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  document.body.classList.remove('overflow-hidden');
});

// 2. Miniaturas del quick-view: cada uma trae en data-qv-thumb la URL de
//    su foto en tamaño completo, pero nadie la leía, así que la imagen
//    principal del modal nunca cambiaba. Delegado porque el contenido del
//    quick-view se inyecta por AJAX después de cargar la página.
document.addEventListener('click', (event) => {
  const thumb = event.target.closest('[data-qv-thumb]');
  if (!thumb) return;

  const url = thumb.dataset.qvThumb;
  const mainImage = document.getElementById('qv-main-image');
  if (!url || !mainImage) return;

  mainImage.src = url;

  // Marca visual de cuál está activa (las clases replican las que el
  // Blade pinta para la primera miniatura).
  thumb.closest('[data-qv-thumbnails]')?.querySelectorAll('[data-qv-thumb]').forEach((other) => {
    const isActive = other === thumb;
    other.classList.toggle('border-white', isActive);
    other.classList.toggle('ring-2', isActive);
    other.classList.toggle('ring-white/20', isActive);
    other.classList.toggle('border-line/60', !isActive);
    other.classList.toggle('opacity-60', !isActive);
  });
});

/* -------------------------------------------------------------------------
 | Mini galería de las tarjetas de producto (flechas, puntos y hover-flip)
 |
 | Vivía como un <script> inline DENTRO de product-card.blade.php, o sea
 | que se duplicaba una vez por tarjeta renderizada: en la tienda con 24
 | productos eran 24 copias idénticas del mismo código, cada una
 | registrando sus 4 listeners sobre `document` — ~96 listeners haciendo
 | exactamente lo mismo, y cada clic o cada movimiento del ratón sobre una
 | tarjeta ejecutándose 24 veces.
 |
 | El código ya usaba delegación sobre `document`, así que moverlo aquí no
 | cambia su comportamiento (incluidas las tarjetas que llegan por AJAX al
 | filtrar el catálogo) — solo deja de repetirse.
 * ---------------------------------------------------------------------- */

document.addEventListener('click', (e) => {
  if (!e.target || typeof e.target.closest !== 'function') return;
  const dot = e.target.closest('[data-gallery-dot]');
  const prev = e.target.closest('[data-gallery-prev]');
  const next = e.target.closest('[data-gallery-next]');

  if (!dot && !prev && !next) return;

  e.preventDefault();
  e.stopPropagation();

  const gallery = (dot || prev || next).closest('[data-card-gallery]');
  if (!gallery) return;

  // Marcar que el usuario interactuó para pausar el reset automático al salir
  gallery.setAttribute('data-interacted', 'true');

  const imgs = Array.from(gallery.querySelectorAll('[data-gallery-img]'));
  const dots = Array.from(gallery.querySelectorAll('[data-gallery-dot]'));
  if (!imgs.length) return;

  let currentIndex = parseInt(gallery.getAttribute('data-active-index') || '0', 10);

  if (dot) {
    currentIndex = parseInt(dot.getAttribute('data-gallery-dot'), 10);
  } else if (prev) {
    currentIndex = (currentIndex - 1 + imgs.length) % imgs.length;
  } else if (next) {
    currentIndex = (currentIndex + 1) % imgs.length;
  }

  gallery.setAttribute('data-active-index', currentIndex);

  imgs.forEach((img, idx) => {
    if (idx === currentIndex) {
      img.classList.remove('opacity-0');
      img.classList.add('opacity-100');
    } else {
      img.classList.remove('opacity-100');
      img.classList.add('opacity-0');
    }
  });

  dots.forEach((d, idx) => {
    d.setAttribute('data-active', idx === currentIndex ? 'true' : 'false');
  });
});

// Hover sobre el dot: Cambiar de foto y marcar interacción
document.addEventListener('mouseover', (e) => {
  if (!e.target || typeof e.target.closest !== 'function') return;
  const dot = e.target.closest('[data-gallery-dot]');
  if (!dot) return;

  const gallery = dot.closest('[data-card-gallery]');
  if (!gallery) return;

  gallery.setAttribute('data-interacted', 'true');

  const imgs = Array.from(gallery.querySelectorAll('[data-gallery-img]'));
  const dots = Array.from(gallery.querySelectorAll('[data-gallery-dot]'));
  const targetIndex = parseInt(dot.getAttribute('data-gallery-dot'), 10);

  gallery.setAttribute('data-active-index', targetIndex);

  imgs.forEach((img, idx) => {
    if (idx === targetIndex) {
      img.classList.remove('opacity-0');
      img.classList.add('opacity-100');
    } else {
      img.classList.remove('opacity-100');
      img.classList.add('opacity-0');
    }
  });

  dots.forEach((d, idx) => {
    d.setAttribute('data-active', idx === targetIndex ? 'true' : 'false');
  });
});

// Hover Flip: Mostrar segunda imagen al entrar y resetear al salir (si no hay interacción manual)
document.addEventListener('mouseenter', (e) => {
  if (!e.target || typeof e.target.closest !== 'function') return;
  const gallery = e.target.closest('[data-card-gallery]');
  if (!gallery) return;

  if (gallery.getAttribute('data-interacted') === 'true') return;

  const imgs = Array.from(gallery.querySelectorAll('[data-gallery-img]'));
  const dots = Array.from(gallery.querySelectorAll('[data-gallery-dot]'));
  if (imgs.length < 2) return;

  // Cambiar a la segunda imagen (índice 1)
  gallery.setAttribute('data-active-index', '1');
  imgs[0].classList.remove('opacity-100');
  imgs[0].classList.add('opacity-0');
  imgs[1].classList.remove('opacity-0');
  imgs[1].classList.add('opacity-100');

  if (dots.length >= 2) {
    dots[0].setAttribute('data-active', 'false');
    dots[1].setAttribute('data-active', 'true');
  }
}, true);

document.addEventListener('mouseleave', (e) => {
  if (!e.target || typeof e.target.closest !== 'function') return;
  const gallery = e.target.closest('[data-card-gallery]');
  if (!gallery) return;

  if (gallery.getAttribute('data-interacted') === 'true') return;

  const imgs = Array.from(gallery.querySelectorAll('[data-gallery-img]'));
  const dots = Array.from(gallery.querySelectorAll('[data-gallery-dot]'));
  if (imgs.length < 2) return;

  // Retornar a la primera imagen (índice 0)
  gallery.setAttribute('data-active-index', '0');
  imgs.forEach((img, idx) => {
    if (idx === 0) {
      img.classList.remove('opacity-0');
      img.classList.add('opacity-100');
    } else {
      img.classList.remove('opacity-100');
      img.classList.add('opacity-0');
    }
  });

  dots.forEach((d, idx) => {
    d.setAttribute('data-active', idx === 0 ? 'true' : 'false');
  });
}, true);
  

/* -------------------------------------------------------------------------
 | Filtro de precio: slider doble con histograma
 |
 | Reemplaza los dos <input type="number"> sueltos. Cada [data-price-slider]
 | trae dos <input type="range"> superpuestos (mínimo y máximo) más un div
 | [data-price-fill] que pinta el tramo activo entre ambos. Aplica el
 | filtro solo, sin botón: al soltar cualquiera de los dos tiradores
 | (evento "change", no "input" — si no, mandaría un filtro por cada
 | píxel arrastrado) o al tocar uno de los tramos de un clic.
 |
 | Expuesto como initPriceSlider() (no una IIFE) porque
 | initCatalogAjaxFilters, en el módulo de más abajo, lo vuelve a llamar
 | cada vez que reemplaza el sidebar por AJAX — los <input> nuevos llegan
 | sin ningún listener propio.
 * ---------------------------------------------------------------------- */

function initPriceSlider(root = document) {
  root.querySelectorAll('[data-price-slider]').forEach((container) => {
    const minRange = container.querySelector('[data-price-min-range]');
    const maxRange = container.querySelector('[data-price-max-range]');
    const fill = container.querySelector('[data-price-fill]');
    if (!minRange || !maxRange || !fill) return;

    const form = minRange.form;
    const minLabel = form?.querySelector('[data-price-min-label]');
    const maxLabel = form?.querySelector('[data-price-max-label]');

    const boundMin = Number(minRange.min);
    const boundMax = Number(minRange.max);
    const span = boundMax - boundMin || 1;
    // Que no se crucen los dos tiradores: sin este piso, arrastrar el de
    // "desde" más allá del de "hasta" (o viceversa) los deja invertidos y
    // el relleno verde se pinta al revés.
    const minGap = Math.max(Number(minRange.step) || 1, Math.round(span * 0.01));

    const formatCOP = (value) => `$${Math.round(value).toLocaleString('es-CO')}`;

    const paintFill = (minVal, maxVal) => {
      const minPct = ((minVal - boundMin) / span) * 100;
      const maxPct = ((maxVal - boundMin) / span) * 100;
      fill.style.left = `${minPct}%`;
      fill.style.right = `${100 - maxPct}%`;
    };

    const paintLabels = (minVal, maxVal) => {
      if (minLabel) minLabel.textContent = formatCOP(minVal);
      if (maxLabel) maxLabel.textContent = formatCOP(maxVal);
      minRange.setAttribute('aria-valuetext', formatCOP(minVal));
      maxRange.setAttribute('aria-valuetext', formatCOP(maxVal));
    };

    const enforceGap = (movedInput) => {
      let minVal = Number(minRange.value);
      let maxVal = Number(maxRange.value);

      if (minVal > maxVal - minGap) {
        if (movedInput === maxRange) {
          minVal = maxVal - minGap;
          minRange.value = String(Math.max(boundMin, minVal));
        } else {
          maxVal = minVal + minGap;
          maxRange.value = String(Math.min(boundMax, maxVal));
        }
      }

      return [Number(minRange.value), Number(maxRange.value)];
    };

    const handleInput = (event) => {
      const [minVal, maxVal] = enforceGap(event.target);
      paintFill(minVal, maxVal);
      paintLabels(minVal, maxVal);
    };

    minRange.addEventListener('input', handleInput);
    maxRange.addEventListener('input', handleInput);
    minRange.addEventListener('change', () => form?.requestSubmit());
    maxRange.addEventListener('change', () => form?.requestSubmit());

    // Estado inicial: pintar sin esperar a que el visitante toque nada.
    paintFill(Number(minRange.value), Number(maxRange.value));
    paintLabels(Number(minRange.value), Number(maxRange.value));
  });

  root.querySelectorAll('[data-price-preset]').forEach((button) => {
    button.addEventListener('click', () => {
      const form = button.closest('form');
      const minRange = form?.querySelector('[data-price-min-range]');
      const maxRange = form?.querySelector('[data-price-max-range]');
      if (!form || !minRange || !maxRange) return;

      minRange.value = button.dataset.presetMin;
      maxRange.value = button.dataset.presetMax;
      // "input" repinta el relleno/las etiquetas con el mismo código que
      // usa arrastrar el slider — un solo camino para los dos gestos.
      minRange.dispatchEvent(new Event('input', { bubbles: true }));
      form.requestSubmit();
    });
  });
}

document.addEventListener('DOMContentLoaded', () => initPriceSlider());
