<?php
/**
 * O pop-up do Login e do Registo, impresso no fim de cada página.
 *
 * Abre-se com qualquer link que acabe em #area-reservada (Login) ou
 * #area-reservada-registo (Registo), venha ele do cabeçalho, de um botão do
 * Elementor ou de um campo ACF. É esse o único contrato com o resto do site: o
 * tema escreve a âncora, o plugin trata do que ela abre.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inclui um template do plugin, deixando o tema substituí-lo.
 *
 * Um ficheiro em <tema>/area-reservada/<nome>.php ganha ao do plugin, para que
 * um ajuste de desenho possa ficar do lado do tema sem tocar aqui.
 */
function jelly_ar_template( $nome, $args = [] ) {
	$ficheiro = locate_template( 'area-reservada/' . $nome . '.php' );

	if ( ! $ficheiro ) {
		$ficheiro = JELLY_AR_DIR . 'templates/' . $nome . '.php';
	}

	if ( file_exists( $ficheiro ) ) {
		load_template( $ficheiro, false, $args );
	}
}

/**
 * O logótipo branco do tema, se o tema for este. Sem ele, o painel mostra o
 * nome do site em texto.
 */
function jelly_ar_logo_url() {
	$logo = get_stylesheet_directory() . '/assets/img/logo-branco.svg';

	return file_exists( $logo ) ? get_stylesheet_directory_uri() . '/assets/img/logo-branco.svg' : '';
}

function jelly_ar_imprimir_modal() {
	// A pré-visualização do Elementor corre no front-end; lá o pop-up só estorva.
	if ( is_admin() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	jelly_ar_template( 'modal' );
}
add_action( 'wp_footer', 'jelly_ar_imprimir_modal', 5 );

/**
 * A versão de um ficheiro do plugin para o endereço (?ver=): a do plugin e a
 * hora da última alteração. Com só a do plugin, um ficheiro mudado sem mudar
 * a versão continuava com o mesmo endereço, e o browser servia o antigo da
 * cache — foi assim que um registo mostrou "Pedido enviado" com o JavaScript
 * de demonstração, sem nada chegar ao servidor.
 */
function jelly_ar_versao_ficheiro( $relativo ) {
	$caminho = JELLY_AR_DIR . $relativo;

	return JELLY_AR_VERSION . ( file_exists( $caminho ) ? '.' . filemtime( $caminho ) : '' );
}
