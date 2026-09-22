<?php
/**
 * The part of the Notícias page that changes: the grid and the pagination.
 *
 * Split from arquivo.php because this is exactly what the AJAX endpoint returns
 * — one template rendered by both, so the page a visitor lands on and the page
 * they filter into are built by the same code and cannot drift apart.
 *
 * It reads the filter and the page number from the request, which is what makes
 * that work: on the page they arrive as ?categoria= and ?pg=, and the script
 * sends the same two to admin-ajax. Nothing here knows which of the two it is
 * answering.
 *
 * The featured card, the section label and the filter bar are not here: none of
 * them depends on what is being listed, so they live in the shell and the swap
 * leaves them alone. All this needs to know about the featured post is which
 * one it is, to keep it out of the grid.
 */
$pagina_id = apit_noticias_pagina_id();

$por_pagina      = apit_noticias_numero( 'noticias_por_pagina', 9, 1, 24, $pagina_id );
$colunas         = apit_noticias_numero( 'noticias_colunas', 3, 2, 4, $pagina_id );
$mostrar_data    = apit_noticias_bool( 'noticias_mostrar_data', true, $pagina_id );
$mostrar_resumo  = apit_noticias_bool( 'noticias_mostrar_resumo', true, $pagina_id );
$resumo_palavras = apit_noticias_numero( 'noticias_resumo_palavras', 22, 5, 60, $pagina_id );
$ler_mais        = apit_noticias_texto( 'noticias_ler_mais', __( 'Ler notícia', 'apit' ), $pagina_id );
$texto_vazio     = apit_noticias_texto( 'noticias_vazio', __( 'De momento não há mais notícias para mostrar.', 'apit' ), $pagina_id );
$mostrar_paginas = apit_noticias_bool( 'noticias_paginacao_mostrar', true, $pagina_id );
$label_anterior  = apit_noticias_texto( 'noticias_paginacao_anterior', __( 'Anterior', 'apit' ), $pagina_id );
$label_seguinte  = apit_noticias_texto( 'noticias_paginacao_seguinte', __( 'Seguinte', 'apit' ), $pagina_id );

$pagina  = apit_noticias_pagina_atual();
$termo   = apit_noticias_categoria_ativa();
$var_pag = apit_noticias_var_pagina();

/*
 * ignore_sticky_posts because the featured slot is chosen by a field of our
 * own: left on, WordPress would also lift the sticky post to the top of the
 * grid, where it would appear out of date order.
 */
$lista = [
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'ignore_sticky_posts' => true,
];

if ( $termo ) {
	$lista['cat'] = $termo->term_id;
}

/*
 * The featured post is left out of the grid on every page — including the ones
 * where the card is hidden, so it is never met twice while paging through.
 */
$destaque = apit_noticias_destaque_da_pagina( $pagina_id );

$consulta = new WP_Query( array_merge( $lista, [
	'posts_per_page' => $por_pagina,
	'offset'         => ( $pagina - 1 ) * $por_pagina,
	'post__not_in'   => $destaque ? [ $destaque->ID ] : [],
] ) );

$total_paginas = (int) ceil( $consulta->found_posts / $por_pagina );
?>
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
	 * Shown whenever the grid comes back empty, including when the featured card
	 * above it is the only post in the category being filtered: the label and
	 * the filter are printed either way, and a heading over nothing reads as a
	 * page that failed to load.
	 */
	?>
	<p class="noticias-arquivo__vazio"><?php echo esc_html( $texto_vazio ); ?></p>
<?php endif; ?>

<?php if ( $mostrar_paginas && $total_paginas > 1 ) : ?>
	<nav class="noticias-paginacao" aria-label="<?php esc_attr_e( 'Páginas de notícias', 'apit' ); ?>">
		<?php
		/*
		 * Built on the page's own address plus the category in force, and never
		 * on the address being requested: over AJAX that address is
		 * admin-ajax.php, and a base taken from it would hand the visitor links
		 * into wp-admin.
		 */
		$base_url = apit_noticias_url_categoria( $termo ? $termo->slug : '' );

		/*
		 * paginate_links() does not build only on the base it is given: it also
		 * reads get_pagenum_link() — the address being requested — and appends
		 * whatever query arguments it finds there to every link. Over AJAX that
		 * address is admin-ajax.php, so the pagination came back carrying
		 * `action=apit_noticias&pagina=9` and pushed those into the visitor's
		 * address bar.
		 *
		 * The filter hands it the page's own clean permalink for the duration of
		 * the call, so there is nothing to append and the base decides the
		 * links. It is removed straight after: this is our pagination's
		 * business, not the site's.
		 */
		$sem_extras = function () use ( $pagina_id ) {
			return get_permalink( $pagina_id );
		};

		add_filter( 'get_pagenum_link', $sem_extras, 99 );

		echo paginate_links( [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links escapes its own output.
			'base'      => add_query_arg( $var_pag, '%#%', $base_url ),
			'format'    => '',
			'current'   => $pagina,
			'total'     => $total_paginas,
			'mid_size'  => 1,
			'prev_text' => esc_html( $label_anterior ),
			'next_text' => esc_html( $label_seguinte ),
		] );

		remove_filter( 'get_pagenum_link', $sem_extras, 99 );
		?>
	</nav>
<?php endif; ?>
