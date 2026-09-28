<?php
/**
 * Plugin Name: Área Reservada APIT
 * Description: Área reservada dos associados da APIT — login, registo e, nas fases seguintes, documentos, encontros, marcações e calendário.
 * Version:     0.18.0
 * Author:      Jelly
 * Author URI:  https://jelly.pt
 * Text Domain: jelly-area-reservada
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

// Acertar com o cabeçalho acima e com a entrada do CHANGELOG.md do repositório.
define( 'JELLY_AR_VERSION', '0.18.0' );
define( 'JELLY_AR_DIR', plugin_dir_path( __FILE__ ) );
define( 'JELLY_AR_URL', plugin_dir_url( __FILE__ ) );

/*
 * Enquanto for true, o back-office e a exportação mostram, a seguir aos dados
 * reais, os de inc/admin-exemplo.php — para a apresentação não mostrar listas
 * vazias. Os de exemplo têm ids a partir de JELLY_AR_EXEMPLO_ID, para nunca
 * darem com uma conta ou um documento verdadeiro. Passa a false quando a área
 * tiver dados seus.
 */
define( 'JELLY_AR_EXEMPLO', true );
define( 'JELLY_AR_EXEMPLO_ID', 900000 );

/*
 * Um ficheiro por módulo. Os que vêm a seguir — encontros,
 * eventos e mesas, marcações, calendário — entram aqui da mesma maneira.
 */
require_once JELLY_AR_DIR . 'inc/instalar.php';
require_once JELLY_AR_DIR . 'inc/acessos.php';
require_once JELLY_AR_DIR . 'inc/associados.php';
require_once JELLY_AR_DIR . 'inc/emails.php';
require_once JELLY_AR_DIR . 'inc/registo.php';
require_once JELLY_AR_DIR . 'inc/sessao.php';
require_once JELLY_AR_DIR . 'inc/documentos-dados.php';
require_once JELLY_AR_DIR . 'inc/eventos-dados.php';
require_once JELLY_AR_DIR . 'inc/mesas-dados.php';
require_once JELLY_AR_DIR . 'inc/historico.php';
require_once JELLY_AR_DIR . 'inc/marcacoes.php';
require_once JELLY_AR_DIR . 'inc/assets.php';
require_once JELLY_AR_DIR . 'inc/modal.php';

if ( is_admin() ) {
	require_once JELLY_AR_DIR . 'inc/admin.php';
	require_once JELLY_AR_DIR . 'inc/painel.php';
	require_once JELLY_AR_DIR . 'inc/lista.php';
	require_once JELLY_AR_DIR . 'inc/utilizadores.php';
	require_once JELLY_AR_DIR . 'inc/documentos.php';
	require_once JELLY_AR_DIR . 'inc/documentos-categorias.php';
	require_once JELLY_AR_DIR . 'inc/eventos.php';
	require_once JELLY_AR_DIR . 'inc/eventos-categorias.php';
	require_once JELLY_AR_DIR . 'inc/mesas.php';
	require_once JELLY_AR_DIR . 'inc/aprovacoes.php';
	require_once JELLY_AR_DIR . 'inc/exportar.php';
}

register_activation_hook( __FILE__, 'jelly_ar_instalar' );
