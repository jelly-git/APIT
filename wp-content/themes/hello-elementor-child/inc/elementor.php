<?php
/**
 * Elementor integration.
 *
 * Things the free Elementor cannot express on its own, added around it rather
 * than by replacing its widgets — so whoever edits the page keeps editing it in
 * Elementor.
 */

defined( 'ABSPATH' ) || exit;

/**
 * File extensions treated as documents to download rather than to open.
 */
function apit_extensoes_para_descarregar() {
	return apply_filters(
		'apit_extensoes_para_descarregar',
		[ 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'zip' ]
	);
}

/**
 * Makes an Elementor button that points at an uploaded document download it,
 * instead of handing it to the browser's built-in viewer.
 *
 * Elementor's free link field offers only "open in new window" and "nofollow",
 * with no way to add an attribute, so the `download` goes on here. Only files
 * under the uploads directory are touched: a browser ignores `download` on a
 * cross-origin link anyway, and an internal page link should navigate, not
 * download.
 *
 * The saved file keeps the name it has in the media library.
 */
function apit_botao_descarrega_ficheiro( $conteudo, $widget ) {
	if ( 'button' !== $widget->get_name() ) {
		return $conteudo;
	}

	$uploads = wp_get_upload_dir();
	$base    = wp_parse_url( $uploads['baseurl'], PHP_URL_PATH );

	if ( ! $base ) {
		return $conteudo;
	}

	$extensoes = apit_extensoes_para_descarregar();

	return preg_replace_callback(
		'#<a\s([^>]*?href="([^"]*)"[^>]*?)>#i',
		function ( $partes ) use ( $base, $extensoes ) {
			list( $tag, $atributos, $href ) = $partes;

			// Already carries the attribute.
			if ( preg_match( '/\sdownload(?:\s|=|$)/i', $atributos ) ) {
				return $tag;
			}

			$caminho = (string) wp_parse_url( $href, PHP_URL_PATH );

			if ( '' === $caminho || 0 !== strpos( $caminho, $base ) ) {
				return $tag;
			}

			$extensao = strtolower( pathinfo( $caminho, PATHINFO_EXTENSION ) );

			if ( ! in_array( $extensao, $extensoes, true ) ) {
				return $tag;
			}

			return '<a ' . $atributos . ' download>';
		},
		$conteudo
	);
}
add_filter( 'elementor/widget/render_content', 'apit_botao_descarrega_ficheiro', 10, 2 );

/**
 * Exposes the document icon as a custom property, so the buttons in the hero
 * can wear an uploaded image.
 *
 * Elementor's icon picker only offers Font Awesome, and the design uses an
 * uploaded PNG. Printing the URL here keeps the image editable in wp-admin
 * while the button itself stays a plain Elementor button.
 */
function apit_sobre_icones_inline() {
	/*
	 * Only Sobre a APIT: the Documentos page has its own download glyph, which
	 * ships with the theme and is drawn straight from the stylesheet — there is
	 * no field to read for it and so nothing to print here.
	 */
	if ( ! is_page( 'sobre-apit' ) ) {
		return;
	}

	$icone = apit_media_url( (string) apit_campo( 'sobre_doc_icone', get_queried_object_id() ), 'img' );

	if ( ! $icone ) {
		return;
	}

	printf(
		'<style id="apit-sobre-icones">.apit-sobre-hero{--apit-icone-doc:url("%s");}</style>' . "\n",
		esc_url( $icone )
	);
}
add_action( 'wp_head', 'apit_sobre_icones_inline', 20 );

/**
 * Renders a saved Elementor template by ID: [apit_template id="123"].
 *
 * This is what Elementor Pro's Template widget does, and what the free plugin
 * leaves out. It matters because a saved template inserted the normal way is
 * *copied* into the page: editing the template afterwards changes nothing on
 * the pages that took a copy. Pro's Global Widget is the feature that
 * propagates, and it is not in the free plugin — nor is an
 * [elementor-template] shortcode, which only registers [elementor-element].
 *
 * Rendering through Elementor's own get_builder_content_for_display means the
 * block is read live from the template on every request, so the same block on
 * the Internacionalização, Calendário and Documentos pages is edited once, in
 * Elementor, and changes on all three.
 *
 * with_css is left on so the template's own generated CSS is printed on a page
 * that Elementor would not otherwise know needs it.
 */
function apit_shortcode_template( $atts ) {
	$atts = shortcode_atts( [ 'id' => '' ], $atts, 'apit_template' );
	$id   = (int) $atts['id'];

	if ( ! $id || ! class_exists( '\Elementor\Plugin' ) ) {
		return '';
	}

	$template = get_post( $id );

	/*
	 * Only from the template library, and only if published. Without this the
	 * shortcode would render any post by ID — including a draft, or a page,
	 * which would nest a whole page inside another.
	 */
	if ( ! $template || 'elementor_library' !== $template->post_type || 'publish' !== $template->post_status ) {
		return '';
	}

	return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $id, true );
}
add_shortcode( 'apit_template', 'apit_shortcode_template' );
