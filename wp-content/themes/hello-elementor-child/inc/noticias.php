<?php
/**
 * The Notícias archive page.
 *
 * Kept out of the template part for the same reason inc/hero.php is: the part
 * is the markup, and these are the decisions behind it — which post is the
 * featured one, which categories the filter offers, where the pagination
 * points. They are also the pieces most likely to be reused if a category
 * archive is ever designed.
 *
 * Everything the page shows is a field on the page itself (see
 * acf-json/group_noticias.json), so the labels, the counts and the toggles are
 * the client's to change in wp-admin. Nothing here reads a hardcoded string
 * that an editor might reasonably want to alter.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The query var the list paginates with.
 *
 * A query argument rather than the pretty /noticias/2/ form on purpose: on a
 * static page that second segment is WordPress's `page` var, which belongs to
 * <!--nextpage--> pagination, and redirect_canonical sends it back to page 1
 * when the post has no such break. A query argument has no rewrite rule to
 * depend on either, which matters because the site is deployed into a
 * subdirectory.
 */
function apit_noticias_var_pagina() {
	return 'pg';
}

/**
 * The page whose fields the section is rendered from.
 *
 * On the site it is the page being viewed. Over AJAX there is no queried
 * object — admin-ajax.php has no main query — so the script says which page it
 * is asking on behalf of, and that is checked here rather than trusted: a
 * published page, or nothing. Without the check any post ID would do, and the
 * endpoint would render the block using another post's meta.
 */
