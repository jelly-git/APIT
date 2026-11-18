<?php
/**
 * Entrar, recuperar a palavra-passe e defini-la — tudo no pop-up do site, com
 * o desenho dele, e não nas páginas do WordPress (wp-login.php).
 *
 * Os três formulários (templates/form-login.php, form-recuperar.php e
 * form-senha.php) falam com o admin-ajax, como o registo, e a resposta é JSON:
 * { sucesso }, { erros: { campo: mensagem } } ou { mensagem }.
 *
 * A ligação para definir a palavra-passe (dos e-mails de aprovação e de
 * recuperação) abre o site com o pop-up no painel "senha":
 *   /?ar-chave=<chave>&ar-conta=<login>#area-reservada-senha
 * A chave é a da recuperação do WordPress (get_password_reset_key()), que já
 * sabe expirar e servir uma vez só. Quem chegar à página do WordPress com uma
 * dessas chaves e for associado é reencaminhado para aqui.
 *
 * Contra abusos: um nonce em cada formulário, um limite de tentativas por IP
 * e, nos pedidos que mandam e-mail, outro por endereço. Nenhuma resposta diz
 * se um e-mail tem conta — nem pelo tempo que leva.
 *
 * É a porta dos associados: as contas da equipa entram e definem a palavra-passe
 * pelo WordPress (wp-login.php), com o que lá houver de proteção.
 */

defined( 'ABSPATH' ) || exit;

// O mínimo de caracteres de uma palavra-passe. O mesmo no browser (assets/js/area-reservada.js).
const JELLY_AR_SENHA_MINIMO = 10;

/**
 * Conta mais uma tentativa de $acao e diz se passou de $maximo em $janela
 * segundos. Conta-se por IP ou, com $quem, por outra coisa — um e-mail, para
 * os pedidos que mandam correio a alguém. O IP e o e-mail guardam-se só como
 * hash.
 *
 * Ler e somar ficam dentro de um trinco do MySQL (GET_LOCK): sem ele, vários
 * pedidos ao mesmo tempo liam todos a mesma conta e passavam todos.
 */
function jelly_ar_excedido( $acao, $maximo, $janela, $quem = null ) {
	global $wpdb;

	if ( null === $quem ) {
		$quem = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

	$chave  = 'jelly_ar_' . $acao . '_' . md5( strtolower( $quem ) . wp_salt( 'nonce' ) );
	$trinco = '1' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $chave ) ); // phpcs:ignore WordPress.DB

	$conta    = (int) get_transient( $chave );
	$excedido = $conta >= $maximo;

	if ( ! $excedido ) {
		set_transient( $chave, $conta + 1, $janela );
	}

	if ( $trinco ) {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $chave ) ); // phpcs:ignore WordPress.DB
	}

	return $excedido;
}

// Quantos e-mails de recuperação ou de registo um mesmo endereço recebe por hora.
const JELLY_AR_EMAILS_POR_ENDERECO = 3;

function jelly_ar_responder( $dados, $estado = 200 ) {
	wp_send_json( $dados, $estado );
}

function jelly_ar_pedido_valido( $nonce ) {
	if ( ! check_ajax_referer( $nonce, '_wpnonce', false ) ) {
		jelly_ar_responder( [ 'mensagem' => __( 'A página esteve aberta muito tempo. É necessário recarregá-la e tentar de novo.', 'jelly-area-reservada' ) ], 403 );
	}
}

/**
 * A ligação para definir a palavra-passe, no pop-up do site. '' se a chave não
 * se puder criar.
 */
function jelly_ar_ligacao_senha( $user ) {
	$chave = get_password_reset_key( $user );

	// Sem chave não há e-mail: o motivo fica no último erro de envio, que o back-office mostra.
	if ( is_wp_error( $chave ) ) {
		jelly_ar_email_erro_guardar(
			$user->user_email,
			/* translators: %s: o erro do WordPress */
			sprintf( __( 'O WordPress não criou a ligação para definir a palavra-passe, e o e-mail não chegou a ser enviado: %s', 'jelly-area-reservada' ), $chave->get_error_message() )
		);
		return '';
	}

	return jelly_ar_url_senha( $chave, $user->user_login );
}

function jelly_ar_url_senha( $chave, $login ) {
	return add_query_arg(
		[
			'ar-chave' => rawurlencode( $chave ),
			'ar-conta' => rawurlencode( $login ),
		],
		home_url( '/' )
	) . '#area-reservada-senha';
}

