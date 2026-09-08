<?php
/**
 * Associados — partner logos carousel.
 *
 * The arrows only earn their place once there is something to scroll to: a
 * single logo fills the track on its own, so the pair is hidden rather than
 * left sitting there doing nothing. The link follows the same rule already
 * used for the calendar's event button in template-parts/calendario.php —
 * label and destination are both required, because a button with only one
 * of the two either says nothing or goes nowhere.
 */
$label  = trim( (string) apit_campo( 'assoc_logos_label' ) );
$texto  = trim( (string) apit_campo( 'assoc_logos_texto' ) );
$botao  = trim( (string) apit_campo( 'assoc_logos_botao' ) );
$url    = trim( (string) apit_campo( 'assoc_logos_url' ) );
$logos  = apit_campo( 'assoc_logos' );

if ( ! is_array( $logos ) ) {
	$logos = [];
}

$ids = array_filter( array_map( 'intval', $logos ) );

if ( ! $ids ) {
	return;
}

$mostrar_setas = count( $ids ) > 1;
$mostrar_botao = $botao && $url;
$mostrar_acoes = $mostrar_setas || $mostrar_botao;
?>
<section class="assoc-logos">
	<div class="assoc-logos__topo">
		<div class="assoc-logos__intro">
			<?php if ( $label ) : ?>
				<p class="apit-secao__etiqueta"><?php echo esc_html( $label ); ?></p>
			<?php endif; ?>

			<?php if ( $texto ) : ?>
				<p class="assoc-logos__texto"><?php echo nl2br( esc_html( $texto ) ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $mostrar_acoes ) : ?>
			<div class="assoc-logos__acoes">
				<?php if ( $mostrar_setas ) : ?>
					<button class="assoc-logos__seta" type="button" data-dir="anterior" aria-label="<?php esc_attr_e( 'Anterior', 'apit' ); ?>"></button>
					<button class="assoc-logos__seta" type="button" data-dir="seguinte" aria-label="<?php esc_attr_e( 'Seguinte', 'apit' ); ?>"></button>
				<?php endif; ?>

				<?php if ( $mostrar_botao ) : ?>
					<a class="btn btn--outline" href="<?php echo esc_url( $url ); ?>">
						<?php echo esc_html( $botao ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="assoc-logos__track">
		<?php foreach ( $ids as $id ) : ?>
			<div class="assoc-logos__item">
				<?php echo wp_get_attachment_image( $id, 'medium', false, [ 'alt' => '' ] ); ?>
			</div>
		<?php endforeach; ?>
	</div>
</section>
