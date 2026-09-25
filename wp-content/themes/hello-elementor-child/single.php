<?php
/**
 * A single news article.
 *
 * Elementor's free plugin has no Theme Builder, so a single-post layout cannot
 * be drawn in Elementor: what surrounds the article has to be a theme template.
 * This one therefore keeps the theme's part to what is genuinely per-post —
 * the hero, the meta, the navigation, the row of other news — and hands
 * everything else to Elementor:
 *
 * - the body of the article is `the_content()`, so a post opened with "Editar
 *   com Elementor" is built from Elementor widgets, whichever the editor wants;
 * - what closes the article is a saved Elementor template, chosen in the field
 *   "Bloco que fecha a notícia" on the Notícias page and rendered live by
 *   [apit_template], so editing it once changes every article.
 *
 * The hero is the one the five pages share, with the Notícias gradient and the
 * article's own featured image behind it. The settings all come from the
 * Notícias page's field group: one screen in the back office for the whole
 * section, list and article alike.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$noticias_id  = apit_noticias_pagina();
$url_noticias = $noticias_id ? get_permalink( $noticias_id ) : home_url( '/' );

while ( have_posts() ) :
	the_post();

	$categorias = get_the_category();
	$categoria  = $categorias ? $categorias[0] : null;
	$capa       = get_the_post_thumbnail_url( get_the_ID(), 'full' );

	$voltar        = apit_noticias_texto( 'single_voltar', __( 'Voltar às notícias', 'apit' ), $noticias_id );
	$mostrar_nav   = apit_noticias_bool( 'single_nav', true, $noticias_id );
	$mostrar_rel   = apit_noticias_bool( 'single_relacionadas_mostrar', true, $noticias_id );
	$rel_etiqueta  = apit_noticias_texto( 'single_relacionadas_etiqueta', __( 'Outras notícias', 'apit' ), $noticias_id );
	$rel_numero    = apit_noticias_numero( 'single_relacionadas_numero', 3, 2, 4, $noticias_id );
	$rodape        = (int) apit_campo( 'single_rodape', $noticias_id );
	$relacionadas  = $mostrar_rel ? apit_noticias_relacionadas( get_the_ID(), $rel_numero ) : [];
	?>
	<main id="content" <?php post_class( 'noticia-single' ); ?>
		style="<?php echo esc_attr( apit_cor_categoria_style( $categoria ? $categoria->name : '' ) ); ?>">

		<header class="apit-pagina-hero hero--noticias noticia-single__hero">
			<?php if ( $capa ) : ?>
				<?php
				/*
				 * The article's own picture, in the layer the hero already has
				 * for a video or a gallery — so it is cropped, scrimmed and
				 * faded by the rules the other heroes use, and nothing here
				 * invents a second way of putting an image behind a hero.
				 */
				?>
				<div class="apit-hero__media">
					<?php echo get_the_post_thumbnail( get_the_ID(), 'full', [ 'class' => 'apit-hero__poster', 'alt' => '' ] ); ?>
				</div>
			<?php endif; ?>

			<?php echo do_shortcode( '[apit_wordmark texto="NOTÍ CIAS"]' ); ?>

			<div class="pagina-hero__col">
				<?php
				// O mesmo trilho das páginas, de inc/breadcrumbs.php: aqui é
				// Home / Notícias / a categoria do artigo.
				echo apit_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput -- escapado na origem.
				?>

				<h1 class="pagina-hero__titulo noticia-single__titulo"><?php the_title(); ?></h1>

				<p class="noticia-single__meta">
					<?php if ( $categoria ) : ?>
						<span class="noticia-single__categoria"><?php echo esc_html( $categoria->name ); ?></span>
					<?php endif; ?>

					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</p>
			</div>
		</header>

		<div class="apit-secao apit-secao--clara noticia-single__corpo">
			<article class="apit-container noticia-single__artigo">
				<?php
				/*
				 * The whole of the article, exactly as the editor built it —
				 * the classic editor's blocks, or an Elementor layout when the
				 * post was opened with "Editar com Elementor".
				 */
				the_content();

				wp_link_pages( [
					'before' => '<nav class="noticia-single__paginas">',
					'after'  => '</nav>',
				] );
				?>

				<?php if ( has_tag() ) : ?>
					<p class="noticia-single__tags"><?php the_tags( '', ' ' ); ?></p>
				<?php endif; ?>
			</article>

			<?php if ( $mostrar_nav || $voltar ) : ?>
				<?php
				$anterior = $mostrar_nav ? get_previous_post() : null;
				$seguinte = $mostrar_nav ? get_next_post() : null;
				?>
				<nav class="apit-container noticia-single__nav" aria-label="<?php esc_attr_e( 'Outras notícias', 'apit' ); ?>">
					<?php if ( $anterior ) : ?>
						<a class="noticia-single__salto noticia-single__salto--anterior" href="<?php echo esc_url( get_permalink( $anterior ) ); ?>">
							<span class="noticia-single__salto-rotulo"><?php esc_html_e( 'Anterior', 'apit' ); ?></span>
							<span class="noticia-single__salto-titulo"><?php echo esc_html( get_the_title( $anterior ) ); ?></span>
						</a>
					<?php endif; ?>

					<?php if ( $voltar ) : ?>
						<a class="btn btn--outline btn--escuro noticia-single__voltar" href="<?php echo esc_url( $url_noticias ); ?>">
							<?php echo esc_html( $voltar ); ?>
						</a>
					<?php endif; ?>

					<?php if ( $seguinte ) : ?>
						<a class="noticia-single__salto noticia-single__salto--seguinte" href="<?php echo esc_url( get_permalink( $seguinte ) ); ?>">
							<span class="noticia-single__salto-rotulo"><?php esc_html_e( 'Seguinte', 'apit' ); ?></span>
							<span class="noticia-single__salto-titulo"><?php echo esc_html( get_the_title( $seguinte ) ); ?></span>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</div>

		<?php if ( $relacionadas ) : ?>
			<section class="apit-secao apit-secao--clara noticia-single__relacionadas">
				<div class="apit-container">
					<?php if ( $rel_etiqueta ) : ?>
						<h2 class="apit-secao__etiqueta"><?php echo esc_html( $rel_etiqueta ); ?></h2>
					<?php endif; ?>

					<div class="noticias-arquivo__grelha" style="--apit-colunas: <?php echo (int) $rel_numero; ?>">
						<?php
						/*
						 * The archive's card, unchanged: the row under an
						 * article and the row inside the list are the same
						 * component, so they cannot drift apart.
						 */
						foreach ( $relacionadas as $relacionada ) {
							get_template_part( 'template-parts/noticias/cartao', null, [
								'post'            => $relacionada,
								'mostrar_data'    => apit_noticias_bool( 'noticias_mostrar_data', true, $noticias_id ),
								'mostrar_resumo'  => apit_noticias_bool( 'noticias_mostrar_resumo', true, $noticias_id ),
								'resumo_palavras' => apit_noticias_numero( 'noticias_resumo_palavras', 22, 5, 60, $noticias_id ),
								'ler_mais'        => apit_noticias_texto( 'noticias_ler_mais', __( 'Ler notícia', 'apit' ), $noticias_id ),
							] );
						}
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $rodape ) : ?>
			<?php echo do_shortcode( sprintf( '[apit_template id="%d"]', $rodape ) ); ?>
		<?php endif; ?>
	</main>
	<?php
endwhile;

get_footer();
