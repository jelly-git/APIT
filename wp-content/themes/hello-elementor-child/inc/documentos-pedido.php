<?php
/**
 * Documentos que só se descarregam depois de um formulário.
 *
 * Quem quer um Anuário ou os Estatutos diz quem é — Nome, Empresa e Email — e só
 * então recebe o ficheiro. Três peças:
 *
 *  1. O pedido. O visitante preenche o formulário do tema (o diálogo de
 *     template-parts/pedido-documento.php) e o script envia-o à rota REST
 *     apit/v1/pedido-documento. Quem guarda o pedido é o Gravity Forms, no
 *     formulário "Pedido de documento": as entradas ficam em Formulários >
 *     Entradas, com o documento pedido, e as notificações são as dele.
 *
 *  2. O link. A resposta traz um endereço assinado e com validade curta. O
 *     endereço real do ficheiro nunca sai na página: nos Anuários o botão só
 *     leva o número do documento, e nos Estatutos o link é "#pedir-estatutos".
 *
 *  3. O download. O endereço assinado passa por aqui, que confere a assinatura
 *     e entrega o ficheiro com Content-Disposition: attachment.
 *
 * O que fica de fora: o ficheiro continua na biblioteca de media, e quem já
 * souber o seu endereço directo consegue abri-lo sem passar pelo formulário.
 * Para o fechar de vez é preciso que o servidor recuse esses endereços (uma
 * regra no .htaccess sobre a pasta onde estes PDFs ficam).
 */

defined( 'ABSPATH' ) || exit;

/**
 * As áreas de documentos cujo download pede o formulário, pelo slug.
 *
 * Brochuras e Estudos ficam de fora: descarregam-se directamente. Para juntar
 * outra área basta o filtro (ou acrescentar o slug aqui).
 */
function apit_areas_com_pedido() {
	return (array) apply_filters( 'apit_areas_com_pedido', [ 'anuarios' ] );
}

/**
 * Se um documento da biblioteca está numa área que pede o formulário.
 */
function apit_documento_com_pedido( $post_id ) {
	$slugs = wp_get_post_terms( (int) $post_id, 'apit_area_documento', [ 'fields' => 'slugs' ] );

	return is_array( $slugs ) && (bool) array_intersect( $slugs, apit_areas_com_pedido() );
}

/**
 * O ficheiro dos Estatutos.
 *
 * Escolhido em Personalizar > APIT — Documentos. Enquanto não houver escolha,
 * usa o endereço do item "Estatutos" da barra superior, que é onde o ficheiro
 * já estava ligado.
 */
function apit_estatutos_url() {
	$url = trim( (string) get_theme_mod( 'apit_estatutos_ficheiro', '' ) );

	if ( '' !== $url ) {
		return $url;
	}

	$locais = get_nav_menu_locations();
	$itens  = ! empty( $locais['top-bar'] ) ? wp_get_nav_menu_items( $locais['top-bar'] ) : [];

	foreach ( (array) $itens as $item ) {
		$url = trim( (string) $item->url );

		if ( 'estatutos' === mb_strtolower( trim( wp_strip_all_tags( (string) $item->title ) ) ) && '' !== $url && '#' !== $url[0] ) {
			return $url;
		}
	}

	return '';
}

/**
 * O campo onde se escolhe o ficheiro dos Estatutos.
 */
function apit_documentos_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'apit_documentos', [
		'title'       => __( 'APIT — Documentos', 'apit' ),
		'description' => __( 'Ficheiros que se descarregam depois de um formulário. Nos links dos Estatutos (botão em Sobre a APIT e barra superior) use o endereço #pedir-estatutos.', 'apit' ),
		'priority'    => 161,
	] );

	$wp_customize->add_setting( 'apit_estatutos_ficheiro', [
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
	] );

	$wp_customize->add_control( new WP_Customize_Upload_Control( $wp_customize, 'apit_estatutos_ficheiro', [
		'label'   => __( 'Ficheiro dos Estatutos', 'apit' ),
		'section' => 'apit_documentos',
	] ) );
}
add_action( 'customize_register', 'apit_documentos_customize_register' );

