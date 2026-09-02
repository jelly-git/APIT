<?php
/**
 * Custom post types and meta for the APIT site.
 */

defined( 'ABSPATH' ) || exit;

/**
 * "Evento" powers the Calendário section on the home page.
 */
function apit_register_evento_post_type() {
	register_post_type( 'apit_evento', [
		'labels' => [
			'name'               => __( 'Eventos', 'apit' ),
			'singular_name'      => __( 'Evento', 'apit' ),
			'add_new_item'       => __( 'Adicionar novo evento', 'apit' ),
			'edit_item'          => __( 'Editar evento', 'apit' ),
			'not_found'          => __( 'Nenhum evento encontrado', 'apit' ),
		],
		'public'        => true,
		'has_archive'   => true,
		'rewrite'       => [ 'slug' => 'eventos' ],
		'menu_icon'     => 'dashicons-calendar-alt',
		'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
		'show_in_rest'  => true,
	] );
}
add_action( 'init', 'apit_register_evento_post_type' );

/**
 * Event fields shown on the calendar cards. Registered with show_in_rest so the
 * block editor sidebar and WP-CLI can both read and write them.
 */
function apit_register_evento_meta() {
	$fields = [
		'apit_evento_data'       => 'string', // Ymd, the format ACF's date picker stores; drives the day/month badge
		'apit_evento_categoria'  => 'string', // pill over the top edge, e.g. "Evento APIT"
		'apit_evento_local'      => 'string', // location pill, e.g. "Lisboa"
		'apit_evento_acao_texto' => 'string', // optional button label, e.g. "Marcar reunião"
		'apit_evento_acao_url'   => 'string', // where that button points
	];

	foreach ( $fields as $key => $type ) {
		register_post_meta( 'apit_evento', $key, [
			'type'              => $type,
			'single'            => true,
			'show_in_rest'      => true,
			/*
			 * The action field holds a URL, so it needs URL sanitising. It has to
			 * be wrapped: register_post_meta passes the meta key as the second
			 * argument, which esc_url_raw would read as its $protocols list and
			 * then reject every URL.
			 */
			'sanitize_callback' => 'apit_evento_acao_url' === $key
				? function ( $value ) { return esc_url_raw( $value ); }
				: 'sanitize_text_field',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		] );
	}
}
add_action( 'init', 'apit_register_evento_meta' );

/**
 * Upcoming events for the calendar, soonest first. Events without a date sort
 * last so a half-filled draft never pushes a dated event off the row.
 */
function apit_get_proximos_eventos( $limit = 4 ) {
	return get_posts( [
		'post_type'      => 'apit_evento',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'meta_key'       => 'apit_evento_data',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	] );
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
			'cor'  => '#4a85c8',
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
