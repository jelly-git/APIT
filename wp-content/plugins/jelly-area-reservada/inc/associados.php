<?php
/**
 * Os associados ficam do lado do site: não entram no wp-admin nem veem a
 * barra de topo do WordPress.
 *
 * Um associado é quem tem o papel apit_associado (jelly_ar_e_associado(), em
 * inc/acessos.php). Se a mesma conta for também administrador, manda o papel
 * de administrador e nada disto se aplica.
 *
 * Ficam de fora do bloqueio o admin-ajax.php e o admin-post.php: vivem na
 * pasta wp-admin mas são as portas por onde os formulários do site falam com
 * o servidor, e o login e o registo do pop-up vão precisar delas. O
 * wp-login.php — entrar, sair, recuperar a palavra-passe — não está no
 * wp-admin e continua a funcionar.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Associado e só associado: com o papel de administrador também, não conta.
 */
function jelly_ar_so_associado( $user = null ) {
	$user = $user instanceof WP_User ? $user : wp_get_current_user();

	return jelly_ar_e_associado( $user ) && ! jelly_ar_e_administrador( $user );
}

/**
 * Para onde vai um associado que tenta entrar no wp-admin ou acaba de fazer
 * login. Hoje a página inicial; quando existir o painel do associado, passa a
 * ser ele, pelo filtro.
 */
function jelly_ar_destino_associado() {
	return apply_filters( 'jelly_ar_destino_associado', home_url( '/' ) );
}

function jelly_ar_barrar_wp_admin() {
	if ( ! jelly_ar_so_associado() || wp_doing_ajax() ) {
		return;
	}

	// admin-post.php corre admin_init também, e tem de passar.
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';

	if ( 'admin-post.php' === $script ) {
		return;
	}

	wp_safe_redirect( jelly_ar_destino_associado() );
	exit;
}
add_action( 'admin_init', 'jelly_ar_barrar_wp_admin', 0 );

function jelly_ar_sem_barra_de_topo( $mostrar ) {
	return jelly_ar_so_associado() ? false : $mostrar;
}
add_filter( 'show_admin_bar', 'jelly_ar_sem_barra_de_topo' );

/*
 * Depois do login, o WordPress manda para o wp-admin por omissão; um
 * associado vai para o site. Um destino pedido que seja do site mantém-se.
 */
function jelly_ar_destino_depois_do_login( $destino, $pedido, $user ) {
	if ( ! $user instanceof WP_User || ! jelly_ar_so_associado( $user ) ) {
		return $destino;
	}

	if ( '' === (string) $destino || 0 === strpos( $destino, admin_url() ) ) {
		return jelly_ar_destino_associado();
	}

	return $destino;
}
add_filter( 'login_redirect', 'jelly_ar_destino_depois_do_login', 10, 3 );

/* ---------- O perfil ---------- */

/*
 * O associado entra com a conta do WordPress (e-mail e palavra-passe), mas o
 * perfil — nome, empresa, telefone, estado e aprovação — está na tabela
 * jelly_ar_associados, uma linha por conta.
 */

/**
 * O perfil de um associado, ou null se a conta não tiver.
 */
function jelly_ar_associado( $user_id ) {
	global $wpdb;

	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . jelly_ar_tabela( 'associados' ) . ' WHERE user_id = %d', $user_id ) ); // phpcs:ignore WordPress.DB
}

/**
 * O estado do acesso: pendente, ativo, suspenso ou rejeitado. Sem perfil,
 * conta como pendente — ninguém fica com acesso por omissão.
 */
function jelly_ar_associado_estado( $user_id ) {
	$a = jelly_ar_associado( $user_id );

	return $a ? $a->estado : 'pendente';
}

/* ---------- Só entra quem está aprovado ---------- */

/*
 * A conta de um pedido de registo existe desde o primeiro minuto, mas só entra
 * depois de a APIT o aprovar. Corre a seguir à verificação da palavra-passe
 * (prioridade 30), para que uma palavra-passe errada continue a dar o erro de
 * sempre e só quem acertou fique a saber do estado do pedido.
 */
function jelly_ar_login_so_aprovados( $user ) {
	if ( ! $user instanceof WP_User || ! jelly_ar_so_associado( $user ) ) {
		return $user;
	}

	$estado    = jelly_ar_associado_estado( $user->ID );
	$mensagens = [
		'pendente'  => __( 'O pedido de acesso ainda está à espera de aprovação. Quando a APIT o aprovar, é enviado um e-mail.', 'jelly-area-reservada' ),
		'suspenso'  => __( 'O seu acesso à área reservada está suspenso. Para saber mais, contacte a APIT.', 'jelly-area-reservada' ),
		'rejeitado' => __( 'O seu pedido de acesso não foi aprovado. Para saber mais, contacte a APIT.', 'jelly-area-reservada' ),
	];

	if ( 'ativo' === $estado ) {
		return $user;
	}

	return new WP_Error( 'jelly_ar_sem_acesso', $mensagens[ $estado ] ?? $mensagens['pendente'] );
}
add_filter( 'authenticate', 'jelly_ar_login_so_aprovados', 30 );

/* ---------- Fora da lista de utilizadores do WordPress ---------- */

/*
 * Os associados gerem-se na Área Reservada. No ecrã Utilizadores do wp-admin
 * ficam só as contas da equipa: sem os associados na lista, no "Todos (N)" nem
 * no filtro por papel. A conta continua a existir — é ela que faz o login —,
 * só não aparece ali.
 *
 * Uma conta que seja também administrador não se esconde: é da equipa.
 */

/**
 * O SQL dos ids dos associados que não são administradores, pelo papel
 * guardado em wp_usermeta (wp_capabilities, serializado).
 */
function jelly_ar_associados_sql() {
	global $wpdb;

	return $wpdb->prepare(
		"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s AND meta_value NOT LIKE %s",
		$wpdb->get_blog_prefix() . 'capabilities',
		'%' . $wpdb->esc_like( '"apit_associado"' ) . '%',
		'%' . $wpdb->esc_like( '"administrator"' ) . '%'
	);
}

function jelly_ar_no_ecra_utilizadores() {
	global $pagenow;

	return is_admin() && 'users.php' === $pagenow;
}

function jelly_ar_esconder_associados( $consulta ) {
	global $wpdb;

	if ( ! jelly_ar_no_ecra_utilizadores() ) {
		return;
	}

	$consulta->query_where .= " AND {$wpdb->users}.ID NOT IN (" . jelly_ar_associados_sql() . ')';
}
add_action( 'pre_user_query', 'jelly_ar_esconder_associados' );

/**
 * Os separadores por cima da lista: sai o "Associado (N)" e o "Todos (N)"
 * deixa de os contar, para o número bater com as linhas.
 */
function jelly_ar_separadores_sem_associados( $separadores ) {
	global $wpdb;

	unset( $separadores['apit_associado'] );

	$escondidos = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM (' . jelly_ar_associados_sql() . ') a' ); // phpcs:ignore WordPress.DB

	if ( $escondidos && isset( $separadores['all'] ) ) {
		$total = count_users()['total_users'] - $escondidos;

		$separadores['all'] = preg_replace( '#<span class="count">\([^)]*\)</span>#', '<span class="count">(' . number_format_i18n( $total ) . ')</span>', $separadores['all'] );
	}

	return $separadores;
}
add_filter( 'views_users', 'jelly_ar_separadores_sem_associados' );
