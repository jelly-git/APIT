<?php
/**
 * Os encontros e os vídeos deles, nas tabelas da AR (jelly_ar_encontros,
 * jelly_ar_encontro_videos — ver inc/instalar.php).
 *
 * Um encontro é como um artigo de notícias — título, data, resumo, texto e
 * imagem — com uma lista de vídeos do YouTube. Os vídeos são "não listados"
 * no YouTube: quem não tem o endereço não os encontra, e o endereço só está
 * aqui, na Área Reservada. Os encontros não aparecem no site.
 *
 *   estado   publicado | rascunho | lixo. O lixo não apaga: o encontro sai
 *            das listas, e a linha e os vídeos ficam
 */

defined( 'ABSPATH' ) || exit;

const JELLY_AR_ENCONTRO_ESTADOS = [ 'publicado', 'rascunho', 'lixo' ];

/* ---------- O YouTube ---------- */

/**
 * O identificador de um vídeo do YouTube (os 11 caracteres de watch?v=) a
 * partir do que se colar: o endereço da barra do browser, o de "Partilhar"
 * (youtu.be/…), o de incorporar, o de um short ou de um direto, ou só o
 * identificador. '' quando não se reconhece.
 */
function jelly_ar_youtube_id( $texto ) {
	$texto = trim( (string) $texto );

	if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $texto ) ) {
		return $texto;
	}

	if ( preg_match( '~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~i', $texto, $m ) ) {
		return $m[1];
	}

	return '';
}

function jelly_ar_youtube_url( $id ) {
	return 'https://www.youtube.com/watch?v=' . rawurlencode( $id );
}

/**
 * A imagem que o YouTube gera para cada vídeo; existe também nos não listados.
 */
function jelly_ar_youtube_miniatura( $id ) {
	return 'https://i.ytimg.com/vi/' . rawurlencode( $id ) . '/hqdefault.jpg';
}

/**
 * O endereço para incorporar o vídeo: o do youtube-nocookie, que só guarda
 * cookies no browser de quem carrega no play.
 */
function jelly_ar_youtube_embed( $id ) {
	return 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $id ) . '?rel=0';
}

/**
 * O título do vídeo no YouTube, pelo oEmbed (funciona com os não listados),
 * para quando não se escreveu um. '' se o YouTube não responder.
 */
function jelly_ar_youtube_titulo( $id ) {
	$resposta = wp_remote_get( 'https://www.youtube.com/oembed?format=json&url=' . rawurlencode( jelly_ar_youtube_url( $id ) ), [ 'timeout' => 5 ] );

	if ( is_wp_error( $resposta ) || 200 !== wp_remote_retrieve_response_code( $resposta ) ) {
		return '';
	}

	$dados = json_decode( wp_remote_retrieve_body( $resposta ), true );

	return isset( $dados['title'] ) ? mb_substr( sanitize_text_field( $dados['title'] ), 0, 255 ) : '';
}

/* ---------- O slug ---------- */

/**
 * O slug de um encontro, a partir do título, e único entre os encontros:
 * se outro já o tiver, -2, -3… $id é o do próprio encontro, que não conta.
 */
function jelly_ar_encontro_slug( $titulo, $id = 0 ) {
	global $wpdb;

	$base = sanitize_title( $titulo );
	$base = '' !== $base ? mb_substr( $base, 0, 190 ) : 'encontro';
	$slug = $base;

	for ( $n = 2; $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . jelly_ar_tabela( 'encontros' ) . ' WHERE slug = %s AND id <> %d LIMIT 1', $slug, $id ) ); $n++ ) { // phpcs:ignore WordPress.DB
		$slug = $base . '-' . $n;
	}

	return $slug;
}

/* ---------- Ler ---------- */

/**
 * Os vídeos de vários encontros de uma vez: encontro_id => [ vídeos ], cada
 * um com id, youtube, titulo, url e miniatura, pela ordem.
 */
function jelly_ar_encontros_videos( $ids ) {
	global $wpdb;

	$ids = array_filter( array_map( 'absint', (array) $ids ) );

	if ( ! $ids ) {
		return [];
	}

	$lista = array_fill_keys( $ids, [] );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	foreach ( $wpdb->get_results( 'SELECT * FROM ' . jelly_ar_tabela( 'encontro_videos' ) . ' WHERE encontro_id IN (' . implode( ',', $ids ) . ') ORDER BY encontro_id, ordem, id' ) as $v ) {
		$lista[ (int) $v->encontro_id ][] = [
			'id'        => (int) $v->id,
			'youtube'   => $v->youtube_id,
			'titulo'    => $v->titulo,
			'url'       => jelly_ar_youtube_url( $v->youtube_id ),
			'miniatura' => jelly_ar_youtube_miniatura( $v->youtube_id ),
		];
	}

	return $lista;
}

/**
 * Uma linha da tabela na forma que os ecrãs usam, com os vídeos e a capa: a
 * imagem escolhida ou, sem ela, a miniatura do primeiro vídeo ('' sem as duas).
 */
function jelly_ar_encontro_da_linha( $l, $videos ) {
	$imagem = $l->imagem_id ? wp_get_attachment_image_url( (int) $l->imagem_id, 'medium_large' ) : '';
	$ts     = strtotime( $l->data );

	return [
		'id'           => (int) $l->id,
		'titulo'       => $l->titulo,
		'slug'         => (string) $l->slug,
		'data'         => $l->data,
		'data_texto'   => $ts ? jelly_ar_data( 'j M Y', $ts ) : '—',
		'resumo'       => $l->resumo,
		'texto'        => $l->texto,
		'imagem_id'    => $imagem ? (int) $l->imagem_id : 0,
		'imagem'       => $imagem ? $imagem : '',
		'capa'         => $imagem ? $imagem : ( $videos ? $videos[0]['miniatura'] : '' ),
		'videos'       => $videos,
		'estado'       => $l->estado,
		'publicado_em' => $l->publicado_em,
	];
}

/**
 * Os encontros, do mais recente para o mais antigo. $onde é o WHERE, já
 * preparado; por omissão, todos menos os do lixo (o back-office).
 */
function jelly_ar_encontros( $onde = '' ) {
	global $wpdb;

	if ( '' === $onde ) {
		$onde = $wpdb->prepare( 'WHERE estado <> %s', 'lixo' );
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results( 'SELECT * FROM ' . jelly_ar_tabela( 'encontros' ) . " {$onde} ORDER BY data DESC, id DESC" );
	$videos = jelly_ar_encontros_videos( wp_list_pluck( $linhas, 'id' ) );

	return array_map( function ( $l ) use ( $videos ) {
		return jelly_ar_encontro_da_linha( $l, $videos[ (int) $l->id ] ?? [] );
	}, $linhas );
}

/**
 * Os publicados — os que os associados veem.
 */
function jelly_ar_encontros_publicados() {
	global $wpdb;

	return jelly_ar_encontros( $wpdb->prepare( 'WHERE estado = %s', 'publicado' ) );
}

function jelly_ar_encontro( $id ) {
	global $wpdb;

	$l = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . jelly_ar_tabela( 'encontros' ) . ' WHERE id = %d AND estado <> %s', $id, 'lixo' ) ); // phpcs:ignore WordPress.DB

	return $l ? jelly_ar_encontro_da_linha( $l, jelly_ar_encontros_videos( [ $l->id ] )[ (int) $l->id ] ?? [] ) : null;
}
