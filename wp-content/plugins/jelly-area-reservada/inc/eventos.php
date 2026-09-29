<?php
/**
 * Eventos no back-office: a lista, com os filtros e a ordem, e a gravação.
 *
 * Os eventos estão na tabela jelly_ar_eventos (inc/instalar.php) e leem-se
 * por inc/eventos-dados.php, de onde também o calendário e a pesquisa do site
 * os tiram. Um evento só, para o site e para a AR: no site aparecem todos os
 * publicados; na Área Reservada, os que aceitam marcações. O que os distingue:
 *
 *   fim         o último dia; sem ele, o evento é de um dia. Com ele, fica no
 *               calendário até ao fim desse dia, e a linha das datas mostra o
 *               intervalo ("6–9 out 2026")
 *   marcacoes   1 se os associados podem marcar mesas. Só com ela o cartão do
 *               calendário tem botão, e o link é o da marcação
 *               (jelly_ar_botao_do_evento()); botao_texto é o texto dele
 *   estado      publicado | rascunho | lixo. O lixo não apaga: o evento sai
 *               das listas e do site, e a linha fica
 *
 * As categorias, com as cores dos cartões, estão em jelly_ar_evento_categorias
 * e geridas em Eventos → Categorias (inc/eventos-categorias.php).
 */

defined( 'ABSPATH' ) || exit;

// Ler os eventos e as categorias: inc/eventos-dados.php (jelly_ar_eventos_todos(), jelly_ar_evento(), jelly_ar_evento_categorias()).

/* ---------- A lista ---------- */

function jelly_ar_eventos_lista() {
	return new Jelly_AR_Lista(
		'eventos',
		[
			'quando'    => 'proximos',
			'categoria' => '',
		],
		'data',
		function () {
			return [ 'titulo', 'data', 'local' ];
		},
		[
			'quando'    => [ 'proximos', 'passados', 'todos' ],
			'categoria' => array_keys( jelly_ar_evento_categorias() ),
		]
	);
}

/**
 * A pesquisa, os filtros e a ordem. Os próximos são os que ainda não
 * acabaram — um evento a decorrer hoje ainda é próximo.
 */
function jelly_ar_eventos_filtrar( $eventos, $pedido ) {
	$hoje = current_time( 'Ymd' );

	$encontrados = array_values( array_filter( $eventos, function ( $e ) use ( $pedido ) {
		if ( $pedido['categoria'] && $e['categoria'] !== $pedido['categoria'] ) {
			return false;
		}

		return '' === $pedido['q'] || Jelly_AR_Lista::contem( $pedido['q'], [ $e['titulo'], $e['local'], $e['resumo'], $e['categoria_nome'] ] );
	} ) );

	$quando = function ( $e ) use ( $hoje ) {
		return $e['fim'] >= $hoje ? 'proximos' : 'passados';
	};

	$lista = 'todos' === $pedido['quando'] ? $encontrados : array_values( array_filter( $encontrados, function ( $e ) use ( $quando, $pedido ) {
		return $quando( $e ) === $pedido['quando'];
	} ) );

	$ordenar = $pedido['ordenar'];
	$ordem   = $pedido['ordem'];

	usort( $lista, function ( $a, $b ) use ( $ordenar, $ordem ) {
		if ( 'data' === $ordenar ) {
			$r = $a['inicio'] <=> $b['inicio'];
			return 'desc' === $ordem ? -$r : $r;
		}

		return Jelly_AR_Lista::comparar_texto( $a[ $ordenar ], $b[ $ordenar ], $ordem );
	} );

	return [
		'encontrados' => $encontrados,
		'lista'       => $lista,
		'contagem'    => array_count_values( array_map( $quando, $encontrados ) ),
	];
}

/* ---------- Guardar ---------- */

/**
 * O formulário do evento — novo ou a editar — chega aqui (admin-post.php).
 */
