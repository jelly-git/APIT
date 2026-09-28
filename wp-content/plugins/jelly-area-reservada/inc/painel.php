<?php
/**
 * O Painel: a entrada do back-office. Junta, numa página, o que pede atenção
 * e o estado da área — sem dados próprios: lê o que as outras áreas já têm.
 *
 * - por decidir: os pedidos de marcação e os registos por aprovar;
 * - a agenda: as marcações dos próximos 7 dias;
 * - os próximos eventos com marcações, com a ocupação e a disponibilidade;
 * - a atividade recente (o histórico de todos os eventos);
 * - os números dos associados e dos documentos, nos últimos 30 dias;
 * - os alertas: o e-mail que não sai autenticado, eventos com marcações sem
 *   mesas ou sem horários, e os dados de exemplo ainda ligados.
 */

defined( 'ABSPATH' ) || exit;

/**
 * As últimas ações de todos os eventos, com o título do evento.
 */
function jelly_ar_historico_recente( $limite = 8 ) {
	global $wpdb;

	$r = jelly_ar_tabela( 'registo' );
	$e = jelly_ar_tabela( 'eventos' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( $wpdb->prepare( "SELECT r.*, u.display_name, e.titulo AS evento FROM {$r} r LEFT JOIN {$wpdb->users} u ON u.ID = r.autor_id LEFT JOIN {$e} e ON e.id = r.evento_id ORDER BY r.criado_em DESC, r.id DESC LIMIT %d", $limite ) );

	$acoes = jelly_ar_historico_acoes();

	return array_map( function ( $l ) use ( $acoes ) {
		return [
			'quando'    => get_date_from_gmt( $l->criado_em, 'd/m/Y H:i' ),
			'acao'      => $l->acao,
			'rotulo'    => $acoes[ $l->acao ] ?? $l->acao,
			'autor'     => $l->display_name ? $l->display_name : __( 'Sistema', 'jelly-area-reservada' ),
			'evento'    => (string) $l->evento,
			'evento_id' => (int) $l->evento_id,
			'resumo'    => $l->resumo,
		];
	}, $linhas );
}

/**
 * Tudo o que o Painel mostra.
 */
function jelly_ar_painel_dados() {
	global $wpdb;

	$hoje    = current_time( 'Y-m-d' );
	$semana  = gmdate( 'Y-m-d', strtotime( $hoje . ' +6 days' ) );
	$ha30    = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
	$pedidos = array_values( array_filter( jelly_ar_marcacoes_todas(), function ( $m ) {
		return 'pendente' === $m['estado'];
	} ) );
	$todos   = jelly_ar_utilizadores_todos();
	$registos = array_values( array_filter( $todos, function ( $u ) {
		return 'pendente' === $u['estado'];
	} ) );

	// Os próximos eventos com marcações, do mais próximo: a ocupação e a disponibilidade.
	$eventos = array_values( array_filter( jelly_ar_eventos_todos(), function ( $e ) use ( $hoje ) {
		return $e['marcacoes'] && 'publicado' === $e['estado'] && ( $e['fim'] ? $e['fim'] : $e['inicio'] ) >= str_replace( '-', '', $hoje );
	} ) );
	usort( $eventos, function ( $a, $b ) {
		return strcmp( $a['inicio'], $b['inicio'] );
	} );

	$proximos   = [];
	$sem_grelha = [];
	// Só se avisa da grelha em falta nos eventos dos próximos 90 dias: um evento daqui a dois anos não é urgente.
	$breve = gmdate( 'Ymd', strtotime( $hoje . ' +90 days' ) );
	foreach ( $eventos as $e ) {
		$r = jelly_ar_mesas_resumo( $e['id'] );

		if ( ( ! $r['mesas'] || ! $r['dias'] ) && $e['inicio'] <= $breve ) {
			$sem_grelha[] = [ 'id' => $e['id'], 'titulo' => $e['titulo'], 'falta' => ! $r['mesas'] ? 'mesas' : 'horarios' ];
		}

		if ( count( $proximos ) < 4 ) {
			$proximos[] = [
				'evento'  => $e,
				'resumo'  => $r,
				'estado'  => jelly_ar_disponibilidade_estado( $e ),
			];
		}
	}

	$acessos   = jelly_ar_tabela( 'acessos' );
	$descargas = jelly_ar_tabela( 'descargas' );
	$assoc     = jelly_ar_tabela( 'associados' );

	return [
		'pedidos'    => $pedidos,
		'registos'   => $registos,
		'agenda'     => jelly_ar_marcacoes_entre( $hoje, $semana ),
		'proximos'   => $proximos,
		'sem_grelha' => $sem_grelha,
		'atividade'  => jelly_ar_historico_recente( 8 ),
		'numeros'    => [
			'ativos'    => count( array_filter( $todos, function ( $u ) {
				return 'ativo' === $u['estado'];
			} ) ),
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			'registos'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$assoc} WHERE registado_em >= %s", $ha30 ) ),
			'acessos'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$acessos} WHERE criado_em >= %s", $ha30 ) ),
			'descargas' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$descargas} WHERE criado_em >= %s", $ha30 ) ),
			'publicados' => (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . jelly_ar_tabela( 'documentos' ) . " WHERE estado = 'publicado'" ),
			'marcacoes'  => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . jelly_ar_tabela( 'marcacoes' ) . ' WHERE pedido_em >= %s', $ha30 ) ),
			// phpcs:enable
		],
		'atividade30' => jelly_ar_painel_atividade(),
		'smtp'       => function_exists( 'jelly_ar_envio_autenticado' ) ? jelly_ar_envio_autenticado() : null,
	];
}

