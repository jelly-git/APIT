<?php
/**
 * Back-office da Área Reservada.
 *
 * Vive no wp-admin, com um menu "Área Reservada" e uma página por área. Nas
 * páginas dele o cromado do WordPress — menu lateral, barra de topo, rodapé,
 * avisos de outros plugins — sai do caminho e fica a interface da APIT em ecrã
 * inteiro, com a sua própria navegação e um link de volta ao WordPress.
 *
 * Nesta fase só há desenho: os conteúdos vêm de inc/admin-exemplo.php.
 */

defined( 'ABSPATH' ) || exit;

// Quem entra aqui decide-o jelly_ar_e_administrador(), em inc/acessos.php.

/**
 * As áreas do back-office, pela ordem do menu. A chave é o que vai no `page=`
 * do endereço e o nome do template em templates/admin/.
 *
 * Todas aparecem na navegação, para se ver desde já como o back-office se
 * arruma; só as que têm `pronta` são páginas. As outras ficam marcadas "em
 * breve" e ganham página quando o módulo delas chegar.
 */
function jelly_ar_admin_paginas() {
	return [
		'painel'       => [ 'titulo' => __( 'Painel', 'jelly-area-reservada' ), 'icone' => 'fa-gauge-high', 'pronta' => false ],
		'utilizadores' => [ 'titulo' => __( 'Utilizadores', 'jelly-area-reservada' ), 'icone' => 'fa-user-group', 'pronta' => true ],
		'eventos'      => [ 'titulo' => __( 'Eventos', 'jelly-area-reservada' ), 'icone' => 'fa-earth-europe', 'pronta' => true ],
		'mesas'        => [ 'titulo' => __( 'Mesas e horários', 'jelly-area-reservada' ), 'icone' => 'fa-table-cells-large', 'pronta' => false ],
		'marcacoes'    => [ 'titulo' => __( 'Aprovações', 'jelly-area-reservada' ), 'icone' => 'fa-circle-check', 'pronta' => false ],
		'documentos'   => [ 'titulo' => __( 'Documentos', 'jelly-area-reservada' ), 'icone' => 'fa-file-lines', 'pronta' => true ],
		'encontros'    => [ 'titulo' => __( 'Encontros', 'jelly-area-reservada' ), 'icone' => 'fa-people-group', 'pronta' => false ],
		'calendario'   => [ 'titulo' => __( 'Calendário', 'jelly-area-reservada' ), 'icone' => 'fa-calendar-days', 'pronta' => false ],
	];
}

/**
 * O `page=` de cada área. A entrada principal do menu leva à primeira área
 * pronta — hoje os Utilizadores; quando houver Painel, passa a ser ele.
 */
function jelly_ar_admin_slug( $chave ) {
	foreach ( jelly_ar_admin_paginas() as $c => $p ) {
		if ( $p['pronta'] ) {
			return $c === $chave ? 'jelly-ar' : 'jelly-ar-' . $chave;
		}
	}

	return 'jelly-ar-' . $chave;
}

function jelly_ar_admin_url( $chave, $args = [] ) {
	return add_query_arg( array_merge( [ 'page' => jelly_ar_admin_slug( $chave ) ], $args ), admin_url( 'admin.php' ) );
}

/**
 * A área em que se está, ou null fora do back-office.
 */
function jelly_ar_admin_atual() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	foreach ( jelly_ar_admin_paginas() as $chave => $pagina ) {
		if ( $pagina['pronta'] && jelly_ar_admin_slug( $chave ) === $page ) {
			return $chave;
		}
	}

	return null;
}

/*
 * Quem não é administrador nem vê o menu. Sem o menu registado, o WordPress
 * já recusa as páginas; jelly_ar_admin_barrar(), abaixo, recusa-as outra vez
 * por conta própria, para não depender só disso.
 */
function jelly_ar_admin_menu() {
	if ( ! jelly_ar_e_administrador() ) {
		return;
	}

	$paginas = jelly_ar_admin_paginas();
	$cap     = 'manage_options';

	add_menu_page(
		__( 'Área Reservada', 'jelly-area-reservada' ),
		__( 'Área Reservada', 'jelly-area-reservada' ),
		$cap,
		'jelly-ar',
		'jelly_ar_admin_render',
		'dashicons-lock',
		3
	);

	foreach ( $paginas as $chave => $pagina ) {
		if ( ! $pagina['pronta'] ) {
			continue;
		}
		add_submenu_page( 'jelly-ar', $pagina['titulo'], $pagina['titulo'], $cap, jelly_ar_admin_slug( $chave ), 'jelly_ar_admin_render' );
	}
}
add_action( 'admin_menu', 'jelly_ar_admin_menu' );

/*
 * Qualquer pedido a uma página do back-office — as prontas e as que ainda não
 * existem — é recusado a quem não é administrador, antes de se carregar o
 * que quer que seja.
 */
