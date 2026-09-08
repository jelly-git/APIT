<?php
/**
 * Internacionalização — brand block pairing the logo with a short pitch.
 *
 * Kept as its own template part (rather than folded into the section around
 * it) so the logo and its copy can be reordered or reused on their own,
 * mirroring how the other Internacionalização blocks are split apart.
 *
 * Content comes from the ACF group on the page (Internacionalização › Marca).
 */
$logo   = (int) apit_campo( 'inter_marca_logo' );
$titulo = trim( (string) apit_campo( 'inter_marca_titulo' ) );
$texto  = trim( (string) apit_campo( 'inter_marca_texto' ) );

if ( ! $logo && ! $titulo && ! $texto ) {
	return;
}
?>
<section class="inter-marca">
	<div class="inter-marca__logo">
		<?php if ( $logo ) : ?>
			<?php echo wp_get_attachment_image( $logo, 'large', false, [ 'alt' => '' ] ); ?>
		<?php endif; ?>
	</div>

	<div class="inter-marca__corpo">
		<?php if ( $titulo ) : ?>
			<h2 class="inter-marca__titulo"><?php echo esc_html( $titulo ); ?></h2>
		<?php endif; ?>

		<?php if ( $texto ) : ?>
			<p class="inter-marca__paragrafo"><?php echo nl2br( esc_html( $texto ) ); ?></p>
		<?php endif; ?>
	</div>
</section>