/**
 * A atividade dos últimos 30 dias, dia a dia, para o gráfico do Painel: os
 * pedidos de marcação que entraram (feitos pela equipa ou pedidos pelos
 * associados) e os acessos dos associados. E, para cada número do mês, o dos
 * 30 dias anteriores, para a comparação.
 */
function jelly_ar_painel_atividade() {
	global $wpdb;

	$agora = time();
	$de    = gmdate( 'Y-m-d', $agora - 29 * DAY_IN_SECONDS );
	$dias  = [];
	for ( $i = 29; $i >= 0; $i-- ) {
		$dias[ gmdate( 'Y-m-d', $agora - $i * DAY_IN_SECONDS ) ] = [ 'marcacoes' => 0, 'acessos' => 0 ];
	}

	$series = [
		'marcacoes' => [ jelly_ar_tabela( 'marcacoes' ), 'pedido_em' ],
		'acessos'   => [ jelly_ar_tabela( 'acessos' ), 'criado_em' ],
	];

	foreach ( $series as $chave => $s ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$linhas = $wpdb->get_results( $wpdb->prepare( "SELECT DATE({$s[1]}) AS dia, COUNT(*) AS n FROM {$s[0]} WHERE {$s[1]} >= %s GROUP BY DATE({$s[1]})", $de . ' 00:00:00' ) );
		foreach ( $linhas as $l ) {
			if ( isset( $dias[ $l->dia ] ) ) {
				$dias[ $l->dia ][ $chave ] = (int) $l->n;
			}
		}
	}

	// O mesmo número nos 30 dias antes destes, para a seta de subida ou descida.
	$antes_de  = gmdate( 'Y-m-d H:i:s', $agora - 60 * DAY_IN_SECONDS );
	$antes_ate = gmdate( 'Y-m-d H:i:s', $agora - 30 * DAY_IN_SECONDS );
	$contar    = function ( $tabela, $coluna ) use ( $wpdb, $antes_de, $antes_ate ) {
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabela} WHERE {$coluna} >= %s AND {$coluna} < %s", $antes_de, $antes_ate ) ); // phpcs:ignore WordPress.DB
	};

	return [
		'dias'     => $dias,
		'anterior' => [
			'registos'  => $contar( jelly_ar_tabela( 'associados' ), 'registado_em' ),
			'acessos'   => $contar( jelly_ar_tabela( 'acessos' ), 'criado_em' ),
			'descargas' => $contar( jelly_ar_tabela( 'descargas' ), 'criado_em' ),
			'marcacoes' => $contar( jelly_ar_tabela( 'marcacoes' ), 'pedido_em' ),
		],
	];
}

/**
 * As descargas dos documentos nos últimos 30 dias — o número real, para o
 * cartão dos Documentos.
 */
function jelly_ar_descargas_30_dias() {
	global $wpdb;

	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . jelly_ar_tabela( 'descargas' ) . ' WHERE criado_em >= %s', gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB
}
