<?php
/**
 * As mesas e os horários de um evento, e a grelha de marcações que sai dos dois.
 *
 * Tudo pertence a um evento (inc/eventos-dados.php) e só serve os que aceitam
 * marcações — o visto "Os associados podem marcar mesas neste evento":
 *
 *   jelly_ar_mesas            as mesas do evento: nome, localização, lugares
 *   jelly_ar_evento_horarios  um período de um dia por linha: a hora de início,
 *                             a de fim e o intervalo entre marcações. Um dia
 *                             pode ter vários (10:00–12:00 e 13:00–15:00)
 *   jelly_ar_marcacoes        um pedido de um associado para uma mesa, num dia,
 *                             a uma hora (o início de um bloco)
 *
 * Os blocos saem dos períodos do dia: em cada um, de hora_inicio a hora_fim,
 * de intervalo em intervalo, e só os que cabem inteiros antes do fim; entre
 * dois períodos não há blocos. Um bloco de uma mesa
 * leva tantos associados quantos os lugares dela; cada marcação ocupa um
 * lugar enquanto não for rejeitada nem cancelada (ocupa = 1). A chave única
 * da tabela impede o mesmo associado duas vezes no mesmo bloco; os lugares, e
 * o associado não estar em duas mesas à mesma hora, verificam-se aqui.
 *
 * É daqui que o back-office (inc/mesas.php) e, mais tarde, a área do
 * associado leem.
 */

defined( 'ABSPATH' ) || exit;

/*
 * Os intervalos que se podem escolher, em minutos. Por agora só 30, o das
 * reuniões nos mercados: com um só, o ecrã mostra-o em vez de o pôr a escolher.
 * Para voltar a dar a escolha, basta pôr aqui os outros (15, 45, 60 já funcionam).
 */
const JELLY_AR_INTERVALOS        = [ 30 ];
const JELLY_AR_INTERVALO_OMISSAO = 30;

/*
 * Lugares por mesa. Por agora não há: cada horário de uma mesa leva uma
 * marcação, de um associado. Com false, toda a mesa conta como um lugar
 * (jelly_ar_mesas()), e os ecrãs falam de horários e marcações, sem campo de
 * lugares. O resto — a grelha, os pedidos, as mudanças — já sabe contar
 * lugares: para os voltar a ter, basta pôr true, e o número de cada mesa
 * volta a ser o da coluna `lugares` da tabela, que continua lá.
 */
const JELLY_AR_LUGARES = false;

/**
 * Os dias do evento, do início ao fim, em Y-m-d. Um evento de um dia só tem
 * um. Nunca mais de 31, para uma data de fim errada não desenhar um ano.
 */
function jelly_ar_evento_dias( $evento ) {
	$inicio = DateTime::createFromFormat( '!Ymd', (string) $evento['inicio'] );
	$fim    = $evento['fim'] ? DateTime::createFromFormat( '!Ymd', (string) $evento['fim'] ) : clone $inicio;

	if ( ! $inicio || ! $fim || $fim < $inicio ) {
		return $inicio ? [ $inicio->format( 'Y-m-d' ) ] : [];
	}

	$dias = [];
	for ( $d = clone $inicio; $d <= $fim && count( $dias ) < 31; $d->modify( '+1 day' ) ) {
		$dias[] = $d->format( 'Y-m-d' );
	}

	return $dias;
}

/**
 * As mesas do evento, pela ordem, com quantas marcações tem cada uma (todas,
 * também as rejeitadas: é o que impede apagá-la).
 */
function jelly_ar_mesas( $evento_id ) {
	global $wpdb;

	$m = jelly_ar_tabela( 'mesas' );
	$c = jelly_ar_tabela( 'marcacoes' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( $wpdb->prepare( "SELECT m.*, (SELECT COUNT(*) FROM {$c} x WHERE x.mesa_id = m.id) AS marcacoes FROM {$m} m WHERE m.evento_id = %d ORDER BY m.ordem, m.id", $evento_id ) );

	return array_map( function ( $l ) {
		return [
			'id'          => (int) $l->id,
			'nome'        => $l->nome,
			'localizacao' => $l->localizacao,
			// Sem lugares por mesa (JELLY_AR_LUGARES), uma marcação por horário.
			'lugares'     => JELLY_AR_LUGARES ? (int) $l->lugares : 1,
			'marcacoes'   => (int) $l->marcacoes,
		];
	}, $linhas );
}

/**
 * Os horários do evento, por dia (Y-m-d): início e fim em H:i, e o intervalo.
 */
function jelly_ar_horarios( $evento_id ) {
	global $wpdb;

	$r = [];
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . jelly_ar_tabela( 'evento_horarios' ) . ' WHERE evento_id = %d ORDER BY dia, hora_inicio', $evento_id ) ) as $l ) {
		$periodo = [ 'inicio' => substr( $l->hora_inicio, 0, 5 ), 'fim' => substr( $l->hora_fim, 0, 5 ) ];

		if ( ! isset( $r[ $l->dia ] ) ) {
			$r[ $l->dia ] = $periodo + [ 'intervalo' => (int) $l->intervalo, 'periodos' => [] ];
		}

		// O início e o fim do dia são os do primeiro e do último período.
		$r[ $l->dia ]['fim']        = $periodo['fim'];
		$r[ $l->dia ]['periodos'][] = $periodo;
	}

	return $r;
}

