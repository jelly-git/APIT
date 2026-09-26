<?php
/**
 * Mesas e horários no back-office: criar, mudar e apagar as mesas de um evento,
 * e gravar os horários de cada dia. Os dados e a grelha estão em
 * inc/mesas-dados.php; o ecrã, em templates/admin/mesas.php.
 *
 * O que já tem marcações não se estraga: uma mesa com marcações não se apaga,
 * e um horário não muda de forma a deixar uma marcação fora dos blocos — nem
 * tirando o dia, nem encolhendo as horas, nem mudando o intervalo.
 */

defined( 'ABSPATH' ) || exit;

function jelly_ar_mesas_url( $evento_id, $args = [] ) {
	return jelly_ar_admin_url( 'mesas', array_merge( [ 'evento' => (int) $evento_id ], $args ) );
}

/**
 * O que os formulários têm em comum: administrador, o nonce, e um evento que
 * existe. Devolve o evento; $voltar recebe os argumentos do endereço de volta.
 */
function jelly_ar_mesas_pedido( $nonce, $separador ) {
	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	$id = isset( $_POST['evento'] ) ? absint( $_POST['evento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	check_admin_referer( $nonce . '_' . $id );

	$evento = jelly_ar_evento( $id );

	if ( ! $evento ) {
		wp_die( esc_html__( 'Esse evento não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	$voltar = function ( $args ) use ( $id, $separador ) {
		$url = jelly_ar_mesas_url( $id, $args + [ 'separador' => $separador ] );

		// Na grelha, de volta ao dia do bloco (id="jar-dia-<dia>").
		if ( 'grelha' === $separador && ! empty( $args['dia'] ) ) {
			$url .= '#jar-dia-' . $args['dia'];
		}

		wp_safe_redirect( $url );
		exit;
	};

	return [ $evento, $voltar ];
}

/**
 * O nome, a localização e os lugares do pedido, limpos. Sem nome: erro.
 */
function jelly_ar_mesa_campos( $voltar ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado em jelly_ar_mesas_pedido()
	$nome        = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
	$localizacao = isset( $_POST['localizacao'] ) ? sanitize_text_field( wp_unslash( $_POST['localizacao'] ) ) : '';
	$lugares     = isset( $_POST['lugares'] ) ? absint( $_POST['lugares'] ) : 4;
	// phpcs:enable

	if ( '' === $nome ) {
		$voltar( [ 'erro' => 'mesa-nome' ] );
	}

	return [
		'nome'        => mb_substr( $nome, 0, 80 ),
		'localizacao' => mb_substr( $localizacao, 0, 150 ),
		'lugares'     => min( 50, max( 1, $lugares ) ),
	];
}

function jelly_ar_mesa_criar() {
	global $wpdb;

	list( $evento, $voltar ) = jelly_ar_mesas_pedido( 'jelly_ar_mesa_criar', 'mesas' );

	$tabela = jelly_ar_tabela( 'mesas' );
	$campos = jelly_ar_mesa_campos( $voltar );
	$ordem  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(ordem), 0) FROM {$tabela} WHERE evento_id = %d", $evento['id'] ) ); // phpcs:ignore WordPress.DB

	// A nova vai para o fim.
	$wpdb->insert( $tabela, $campos + [ 'evento_id' => $evento['id'], 'ordem' => $ordem + 1 ] ); // phpcs:ignore WordPress.DB

	$voltar( [ 'aviso' => 'mesa-criada' ] );
}
add_action( 'admin_post_jelly_ar_mesa_criar', 'jelly_ar_mesa_criar' );

/**
 * A mesa do pedido, se for deste evento. Um id de outro evento conta como
 * nenhum.
 */
function jelly_ar_mesa_do_pedido( $evento, $voltar ) {
	$id = isset( $_POST['mesa'] ) ? absint( $_POST['mesa'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	foreach ( jelly_ar_mesas( $evento['id'] ) as $m ) {
		if ( $m['id'] === $id ) {
			return $m;
		}
	}

	$voltar( [ 'erro' => 'mesa-falhou' ] );
}

function jelly_ar_mesa_editar() {
	global $wpdb;

	list( $evento, $voltar ) = jelly_ar_mesas_pedido( 'jelly_ar_mesa_editar', 'mesas' );

	$mesa   = jelly_ar_mesa_do_pedido( $evento, $voltar );
	$campos = jelly_ar_mesa_campos( $voltar );

	// Os lugares não descem abaixo dos ocupados no bloco mais cheio da mesa.
	$cheio = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) AS n FROM ' . jelly_ar_tabela( 'marcacoes' ) . ' WHERE mesa_id = %d AND ocupa = 1 GROUP BY dia, hora ORDER BY n DESC LIMIT 1', $mesa['id'] ) ); // phpcs:ignore WordPress.DB

	if ( $campos['lugares'] < $cheio ) {
		$voltar( [ 'erro' => 'mesa-lugares', 'n' => $cheio ] );
	}

	$wpdb->update( jelly_ar_tabela( 'mesas' ), $campos, [ 'id' => $mesa['id'] ] ); // phpcs:ignore WordPress.DB

	$voltar( [ 'aviso' => 'mesa-atualizada' ] );
}
add_action( 'admin_post_jelly_ar_mesa_editar', 'jelly_ar_mesa_editar' );

function jelly_ar_mesa_apagar() {
	global $wpdb;

	list( $evento, $voltar ) = jelly_ar_mesas_pedido( 'jelly_ar_mesa_apagar', 'mesas' );

	$mesa = jelly_ar_mesa_do_pedido( $evento, $voltar );

	// A mesma regra do ecrã: com marcações — também as rejeitadas, que são histórico —, não se apaga.
	if ( $mesa['marcacoes'] > 0 ) {
		$voltar( [ 'erro' => 'mesa-em-uso' ] );
	}

	$wpdb->delete( jelly_ar_tabela( 'mesas' ), [ 'id' => $mesa['id'] ], [ '%d' ] ); // phpcs:ignore WordPress.DB

	$voltar( [ 'aviso' => 'mesa-apagada' ] );
}
add_action( 'admin_post_jelly_ar_mesa_apagar', 'jelly_ar_mesa_apagar' );

/**
 * Os horários de todos os dias do evento, num só formulário: cada dia com o
 * visto "há marcações neste dia", a hora de início e a de fim; o intervalo é
 * um para o evento todo.
 *
 * Antes de gravar, confirma-se que nenhuma marcação fica de fora: o seu dia
 * continua marcado, a sua hora cabe entre o início e o fim, e cai num bloco
 * do intervalo novo. Se alguma ficar, nada se grava.
 */
function jelly_ar_horarios_guardar() {
	global $wpdb;

	list( $evento, $voltar ) = jelly_ar_mesas_pedido( 'jelly_ar_horarios_guardar', 'horarios' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado em jelly_ar_mesas_pedido()
	$ativos    = isset( $_POST['dia'] ) ? array_map( 'sanitize_text_field', array_keys( (array) wp_unslash( $_POST['dia'] ) ) ) : [];
	$inicios   = isset( $_POST['inicio'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['inicio'] ) ) : [];
	$fins      = isset( $_POST['fim'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['fim'] ) ) : [];
	$intervalo = isset( $_POST['intervalo'] ) ? absint( $_POST['intervalo'] ) : JELLY_AR_INTERVALO_OMISSAO;
	// phpcs:enable

	if ( ! in_array( $intervalo, JELLY_AR_INTERVALOS, true ) ) {
		$intervalo = JELLY_AR_INTERVALO_OMISSAO;
	}

	$hora = function ( $v ) {
		return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $v ) ? $v : '';
	};

	// Só os dias do evento contam; um dia de fora que chegue no pedido ignora-se.
	$novos = [];
	foreach ( jelly_ar_evento_dias( $evento ) as $dia ) {
		if ( ! in_array( $dia, $ativos, true ) ) {
			continue;
		}

		$inicio = $hora( $inicios[ $dia ] ?? '' );
		$fim    = $hora( $fins[ $dia ] ?? '' );

		if ( ! $inicio || ! $fim || jelly_ar_minutos( $fim ) - jelly_ar_minutos( $inicio ) < $intervalo ) {
			$voltar( [ 'erro' => 'horario-horas', 'dia' => $dia ] );
		}

		// As horas vão ao passo do intervalo: com 30 minutos, 10:00 ou 10:30, e não 10:15.
		$passo = jelly_ar_passo_horas( $intervalo );
		if ( jelly_ar_minutos( $inicio ) % $passo || jelly_ar_minutos( $fim ) % $passo ) {
			$voltar( [ 'erro' => 'horario-passo', 'dia' => $dia ] );
		}

		$novos[ $dia ] = [ 'inicio' => $inicio, 'fim' => $fim, 'intervalo' => $intervalo ];
	}

	// Sem nenhum dia não há onde marcar: o ecrã já avisa, e aqui recusa-se o mesmo.
	if ( ! $novos ) {
		$voltar( [ 'erro' => 'horario-nenhum' ] );
	}

	// Nenhuma marcação pode ficar fora dos blocos novos.
	foreach ( jelly_ar_marcacoes_grelha( $evento['id'] ) as $por_dia ) {
		foreach ( $por_dia as $dia => $por_hora ) {
			$blocos = isset( $novos[ $dia ] ) ? jelly_ar_blocos( $novos[ $dia ] ) : [];

			foreach ( array_keys( $por_hora ) as $h ) {
				if ( ! in_array( $h, $blocos, true ) ) {
					$voltar( [ 'erro' => 'horario-marcacoes', 'dia' => $dia ] );
				}
			}
		}
	}

	$tabela = jelly_ar_tabela( 'evento_horarios' );
	$wpdb->delete( $tabela, [ 'evento_id' => $evento['id'] ], [ '%d' ] ); // phpcs:ignore WordPress.DB

	foreach ( $novos as $dia => $h ) {
		$wpdb->insert( // phpcs:ignore WordPress.DB
			$tabela,
			[
				'evento_id'   => $evento['id'],
				'dia'         => $dia,
				'hora_inicio' => $h['inicio'] . ':00',
				'hora_fim'    => $h['fim'] . ':00',
				'intervalo'   => $h['intervalo'],
			]
		);
	}

	$voltar( [ 'aviso' => 'horarios' ] );
}
add_action( 'admin_post_jelly_ar_horarios_guardar', 'jelly_ar_horarios_guardar' );

/* ---------- As marcações de um bloco da grelha ---------- */

/*
 * Um clique num bloco da grelha abre a janela desse bloco
 * (templates/admin/mesas.php): com lugares livres, a equipa marca associados;
 * com marcações, remove-as ou muda-as para outro bloco. Cada associado afetado
 * recebe um e-mail (jelly_ar_email_marcacao()). As regras — os lugares da
 * mesa, ninguém em duas mesas à mesma hora — estão em inc/mesas-dados.php.
 */

/**
 * O dia (Y-m-d) e a hora (H:i) de um pedido, ou '' se não tiverem a forma certa.
 */
function jelly_ar_bloco_pedido( $dia, $hora ) {
	return [
		preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dia ) ? $dia : '',
		preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $hora ) ? $hora : '',
	];
}

function jelly_ar_marcacao_criar() {
	list( $evento, $voltar ) = jelly_ar_mesas_pedido( 'jelly_ar_marcacoes', 'grelha' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado em jelly_ar_mesas_pedido()
	$mesa_id = isset( $_POST['mesa'] ) ? absint( $_POST['mesa'] ) : 0;
	$users   = isset( $_POST['user'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['user'] ) ) : [];
	list( $dia, $hora ) = jelly_ar_bloco_pedido(
		isset( $_POST['dia'] ) ? sanitize_text_field( wp_unslash( $_POST['dia'] ) ) : '',
		isset( $_POST['hora'] ) ? sanitize_text_field( wp_unslash( $_POST['hora'] ) ) : ''
	);
	// phpcs:enable

	$marcados = jelly_ar_marcar( $evento['id'], $mesa_id, $dia, $hora, $users );

	if ( is_wp_error( $marcados ) ) {
		$voltar( [ 'erro' => $marcados->get_error_code(), 'dia' => $dia ] );
	}

	$mesa   = jelly_ar_bloco_valido( $evento['id'], $mesa_id, $dia, $hora );
	$falhou = 0;

	foreach ( $marcados as $u ) {
		$falhou += jelly_ar_email_marcacao( $u, 'marcada', $evento, $mesa, $dia, $hora ) ? 0 : 1;
	}

	$voltar( [ 'aviso' => 'marcacao-criada', 'n' => count( $marcados ), 'sem-email' => $falhou, 'dia' => $dia ] );
}
add_action( 'admin_post_jelly_ar_marcacao_criar', 'jelly_ar_marcacao_criar' );

/**
 * Remover ou mudar as marcações escolhidas de um bloco: operacao=remover, ou
 * operacao=mover com destino="<mesa>|<Y-m-d>|<H:i>".
 */
function jelly_ar_marcacoes_alterar() {
	list( $evento, $voltar ) = jelly_ar_mesas_pedido( 'jelly_ar_marcacoes', 'grelha' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado em jelly_ar_mesas_pedido()
	$operacao = isset( $_POST['operacao'] ) ? sanitize_key( wp_unslash( $_POST['operacao'] ) ) : '';
	$ids      = isset( $_POST['marcacao'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['marcacao'] ) ) : [];
	$destino  = isset( $_POST['destino'] ) ? explode( '|', sanitize_text_field( wp_unslash( $_POST['destino'] ) ) ) : [];
	// phpcs:enable

	$mesas = [];
	foreach ( jelly_ar_mesas( $evento['id'] ) as $m ) {
		$mesas[ $m['id'] ] = $m;
	}

	$falhou = 0;

	if ( 'remover' === $operacao ) {
		$removidas = jelly_ar_marcacoes_remover( $evento['id'], $ids );

		if ( ! $removidas ) {
			$voltar( [ 'erro' => 'marcacao-ninguem' ] );
		}

		foreach ( $removidas as $r ) {
			$falhou += jelly_ar_email_marcacao( $r['user_id'], 'cancelada', $evento, $mesas[ $r['mesa_id'] ], $r['dia'], $r['hora'] ) ? 0 : 1;
		}

		$voltar( [ 'aviso' => 'marcacao-removida', 'n' => count( $removidas ), 'sem-email' => $falhou, 'dia' => $removidas[0]['dia'] ] );
	}

	if ( 'mover' !== $operacao || 3 !== count( $destino ) ) {
		$voltar( [ 'erro' => 'marcacao-bloco' ] );
	}

	list( $dia, $hora ) = jelly_ar_bloco_pedido( $destino[1], $destino[2] );
	$mesa_id            = absint( $destino[0] );

	$mudadas = jelly_ar_marcacoes_mover( $evento['id'], $ids, $mesa_id, $dia, $hora );

	if ( is_wp_error( $mudadas ) ) {
		$voltar( [ 'erro' => $mudadas->get_error_code(), 'dia' => $dia ] );
	}

	foreach ( $mudadas as $m ) {
		$antes   = [ 'mesa' => $mesas[ $m['mesa_id'] ], 'dia' => $m['dia'], 'hora' => $m['hora'] ];
		$falhou += jelly_ar_email_marcacao( $m['user_id'], 'mudada', $evento, $mesas[ $mesa_id ], $dia, $hora, $antes ) ? 0 : 1;
	}

	$voltar( [ 'aviso' => 'marcacao-mudada', 'n' => count( $mudadas ), 'sem-email' => $falhou, 'dia' => $dia ] );
}
add_action( 'admin_post_jelly_ar_marcacoes_alterar', 'jelly_ar_marcacoes_alterar' );
