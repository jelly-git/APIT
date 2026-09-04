<?php
/**
 * Hello Elementor Child - APIT
 */

defined( 'ABSPATH' ) || exit;

// Keep in sync with the Version header in style.css and with CHANGELOG.md.
define( 'APIT_CHILD_VERSION', '0.22.7' );

require_once get_stylesheet_directory() . '/inc/categoria-cores.php';
require_once get_stylesheet_directory() . '/inc/post-types.php';
require_once get_stylesheet_directory() . '/inc/acf.php';
require_once get_stylesheet_directory() . '/inc/elementor.php';
require_once get_stylesheet_directory() . '/inc/shortcodes.php';

function apit_child_enqueue_assets() {
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

	/*
	 * Depends on the parent theme's own handles (reset.css, theme.css,
	 * header-footer.css) so the child stylesheet is printed after them. Without
	 * this it loaded first and lost every specificity tie — the parent's
	 * `[type="submit"], button { border-radius: 3px }` was beating our buttons.
	 */
	wp_enqueue_style(
		'apit-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[ 'apit-omnes-font', 'font-awesome-free', 'hello-elementor', 'hello-elementor-theme-style', 'hello-elementor-header-footer' ],
		APIT_CHILD_VERSION
	);

	/*
	 * Page-specific stylesheets, loaded only where they apply. Keeping them out
	 * of style.css means a change to one page cannot regress another, and the
	 * home page does not pay for CSS it never uses.
	 */
	if ( is_page( 'sobre-apit' ) ) {
		wp_enqueue_style(
			'apit-sobre-style',
			get_stylesheet_directory_uri() . '/assets/css/sobre.css',
			[ 'apit-child-style' ],
			APIT_CHILD_VERSION
		);
	}

	wp_enqueue_script(
		'apit-menu-mobile',
		get_stylesheet_directory_uri() . '/assets/js/menu-mobile.js',
		[],
		APIT_CHILD_VERSION,
		true
	);

	wp_enqueue_script(
		'apit-calendario',
		get_stylesheet_directory_uri() . '/assets/js/calendario.js',
		[],
		APIT_CHILD_VERSION,
		true
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
 * The networks the site links to, in the order they are shown.
 *
 * One list, read by the Customizer that stores the addresses and by every place
 * that draws them — the header, the mobile menu, the footer and the contacts
 * block on Sobre a APIT. The names used to be repeated in each of those, so a
 * change meant four edits and a chance of them drifting apart.
 */
function apit_redes_sociais() {
	return apply_filters( 'apit_redes_sociais', [
		'instagram' => [
			'nome'  => 'Instagram',
			'icone' => 'fa-instagram',
		],
		'x_twitter' => [
			'nome'  => 'X (Twitter)',
			'icone' => 'fa-x-twitter',
		],
		'facebook'  => [
			'nome'  => 'Facebook',
			'icone' => 'fa-facebook-f',
		],
		'youtube'   => [
			'nome'  => 'YouTube',
			'icone' => 'fa-youtube',
		],
	] );
}

/**
 * Social links, editable in Personalizar > APIT — Redes Sociais without
 * touching code.
 */
function apit_child_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'apit_social', [
		'title'    => __( 'APIT — Redes Sociais', 'apit' ),
		'priority' => 160,
	] );

	foreach ( apit_redes_sociais() as $chave => $rede ) {
		$setting_id = 'apit_social_' . $chave;

		$wp_customize->add_setting( $setting_id, [
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		] );

		$wp_customize->add_control( $setting_id, [
			'label'   => $rede['nome'],
			'section' => 'apit_social',
			'type'    => 'url',
		] );
	}
}
add_action( 'customize_register', 'apit_child_customize_register' );

/**
 * Prints the row of icon links.
 *
 * They open in a new tab, so a visitor following one does not lose the page
 * they were on. `noopener` goes with it: without it the opened page can reach
 * back into this one through window.opener.
 *
 * The label says so too — the icon alone gives a screen reader no hint that the
 * link leaves for somewhere else.
 *
 * A network with no address set is skipped rather than linked to "#", so an
 * empty field leaves no dead icon behind.
 */
function apit_redes_sociais_html() {
	foreach ( apit_redes_sociais() as $chave => $rede ) {
		$url = trim( (string) get_theme_mod( 'apit_social_' . $chave, '' ) );

		if ( '' === $url || '#' === $url ) {
			continue;
		}

		printf(
			'<a href="%s" target="_blank" rel="noopener" aria-label="%s"><i class="fa-brands %s" aria-hidden="true"></i></a>',
			esc_url( $url ),
			/* translators: %s: social network name. */
			esc_attr( sprintf( __( '%s (abre num novo separador)', 'apit' ), $rede['nome'] ) ),
			esc_attr( $rede['icone'] )
		);
	}
}
