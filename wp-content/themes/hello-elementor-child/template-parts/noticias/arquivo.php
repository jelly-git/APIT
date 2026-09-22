<?php
/**
 * The Notícias page: featured card, category filter, grid and pagination.
 *
 * Built from the same card the Home uses, at archive scale. Every label, count
 * and toggle is a field on the page — the section is deliberately dumb, so that
 * changing what it says never means changing this file.
 *
 * The filter and the page number travel as query arguments (?categoria=,
 * ?pg=) rather than as path segments: see the note in inc/noticias.php on why
 * a static page cannot safely paginate through /2/.
 */
$pagina_id = get_queried_object_id();

/*
 * Three readers, so a missing field and an emptied field both fall back to the
 * value the design shows, and a number the client clears cannot turn into a
 * query for zero posts.
 */
$texto_de = function ( $nome, $omissao ) use ( $pagina_id ) {
	$valor = trim( (string) apit_campo( $nome, $pagina_id ) );

	return '' === $valor ? $omissao : $valor;
};

$bool_de = function ( $nome, $omissao ) use ( $pagina_id ) {
	$valor = apit_campo( $nome, $pagina_id );

	return null === $valor ? $omissao : (bool) $valor;
};

$numero_de = function ( $nome, $omissao, $minimo, $maximo ) use ( $pagina_id ) {
	$valor = (int) apit_campo( $nome, $pagina_id );

	return $valor > 0 ? max( $minimo, min( $maximo, $valor ) ) : $omissao;
};

$mostrar_destaque = $bool_de( 'noticias_destaque_mostrar', true );
$destaque_fonte   = $texto_de( 'noticias_destaque_fonte', 'marcada' );
$destaque_label   = $texto_de( 'noticias_destaque_etiqueta', __( 'Em destaque', 'apit' ) );
$lista_label      = $texto_de( 'noticias_lista_etiqueta', __( 'Todas as notícias', 'apit' ) );
$mostrar_filtros  = $bool_de( 'noticias_filtros_mostrar', true );
$filtros_todas    = $texto_de( 'noticias_filtros_todas', __( 'Todas', 'apit' ) );
$por_pagina       = $numero_de( 'noticias_por_pagina', 9, 1, 24 );
$colunas          = $numero_de( 'noticias_colunas', 3, 2, 4 );
$mostrar_data     = $bool_de( 'noticias_mostrar_data', true );
$mostrar_resumo   = $bool_de( 'noticias_mostrar_resumo', true );
$resumo_palavras  = $numero_de( 'noticias_resumo_palavras', 22, 5, 60 );
$ler_mais         = $texto_de( 'noticias_ler_mais', __( 'Ler notícia', 'apit' ) );
$texto_vazio      = $texto_de( 'noticias_vazio', __( 'De momento não há mais notícias para mostrar.', 'apit' ) );
$mostrar_paginas  = $bool_de( 'noticias_paginacao_mostrar', true );
$label_anterior   = $texto_de( 'noticias_paginacao_anterior', __( 'Anterior', 'apit' ) );
$label_seguinte   = $texto_de( 'noticias_paginacao_seguinte', __( 'Seguinte', 'apit' ) );

$pagina  = apit_noticias_pagina_atual();
$termo   = apit_noticias_categoria_ativa();
$var_pag = apit_noticias_var_pagina();

/*
 * The shared half of every query on this page. ignore_sticky_posts because the
 * featured slot is chosen here, deliberately: left on, WordPress would also
 * lift the sticky post to the top of the grid, where it would appear a second
 * time.
 */
$base = [
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
];

if ( $termo ) {
	$base['cat'] = $termo->term_id;
}

/*
 * The featured post is shown on the first page only, and is left out of the
 * grid on every page — including the ones it is not shown on, so it is never
 * met twice while paging through.
 */
$destaque = $mostrar_destaque ? apit_noticia_destaque( $base, $destaque_fonte ) : null;

$consulta = new WP_Query( array_merge( $base, [
	'posts_per_page' => $por_pagina,
	'offset'         => ( $pagina - 1 ) * $por_pagina,
	'post__not_in'   => $destaque ? [ $destaque->ID ] : [],
] ) );

$total_paginas = (int) ceil( $consulta->found_posts / $por_pagina );
$categorias    = $mostrar_filtros ? apit_noticias_categorias( apit_campo( 'noticias_filtros_categorias', $pagina_id ) ) : [];
?>
<section class="noticias-arquivo apit-container">
	<?php if ( $destaque && 1 === $pagina ) : ?>
		<?php
		$cats_destaque = get_the_category( $destaque->ID );
		$cat_destaque  = $cats_destaque ? $cats_destaque[0]->name : '';
		$capa_destaque = get_the_post_thumbnail_url( $destaque->ID, 'full' );
		?>
		<div class="noticias-arquivo__destaque">
			<?php if ( $destaque_label ) : ?>
				<h2 class="apit-secao__etiqueta"><?php echo esc_html( $destaque_label ); ?></h2>
			<?php endif; ?>

			<article class="noticia noticia--destaque"
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

	<div class="noticias-arquivo__cabecalho">
		<?php if ( $lista_label ) : ?>
			<h2 class="apit-secao__etiqueta noticias-arquivo__etiqueta"><?php echo esc_html( $lista_label ); ?></h2>
		<?php endif; ?>

		<?php if ( $categorias ) : ?>
			<nav class="noticias-filtros" aria-label="<?php esc_attr_e( 'Filtrar notícias por categoria', 'apit' ); ?>">
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

	<?php if ( $consulta->have_posts() ) : ?>
		<div class="noticias-arquivo__grelha" style="--apit-colunas: <?php echo (int) $colunas; ?>">
			<?php
			foreach ( $consulta->posts as $noticia ) {
				get_template_part( 'template-parts/noticias/cartao', null, [
					'post'            => $noticia,
					'mostrar_data'    => $mostrar_data,
					'mostrar_resumo'  => $mostrar_resumo,
					'resumo_palavras' => $resumo_palavras,
					'ler_mais'        => $ler_mais,
				] );
			}
			?>
		</div>
	<?php else : ?>
		<?php
		/*
		 * Shown whenever the grid comes back empty, including when the featured
		 * card above it is the only post in the category being filtered: the
		 * label and the filter are printed either way, and a heading over
		 * nothing reads as a page that failed to load.
		 */
		?>
		<p class="noticias-arquivo__vazio"><?php echo esc_html( $texto_vazio ); ?></p>
	<?php endif; ?>

	<?php if ( $mostrar_paginas && $total_paginas > 1 ) : ?>
		<nav class="noticias-paginacao" aria-label="<?php esc_attr_e( 'Páginas de notícias', 'apit' ); ?>">
			<?php
			/*
			 * add_query_arg builds on the address being viewed, so the category
			 * currently selected travels with the page number without this
			 * having to know about it.
			 */
			echo paginate_links( [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links escapes its own output.
				'base'      => add_query_arg( $var_pag, '%#%' ),
				'format'    => '',
				'current'   => $pagina,
				'total'     => $total_paginas,
				'mid_size'  => 1,
				'prev_text' => esc_html( $label_anterior ),
				'next_text' => esc_html( $label_seguinte ),
			] );
			?>
		</nav>
	<?php endif; ?>
</section>