/**
 * A chave e a conta do endereço, para o painel "senha": a conta (WP_User), se
 * a chave for válida; o erro, se não; null sem chave no endereço.
 */
function jelly_ar_senha_pedida() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- a chave é o que autoriza
	if ( empty( $_GET['ar-chave'] ) || empty( $_GET['ar-conta'] ) ) {
		return null;
	}

	$chave = sanitize_text_field( wp_unslash( $_GET['ar-chave'] ) );
	$conta = sanitize_user( wp_unslash( $_GET['ar-conta'] ) );
	// phpcs:enable

	$user = check_password_reset_key( $chave, $conta );

	return is_wp_error( $user ) ? $user : [ 'user' => $user, 'chave' => $chave, 'conta' => $conta ];
}

/*
 * A página que chega com a chave no endereço não fica em cache nenhuma, e não
 * diz o endereço a quem carregar a seguir: a chave vale uma palavra-passe. O
 * JavaScript tira-a do endereço logo ao abrir (assets/js/area-reservada.js);
 * o formulário já a tem, num campo escondido.
 */
function jelly_ar_senha_cabecalhos() {
	if ( isset( $_GET['ar-chave'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		nocache_headers();
		header( 'Referrer-Policy: no-referrer' );
	}
}
add_action( 'send_headers', 'jelly_ar_senha_cabecalhos' );

/*
 * Um associado que chegue à página do WordPress para definir a palavra-passe
 * (uma ligação antiga, ou a recuperação do próprio WordPress) vai para o
 * painel do site. Os administradores continuam na do WordPress.
 */
function jelly_ar_senha_no_site() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$chave = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
	$conta = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ) ) : '';
	// phpcs:enable

	$user = $conta ? get_user_by( 'login', $conta ) : false;

	if ( $chave && $user && jelly_ar_so_associado( $user ) ) {
		wp_safe_redirect( jelly_ar_url_senha( $chave, $conta ) );
		exit;
	}
}
add_action( 'login_form_rp', 'jelly_ar_senha_no_site' );
add_action( 'login_form_resetpass', 'jelly_ar_senha_no_site' );

/* ---------- Entrar ---------- */

