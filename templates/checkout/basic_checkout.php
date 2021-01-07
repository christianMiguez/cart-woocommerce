<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mp-panel-checkout">
  <div class="mp-row-checkout">
	<?php if ( 0 !== (int) $credito) : ?>
	<div id="framePayments" class="mp-col-md-12">
		<div class="frame-tarjetas">
			<p class="mp-subtitle-basic-checkout">
				<?php echo esc_html_e( 'Credit cards', 'woocommerce-mercadopago' ); ?>
				<span class="mp-badge-checkout"><?php echo esc_html_e( 'Until', 'woocommerce-mercadopago' ); ?> <?php echo $installments; ?> <?php echo __( $str_cuotas, 'woocommerce-mercadopago' ); ?></span>
			</p>
			<?php foreach ( $tarjetas as $tarjeta ) : ?>
				<?php if ( $tarjeta['type'] == 'credit_card' ) : ?>
				<img src="<?php echo esc_html( $tarjeta['image']); ?>" class="mp-img-fluid mp-img-tarjetas" alt=""/>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $debito != 0 ) : ?>
	<div id="framePayments" class="mp-col-md-6 mp-pr-15">
		<div class="frame-tarjetas">
			<p class="submp-title-checkout"><?php echo __( 'Debit card', 'woocommerce-mercadopago' ); ?></p>

			<?php foreach ( $tarjetas as $tarjeta ) : ?>
				<?php if ( $tarjeta['type'] == 'debit_card' || $tarjeta['type'] == 'prepaid_card' ) : ?>
				<img src="<?php echo $tarjeta['image']; ?>" class="mp-img-fluid mp-img-tarjetas" alt="" />
			  <?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $efectivo != 0 ) : ?>
	<div id="framePayments" class="mp-col-md-6">
		<div class="frame-tarjetas">
			<p class="submp-title-checkout"><?php echo __( 'Payments in cash', 'woocommerce-mercadopago' ); ?></p>

			<?php foreach ( $tarjetas as $tarjeta ) : ?>
				<?php if ( $tarjeta['type'] != 'credit_card' && $tarjeta['type'] != 'debit_card' && $tarjeta['type'] != 'prepaid_card' ) : ?>
				<img src="<?php echo $tarjeta['image']; ?>" class="mp-img-fluid mp-img-tarjetas" alt=""/>
			  <?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $method == 'redirect' ) : ?>
	<div class="mp-col-md-12 mp-pt-20">
		<div class="mp-redirect-frame">
			<img src="<?php echo $cho_image; ?>" class="mp-img-fluid mp-img-redirect" alt=""/>
			<p><?php echo __( 'We take you to our site to complete the payment', 'woocommerce-mercadopago' ); ?></p>
		</div>
	</div>
	<?php endif; ?>

  </div>
</div>

<script type="text/javascript" src="<?php echo $path_to_javascript; ?>?ver=<?php echo $plugin_version; ?>"></script>
