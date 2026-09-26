<?php
/**
 * Exportações em CSV:
 * - a lista de utilizadores, tal como está filtrada, pesquisada e ordenada no
 *   ecrã, mas inteira — sem a paginação;
 * - o histórico de acessos de um utilizador, a partir do perfil.
 *
 * Os ficheiros abrem direito no Excel em português: separador ";" e BOM UTF-8,
 * sem o qual os acentos chegam partidos.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abre a resposta como um CSV para descarregar e devolve o ficheiro onde
 * escrever, já com o BOM e a linha dos títulos.
 */
function jelly_ar_csv_comecar( $nome, $titulos ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $nome . '-' . gmdate( 'Y-m-d' ) . '.csv"' );

	$saida = fopen( 'php://output', 'w' );
	fwrite( $saida, "\xEF\xBB\xBF" );
	fputcsv( $saida, $titulos, ';' );

	return $saida;
}

function jelly_ar_exportar_pode() {
	// Os dados dos associados só saem para administradores.
	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}
}

/* ---------- Utilizadores ---------- */

/**
 * O endereço da exportação, com o filtro, a pesquisa e a ordem da lista.
 */
function jelly_ar_url_exportar_utilizadores( $pedido ) {
	$args = array_filter( array_merge( [ 'action' => 'jelly_ar_exportar_utilizadores' ], $pedido ), 'strlen' );

	return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), 'jelly_ar_exportar_utilizadores' );
}

function jelly_ar_exportar_utilizadores() {
	jelly_ar_exportar_pode();
	check_admin_referer( 'jelly_ar_exportar_utilizadores' );

	$pedido = jelly_ar_utilizadores_lista()->pedido();
	$lista  = jelly_ar_utilizadores_filtrar( jelly_ar_utilizadores_todos(), $pedido )['lista'];
	$nomes  = [
		'pendente'  => __( 'Por aprovar', 'jelly-area-reservada' ),
		'ativo'     => __( 'Ativo', 'jelly-area-reservada' ),
		'suspenso'  => __( 'Suspenso', 'jelly-area-reservada' ),
		'rejeitado' => __( 'Rejeitado', 'jelly-area-reservada' ),
	];

	$saida = jelly_ar_csv_comecar(
		'utilizadores-area-reservada' . ( $pedido['estado'] ? '-' . $pedido['estado'] : '' ),
		[ 'Nome', 'Apelido', 'E-mail', 'Telefone', 'Empresa', 'Estado', 'Registo', 'Aprovado em', 'Aprovado por', 'Último acesso', 'Acessos' ]
	);

	foreach ( $lista as $u ) {
		fputcsv( $saida, [
			$u['nome'],
			$u['apelido'],
			$u['email'],
			$u['telefone'],
			$u['empresa'],
			$nomes[ $u['estado'] ] ?? $u['estado'],
			$u['registo'],
			$u['aprovado'],
			$u['aprovado_por'],
			$u['ultimo'],
			$u['acessos'],
		], ';' );
	}

	fclose( $saida );
	exit;
}
add_action( 'admin_post_jelly_ar_exportar_utilizadores', 'jelly_ar_exportar_utilizadores' );

/* ---------- Descargas de um documento ---------- */

function jelly_ar_url_exportar_descargas( $documento ) {
	return wp_nonce_url(
		add_query_arg( [ 'action' => 'jelly_ar_exportar_descargas', 'documento' => (int) $documento ], admin_url( 'admin-post.php' ) ),
		'jelly_ar_exportar_descargas'
	);
}

function jelly_ar_exportar_descargas() {
	jelly_ar_exportar_pode();
	check_admin_referer( 'jelly_ar_exportar_descargas' );

	$documento = isset( $_GET['documento'] ) ? absint( $_GET['documento'] ) : 0;
	$doc       = jelly_ar_documento( $documento );

	if ( ! $doc ) {
		wp_die( esc_html__( 'Esse documento não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	$saida = jelly_ar_csv_comecar( 'descargas-' . sanitize_title( $doc['titulo'] ), [ 'Data e hora', 'Nome', 'E-mail', 'Empresa' ] );

	foreach ( jelly_ar_descargas_de( $doc ) as $d ) {
		fputcsv( $saida, [ $d['quando'], $d['nome'], $d['email'], $d['empresa'] ], ';' );
	}

	fclose( $saida );
	exit;
}
add_action( 'admin_post_jelly_ar_exportar_descargas', 'jelly_ar_exportar_descargas' );

/* ---------- Acessos ---------- */

function jelly_ar_url_exportar_acessos( $utilizador = 0 ) {
	$args = [ 'action' => 'jelly_ar_exportar_acessos' ];

	if ( $utilizador ) {
		$args['utilizador'] = (int) $utilizador;
	}

	return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), 'jelly_ar_exportar_acessos' );
}

/**
 * Os acessos, do mais recente para o mais antigo, cada um com
 * quando / nome / email / ip / dispositivo.
 */
function jelly_ar_obter_acessos( $utilizador = 0 ) {
	/*
	 * Os de exemplo têm ids a partir de 900000 (inc/admin-exemplo.php): esses
	 * leem-se dos exemplos. Os outros, e todos juntos, da tabela — com os de
	 * exemplo a seguir, enquanto os houver.
	 */
	$exemplo = [];

	if ( JELLY_AR_EXEMPLO && ( ! $utilizador || $utilizador >= JELLY_AR_EXEMPLO_ID ) ) {
		require_once JELLY_AR_DIR . 'inc/admin-exemplo.php';

		$exemplo = jelly_ar_exemplo_acessos( $utilizador );

		if ( $utilizador ) {
			return $exemplo;
		}
	}

	return array_merge( jelly_ar_acessos_reais( $utilizador ), $exemplo );
}

function jelly_ar_acessos_reais( $utilizador = 0 ) {
	global $wpdb;

	$tabela = jelly_ar_tabela_acessos();
	$onde   = $utilizador ? $wpdb->prepare( 'WHERE a.user_id = %d', $utilizador ) : '';

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
	$linhas = $wpdb->get_results( "SELECT a.criado_em, a.ip, a.user_agent, u.display_name, u.user_email FROM {$tabela} a LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id {$onde} ORDER BY a.criado_em DESC" );

	return array_map( function ( $l ) {
		return [
			'quando'      => get_date_from_gmt( $l->criado_em, 'd/m/Y H:i' ),
			'nome'        => $l->display_name,
			'email'       => $l->user_email,
			'ip'          => $l->ip,
			'dispositivo' => $l->user_agent,
		];
	}, $linhas );
}

function jelly_ar_exportar_acessos() {
	jelly_ar_exportar_pode();
	check_admin_referer( 'jelly_ar_exportar_acessos' );

	$utilizador = isset( $_GET['utilizador'] ) ? absint( $_GET['utilizador'] ) : 0;
	$saida      = jelly_ar_csv_comecar(
		'acessos-area-reservada' . ( $utilizador ? '-' . $utilizador : '' ),
		[ 'Data e hora', 'Nome', 'E-mail', 'IP', 'Dispositivo' ]
	);

	foreach ( jelly_ar_obter_acessos( $utilizador ) as $a ) {
		fputcsv( $saida, [ $a['quando'], $a['nome'], $a['email'], $a['ip'], $a['dispositivo'] ], ';' );
	}

	fclose( $saida );
	exit;
}
add_action( 'admin_post_jelly_ar_exportar_acessos', 'jelly_ar_exportar_acessos' );
