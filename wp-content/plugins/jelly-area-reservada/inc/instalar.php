<?php
/**
 * As tabelas da Área Reservada, e a criação delas.
 *
 * A AR guarda os seus dados em tabelas próprias, e não nas do WordPress: o
 * plugin leva o esquema consigo para qualquer WordPress, não depende de tipos
 * nem de campos do tema, e as listas — que podem crescer muito — filtram,
 * ordenam e paginam por colunas com índice, não por meta.
 *
 * Do WordPress usa só o login: um associado é um utilizador do WordPress com o
 * papel apit_associado, e é por ele que entra, define e recupera a palavra-
 * passe. O resto do associado está em jelly_ar_associados.
 *
 *   jelly_ar_associados        o perfil de cada associado, 1 para 1 com o
 *                              utilizador (user_id). O e-mail fica só no
 *                              wp_users, porque é com ele que se entra
 *   jelly_ar_acessos           uma linha por login de um associado
 *
 *   jelly_ar_doc_categorias    as categorias dos documentos
 *   jelly_ar_documentos        os documentos; o ficheiro está na pasta
 *                              uploads/jelly-area-reservada/documentos/
 *   jelly_ar_descargas         uma linha por descarga de um associado
 *   jelly_ar_evento_documentos que documentos tem cada evento: uma linha por
 *                              par. Um documento pode estar em vários
 *                              eventos, e um evento ter vários documentos
 *
 *   jelly_ar_evento_categorias as categorias dos eventos, com as duas cores
 *   jelly_ar_eventos           os eventos — do calendário do site e da AR
 *
 *   jelly_ar_mesas             as mesas de um evento com marcações
 *   jelly_ar_evento_horarios   os dias e as horas de marcação de um evento
 *   jelly_ar_marcacoes         os pedidos de mesa dos associados
 *   jelly_ar_registo           quem fez o quê nas marcações, mesas e horários
 *
 * Os estados guardam-se como texto (varchar) e não como ENUM: acrescentar um
 * estado não obriga a mudar o esquema, e os valores aceites são verificados no
 * PHP, antes de chegarem aqui.
 *
 * As datas e horas de registo estão em UTC, como as do WordPress; as datas dos
 * eventos e das marcações são dias e horas do calendário, sem fuso.
 */

defined( 'ABSPATH' ) || exit;

// Sobe quando o que jelly_ar_instalar() cria mudar, para ela voltar a correr.
define( 'JELLY_AR_DB_VERSION', '12' );

/**
 * O nome completo de uma tabela da AR: jelly_ar_tabela( 'eventos' ).
 */
function jelly_ar_tabela( $nome ) {
	global $wpdb;

	return $wpdb->prefix . 'jelly_ar_' . $nome;
}

/**
 * Um slug que ainda não existe, a partir do nome: "Eventos" → "eventos",
 * e "eventos-2" se esse já estiver tomado.
 */
function jelly_ar_slug_livre( $tabela, $nome ) {
	global $wpdb;

	$base = sanitize_title( $nome );
	$base = '' !== $base ? mb_substr( $base, 0, 70 ) : 'categoria';
	$slug = $base;

	for ( $n = 2; $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . jelly_ar_tabela( $tabela ) . ' WHERE slug = %s', $slug ) ); $n++ ) { // phpcs:ignore WordPress.DB
		$slug = $base . '-' . $n;
	}

	return $slug;
}

function jelly_ar_tabela_acessos() {
	return jelly_ar_tabela( 'acessos' );
}

function jelly_ar_tabela_descargas() {
	return jelly_ar_tabela( 'descargas' );
}

/**
 * O esquema, tabela a tabela, na forma que o dbDelta pede: uma coluna por
 * linha e dois espaços depois de PRIMARY KEY. O dbDelta cria o que falta e
 * acrescenta colunas e índices novos, sem apagar nada.
 */
