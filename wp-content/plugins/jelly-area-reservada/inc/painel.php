<?php
/**
 * O Painel: a entrada do back-office. Junta, numa página, o que pede atenção
 * e o estado da área — sem dados próprios: lê o que as outras áreas já têm.
 *
 * - por decidir: os pedidos de marcação e os registos por aprovar;
 * - a agenda: as marcações dos próximos 7 dias;
 * - os próximos eventos com marcações, com a ocupação e a disponibilidade;
 * - a atividade recente (o histórico de todos os eventos);
 * - a atividade do mês (do dia 1 ao fim), num gráfico, e os totais dele;
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

	return [
		'pedidos'    => $pedidos,
		'registos'   => $registos,
		'agenda'     => jelly_ar_marcacoes_entre( $hoje, $semana ),
		'proximos'   => $proximos,
		'sem_grelha' => $sem_grelha,
		'atividade'  => jelly_ar_historico_recente( 8 ),
		'numeros'    => [
			'ativos' => count( array_filter( $todos, function ( $u ) {
				return 'ativo' === $u['estado'];
			} ) ),
		],
		// O mês do gráfico e os totais dele; o mês vem no endereço (mes=2026-09), e por omissão é o de hoje.
		'mes'        => jelly_ar_painel_mes( isset( $_GET['mes'] ) ? sanitize_text_field( wp_unslash( $_GET['mes'] ) ) : '' ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		'smtp'       => function_exists( 'jelly_ar_envio_autenticado' ) ? jelly_ar_envio_autenticado() : null,
	];
}

/**
 * A atividade de um mês, do dia 1 ao último, para o gráfico do Painel: os
 * pedidos de marcação que entraram (feitos pela equipa ou pedidos pelos
 * associados) e os acessos dos associados, por dia. Os dias que ainda não
 * chegaram vêm com `futuro`, para o gráfico os deixar vazios. E os totais do
 * mês: pedidos, acessos, registos novos e descargas. Sem comparação com o mês
 * anterior: há meses sem eventos, e a diferença não diria nada.
 *
 * @param string $mes Y-m; por omissão, o mês de hoje. Um mês por vir volta ao de hoje.
 */
function jelly_ar_painel_atividade( $mes = '' ) {
	global $wpdb;

	$hoje = current_time( 'Y-m-d' );
	$mes  = preg_match( '/^\d{4}-\d{2}$/', (string) $mes ) && $mes <= substr( $hoje, 0, 7 ) ? $mes : substr( $hoje, 0, 7 );
	$de   = $mes . '-01';
	$fim  = gmdate( 'Y-m-t', strtotime( $de ) );
	$dias = [];
	for ( $d = strtotime( $de ); $d <= strtotime( $fim ); $d += DAY_IN_SECONDS ) {
		$dia          = gmdate( 'Y-m-d', $d );
		$dias[ $dia ] = [ 'marcacoes' => 0, 'acessos' => 0, 'futuro' => $dia > $hoje ];
	}

	$series = [
		'marcacoes' => [ jelly_ar_tabela( 'marcacoes' ), 'pedido_em' ],
		'acessos'   => [ jelly_ar_tabela( 'acessos' ), 'criado_em' ],
	];

	foreach ( $series as $chave => $s ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$linhas = $wpdb->get_results( $wpdb->prepare( "SELECT DATE({$s[1]}) AS dia, COUNT(*) AS n FROM {$s[0]} WHERE {$s[1]} BETWEEN %s AND %s GROUP BY DATE({$s[1]})", $de . ' 00:00:00', $fim . ' 23:59:59' ) );
		foreach ( $linhas as $l ) {
			if ( isset( $dias[ $l->dia ] ) ) {
				$dias[ $l->dia ][ $chave ] = (int) $l->n;
			}
		}
	}

	// Os totais do mês.
	$contar = function ( $tabela, $coluna ) use ( $wpdb, $de, $fim ) {
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabela} WHERE {$coluna} BETWEEN %s AND %s", $de . ' 00:00:00', $fim . ' 23:59:59' ) ); // phpcs:ignore WordPress.DB
	};

	return [
		'mes'    => $mes,
		'dias'   => $dias,
		'totais' => [
			'marcacoes' => $contar( jelly_ar_tabela( 'marcacoes' ), 'pedido_em' ),
			'acessos'   => $contar( jelly_ar_tabela( 'acessos' ), 'criado_em' ),
			'registos'  => $contar( jelly_ar_tabela( 'associados' ), 'registado_em' ),
			'descargas' => $contar( jelly_ar_tabela( 'descargas' ), 'criado_em' ),
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

/**
 * O cartão do mês do Painel, pronto a mostrar: o título, os meses vizinhos
 * para as setas, os dados do gráfico e os totais. Serve a página e o pedido
 * Ajax das setas (jelly_ar_painel_mes_ajax()), para os dois darem o mesmo.
 *
 * @param string $mes Y-m; por omissão, o de hoje.
 */
function jelly_ar_painel_mes( $mes = '' ) {
	$a      = jelly_ar_painel_atividade( $mes );
	$mes_ts = strtotime( $a['mes'] . '-01' );
	$atual  = current_time( 'Y-m' );
	$hoje   = current_time( 'Y-m-d' );
	$dias   = [];

	foreach ( $a['dias'] as $dia => $v ) {
		// No mês de hoje, o gráfico acaba em hoje: os dias que ainda não chegaram não entram.
		if ( $v['futuro'] ) {
			continue;
		}

		$ts     = strtotime( $dia );
		$dias[] = [
			// Por baixo do gráfico "1 set"; na dica "Ter, 1 set".
			'curto'     => jelly_ar_data( 'j M', $ts ),
			'rotulo'    => ucfirst( jelly_ar_data( 'D, j M', $ts ) ),
			'hoje'      => $hoje === $dia,
			'futuro'    => $v['futuro'],
			'marcacoes' => $v['marcacoes'],
			'acessos'   => $v['acessos'],
		];
	}

	$seguinte = gmdate( 'Y-m', strtotime( '+1 month', $mes_ts ) );

	return [
		'mes'      => $a['mes'],
		/* translators: %s: mês e ano */
		'titulo'   => sprintf( __( 'Atividade de %s', 'jelly-area-reservada' ), jelly_ar_data( 'F \d\e Y', $mes_ts ) ),
		'anterior' => gmdate( 'Y-m', strtotime( '-1 month', $mes_ts ) ),
		// O seguinte só até ao mês de hoje.
		'seguinte' => $seguinte <= $atual ? $seguinte : '',
		'atual'    => $atual,
		'grafico'  => [
			'series' => [
				[ 'chave' => 'marcacoes', 'nome' => __( 'Pedidos de marcação', 'jelly-area-reservada' ), 'cor' => '#f41892' ],
				[ 'chave' => 'acessos', 'nome' => __( 'Acessos', 'jelly-area-reservada' ), 'cor' => '#8048a6' ],
			],
			'dias'   => $dias,
		],
		'totais'   => $a['totais'],
	];
}

/**
 * As setas do cartão do mês: o mês pedido, em JSON, sem recarregar a página.
 */
function jelly_ar_painel_mes_ajax() {
	if ( ! jelly_ar_e_administrador() ) {
		wp_send_json_error( null, 403 );
	}

	check_ajax_referer( 'jelly_ar_painel_mes' );

	wp_send_json_success( jelly_ar_painel_mes( isset( $_GET['mes'] ) ? sanitize_text_field( wp_unslash( $_GET['mes'] ) ) : '' ) );
}
add_action( 'wp_ajax_jelly_ar_painel_mes', 'jelly_ar_painel_mes_ajax' );
