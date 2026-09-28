<?php
/**
 * A área privada: a página de cada associado, com os dados dele.
 *
 * Vive num endereço do plugin, /area-reservada/, e não numa página do
 * WordPress: não depende de nada na base de dados, e por isso sobe com o
 * código. A página é desenhada pelo plugin (templates/area-privada.php), com
 * o cabeçalho e o rodapé do tema.
 *
 * Cada um vê só o que é seu: a página lê sempre a conta com sessão
 * (get_current_user_id()), nunca um id vindo do endereço.
 *
 * - Sem sessão: vai para a página inicial com o pop-up de entrar aberto; ao
 *   entrar, o login traz de volta aqui (jelly_ar_destino_associado()).
 * - Com sessão, mas sem acesso (um associado suspenso, uma conta que não é da
 *   AR): vai para a página inicial.
 *
 * Com sessão, os botões "Área Reservada" do tema (apit_area_reservada_url())
 * passam a trazer aqui, em vez de abrirem o pop-up.
 */

defined( 'ABSPATH' ) || exit;

// O endereço, sem barras: /area-reservada/.
const JELLY_AR_AREA_CAMINHO = 'area-reservada';

function jelly_ar_area_url() {
	return home_url( '/' . JELLY_AR_AREA_CAMINHO . '/' );
}

/**
 * Quem pode ver a área privada: um associado ativo, ou a equipa.
 */
function jelly_ar_area_tem_acesso( $user = null ) {
	$user = $user instanceof WP_User ? $user : wp_get_current_user();

	if ( ! $user->exists() ) {
		return false;
	}

	if ( jelly_ar_e_administrador( $user ) ) {
		return true;
	}

	return jelly_ar_e_associado( $user ) && 'ativo' === jelly_ar_associado_estado( $user->ID );
}

/* ---------- O endereço ---------- */

function jelly_ar_area_regra() {
	add_rewrite_rule( '^' . JELLY_AR_AREA_CAMINHO . '/?$', 'index.php?jelly_ar_area=1', 'top' );

	// A regra nova só vale depois de as regras se refazerem: uma vez por versão do plugin.
	if ( get_option( 'jelly_ar_regras' ) !== JELLY_AR_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'jelly_ar_regras', JELLY_AR_VERSION, true );
	}
}
add_action( 'init', 'jelly_ar_area_regra' );

function jelly_ar_area_query_vars( $vars ) {
	$vars[] = 'jelly_ar_area';

	return $vars;
}
add_filter( 'query_vars', 'jelly_ar_area_query_vars' );

function jelly_ar_e_area() {
	return (bool) get_query_var( 'jelly_ar_area' );
}

/* ---------- A página ---------- */

function jelly_ar_area_mostrar() {
	if ( ! jelly_ar_e_area() ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/#area-reservada' ) );
		exit;
	}

	if ( ! jelly_ar_area_tem_acesso() ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}

	// Dados de uma pessoa: nada de caches pelo caminho.
	nocache_headers();
	status_header( 200 );

	include JELLY_AR_DIR . 'templates/area-privada.php';
	exit;
}
add_action( 'template_redirect', 'jelly_ar_area_mostrar' );

/**
 * A página não é um post: sem isto, o WordPress dava-lhe o título da página
 * inicial e a classe de 404.
 */
function jelly_ar_area_titulo( $partes ) {
	if ( jelly_ar_e_area() ) {
		$partes['title'] = __( 'Área Reservada', 'jelly-area-reservada' );
	}

	return $partes;
}
add_filter( 'document_title_parts', 'jelly_ar_area_titulo' );

function jelly_ar_area_body_class( $classes ) {
	if ( jelly_ar_e_area() ) {
		$classes   = array_diff( $classes, [ 'error404', 'home', 'blog' ] );
		$classes[] = 'apit-area-privada';
	}

	return $classes;
}
add_filter( 'body_class', 'jelly_ar_area_body_class' );

/**
 * O hero da página é o das páginas do tema: a folha dele só se carrega nas
 * páginas que o tema conhece, e esta não é uma delas.
 */
function jelly_ar_area_estilos() {
	if ( ! jelly_ar_e_area() ) {
		return;
	}

	$folha = get_stylesheet_directory() . '/assets/css/paginas.css';

	if ( ! wp_style_is( 'apit-paginas-style' ) && file_exists( $folha ) ) {
		wp_enqueue_style(
			'apit-paginas-style',
			get_stylesheet_directory_uri() . '/assets/css/paginas.css',
			wp_style_is( 'apit-child-style', 'registered' ) ? [ 'apit-child-style' ] : [],
			defined( 'APIT_CHILD_VERSION' ) ? APIT_CHILD_VERSION : JELLY_AR_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'jelly_ar_area_estilos', 15 );

/* ---------- Ligar o login e os botões à área ---------- */

// Ao entrar (e ao tentar abrir o wp-admin), o associado vem para a sua área.
add_filter( 'jelly_ar_destino_associado', 'jelly_ar_area_url' );

// Com sessão, os botões "Área Reservada" do tema levam à área; sem sessão, abrem o pop-up.
function jelly_ar_area_botoes( $url ) {
	return jelly_ar_area_tem_acesso() ? jelly_ar_area_url() : $url;
}
add_filter( 'apit_area_reservada_url', 'jelly_ar_area_botoes' );
