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
		'apit_evento_data'       => 'string', // Y-m-d, drives the day/month badge
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
