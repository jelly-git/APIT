<?php
/**
 * As mesas e os horários de um evento, e a grelha de marcações que sai dos dois.
 *
 * Tudo pertence a um evento (inc/eventos-dados.php) e só serve os que aceitam
 * marcações — o visto "Os associados podem marcar mesas neste evento":
 *
 *   jelly_ar_mesas            as mesas do evento: nome, localização, lugares
 *   jelly_ar_evento_horarios  um dia do evento por linha: a hora de início, a
 *                             de fim e o intervalo entre marcações
 *   jelly_ar_marcacoes        um pedido de um associado para uma mesa, num dia,
 *                             a uma hora (o início de um bloco)
 *
 * Os blocos saem do horário: de hora_inicio a hora_fim, de intervalo em
 * intervalo, e só os que cabem inteiros antes do fim. Um bloco de uma mesa
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
			'lugares'     => (int) $l->lugares,
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
	foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . jelly_ar_tabela( 'evento_horarios' ) . ' WHERE evento_id = %d ORDER BY dia', $evento_id ) ) as $l ) {
		$r[ $l->dia ] = [
			'inicio'    => substr( $l->hora_inicio, 0, 5 ),
			'fim'       => substr( $l->hora_fim, 0, 5 ),
			'intervalo' => (int) $l->intervalo,
		];
	}

	return $r;
}

/**
 * Os blocos de um horário: as horas de início (H:i) dos que cabem inteiros
 * entre o início e o fim.
 */
function jelly_ar_blocos( $horario ) {
	$inicio    = jelly_ar_minutos( $horario['inicio'] );
	$fim       = jelly_ar_minutos( $horario['fim'] );
	$intervalo = max( 5, (int) $horario['intervalo'] );
	$blocos    = [];

	for ( $m = $inicio; $m + $intervalo <= $fim; $m += $intervalo ) {
		$blocos[] = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
	}

	return $blocos;
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

	if ( jelly_ar_bloco_ocupados( $mesa['id'], $dia, $hora ) + count( $users ) > $mesa['lugares'] ) {
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
	}

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
	}

	return $marcacoes;
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
