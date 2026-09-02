<?php
/**
 * The four circles closing the hero.
 *
 * The client asked for all four to use the same artwork (the last of the four
 * Figma options) at a smaller size, so one image is reused across the row
 * rather than a different gradient per column.
 *
 * @var array $args bola, texto_1 … texto_4
 */
$bola = apit_media_url( $args['bola'] ?? '', 'img' );

$textos = [];

for ( $i = 1; $i <= 4; $i++ ) {
	$texto = trim( (string) ( $args[ 'texto_' . $i ] ?? '' ) );

	if ( '' !== $texto ) {
		$textos[] = $texto;
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
