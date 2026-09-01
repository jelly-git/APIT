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
 * - bloco: a 410x129 block of solid category colour with the text inside it in
 *   white (Rect 12, text at y=2063/2094).
 *
 * A secondary post renders as "imagem" when it has a featured image and as
 * "bloco" when it does not.
 */

/*
 * The featured (large) card is whichever post is marked "sticky", so an editor
 * chooses it rather than it always being the newest one. Without a sticky post
 * it falls back to the most recent.
 */
$sticky = get_option( 'sticky_posts' );

$noticias = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 3,
] );

if ( ! $noticias ) {
	return;
}

// Pull the sticky post to the front if it is in the result set.
if ( $sticky ) {
	usort( $noticias, function ( $a, $b ) use ( $sticky ) {
		return ( in_array( $b->ID, $sticky, true ) ? 1 : 0 ) - ( in_array( $a->ID, $sticky, true ) ? 1 : 0 );
	} );
}

$destaque   = array_shift( $noticias );
$secundaria = $noticias;

/*
 * The design stacks the image card above the colour block, so posts with a
 * featured image come first — the variant is decided by that image.
 */
usort( $secundaria, function ( $a, $b ) {
	return ( has_post_thumbnail( $b ) ? 1 : 0 ) - ( has_post_thumbnail( $a ) ? 1 : 0 );
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
		$thumb = get_the_post_thumbnail_url( $destaque->ID, 'full' );
		?>
		<article class="noticia noticia--destaque">
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
			<?php foreach ( $secundaria as $post ) : ?>
				<?php
				$cat     = $categoria_de( $post );
				$thumb   = get_the_post_thumbnail_url( $post->ID, 'large' );
				$variant = $thumb ? 'imagem' : 'bloco';
				?>
				<article class="noticia noticia--<?php echo esc_attr( $variant ); ?>"
					style="<?php echo esc_attr( apit_cor_categoria_style( $cat ) ); ?>">
					<a class="noticia__link" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
						<?php if ( $thumb ) : ?>
							<span class="noticia__imagem" style="background-image: url('<?php echo esc_url( $thumb ); ?>')"></span>
						<?php endif; ?>

						<span class="noticia__texto">
							<?php if ( $cat ) : ?>
								<span class="noticia__categoria"><?php echo esc_html( $cat ); ?></span>
							<?php endif; ?>
							<span class="noticia__title"><?php echo esc_html( get_the_title( $post ) ); ?></span>
						</span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
