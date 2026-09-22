<?php
/**
 * The Notícias page: the frame around the list.
 *
 * Two boxes. The featured card is drawn here, once, and stays where it is; only
 * resultados.php — the label, the filter, the grid and the pagination — is
 * swapped when a category or a page is chosen, and it is also what the AJAX
 * endpoint returns.
 *
 * The card is outside the swapped box on purpose: it does not depend on the
 * filter, so re-rendering it on every click meant replacing an identical card
 * and making the browser paint its image again — which is what read as the
 * featured area reloading. Left alone in the DOM, it does not so much as blink.
 *
 * The filter and the pagination stay ordinary links, pointing at ?categoria=
 * and ?pg= on this same page. That is what the script intercepts; without it,
 * or if a request fails, they simply navigate and the page renders the same
 * list.
 *
 * The three data attributes are what the script needs and nothing more: where
 * to ask, and which page's fields to render — there is no inline script and no
 * global to carry them.
 */
$pagina_id = apit_noticias_pagina_id();
$destaque  = apit_noticias_destaque_da_pagina( $pagina_id );
$etiqueta  = apit_noticias_texto( 'noticias_destaque_etiqueta', __( 'Em destaque', 'apit' ), $pagina_id );
?>
<section class="noticias-arquivo apit-container"
	data-noticias
	data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	data-pagina="<?php echo (int) $pagina_id; ?>">
	<?php if ( $destaque ) : ?>
		<?php
		$categorias    = get_the_category( $destaque->ID );
		$cat_destaque  = $categorias ? $categorias[0]->name : '';
		$capa_destaque = get_the_post_thumbnail_url( $destaque->ID, 'full' );
		$mostrar_data  = apit_noticias_bool( 'noticias_mostrar_data', true, $pagina_id );
		/*
		 * Hidden from the second page of the list onwards, and hidden by the
		 * script from then on — not removed, which is the whole point: the card
		 * that comes back is the same element, already painted.
		 */
		?>
		<div class="noticias-arquivo__destaque" data-noticias-destaque
			<?php echo apit_noticias_pagina_atual() > 1 ? 'hidden' : ''; ?>>
			<?php if ( $etiqueta ) : ?>
				<h2 class="apit-secao__etiqueta"><?php echo esc_html( $etiqueta ); ?></h2>
			<?php endif; ?>

			<article class="noticia noticia--destaque<?php echo $capa_destaque ? '' : ' noticia--sem-imagem'; ?>"
				style="<?php echo esc_attr( apit_cor_categoria_style( $cat_destaque ) ); ?>">
				<a class="noticia__link" href="<?php echo esc_url( get_permalink( $destaque ) ); ?>">
					<span class="noticia__imagem"
						<?php if ( $capa_destaque ) : ?>style="background-image: url('<?php echo esc_url( $capa_destaque ); ?>')"<?php endif; ?>></span>

					<span class="noticia__painel">
						<span class="noticia__meta">
							<?php if ( $cat_destaque ) : ?>
								<span class="noticia__categoria"><?php echo esc_html( $cat_destaque ); ?></span>
							<?php endif; ?>

							<?php if ( $mostrar_data ) : ?>
								<time class="noticia__data" datetime="<?php echo esc_attr( get_the_date( 'c', $destaque ) ); ?>">
									<?php echo esc_html( get_the_date( '', $destaque ) ); ?>
								</time>
							<?php endif; ?>
						</span>

						<span class="noticia__title"><?php echo esc_html( get_the_title( $destaque ) ); ?></span>
					</span>
				</a>
			</article>
		</div>
	<?php endif; ?>

	<?php
	/*
	 * The label and the filter are drawn once and left alone, for the same
	 * reason as the card above: neither depends on what is being filtered.
	 * Only which pill is marked does, and the script moves that mark itself —
	 * replacing the bar would flicker the whole row and lose the focus of
	 * whoever had just clicked it.
	 */
	$lista_label     = apit_noticias_texto( 'noticias_lista_etiqueta', __( 'Todas as notícias', 'apit' ), $pagina_id );
	$mostrar_filtros = apit_noticias_bool( 'noticias_filtros_mostrar', true, $pagina_id );
	$filtros_todas   = apit_noticias_texto( 'noticias_filtros_todas', __( 'Todas', 'apit' ), $pagina_id );
	$termo           = apit_noticias_categoria_ativa();
	$categorias      = $mostrar_filtros ? apit_noticias_categorias( apit_campo( 'noticias_filtros_categorias', $pagina_id ) ) : [];
	?>
	<div class="noticias-arquivo__cabecalho">
		<?php if ( $lista_label ) : ?>
			<h2 class="apit-secao__etiqueta noticias-arquivo__etiqueta"><?php echo esc_html( $lista_label ); ?></h2>
		<?php endif; ?>

		<?php if ( $categorias ) : ?>
			<nav class="noticias-filtros" data-noticias-filtros aria-label="<?php esc_attr_e( 'Filtrar notícias por categoria', 'apit' ); ?>">
				<ul class="noticias-filtros__lista">
					<li>
						<a class="noticias-filtros__item<?php echo $termo ? '' : ' is-ativo'; ?>"
							href="<?php echo esc_url( apit_noticias_url_categoria( '' ) ); ?>"
							<?php echo $termo ? '' : 'aria-current="page"'; ?>>
							<?php echo esc_html( $filtros_todas ); ?>
						</a>
					</li>

					<?php foreach ( $categorias as $categoria ) : ?>
						<?php $ativo = $termo && $termo->term_id === $categoria->term_id; ?>
						<li>
							<a class="noticias-filtros__item<?php echo $ativo ? ' is-ativo' : ''; ?>"
								style="<?php echo esc_attr( apit_cor_categoria_style( $categoria->slug ) ); ?>"
								href="<?php echo esc_url( apit_noticias_url_categoria( $categoria->slug ) ); ?>"
								<?php echo $ativo ? 'aria-current="page"' : ''; ?>>
								<?php echo esc_html( $categoria->name ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
	</div>

	<?php
	/*
	 * aria-live so the change is announced: the visitor clicked a filter, the
	 * page did not reload, and a screen reader would otherwise have nothing to
	 * report. "polite" because it can wait for the current utterance to finish.
	 */
	?>
	<div class="noticias-arquivo__conteudo" data-noticias-conteudo aria-live="polite">
		<?php get_template_part( 'template-parts/noticias/resultados' ); ?>
	</div>
</section>
