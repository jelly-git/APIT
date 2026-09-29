<?php
/**
 * As marcações de mesa pedidas pelos associados no site.
 *
 * O botão dos cartões do calendário (jelly_ar_url_marcacao()) aponta sempre
 * para #area-reservada-marcar-<id>, com ou sem sessão: a página pode vir de uma
 * cache, e quem decide o que o pop-up mostra é este pedido, feito no clique:
 *
 * - sem sessão, o pop-up abre o login e volta à marcação depois de entrar;
 * - com a sessão de um associado ativo, os dias, as horas e as mesas com lugar
 *   (jelly_ar_disponibilidade()), com os dias em que já tem marcação marcados
 *   como tal — é uma por dia do evento —; ou, sem nenhum dia por marcar, a
 *   marcação que já tem;
 * - com outra sessão (a da equipa), um aviso: marcar é para associados.
 *
 * O pedido entra pendente (jelly_ar_pedir()) e é aprovado em Aprovações. O
 * associado recebe o e-mail de pedido recebido, e a equipa o aviso.
 */

defined( 'ABSPATH' ) || exit;

/**
 * O resumo de uma marcação para o pop-up: o dia, as horas e a mesa por
 * extenso, e o estado.
 */
function jelly_ar_marcacao_resumo( $evento, $marcacao ) {
	$horarios  = jelly_ar_horarios( $evento['id'] );
	$intervalo = $horarios[ $marcacao['dia'] ]['intervalo'] ?? JELLY_AR_INTERVALO_OMISSAO;
	$mesa      = null;

	foreach ( jelly_ar_mesas( $evento['id'] ) as $m ) {
		if ( $m['id'] === $marcacao['mesa_id'] ) {
			$mesa = $m;
		}
	}

	$d   = DateTime::createFromFormat( '!Y-m-d', $marcacao['dia'] );
	$ts  = $d ? $d->getTimestamp() : 0;
	$fim = jelly_ar_minutos( $marcacao['hora'] ) + $intervalo;

	return [
		'quando'     => jelly_ar_email_quando( $marcacao['dia'], $marcacao['hora'], $intervalo ),
		'mesa'       => $mesa ? jelly_ar_email_mesa( $mesa ) : '—',
		'estado'     => $marcacao['estado'],
		// Em partes, para o cartão da marcação no pop-up: a data em bloco, as horas e a mesa.
		'semana'     => $ts ? jelly_ar_data( 'l', $ts ) : '',
		'dia'        => $ts ? jelly_ar_data( 'j', $ts ) : '',
		'mes'        => $ts ? jelly_ar_data( 'M', $ts ) : '',
		'ano'        => $ts ? jelly_ar_data( 'Y', $ts ) : '',
		'horas'      => $marcacao['hora'] . ' – ' . sprintf( '%02d:%02d', intdiv( $fim, 60 ), $fim % 60 ),
		'mesa_nome'  => $mesa ? $mesa['nome'] : '—',
		'mesa_local' => $mesa ? $mesa['localizacao'] : '',
	];
}

/**
 * Os dias da disponibilidade, com `minha` nos dias em que o associado já tem
 * marcação (uma por dia): o pop-up mostra-os como "Já marcado" e não os deixa
 * escolher.
 */
function jelly_ar_marcacao_dias( $evento, $minhas ) {
	return array_map( function ( $d ) use ( $minhas ) {
		$d['minha'] = isset( $minhas[ $d['dia'] ] );
		return $d;
	}, jelly_ar_disponibilidade( $evento ) );
}

/**
 * O evento do pedido, se aceitar marcações e ainda não tiver passado; ou null.
 */
function jelly_ar_evento_para_marcar( $id ) {
	$evento = jelly_ar_evento( $id );

	if ( ! $evento || empty( $evento['marcacoes'] ) || 'publicado' !== $evento['estado'] ) {
		return null;
	}

	return $evento;
}

/**
 * O que o pop-up precisa para marcar num evento.
 */