function jelly_ar_esquema() {
	global $wpdb;

	$c = $wpdb->get_charset_collate();
	$t = 'jelly_ar_tabela';

	return [
		"CREATE TABLE {$t( 'associados' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			nome varchar(100) NOT NULL DEFAULT '',
			apelido varchar(100) NOT NULL DEFAULT '',
			telefone varchar(30) NOT NULL DEFAULT '',
			empresa varchar(150) NOT NULL DEFAULT '',
			estado varchar(20) NOT NULL DEFAULT 'pendente',
			registado_em datetime NOT NULL,
			aprovado_em datetime DEFAULT NULL,
			aprovado_por bigint(20) unsigned DEFAULT NULL,
			termos_em datetime DEFAULT NULL,
			atualizado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_id (user_id),
			KEY estado (estado),
			KEY apelido (apelido),
			KEY empresa (empresa)
		) {$c};",

		"CREATE TABLE {$t( 'acessos' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			criado_em datetime NOT NULL,
			ip varchar(45) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY criado_em (criado_em)
		) {$c};",

		"CREATE TABLE {$t( 'doc_categorias' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			nome varchar(80) NOT NULL,
			slug varchar(80) NOT NULL,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) {$c};",

		"CREATE TABLE {$t( 'documentos' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			titulo varchar(255) NOT NULL,
			descricao text NOT NULL,
			categoria_id bigint(20) unsigned DEFAULT NULL,
			estado varchar(20) NOT NULL DEFAULT 'publicado',
			ficheiro varchar(100) NOT NULL,
			nome_original varchar(255) NOT NULL,
			tipo varchar(10) NOT NULL,
			tamanho bigint(20) unsigned NOT NULL DEFAULT 0,
			autor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			criado_em datetime NOT NULL,
			atualizado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY estado (estado),
			KEY categoria_id (categoria_id),
			KEY criado_em (criado_em)
		) {$c};",

		"CREATE TABLE {$t( 'descargas' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			documento_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY documento_id (documento_id),
			KEY user_id (user_id)
		) {$c};",

		/*
		 * A ligação entre eventos e documentos, escolhida dos dois lados — no
		 * cartão Documentos do evento e no cartão Eventos do documento. Os dois
		 * leem e gravam aqui, e por isso mostram sempre o mesmo.
		 */
		"CREATE TABLE {$t( 'evento_documentos' )} (
			evento_id bigint(20) unsigned NOT NULL,
			documento_id bigint(20) unsigned NOT NULL,
			criado_em datetime NOT NULL,
			PRIMARY KEY  (evento_id,documento_id),
			KEY documento_id (documento_id)
		) {$c};",

		"CREATE TABLE {$t( 'evento_categorias' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			nome varchar(80) NOT NULL,
			slug varchar(80) NOT NULL,
			cor_inicio varchar(7) NOT NULL DEFAULT '#f41892',
			cor_fim varchar(7) NOT NULL DEFAULT '#e9edf0',
			criado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) {$c};",

		"CREATE TABLE {$t( 'eventos' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			titulo varchar(255) NOT NULL,
			slug varchar(200) NOT NULL DEFAULT '',
			resumo text NOT NULL,
			categoria_id bigint(20) unsigned DEFAULT NULL,
			inicio date NOT NULL,
			fim date DEFAULT NULL,
			local varchar(150) NOT NULL DEFAULT '',
			onde varchar(20) NOT NULL DEFAULT 'site',
			marcacoes tinyint(1) NOT NULL DEFAULT 0,
			botao_texto varchar(60) NOT NULL DEFAULT '',
			estado varchar(20) NOT NULL DEFAULT 'publicado',
			autor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			criado_em datetime NOT NULL,
			atualizado_em datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY calendario (estado,onde,fim),
			KEY slug (slug),
			KEY inicio (inicio),
			KEY categoria_id (categoria_id)
		) {$c};",

		"CREATE TABLE {$t( 'mesas' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			evento_id bigint(20) unsigned NOT NULL,
			nome varchar(80) NOT NULL,
			localizacao varchar(150) NOT NULL DEFAULT '',
			lugares smallint(5) unsigned NOT NULL DEFAULT 4,
			ordem smallint(5) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY evento_id (evento_id)
		) {$c};",

		/*
		 * Um período de marcações de um dia por linha: um dia pode ter vários
		 * (10:00–12:00 e 13:00–15:00), que não se sobrepõem — isso verifica-se
		 * no PHP (jelly_ar_horarios_guardar(), inc/mesas.php).
		 */
		"CREATE TABLE {$t( 'evento_horarios' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			evento_id bigint(20) unsigned NOT NULL,
			dia date NOT NULL,
			hora_inicio time NOT NULL,
			hora_fim time NOT NULL,
			intervalo smallint(5) unsigned NOT NULL DEFAULT 30,
			PRIMARY KEY  (id),
			KEY evento_periodo (evento_id,dia,hora_inicio)
		) {$c};",

		/*
		 * Um bloco (uma mesa, num dia, a uma hora) leva vários associados, até
		 * aos lugares da mesa; esse limite é verificado no PHP
		 * (inc/mesas-dados.php). `ocupa` é 1 enquanto a marcação prende um
		 * lugar (pendente ou aprovada) e NULL quando o solta (rejeitada ou
		 * cancelada). Como dois NULL nunca colidem numa chave única, a chave
		 * (mesa, dia, hora, associado, ocupa) impede o mesmo associado duas
		 * vezes no mesmo bloco e deixa as rejeitadas e as canceladas ficar no
		 * histórico.
		 */
		"CREATE TABLE {$t( 'marcacoes' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			evento_id bigint(20) unsigned NOT NULL,
			mesa_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			dia date NOT NULL,
			hora time NOT NULL,
			estado varchar(20) NOT NULL DEFAULT 'pendente',
			ocupa tinyint(1) DEFAULT 1,
			pedido_em datetime NOT NULL,
			decidido_em datetime DEFAULT NULL,
			decidido_por bigint(20) unsigned DEFAULT NULL,
			notas text NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY lugar (mesa_id,dia,hora,user_id,ocupa),
			KEY evento_id (evento_id),
			KEY user_id (user_id),
			KEY estado (estado)
		) {$c};",

		/*
		 * O registo do que se faz nas marcações, nas mesas e nos horários: uma
		 * linha por ação, com quem a fez (autor_id: a equipa, ou o associado
		 * que pediu) e a quem diz respeito (associado_id, nas marcações). O
		 * resumo fica escrito no momento — "Ana Silva · Mesa 1 · 7 out 10:00" —
		 * para se ler mesmo depois de a mesa ou o horário mudarem.
		 */
		"CREATE TABLE {$t( 'registo' )} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			criado_em datetime NOT NULL,
			autor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			acao varchar(40) NOT NULL,
			evento_id bigint(20) unsigned DEFAULT NULL,
			marcacao_id bigint(20) unsigned DEFAULT NULL,
			associado_id bigint(20) unsigned DEFAULT NULL,
			resumo text NOT NULL,
			PRIMARY KEY  (id),
			KEY evento_id (evento_id),
			KEY marcacao_id (marcacao_id),
			KEY associado_id (associado_id)
		) {$c};",
	];
}

