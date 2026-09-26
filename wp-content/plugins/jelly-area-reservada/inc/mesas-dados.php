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
 * intervalo, e só os que cabem inteiros antes do fim. Uma marcação ocupa um
 * bloco de uma mesa enquanto não for rejeitada nem cancelada (ocupa = 1; a
 * chave única da tabela impede duas no mesmo bloco).
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
 * As marcações que ocupam blocos (as não rejeitadas nem canceladas), para a
 * grelha: [ mesa_id ][ dia ][ H:i ] => estado e quem.
 */
function jelly_ar_marcacoes_grelha( $evento_id ) {
	global $wpdb;

	$c = jelly_ar_tabela( 'marcacoes' );
	$a = jelly_ar_tabela( 'associados' );
	$r = [];

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( $wpdb->prepare( "SELECT c.mesa_id, c.dia, c.hora, c.estado, c.user_id, a.nome, a.apelido, a.empresa FROM {$c} c LEFT JOIN {$a} a ON a.user_id = c.user_id WHERE c.evento_id = %d AND c.ocupa = 1", $evento_id ) );

	foreach ( $linhas as $l ) {
		$r[ (int) $l->mesa_id ][ $l->dia ][ substr( $l->hora, 0, 5 ) ] = [
			'estado'  => $l->estado,
			'quem'    => trim( $l->nome . ' ' . $l->apelido ),
			'empresa' => (string) $l->empresa,
		];
	}

	return $r;
}

/**
 * O resumo de um evento para as listas: mesas, dias com horário, blocos que se
 * podem marcar (mesas × blocos de cada dia) e quantos estão ocupados.
 */
function jelly_ar_mesas_resumo( $evento_id ) {
	global $wpdb;

	$mesas    = count( jelly_ar_mesas( $evento_id ) );
	$horarios = jelly_ar_horarios( $evento_id );
	$blocos   = 0;

	foreach ( $horarios as $h ) {
		$blocos += count( jelly_ar_blocos( $h ) ) * $mesas;
	}

	$contagem = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(estado = 'aprovada') AS confirmadas, SUM(estado = 'pendente') AS pendentes FROM " . jelly_ar_tabela( 'marcacoes' ) . ' WHERE evento_id = %d AND ocupa = 1', $evento_id ) ); // phpcs:ignore WordPress.DB

	return [
		'mesas'       => $mesas,
		'dias'        => count( $horarios ),
		'blocos'      => $blocos,
		'confirmadas' => (int) ( $contagem->confirmadas ?? 0 ),
		'pendentes'   => (int) ( $contagem->pendentes ?? 0 ),
	];
}