function jelly_ar_entrar() {
	jelly_ar_pedido_valido( 'jelly_ar_entrar' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado em jelly_ar_pedido_valido()
	$email    = isset( $_POST['user_login'] ) ? sanitize_email( wp_unslash( $_POST['user_login'] ) ) : '';
	$senha    = isset( $_POST['user_pass'] ) ? (string) wp_unslash( $_POST['user_pass'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- uma palavra-passe não se limpa
	$lembrar  = ! empty( $_POST['remember'] );
	// phpcs:enable

	$erros = [];
	if ( '' === $email || ! is_email( $email ) ) {
		$erros['user_login'] = __( 'É necessário um e-mail válido, como nome@empresa.pt.', 'jelly-area-reservada' );
	}
	if ( '' === $senha ) {
		$erros['user_pass'] = __( 'É necessário preencher a palavra-passe.', 'jelly-area-reservada' );
	}
	if ( $erros ) {
		jelly_ar_responder( [ 'erros' => $erros ], 422 );
	}

	if ( jelly_ar_excedido( 'entrar', 8, 15 * MINUTE_IN_SECONDS ) ) {
		jelly_ar_responder( [ 'mensagem' => __( 'Foram feitas demasiadas tentativas a partir desta ligação. É possível tentar de novo daqui a 15 minutos.', 'jelly-area-reservada' ) ], 429 );
	}

	$errado = __( 'O e-mail ou a palavra-passe não estão corretos.', 'jelly-area-reservada' );
	$user   = get_user_by( 'email', $email );

	/*
	 * Sem conta, calcula-se na mesma um hash da palavra-passe: o tempo que a
	 * verificação de uma conta real leva. Sem isto, a resposta mais rápida
	 * dizia que o e-mail não está registado, apesar da mensagem ser a mesma.
	 */
	if ( ! $user ) {
		wp_hash_password( $senha );
		jelly_ar_responder( [ 'mensagem' => $errado ], 401 );
	}

	/*
	 * Primeiro só se verifica (wp_authenticate(), com os filtros de sempre: a
	 * palavra-passe, e depois o estado do associado, inc/associados.php); a
	 * sessão só se abre a seguir, e só a um associado.
	 */
	$verificado = wp_authenticate( $user->user_login, $senha );

	if ( is_wp_error( $verificado ) ) {
		// Um acesso por aprovar, suspenso ou rejeitado diz porquê (inc/associados.php); o resto, a mensagem de sempre.
		$mensagem = 'jelly_ar_sem_acesso' === $verificado->get_error_code() ? $verificado->get_error_message() : $errado;
		jelly_ar_responder( [ 'mensagem' => $mensagem ], 401 );
	}

	/*
	 * O pop-up é a porta dos associados. A equipa entra pelo login do
	 * WordPress, onde fica qualquer proteção que o WordPress lá tenha (um
	 * segundo passo de autenticação, por exemplo) — e que este caminho
	 * saltaria. Só se diz a quem acertou a palavra-passe.
	 */
	if ( ! jelly_ar_so_associado( $verificado ) ) {
		jelly_ar_responder(
			[
				/* translators: %s: endereço do login do WordPress */
				'mensagem' => sprintf( __( 'Esta conta é da equipa da APIT: o acesso faz-se pelo back-office, em %s.', 'jelly-area-reservada' ), wp_login_url() ),
			],
			403
		);
	}

	// O que o wp_signon() faria depois de verificar: a sessão, e o wp_login (que regista o acesso, inc/acessos.php).
	wp_set_auth_cookie( $verificado->ID, $lembrar, is_ssl() );
	do_action( 'wp_login', $verificado->user_login, $verificado );

	jelly_ar_responder(
		[
			'sucesso' => true,
			'destino' => jelly_ar_destino_associado(),
		]
	);
}
add_action( 'wp_ajax_nopriv_jelly_ar_entrar', 'jelly_ar_entrar' );
add_action( 'wp_ajax_jelly_ar_entrar', 'jelly_ar_entrar' );

/* ---------- Esqueceu-se da palavra-passe? ---------- */

/*
 * A resposta é sempre a mesma, haja ou não conta: não diz a ninguém quem está
 * registado. Só um associado com o acesso ativo recebe o e-mail — um pedido
 * por aprovar recebe a ligação ao ser aprovado, e um suspenso não entraria.
 */
function jelly_ar_recuperar() {
	jelly_ar_pedido_valido( 'jelly_ar_recuperar' );

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( '' === $email || ! is_email( $email ) ) {
		jelly_ar_responder( [ 'erros' => [ 'email' => __( 'É necessário um e-mail válido, como nome@empresa.pt.', 'jelly-area-reservada' ) ] ], 422 );
	}

	if ( jelly_ar_excedido( 'recuperar', 5, HOUR_IN_SECONDS ) ) {
		jelly_ar_responder( [ 'mensagem' => __( 'Foram feitos demasiados pedidos a partir desta ligação. É possível tentar de novo daqui a uma hora.', 'jelly-area-reservada' ) ], 429 );
	}

	$user   = get_user_by( 'email', $email );
	$perfil = $user && jelly_ar_so_associado( $user ) ? jelly_ar_associado( $user->ID ) : null;

	/*
	 * Também por endereço: com muitos IPs, alguém podia encher a caixa de um
	 * associado — e cada pedido invalida a ligação do anterior. Passado o
	 * limite, a resposta é a mesma, mas não sai e-mail.
	 */
	if ( $perfil && 'ativo' === $perfil->estado && ! jelly_ar_excedido( 'recuperar_email', JELLY_AR_EMAILS_POR_ENDERECO, HOUR_IN_SECONDS, $email ) ) {
		jelly_ar_email_nova_senha( $user, $perfil );
	}

	jelly_ar_responder( [ 'sucesso' => true ] );
}
add_action( 'wp_ajax_nopriv_jelly_ar_recuperar', 'jelly_ar_recuperar' );
add_action( 'wp_ajax_jelly_ar_recuperar', 'jelly_ar_recuperar' );

/* ---------- Definir a palavra-passe ---------- */

function jelly_ar_definir_senha() {
	jelly_ar_pedido_valido( 'jelly_ar_senha' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- verificado acima; uma palavra-passe não se limpa
	$chave  = isset( $_POST['chave'] ) ? sanitize_text_field( wp_unslash( $_POST['chave'] ) ) : '';
	$conta  = isset( $_POST['conta'] ) ? sanitize_user( wp_unslash( $_POST['conta'] ) ) : '';
	$senha  = isset( $_POST['senha'] ) ? (string) wp_unslash( $_POST['senha'] ) : '';
	$senha2 = isset( $_POST['senha2'] ) ? (string) wp_unslash( $_POST['senha2'] ) : '';
	// phpcs:enable

	if ( jelly_ar_excedido( 'senha', 10, HOUR_IN_SECONDS ) ) {
		jelly_ar_responder( [ 'mensagem' => __( 'Foram feitas demasiadas tentativas a partir desta ligação. É possível tentar de novo daqui a uma hora.', 'jelly-area-reservada' ) ], 429 );
	}

	$user = check_password_reset_key( $chave, $conta );

	if ( is_wp_error( $user ) ) {
		jelly_ar_responder( [ 'mensagem' => __( 'A ligação expirou ou já foi utilizada. É possível pedir uma nova em «Esqueceu-se da palavra-passe?».', 'jelly-area-reservada' ), 'expirada' => true ], 410 );
	}

	// Como no entrar: as contas da equipa tratam da palavra-passe no WordPress, não aqui.
	if ( ! jelly_ar_so_associado( $user ) ) {
		jelly_ar_responder(
			[
				/* translators: %s: endereço da recuperação do WordPress */
				'mensagem' => sprintf( __( 'Esta conta é da equipa da APIT: a palavra-passe define-se no back-office, em %s.', 'jelly-area-reservada' ), wp_lostpassword_url() ),
			],
			403
		);
	}

	$erros = [];
	if ( mb_strlen( $senha ) < JELLY_AR_SENHA_MINIMO || '' === trim( $senha ) ) {
		/* translators: %d: número de caracteres */
		$erros['senha'] = sprintf( __( 'A palavra-passe tem de ter pelo menos %d caracteres.', 'jelly-area-reservada' ), JELLY_AR_SENHA_MINIMO );
	} elseif ( 0 === strcasecmp( $senha, $user->user_email ) ) {
		$erros['senha'] = __( 'A palavra-passe não pode ser o próprio e-mail.', 'jelly-area-reservada' );
	}
	if ( $senha2 !== $senha ) {
		$erros['senha2'] = __( 'As duas palavras-passe não são iguais.', 'jelly-area-reservada' );
	}
	if ( $erros ) {
		jelly_ar_responder( [ 'erros' => $erros ], 422 );
	}

	/*
	 * Muda a palavra-passe e apaga a chave: a ligação já não serve outra vez.
	 * O aviso do WordPress ao administrador ("Password changed", em inglês e
	 * texto simples) não sai: o e-mail da AR, abaixo, é o que conta.
	 */
	remove_action( 'after_password_reset', 'wp_password_change_notification' );
	reset_password( $user, $senha );

	// A conclusão do registo, ou o aviso de que a palavra-passe mudou (inc/emails.php).
	$perfil = jelly_ar_associado( $user->ID );

	if ( $perfil ) {
		jelly_ar_email_senha_definida( $user, $perfil );
	}

	/*
	 * Quem tem acesso à Área Reservada fica já com a sessão iniciada, sem voltar
	 * a escrever o que acabou de escolher: a ligação do e-mail provou que a
	 * conta é sua. O painel "Palavra-passe definida" confirma, e o "Entrar"
	 * leva ao `destino` (assets/js/area-reservada.js). Uma sessão de outra conta
	 * neste browser dá lugar a esta. O wp_login regista o acesso
	 * (inc/acessos.php), como um login normal. Sem acesso, o "Entrar" abre o
	 * login, como sempre. Só associados chegam aqui (acima).
	 */
	if ( jelly_ar_area_tem_acesso( $user ) ) {
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, false, is_ssl() );
		do_action( 'wp_login', $user->user_login, $user );

		jelly_ar_responder(
			[
				'sucesso' => true,
				'email'   => $user->user_email,
				'destino' => jelly_ar_area_url(),
			]
		);
	}

	jelly_ar_responder( [ 'sucesso' => true, 'email' => $user->user_email ] );
}
add_action( 'wp_ajax_nopriv_jelly_ar_senha', 'jelly_ar_definir_senha' );
add_action( 'wp_ajax_jelly_ar_senha', 'jelly_ar_definir_senha' );
