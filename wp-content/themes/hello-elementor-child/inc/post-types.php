<?php
/**
 * Custom post types and meta for the APIT site.
 */

defined( 'ABSPATH' ) || exit;

/*
 * Events are not a post type any more. They and their categories live in the
 * Área Reservada plugin's own tables and are managed in its back-office; the
 * calendar reads them from there (jelly_ar_eventos_calendario(), see
 * template-parts/calendario.php) and so does the search.
 */

/* -------------------------------------------------------------------------
 * Documentos
 * ---------------------------------------------------------------------- */

/**
 * The document library on the Documentos page — anuários, brochuras, estudos.
 *
 * One post type with a taxonomy for the three areas, rather than three types:
 * the card is the same in all of them, and a fourth area is then a term the
 * client adds instead of a developer's release.
 *
 * The cover is the featured image and the file is an ACF field. No archive and
 * no single view: a document is a cover and a download, and there is nothing
 * to put on a page of its own.
 */
function apit_register_documento_post_type() {
	register_post_type( 'apit_documento', [
		'labels' => [
			'name'          => __( 'Documentos', 'apit' ),
			'singular_name' => __( 'Documento', 'apit' ),
			'menu_name'     => __( 'Documentos', 'apit' ),
			'add_new_item'  => __( 'Adicionar documento', 'apit' ),
			'edit_item'     => __( 'Editar documento', 'apit' ),
			'not_found'     => __( 'Nenhum documento encontrado', 'apit' ),
		],
		'public'             => false,
		'publicly_queryable' => false,
		'has_archive'        => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'menu_icon'          => 'dashicons-media-document',
		'supports'           => [ 'title', 'thumbnail', 'page-attributes' ],
		/*
		 * The classic editor: every field on a document is either the title,
		 * the featured image or an ACF field, so the block editor would only
		 * offer an empty canvas to fill in by mistake.
		 */
		'show_in_rest'       => false,
	] );
}
add_action( 'init', 'apit_register_documento_post_type' );

/**
 * The three areas the Documentos page groups by — Anuários, Brochuras,
 * Estudos, as named in the design.
 *
 * Hierarchical so the box is a checklist and not a free-text tag field: the
 * areas are a fixed short list, and a typo would silently create a fourth.
 */
function apit_register_area_documento() {
	register_taxonomy( 'apit_area_documento', [ 'apit_documento' ], [
		'labels' => [
			'name'          => __( 'Áreas de documento', 'apit' ),
			'singular_name' => __( 'Área', 'apit' ),
			'menu_name'     => __( 'Áreas', 'apit' ),
			'add_new_item'  => __( 'Adicionar área', 'apit' ),
			'edit_item'     => __( 'Editar área', 'apit' ),
		],
		'public'            => false,
		'show_ui'           => true,
		'show_admin_column' => true,
		'hierarchical'      => true,
		'show_in_rest'      => false,
	] );
}
add_action( 'init', 'apit_register_area_documento' );

/**
 * The documents of one area, in the order set on each document.
 *
 * menu_order, not the date: the design shows a deliberate sequence of covers,
 * and a document's publication date has nothing to do with where it belongs in
 * the row.
 */
function apit_get_documentos( $area, $limit = -1 ) {
	$termo = is_numeric( $area ) ? (int) $area : $area;

	return get_posts( [
		'post_type'      => 'apit_documento',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => [
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		],
		'tax_query'      => [
			[
				'taxonomy' => 'apit_area_documento',
				'field'    => is_numeric( $termo ) ? 'term_id' : 'slug',
				'terms'    => $termo,
			],
		],
	] );
}

/**
 * The areas that actually have documents, in the order they are set in
 * wp-admin — so the page's three groups come from the content, not from a list
 * repeated in a template.
 */
function apit_get_areas_documento() {
	$termos = get_terms( [
		'taxonomy'   => 'apit_area_documento',
		'hide_empty' => true,
		'orderby'    => 'term_order',
	] );

	return is_wp_error( $termos ) ? [] : $termos;
}