function jelly_ar_instalar() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	foreach ( jelly_ar_esquema() as $sql ) {
		dbDelta( $sql );
	}

	// A pasta dos ficheiros, já fechada ao público antes do primeiro ficheiro.
	jelly_ar_pasta_documentos();

	if ( ! get_role( 'apit_associado' ) ) {
		add_role( 'apit_associado', __( 'Associado APIT', 'jelly-area-reservada' ), [ 'read' => true ] );
	}

	jelly_ar_limpar_migracao();

	// As categorias com que a área começa, se ainda não houver nenhuma.
	jelly_ar_criar_categorias_iniciais();

	update_option( 'jelly_ar_db_version', JELLY_AR_DB_VERSION );
}

/*
 * Os eventos vieram dos posts apit_evento do WordPress numa migração que já
 * correu e saiu do plugin (esquema 4). O que ela deixou para trás sai aqui: a
 * coluna que ligava cada evento ao post de onde veio, e o registo da passagem.
 * O dbDelta acrescenta colunas mas nunca as tira, por isso é à mão.
 */
function jelly_ar_limpar_migracao() {
	global $wpdb;

	$tabela = jelly_ar_tabela( 'eventos' );

	if ( $wpdb->get_var( "SHOW COLUMNS FROM {$tabela} LIKE 'origem_post_id'" ) ) { // phpcs:ignore WordPress.DB
		$wpdb->query( "ALTER TABLE {$tabela} DROP INDEX origem_post_id, DROP COLUMN origem_post_id" ); // phpcs:ignore WordPress.DB
	}

	delete_option( 'jelly_ar_migracao' );

	/*
	 * Esquema 7 → 8: um documento tinha um só evento, na coluna evento_id.
	 * Passou a poder ter vários, na tabela jelly_ar_evento_documentos, que o
	 * dbDelta já criou: o que estiver na coluna passa para lá, e a coluna sai.
	 */
	$documentos = jelly_ar_tabela( 'documentos' );

	if ( $wpdb->get_var( "SHOW COLUMNS FROM {$documentos} LIKE 'evento_id'" ) ) { // phpcs:ignore WordPress.DB
		$wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO ' . jelly_ar_tabela( 'evento_documentos' ) . " (evento_id, documento_id, criado_em) SELECT evento_id, id, %s FROM {$documentos} WHERE evento_id IS NOT NULL", current_time( 'mysql', true ) ) ); // phpcs:ignore WordPress.DB
		$wpdb->query( "ALTER TABLE {$documentos} DROP INDEX evento_id, DROP COLUMN evento_id" ); // phpcs:ignore WordPress.DB
	}

	/*
	 * Esquema 8 → 9: um bloco tinha um só associado, pela chave única `bloco`
	 * (mesa, dia, hora, ocupa). Passou a levar vários, até aos lugares da
	 * mesa: o dbDelta já criou a chave `lugar`, que tem também o associado, e
	 * a antiga sai aqui — o dbDelta nunca tira chaves.
	 */
	$marcacoes = jelly_ar_tabela( 'marcacoes' );

	if ( $wpdb->get_var( "SHOW INDEX FROM {$marcacoes} WHERE Key_name = 'bloco'" ) ) { // phpcs:ignore WordPress.DB
		$wpdb->query( "ALTER TABLE {$marcacoes} DROP INDEX bloco" ); // phpcs:ignore WordPress.DB
	}

	/*
	 * Esquema 10 → 11: um dia tinha um só horário, pela chave única
	 * `evento_dia`. Passou a poder ter vários períodos: o dbDelta já criou a
	 * chave `evento_periodo`, que não é única, e a antiga sai aqui. Os
	 * horários que já existem ficam, cada um como o único período do seu dia.
	 */
	$horarios = jelly_ar_tabela( 'evento_horarios' );

	if ( $wpdb->get_var( "SHOW INDEX FROM {$horarios} WHERE Key_name = 'evento_dia'" ) ) { // phpcs:ignore WordPress.DB
		$wpdb->query( "ALTER TABLE {$horarios} DROP INDEX evento_dia" ); // phpcs:ignore WordPress.DB
	}

	/*
	 * Esquema 11 → 12: os eventos ganham um slug, para o endereço da página na
	 * ARU (/area-reservada/eventos/conecta-2026/). Os que ainda não o têm
	 * recebem-no do título, do mais antigo para o mais novo.
	 */
	$eventos = jelly_ar_tabela( 'eventos' );

	foreach ( $wpdb->get_results( "SELECT id, titulo FROM {$eventos} WHERE slug = '' ORDER BY id" ) as $l ) { // phpcs:ignore WordPress.DB
		$wpdb->update( $eventos, [ 'slug' => jelly_ar_evento_slug( $l->titulo, (int) $l->id ) ], [ 'id' => $l->id ] ); // phpcs:ignore WordPress.DB
	}
}

/*
 * A ativação corre a instalação; esta verificação apanha o que a ativação não
 * apanha — uma atualização do plugin, ou o servidor, onde o plugin chega pelo
 * deploy já ativo na base de dados.
 */
function jelly_ar_verificar_instalacao() {
	if ( get_option( 'jelly_ar_db_version' ) !== JELLY_AR_DB_VERSION ) {
		jelly_ar_instalar();
	}
}
add_action( 'admin_init', 'jelly_ar_verificar_instalacao' );