/**
 * O que a chave de um pedido designa: título e endereço do ficheiro.
 *
 * Chaves: "estatutos", ou "doc-123" para o documento 123 da biblioteca. Um
 * documento fora das áreas que pedem formulário não é servido por aqui, para
 * este endereço não ser uma porta para tudo o que está na biblioteca.
 *
 * @return array|null titulo, ficheiro
 */
function apit_pedido_item( $chave ) {
	if ( 'estatutos' === $chave ) {
		$url = apit_estatutos_url();

		return $url ? [ 'titulo' => __( 'Estatutos', 'apit' ), 'ficheiro' => $url ] : null;
	}

	if ( ! preg_match( '/^doc-(\d+)$/', (string) $chave, $m ) ) {
		return null;
	}

	$id   = (int) $m[1];
	$post = get_post( $id );

	if ( ! $post || 'apit_documento' !== $post->post_type || 'publish' !== $post->post_status || ! apit_documento_com_pedido( $id ) ) {
		return null;
	}

	$ficheiro = apit_media_url( (string) apit_campo( 'documento_ficheiro', $id ), 'img' );

	return $ficheiro ? [ 'titulo' => get_the_title( $post ), 'ficheiro' => $ficheiro ] : null;
}

/* -------------------------------------------------------------------------
 * O formulário "Pedido de documento", no Gravity Forms
 * ---------------------------------------------------------------------- */

/**
 * O id do formulário, procurado pelo título. Se ainda não existe, cria-o — assim
 * o código chega a produção sem ser preciso importar nada à mão.
 */
function apit_pedido_form_id() {
	static $id = null;

	if ( null !== $id ) {
		return $id;
	}

	$id = 0;

	if ( ! class_exists( 'GFAPI' ) ) {
		return $id;
	}

	foreach ( GFAPI::get_forms() as $form ) {
		if ( 'pedido de documento' === mb_strtolower( trim( (string) $form['title'] ) ) ) {
			$id = (int) $form['id'];

			return $id;
		}
	}

	$novo = GFAPI::add_form( [
		'title'       => 'Pedido de documento',
		'description' => 'Pedidos de download de Anuários e Estatutos. Preenchido pelo diálogo do site (inc/documentos-pedido.php).',
		'fields'      => [
			[ 'id' => 1, 'type' => 'text', 'label' => 'Nome', 'isRequired' => true ],
			[ 'id' => 2, 'type' => 'text', 'label' => 'Empresa', 'isRequired' => true ],
			[ 'id' => 3, 'type' => 'email', 'label' => 'Email', 'isRequired' => true ],
			[ 'id' => 4, 'type' => 'text', 'label' => 'Documento' ],
			[ 'id' => 5, 'type' => 'text', 'label' => 'Consentimento' ],
		],
	] );

	$id = is_wp_error( $novo ) ? 0 : (int) $novo;

	return $id;
}
// Aparece no back-office logo na primeira visita, não só no primeiro pedido.
add_action( 'admin_init', 'apit_pedido_form_id' );

/**
 * Os campos do formulário por papel, encontrados pelo rótulo e não pelo id, como
 * na newsletter: um campo refeito no back-office muda de id.
 */
function apit_pedido_campos( $form ) {
	$campos = [];

	foreach ( $form['fields'] as $campo ) {
		$rotulo = mb_strtolower( trim( (string) $campo->label ) );

		if ( 'email' === $campo->type && ! isset( $campos['email'] ) ) {
			$campos['email'] = $campo;
		} elseif ( in_array( $rotulo, [ 'nome', 'empresa', 'documento', 'consentimento' ], true ) && ! isset( $campos[ $rotulo ] ) ) {
			$campos[ $rotulo ] = $campo;
		}
	}

	return $campos;
}

/* -------------------------------------------------------------------------
 * O link assinado
 * ---------------------------------------------------------------------- */

function apit_pedido_assinar( $chave, $validade ) {
	return hash_hmac( 'sha256', $chave . '|' . $validade, wp_salt( 'auth' ) );
}

/**
 * O endereço de download, válido durante 15 minutos.
 */
function apit_pedido_link( $chave ) {
	$validade = time() + 15 * MINUTE_IN_SECONDS;

	return add_query_arg( [
		'apit_download' => $chave,
		'e'             => $validade,
		's'             => apit_pedido_assinar( $chave, $validade ),
	], home_url( '/' ) );
}

