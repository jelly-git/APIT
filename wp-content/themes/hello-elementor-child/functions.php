<?php
/**
 * Hello Elementor Child - APIT
 */

defined( 'ABSPATH' ) || exit;

// Keep in sync with the Version header in style.css and with CHANGELOG.md.
define( 'APIT_CHILD_VERSION', '0.33.4' );

require_once get_stylesheet_directory() . '/inc/categoria-cores.php';
require_once get_stylesheet_directory() . '/inc/post-types.php';
require_once get_stylesheet_directory() . '/inc/acf.php';
require_once get_stylesheet_directory() . '/inc/elementor.php';
require_once get_stylesheet_directory() . '/inc/hero.php';
require_once get_stylesheet_directory() . '/inc/links.php';
require_once get_stylesheet_directory() . '/inc/noticias.php';
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

	/*
	 * The pages built from the same set of parts share one stylesheet, and so
	 * does the single article, which wears the same hero: one layout with a
	 * gradient each, and the blocks that repeat between them are the same
	 * markup. Splitting it per page would mean six copies of everything but the
	 * gradient.
	 */
	if ( is_page( apit_paginas_com_folha_comum() ) || is_singular( 'post' ) ) {
		wp_enqueue_style(
			'apit-paginas-style',
			get_stylesheet_directory_uri() . '/assets/css/paginas.css',
			[ 'apit-child-style' ],
			APIT_CHILD_VERSION
		);
	}

	/*
	 * Notícias takes the shared hero above and adds this: the filter, the grid,
	 * the pagination and the article view, which exist nowhere else. It depends
	 * on the shared sheet so its own rules are printed after it — the card is
	 * the Home's card with pieces added, and those additions have to win.
	 *
	 * The article carries it too: the row of other news under it is the same
	 * card, and the body's typography lives in the same file as everything else
	 * about the news.
	 */
	if ( is_page( 'noticias' ) || is_singular( 'post' ) ) {
		wp_enqueue_style(
			'apit-noticias-style',
			get_stylesheet_directory_uri() . '/assets/css/noticias.css',
			[ 'apit-paginas-style' ],
			APIT_CHILD_VERSION
		);

		/*
		 * The filter and the pagination without a page reload. It only enhances
		 * links the page already prints, so it goes in the footer and nothing
		 * waits for it — with the script absent or broken, the same links
		 * navigate and the server renders the same list.
		 */
		wp_enqueue_script(
			'apit-noticias',
			get_stylesheet_directory_uri() . '/assets/js/noticias.js',
			[],
			APIT_CHILD_VERSION,
			true
		);
	}

	/*
	 * The Internacionalização band is shown on several pages and under every
	 * article, so its rules live in their own file rather than in the stylesheet
	 * of any one of them — and rather than in style.css, which every other page
	 * would then carry for nothing.
	 *
	 * Which pages get it is read from the page itself and not from a list kept
	 * here. The list was four slugs, and the Media Kit — which drops the same
	 * shortcode in — came out with the band unstyled: no background, the button
	 * a ghost, the Watch Portugal lockup barely there. Dropping the shortcode in
	 * has to be enough, or the next page pays the same price.
	 *
	 * The article keeps its own clause: there the band comes from the saved
	 * template that closes the piece, not from anything stored on the post.
	 */
	if ( apit_pagina_usa_shortcode( 'apit_internacionalizacao' ) || is_singular( 'post' ) ) {
		wp_enqueue_style(
			'apit-inter-style',
			get_stylesheet_directory_uri() . '/assets/css/internacionalizacao.css',
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

	/*
	 * Only the two pages with a hero carry the slider. It does nothing on a
	 * single-image gallery — the markup then has no data-restantes for it to
	 * find — but there is no reason to ship it to a page with no hero at all.
	 */
	if ( is_front_page() || is_page( 'sobre-apit' ) ) {
		wp_enqueue_script(
			'apit-hero-slider',
			get_stylesheet_directory_uri() . '/assets/js/hero-slider.js',
			[],
			APIT_CHILD_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'apit_child_enqueue_assets' );

/**
 * Whether the page being served uses a shortcode, so its stylesheet can be
 * decided by the page instead of by a list kept in this file.
 *
 * Two places to look. `post_content` is where WordPress keeps a shortcode
 * written in the editor — and, on an Elementor page, a plain-text copy that is
 * only rewritten when someone saves in the editor, so it goes stale. The
 * shortcode a page really renders is the one in `_elementor_data`, which is why
 * that is checked too and why a plain `strpos` is enough there: the field is
 * JSON, and `has_shortcode()` would have to parse the whole tree to find what a
 * substring finds straight away.
 *
 * Runs on wp_enqueue_scripts, which fires inside wp_head with the main query
 * already resolved — early enough for the stylesheet to be printed in the head
 * rather than after the band has been painted without it.
 */
function apit_pagina_usa_shortcode( $etiqueta ) {
	$id = get_queried_object_id();

	if ( ! $id ) {
		return false;
	}

	$post = get_post( $id );

	if ( $post && has_shortcode( (string) $post->post_content, $etiqueta ) ) {
		return true;
	}

	$dados = get_post_meta( $id, '_elementor_data', true );

	return is_string( $dados ) && false !== strpos( $dados, '[' . $etiqueta );
}

/**
 * The pages that share assets/css/paginas.css.
 *
 * One list, read by the enqueue and by the inline icon rule, so another page
 * joining them is a single edit instead of a hunt through functions.php.
 */
function apit_paginas_com_folha_comum() {
	return apply_filters( 'apit_paginas_com_folha_comum', [
		'associados',
		'todos-os-associados',
		'internacionalizacao',
		'calendario',
		'documentos',
		'media-kit',
		'noticias',
	] );
}

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
