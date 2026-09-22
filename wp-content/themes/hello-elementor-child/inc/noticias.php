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
 * The featured post: the one an editor marked as sticky, or the most recent.
 *
 * The choice is a field, because "the newest one" and "the one we want up
 * there" are different editorial policies and the client should be able to
 * switch between them. With "marcada" and nothing marked it falls back to the
 * most recent, so the slot is never empty.
 *
 * $base carries the category filter, which is what makes the featured card
 * follow the filter instead of staying on a post from another section.
 */
function apit_noticia_destaque( array $base, $fonte ) {
	if ( 'marcada' === $fonte ) {
		$marcadas = get_option( 'sticky_posts' );

		if ( $marcadas ) {
			$posts = get_posts( array_merge( $base, [
				'post__in'       => $marcadas,
				'posts_per_page' => 1,
			] ) );

			if ( $posts ) {
				return $posts[0];
			}
		}
	}

	$posts = get_posts( array_merge( $base, [ 'posts_per_page' => 1 ] ) );

	return $posts ? $posts[0] : null;
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
