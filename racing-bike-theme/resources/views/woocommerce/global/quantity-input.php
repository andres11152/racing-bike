<?php
/**
 * Product quantity inputs - Racing Bike Enterprise
 *
 * @version 9.4.0
 *
 * @var bool   $readonly If the input should be set to readonly.
 * @var string $type     The input type attribute.
 * @var int    $step     The step attribute.
 * @var int    $min_value The minimum value attribute.
 * @var int    $max_value The maximum value attribute.
 * @var string $input_id The input id attribute.
 * @var string $input_name The input name attribute.
 * @var string $input_value The input value attribute.
 * @var array  $classes The classes to apply to the input.
 * @var string $placeholder The placeholder attribute.
 * @var string $inputmode The inputmode attribute.
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: Quantity. */
$label = ! empty( $args['product_name'] ) ? sprintf( esc_html__( '%s cantidad', 'woocommerce' ), wp_strip_all_tags( $args['product_name'] ) ) : esc_html__( 'Cantidad', 'woocommerce' );

if ( $max_value && $min_value === $max_value ) {
	?>
	<div class="quantity hidden">
		<input type="hidden" id="<?php echo esc_attr( $input_id ); ?>" class="qty" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $min_value ); ?>" />
	</div>
	<?php
} else {
	?>
	<div class="quantity rb-quantity-pill inline-flex items-center justify-between rounded-full border border-white/10 bg-white/[0.04] p-1 shadow-sm transition-all hover:border-white/20 h-12 shrink-0">
		<?php do_action( 'woocommerce_before_quantity_input_field' ); ?>

		<label class="screen-reader-text sr-only" for="<?php echo esc_attr( $input_id ); ?>">
			<?php echo esc_html( $label ); ?>
		</label>

		<button
			type="button"
			class="rb-qty-btn rb-qty-minus size-10 rounded-full flex items-center justify-center text-zinc-400 hover:text-white hover:bg-white/10 active:scale-90 transition-all cursor-pointer select-none bg-transparent border-0"
			aria-label="<?php echo esc_attr__( 'Reducir cantidad', 'sage' ); ?>"
			tabindex="-1"
		>
			<svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
				<path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
			</svg>
		</button>

		<input
			type="<?php echo esc_attr( $type ); ?>"
			<?php echo $readonly ? 'readonly="readonly"' : ''; ?>
			id="<?php echo esc_attr( $input_id ); ?>"
			class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?> rb-qty-input w-10 sm:w-12 border-0 bg-transparent py-0 text-center text-sm font-bold text-white focus:outline-none select-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none m-0"
			name="<?php echo esc_attr( $input_name ); ?>"
			value="<?php echo esc_attr( $input_value ); ?>"
			aria-label="<?php esc_attr_e( 'Product quantity', 'woocommerce' ); ?>"
			size="4"
			min="<?php echo esc_attr( $min_value ); ?>"
			max="<?php echo esc_attr( 0 < $max_value ? $max_value : '' ); ?>"
			<?php if ( ! $readonly ) : ?>
				step="<?php echo esc_attr( $step ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				inputmode="<?php echo esc_attr( $inputmode ); ?>"
				autocomplete="<?php echo esc_attr( isset( $autocomplete ) ? $autocomplete : 'off' ); ?>"
			<?php endif; ?>
		/>

		<button
			type="button"
			class="rb-qty-btn rb-qty-plus size-10 rounded-full flex items-center justify-center text-zinc-400 hover:text-white hover:bg-white/10 active:scale-90 transition-all cursor-pointer select-none bg-transparent border-0"
			aria-label="<?php echo esc_attr__( 'Aumentar cantidad', 'sage' ); ?>"
			tabindex="-1"
		>
			<svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
			</svg>
		</button>

		<?php do_action( 'woocommerce_after_quantity_input_field' ); ?>
	</div>
	<?php
}