/**
 * Os blocos de um dia: as horas de início (H:i) dos que cabem inteiros em cada
 * período. Aceita também um só período (início, fim e intervalo, sem
 * `periodos`).
 */
function jelly_ar_blocos( $horario ) {
	$intervalo = max( 5, (int) $horario['intervalo'] );
	$periodos  = ! empty( $horario['periodos'] ) ? $horario['periodos'] : [ $horario ];
	$blocos    = [];

	foreach ( $periodos as $p ) {
		$fim = jelly_ar_minutos( $p['fim'] );

		for ( $m = jelly_ar_minutos( $p['inicio'] ); $m + $intervalo <= $fim; $m += $intervalo ) {
			$blocos[] = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
		}
	}

	return $blocos;
}

/**
 * Os períodos de um dia por extenso: "10:00–12:00 · 13:00–15:00".
 */
function jelly_ar_periodos_texto( $horario ) {
	$periodos = ! empty( $horario['periodos'] ) ? $horario['periodos'] : [ $horario ];

	return implode( ' · ', array_map( function ( $p ) {
		return $p['inicio'] . '–' . $p['fim'];
	}, $periodos ) );
}

/**
 * De quantos em quantos minutos se escolhem as horas de início e de fim, para
 * um intervalo: o próprio intervalo (30 → 10:00, 10:30…; 60 → horas certas),
 * menos com 45, que fica de 15 em 15 — de 45 em 45 a contar da meia-noite
 * daria 00:45, 01:30, 02:15…, e 10:00 nem existiria. Os blocos têm sempre o
 * intervalo, a partir da hora escolhida.
 */
function jelly_ar_passo_horas( $intervalo ) {
	return 45 === (int) $intervalo ? 15 : max( 15, (int) $intervalo );
}

// "09:30" → 570.
function jelly_ar_minutos( $hora ) {
	list( $h, $m ) = array_map( 'intval', explode( ':', $hora . ':0' ) );

	return $h * 60 + $m;
}

/**
 * As marcações que ocupam lugares (as não rejeitadas nem canceladas), para a
 * grelha: [ mesa_id ][ dia ][ H:i ] => a lista das marcações desse bloco, pela
 * ordem em que foram feitas.
 */
function jelly_ar_marcacoes_grelha( $evento_id ) {
	global $wpdb;

	$c = jelly_ar_tabela( 'marcacoes' );
	$a = jelly_ar_tabela( 'associados' );
	$r = [];

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( $wpdb->prepare( "SELECT c.id, c.mesa_id, c.dia, c.hora, c.estado, c.user_id, a.nome, a.apelido, a.empresa FROM {$c} c LEFT JOIN {$a} a ON a.user_id = c.user_id WHERE c.evento_id = %d AND c.ocupa = 1 ORDER BY c.id", $evento_id ) );

	foreach ( $linhas as $l ) {
		$r[ (int) $l->mesa_id ][ $l->dia ][ substr( $l->hora, 0, 5 ) ][] = [
			'id'      => (int) $l->id,
			'user_id' => (int) $l->user_id,
			'estado'  => $l->estado,
			'quem'    => trim( $l->nome . ' ' . $l->apelido ),
			'empresa' => (string) $l->empresa,
		];
	}

	return $r;
}

/**
 * Os associados que se podem marcar: os ativos, por nome.
 */
