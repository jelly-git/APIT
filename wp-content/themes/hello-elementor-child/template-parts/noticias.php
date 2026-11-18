<?php
/**
 * Notícias section (home) — Figma nodes 16:19927 / 16:19929 / 16:19935
 *
 * The three cards are deliberately different, per the design:
 *
 * - destaque: a 855x523 image with a white 496x225 panel over its bottom-left
 *   corner holding the category and title in dark text (Rect 10 + Rect 13);
 * - imagem: a 407x218 image with the category and title below it, on the
 *   section background (Rect 11, text at y=1889/1925);
 * - texto: a notícia sem fotografia, só com a etiqueta e o título, abertos por
 *   uma linha fina na cor da categoria.
 *
 * A secondary post renders as "imagem" when it has a photograph and as "texto"
 * when it does not. O "texto" substituiu o bloco de cor sólida do Figma (Rect
 * 12) em 0.83.0: era um segundo bloco de cor ao lado das fotografias, o mesmo
 * ruído que a APIT pediu para tirar das notícias. Um degradé dos antigos conta
 * como não ter fotografia (apit_noticia_imagem, em inc/noticias.php).
 */

/*
 * The featured (large) card is the newest post with "Notícia em destaque"
 * ticked, which is the same field and the same rule as the Notícias page — one
 * switch in the back office, not one per place the card appears. With nothing
 * ticked it falls back to the newest post, so the slot is never empty.
 */
$base = [
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
];

$destaque = apit_noticia_destaque( $base, 'marcada' );

if ( ! $destaque ) {
	return;
}

$secundaria = get_posts( array_merge( $base, [
	'posts_per_page' => 2,
	'post__not_in'   => [ $destaque->ID ],
] ) );

/*
 * The design stacks the image card above the text one, so posts with a
 * photograph come first — the variant is decided by that photograph.
 */
usort( $secundaria, function ( $a, $b ) {
	return ( apit_noticia_imagem( $b ) ? 1 : 0 ) - ( apit_noticia_imagem( $a ) ? 1 : 0 );
} );

/**
 * Category name of a post, or an empty string.
 */
$categoria_de = function ( $post ) {
	$cats = get_the_category( $post->ID );

	return $cats ? $cats[0]->name : '';
};
?>
<section class="noticias">
	<div class="apit-container noticias__grid">
		<?php
		$cat   = $categoria_de( $destaque );
		$thumb = apit_noticia_imagem( $destaque, 'full' );
		?>
		<article class="noticia noticia--destaque<?php echo $thumb ? '' : ' noticia--sem-imagem'; ?>"
			style="<?php echo esc_attr( apit_cor_categoria_style( $cat ) ); ?>">
			<a class="noticia__link" href="<?php echo esc_url( get_permalink( $destaque ) ); ?>">
				<span class="noticia__imagem"
					<?php if ( $thumb ) : ?>style="background-image: url('<?php echo esc_url( $thumb ); ?>')"<?php endif; ?>></span>

				<span class="noticia__painel">
					<?php if ( $cat ) : ?>
						<span class="noticia__categoria"><?php echo esc_html( $cat ); ?></span>
					<?php endif; ?>
					<span class="noticia__title"><?php echo esc_html( get_the_title( $destaque ) ); ?></span>
				</span>
			</a>
		</article>

		<div class="noticias__coluna">
			<?php // O ciclo não se chama $post: este ficheiro corre dentro de load_template(), onde $post é a página atual do WordPress, e o ciclo deixava-a presa na última notícia. ?>
			<?php foreach ( $secundaria as $noticia ) : ?>
				<?php
				$cat     = $categoria_de( $noticia );
				$thumb   = apit_noticia_imagem( $noticia, 'large' );
				$variant = $thumb ? 'imagem' : 'texto';
				?>
				<article class="noticia noticia--<?php echo esc_attr( $variant ); ?>"
					style="<?php echo esc_attr( apit_cor_categoria_style( $cat ) ); ?>">
					<a class="noticia__link" href="<?php echo esc_url( get_permalink( $noticia ) ); ?>">
						<?php if ( $thumb ) : ?>
							<span class="noticia__imagem" style="background-image: url('<?php echo esc_url( $thumb ); ?>')"></span>
						<?php endif; ?>

						<span class="noticia__texto">
							<?php if ( $cat ) : ?>
								<span class="noticia__categoria"><?php echo esc_html( $cat ); ?></span>
							<?php endif; ?>
							<span class="noticia__title"><?php echo esc_html( get_the_title( $noticia ) ); ?></span>
						</span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
