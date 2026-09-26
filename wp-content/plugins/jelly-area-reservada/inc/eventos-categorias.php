<?php
/**
 * As categorias dos eventos: criar, mudar e apagar, no ecrã Eventos →
 * Categorias — como as dos documentos, com as duas cores a mais.
 *
 * Estão na tabela jelly_ar_evento_categorias (inc/instalar.php). Cada uma dá
 * ao cartão do calendário do site o gradiente: a cor de início e a de fim.
 *
 * Mudar o nome não muda o slug, que é o que os filtros guardados usam.
 *
 * Uma categoria com eventos não se apaga — e contam todos: publicados,
 * rascunhos e os que estão no lixo, que se recuperados ficariam sem ela.
 */

defined( 'ABSPATH' ) || exit;

// As cores de uma categoria: jelly_ar_evento_categoria_cores(), em inc/eventos-dados.php.

/**
 * Quantos eventos usam a categoria, em qualquer estado, lixo incluído.
 */
function jelly_ar_evento_categoria_uso( $id ) {
	global $wpdb;

	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . jelly_ar_tabela( 'eventos' ) . ' WHERE categoria_id = %d', $id ) ); // phpcs:ignore WordPress.DB
}

function jelly_ar_evento_categorias_com_contagem() {
	global $wpdb;

	$c = jelly_ar_tabela( 'evento_categorias' );
	$e = jelly_ar_tabela( 'eventos' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( "SELECT c.*, COUNT(e.id) AS eventos FROM {$c} c LEFT JOIN {$e} e ON e.categoria_id = c.id GROUP BY c.id ORDER BY c.nome" );

	return array_map( function ( $l ) {
		return [
			'id'      => (int) $l->id,
			'nome'    => $l->nome,
			'slug'    => $l->slug,
			'cores'   => [ 'inicio' => $l->cor_inicio, 'fim' => $l->cor_fim ],
			'eventos' => (int) $l->eventos,
		];
	}, $linhas );
}

function jelly_ar_evento_categoria_voltar( $args ) {
	wp_safe_redirect( jelly_ar_admin_url( 'eventos', array_merge( [ 'categorias' => 1 ], $args ) ) );
	exit;
}

function jelly_ar_evento_categoria_pedido( $nonce ) {
	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( $nonce );
}

/**
 * O nome e as cores do pedido, limpos. Nome vazio ou repetido — sem contar
 * maiúsculas nem acentos — volta com erro; uma cor que não é hexadecimal
 * volta à de partida.
 */
function jelly_ar_evento_categoria_campos( $menos = 0 ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado em jelly_ar_evento_categoria_pedido()
	$nome   = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : '';
	$inicio = isset( $_POST['cor_inicio'] ) ? sanitize_hex_color( wp_unslash( $_POST['cor_inicio'] ) ) : '';
	$fim    = isset( $_POST['cor_fim'] ) ? sanitize_hex_color( wp_unslash( $_POST['cor_fim'] ) ) : '';
	// phpcs:enable

	if ( '' === $nome ) {
		jelly_ar_evento_categoria_voltar( [ 'erro' => 'categoria-nome' ] );
	}

	foreach ( jelly_ar_evento_categorias_com_contagem() as $c ) {
		if ( $c['id'] !== $menos && 0 === strcasecmp( remove_accents( $c['nome'] ), remove_accents( $nome ) ) ) {
			jelly_ar_evento_categoria_voltar( [ 'erro' => 'categoria-existe' ] );
		}
	}

	$padrao = jelly_ar_evento_categoria_cores( 0 );

	return [
		'nome'       => mb_substr( $nome, 0, 80 ),
		'cor_inicio' => $inicio ? $inicio : $padrao['inicio'],
		'cor_fim'    => $fim ? $fim : $padrao['fim'],
	];
}

function jelly_ar_evento_categoria_criar() {
	global $wpdb;

	jelly_ar_evento_categoria_pedido( 'jelly_ar_evento_categoria_criar' );

	$campos = jelly_ar_evento_categoria_campos();
	$feito  = $wpdb->insert( // phpcs:ignore WordPress.DB
		jelly_ar_tabela( 'evento_categorias' ),
		$campos + [
			'slug'      => jelly_ar_slug_livre( 'evento_categorias', $campos['nome'] ),
			'criado_em' => current_time( 'mysql', true ),
		]
	);

	jelly_ar_evento_categoria_voltar( [ false === $feito ? 'erro' : 'aviso' => false === $feito ? 'categoria-falhou' : 'categoria-criada' ] );
}
add_action( 'admin_post_jelly_ar_evento_categoria_criar', 'jelly_ar_evento_categoria_criar' );

function jelly_ar_evento_categoria_editar() {
	global $wpdb;

	$id = isset( $_POST['categoria'] ) ? absint( $_POST['categoria'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	jelly_ar_evento_categoria_pedido( 'jelly_ar_evento_categoria_editar_' . $id );

	if ( ! wp_list_filter( jelly_ar_evento_categorias_com_contagem(), [ 'id' => $id ] ) ) {
		jelly_ar_evento_categoria_voltar( [ 'erro' => 'categoria-falhou' ] );
	}

	// O nome e as cores; o slug fica, para os filtros e os endereços não se perderem.
	$feito = $wpdb->update( jelly_ar_tabela( 'evento_categorias' ), jelly_ar_evento_categoria_campos( $id ), [ 'id' => $id ] ); // phpcs:ignore WordPress.DB

	jelly_ar_evento_categoria_voltar( [ false === $feito ? 'erro' : 'aviso' => false === $feito ? 'categoria-falhou' : 'categoria-atualizada' ] );
}
add_action( 'admin_post_jelly_ar_evento_categoria_editar', 'jelly_ar_evento_categoria_editar' );

function jelly_ar_evento_categoria_apagar() {
	global $wpdb;

	$id = isset( $_POST['categoria'] ) ? absint( $_POST['categoria'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	jelly_ar_evento_categoria_pedido( 'jelly_ar_evento_categoria_apagar_' . $id );

	if ( ! wp_list_filter( jelly_ar_evento_categorias_com_contagem(), [ 'id' => $id ] ) ) {
		jelly_ar_evento_categoria_voltar( [ 'erro' => 'categoria-falhou' ] );
	}

	// A mesma regra do ecrã, verificada outra vez aqui.
	if ( jelly_ar_evento_categoria_uso( $id ) > 0 ) {
		jelly_ar_evento_categoria_voltar( [ 'erro' => 'categoria-em-uso' ] );
	}

	$wpdb->delete( jelly_ar_tabela( 'evento_categorias' ), [ 'id' => $id ], [ '%d' ] ); // phpcs:ignore WordPress.DB

	jelly_ar_evento_categoria_voltar( [ 'aviso' => 'categoria-apagada' ] );
}
add_action( 'admin_post_jelly_ar_evento_categoria_apagar', 'jelly_ar_evento_categoria_apagar' );
