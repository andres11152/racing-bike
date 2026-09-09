{{--
  Plantilla limpia para los Totales del Carrito (cart-totals.blade.php).
--}}

@php
  if (! defined('ABSPATH')) exit;
@endphp

<div class="cart_totals {{ WC()->customer->has_calculated_shipping() ? 'calculated_shipping' : '' }}">
  @php do_action('woocommerce_before_cart_totals'); @endphp

  <h2 class="text-base font-bold uppercase tracking-wider text-white mb-4 pb-2 border-b border-line">
    {{ __('Total del Carrito', 'sage') }}
  </h2>

  <table cellspacing="0" class="shop_table shop_table_responsive w-full text-xs font-bold uppercase tracking-wider">
    <tr class="cart-subtotal border-b border-line/60 py-3">
      <th class="py-3 text-ink-muted text-left font-semibold">{{ __('Subtotal', 'sage') }}</th>
      <td data-title="{{ __('Subtotal', 'sage') }}" class="py-3 text-right text-white font-bold">{!! wc_cart_totals_subtotal_html() !!}</td>
    </tr>

    @foreach (WC()->cart->get_coupons() as $code => $coupon)
      <tr class="cart-discount coupon-{{ esc_attr($code) }} border-b border-line/60 py-3">
        <th class="py-3 text-ink-muted text-left font-semibold">{{ wc_cart_totals_coupon_label($coupon) }}</th>
        <td data-title="{{ esc_attr(wc_cart_totals_coupon_label($coupon)) }}" class="py-3 text-right text-emerald-400">{!! wc_cart_totals_coupon_html($coupon) !!}</td>
      </tr>
    @endforeach

    @if (WC()->cart->needs_shipping() && WC()->cart->show_shipping())
      @php do_action('woocommerce_cart_totals_before_shipping'); @endphp
      @php wc_cart_totals_shipping_html(); @endphp
      @php do_action('woocommerce_cart_totals_after_shipping'); @endphp
    @elseif (WC()->cart->needs_shipping() && 'yes' === get_option('woocommerce_enable_shipping_calc'))
      <tr class="shipping border-b border-line/60 py-3">
        <th class="py-3 text-ink-muted text-left font-semibold">{{ __('Envío', 'sage') }}</th>
        <td data-title="{{ __('Envío', 'sage') }}" class="py-3 text-right text-ink-subtle">{!! woocommerce_shipping_calculator() !!}</td>
      </tr>
    @endif

    @foreach (WC()->cart->get_fees() as $fee)
      <tr class="fee border-b border-line/60 py-3">
        <th class="py-3 text-ink-muted text-left font-semibold">{{ esc_html($fee->name) }}</th>
        <td data-title="{{ esc_html($fee->name) }}" class="py-3 text-right text-white">{!! wc_cart_totals_fee_html($fee) !!}</td>
      </tr>
    @endforeach

    @if (wc_tax_enabled() && ! WC()->cart->display_prices_including_tax())
      @php
        $taxable_address = WC()->customer->get_taxable_address();
        $estimated_text  = '';

        if (WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping()) {
          $estimated_text = sprintf(' <small>' . esc_html__('(estimated for %s)', 'woocommerce') . '</small>', WC()->countries->estimated_for_prefix($taxable_address[0]) . WC()->countries->countries[$taxable_address[0]]);
        }
      @endphp

      @if ('itemized' === get_option('woocommerce_tax_total_display'))
        @foreach (WC()->cart->get_tax_totals() as $code => $tax)
          <tr class="tax-rate tax-rate-{{ esc_attr(sanitize_title($code)) }} border-b border-line/60 py-3">
            <th class="py-3 text-ink-muted text-left font-semibold">{{ esc_html($tax->label) . $estimated_text }}</th>
            <td data-title="{{ esc_attr($tax->label) }}" class="py-3 text-right text-white">{!! wp_kses_post($tax->formatted_amount) !!}</td>
          </tr>
        @endforeach
      @else
        <tr class="tax-total border-b border-line/60 py-3">
          <th class="py-3 text-ink-muted text-left font-semibold">{{ esc_html(WC()->countries->tax_or_vat()) . $estimated_text }}</th>
          <td data-title="{{ esc_attr(WC()->countries->tax_or_vat()) }}" class="py-3 text-right text-white">{!! wc_cart_totals_taxes_total_html() !!}</td>
        </tr>
      @endif
    @endif

    @php do_action('woocommerce_cart_totals_before_order_total'); @endphp

    <tr class="order-total border-b border-line py-4">
      <th class="py-4 text-white font-bold text-sm text-left">{{ __('Total', 'sage') }}</th>
      <td data-title="{{ __('Total', 'sage') }}" class="py-4 text-right text-base font-black text-white">{!! wc_cart_totals_order_total_html() !!}</td>
    </tr>

    @php do_action('woocommerce_cart_totals_after_order_total'); @endphp
  </table>

  <div class="wc-proceed-to-checkout mt-6">
    @php do_action('woocommerce_proceed_to_checkout'); @endphp
  </div>

  @php do_action('woocommerce_after_cart_totals'); @endphp
</div>