function jelly_ar_associados_ativos() {
	global $wpdb;

	$a = jelly_ar_tabela( 'associados' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( "SELECT a.user_id, a.nome, a.apelido, a.empresa, u.user_email FROM {$a} a INNER JOIN {$wpdb->users} u ON u.ID = a.user_id WHERE a.estado = 'ativo' ORDER BY a.nome, a.apelido" );

	return array_map( function ( $l ) {
		return [
			'id'      => (int) $l->user_id,
			'nome'    => trim( $l->nome . ' ' . $l->apelido ),
			'empresa' => (string) $l->empresa,
			'email'   => $l->user_email,
		];
	}, $linhas );
}

/**
 * Um bloco do evento, se existir: a mesa é do evento, o dia tem horário e a
 * hora é o início de um dos blocos desse dia. Devolve a mesa, ou null.
 */
function jelly_ar_bloco_valido( $evento_id, $mesa_id, $dia, $hora ) {
	$horarios = jelly_ar_horarios( $evento_id );

	if ( ! isset( $horarios[ $dia ] ) || ! in_array( $hora, jelly_ar_blocos( $horarios[ $dia ] ), true ) ) {
		return null;
	}

	foreach ( jelly_ar_mesas( $evento_id ) as $m ) {
		if ( $m['id'] === (int) $mesa_id ) {
			return $m;
		}
	}

	return null;
}

/**
 * Quantos lugares de um bloco estão ocupados, sem contar as marcações de
 * $excepto (as que se estão a mudar para lá).
 */
function jelly_ar_bloco_ocupados( $mesa_id, $dia, $hora, $excepto = [] ) {
	global $wpdb;

	$sql = $wpdb->prepare( 'SELECT id FROM ' . jelly_ar_tabela( 'marcacoes' ) . ' WHERE mesa_id = %d AND dia = %s AND hora = %s AND ocupa = 1', $mesa_id, $dia, $hora . ':00' ); // phpcs:ignore WordPress.DB

	return count( array_diff( array_map( 'intval', $wpdb->get_col( $sql ) ), $excepto ) ); // phpcs:ignore WordPress.DB
}

/**
 * Os associados, de entre $users, que já têm um lugar noutra marcação a essa
 * hora do evento — em qualquer mesa: ninguém está em duas ao mesmo tempo.
 * Não conta as marcações de $excepto.
 */
function jelly_ar_ocupados_a_hora( $evento_id, $dia, $hora, $users, $excepto = [] ) {
	global $wpdb;

	if ( ! $users ) {
		return [];
	}

	$lista = implode( ',', array_map( 'intval', $users ) );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( $wpdb->prepare( 'SELECT id, user_id FROM ' . jelly_ar_tabela( 'marcacoes' ) . " WHERE evento_id = %d AND dia = %s AND hora = %s AND ocupa = 1 AND user_id IN ({$lista})", $evento_id, $dia, $hora . ':00' ) );

	$r = [];
	foreach ( $linhas as $l ) {
		if ( ! in_array( (int) $l->id, $excepto, true ) ) {
			$r[] = (int) $l->user_id;
		}
	}

	return array_values( array_unique( $r ) );
}

/**
 * As marcações vivas do evento com esses ids — as que se removem ou mudam.
 * Um id de outro evento, ou de uma marcação já solta, fica de fora.
 */
function jelly_ar_marcacoes_do_evento( $evento_id, $ids ) {
	global $wpdb;

	$ids = array_filter( array_map( 'intval', (array) $ids ) );

	if ( ! $ids ) {
		return [];
	}

	$lista = implode( ',', $ids );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . jelly_ar_tabela( 'marcacoes' ) . " WHERE evento_id = %d AND ocupa = 1 AND id IN ({$lista})", $evento_id ) );

	return array_map( function ( $l ) {
		return [
			'id'      => (int) $l->id,
			'mesa_id' => (int) $l->mesa_id,
			'user_id' => (int) $l->user_id,
			'dia'     => $l->dia,
			'hora'    => substr( $l->hora, 0, 5 ),
			'estado'  => $l->estado,
		];
	}, $linhas );
}

/**
 * A equipa marca associados num bloco. As marcações entram já confirmadas:
 * quem as faz é a própria APIT.
 *
 * @return array|WP_Error Os ids dos associados marcados, ou o erro:
 *                        marcacao-bloco (o bloco não existe), marcacao-ninguem
 *                        (nenhum associado ativo escolhido), marcacao-cheia
 *                        (não há lugares para todos), marcacao-hora (algum já
 *                        está marcado a essa hora).
 */
