<?php
/**
 * Os documentos a sério: onde ficam os ficheiros, como se guardam, como se
 * descarregam e como se apagam.
 *
 * Os ficheiros ficam em wp-content/uploads/jelly-area-reservada/documentos/:
 * uma pasta com o nome do plugin, e dentro dela uma por área — a dos
 * documentos é a primeira. Cada ficheiro tem um nome aleatório, para que um
 * endereço não se adivinhe, e a pasta está fechada ao público: nem o
 * endereço direto nem a listagem da pasta dão nada. O ficheiro só sai por
 * jelly_ar_descarregar(), que confirma quem pede.
 *
 * Nada disto vai para a Biblioteca de Media do WordPress: tudo o que lá está
 * tem um endereço público.
 *
 * Os documentos estão na tabela jelly_ar_documentos, as categorias em
 * jelly_ar_doc_categorias e as descargas dos associados em
 * jelly_ar_descargas (inc/instalar.php). As descargas dos administradores
 * não contam.
 *
 * Atenção ao servidor: a pasta fecha-se com um .htaccess, que o Apache lê. Um
 * servidor nginx ignora-o, e aí é preciso uma regra na configuração dele para
 * a pasta — está no DEPLOY.md.
 */

defined( 'ABSPATH' ) || exit;

// O maior ficheiro aceite. O PHP do servidor pode aceitar menos: ver jelly_ar_documento_maximo().
const JELLY_AR_DOC_MAXIMO = 50 * MB_IN_BYTES;

/**
 * Os tipos aceites e, para cada um, os tipos MIME em que o conteúdo pode
 * chegar. Os do Office são ZIP por dentro, e há servidores que os leem assim.
 */
const JELLY_AR_DOC_TIPOS = [
	'pdf'  => [ 'application/pdf' ],
	'docx' => [ 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream' ],
	'xlsx' => [ 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream' ],
	'pptx' => [ 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream' ],
	'zip'  => [ 'application/zip', 'application/x-zip-compressed', 'application/octet-stream' ],
];

/*
 * As categorias com que a área começa, se a tabela estiver vazia. Depois disso
 * o back-office cria, muda e apaga (inc/documentos-categorias.php).
 */
const JELLY_AR_DOC_CATEGORIAS_INICIAIS = [
	'mercados'       => 'Guias de mercado',
	'eventos'        => 'Eventos',
	'formularios'    => 'Formulários',
	'institucional'  => 'Institucional',
	'regulamentos'   => 'Regulamentos',
	'watch-portugal' => 'Watch Portugal',
];

function jelly_ar_criar_categorias_iniciais() {
	global $wpdb;

	$tabela = jelly_ar_tabela( 'doc_categorias' );

	if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabela}" ) > 0 ) { // phpcs:ignore WordPress.DB
		return;
	}

	foreach ( JELLY_AR_DOC_CATEGORIAS_INICIAIS as $slug => $nome ) {
		$wpdb->insert( $tabela, [ 'nome' => $nome, 'slug' => $slug, 'criado_em' => current_time( 'mysql', true ) ] ); // phpcs:ignore WordPress.DB
	}
}

/**
 * As categorias, por ordem alfabética: slug => nome.
 */
function jelly_ar_documento_categorias() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	return wp_list_pluck( $wpdb->get_results( 'SELECT slug, nome FROM ' . jelly_ar_tabela( 'doc_categorias' ) . ' ORDER BY nome' ), 'nome', 'slug' );
}

/**
 * O id de uma categoria pelo slug, ou 0.
 */
function jelly_ar_documento_categoria_id( $slug ) {
	global $wpdb;

	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . jelly_ar_tabela( 'doc_categorias' ) . ' WHERE slug = %s', $slug ) ); // phpcs:ignore WordPress.DB
}

/* ---------- A pasta ---------- */

/**
 * O caminho da pasta dos documentos, criada e fechada se ainda não estiver.
 * Devolve '' se não se conseguir criar.
 */
