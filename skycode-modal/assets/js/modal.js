(function () {
    'use strict';

    if (typeof window.skcMdCampaigns === 'undefined' || !window.skcMdCampaigns.length) {
        return;
    }

    var CONFIG = window.skcMdConfig || {};
    var AJAX_URL = CONFIG.ajaxUrl || '/wp-admin/admin-ajax.php';
    var STORAGE_PREFIX = 'skc_md_';
    var DAY_MS = 24 * 60 * 60 * 1000;

    var shownThisPageLoad = false;
    var activeCampaignId = null;
    var lastFocusedEl = null;

    // ---------------------------------------------------------------
    // Almacenamiento por visitante (localStorage con fallback en memoria)
    // ---------------------------------------------------------------
    var memoryStore = {};
    function storageGet(key) {
        try {
            var v = window.localStorage.getItem(key);
            return v === null ? null : JSON.parse(v);
        } catch (e) {
            return Object.prototype.hasOwnProperty.call(memoryStore, key) ? memoryStore[key] : null;
        }
    }
    function storageSet(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            memoryStore[key] = value;
        }
    }

    function getVisitorHash() {
        var key = STORAGE_PREFIX + 'visitor';
        var hash = storageGet(key);
        if (!hash) {
            hash = 'v' + Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
            storageSet(key, hash);
        }
        return hash;
    }

    function getCampaignState(id) {
        return storageGet(STORAGE_PREFIX + 'c' + id) || { impressions: 0, lastShown: 0, converted: false, variant: null, pageViews: 0 };
    }
    function setCampaignState(id, state) {
        storageSet(STORAGE_PREFIX + 'c' + id, state);
    }

    function isFirstVisit() {
        var key = STORAGE_PREFIX + 'seen';
        var seen = storageGet(key);
        if (!seen) {
            storageSet(key, true);
            return true;
        }
        return false;
    }
    var firstVisit = isFirstVisit();

    function getUtmSource() {
        var params = new URLSearchParams(window.location.search);
        return params.get('utm_source') || '';
    }

    // ---------------------------------------------------------------
    // Reglas de frecuencia (cliente)
    // ---------------------------------------------------------------
    function passesClientRules(campaign) {
        var rules = campaign.clientRules || {};
        var state = getCampaignState(campaign.id);

        if (rules.freqHideOnConvert && state.converted) {
            return false;
        }

        if (rules.freqMaxImpressions && state.impressions >= rules.freqMaxImpressions) {
            var cooldownMs = (rules.freqCooldownDays || 0) * DAY_MS;
            if (!cooldownMs || (Date.now() - state.lastShown) < cooldownMs) {
                return false;
            }
        }

        if (rules.firstVisit === 'first_time' && !firstVisit) {
            return false;
        }
        if (rules.firstVisit === 'returning' && firstVisit) {
            return false;
        }

        if (rules.utmSource && rules.utmSource !== getUtmSource()) {
            return false;
        }

        return true;
    }

    // ---------------------------------------------------------------
    // Selección de variante A/B (persistida por campaña)
    // ---------------------------------------------------------------
    function pickVariant(campaign) {
        var variants = campaign.variants || [];
        if (!variants.length) {
            return null;
        }

        var state = getCampaignState(campaign.id);
        if (state.variant) {
            var existing = variants.filter(function (v) { return v.key === state.variant; })[0];
            if (existing) {
                return existing;
            }
        }

        var totalWeight = variants.reduce(function (sum, v) { return sum + (v.weight || 1); }, 0);
        var roll = Math.random() * totalWeight;
        var picked = variants[0];
        var acc = 0;
        for (var i = 0; i < variants.length; i++) {
            acc += (variants[i].weight || 1);
            if (roll <= acc) {
                picked = variants[i];
                break;
            }
        }

        state.variant = picked.key;
        setCampaignState(campaign.id, state);
        return picked;
    }

    function applyVariant(el, variant) {
        if (!variant) {
            return;
        }
        ['headline', 'subtext', 'button_text'].forEach(function (field) {
            if (!variant[field]) {
                return;
            }
            var target = el.querySelector('[data-skc-md-field="' + field + '"]');
            if (target) {
                target.textContent = variant[field];
            }
        });
        var variantInput = el.querySelector('.skc-md-variant-input');
        if (variantInput) {
            variantInput.value = variant.key;
        }
    }

    // ---------------------------------------------------------------
    // Beacon de eventos (impression, view, close, submit, convert)
    // ---------------------------------------------------------------
    function trackEvent(campaignId, event, variant) {
        var body = new URLSearchParams();
        body.set('action', 'skc_md_track');
        body.set('nonce', CONFIG.trackNonce || '');
        body.set('campaign_id', campaignId);
        body.set('event', event);
        body.set('variant', variant || '');
        body.set('visitor', getVisitorHash());
        body.set('url', window.location.href);

        if (navigator.sendBeacon) {
            var blob = new Blob([body.toString()], { type: 'application/x-www-form-urlencoded' });
            navigator.sendBeacon(AJAX_URL, blob);
        } else {
            fetch(AJAX_URL, { method: 'POST', body: body, keepalive: true });
        }
    }

    // ---------------------------------------------------------------
    // Apertura / cierre con accesibilidad (focus trap, ESC, foco previo)
    // ---------------------------------------------------------------
    function getFocusable(el) {
        return Array.prototype.slice.call(
            el.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])')
        );
    }

    function trapFocus(e, el) {
        if (e.key !== 'Tab') {
            return;
        }
        var focusable = getFocusable(el);
        if (!focusable.length) {
            return;
        }
        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }

    function openModal(campaign) {
        if (shownThisPageLoad && CONFIG.maxActiveModalsPerPage <= 1) {
            return;
        }

        var el = document.getElementById('skc-md-modal-' + campaign.id);
        if (!el) {
            return;
        }

        var variant = pickVariant(campaign);
        applyVariant(el, variant);

        var startedAt = el.querySelector('.skc-md-started-at');
        if (startedAt) {
            startedAt.value = String(Date.now());
        }

        lastFocusedEl = document.activeElement;
        el.hidden = false;
        requestAnimationFrame(function () {
            el.classList.add('skc-md-open');
        });
        document.body.style.overflow = 'hidden';

        var focusable = getFocusable(el);
        if (focusable.length) {
            focusable[0].focus();
        }

        function onKeydown(e) {
            if (e.key === 'Escape') {
                closeModal(campaign, el, 'close');
            }
            trapFocus(e, el);
        }
        el._skcMdKeydown = onKeydown;
        document.addEventListener('keydown', onKeydown);

        shownThisPageLoad = true;
        activeCampaignId = campaign.id;

        var state = getCampaignState(campaign.id);
        state.impressions += 1;
        state.lastShown = Date.now();
        setCampaignState(campaign.id, state);

        trackEvent(campaign.id, 'impression', variant ? variant.key : '');
    }

    function closeModal(campaign, el, eventName) {
        el.classList.remove('skc-md-open');
        el.hidden = true;
        document.body.style.overflow = '';

        if (el._skcMdKeydown) {
            document.removeEventListener('keydown', el._skcMdKeydown);
        }

        if (lastFocusedEl && typeof lastFocusedEl.focus === 'function') {
            lastFocusedEl.focus();
        }

        if (activeCampaignId === campaign.id) {
            activeCampaignId = null;
        }

        if (eventName) {
            var variantInput = el.querySelector('.skc-md-variant-input');
            trackEvent(campaign.id, eventName, variantInput ? variantInput.value : '');
        }
    }

    // ---------------------------------------------------------------
    // Envío del formulario (lead)
    // ---------------------------------------------------------------
    function bindForm(campaign, el) {
        var form = el.querySelector('.skc-md-form');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var submitBtn = form.querySelector('.skc-md-submit');
            var message = form.querySelector('.skc-md-message');
            var startedAt = parseInt(form.querySelector('.skc-md-started-at').value, 10) || Date.now();
            var minFillMs = (CONFIG.minFillSeconds || 0) * 1000;

            if (Date.now() - startedAt < minFillMs) {
                return; // probable bot: no feedback, se ignora silenciosamente.
            }

            var body = new URLSearchParams(new FormData(form));
            body.set('action', 'skc_md_submit_lead');

            submitBtn.disabled = true;
            message.textContent = '';
            message.className = 'skc-md-message';

            fetch(AJAX_URL, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    submitBtn.disabled = false;
                    if (json.success) {
                        message.textContent = json.data && json.data.message ? json.data.message : 'OK';
                        message.classList.add('is-success');

                        var state = getCampaignState(campaign.id);
                        state.converted = true;
                        setCampaignState(campaign.id, state);

                        var variantInput = form.querySelector('.skc-md-variant-input');
                        trackEvent(campaign.id, 'convert', variantInput ? variantInput.value : '');

                        var redirect = campaign.redirectAfterSubmit;
                        setTimeout(function () {
                            if (redirect) {
                                window.location.href = redirect;
                            } else {
                                closeModal(campaign, el, null);
                            }
                        }, 1200);
                    } else {
                        message.textContent = (json.data && json.data.message) || 'Error';
                        message.classList.add('is-error');
                    }
                })
                .catch(function () {
                    submitBtn.disabled = false;
                    message.textContent = 'Error de red, inténtalo de nuevo.';
                    message.classList.add('is-error');
                });
        });
    }

    // ---------------------------------------------------------------
    // Motor de triggers
    // ---------------------------------------------------------------
    function armTrigger(campaign) {
        var el = document.getElementById('skc-md-modal-' + campaign.id);
        if (!el) {
            return;
        }

        bindForm(campaign, el);

        el.querySelectorAll('[data-skc-md-close]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeModal(campaign, el, 'close');
            });
        });

        var fired = false;
        function fire() {
            if (fired || activeCampaignId !== null) {
                return;
            }
            fired = true;
            openModal(campaign);
        }

        var t = campaign.trigger || {};

        switch (t.type) {
            case 'on_load':
                fire();
                break;

            case 'time_delay':
                setTimeout(fire, Math.max(0, (t.value || 0) * 1000));
                break;

            case 'scroll_percent':
                var onScroll = function () {
                    var doc = document.documentElement;
                    var scrolled = (window.scrollY / (doc.scrollHeight - window.innerHeight)) * 100;
                    if (scrolled >= (t.value || 50)) {
                        fire();
                        window.removeEventListener('scroll', onScroll);
                    }
                };
                window.addEventListener('scroll', onScroll, { passive: true });
                break;

            case 'exit_intent':
                var onMouseOut = function (e) {
                    if (!e.relatedTarget && !e.toElement && e.clientY <= 0) {
                        fire();
                        document.removeEventListener('mouseout', onMouseOut);
                    }
                };
                document.addEventListener('mouseout', onMouseOut);

                var touchStartY = 0;
                document.addEventListener('touchstart', function (e) {
                    touchStartY = e.touches[0].clientY;
                }, { passive: true });
                document.addEventListener('touchmove', function (e) {
                    if (e.touches[0].clientY - touchStartY > 80 && window.scrollY < 50) {
                        fire();
                    }
                }, { passive: true });
                break;

            case 'inactivity':
                var idleTimer;
                var resetIdle = function () {
                    clearTimeout(idleTimer);
                    idleTimer = setTimeout(fire, Math.max(1, (t.value || 30)) * 1000);
                };
                ['mousemove', 'keydown', 'scroll', 'touchstart'].forEach(function (evt) {
                    document.addEventListener(evt, resetIdle, { passive: true });
                });
                resetIdle();
                break;

            case 'page_count':
                var state = getCampaignState(campaign.id);
                state.pageViews = (state.pageViews || 0) + 1;
                setCampaignState(campaign.id, state);
                if (state.pageViews >= (t.value || 3)) {
                    fire();
                }
                break;

            case 'click_selector':
                if (t.selector) {
                    document.querySelectorAll(t.selector).forEach(function (node) {
                        node.addEventListener('click', function (e) {
                            e.preventDefault();
                            fire();
                        });
                    });
                }
                break;
        }
    }

    function init() {
        var candidates = window.skcMdCampaigns
            .filter(passesClientRules)
            .sort(function (a, b) { return (a.priority || 10) - (b.priority || 10); });

        candidates.forEach(armTrigger);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