function jelly_ar_marcar( $evento_id, $mesa_id, $dia, $hora, $users ) {
	global $wpdb;

	$mesa = jelly_ar_bloco_valido( $evento_id, $mesa_id, $dia, $hora );

	if ( ! $mesa ) {
		return new WP_Error( 'marcacao-bloco' );
	}

	// Só associados ativos.
	$ativos = wp_list_pluck( jelly_ar_associados_ativos(), 'id' );
	$users  = array_values( array_intersect( array_unique( array_map( 'intval', (array) $users ) ), $ativos ) );

	if ( ! $users ) {
		return new WP_Error( 'marcacao-ninguem' );
	}

	if ( jelly_ar_ocupados_a_hora( $evento_id, $dia, $hora, $users ) ) {
		return new WP_Error( 'marcacao-hora' );
	}

	// O mesmo trinco do pedido do associado (jelly_ar_pedir()): um de cada vez neste bloco.
	if ( ! jelly_ar_bloco_trancar( $mesa['id'], $dia, $hora ) ) {
		return new WP_Error( 'marcacao-ocupado' );
	}

	if ( jelly_ar_bloco_ocupados( $mesa['id'], $dia, $hora ) + count( $users ) > $mesa['lugares'] ) {
		jelly_ar_bloco_soltar( $mesa['id'], $dia, $hora );
		return new WP_Error( 'marcacao-cheia' );
	}

	$agora = current_time( 'mysql', true );

	foreach ( $users as $u ) {
		$wpdb->insert( // phpcs:ignore WordPress.DB
			jelly_ar_tabela( 'marcacoes' ),
			[
				'evento_id'    => $evento_id,
				'mesa_id'      => $mesa['id'],
				'user_id'      => $u,
				'dia'          => $dia,
				'hora'         => $hora . ':00',
				'estado'       => 'aprovada',
				'ocupa'        => 1,
				'pedido_em'    => $agora,
				'decidido_em'  => $agora,
				'decidido_por' => get_current_user_id(),
				'notas'        => '',
			]
		);

		jelly_ar_historico_gravar(
			'marcacao-criada',
			[
				'evento_id'    => $evento_id,
				'marcacao_id'  => $wpdb->insert_id,
				'associado_id' => $u,
				'resumo'       => jelly_ar_historico_marcacao( $u, $mesa, $dia, $hora ),
			]
		);
	}

	jelly_ar_bloco_soltar( $mesa['id'], $dia, $hora );

	return $users;
}

/**
 * A equipa retira marcações: ficam canceladas, no histórico, e o lugar solta-se.
 *
 * @return array As marcações retiradas (como jelly_ar_marcacoes_do_evento()).
 */
function jelly_ar_marcacoes_remover( $evento_id, $ids ) {
	global $wpdb;

	$marcacoes = jelly_ar_marcacoes_do_evento( $evento_id, $ids );

	foreach ( $marcacoes as $m ) {
		$wpdb->update( // phpcs:ignore WordPress.DB
			jelly_ar_tabela( 'marcacoes' ),
			[
				'estado'       => 'cancelada',
				'ocupa'        => null,
				'decidido_em'  => current_time( 'mysql', true ),
				'decidido_por' => get_current_user_id(),
			],
			[ 'id' => $m['id'] ]
		);

		jelly_ar_historico_gravar(
			'marcacao-removida',
			[
				'evento_id'    => $evento_id,
				'marcacao_id'  => $m['id'],
				'associado_id' => $m['user_id'],
				'resumo'       => jelly_ar_historico_marcacao( $m['user_id'], jelly_ar_mesa_nome( $m['mesa_id'] ), $m['dia'], $m['hora'] ),
			]
		);
	}

	return $marcacoes;
}

/**
 * O nome de uma mesa, pelo id — para o histórico, que o escreve por extenso.
 */
function jelly_ar_mesa_nome( $mesa_id ) {
	global $wpdb;

	return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT nome FROM ' . jelly_ar_tabela( 'mesas' ) . ' WHERE id = %d', $mesa_id ) ); // phpcs:ignore WordPress.DB
}

/**
 * A equipa muda marcações para outro bloco — outra hora, outra mesa ou outro
 * dia do evento. Mantêm o estado que tinham (pendente ou confirmada).
 *
 * @return array|WP_Error As marcações mudadas, como estavam antes; ou o erro:
 *                        marcacao-bloco, marcacao-ninguem, marcacao-mesmo
 *                        (o destino é o bloco onde já estão), marcacao-cheia,
 *                        marcacao-hora.
 */