/* -------------------------------------------------------------------------
 * Sobre a APIT
 * ---------------------------------------------------------------------- */

/**
 * "Equipa" (the Equipa APIT row) and "Órgão Social" (Direção, Assembleia
 * Geral, Conselho Fiscal). Both are plain lists the client extends from
 * wp-admin, which is what keeps the sections growing without touching code.
 *
 * Neither is public: they only ever render inside the Sobre a APIT page, so a
 * single-post URL for "Susana Gato" would be a dead end for visitors and for
 * search engines.
 */
function apit_register_sobre_post_types() {
	register_post_type( 'apit_equipa', [
		'labels' => [
			'name'          => __( 'Equipa', 'apit' ),
			'singular_name' => __( 'Membro da equipa', 'apit' ),
			'add_new_item'  => __( 'Adicionar membro da equipa', 'apit' ),
			'edit_item'     => __( 'Editar membro da equipa', 'apit' ),
			'not_found'     => __( 'Nenhum membro encontrado', 'apit' ),
		],
		'public'              => false,
		'show_ui'             => true,
		'exclude_from_search' => true,
		'menu_icon'           => 'dashicons-groups',
		'supports'            => [ 'title', 'thumbnail', 'page-attributes' ],
		// Classic editor: neither has a content body, so the block canvas would just
		// be an empty frame above the fields that matter.
		'show_in_rest'        => false,
	] );

	register_post_type( 'apit_orgao_social', [
		'labels' => [
			'name'          => __( 'Órgãos Sociais', 'apit' ),
			'singular_name' => __( 'Órgão social', 'apit' ),
			'add_new_item'  => __( 'Adicionar membro de órgão social', 'apit' ),
			'edit_item'     => __( 'Editar membro de órgão social', 'apit' ),
			'not_found'     => __( 'Nenhum membro encontrado', 'apit' ),
		],
		'public'              => false,
		'show_ui'             => true,
		'exclude_from_search' => true,
		'menu_icon'           => 'dashicons-awards',
		'supports'            => [ 'title', 'page-attributes' ],
		'show_in_rest'        => false,
	] );
}
add_action( 'init', 'apit_register_sobre_post_types' );

function apit_register_sobre_meta() {
	$fields = [
		'apit_equipa'       => [ 'apit_equipa_cargo' ],
		'apit_orgao_social' => [ 'apit_orgao_social_orgao', 'apit_orgao_social_cargo' ],
	];

	foreach ( $fields as $post_type => $keys ) {
		foreach ( $keys as $key ) {
			register_post_meta( $post_type, $key, [
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			] );
		}
	}
}
add_action( 'init', 'apit_register_sobre_meta' );

/**
 * The three órgãos, in the order the design lists them, each with the colour
 * its name is set in. Filterable so the client can be given a fourth órgão
 * without this file changing.
 */
function apit_get_orgaos() {
	return apply_filters( 'apit_orgaos', [
		'direcao'         => [
			'nome' => __( 'Direção', 'apit' ),
			'cor'  => '#8048a6',
		],
		'assembleia-geral' => [
			'nome' => __( 'Assembleia Geral', 'apit' ),
			'cor'  => '#4a85c8',
		],
		'conselho-fiscal' => [
			'nome' => __( 'Conselho Fiscal', 'apit' ),
			'cor'  => '#f41892',
		],
	] );
}

/**
 * Team members in the order set by the Order field in wp-admin.
 */
function apit_get_equipa( $limit = -1 ) {
	return get_posts( [
		'post_type'      => 'apit_equipa',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
	] );
}

/**
 * Órgão members keyed by órgão slug, so the template can walk the órgãos in
 * their designed order and skip any that has no members yet.
 */
function apit_get_membros_por_orgao() {
	$membros = get_posts( [
		'post_type'      => 'apit_orgao_social',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
	] );

	$agrupados = [];

	foreach ( $membros as $membro ) {
		$orgao = get_post_meta( $membro->ID, 'apit_orgao_social_orgao', true );

		if ( $orgao ) {
			$agrupados[ $orgao ][] = $membro;
		}
	}

	return $agrupados;
}
