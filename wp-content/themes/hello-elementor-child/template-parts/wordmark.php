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
 * Alignment is an attribute rather than a class the page carries, because it
 * changes what the block is: to the right it is a layer behind the hero's text,
 * to the left it is a column of its own beside it — as "COMO ADERIR" is. The
 * stylesheet reads the modifier; the Elementor panel is where it is chosen.
 *
 * @var array $args texto, alinhamento
 */
$texto = trim( (string) ( $args['texto'] ?? '' ) );

if ( '' === $texto ) {
	return;
}

$alinhamento = 'esquerda' === ( $args['alinhamento'] ?? '' ) ? ' pagina-hero__decor--esquerda' : '';
$palavras    = preg_split( '/\s+/', $texto );
?>
<div class="pagina-hero__decor<?php echo esc_attr( $alinhamento ); ?>" aria-hidden="true">
	<?php foreach ( $palavras as $i => $palavra ) : ?>
		<span class="pagina-hero__word<?php echo $i ? ' pagina-hero__word--baixo' : ''; ?>"><?php echo esc_html( $palavra ); ?></span>
	<?php endforeach; ?>
</div>