function jelly_ar_marcacoes_mover( $evento_id, $ids, $mesa_id, $dia, $hora ) {
	global $wpdb;

	$mesa = jelly_ar_bloco_valido( $evento_id, $mesa_id, $dia, $hora );

	if ( ! $mesa ) {
		return new WP_Error( 'marcacao-bloco' );
	}

	$marcacoes = jelly_ar_marcacoes_do_evento( $evento_id, $ids );

	if ( ! $marcacoes ) {
		return new WP_Error( 'marcacao-ninguem' );
	}

	// As que já estão no destino não se mudam; se forem todas, não há nada a fazer.
	$marcacoes = array_values( array_filter( $marcacoes, function ( $m ) use ( $mesa, $dia, $hora ) {
		return ! ( $m['mesa_id'] === $mesa['id'] && $m['dia'] === $dia && $m['hora'] === $hora );
	} ) );

	if ( ! $marcacoes ) {
		return new WP_Error( 'marcacao-mesmo' );
	}

	$ids = wp_list_pluck( $marcacoes, 'id' );

	if ( jelly_ar_ocupados_a_hora( $evento_id, $dia, $hora, wp_list_pluck( $marcacoes, 'user_id' ), $ids ) ) {
		return new WP_Error( 'marcacao-hora' );
	}

	if ( jelly_ar_bloco_ocupados( $mesa['id'], $dia, $hora, $ids ) + count( $marcacoes ) > $mesa['lugares'] ) {
		return new WP_Error( 'marcacao-cheia' );
	}

	foreach ( $marcacoes as $m ) {
		$wpdb->update( // phpcs:ignore WordPress.DB
			jelly_ar_tabela( 'marcacoes' ),
			[
				'mesa_id' => $mesa['id'],
				'dia'     => $dia,
				'hora'    => $hora . ':00',
			],
			[ 'id' => $m['id'] ]
		);

		// "Ana Silva · Mesa 1 · qua, 7 out · 10:00 → Mesa 2 · qua, 7 out · 10:30": de onde e para onde.
		$depois = jelly_ar_historico_marcacao( $m['user_id'], $mesa, $dia, $hora );
		jelly_ar_historico_gravar(
			'marcacao-mudada',
			[
				'evento_id'    => $evento_id,
				'marcacao_id'  => $m['id'],
				'associado_id' => $m['user_id'],
				'resumo'       => jelly_ar_historico_marcacao( $m['user_id'], jelly_ar_mesa_nome( $m['mesa_id'] ), $m['dia'], $m['hora'] ) . ' → ' . substr( $depois, strpos( $depois, ' · ' ) + strlen( ' · ' ) ),
			]
		);
	}

	return $marcacoes;
}

/**
 * O resumo de um evento para as listas: mesas, dias com horário, blocos (mesas
 * × blocos de cada dia), lugares que se podem marcar (os lugares das mesas ×
 * blocos de cada dia) e quantos estão ocupados.
 */
function jelly_ar_mesas_resumo( $evento_id ) {
	global $wpdb;

	$lista    = jelly_ar_mesas( $evento_id );
	$mesas    = count( $lista );
	$sentados = array_sum( wp_list_pluck( $lista, 'lugares' ) );
	$horarios = jelly_ar_horarios( $evento_id );
	$blocos   = 0;
	$lugares  = 0;

	foreach ( $horarios as $h ) {
		$n        = count( jelly_ar_blocos( $h ) );
		$blocos  += $n * $mesas;
		$lugares += $n * $sentados;
	}

	$contagem = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(estado = 'aprovada') AS confirmadas, SUM(estado = 'pendente') AS pendentes FROM " . jelly_ar_tabela( 'marcacoes' ) . ' WHERE evento_id = %d AND ocupa = 1', $evento_id ) ); // phpcs:ignore WordPress.DB

	return [
		'mesas'       => $mesas,
		'dias'        => count( $horarios ),
		'blocos'      => $blocos,
		'lugares'     => $lugares,
		'confirmadas' => (int) ( $contagem->confirmadas ?? 0 ),
		'pendentes'   => (int) ( $contagem->pendentes ?? 0 ),
	];
}

/**
 * Os ids dos eventos com mesas e horários — a grelha pronta para marcar: pelo
 * menos uma mesa e um dia com horário. Numa consulta só, para a lista de
 * eventos.
 */
function jelly_ar_eventos_com_grelha() {
	global $wpdb;

	$m = jelly_ar_tabela( 'mesas' );
	$h = jelly_ar_tabela( 'evento_horarios' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	return array_map( 'intval', $wpdb->get_col( "SELECT DISTINCT m.evento_id FROM {$m} m WHERE EXISTS (SELECT 1 FROM {$h} h WHERE h.evento_id = m.evento_id)" ) );
}

/* ---------- Trinco de um bloco ---------- */

/*
 * Dois pedidos para o último lugar de um bloco, ao mesmo tempo, veriam os dois
 * o lugar livre e entrariam os dois. O trinco (GET_LOCK do MySQL, por mesa,
 * dia e hora) põe-nos em fila: o segundo só verifica os lugares depois de o
 * primeiro gravar. Solta-se sozinho se o pedido morrer a meio.
 */
function jelly_ar_bloco_trancar( $mesa_id, $dia, $hora ) {
	global $wpdb;

	return '1' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', 'jelly_ar_' . $mesa_id . '_' . $dia . '_' . $hora ) ); // phpcs:ignore WordPress.DB
}

function jelly_ar_bloco_soltar( $mesa_id, $dia, $hora ) {
	global $wpdb;

	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'jelly_ar_' . $mesa_id . '_' . $dia . '_' . $hora ) ); // phpcs:ignore WordPress.DB
}