/**
 * Faz o pedido: valida, guarda no Gravity Forms e devolve o link.
 *
 * @param array $dados chave, nome, empresa, email, consentimento e a armadilha.
 * @return array ok, mensagem, erros (por campo), e url quando ok
 */
function apit_pedido_registar( array $dados ) {
	$chave = sanitize_text_field( wp_unslash( (string) ( $dados['chave'] ?? '' ) ) );
	$item  = apit_pedido_item( $chave );

	if ( ! $item ) {
		return [ 'ok' => false, 'mensagem' => __( 'Este documento não está disponível de momento.', 'apit' ), 'erros' => [] ];
	}

	// A armadilha: invisível para quem lê a página, preenchida por quem preenche tudo.
	if ( '' !== trim( (string) ( $dados['apit_site'] ?? '' ) ) ) {
		return [ 'ok' => false, 'mensagem' => __( 'Não foi possível concluir o pedido.', 'apit' ), 'erros' => [] ];
	}

	// Dez pedidos por dez minutos e por endereço: sobra para qualquer pessoa, falta para um robô.
	$tentativas_chave = 'apit_ped_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$tentativas       = (int) get_transient( $tentativas_chave );

	if ( $tentativas >= 10 ) {
		return [ 'ok' => false, 'mensagem' => __( 'Demasiados pedidos. Tente de novo dentro de alguns minutos.', 'apit' ), 'erros' => [] ];
	}

	set_transient( $tentativas_chave, $tentativas + 1, 10 * MINUTE_IN_SECONDS );

	$form = class_exists( 'GFAPI' ) ? GFAPI::get_form( apit_pedido_form_id() ) : null;

	if ( ! $form || empty( $form['is_active'] ) || ! empty( $form['is_trash'] ) ) {
		return [ 'ok' => false, 'mensagem' => __( 'O pedido não está disponível de momento. Tente mais tarde.', 'apit' ), 'erros' => [] ];
	}

	$campos = apit_pedido_campos( $form );

	if ( empty( $campos['email'] ) ) {
		return [ 'ok' => false, 'mensagem' => __( 'O pedido não está disponível de momento. Tente mais tarde.', 'apit' ), 'erros' => [] ];
	}

	$limpo = static function ( $papel ) use ( $dados ) {
		return trim( sanitize_text_field( wp_unslash( (string) ( $dados[ $papel ] ?? '' ) ) ) );
	};

	$nome    = $limpo( 'nome' );
	$empresa = $limpo( 'empresa' );
	$email   = trim( sanitize_email( wp_unslash( (string) ( $dados['email'] ?? '' ) ) ) );
	$erros   = [];

	if ( '' === $nome ) {
		$erros['nome'] = __( 'Indique o seu nome.', 'apit' );
	}

	if ( '' === $empresa ) {
		$erros['empresa'] = __( 'Indique a sua empresa.', 'apit' );
	}

	if ( '' === $email || ! is_email( $email ) ) {
		$erros['email'] = __( 'Indique um email válido.', 'apit' );
	}

	if ( empty( $dados['consentimento'] ) ) {
		$erros['consentimento'] = __( 'É preciso aceitar a Política de Privacidade.', 'apit' );
	}

	if ( $erros ) {
		return [ 'ok' => false, 'mensagem' => implode( ' ', array_unique( $erros ) ), 'erros' => $erros ];
	}

	$valores = [
		'input_' . $campos['email']->id => $email,
	];

	$por_papel = [
		'nome'          => $nome,
		'empresa'       => $empresa,
		'documento'     => $item['titulo'],
		'consentimento' => 'Aceito os termos da Política de Privacidade',
	];

	foreach ( $por_papel as $papel => $valor ) {
		if ( ! empty( $campos[ $papel ] ) ) {
			$valores[ 'input_' . $campos[ $papel ]->id ] = $valor;
		}
	}

	$resultado = GFAPI::submit_form( $form['id'], $valores );

	if ( is_wp_error( $resultado ) || empty( $resultado['is_valid'] ) ) {
		return [ 'ok' => false, 'mensagem' => __( 'Verifique os campos e tente de novo.', 'apit' ), 'erros' => [] ];
	}

	return [
		'ok'       => true,
		'mensagem' => __( 'Obrigado. O download vai começar.', 'apit' ),
		'erros'    => [],
		'url'      => apit_pedido_link( $chave ),
	];
}

