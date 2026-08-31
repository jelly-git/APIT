<?php
/**
 * Hello Elementor Child - APIT
 */

defined( 'ABSPATH' ) || exit;

define( 'APIT_CHILD_VERSION', '1.0.0' );

function apit_child_enqueue_assets() {
	wp_enqueue_style(
		'hello-elementor-parent',
		get_template_directory_uri() . '/style.css',
		[],
		APIT_CHILD_VERSION
	);

	wp_enqueue_style(
		'apit-omnes-font',
		'https://use.typekit.net/uqy3rtf.css',
		[],
		null
	);

	wp_enqueue_style(
		'font-awesome-free',
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css',
		[],
		'6.7.2'
	);

	wp_enqueue_style(
		'apit-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[ 'hello-elementor-parent', 'apit-omnes-font', 'font-awesome-free' ],
		APIT_CHILD_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'apit_child_enqueue_assets' );

function apit_child_setup() {
	register_nav_menus( [
		'top-bar'      => __( 'Barra superior', 'apit' ),
		'primary'      => __( 'Menu principal', 'apit' ),
		'footer-links' => __( 'Links do rodapé', 'apit' ),
	] );
}
add_action( 'after_setup_theme', 'apit_child_setup' );

/**
 * Social links, editable in Customizer > APIT > Redes Sociais without touching code.
 */
function apit_child_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'apit_social', [
		'title'    => __( 'APIT — Redes Sociais', 'apit' ),
		'priority' => 160,
	] );

	$networks = [
		'instagram'  => 'Instagram',
		'x_twitter'  => 'X (Twitter)',
		'facebook'   => 'Facebook',
		'linkedin'   => 'LinkedIn',
	];

	foreach ( $networks as $key => $label ) {
		$setting_id = 'apit_social_' . $key;

		$wp_customize->add_setting( $setting_id, [
			'default'           => '#',
			'sanitize_callback' => 'esc_url_raw',
		] );

		$wp_customize->add_control( $setting_id, [
			'label'   => $label,
			'section' => 'apit_social',
			'type'    => 'url',
		] );
	}
}
add_action( 'customize_register', 'apit_child_customize_register' );

function apit_child_social_url( $network ) {
	return esc_url( get_theme_mod( 'apit_social_' . $network, '#' ) );
}
