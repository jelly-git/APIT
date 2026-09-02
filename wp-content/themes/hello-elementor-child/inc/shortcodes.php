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

/**
 * Resolves a media reference to a URL. Accepts a full URL, a root-relative
 * path, a media library attachment ID, or a bare filename inside the theme
 * asset folder given by $pasta.
 *
 * Filenames resolve at runtime so nothing hardcodes the site URL — which
 * matters because the site has to move to another domain on deploy.
 */
function apit_media_url( $valor, $pasta ) {
	$valor = trim( (string) $valor );

	if ( '' === $valor ) {
		return '';
	}

	if ( ctype_digit( $valor ) ) {
		return (string) wp_get_attachment_url( (int) $valor );
	}

	if ( preg_match( '#^(https?:)?//#', $valor ) || '/' === $valor[0] ) {
		return $valor;
	}

	return get_stylesheet_directory_uri() . '/assets/' . $pasta . '/' . $valor;
}

/**
 * Hero background media. Both sources are optional: with only "video" it plays
 * the file, with only "imagem" it shows the still, and with both the image
 * becomes the video's poster — so an image is what loads first either way. The
 * hero's CSS gradient remains underneath as the last fallback.
 *
 * Usage: [apit_hero_media video="homepage.mp4" imagem="poster.jpg"]
 */
function apit_shortcode_hero_media( $atts ) {
	$atts = shortcode_atts(
		[
			'video'    => '',
			'imagem'   => '',
			'autoplay' => 'yes',
			'loop'     => 'yes',
			'controls' => 'no',
		],
		$atts,
		'apit_hero_media'
	);

	ob_start();
	get_template_part( 'template-parts/hero-media', null, $atts );
	return ob_get_clean();
}
add_shortcode( 'apit_hero_media', 'apit_shortcode_hero_media' );

/* -------------------------------------------------------------------------
 * Sobre a APIT
 * ---------------------------------------------------------------------- */

/**
 * The oversized "ABOUT US" wordmark behind the hero. Decorative, so it is
 * hidden from assistive technology — the real page title is the h1 beside it.
 */
function apit_shortcode_sobre_hero_decor() {
	return apit_render_template_part( 'template-parts/sobre/hero-decor' );
}
add_shortcode( 'apit_sobre_hero_decor', 'apit_shortcode_sobre_hero_decor' );

/*
 * The sections below take no attributes: their copy lives in the ACF field
 * group on the page, so it is edited in wp-admin rather than by hand-editing a
 * shortcode string inside Elementor.
 */

function apit_shortcode_sobre_pilares() {
	return apit_render_template_part( 'template-parts/sobre/pilares' );
}
add_shortcode( 'apit_sobre_pilares', 'apit_shortcode_sobre_pilares' );

function apit_shortcode_equipa() {
	return apit_render_template_part( 'template-parts/sobre/equipa' );
}
add_shortcode( 'apit_equipa', 'apit_shortcode_equipa' );

/**
 * Associados. The client dropped the logo strip, so this is the label and the
 * link through to the full list.
 */
function apit_shortcode_associados() {
	return apit_render_template_part( 'template-parts/sobre/associados' );
}
add_shortcode( 'apit_associados', 'apit_shortcode_associados' );

function apit_shortcode_orgaos_sociais() {
	return apit_render_template_part( 'template-parts/sobre/orgaos-sociais' );
}
add_shortcode( 'apit_orgaos_sociais', 'apit_shortcode_orgaos_sociais' );

function apit_shortcode_internacionalizacao() {
	return apit_render_template_part( 'template-parts/sobre/internacionalizacao' );
}
add_shortcode( 'apit_internacionalizacao', 'apit_shortcode_internacionalizacao' );

/**
 * Contactos, with the address block beside a live Google Maps embed. The embed
 * URL is built from the address itself and needs no API key, which keeps the
 * map working on any domain the site moves to.
 */
function apit_shortcode_contactos() {
	return apit_render_template_part( 'template-parts/sobre/contactos' );
}
add_shortcode( 'apit_contactos', 'apit_shortcode_contactos' );