function jelly_ar_evento_guardar() {
	global $wpdb;

	$id     = isset( $_POST['evento'] ) ? absint( $_POST['evento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$voltar = function ( $erro ) use ( $id ) {
		wp_safe_redirect( jelly_ar_admin_url( 'eventos', $id ? [ 'evento' => $id, 'editar' => 1, 'erro' => $erro ] : [ 'novo' => 1, 'erro' => $erro ] ) );
		exit;
	};

	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( 'jelly_ar_evento_guardar_' . $id );

	if ( $id && ! jelly_ar_evento( $id ) ) {
		wp_die( esc_html__( 'Esse evento não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	$texto = function ( $campo ) {
		return isset( $_POST[ $campo ] ) ? sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	};

	// As datas chegam do <input type="date"> como Y-m-d, que é o que a coluna date guarda.
	$data = function ( $campo ) use ( $texto ) {
		$d = DateTime::createFromFormat( '!Y-m-d', $texto( $campo ) );
		return $d ? $d->format( 'Y-m-d' ) : '';
	};

	$titulo     = $texto( 'titulo' );
	$categoria  = isset( $_POST['categoria'] ) ? absint( $_POST['categoria'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$inicio     = $data( 'inicio' );
	$fim        = $data( 'fim' );
	$resumo     = isset( $_POST['resumo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['resumo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$categorias = wp_list_pluck( jelly_ar_evento_categorias(), 'id' );

	if ( '' === $titulo || '' === $inicio || ! in_array( $categoria, $categorias, true ) ) {
		$voltar( 'campos' );
	}

	if ( $fim && $fim < $inicio ) {
		$voltar( 'datas' );
	}

	$agora = current_time( 'mysql', true );
	$linha = [
		'titulo'        => $titulo,
		'resumo'        => $resumo,
		'categoria_id'  => $categoria,
		'inicio'        => $inicio,
		'fim'           => $fim && $fim !== $inicio ? $fim : null,
		'local'         => mb_substr( $texto( 'local' ), 0, 150 ),
		'marcacoes'     => empty( $_POST['marcacoes'] ) ? 0 : 1, // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'botao_texto'   => mb_substr( $texto( 'acao_texto' ), 0, 60 ),
		'atualizado_em' => $agora,
	];
	$novo = ! $id;

	if ( $novo ) {
		// Um evento novo publica-se já, ou fica em rascunho, conforme se escolheu.
		$linha['estado']    = isset( $_POST['estado'] ) && 'rascunho' === $_POST['estado'] ? 'rascunho' : 'publicado'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$linha['autor_id']  = get_current_user_id();
		$linha['criado_em'] = $agora;

		$feito = $wpdb->insert( jelly_ar_tabela( 'eventos' ), $linha ); // phpcs:ignore WordPress.DB
		$id    = (int) $wpdb->insert_id;
	} else {
		$feito = $wpdb->update( jelly_ar_tabela( 'eventos' ), $linha, [ 'id' => $id ] ); // phpcs:ignore WordPress.DB
	}

	if ( false === $feito || ! $id ) {
		$voltar( 'falhou' );
	}

	wp_safe_redirect( jelly_ar_admin_url( 'eventos', [ 'evento' => $id, 'aviso' => $novo ? 'criado' : 'atualizado' ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_evento_guardar', 'jelly_ar_evento_guardar' );

/**
 * Publicar, passar a rascunho ou mandar para o lixo. O lixo não apaga a
 * linha: o evento sai das listas e do site, e fica para se poder recuperar.
 */
function jelly_ar_evento_estado() {
	global $wpdb;

	$id   = isset( $_POST['evento'] ) ? absint( $_POST['evento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$acao = isset( $_POST['estado'] ) ? sanitize_key( wp_unslash( $_POST['estado'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$para = [ 'publicar' => 'publicado', 'rascunho' => 'rascunho', 'lixo' => 'lixo' ];

	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( 'jelly_ar_evento_estado_' . $id );

	if ( ! jelly_ar_evento( $id ) || ! isset( $para[ $acao ] ) ) {
		wp_die( esc_html__( 'Esse evento não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	$wpdb->update( jelly_ar_tabela( 'eventos' ), [ 'estado' => $para[ $acao ], 'atualizado_em' => current_time( 'mysql', true ) ], [ 'id' => $id ] ); // phpcs:ignore WordPress.DB

	if ( 'lixo' === $acao ) {
		wp_safe_redirect( jelly_ar_admin_url( 'eventos', [ 'aviso' => 'lixo' ] ) );
		exit;
	}

	wp_safe_redirect( jelly_ar_admin_url( 'eventos', [ 'evento' => $id, 'aviso' => 'publicar' === $acao ? 'publicado' : 'rascunho' ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_evento_estado', 'jelly_ar_evento_estado' );
