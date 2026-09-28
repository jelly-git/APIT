<?php
/**
 * O Calendário do back-office por Ajax: as setas do mês, o Hoje e o filtro do
 * evento trazem o mês pedido sem recarregar a página.
 *
 * Devolve a própria página (templates/admin/calendario.php), desenhada com o
 * mês e o evento do pedido: o JavaScript (assets/js/admin.js) tira dela a zona
 * data-jar-calendario-zona e troca-a pela que está no ecrã. Assim o Calendário
 * desenha-se num sítio só, e a página e o Ajax dão o mesmo.
 */

defined( 'ABSPATH' ) || exit;

function jelly_ar_calendario_ajax() {
	if ( ! jelly_ar_e_administrador() ) {
		wp_send_json_error( null, 403 );
	}

	check_ajax_referer( 'jelly_ar_calendario' );

	// A template lê o mês e o evento de $_GET, que o pedido traz.
	ob_start();
	include JELLY_AR_DIR . 'templates/admin/calendario.php';

	wp_send_json_success( [ 'html' => ob_get_clean() ] );
}
add_action( 'wp_ajax_jelly_ar_calendario', 'jelly_ar_calendario_ajax' );