/* ---------- O pedido do associado ---------- */

/**
 * A marcação viva (pendente ou aprovada) de um associado num evento, ou null.
 * Cada associado tem no máximo uma por evento.
 */
function jelly_ar_marcacao_do_associado( $evento_id, $user_id ) {
	global $wpdb;

	$l = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . jelly_ar_tabela( 'marcacoes' ) . ' WHERE evento_id = %d AND user_id = %d AND ocupa = 1 ORDER BY id DESC LIMIT 1', $evento_id, $user_id ) ); // phpcs:ignore WordPress.DB

	return $l ? [
		'id'      => (int) $l->id,
		'mesa_id' => (int) $l->mesa_id,
		'dia'     => $l->dia,
		'hora'    => substr( $l->hora, 0, 5 ),
		'estado'  => $l->estado,
	] : null;
}

/**
 * O que o associado pode escolher num evento: os dias com horário, de hoje em
 * diante; em cada dia, os blocos; em cada bloco, as mesas com os lugares
 * livres. Uma hora já passada, no dia de hoje, não entra.
 */
function jelly_ar_disponibilidade( $evento ) {
	$mesas    = jelly_ar_mesas( $evento['id'] );
	$horarios = jelly_ar_horarios( $evento['id'] );
	$ocupados = jelly_ar_marcacoes_grelha( $evento['id'] );
	$hoje     = current_time( 'Y-m-d' );
	$agora    = current_time( 'H:i' );
	$dias     = [];

	foreach ( jelly_ar_evento_dias( $evento ) as $dia ) {
		if ( ! isset( $horarios[ $dia ] ) || $dia < $hoje ) {
			continue;
		}

		$intervalo = $horarios[ $dia ]['intervalo'];
		$blocos    = [];

		foreach ( jelly_ar_blocos( $horarios[ $dia ] ) as $hora ) {
			if ( $dia === $hoje && $hora <= $agora ) {
				continue;
			}

			$fim    = jelly_ar_minutos( $hora ) + $intervalo;
			$livres = [];

			foreach ( $mesas as $m ) {
				$n = $m['lugares'] - count( $ocupados[ $m['id'] ][ $dia ][ $hora ] ?? [] );

				$livres[] = [
					'id'          => $m['id'],
					'nome'        => $m['nome'],
					'localizacao' => $m['localizacao'],
					'lugares'     => $m['lugares'],
					'livres'      => max( 0, $n ),
				];
			}

			$blocos[] = [
				'hora'  => $hora,
				'fim'   => sprintf( '%02d:%02d', intdiv( $fim, 60 ), $fim % 60 ),
				'mesas' => $livres,
			];
		}

		if ( $blocos ) {
			$d      = DateTime::createFromFormat( '!Y-m-d', $dia );
			$dias[] = [
				'dia'    => $dia,
				'rotulo' => ucfirst( jelly_ar_data( 'D, j M', $d->getTimestamp() ) ),
				'blocos' => $blocos,
			];
		}
	}

	return $dias;
}

/**
 * Um associado pede um lugar numa mesa, a uma hora: fica pendente, à espera
 * da aprovação da equipa (Aprovações).
 *
 * @return array|WP_Error A mesa, ou o erro: marcacao-fechada (o evento não
 *                        aceita marcações), marcacao-tem (já tem uma neste
 *                        evento), marcacao-bloco, marcacao-cheia,
 *                        marcacao-ocupado (o trinco não se conseguiu).
 */