function jelly_ar_marcacao_dados() {
	$id = isset( $_GET['evento'] ) ? absint( $_GET['evento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ! is_user_logged_in() ) {
		wp_send_json( [ 'sessao' => false ] );
	}

	$user_id = get_current_user_id();
	$evento  = jelly_ar_evento_para_marcar( $id );

	if ( ! $evento ) {
		wp_send_json( [ 'sessao' => true, 'mensagem' => __( 'Este evento já não aceita marcações.', 'jelly-area-reservada' ) ] );
	}

	$base = [
		'sessao' => true,
		'evento' => [
			'id'     => $evento['id'],
			'titulo' => $evento['titulo'],
			'datas'  => jelly_ar_intervalo_datas( $evento['inicio'], $evento['fim'] ),
			'local'  => $evento['local'],
		],
	];

	if ( 'ativo' !== jelly_ar_associado_estado( $user_id ) || ! jelly_ar_associado( $user_id ) ) {
		wp_send_json( $base + [ 'mensagem' => __( 'A marcação de mesas é feita pelos associados da APIT, com a sessão iniciada na Área Reservada.', 'jelly-area-reservada' ) ] );
	}

	$minhas = jelly_ar_marcacoes_do_associado( $evento['id'], $user_id );
	$dias   = jelly_ar_marcacao_dias( $evento, $minhas );

	// Ainda há um dia em que pode marcar: um sem marcação sua, com algum lugar livre.
	$por_marcar = array_filter( $dias, function ( $d ) {
		if ( $d['minha'] ) {
			return false;
		}
		foreach ( $d['blocos'] as $b ) {
			if ( array_sum( wp_list_pluck( $b['mesas'], 'livres' ) ) ) {
				return true;
			}
		}
		return false;
	} );

	// Sem nenhum dia por marcar, e com marcação: o pop-up mostra a primeira.
	if ( $minhas && ! $por_marcar ) {
		wp_send_json( $base + [ 'marcacao' => jelly_ar_marcacao_resumo( $evento, reset( $minhas ) ) ] );
	}

	wp_send_json(
		$base + [
			'dias'    => $dias,
			// Sem lugares por mesa (JELLY_AR_LUGARES), cada mesa mostra-se livre ou marcada.
			'lugares' => JELLY_AR_LUGARES,
			// O nonce vem aqui, e não na página: a página pode estar numa cache.
			'nonce'   => wp_create_nonce( 'jelly_ar_marcar_' . $evento['id'] ),
		]
	);
}
add_action( 'wp_ajax_jelly_ar_marcacao_dados', 'jelly_ar_marcacao_dados' );
add_action( 'wp_ajax_nopriv_jelly_ar_marcacao_dados', 'jelly_ar_marcacao_dados' );

/**
 * O pedido de marcação: uma mesa, num dia, a uma hora.
 */
function jelly_ar_marcacao_pedir() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado abaixo, com o evento
	$id      = isset( $_POST['evento'] ) ? absint( $_POST['evento'] ) : 0;
	$mesa_id = isset( $_POST['mesa'] ) ? absint( $_POST['mesa'] ) : 0;
	$dia     = isset( $_POST['dia'] ) ? sanitize_text_field( wp_unslash( $_POST['dia'] ) ) : '';
	$hora    = isset( $_POST['hora'] ) ? sanitize_text_field( wp_unslash( $_POST['hora'] ) ) : '';
	// phpcs:enable

	if ( ! is_user_logged_in() ) {
		wp_send_json( [ 'sessao' => false ] );
	}

	if ( ! check_ajax_referer( 'jelly_ar_marcar_' . $id, '_wpnonce', false ) ) {
		wp_send_json( [ 'mensagem' => __( 'A página esteve aberta demasiado tempo. Feche esta janela e volte a abrir a marcação.', 'jelly-area-reservada' ) ], 403 );
	}

	$user_id = get_current_user_id();
	$evento  = jelly_ar_evento_para_marcar( $id );

	if ( ! $evento || 'ativo' !== jelly_ar_associado_estado( $user_id ) ) {
		wp_send_json( [ 'mensagem' => __( 'Não é possível marcar neste evento.', 'jelly-area-reservada' ) ], 403 );
	}

	list( $dia, $hora ) = jelly_ar_bloco_pedido( $dia, $hora );

	$mesa = jelly_ar_pedir( $evento, $user_id, $mesa_id, $dia, $hora );

	if ( is_wp_error( $mesa ) ) {
		$mensagens = [
			'marcacao-fechada' => __( 'Este evento já não aceita marcações.', 'jelly-area-reservada' ),
			'marcacao-tem'     => __( 'Já existe uma marcação sua nesse dia (é uma por dia). Escolha outro dia, por favor.', 'jelly-area-reservada' ),
			'marcacao-bloco'   => __( 'Esse horário já não está disponível. Escolha outro, por favor.', 'jelly-area-reservada' ),
			'marcacao-cheia'   => __( 'Entretanto, essa mesa ficou marcada a essa hora. Escolha outra mesa ou outra hora, por favor.', 'jelly-area-reservada' ),
			'marcacao-ocupado' => __( 'Há outro pedido a ser tratado para esse horário. Tente de novo dentro de momentos.', 'jelly-area-reservada' ),
		];

		wp_send_json(
			[
				'mensagem' => $mensagens[ $mesa->get_error_code() ] ?? __( 'Não foi possível fazer o pedido.', 'jelly-area-reservada' ),
				// Com os lugares mudados, o pop-up redesenha a escolha com os de agora.
				'dias'     => jelly_ar_marcacao_dias( $evento, jelly_ar_marcacoes_do_associado( $evento['id'], $user_id ) ),
			]
		);
	}

	// Os dois e-mails saem depois da resposta (jelly_ar_email_depois()): o pop-up confirma logo.
	jelly_ar_email_depois( 'jelly_ar_email_marcacao', [ $user_id, 'pedida', $evento, $mesa, $dia, $hora ] );
	jelly_ar_email_depois( 'jelly_ar_email_marcacao_equipa', [ $user_id, $evento, $mesa, $dia, $hora ] );

	wp_send_json(
		[
			'sucesso'  => true,
			'marcacao' => jelly_ar_marcacao_resumo( $evento, jelly_ar_marcacao_do_associado( $evento['id'], $user_id, $dia ) ),
		]
	);
}
add_action( 'wp_ajax_jelly_ar_marcacao_pedir', 'jelly_ar_marcacao_pedir' );
add_action( 'wp_ajax_nopriv_jelly_ar_marcacao_pedir', 'jelly_ar_marcacao_pedir' );