function jelly_ar_pasta_documentos() {
	$uploads = wp_upload_dir( null, false );
	$raiz    = trailingslashit( $uploads['basedir'] ) . 'jelly-area-reservada';
	$pasta   = $raiz . '/documentos';

	if ( ! wp_mkdir_p( $pasta ) ) {
		return '';
	}

	// As duas pastas levam a proteção: a de cima também, para as áreas que vierem.
	foreach ( [ $raiz, $pasta ] as $p ) {
		if ( ! file_exists( $p . '/.htaccess' ) ) {
			// "Require" é do Apache 2.4; o bloco de baixo, do 2.2.
			file_put_contents( $p . '/.htaccess', "# Jelly — Área Reservada: os ficheiros só saem pelo plugin.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n" );
		}
		if ( ! file_exists( $p . '/index.php' ) ) {
			file_put_contents( $p . '/index.php', "<?php\n// Silêncio.\n" );
		}
	}

	return $pasta;
}

/**
 * O maior ficheiro que se pode carregar: o do plugin ou o do PHP, o menor.
 */
function jelly_ar_documento_maximo() {
	return min( JELLY_AR_DOC_MAXIMO, wp_max_upload_size() );
}

/* ---------- Ler ---------- */

/**
 * A consulta base: o documento com a categoria e o número de descargas.
 */
