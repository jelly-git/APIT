<?php
/**
 * Imagem de destaque automática: os degradés do Brand Kit, um de cada vez.
 *
 * Um artigo gravado sem imagem de destaque recebe um dos oito degradés de
 * `wp-content/uploads/degrade-artigos/` (degrade-01.png … degrade-08.png). O
 * primeiro artigo sem imagem leva o 01, o seguinte o 02, e assim até ao 08;
 * depois do 08 volta ao 01. O último usado fica guardado na opção
 * `apit_degrade_ultimo`.
 *
 * Só se mexe em artigos que não têm imagem. Quem escolhe uma imagem — no
 * editor, no Elementor, ou por importação — nunca a vê trocada, e a escolha não
 * gasta nenhum degradé: a rotação só avança quando um degradé é de facto
 * atribuído.
 *
 * Os ficheiros são PNG na pasta de uploads e não estão na Multimédia à
 * partida. Cada degradé entra na biblioteca na primeira vez que é usado e é
 * encontrado depois pelo caminho do ficheiro, não por um id guardado: o id muda
 * de um site para o outro, o caminho não. A pasta sobe para produção com os
 * uploads e as entradas da biblioteca com a base de dados.
 *
 * O gancho é `wp_after_insert_post`, e não `save_post`, de propósito. No
 * editor de blocos a imagem escolhida é gravada pela API REST depois do
 * `save_post`; nessa altura o artigo ainda parece não a ter, e o degradé era
 * atribuído (e gasto) por cima de uma imagem que ia ser posta a seguir. O
 * `wp_after_insert_post` corre quando o artigo, os termos e os metadados já
 * estão gravados.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Quantos degradés há na rotação: degrade-01 … degrade-NN.
 */
function apit_degrade_total() {
	return 8;
}

/**
 * O caminho do degradé N dentro da pasta de uploads.
 *
 * É também o valor de `_wp_attached_file` da entrada na Multimédia, e é por ele
 * que a entrada é encontrada.
 */
function apit_degrade_caminho( $n ) {
	return sprintf( 'degrade-artigos/degrade-%02d.png', $n );
}

/**
 * O próximo degradé da rotação, de 1 a apit_degrade_total().
 *
 * Um valor guardado que não faça sentido (vazio, negativo, de uma rotação que
 * tinha mais degradés) recomeça no 01, em vez de dar um número que não existe.
 */
function apit_degrade_proximo() {
	$total  = apit_degrade_total();
	$ultimo = (int) get_option( 'apit_degrade_ultimo', 0 );

	if ( $ultimo < 1 || $ultimo > $total ) {
		return 1;
	}

	return ( $ultimo % $total ) + 1;
}

/**
 * O id do degradé N na Multimédia, criando a entrada se ainda não existir.
 *
 * Devolve 0 se o ficheiro não estiver na pasta de uploads — por exemplo num
 * servidor onde a pasta ainda não foi copiada. O artigo fica então sem imagem,
 * como estava, e a rotação não avança.
 */
function apit_degrade_anexo( $n ) {
	$caminho = apit_degrade_caminho( $n );

	$existentes = get_posts( [
		'post_type'              => 'attachment',
		'post_status'            => 'any',
		'posts_per_page'         => 1,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		'meta_key'               => '_wp_attached_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'             => $caminho, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	] );

	if ( $existentes ) {
		return (int) $existentes[0];
	}

	$uploads  = wp_get_upload_dir();
	$completo = trailingslashit( $uploads['basedir'] ) . $caminho;

	if ( ! is_readable( $completo ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';

	// Sem artigo-pai: o anexo é partilhado por todos os artigos que o usarem.
	$id = wp_insert_attachment(
		[
			'post_mime_type' => 'image/png',
			'post_title'     => sprintf( 'Degradé %02d', $n ),
			'post_status'    => 'inherit',
			'guid'           => trailingslashit( $uploads['baseurl'] ) . $caminho,
		],
		$completo,
		0,
		true
	);

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $completo ) );

	return (int) $id;
}

/**
 * Dá um degradé ao artigo acabado de gravar, se não tiver imagem de destaque.
 *
 * @param int     $post_id Id do artigo.
 * @param WP_Post $post    O artigo, já gravado.
 */
function apit_degrade_ao_gravar( $post_id, $post ) {
	if ( ! $post || 'post' !== $post->post_type ) {
		return;
	}

	// Rascunhos automáticos do editor, lixo, revisões e gravações automáticas
	// não são artigos a sério: não gastam degradés.
	if ( in_array( $post->post_status, [ 'auto-draft', 'trash', 'inherit' ], true )
		|| wp_is_post_revision( $post_id )
		|| wp_is_post_autosave( $post_id )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}

	// Uma importação traz as suas imagens, que são postas depois de o artigo
	// existir: não se antecipa nem se gasta a rotação.
	if ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) {
		return;
	}

	// Não chega haver um id guardado: o `has_post_thumbnail` só olha para ele,
	// e uma imagem apagada por fora (base de dados, ficheiros) deixava o artigo
	// com uma referência morta e sem imagem nenhuma, para sempre.
	$atual = (int) get_post_thumbnail_id( $post_id );

	if ( $atual && 'attachment' === get_post_type( $atual ) ) {
		return;
	}

	$n     = apit_degrade_proximo();
	$anexo = apit_degrade_anexo( $n );

	if ( ! $anexo || ! set_post_thumbnail( $post_id, $anexo ) ) {
		return;
	}

	// Só agora a rotação avança: um degradé que não chegou a ser atribuído
	// não conta como usado.
	update_option( 'apit_degrade_ultimo', $n, false );
}
add_action( 'wp_after_insert_post', 'apit_degrade_ao_gravar', 10, 2 );