function jelly_ar_pedir( $evento, $user_id, $mesa_id, $dia, $hora ) {
	global $wpdb;

	if ( empty( $evento['marcacoes'] ) || 'publicado' !== $evento['estado'] ) {
		return new WP_Error( 'marcacao-fechada' );
	}

	if ( jelly_ar_marcacao_do_associado( $evento['id'], $user_id ) ) {
		return new WP_Error( 'marcacao-tem' );
	}

	// Só os blocos que a disponibilidade mostra: de hoje em diante.
	$aberto = false;
	foreach ( jelly_ar_disponibilidade( $evento ) as $d ) {
		if ( $d['dia'] === $dia && in_array( $hora, wp_list_pluck( $d['blocos'], 'hora' ), true ) ) {
			$aberto = true;
		}
	}

	$mesa = $aberto ? jelly_ar_bloco_valido( $evento['id'], $mesa_id, $dia, $hora ) : null;

	if ( ! $mesa ) {
		return new WP_Error( 'marcacao-bloco' );
	}

	if ( ! jelly_ar_bloco_trancar( $mesa['id'], $dia, $hora ) ) {
		return new WP_Error( 'marcacao-ocupado' );
	}

	if ( jelly_ar_bloco_ocupados( $mesa['id'], $dia, $hora ) >= $mesa['lugares'] ) {
		jelly_ar_bloco_soltar( $mesa['id'], $dia, $hora );
		return new WP_Error( 'marcacao-cheia' );
	}

	$wpdb->insert( // phpcs:ignore WordPress.DB
		jelly_ar_tabela( 'marcacoes' ),
		[
			'evento_id' => $evento['id'],
			'mesa_id'   => $mesa['id'],
			'user_id'   => $user_id,
			'dia'       => $dia,
			'hora'      => $hora . ':00',
			'estado'    => 'pendente',
			'ocupa'     => 1,
			'pedido_em' => current_time( 'mysql', true ),
			'notas'     => '',
		]
	);

	$ok = (bool) $wpdb->insert_id;
	jelly_ar_bloco_soltar( $mesa['id'], $dia, $hora );

	if ( $ok ) {
		// O autor é o próprio associado, que é quem tem a sessão.
		jelly_ar_historico_gravar(
			'marcacao-pedida',
			[
				'evento_id'    => $evento['id'],
				'marcacao_id'  => $wpdb->insert_id,
				'associado_id' => $user_id,
				'resumo'       => jelly_ar_historico_marcacao( $user_id, $mesa, $dia, $hora ),
			]
		);
	}

	return $ok ? $mesa : new WP_Error( 'marcacao-cheia' );
}

/* ---------- As aprovações ---------- */

/**
 * Aprovar ou rejeitar pedidos pendentes. Rejeitado, o lugar solta-se
 * (ocupa = NULL) e o pedido fica no histórico. A grelha lê o estado daqui, por
 * isso fica logo certa: confirmado, ou o lugar outra vez livre.
 *
 * @param int[]  $ids     As marcações.
 * @param string $decisao aprovada ou rejeitada.
 * @return array As marcações decididas (só as que estavam pendentes).
 */
function jelly_ar_marcacoes_decidir( $ids, $decisao ) {
	global $wpdb;

	if ( ! in_array( $decisao, [ 'aprovada', 'rejeitada' ], true ) ) {
		return [];
	}

	$ids = array_filter( array_map( 'intval', (array) $ids ) );

	if ( ! $ids ) {
		return [];
	}

	$lista  = implode( ',', $ids );
	$tabela = jelly_ar_tabela( 'marcacoes' );
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( "SELECT * FROM {$tabela} WHERE estado = 'pendente' AND ocupa = 1 AND id IN ({$lista})" );

	foreach ( $linhas as $l ) {
		$wpdb->update( // phpcs:ignore WordPress.DB
			$tabela,
			[
				'estado'       => $decisao,
				'ocupa'        => 'aprovada' === $decisao ? 1 : null,
				'decidido_em'  => current_time( 'mysql', true ),
				'decidido_por' => get_current_user_id(),
			],
			[ 'id' => $l->id ]
		);

		jelly_ar_historico_gravar(
			'aprovada' === $decisao ? 'marcacao-aprovada' : 'marcacao-rejeitada',
			[
				'evento_id'    => (int) $l->evento_id,
				'marcacao_id'  => (int) $l->id,
				'associado_id' => (int) $l->user_id,
				'resumo'       => jelly_ar_historico_marcacao( (int) $l->user_id, jelly_ar_mesa_nome( $l->mesa_id ), $l->dia, substr( $l->hora, 0, 5 ) ),
			]
		);
	}

	return array_map( function ( $l ) {
		return [
			'id'        => (int) $l->id,
			'evento_id' => (int) $l->evento_id,
			'mesa_id'   => (int) $l->mesa_id,
			'user_id'   => (int) $l->user_id,
			'dia'       => $l->dia,
			'hora'      => substr( $l->hora, 0, 5 ),
		];
	}, $linhas );
}

/**
 * As marcações para a lista das Aprovações, com o associado, o evento e a
 * mesa; as mais recentes primeiro.
 */
