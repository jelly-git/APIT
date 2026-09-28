<?php
/**
 * Os meus dados, na ARU (/area-reservada/perfil/, templates/aru/perfil.php):
 * os dois formulários que o associado pode enviar sobre a própria conta.
 *
 * - Dados: só o telefone e a empresa. O nome e o e-mail mostram-se, mas não
 *   se mudam aqui — identificam a conta, e mudam-se pela equipa.
 * - Palavra-passe: a atual, a nova e a confirmação, com as regras de quando
 *   se define pela primeira vez (inc/sessao.php). A sessão continua aberta, e
 *   sai o e-mail da AR a avisar da mudança.
 *
 * Os dois vão ao admin-post.php (que o bloqueio do wp-admin deixa passar,
 * inc/associados.php) e voltam à página com um aviso (?aviso=) ou um erro
 * (?erro=). Mexem sempre na conta com sessão, nunca num id vindo do pedido.
 */

defined( 'ABSPATH' ) || exit;

/**
 * De volta à página dos dados, com o aviso ou o erro.
 */
function jelly_ar_perfil_voltar( $args ) {
	wp_safe_redirect( add_query_arg( $args, jelly_ar_area_url( 'perfil' ) ) . '#' . ( isset( $args['senha'] ) ? 'jar-senha' : 'jar-dados' ) );
	exit;
}

/**
 * Só quem tem acesso à ARU envia estes formulários.
 */
function jelly_ar_perfil_pedido( $nonce ) {
	if ( ! is_user_logged_in() || ! jelly_ar_area_tem_acesso() ) {
		wp_safe_redirect( home_url( '/#area-reservada' ) );
		exit;
	}

	check_admin_referer( $nonce );
}

/* ---------- O telefone e a empresa ---------- */

function jelly_ar_perfil_guardar() {
	global $wpdb;

	jelly_ar_perfil_pedido( 'jelly_ar_perfil' );

	$user_id = get_current_user_id();
	$perfil  = jelly_ar_associado( $user_id );

	// A equipa não tem perfil de associado: não há telefone nem empresa para guardar.
	if ( ! $perfil ) {
		jelly_ar_perfil_voltar( [ 'erro' => 'sem-perfil' ] );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado em jelly_ar_perfil_pedido()
	$telefone = isset( $_POST['telefone'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['telefone'] ) ), 0, 30 ) : '';
	$empresa  = isset( $_POST['empresa'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['empresa'] ) ), 0, 150 ) : '';
	// phpcs:enable

	// A mesma regra do registo (inc/registo.php).
	if ( '' !== $telefone && ! preg_match( '/^\+?[\d\s().-]{9,20}$/', $telefone ) ) {
		jelly_ar_perfil_voltar( [ 'erro' => 'telefone' ] );
	}

	$wpdb->update( // phpcs:ignore WordPress.DB
		jelly_ar_tabela( 'associados' ),
		[
			'telefone'      => jelly_ar_telefone( $telefone ),
			'empresa'       => $empresa,
			'atualizado_em' => current_time( 'mysql', true ),
		],
		[ 'user_id' => $user_id ]
	);

	jelly_ar_perfil_voltar( [ 'aviso' => 'dados' ] );
}
add_action( 'admin_post_jelly_ar_perfil', 'jelly_ar_perfil_guardar' );

/* ---------- A palavra-passe ---------- */

function jelly_ar_perfil_senha() {
	jelly_ar_perfil_pedido( 'jelly_ar_perfil_senha' );

	$user = wp_get_current_user();

	// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- verificado acima; uma palavra-passe não se limpa
	$atual  = isset( $_POST['atual'] ) ? (string) wp_unslash( $_POST['atual'] ) : '';
	$senha  = isset( $_POST['senha'] ) ? (string) wp_unslash( $_POST['senha'] ) : '';
	$senha2 = isset( $_POST['senha2'] ) ? (string) wp_unslash( $_POST['senha2'] ) : '';
	// phpcs:enable

	if ( jelly_ar_excedido( 'perfil_senha', 10, HOUR_IN_SECONDS ) ) {
		jelly_ar_perfil_voltar( [ 'erro' => 'tentativas', 'senha' => 1 ] );
	}

	if ( ! wp_check_password( $atual, $user->user_pass, $user->ID ) ) {
		jelly_ar_perfil_voltar( [ 'erro' => 'senha-atual', 'senha' => 1 ] );
	}

	// As regras de quando se define (jelly_ar_definir_senha(), inc/sessao.php).
	if ( mb_strlen( $senha ) < JELLY_AR_SENHA_MINIMO || '' === trim( $senha ) ) {
		jelly_ar_perfil_voltar( [ 'erro' => 'senha-curta', 'senha' => 1 ] );
	}
	if ( 0 === strcasecmp( $senha, $user->user_email ) ) {
		jelly_ar_perfil_voltar( [ 'erro' => 'senha-email', 'senha' => 1 ] );
	}
	if ( $senha2 !== $senha ) {
		jelly_ar_perfil_voltar( [ 'erro' => 'senha-diferente', 'senha' => 1 ] );
	}

	/*
	 * wp_update_user() muda a palavra-passe e, por ser a conta com sessão,
	 * volta a abri-la (as outras sessões fecham). O e-mail do WordPress
	 * ("Password Changed", em inglês e texto simples) não sai: sai o da AR.
	 */
	add_filter( 'send_password_change_email', '__return_false' );
	$feito = wp_update_user( [ 'ID' => $user->ID, 'user_pass' => $senha ] );

	if ( is_wp_error( $feito ) ) {
		jelly_ar_perfil_voltar( [ 'erro' => 'senha-falhou', 'senha' => 1 ] );
	}

	$perfil = jelly_ar_associado( $user->ID );

	if ( $perfil ) {
		jelly_ar_email_depois( 'jelly_ar_email_senha_definida', [ get_userdata( $user->ID ), $perfil ] );
	}

	jelly_ar_perfil_voltar( [ 'aviso' => 'senha', 'senha' => 1 ] );
}
add_action( 'admin_post_jelly_ar_perfil_senha', 'jelly_ar_perfil_senha' );
