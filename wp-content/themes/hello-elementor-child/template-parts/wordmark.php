<?php
/**
 * The oversized wordmark behind a page hero — ASSOCIADOS, WORLD, EVENTOS,
 * DOCUMENTOS, COMO ADERIR.
 *
 * The Figma original uses a "Scanline warp" effect. This is the same CSS
 * approximation the Sobre a APIT hero uses: the white type is masked by a
 * repeating horizontal stripe gradient, so the letters dissolve into lines the
 * way they do in the design. The warp itself — the wave distortion — has no CSS
 * equivalent; a word that has to be exact needs exporting as an image.
 *
 * The text arrives as a shortcode attribute rather than a field because it
 * belongs to the hero's composition, not to the page's content: it is edited in
 * the Elementor panel beside the title it sits behind.
 *
 * Long wordmarks are split on spaces so each word gets its own line, which is
 * how the design sets "COMO ADERIR". A single word stays on one line.
 *
 * Decorative, so it is hidden from assistive technology — the real heading is
 * the h1 beside it.
 *
 * @var array $args texto
 */
$texto = trim( (string) ( $args['texto'] ?? '' ) );

if ( '' === $texto ) {
	return;
}

$palavras = preg_split( '/\s+/', $texto );
?>
<div class="pagina-hero__decor" aria-hidden="true">
	<?php foreach ( $palavras as $i => $palavra ) : ?>
		<span class="pagina-hero__word<?php echo $i ? ' pagina-hero__word--baixo' : ''; ?>"><?php echo esc_html( $palavra ); ?></span>
	<?php endforeach; ?>
</div>
