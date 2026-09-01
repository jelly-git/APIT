<?php
/**
 * Shortcodes that expose the coded sections to the Elementor editor.
 *
 * Elementor free has no Posts or Form widget, so the dynamic sections (events,
 * news, newsletter) are rendered by the theme and dropped into the page as
 * shortcode widgets. Their content stays editable in wp-admin.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders a template part into a string for shortcode output.
 */
function apit_render_template_part( $slug ) {
	ob_start();
	get_template_part( $slug );
	return ob_get_clean();
}

function apit_shortcode_hero_decor() {
	return apit_render_template_part( 'template-parts/hero-decor' );
}
add_shortcode( 'apit_hero_decor', 'apit_shortcode_hero_decor' );

function apit_shortcode_calendario() {
	return apit_render_template_part( 'template-parts/calendario' );
}
add_shortcode( 'apit_calendario', 'apit_shortcode_calendario' );

function apit_shortcode_noticias() {
	return apit_render_template_part( 'template-parts/noticias' );
}
add_shortcode( 'apit_noticias', 'apit_shortcode_noticias' );

function apit_shortcode_newsletter() {
	return apit_render_template_part( 'template-parts/newsletter' );
}
add_shortcode( 'apit_newsletter', 'apit_shortcode_newsletter' );
