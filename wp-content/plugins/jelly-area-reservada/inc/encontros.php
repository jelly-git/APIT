<?php
/**
 * Encontros no back-office: a lista, com os separadores e a ordem, e a
 * gravação — os dados do encontro e os vídeos, que se gravam juntos no
 * encontro novo e cada um no seu cartão depois.
 *
 * Ler os encontros: inc/encontros-dados.php.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- A lista ---------- */

function jelly_ar_encontros_lista() {
	return new Jelly_AR_Lista(
		'encontros',
		[ 'estado' => 'todos' ],
		'data',
		function () {
			return [ 'titulo', 'data' ];
		},
		[ 'estado' => [ 'todos', 'publicado', 'rascunho' ] ],
		'desc'
	);
}

/**
 * A pesquisa, o separador e a ordem.
 */
function jelly_ar_encontros_filtrar( $encontros, $pedido ) {
	$encontrados = array_values( array_filter( $encontros, function ( $e ) use ( $pedido ) {
		return '' === $pedido['q'] || Jelly_AR_Lista::contem( $pedido['q'], array_merge( [ $e['titulo'], $e['resumo'] ], wp_list_pluck( $e['videos'], 'titulo' ) ) );
	} ) );

	$lista = 'todos' === $pedido['estado'] ? $encontrados : array_values( array_filter( $encontrados, function ( $e ) use ( $pedido ) {
		return $e['estado'] === $pedido['estado'];
	} ) );

	$ordenar = $pedido['ordenar'];
	$ordem   = $pedido['ordem'];

	usort( $lista, function ( $a, $b ) use ( $ordenar, $ordem ) {
		if ( 'data' === $ordenar ) {
			$r = [ $a['data'], $a['id'] ] <=> [ $b['data'], $b['id'] ];
			return 'desc' === $ordem ? -$r : $r;
		}

		return Jelly_AR_Lista::comparar_texto( $a[ $ordenar ], $b[ $ordenar ], $ordem );
	} );

	return [
		'encontrados' => $encontrados,
		'lista'       => $lista,
		'contagem'    => array_count_values( wp_list_pluck( $encontrados, 'estado' ) ),
	];
}

/* ---------- Guardar ---------- */

function jelly_ar_encontro_so_administradores() {
	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}
}

/**
 * Os vídeos que chegam do formulário, pela ordem das linhas: videos[id][],
 * videos[url][] e videos[titulo][]. Uma linha sem endereço não conta; uma
 * com um endereço que não é do YouTube faz voltar ao formulário com o erro.
 *
 * @return array|string Os vídeos, ou 'video' se um endereço não se reconhece.
 */
function jelly_ar_encontro_videos_pedidos() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado por quem chama.
	$bruto = isset( $_POST['videos'] ) && is_array( $_POST['videos'] ) ? wp_unslash( $_POST['videos'] ) : [];
	// phpcs:enable
	$urls   = isset( $bruto['url'] ) ? (array) $bruto['url'] : [];
	$videos = [];

	foreach ( $urls as $i => $url ) {
		$url = trim( sanitize_text_field( $url ) );

		if ( '' === $url ) {
			continue;
		}

		$youtube = jelly_ar_youtube_id( $url );

		if ( '' === $youtube ) {
			return 'video';
		}

		$videos[] = [
			'id'      => absint( $bruto['id'][ $i ] ?? 0 ),
			'youtube' => $youtube,
			'titulo'  => mb_substr( sanitize_text_field( $bruto['titulo'][ $i ] ?? '' ), 0, 255 ),
		];
	}

	return $videos;
}

/**
 * Grava os vídeos de um encontro tal como vêm: os que já existiam atualizam-
 * -se (e mantêm o id), os novos entram, os que saíram da lista apagam-se. Um
 * vídeo sem título fica com o do YouTube.
 */
function jelly_ar_encontro_videos_gravar( $encontro_id, $videos ) {
	global $wpdb;

	$tabela  = jelly_ar_tabela( 'encontro_videos' );
	$antigos = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$tabela} WHERE encontro_id = %d", $encontro_id ) ) ); // phpcs:ignore WordPress.DB
	$ficam   = [];

	foreach ( array_values( $videos ) as $ordem => $v ) {
		$linha = [
			'youtube_id' => $v['youtube'],
			'titulo'     => '' !== $v['titulo'] ? $v['titulo'] : jelly_ar_youtube_titulo( $v['youtube'] ),
			'ordem'      => $ordem,
		];

		if ( $v['id'] && in_array( $v['id'], $antigos, true ) ) {
			$wpdb->update( $tabela, $linha, [ 'id' => $v['id'] ] ); // phpcs:ignore WordPress.DB
			$ficam[] = $v['id'];
		} else {
			$wpdb->insert( $tabela, $linha + [ 'encontro_id' => $encontro_id ] ); // phpcs:ignore WordPress.DB
		}
	}

	foreach ( array_diff( $antigos, $ficam ) as $sai ) {
		$wpdb->delete( $tabela, [ 'id' => $sai ] ); // phpcs:ignore WordPress.DB
	}
}

/**
 * O formulário do encontro — novo ou a editar — chega aqui (admin-post.php).
 * No novo vêm os dados e os vídeos; na edição, cada cartão manda o seu:
 * `parte` é "dados" ou "videos".
 */
