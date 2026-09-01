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
		'apit_evento_data'      => 'string', // Y-m-d, drives the day/month badge
		'apit_evento_categoria' => 'string', // pill above the card, e.g. "Encontro APIT"
		'apit_evento_etiqueta'  => 'string', // pill inside the card, e.g. "Associados"
		'apit_evento_extra'     => 'string', // optional second pill inside the card
	];

	foreach ( $fields as $key => $type ) {
		register_post_meta( 'apit_evento', $key, [
			'type'              => $type,
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
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
