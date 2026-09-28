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
	$e  = jelly_ar_tabela( 'eventos' );
	$ed = jelly_ar_tabela( 'evento_documentos' );

	/*
	 * Os eventos do documento vêm juntos numa coluna só, pela ordem das datas,
	 * para a lista os mostrar sem uma consulta por linha. Os do lixo não contam.
	 */
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	return $wpdb->get_results(
		"SELECT d.*, c.slug AS cat_slug, c.nome AS cat_nome,
			(SELECT COUNT(*) FROM {$dl} x WHERE x.documento_id = d.id) AS descargas,
			(SELECT GROUP_CONCAT(e.titulo ORDER BY e.inicio SEPARATOR '\n') FROM {$ed} l INNER JOIN {$e} e ON e.id = l.evento_id WHERE l.documento_id = d.id AND e.estado <> 'lixo') AS eventos_titulos
		FROM {$d} d LEFT JOIN {$c} c ON c.id = d.categoria_id {$onde}"
	);
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
		'eventos'   => '' !== (string) $l->eventos_titulos ? explode( "\n", $l->eventos_titulos ) : [],
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

	// O evento de onde se veio (o botão Novo documento do cartão dele), para voltar a ele.
	$origem = isset( $_POST['evento_origem'] ) ? absint( $_POST['evento_origem'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$origem = $origem && jelly_ar_evento( $origem ) ? $origem : 0;

	$voltar = function ( $erro ) use ( $origem ) {
		wp_safe_redirect( jelly_ar_admin_url( 'documentos', array_filter( [ 'novo' => 1, 'erro' => $erro, 'evento' => $origem ] ) ) );
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

	$id = (int) $wpdb->insert_id;

	// Os eventos escolhidos no formulário (os que existem e não estão no lixo).
	jelly_ar_ligacoes_gravar( 'documento', $id, jelly_ar_ligacoes_pedidas( 'eventos', wp_list_pluck( jelly_ar_eventos_todos(), 'id' ) ) );

	// Vindo de um evento, volta-se a ele; senão, à página do documento novo.
	wp_safe_redirect(
		$origem
			? jelly_ar_admin_url( 'eventos', [ 'evento' => $origem, 'aviso' => 'documento-novo' ] )
			: jelly_ar_admin_url( 'documentos', [ 'documento' => $id, 'aviso' => 'guardado' ] )
	);
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

	if ( jelly_ar_documento_exemplo_apagar( $id ) ) {
		wp_safe_redirect( jelly_ar_admin_url( 'documentos', [ 'aviso' => 'apagado' ] ) );
		exit;
	}

	$doc = jelly_ar_documento_real( $id );

	if ( ! $doc ) {
		wp_die( esc_html__( 'Esse documento não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	jelly_ar_documento_apagar( $doc );

	wp_safe_redirect( jelly_ar_admin_url( 'documentos', [ 'aviso' => 'apagado' ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_apagar_documento', 'jelly_ar_apagar_documento' );

/**
 * Apaga um documento: o ficheiro, o histórico de descargas, as ligações aos
 * eventos e o documento — nada fica para trás.
 */
function jelly_ar_documento_apagar( $doc ) {
	global $wpdb;

	$pasta = jelly_ar_pasta_documentos();

	if ( '' !== $doc['guardado'] && '' !== $pasta ) {
		wp_delete_file( $pasta . '/' . basename( $doc['guardado'] ) );
	}

	$wpdb->delete( jelly_ar_tabela( 'descargas' ), [ 'documento_id' => $doc['id'] ], [ '%d' ] ); // phpcs:ignore WordPress.DB
	$wpdb->delete( jelly_ar_tabela( 'evento_documentos' ), [ 'documento_id' => $doc['id'] ], [ '%d' ] ); // phpcs:ignore WordPress.DB
	$wpdb->delete( jelly_ar_tabela( 'documentos' ), [ 'id' => $doc['id'] ], [ '%d' ] ); // phpcs:ignore WordPress.DB
}

/**
 * Apagar um documento de exemplo: não tem ficheiro nem linha na base de dados,
 * por isso o id fica numa opção e o documento deixa de aparecer
 * (jelly_ar_exemplo_documentos()). Devolve false se o id não for de exemplo.
 */
function jelly_ar_documento_exemplo_apagar( $id ) {
	if ( ! JELLY_AR_EXEMPLO || $id < JELLY_AR_EXEMPLO_ID ) {
		return false;
	}

	$fora = array_map( 'intval', (array) get_option( 'jelly_ar_exemplo_documentos_apagados', [] ) );

	if ( ! in_array( $id, $fora, true ) ) {
		$fora[] = $id;
		update_option( 'jelly_ar_exemplo_documentos_apagados', $fora, false );
	}

	return true;
}

/**
 * Apagar vários documentos de uma vez, os escolhidos na lista (documento[]):
 * os da AR e os de exemplo. Um id que não exista ignora-se.
 */
function jelly_ar_apagar_documentos() {
	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( 'jelly_ar_apagar_documentos' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado acima
	$ids    = isset( $_POST['documento'] ) ? array_unique( array_map( 'absint', (array) wp_unslash( $_POST['documento'] ) ) ) : [];
	$voltar = isset( $_POST['voltar'] ) ? esc_url_raw( wp_unslash( $_POST['voltar'] ) ) : '';
	// phpcs:enable

	$n = 0;
	foreach ( $ids as $id ) {
		if ( jelly_ar_documento_exemplo_apagar( $id ) ) {
			$n++;
			continue;
		}

		$doc = jelly_ar_documento_real( $id );
		if ( $doc ) {
			jelly_ar_documento_apagar( $doc );
			$n++;
		}
	}

	// De volta à lista como estava — o separador, a categoria, a pesquisa.
	$destino = $voltar && 0 === strpos( $voltar, admin_url() ) ? $voltar : jelly_ar_admin_url( 'documentos' );

	wp_safe_redirect( add_query_arg( $n ? [ 'aviso' => 'apagados', 'n' => $n ] : [ 'aviso' => 'nenhum' ], remove_query_arg( [ 'aviso', 'n' ], $destino ) ) );
	exit;
}
add_action( 'admin_post_jelly_ar_apagar_documentos', 'jelly_ar_apagar_documentos' );

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

/* ---------- Eventos e documentos ---------- */

/*
 * Um documento pode estar em vários eventos, e um evento ter vários
 * documentos: a tabela jelly_ar_evento_documentos guarda um par por linha.
 * Escolhe-se dos dois lados — o cartão Documentos do evento e o cartão Eventos
 * do documento —, e os dois leem e gravam essa tabela: o que se marca de um
 * lado aparece já marcado do outro.
 */

/**
 * Os documentos de um evento, por título. Com $so_publicados, só os que os
 * associados veem — é o que a página do evento na área reservada vai usar.
 */
function jelly_ar_evento_documentos( $evento, $so_publicados = false ) {
	global $wpdb;

	$onde = $wpdb->prepare( 'WHERE d.id IN (SELECT documento_id FROM ' . jelly_ar_tabela( 'evento_documentos' ) . ' WHERE evento_id = %d)', $evento )
		. ( $so_publicados ? " AND d.estado = 'publicado'" : '' );

	return array_map( 'jelly_ar_documento_da_linha', jelly_ar_documentos_consulta( $onde . ' ORDER BY d.titulo' ) );
}

/**
 * Os ids dos eventos de um documento. Os do lixo contam: se o evento for
 * recuperado, volta com os seus documentos.
 */
function jelly_ar_documento_eventos_ids( $documento ) {
	global $wpdb;

	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT evento_id FROM ' . jelly_ar_tabela( 'evento_documentos' ) . ' WHERE documento_id = %d', $documento ) ) ); // phpcs:ignore WordPress.DB
}

/**
 * Os eventos de um documento, pela ordem das datas, sem os do lixo.
 */
function jelly_ar_documento_eventos( $documento ) {
	$ids     = jelly_ar_documento_eventos_ids( $documento );
	$eventos = array_values( array_filter( jelly_ar_eventos_todos(), function ( $e ) use ( $ids ) {
		return in_array( $e['id'], $ids, true );
	} ) );

	// A mesma ordem que a lista de Documentos usa: do mais cedo para o mais tarde.
	usort( $eventos, function ( $a, $b ) {
		return strcmp( $a['inicio'], $b['inicio'] );
	} );

	return $eventos;
}

/**
 * Grava as ligações de um lado: para o evento (ou o documento) $id, ficam
 * exatamente as de $escolhidos. As que não vêm saem, as novas entram, e as
 * dos outros eventos (ou documentos) não se tocam.
 *
 * @param string $lado 'evento' ou 'documento' — de qual dos dois é o $id.
 */
function jelly_ar_ligacoes_gravar( $lado, $id, $escolhidos ) {
	global $wpdb;

	$tabela = jelly_ar_tabela( 'evento_documentos' );
	$este   = 'evento' === $lado ? 'evento_id' : 'documento_id';
	$outro  = 'evento' === $lado ? 'documento_id' : 'evento_id';
	$manter = $escolhidos ? " AND {$outro} NOT IN (" . implode( ',', $escolhidos ) . ')' : '';

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$tabela} WHERE {$este} = %d{$manter}", $id ) ); // phpcs:ignore WordPress.DB

	foreach ( $escolhidos as $o ) {
		$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$tabela} ({$este}, {$outro}, criado_em) VALUES (%d, %d, %s)", $id, $o, current_time( 'mysql', true ) ) ); // phpcs:ignore WordPress.DB
	}
}

/**
 * Os ids que chegaram marcados, só os que existem: documentos da AR, ou
 * eventos que não estão no lixo.
 */
function jelly_ar_ligacoes_pedidas( $campo, $validos ) {
	$pedidos = isset( $_POST[ $campo ] ) ? array_map( 'absint', (array) wp_unslash( $_POST[ $campo ] ) ) : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	return array_values( array_intersect( array_unique( $pedidos ), $validos ) );
}

function jelly_ar_ligacoes_pode() {
	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}
}

/**
 * O cartão Documentos do evento.
 */
function jelly_ar_evento_documentos_guardar() {
	$id = isset( $_POST['evento'] ) ? absint( $_POST['evento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	jelly_ar_ligacoes_pode();
	check_admin_referer( 'jelly_ar_evento_documentos_' . $id );

	if ( ! jelly_ar_evento( $id ) ) {
		wp_die( esc_html__( 'Esse evento não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	jelly_ar_ligacoes_gravar( 'evento', $id, jelly_ar_ligacoes_pedidas( 'documentos', wp_list_pluck( jelly_ar_documentos_reais(), 'id' ) ) );

	wp_safe_redirect( jelly_ar_admin_url( 'eventos', [ 'evento' => $id, 'aviso' => 'documentos' ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_evento_documentos', 'jelly_ar_evento_documentos_guardar' );

/**
 * O cartão Eventos do documento.
 */
function jelly_ar_documento_eventos_guardar() {
	$id = isset( $_POST['documento'] ) ? absint( $_POST['documento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	jelly_ar_ligacoes_pode();
	check_admin_referer( 'jelly_ar_documento_eventos_' . $id );

	if ( ! jelly_ar_documento_real( $id ) ) {
		wp_die( esc_html__( 'Esse documento não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	/*
	 * Os eventos do lixo não aparecem no cartão, por isso não vêm no pedido;
	 * juntam-se aqui, para gravar do cartão não os desligar sem se ver.
	 */
	$lixo = array_diff( jelly_ar_documento_eventos_ids( $id ), wp_list_pluck( jelly_ar_eventos_todos(), 'id' ) );

	jelly_ar_ligacoes_gravar( 'documento', $id, array_merge( jelly_ar_ligacoes_pedidas( 'eventos', wp_list_pluck( jelly_ar_eventos_todos(), 'id' ) ), $lixo ) );

	wp_safe_redirect( jelly_ar_admin_url( 'documentos', [ 'documento' => $id, 'aviso' => 'eventos' ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_documento_eventos', 'jelly_ar_documento_eventos_guardar' );

/**
 * Os ids dos eventos que têm pelo menos um documento — para a coluna
 * Documentos da lista de eventos, numa consulta só.
 */
function jelly_ar_eventos_com_documentos() {
	global $wpdb;

	return array_map( 'intval', $wpdb->get_col( 'SELECT DISTINCT evento_id FROM ' . jelly_ar_tabela( 'evento_documentos' ) ) ); // phpcs:ignore WordPress.DB
}