function apit_pedido_rota() {
	register_rest_route( 'apit/v1', '/pedido-documento', [
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( $pedido ) {
			$r = apit_pedido_registar( (array) $pedido->get_params() );

			return new WP_REST_Response( $r, $r['ok'] ? 200 : 422 );
		},
	] );
}
add_action( 'rest_api_init', 'apit_pedido_rota' );

/* -------------------------------------------------------------------------
 * A entrega do ficheiro
 * ---------------------------------------------------------------------- */

/**
 * O caminho no disco de um ficheiro dentro de wp-content, ou '' se não for um
 * que se possa entregar.
 *
 * Aceita o endereço completo ou o caminho a partir da raiz do site; só interessa
 * o que vem depois de /wp-content/, que é o que se mantém entre o site local, o
 * dev e produção, esteja o site na raiz ou numa subpasta. Só sai de wp-content, e
 * só com extensões de documento — nunca um .php.
 */
function apit_ficheiro_local( $url ) {
	$caminho = (string) wp_parse_url( (string) $url, PHP_URL_PATH );
	$pos     = strpos( $caminho, '/wp-content/' );

	if ( false === $pos ) {
		return '';
	}

	$ficheiro = realpath( WP_CONTENT_DIR . '/' . rawurldecode( substr( $caminho, $pos + strlen( '/wp-content/' ) ) ) );
	$raiz     = realpath( WP_CONTENT_DIR );

	if ( ! $ficheiro || ! $raiz || ! is_file( $ficheiro ) ) {
		return '';
	}

	if ( 0 !== strpos( wp_normalize_path( $ficheiro ), trailingslashit( wp_normalize_path( $raiz ) ) ) ) {
		return '';
	}

	$permitidas = [ 'pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'txt', 'csv' ];

	return in_array( strtolower( pathinfo( $ficheiro, PATHINFO_EXTENSION ) ), $permitidas, true ) ? $ficheiro : '';
}

function apit_pedido_entregar() {
	if ( ! isset( $_GET['apit_download'] ) ) {
		return;
	}

	$chave    = sanitize_text_field( wp_unslash( (string) $_GET['apit_download'] ) );
	$validade = (int) ( $_GET['e'] ?? 0 );
	$s        = (string) ( $_GET['s'] ?? '' );

	if ( $validade < time() || ! hash_equals( apit_pedido_assinar( $chave, $validade ), $s ) ) {
		wp_die(
			esc_html__( 'Este link expirou. Volte à página e peça o documento de novo.', 'apit' ),
			esc_html__( 'Link expirado', 'apit' ),
			[ 'response' => 403, 'back_link' => true ]
		);
	}

	$item = apit_pedido_item( $chave );

	if ( ! $item ) {
		wp_die( esc_html__( 'Este documento não está disponível.', 'apit' ), '', [ 'response' => 404, 'back_link' => true ] );
	}

	$local = apit_ficheiro_local( $item['ficheiro'] );

	// Um ficheiro que não está neste servidor (um endereço externo) não pode ser entregue daqui.
	if ( ! $local ) {
		wp_redirect( esc_url_raw( $item['ficheiro'] ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- o endereço é o do próprio documento, escolhido no back-office.
		exit;
	}

	$tipo = wp_check_filetype( $local );
	$nome = sanitize_file_name( basename( $local ) );

	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Content-Type: ' . ( $tipo['type'] ?: 'application/octet-stream' ) );
	header( 'Content-Disposition: attachment; filename="' . $nome . '"; filename*=UTF-8\'\'' . rawurlencode( $nome ) );
	header( 'Content-Length: ' . filesize( $local ) );
	header( 'X-Robots-Tag: noindex, nofollow' );
	header( 'X-Content-Type-Options: nosniff' );

	readfile( $local );
	exit;
}
add_action( 'template_redirect', 'apit_pedido_entregar', 1 );

/* -------------------------------------------------------------------------
 * O diálogo
 * ---------------------------------------------------------------------- */

/**
 * O diálogo do formulário, uma vez por página: os Estatutos estão na barra
 * superior, que é de todas elas.
 */
function apit_pedido_dialogo() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	get_template_part( 'template-parts/pedido-documento' );
}
add_action( 'wp_footer', 'apit_pedido_dialogo', 5 );
