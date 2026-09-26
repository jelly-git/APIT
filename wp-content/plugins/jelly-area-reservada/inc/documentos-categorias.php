<?php
/**
 * As categorias dos documentos: criar, mudar o nome e apagar, no ecrã
 * Documentos → Categorias.
 *
 * Estão na tabela jelly_ar_doc_categorias (inc/instalar.php).
 *
 * Mudar o nome não muda o slug: o slug é o que os filtros guardados em
 * endereços usam, e continuam a servir.
 *
 * Uma categoria com documentos não se apaga: os documentos ficariam sem
 * categoria, e os associados deixavam de os encontrar pelo filtro. Mudam-se
 * primeiro os documentos para outra, e depois apaga-se.
 */

defined( 'ABSPATH' ) || exit;

/**
 * As categorias com quantos documentos tem cada uma, os reais e os de exemplo
 * em separado. Os dois contam para se poder apagar: é a soma que o ecrã mostra.
 */
function jelly_ar_categorias_com_contagem() {
	global $wpdb;

	$c = jelly_ar_tabela( 'doc_categorias' );
	$d = jelly_ar_tabela( 'documentos' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( "SELECT c.id, c.nome, c.slug, COUNT(d.id) AS reais FROM {$c} c LEFT JOIN {$d} d ON d.categoria_id = c.id GROUP BY c.id ORDER BY c.nome" );

	$exemplo = [];
	if ( JELLY_AR_EXEMPLO ) {
		require_once JELLY_AR_DIR . 'inc/admin-exemplo.php';
		$exemplo = array_count_values( array_column( jelly_ar_exemplo_documentos(), 'categoria' ) );
	}

	return array_map( function ( $l ) use ( $exemplo ) {
		return [
			'id'      => (int) $l->id,
			'nome'    => $l->nome,
			'slug'    => $l->slug,
			'reais'   => (int) $l->reais,
			'exemplo' => $exemplo[ $l->slug ] ?? 0,
		];
	}, $linhas );
}

/**
 * O que as três ações têm em comum: só administradores, e o nonce certo.
 */
function jelly_ar_categoria_pedido( $nonce ) {
	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( $nonce );
}

function jelly_ar_categoria_voltar( $args ) {
	wp_safe_redirect( jelly_ar_admin_url( 'documentos', array_merge( [ 'categorias' => 1 ], $args ) ) );
	exit;
}

/**
 * O nome pedido, limpo, ou volta com erro se vier vazio ou repetido.
 * $menos é a categoria que se está a mudar, que pode ficar com o nome que já tem.
 */
function jelly_ar_categoria_nome( $menos = 0 ) {
	$nome = isset( $_POST['nome'] ) ? sanitize_text_field( wp_unslash( $_POST['nome'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verificado em jelly_ar_categoria_pedido()

	if ( '' === $nome ) {
		jelly_ar_categoria_voltar( [ 'erro' => 'categoria-nome' ] );
	}

	// Sem distinguir maiúsculas nem acentos: "Eventos" e "eventos" seriam a mesma.
	foreach ( jelly_ar_categorias_com_contagem() as $c ) {
		if ( $c['id'] !== $menos && 0 === strcasecmp( remove_accents( $c['nome'] ), remove_accents( $nome ) ) ) {
			jelly_ar_categoria_voltar( [ 'erro' => 'categoria-existe' ] );
		}
	}

	return mb_substr( $nome, 0, 80 );
}

function jelly_ar_categoria_criar() {
	global $wpdb;

	jelly_ar_categoria_pedido( 'jelly_ar_categoria_criar' );

	$nome  = jelly_ar_categoria_nome();
	$feito = $wpdb->insert( // phpcs:ignore WordPress.DB
		jelly_ar_tabela( 'doc_categorias' ),
		[
			'nome'      => $nome,
			'slug'      => jelly_ar_slug_livre( 'doc_categorias', $nome ),
			'criado_em' => current_time( 'mysql', true ),
		]
	);

	jelly_ar_categoria_voltar( [ false === $feito ? 'erro' : 'aviso' => false === $feito ? 'categoria-falhou' : 'categoria-criada' ] );
}
add_action( 'admin_post_jelly_ar_categoria_criar', 'jelly_ar_categoria_criar' );

function jelly_ar_categoria_editar() {
	global $wpdb;

	$id = isset( $_POST['categoria'] ) ? absint( $_POST['categoria'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	jelly_ar_categoria_pedido( 'jelly_ar_categoria_editar_' . $id );

	if ( ! wp_list_filter( jelly_ar_categorias_com_contagem(), [ 'id' => $id ] ) ) {
		jelly_ar_categoria_voltar( [ 'erro' => 'categoria-falhou' ] );
	}

	// Só o nome: o slug fica, para os filtros e os endereços não se perderem.
	$feito = $wpdb->update( jelly_ar_tabela( 'doc_categorias' ), [ 'nome' => jelly_ar_categoria_nome( $id ) ], [ 'id' => $id ] ); // phpcs:ignore WordPress.DB

	jelly_ar_categoria_voltar( [ false === $feito ? 'erro' : 'aviso' => false === $feito ? 'categoria-falhou' : 'categoria-atualizada' ] );
}
add_action( 'admin_post_jelly_ar_categoria_editar', 'jelly_ar_categoria_editar' );

function jelly_ar_categoria_apagar() {
	global $wpdb;

	$id = isset( $_POST['categoria'] ) ? absint( $_POST['categoria'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	jelly_ar_categoria_pedido( 'jelly_ar_categoria_apagar_' . $id );

	$contagem = wp_list_filter( jelly_ar_categorias_com_contagem(), [ 'id' => $id ] );

	if ( ! $contagem ) {
		jelly_ar_categoria_voltar( [ 'erro' => 'categoria-falhou' ] );
	}

	/*
	 * A mesma regra do ecrã, verificada outra vez aqui: com documentos, não se
	 * apaga. Contam os mesmos que o ecrã mostra — os reais e, enquanto houver
	 * dados de exemplo, esses também —, para a regra nunca contradizer o número
	 * que se está a ver.
	 */
	$contagem = current( $contagem );

	if ( $contagem['reais'] + $contagem['exemplo'] > 0 ) {
		jelly_ar_categoria_voltar( [ 'erro' => 'categoria-em-uso' ] );
	}

	$wpdb->delete( jelly_ar_tabela( 'doc_categorias' ), [ 'id' => $id ], [ '%d' ] ); // phpcs:ignore WordPress.DB

	jelly_ar_categoria_voltar( [ 'aviso' => 'categoria-apagada' ] );
}
add_action( 'admin_post_jelly_ar_categoria_apagar', 'jelly_ar_categoria_apagar' );
