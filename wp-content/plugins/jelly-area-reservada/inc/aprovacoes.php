<?php
/**
 * Aprovações: os pedidos de marcação de mesa dos associados, para aprovar ou
 * rejeitar — um a um, ou vários de uma vez. A lista segue as outras do
 * back-office (inc/lista.php): o estado no endereço, a pesquisa, os filtros e
 * a ordem.
 *
 * Aprovar ou rejeitar muda o estado da marcação (jelly_ar_marcacoes_decidir(),
 * inc/mesas-dados.php), e a grelha de Mesas e horários, que lê esse estado,
 * fica logo certa: o lugar passa a confirmado, ou volta a estar livre. Cada
 * associado recebe o e-mail da decisão.
 */

defined( 'ABSPATH' ) || exit;

const JELLY_AR_MARCACAO_ESTADOS = [ 'pendente', 'aprovada', 'rejeitada', 'cancelada' ];

function jelly_ar_aprovacoes_lista() {
	return new Jelly_AR_Lista(
		'marcacoes',
		[
			'estado' => 'pendente',
			'evento' => '',
		],
		'pedido',
		function ( $valores ) {
			$ordenaveis = [ 'nome', 'evento', 'quando', 'pedido' ];

			if ( '' === $valores['estado'] ) {
				$ordenaveis[] = 'estado';
			}

			return $ordenaveis;
		},
		[
			'estado' => JELLY_AR_MARCACAO_ESTADOS,
		],
		'desc'
	);
}

/**
 * Aplica a pesquisa, o evento, o estado e a ordem. `encontrados` leva só a
 * pesquisa e o evento, para os números dos separadores de estado.
 */
function jelly_ar_aprovacoes_filtrar( $marcacoes, $pedido ) {
	$encontrados = array_values( array_filter( $marcacoes, function ( $m ) use ( $pedido ) {
		if ( '' !== $pedido['evento'] && (int) $pedido['evento'] !== $m['evento_id'] ) {
			return false;
		}

		return '' === $pedido['q'] || Jelly_AR_Lista::contem( $pedido['q'], [ $m['nome'], $m['empresa'], $m['email'], $m['evento'], $m['mesa'] ] );
	} ) );

	$lista = $pedido['estado'] ? array_values( array_filter( $encontrados, function ( $m ) use ( $pedido ) {
		return $m['estado'] === $pedido['estado'];
	} ) ) : $encontrados;

	$ordenar = $pedido['ordenar'];
	$ordem   = $pedido['ordem'];

	usort( $lista, function ( $a, $b ) use ( $ordenar, $ordem ) {
		switch ( $ordenar ) {
			case 'nome':
				return Jelly_AR_Lista::comparar_texto( $a['nome'], $b['nome'], $ordem );
			case 'evento':
				return Jelly_AR_Lista::comparar_texto( $a['evento'], $b['evento'], $ordem );
			case 'quando':
				$r = ( $a['dia'] . $a['hora'] ) <=> ( $b['dia'] . $b['hora'] );
				break;
			case 'estado':
				$r = array_search( $a['estado'], JELLY_AR_MARCACAO_ESTADOS, true ) <=> array_search( $b['estado'], JELLY_AR_MARCACAO_ESTADOS, true );
				break;
			default:
				$r = jelly_ar_data_ordenavel( $a['pedido'] ) <=> jelly_ar_data_ordenavel( $b['pedido'] );
		}

		return 'desc' === $ordem ? -$r : $r;
	} );

	return [
		'encontrados' => $encontrados,
		'lista'       => $lista,
	];
}

/**
 * Aprovar ou rejeitar. Os escolhidos vêm em marcacao[]; o botão Aprovar de uma
 * linha manda o id dela em `aprovar`, o de cima manda "escolhidas". Sem
 * `aprovar`, é rejeitar — o que a confirmação envia.
 */
function jelly_ar_aprovacoes_decidir() {
	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( 'jelly_ar_aprovacoes' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado acima
	$aprovar = isset( $_POST['aprovar'] ) ? sanitize_key( wp_unslash( $_POST['aprovar'] ) ) : '';
	$ids     = isset( $_POST['marcacao'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['marcacao'] ) ) : [];
	$voltar  = isset( $_POST['voltar'] ) ? esc_url_raw( wp_unslash( $_POST['voltar'] ) ) : '';
	// phpcs:enable

	if ( ctype_digit( $aprovar ) ) {
		$ids = [ (int) $aprovar ];
	}

	$decisao   = '' !== $aprovar ? 'aprovada' : 'rejeitada';
	$decididas = jelly_ar_marcacoes_decidir( $ids, $decisao );
	$falhou    = 0;
	$eventos   = [];

	foreach ( $decididas as $d ) {
		if ( ! isset( $eventos[ $d['evento_id'] ] ) ) {
			$eventos[ $d['evento_id'] ] = [ jelly_ar_evento( $d['evento_id'] ), [] ];
			foreach ( jelly_ar_mesas( $d['evento_id'] ) as $m ) {
				$eventos[ $d['evento_id'] ][1][ $m['id'] ] = $m;
			}
		}

		list( $evento, $mesas ) = $eventos[ $d['evento_id'] ];

		if ( $evento && isset( $mesas[ $d['mesa_id'] ] ) ) {
			$falhou += jelly_ar_email_marcacao( $d['user_id'], $decisao, $evento, $mesas[ $d['mesa_id'] ], $d['dia'], $d['hora'] ) ? 0 : 1;
		}
	}

	// De volta à lista como estava — o separador, o evento, a pesquisa —, sem o aviso da vez anterior.
	$destino = $voltar && 0 === strpos( $voltar, admin_url() ) ? $voltar : jelly_ar_admin_url( 'marcacoes' );
	$destino = remove_query_arg( [ 'aviso', 'n', 'sem-email' ], $destino );

	wp_safe_redirect(
		add_query_arg(
			$decididas
				? [ 'aviso' => $decisao, 'n' => count( $decididas ), 'sem-email' => $falhou ]
				: [ 'aviso' => 'nenhuma' ],
			$destino
		)
	);
	exit;
}
add_action( 'admin_post_jelly_ar_aprovacoes', 'jelly_ar_aprovacoes_decidir' );