function jelly_ar_encontro_guardar() {
	global $wpdb;

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- o nonce é verificado logo abaixo.
	$id    = isset( $_POST['encontro'] ) ? absint( $_POST['encontro'] ) : 0;
	$parte = isset( $_POST['parte'] ) ? sanitize_key( wp_unslash( $_POST['parte'] ) ) : 'tudo';
	// phpcs:enable

	$voltar = function ( $erro ) use ( $id, $parte ) {
		wp_safe_redirect( jelly_ar_admin_url( 'encontros', $id ? [ 'encontro' => $id, 'editar' => $parte, 'erro' => $erro ] : [ 'novo' => 1, 'erro' => $erro ] ) );
		exit;
	};

	jelly_ar_encontro_so_administradores();
	check_admin_referer( 'jelly_ar_encontro_guardar_' . $id );

	$atual = $id ? jelly_ar_encontro( $id ) : null;

	if ( $id && ! $atual ) {
		wp_die( esc_html__( 'Esse encontro não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	$com_dados  = ! $id || 'dados' === $parte;
	$com_videos = ! $id || 'videos' === $parte;
	$agora      = current_time( 'mysql', true );

	if ( $com_videos ) {
		$videos = jelly_ar_encontro_videos_pedidos();

		if ( ! is_array( $videos ) ) {
			$voltar( $videos );
		}
	}

	if ( $com_dados ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$titulo = isset( $_POST['titulo'] ) ? sanitize_text_field( wp_unslash( $_POST['titulo'] ) ) : '';
		$data   = DateTime::createFromFormat( '!Y-m-d', isset( $_POST['data'] ) ? sanitize_text_field( wp_unslash( $_POST['data'] ) ) : '' );
		$imagem = isset( $_POST['imagem_id'] ) ? absint( $_POST['imagem_id'] ) : 0;
		$linha  = [
			'titulo'        => $titulo,
			'data'          => $data ? $data->format( 'Y-m-d' ) : '',
			'resumo'        => isset( $_POST['resumo'] ) ? sanitize_textarea_field( wp_unslash( $_POST['resumo'] ) ) : '',
			'texto'         => isset( $_POST['texto'] ) ? wp_kses_post( wp_unslash( $_POST['texto'] ) ) : '',
			// Só uma imagem da biblioteca conta.
			'imagem_id'     => $imagem && wp_attachment_is_image( $imagem ) ? $imagem : 0,
			'slug'          => jelly_ar_encontro_slug( $titulo, $id ),
			'atualizado_em' => $agora,
		];
		// phpcs:enable

		if ( '' === $titulo || '' === $linha['data'] ) {
			$voltar( 'campos' );
		}
	} else {
		$linha = [ 'atualizado_em' => $agora ];
	}

	$novo = ! $id;

	if ( $novo ) {
		$estado = isset( $_POST['estado'] ) && 'rascunho' === $_POST['estado'] ? 'rascunho' : 'publicado'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$linha += [
			'estado'       => $estado,
			'autor_id'     => get_current_user_id(),
			'criado_em'    => $agora,
			'publicado_em' => 'publicado' === $estado ? $agora : null,
		];

		$feito = $wpdb->insert( jelly_ar_tabela( 'encontros' ), $linha ); // phpcs:ignore WordPress.DB
		$id    = (int) $wpdb->insert_id;
	} else {
		$feito = $wpdb->update( jelly_ar_tabela( 'encontros' ), $linha, [ 'id' => $id ] ); // phpcs:ignore WordPress.DB
	}

	if ( false === $feito || ! $id ) {
		$voltar( 'falhou' );
	}

	if ( $com_videos ) {
		jelly_ar_encontro_videos_gravar( $id, $videos );
	}

	wp_safe_redirect( jelly_ar_admin_url( 'encontros', [ 'encontro' => $id, 'aviso' => $novo ? 'criado' : ( 'videos' === $parte ? 'videos' : 'atualizado' ) ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_encontro_guardar', 'jelly_ar_encontro_guardar' );

/**
 * Publicar, passar a rascunho ou mandar para o lixo. O lixo não apaga: o
 * encontro sai das listas e da Área Reservada, e fica guardado.
 */
function jelly_ar_encontro_estado() {
	global $wpdb;

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- o nonce é verificado logo abaixo.
	$id   = isset( $_POST['encontro'] ) ? absint( $_POST['encontro'] ) : 0;
	$acao = isset( $_POST['estado'] ) ? sanitize_key( wp_unslash( $_POST['estado'] ) ) : '';
	// phpcs:enable
	$para = [ 'publicar' => 'publicado', 'rascunho' => 'rascunho', 'lixo' => 'lixo' ];

	jelly_ar_encontro_so_administradores();
	check_admin_referer( 'jelly_ar_encontro_estado_' . $id );

	$encontro = jelly_ar_encontro( $id );

	if ( ! $encontro || ! isset( $para[ $acao ] ) ) {
		wp_die( esc_html__( 'Esse encontro não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	$linha = [ 'estado' => $para[ $acao ], 'atualizado_em' => current_time( 'mysql', true ) ];

	// A data da primeira publicação fica; publicar outra vez não a muda.
	if ( 'publicar' === $acao && ! $encontro['publicado_em'] ) {
		$linha['publicado_em'] = $linha['atualizado_em'];
	}

	$wpdb->update( jelly_ar_tabela( 'encontros' ), $linha, [ 'id' => $id ] ); // phpcs:ignore WordPress.DB

	if ( 'lixo' === $acao ) {
		wp_safe_redirect( jelly_ar_admin_url( 'encontros', [ 'aviso' => 'lixo' ] ) );
		exit;
	}

	wp_safe_redirect( jelly_ar_admin_url( 'encontros', [ 'encontro' => $id, 'aviso' => 'publicar' === $acao ? 'publicado' : 'rascunho' ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_encontro_estado', 'jelly_ar_encontro_estado' );
