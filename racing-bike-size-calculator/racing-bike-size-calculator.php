<?php
/**
 * Plugin Name: Racing Bike Size Calculator
 * Description: Calculadora interactiva premium tipo modal para encontrar la talla ideal de bicicleta (Ruta, MTB, Gravel).
 * Version: 1.0.5
 * Author: Skycode Agency
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Registrar estilos y scripts
function rb_size_calculator_register_assets() {
    wp_enqueue_style(
        'rb-size-calculator-css',
        plugins_url( 'assets/css/calculator.css', __FILE__ ),
        array(),
        '1.0.5'
    );

    wp_enqueue_script(
        'rb-size-calculator-js',
        plugins_url( 'assets/js/calculator.js', __FILE__ ),
        array(),
        '1.0.5',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'rb_size_calculator_register_assets' );

// Renderizar el modal en el footer del sitio
function rb_size_calculator_render_modal() {
    ?>
    <div
      id="rb-size-finder-modal"
      class="rb-modal-overlay"
      role="dialog"
      aria-modal="true"
    >
      <div class="rb-modal-content">
        <!-- Brillo ambiental de fondo -->
        <div class="rb-ambient-glow"></div>

        <!-- Encabezado -->
        <div class="rb-modal-header">
          <div class="rb-header-left">
            <div class="rb-icon-wrapper">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                <path d="M5 19h14M5 5h14M12 5v14" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div>
              <h3 class="rb-modal-title">Calculador Inteligente de Talla</h3>
              <p class="rb-modal-subtitle">Encuentra tu marco ideal según tu biomecánica</p>
            </div>
          </div>

          <button type="button" class="rb-modal-close" data-close-size-finder aria-label="Cerrar modal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
              <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <!-- Formulario -->
        <div class="rb-modal-body">
          
          <!-- Disciplina -->
          <div class="rb-form-section">
            <label class="rb-section-label">1. Disciplina de Ciclismo</label>
            <div class="rb-discipline-selector">
              <button type="button" class="rb-discipline-btn active" data-discipline="road">
                <span class="rb-btn-emoji">🚴</span> Ruta / Road
              </button>
              <button type="button" class="rb-discipline-btn" data-discipline="mtb">
                <span class="rb-btn-emoji">⛰️</span> Montaña / MTB
              </button>
              <button type="button" class="rb-discipline-btn" data-discipline="gravel">
                <span class="rb-btn-emoji">🏕️</span> Gravel
              </button>
            </div>
          </div>

          <!-- Estatura -->
          <div class="rb-form-section">
            <div class="rb-section-header">
              <label class="rb-section-label" for="rb-height-range">2. Tu Estatura</label>
              <span class="rb-badge-value"><span id="rb-height-display">175</span> cm</span>
            </div>
            <input type="range" id="rb-height-range" min="140" max="210" value="175" class="rb-modal-slider">
            <div class="rb-range-limits">
              <span>140 cm</span>
              <span>175 cm</span>
              <span>210 cm</span>
            </div>
          </div>

          <!-- Toggle Ajuste Biomecánico Avanzado -->
          <div class="rb-form-section">
            <div class="rb-toggle-container">
              <span class="rb-section-label">3. Ajuste Biomecánico Avanzado</span>
              <label class="rb-switch">
                <input type="checkbox" id="rb-advanced-toggle">
                <span class="rb-switch-slider"></span>
              </label>
            </div>
            <p class="rb-toggle-desc">Usa la medida de tu entrepierna para una precisión perfecta del tamaño de cuadro.</p>
          </div>

          <!-- Entrepierna (Oculto por defecto) -->
          <div class="rb-form-section rb-advanced-section" id="rb-advanced-fields" style="display: none;">
            <div class="rb-section-header">
              <label class="rb-section-label" for="rb-inseam-range">Longitud de Entrepierna</label>
              <span class="rb-badge-value"><span id="rb-inseam-display">80</span> cm</span>
            </div>
            <input type="range" id="rb-inseam-range" min="60" max="100" value="80" class="rb-modal-slider">
            <div class="rb-range-limits">
              <span>60 cm</span>
              <span>80 cm</span>
              <span>100 cm</span>
            </div>
            <p class="rb-field-tip mt-2">Mide descalzo desde el suelo hasta tu entrepierna.</p>
          </div>

          <!-- Resultado -->
          <div class="rb-modal-result-box">
            <span class="rb-result-label">TALLA RECOMENDADA</span>
            <h4 class="rb-result-title" id="rb-modal-recommended-size">TALLA M (54 cm)</h4>
            <p class="rb-result-desc" id="rb-modal-recommended-desc">Cargando recomendación...</p>

            <button type="button" class="rb-apply-btn" id="rb-modal-apply-btn">
              Seleccionar Talla M e ir a Comprar
            </button>
          </div>

        </div>
      </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'rb_size_calculator_render_modal' );

// Shortcode para botón alternativo de apertura
function rb_size_calculator_shortcode( $atts ) {
    $a = shortcode_atts( array(
        'label' => 'Calcular mi Talla',
    ), $atts );

    return '<button type="button" data-open-size-finder class="rb-shortcode-trigger-btn">' . esc_html( $a['label'] ) . '</button>';
}
add_shortcode( 'bike_size_calculator', 'rb_size_calculator_shortcode' );
