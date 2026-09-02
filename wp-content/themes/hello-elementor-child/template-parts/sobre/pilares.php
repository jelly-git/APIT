<?php
/**
 * The four circles closing the hero.
 *
 * The client asked for all four to use the same artwork (the last of the four
 * Figma options) at a smaller size, so one image is reused across the row
 * rather than a different gradient per column — which is why the image is a
 * single field and only the captions repeat.
 *
 * Content comes from the ACF group on the page (Sobre a APIT › Círculos).
 */
$bola   = apit_media_url( (string) apit_campo( 'sobre_bola' ), 'img' );
$linhas = apit_campo( 'sobre_pilares' );

$textos = [];

if ( is_array( $linhas ) ) {
	foreach ( $linhas as $linha ) {
		$texto = trim( (string) ( $linha['texto'] ?? '' ) );

		if ( '' !== $texto ) {
			$textos[] = $texto;
		}
	}
}

if ( ! $textos ) {
	return;
}
?>
<ul class="sobre-pilares">
	<?php foreach ( $textos as $texto ) : ?>
		<li class="sobre-pilares__item">
			<span class="sobre-pilares__bola">
				<?php if ( $bola ) : ?>
					<img src="<?php echo esc_url( $bola ); ?>" alt="" width="140" height="140">
				<?php endif; ?>
			</span>
			<p class="sobre-pilares__texto"><?php echo esc_html( $texto ); ?></p>
		</li>
	<?php endforeach; ?>
</ul>