function jelly_ar_admin_barrar() {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( ( 'jelly-ar' === $page || 0 === strpos( $page, 'jelly-ar-' ) ) && ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}
}
add_action( 'admin_init', 'jelly_ar_admin_barrar', 1 );

function jelly_ar_admin_assets() {
	if ( ! jelly_ar_admin_atual() ) {
		return;
	}

	// As mesmas fontes que o tema usa no site.
	wp_enqueue_style( 'jelly-ar-omnes', 'https://use.typekit.net/uqy3rtf.css', [], null );
	wp_enqueue_style( 'jelly-ar-fa', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css', [], '6.7.2' );
	wp_enqueue_style( 'jelly-ar-admin', JELLY_AR_URL . 'assets/css/admin.css', [], jelly_ar_versao_ficheiro( 'assets/css/admin.css' ) );
	wp_enqueue_script( 'jelly-ar-admin', JELLY_AR_URL . 'assets/js/admin.js', [], jelly_ar_versao_ficheiro( 'assets/js/admin.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'jelly_ar_admin_assets' );

function jelly_ar_admin_body_class( $classes ) {
	return jelly_ar_admin_atual() ? $classes . ' jelly-ar-admin' : $classes;
}
add_filter( 'admin_body_class', 'jelly_ar_admin_body_class' );

/*
 * Os avisos de outros plugins (atualizações, licenças, Elementor) vêm por estes
 * ganchos e cairiam no meio da interface. Nas nossas páginas não se imprimem.
 */
function jelly_ar_admin_sem_avisos() {
	if ( jelly_ar_admin_atual() ) {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
	}
}
add_action( 'in_admin_header', 'jelly_ar_admin_sem_avisos', 1000 );

function jelly_ar_admin_render() {
	$chave = jelly_ar_admin_atual();

	if ( ! $chave || ! jelly_ar_e_administrador() ) {
		return;
	}

	require_once JELLY_AR_DIR . 'inc/admin-exemplo.php';

	jelly_ar_template( 'admin/shell', [ 'chave' => $chave ] );
}

/* ---------- Pequenos auxiliares dos templates ---------- */

/**
 * Um sim ou não numa coluna das listas: o visto ou o traço, sem texto à vista.
 * O leitor de ecrã lê o que o ícone quer dizer — $sim ou $nao —, e o mesmo
 * aparece ao passar o rato.
 */
function jelly_ar_marca( $valor, $sim, $nao ) {
	if ( $valor ) {
		printf(
			'<span class="jar-marca" title="%1$s"><i class="fa-solid fa-check" aria-hidden="true"></i><span class="screen-reader-text">%1$s</span></span>',
			esc_attr( $sim )
		);
		return;
	}

	printf( '<span class="jar-marca jar-marca--nao" title="%1$s"><span aria-hidden="true">—</span><span class="screen-reader-text">%1$s</span></span>', esc_attr( $nao ) );
}

/**
 * Etiqueta de estado. As cores estão no CSS, por estado.
 */
function jelly_ar_estado( $estado ) {
	$nomes = [
		// Utilizadores.
		'ativo'     => __( 'Ativo', 'jelly-area-reservada' ),
		'pendente'  => __( 'Por aprovar', 'jelly-area-reservada' ),
		'suspenso'  => __( 'Suspenso', 'jelly-area-reservada' ),
		'rejeitado' => __( 'Rejeitado', 'jelly-area-reservada' ),
		// Marcações, no perfil.
		'aprovada'  => __( 'Aprovada', 'jelly-area-reservada' ),
		'rejeitada' => __( 'Rejeitada', 'jelly-area-reservada' ),
		// Documentos.
		'publicado' => __( 'Publicado', 'jelly-area-reservada' ),
		'rascunho'  => __( 'Rascunho', 'jelly-area-reservada' ),
	];

	printf(
		'<span class="jar-estado jar-estado--%1$s">%2$s</span>',
		esc_attr( $estado ),
		esc_html( $nomes[ $estado ] ?? $estado )
	);
}

/**
 * "25/09/2026 14:32" em duas linhas, a hora por baixo e mais leve: a coluna
 * fica com a largura da data e não da data e da hora juntas.
 */
function jelly_ar_data_hora( $valor ) {
	if ( '' === (string) $valor ) {
		echo '—';
		return;
	}

	$partes = explode( ' ', $valor, 2 );

	printf(
		'<span class="jar-data">%1$s%2$s</span>',
		esc_html( $partes[0] ),
		isset( $partes[1] ) ? '<small>' . esc_html( $partes[1] ) . '</small>' : ''
	);
}

/**
 * Iniciais para o avatar dos utilizadores de exemplo, que não têm fotografia.
 */
function jelly_ar_iniciais( $nome ) {
	$partes = preg_split( '/\s+/', trim( $nome ) );
	$primeira = mb_substr( $partes[0], 0, 1 );
	$ultima   = count( $partes ) > 1 ? mb_substr( end( $partes ), 0, 1 ) : '';

	return mb_strtoupper( $primeira . $ultima );
}
