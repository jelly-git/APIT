<?php
/**
 * O histórico: quem fez o quê nas marcações, nas mesas e nos horários de um
 * evento, na tabela jelly_ar_registo (inc/instalar.php).
 *
 * Grava-se em cada ação, dentro das funções que a fazem (inc/mesas-dados.php e
 * inc/mesas.php), para nenhum caminho ficar de fora — o back-office, o pop-up
 * do site ou uma decisão nas Aprovações. O autor é quem tem a sessão: a equipa,
 * ou o associado que pediu.
 *
 * Mostra-se no back-office: na janela de cada horário da grelha (o caminho
 * daquela marcação) e no separador Histórico de cada evento.
 */

defined( 'ABSPATH' ) || exit;

/**
 * O que cada ação quer dizer, para mostrar.
 */
function jelly_ar_historico_acoes() {
	return [
		'marcacao-criada'    => __( 'Marcação feita pela equipa', 'jelly-area-reservada' ),
		'marcacao-pedida'    => __( 'Marcação pedida pelo associado', 'jelly-area-reservada' ),
		'marcacao-mudada'    => __( 'Marcação mudada', 'jelly-area-reservada' ),
		'marcacao-removida'  => __( 'Marcação removida', 'jelly-area-reservada' ),
		'marcacao-aprovada'  => __( 'Pedido aprovado', 'jelly-area-reservada' ),
		'marcacao-rejeitada' => __( 'Pedido rejeitado', 'jelly-area-reservada' ),
		'horarios-gravados'  => __( 'Horários gravados', 'jelly-area-reservada' ),
		'mesa-criada'        => __( 'Mesa criada', 'jelly-area-reservada' ),
		'mesa-alterada'      => __( 'Mesa alterada', 'jelly-area-reservada' ),
		'mesa-apagada'       => __( 'Mesa apagada', 'jelly-area-reservada' ),
	];
}

/**
 * Grava uma ação.
 *
 * @param string $acao Uma das chaves de jelly_ar_historico_acoes().
 * @param array  $args evento_id, marcacao_id, associado_id, resumo; autor_id,
 *                     por omissão quem tem a sessão.
 */
function jelly_ar_historico_gravar( $acao, $args ) {
	global $wpdb;

	$wpdb->insert( // phpcs:ignore WordPress.DB
		jelly_ar_tabela( 'registo' ),
		[
			'criado_em'    => current_time( 'mysql', true ),
			'autor_id'     => isset( $args['autor_id'] ) ? (int) $args['autor_id'] : get_current_user_id(),
			'acao'         => $acao,
			'evento_id'    => isset( $args['evento_id'] ) ? (int) $args['evento_id'] : null,
			'marcacao_id'  => isset( $args['marcacao_id'] ) ? (int) $args['marcacao_id'] : null,
			'associado_id' => isset( $args['associado_id'] ) ? (int) $args['associado_id'] : null,
			'resumo'       => (string) ( $args['resumo'] ?? '' ),
		]
	);
}

/**
 * "Ana Silva · Mesa 1 · qua, 7 out · 10:00": a marcação por extenso, no
 * momento em que se grava.
 */
function jelly_ar_historico_marcacao( $user_id, $mesa, $dia, $hora ) {
	$perfil = jelly_ar_associado( $user_id );
	$nome   = $perfil ? trim( $perfil->nome . ' ' . $perfil->apelido ) : '#' . (int) $user_id;
	$d      = DateTime::createFromFormat( '!Y-m-d', $dia );

	return $nome . ' · ' . ( is_array( $mesa ) ? $mesa['nome'] : $mesa ) . ' · ' . ( $d ? jelly_ar_data( 'D, j M', $d->getTimestamp() ) : $dia ) . ' · ' . $hora;
}

/**
 * O histórico de um evento, do mais recente para o mais antigo, com o nome de
 * quem fez cada ação.
 */
function jelly_ar_historico_do_evento( $evento_id, $limite = 200 ) {
	global $wpdb;

	$r = jelly_ar_tabela( 'registo' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( $wpdb->prepare( "SELECT r.*, u.display_name FROM {$r} r LEFT JOIN {$wpdb->users} u ON u.ID = r.autor_id WHERE r.evento_id = %d ORDER BY r.criado_em DESC, r.id DESC LIMIT %d", $evento_id, $limite ) );

	$acoes = jelly_ar_historico_acoes();

	return array_map( function ( $l ) use ( $acoes ) {
		return [
			'quando'      => get_date_from_gmt( $l->criado_em, 'd/m/Y H:i' ),
			'acao'        => $l->acao,
			'rotulo'      => $acoes[ $l->acao ] ?? $l->acao,
			'autor'       => $l->display_name ? $l->display_name : __( 'Sistema', 'jelly-area-reservada' ),
			'marcacao_id' => (int) $l->marcacao_id,
			'resumo'      => $l->resumo,
		];
	}, $linhas );
}

/**
 * O histórico das marcações de um evento, por marcação — para a janela de cada
 * horário da grelha: [ marcacao_id => [ ações, da mais antiga à mais recente ] ].
 */
function jelly_ar_historico_por_marcacao( $evento_id ) {
	$r = [];

	foreach ( array_reverse( jelly_ar_historico_do_evento( $evento_id, 1000 ) ) as $h ) {
		if ( $h['marcacao_id'] ) {
			$r[ $h['marcacao_id'] ][] = [
				'quando' => $h['quando'],
				'rotulo' => $h['rotulo'],
				'autor'  => $h['autor'],
				'resumo' => $h['resumo'],
			];
		}
	}

	return $r;
}