function apit_noticias_pagina_id() {
	if ( ! wp_doing_ajax() ) {
		return get_queried_object_id();
	}

	$id = isset( $_REQUEST['pagina'] ) ? (int) $_REQUEST['pagina'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public, read-only.

	if ( $id <= 0 ) {
		return 0;
	}

	$post = get_post( $id );

	return ( $post && 'page' === $post->post_type && 'publish' === $post->post_status ) ? $id : 0;
}

/**
 * The page number being viewed, never below 1.
 */
function apit_noticias_pagina_atual() {
	$valor = isset( $_GET[ apit_noticias_var_pagina() ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a page number, not an action.
		? (int) $_GET[ apit_noticias_var_pagina() ] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: 1;

	return max( 1, $valor );
}

/**
 * The category being filtered on, as a term, or null for "all".
 *
 * A slug that matches nothing resolves to null rather than to an empty result
 * set, so a mistyped or stale address shows the full list instead of an empty
 * page with a filter pill nobody can see selected.
 */
function apit_noticias_categoria_ativa() {
	$slug = isset( $_GET['categoria'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		? sanitize_title( wp_unslash( $_GET['categoria'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	if ( '' === $slug ) {
		return null;
	}

	$termo = get_term_by( 'slug', $slug, 'category' );

	return ( $termo && ! is_wp_error( $termo ) ) ? $termo : null;
}

/**
 * The address of the list filtered by one category — or unfiltered, with an
 * empty slug.
 *
 * Built from the page's own permalink rather than from the current address, so
 * following a filter drops the page number instead of asking for page 4 of a
 * category that may only have one.
 */
function apit_noticias_url_categoria( $slug ) {
	$url = get_permalink( apit_noticias_pagina_id() );

	if ( ! $url ) {
		$url = home_url( '/' );
	}

	return $slug ? add_query_arg( 'categoria', $slug, $url ) : $url;
}

/**
 * The categories the filter offers.
 *
 * The client's own selection when the field is filled, in the order it was
 * dragged into; otherwise every category that has posts. An explicitly chosen
 * term is kept even when empty — it was chosen — while the automatic list
 * leaves out categories that would filter to nothing.
 *
 * "Sem categoria" is dropped from the automatic list: it is WordPress's default
 * bucket, not an editorial section.
 */
function apit_noticias_categorias( $escolhidas ) {
	if ( $escolhidas ) {
		$termos = [];

		foreach ( (array) $escolhidas as $ref ) {
			$termo = is_object( $ref ) ? $ref : get_term( (int) $ref, 'category' );

			if ( $termo && ! is_wp_error( $termo ) ) {
				$termos[] = $termo;
			}
		}

		return $termos;
	}

	$termos = get_categories( [
		'hide_empty' => true,
		'exclude'    => [ (int) get_option( 'default_category' ) ],
	] );

	return is_wp_error( $termos ) ? [] : $termos;
}

/**
 * The featured post: the newest one an editor ticked, or simply the newest.
 *
 * The tick is a field on the post — "Notícia em destaque", in the sidebar —
 * and not WordPress's "stick to the top of the blog", which used to do this
 * job. Sticky is a blog-index feature with effects of its own on queries, and
 * an editor reading it has no way to know it means the big card on one page.
 *
 * Several posts may carry it. The newest wins, so marking today's news is
 * enough — there is nothing to unmark first, and the slot moves on by itself.
 *
 * The choice between "the marked one" and "the newest" is a field on the page,
 * because those are two different editorial policies. With "marcada" and
 * nothing marked it falls back to the newest, so the slot is never empty.
 *
 * $base is deliberately given without the category filter by the archive: the
 * featured post is the site's, and stays put while the list under it is
 * filtered. It is still a parameter because the Home passes its own base.
 */
function apit_noticia_destaque( array $base, $fonte ) {
	if ( 'marcada' === $fonte ) {
		$posts = get_posts( array_merge( $base, [
			'posts_per_page' => 1,
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'   => 'noticia_destaque',
					'value' => '1',
				],
			],
		] ) );

		if ( $posts ) {
			return $posts[0];
		}
	}

	$posts = get_posts( array_merge( $base, [ 'posts_per_page' => 1 ] ) );

	return $posts ? $posts[0] : null;
}

/*
 * The three field readers, shared by the two templates that make up the page.
 *
 * A missing field and an emptied field both fall back to the value the design
 * shows, and a number the client clears cannot turn into a query for zero
 * posts. They were closures inside the template until the featured card moved
 * out of it and a second template needed the same three.
 */

function apit_noticias_texto( $nome, $omissao, $pagina_id = null ) {
	$valor = trim( (string) apit_campo( $nome, $pagina_id ? $pagina_id : apit_noticias_pagina_id() ) );

	return '' === $valor ? $omissao : $valor;
}

function apit_noticias_bool( $nome, $omissao, $pagina_id = null ) {
	$valor = apit_campo( $nome, $pagina_id ? $pagina_id : apit_noticias_pagina_id() );

	return null === $valor ? $omissao : (bool) $valor;
}

function apit_noticias_numero( $nome, $omissao, $minimo, $maximo, $pagina_id = null ) {
	$valor = (int) apit_campo( $nome, $pagina_id ? $pagina_id : apit_noticias_pagina_id() );

	return $valor > 0 ? max( $minimo, min( $maximo, $valor ) ) : $omissao;
}

/**
 * The page's featured post, worked out once per request.
 *
 * Two templates ask for it — the shell, which draws the card, and the list,
 * which has to leave that post out of the grid — and they must agree. The
 * static cache is what makes asking twice free, and what guarantees they cannot
 * end up with different posts if something changes mid-request.
 */
function apit_noticias_destaque_da_pagina( $pagina_id ) {
	static $cache = [];

	if ( array_key_exists( $pagina_id, $cache ) ) {
		return $cache[ $pagina_id ];
	}

	if ( ! apit_noticias_bool( 'noticias_destaque_mostrar', true, $pagina_id ) ) {
		$cache[ $pagina_id ] = null;

		return null;
	}

	$cache[ $pagina_id ] = apit_noticia_destaque(
		[
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
		],
		apit_noticias_texto( 'noticias_destaque_fonte', 'marcada', $pagina_id )
	);

	return $cache[ $pagina_id ];
}

/**
 * The card's summary: the hand-written excerpt when there is one, otherwise the
 * opening of the post, trimmed to the number of words the client set.
 *
 * get_the_excerpt() is not used directly because it applies the site's own
 * length to a generated excerpt and would ignore that field.
 */
function apit_noticia_resumo( $post, $palavras ) {
	$palavras = max( 0, (int) $palavras );

	if ( ! $palavras ) {
		return '';
	}

	$texto = has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content );

	return wp_trim_words( wp_strip_all_tags( $texto ), $palavras, '…' );
}

/**
 * The endpoint the filter and the pagination call.
 *
 * It returns the same template the page renders, so there is one source for the
 * list and no second copy of the card to keep in step. Registered for logged-in
 * and logged-out visitors alike: the section is public.
 *
 * No nonce. A nonce protects against a request being made on someone's behalf
 * without their intent, and this reads published posts and writes nothing — the
 * same data the page itself serves to anyone. Adding one would also break the
 * section on any page cached for longer than the nonce's lifetime.
 */
function apit_ajax_noticias() {
	$pagina_id = apit_noticias_pagina_id();

	if ( ! $pagina_id ) {
		status_header( 400 );
		wp_die( '', '', [ 'response' => 400 ] );
	}

	get_template_part( 'template-parts/noticias/resultados' );

	wp_die();
}
add_action( 'wp_ajax_apit_noticias', 'apit_ajax_noticias' );
add_action( 'wp_ajax_nopriv_apit_noticias', 'apit_ajax_noticias' );

/**
 * The page the news live on, for a template that is not that page.
 *
 * The single article reads its settings from the Notícias page's own field
 * group — one screen in the back office for everything about the news, instead
 * of a second group somewhere else for the article view.
 */
function apit_noticias_pagina() {
	$pagina = get_page_by_path( 'noticias' );

	return $pagina ? $pagina->ID : 0;
}

/**
 * The posts to show under an article.
 *
 * Its own category first, because that is what "more like this" means, and the
 * most recent of anything to fill the row when the category has too few. A row
 * of two beside a gap looks like something failed to load.
 */
function apit_noticias_relacionadas( $post_id, $numero ) {
	$numero = max( 1, (int) $numero );
	$base   = [
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'post__not_in'        => [ (int) $post_id ],
	];

	$termos = get_the_category( $post_id );

	$posts = $termos
		? get_posts( array_merge( $base, [
			'posts_per_page' => $numero,
			'cat'            => $termos[0]->term_id,
		] ) )
		: [];

	if ( count( $posts ) >= $numero ) {
		return $posts;
	}

	// Fill up, without repeating what came from the category.
	$excluir = array_merge( $base['post__not_in'], wp_list_pluck( $posts, 'ID' ) );

	$resto = get_posts( array_merge( $base, [
		'posts_per_page' => $numero - count( $posts ),
		'post__not_in'   => $excluir,
	] ) );

	return array_merge( $posts, $resto );
}