function jelly_ar_marcacoes_todas() {
	global $wpdb;

	$c = jelly_ar_tabela( 'marcacoes' );
	$a = jelly_ar_tabela( 'associados' );
	$e = jelly_ar_tabela( 'eventos' );
	$m = jelly_ar_tabela( 'mesas' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results(
		"SELECT c.*, a.nome, a.apelido, a.empresa, u.user_email, e.titulo AS evento, m.nome AS mesa, m.localizacao
		FROM {$c} c
		LEFT JOIN {$a} a ON a.user_id = c.user_id
		LEFT JOIN {$wpdb->users} u ON u.ID = c.user_id
		LEFT JOIN {$e} e ON e.id = c.evento_id
		LEFT JOIN {$m} m ON m.id = c.mesa_id
		ORDER BY c.pedido_em DESC, c.id DESC"
	);

	return array_map( function ( $l ) {
		return [
			'id'          => (int) $l->id,
			'evento_id'   => (int) $l->evento_id,
			'evento'      => (string) $l->evento,
			'mesa_id'     => (int) $l->mesa_id,
			'mesa'        => (string) $l->mesa,
			'localizacao' => (string) $l->localizacao,
			'user_id'     => (int) $l->user_id,
			'nome'        => trim( $l->nome . ' ' . $l->apelido ),
			'empresa'     => (string) $l->empresa,
			'email'       => (string) $l->user_email,
			'dia'         => $l->dia,
			'hora'        => substr( $l->hora, 0, 5 ),
			'estado'      => $l->estado,
			'pedido'      => get_date_from_gmt( $l->pedido_em, 'd/m/Y H:i' ),
			// As feitas pela equipa na grelha: decididas no mesmo instante em que foram feitas.
			'pela_equipa' => $l->decidido_em && $l->decidido_em === $l->pedido_em,
		];
	}, $linhas );
}

/**
 * O dia (Y-m-d) e a hora (H:i) de um pedido, ou '' se não tiverem a forma certa.
 */
function jelly_ar_bloco_pedido( $dia, $hora ) {
	return [
		preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dia ) ? $dia : '',
		preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $hora ) ? $hora : '',
	];
}


/**
 * Se um evento ainda tem onde marcar, para o badge de Mesas e horários: o que
 * os associados veem no pop-up (jelly_ar_disponibilidade()), ou seja, os
 * lugares livres nos blocos que ainda não passaram.
 *
 * @return array [ 'estado' => disponivel | completo | terminado | sem-grelha, 'livres' => int ]
 */
function jelly_ar_disponibilidade_estado( $evento ) {
	$fim = $evento['fim'] ? $evento['fim'] : $evento['inicio'];

	if ( $fim && $fim < current_time( 'Ymd' ) ) {
		return [ 'estado' => 'terminado', 'livres' => 0 ];
	}

	if ( ! jelly_ar_mesas( $evento['id'] ) || ! jelly_ar_horarios( $evento['id'] ) ) {
		return [ 'estado' => 'sem-grelha', 'livres' => 0 ];
	}

	$livres = 0;
	foreach ( jelly_ar_disponibilidade( $evento ) as $d ) {
		foreach ( $d['blocos'] as $b ) {
			$livres += array_sum( wp_list_pluck( $b['mesas'], 'livres' ) );
		}
	}

	return [ 'estado' => $livres ? 'disponivel' : 'completo', 'livres' => $livres ];
}

/* ---------- O calendário ---------- */

/**
 * As marcações vivas (pendentes e confirmadas) entre dois dias, para o
 * Calendário do back-office: [ Y-m-d => [ marcações, pela hora ] ], cada uma
 * com o associado, o evento e a mesa. Com $evento_id, só as desse evento.
 */
function jelly_ar_marcacoes_entre( $de, $ate, $evento_id = 0 ) {
	global $wpdb;

	$c = jelly_ar_tabela( 'marcacoes' );
	$a = jelly_ar_tabela( 'associados' );
	$e = jelly_ar_tabela( 'eventos' );
	$m = jelly_ar_tabela( 'mesas' );

	$onde = $evento_id ? $wpdb->prepare( ' AND c.evento_id = %d', $evento_id ) : '';

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT c.id, c.evento_id, c.dia, c.hora, c.estado, a.nome, a.apelido, a.empresa, e.titulo AS evento, e.categoria_id, m.nome AS mesa, m.localizacao
			FROM {$c} c
			LEFT JOIN {$a} a ON a.user_id = c.user_id
			LEFT JOIN {$e} e ON e.id = c.evento_id
			LEFT JOIN {$m} m ON m.id = c.mesa_id
			WHERE c.ocupa = 1 AND c.dia BETWEEN %s AND %s{$onde}
			ORDER BY c.dia, c.hora, m.ordem, c.id",
			$de,
			$ate
		)
	);

	$r = [];
	foreach ( $linhas as $l ) {
		$r[ $l->dia ][] = [
			'id'          => (int) $l->id,
			'evento_id'   => (int) $l->evento_id,
			'evento'      => (string) $l->evento,
			'hora'        => substr( $l->hora, 0, 5 ),
			'estado'      => $l->estado,
			'quem'        => trim( $l->nome . ' ' . $l->apelido ),
			'empresa'     => (string) $l->empresa,
			'mesa'        => (string) $l->mesa,
			'localizacao' => (string) $l->localizacao,
		];
	}

	return $r;
}
