<?php
/**
 * Internal links stored as root-relative paths follow the site into a
 * sub-directory.
 *
 * The links typed into Elementor, the ACF link fields and the menu are kept as
 * paths — "/contactos/", "/associados/todos-os-associados/" — so that they
 * survive the site changing domain without a search-replace. That only holds
 * while WordPress sits at the root of its domain. On the server it lives under
 * /apit/, and a bare "/contactos/" sent the visitor to
 * dev.jellycode.agency/contactos/, outside WordPress: Apache's own 404.
 *
 * So the finished page is corrected on its way out, rather than every stored
 * value rewritten: the data stays portable, and a link added tomorrow in the
 * Elementor panel is covered without anyone knowing about this.
 *
 * Only href and src, only paths that start with a single "/", and only the
 * ones not already under the site's path — so a link built with home_url(),
 * which already carries /apit/, is left alone, and "//cdn.example" is not
 * touched. At the root of a domain the site's path is "/" and nothing runs.
 */

/**
 * The path WordPress lives under, without a trailing slash — "/apit" on the
 * server, "" when it is at the root of the domain.
 */
function apit_caminho_do_site() {
	$caminho = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );

	return rtrim( $caminho, '/' );
}

/**
 * Prefixes the root-relative href and src attributes of an HTML string.
 */
function apit_prefixar_links( $html ) {
	$base = apit_caminho_do_site();

	if ( '' === $base || '' === $html ) {
		return $html;
	}

	$ja_prefixado = preg_quote( $base, '#' );

	return preg_replace(
		'#(\s(?:href|src)=["\'])/(?!/)(?!' . ltrim( $ja_prefixado, '/' ) . '(?:[/"\'?\#]|$))#i',
		'$1' . $base . '/',
		$html
	);
}

/*
 * The whole front-end page, buffered from the start of the template. Not the
 * admin, the editor, feeds, REST or AJAX: those either build their links with
 * home_url() already, or are not pages a visitor clicks through.
 */
function apit_iniciar_prefixo_de_links() {
	if ( is_admin() || is_feed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	// Elementor's preview runs on the front end; its editor has to see the
	// stored value as it is.
	if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( '' === apit_caminho_do_site() ) {
		return;
	}

	ob_start( 'apit_prefixar_links' );
}
add_action( 'template_redirect', 'apit_iniciar_prefixo_de_links', 0 );
