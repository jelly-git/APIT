<?php
/**
 * Notícias section (home) — Figma nodes 16:19927 / 16:19929 / 16:19935
 *
 * The design shows one large featured item plus two stacked secondary items.
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

/**
 * Renders one news card. $variant is "destaque" or "secundaria".
 */
$render_card = function ( $post, $variant ) {
	$categorias = get_the_category( $post->ID );
	$categoria  = $categorias ? $categorias[0]->name : '';
	$thumb      = get_the_post_thumbnail_url( $post->ID, 'large' );
	?>
	<article class="noticia noticia--<?php echo esc_attr( $variant ); ?>"
		<?php if ( $thumb ) : ?>style="background-image: url('<?php echo esc_url( $thumb ); ?>')"<?php endif; ?>>
		<a class="noticia__link" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
			<div class="noticia__content">
				<?php if ( $categoria ) : ?>
					<span class="noticia__categoria"><?php echo esc_html( $categoria ); ?></span>
				<?php endif; ?>
				<h3 class="noticia__title"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
			</div>
		</a>
	</article>
	<?php
};
?>
<section class="noticias">
	<div class="apit-container noticias__grid">
		<?php $render_card( $destaque, 'destaque' ); ?>

		<div class="noticias__coluna">
			<?php foreach ( $secundaria as $post ) : ?>
				<?php $render_card( $post, 'secundaria' ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