function jelly_ar_documentos_consulta( $onde = '' ) {
	global $wpdb;

	$d  = jelly_ar_tabela( 'documentos' );
	$c  = jelly_ar_tabela( 'doc_categorias' );
	$dl = jelly_ar_tabela( 'descargas' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	return $wpdb->get_results( "SELECT d.*, c.slug AS cat_slug, (SELECT COUNT(*) FROM {$dl} x WHERE x.documento_id = d.id) AS descargas FROM {$d} d LEFT JOIN {$c} c ON c.id = d.categoria_id {$onde}" );
}

/**
 * Uma linha da tabela na forma que os ecrãs usam — a mesma dos dados de
 * exemplo.
 */
function jelly_ar_documento_da_linha( $l ) {
	$autor = get_userdata( (int) $l->autor_id );

	return [
		'id'        => (int) $l->id,
		'titulo'    => $l->titulo,
		'descricao' => $l->descricao,
		'categoria' => (string) $l->cat_slug,
		'tipo'      => $l->tipo,
		'ficheiro'  => $l->nome_original,
		'guardado'  => $l->ficheiro,
		'tamanho'   => (int) $l->tamanho,
		'data'      => get_date_from_gmt( $l->criado_em, 'd/m/Y H:i' ),
		'estado'    => $l->estado,
		'autor'     => $autor ? $autor->display_name : '',
		'descargas' => (int) $l->descargas,
		'real'      => true,
	];
}

function jelly_ar_documentos_reais() {
	return array_map( 'jelly_ar_documento_da_linha', jelly_ar_documentos_consulta( 'ORDER BY d.criado_em DESC' ) );
}

function jelly_ar_documento_real( $id ) {
	global $wpdb;

	$linhas = jelly_ar_documentos_consulta( $wpdb->prepare( 'WHERE d.id = %d', $id ) );

	return $linhas ? jelly_ar_documento_da_linha( $linhas[0] ) : null;
}

/**
 * Quem descarregou um documento, do mais recente para o mais antigo, com o
 * nome e a empresa do perfil do associado.
 */
function jelly_ar_descargas_reais( $documento, $limite = 0 ) {
	global $wpdb;

	$sql = $wpdb->prepare(
		'SELECT d.user_id, d.criado_em, u.display_name, u.user_email, a.nome, a.apelido, a.empresa FROM ' . jelly_ar_tabela( 'descargas' ) . " d LEFT JOIN {$wpdb->users} u ON u.ID = d.user_id LEFT JOIN " . jelly_ar_tabela( 'associados' ) . ' a ON a.user_id = d.user_id WHERE d.documento_id = %d ORDER BY d.criado_em DESC',
		$documento
	);

	if ( $limite ) {
		$sql .= ' LIMIT ' . (int) $limite;
	}

	return array_map( function ( $l ) {
		$nome = trim( $l->nome . ' ' . $l->apelido );

		return [
			'utilizador' => (int) $l->user_id,
			'nome'       => '' !== $nome ? $nome : $l->display_name,
			'email'      => $l->user_email,
			'empresa'    => (string) $l->empresa,
			'quando'     => get_date_from_gmt( $l->criado_em, 'd/m/Y H:i' ),
		];
	}, $wpdb->get_results( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
}

/* ---------- Guardar ---------- */

/**
 * O que os dois formulários — novo documento e edição — têm em comum antes de
 * gravar: a permissão, o nonce e um pedido que tenha chegado inteiro.
 * $voltar recebe o código do erro e não volta.
 */
function jelly_ar_documento_pedido_valido( $nonce, $voltar ) {
	/*
	 * Um pedido maior do que o post_max_size do PHP chega sem nada — nem campos
	 * nem ficheiro —, e o nonce falharia com uma mensagem que não diz porquê.
	 */
	if ( empty( $_POST ) && ! empty( $_SERVER['CONTENT_LENGTH'] ) ) {
		$voltar( 'grande' );
	}

	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( $nonce );
}

/**
 * O título, a categoria e a descrição do pedido, já limpos, com a categoria
 * já como id. Falta o título ou a categoria não existe: erro "campos".
 */
function jelly_ar_documento_campos( $voltar ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado em jelly_ar_documento_pedido_valido()
	$titulo    = isset( $_POST['titulo'] ) ? sanitize_text_field( wp_unslash( $_POST['titulo'] ) ) : '';
	$categoria = isset( $_POST['categoria'] ) ? jelly_ar_documento_categoria_id( sanitize_title( wp_unslash( $_POST['categoria'] ) ) ) : 0;
	$descricao = isset( $_POST['descricao'] ) ? sanitize_textarea_field( wp_unslash( $_POST['descricao'] ) ) : '';
	// phpcs:enable

	if ( '' === $titulo || ! $categoria ) {
		$voltar( 'campos' );
	}

	return [
		'titulo'       => mb_substr( $titulo, 0, 255 ),
		'categoria_id' => $categoria,
		'descricao'    => $descricao,
	];
}

/**
 * Recebe o ficheiro enviado no campo "ficheiro": valida-o pelo tipo real do
 * conteúdo e não só pela extensão, e guarda-o na pasta com um nome aleatório.
 *
 * Devolve as colunas dele, ou null se não veio nenhum e não era obrigatório.
 * Qualquer problema vai para $voltar.
 */
function jelly_ar_receber_ficheiro( $obrigatorio, $voltar ) {
	$ficheiro = $_FILES['ficheiro'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing

	if ( ! $ficheiro || UPLOAD_ERR_NO_FILE === $ficheiro['error'] ) {
		if ( $obrigatorio ) {
			$voltar( 'sem-ficheiro' );
		}
		return null;
	}

	if ( in_array( $ficheiro['error'], [ UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ], true ) || $ficheiro['size'] > jelly_ar_documento_maximo() ) {
		$voltar( 'grande' );
	}

	if ( UPLOAD_ERR_OK !== $ficheiro['error'] || ! is_uploaded_file( $ficheiro['tmp_name'] ) ) {
		$voltar( 'falhou' );
	}

	$nome = sanitize_file_name( wp_unslash( $ficheiro['name'] ) );
	$tipo = strtolower( pathinfo( $nome, PATHINFO_EXTENSION ) );

	if ( ! isset( JELLY_AR_DOC_TIPOS[ $tipo ] ) ) {
		$voltar( 'tipo' );
	}

	// O tipo real do conteúdo, para um executável não entrar com o nome de um PDF.
	if ( function_exists( 'finfo_open' ) ) {
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime  = finfo_file( $finfo, $ficheiro['tmp_name'] );
		finfo_close( $finfo );

		if ( ! in_array( $mime, JELLY_AR_DOC_TIPOS[ $tipo ], true ) ) {
			$voltar( 'tipo' );
		}
	}

	$pasta = jelly_ar_pasta_documentos();

	if ( '' === $pasta ) {
		$voltar( 'pasta' );
	}

	$guardado = wp_generate_password( 24, false ) . '.' . $tipo;

	if ( ! move_uploaded_file( $ficheiro['tmp_name'], $pasta . '/' . $guardado ) ) {
		$voltar( 'falhou' );
	}

	// As mesmas permissões que o WordPress dá aos uploads.
	$stat = stat( $pasta );
	chmod( $pasta . '/' . $guardado, ( $stat['mode'] & 0000666 ) ?: 0644 );

	return [
		'ficheiro'      => $guardado,
		'nome_original' => mb_substr( $nome, 0, 255 ),
		'tipo'          => $tipo,
		'tamanho'       => (int) $ficheiro['size'],
	];
}

/**
 * O formulário Novo documento chega aqui (admin-post.php): o ficheiro primeiro,
 * e só com ele guardado se cria o documento.
 */
function jelly_ar_guardar_documento() {
	global $wpdb;

	$voltar = function ( $erro ) {
		wp_safe_redirect( jelly_ar_admin_url( 'documentos', [ 'novo' => 1, 'erro' => $erro ] ) );
		exit;
	};

	jelly_ar_documento_pedido_valido( 'jelly_ar_guardar_documento', $voltar );

	$campos   = jelly_ar_documento_campos( $voltar );
	$publicar = isset( $_POST['estado'] ) && 'rascunho' !== $_POST['estado']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$ficheiro = jelly_ar_receber_ficheiro( true, $voltar );
	$agora    = current_time( 'mysql', true );

	$feito = $wpdb->insert( // phpcs:ignore WordPress.DB
		jelly_ar_tabela( 'documentos' ),
		$campos + $ficheiro + [
			'estado'        => $publicar ? 'publicado' : 'rascunho',
			'autor_id'      => get_current_user_id(),
			'criado_em'     => $agora,
			'atualizado_em' => $agora,
		]
	);

	// Sem documento, o ficheiro não fica órfão na pasta.
	if ( false === $feito ) {
		wp_delete_file( jelly_ar_pasta_documentos() . '/' . $ficheiro['ficheiro'] );
		$voltar( 'falhou' );
	}

	wp_safe_redirect( jelly_ar_admin_url( 'documentos', [ 'documento' => (int) $wpdb->insert_id, 'aviso' => 'guardado' ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_guardar_documento', 'jelly_ar_guardar_documento' );

/**
 * A edição dos dados de um documento: título, categoria, descrição e, se vier
 * um, o ficheiro novo. O antigo só se apaga depois de o novo estar guardado —
 * um carregamento que falhe deixa o documento como estava.
 */
function jelly_ar_editar_documento() {
	global $wpdb;

	$id     = isset( $_POST['documento'] ) ? absint( $_POST['documento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$voltar = function ( $erro ) use ( $id ) {
		wp_safe_redirect( jelly_ar_admin_url( 'documentos', [ 'documento' => $id, 'erro' => $erro, 'editar' => 1 ] ) );
		exit;
	};

	jelly_ar_documento_pedido_valido( 'jelly_ar_editar_documento_' . $id, $voltar );

	$doc = jelly_ar_documento_real( $id );

	if ( ! $doc ) {
		wp_die( esc_html__( 'Esse documento não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	$campos = jelly_ar_documento_campos( $voltar );
	$novo   = jelly_ar_receber_ficheiro( false, $voltar );

	$wpdb->update( // phpcs:ignore WordPress.DB
		jelly_ar_tabela( 'documentos' ),
		$campos + ( $novo ? $novo : [] ) + [ 'atualizado_em' => current_time( 'mysql', true ) ],
		[ 'id' => $id ]
	);

	if ( $novo && $doc['guardado'] !== $novo['ficheiro'] ) {
		wp_delete_file( jelly_ar_pasta_documentos() . '/' . basename( $doc['guardado'] ) );
	}

	wp_safe_redirect( jelly_ar_admin_url( 'documentos', [ 'documento' => $id, 'aviso' => $novo ? 'substituido' : 'atualizado' ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_editar_documento', 'jelly_ar_editar_documento' );

/* ---------- Apagar ---------- */

function jelly_ar_apagar_documento() {
	global $wpdb;

	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	$id = isset( $_POST['documento'] ) ? absint( $_POST['documento'] ) : 0;

	check_admin_referer( 'jelly_ar_apagar_documento_' . $id );

	$doc = jelly_ar_documento_real( $id );

	if ( ! $doc ) {
		wp_die( esc_html__( 'Esse documento não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	// O ficheiro, o histórico de descargas e o documento — nada fica para trás.
	$pasta = jelly_ar_pasta_documentos();

	if ( '' !== $doc['guardado'] && '' !== $pasta ) {
		wp_delete_file( $pasta . '/' . basename( $doc['guardado'] ) );
	}

	$wpdb->delete( jelly_ar_tabela( 'descargas' ), [ 'documento_id' => $id ], [ '%d' ] ); // phpcs:ignore WordPress.DB
	$wpdb->delete( jelly_ar_tabela( 'documentos' ), [ 'id' => $id ], [ '%d' ] ); // phpcs:ignore WordPress.DB

	wp_safe_redirect( jelly_ar_admin_url( 'documentos', [ 'aviso' => 'apagado' ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_apagar_documento', 'jelly_ar_apagar_documento' );

/* ---------- Descarregar ---------- */

/**
 * O endereço de descarga de um documento. Serve o back-office e, mais tarde,
 * a área reservada do site: é o mesmo, e é ele que decide quem pode.
 */
function jelly_ar_url_descarregar( $documento ) {
	return add_query_arg(
		[
			'action'    => 'jelly_ar_descarregar',
			'documento' => (int) $documento,
		],
		admin_url( 'admin-post.php' )
	);
}

/**
 * Pode descarregar quem é administrador, ou um associado com o acesso ativo,
 * e este só os documentos publicados.
 */
function jelly_ar_pode_descarregar( $user, $doc ) {
	if ( jelly_ar_e_administrador( $user ) ) {
		return true;
	}

	return jelly_ar_so_associado( $user )
		&& 'ativo' === jelly_ar_associado_estado( $user->ID )
		&& 'publicado' === $doc['estado'];
}

function jelly_ar_descarregar() {
	global $wpdb;

	$id   = isset( $_GET['documento'] ) ? absint( $_GET['documento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$doc  = jelly_ar_documento_real( $id );
	$user = wp_get_current_user();

	if ( ! $doc || ! jelly_ar_pode_descarregar( $user, $doc ) ) {
		wp_die( esc_html__( 'Não tem acesso a este documento.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	$pasta   = jelly_ar_pasta_documentos();
	$caminho = $pasta . '/' . basename( $doc['guardado'] );

	if ( '' === $pasta || ! is_file( $caminho ) ) {
		wp_die( esc_html__( 'O ficheiro deste documento não foi encontrado.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	// Só as descargas dos associados contam; as da equipa não são uso da área.
	if ( jelly_ar_so_associado( $user ) ) {
		$wpdb->insert( // phpcs:ignore WordPress.DB
			jelly_ar_tabela( 'descargas' ),
			[
				'documento_id' => $id,
				'user_id'      => $user->ID,
				'criado_em'    => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%s' ]
		);
	}

	// Nada do que já estiver no buffer pode ir colado ao ficheiro.
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Content-Type: ' . ( JELLY_AR_DOC_TIPOS[ $doc['tipo'] ][0] ?? 'application/octet-stream' ) );
	header( 'Content-Length: ' . filesize( $caminho ) );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $doc['ficheiro'] ) . '"; filename*=UTF-8\'\'' . rawurlencode( $doc['ficheiro'] ) );
	header( 'X-Content-Type-Options: nosniff' );

	readfile( $caminho ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_jelly_ar_descarregar', 'jelly_ar_descarregar' );

/*
 * Sem sessão, a descarga leva ao login da área reservada. Depois de entrar, a
 * pessoa volta a carregar no documento.
 */
function jelly_ar_descarregar_sem_sessao() {
	wp_safe_redirect( home_url( '/#area-reservada' ) );
	exit;
}
add_action( 'admin_post_nopriv_jelly_ar_descarregar', 'jelly_ar_descarregar_sem_sessao' );
