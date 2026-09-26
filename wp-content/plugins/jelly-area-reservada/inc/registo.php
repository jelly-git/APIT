<?php
/**
 * O pedido de registo do pop-up do site.
 *
 * O formulário (templates/form-registo.php) é enviado pelo pop-up, sem sair da
 * página, para o admin-ajax, e a resposta é JSON: o sucesso, os erros por
 * campo, ou uma mensagem geral.
 *
 * Um pedido aceite cria duas coisas:
 * - a conta do WordPress, com o papel apit_associado e uma palavra-passe
 *   aleatória que ninguém conhece. É só para o login: a pessoa define a sua
 *   quando a APIT aprovar o pedido;
 * - o perfil na tabela jelly_ar_associados, com o estado "pendente" e a hora
 *   do registo e da aceitação da Política de Privacidade.
 *
 * Enquanto o estado não for "ativo", a conta não entra (inc/associados.php).
 *
 * Avisa-se a equipa da APIT, para aprovar no back-office, e a pessoa, para
 * saber que o pedido chegou.
 *
 * Contra abusos: um nonce, um campo-armadilha que as pessoas não veem e os
 * robôs preenchem, e um limite de pedidos por IP. E um e-mail já registado
 * recebe a mesma resposta que um novo — a resposta não diz a ninguém quem já
 * tem conta. Quem é dono desse e-mail recebe um aviso na caixa de correio.
 */

defined( 'ABSPATH' ) || exit;

// Quantos pedidos um IP pode fazer por hora.
const JELLY_AR_REGISTO_LIMITE = 5;

/**
 * Os endereços da equipa que recebem os pedidos novos. Por omissão o e-mail
 * de administração do WordPress (Definições → Geral).
 */
function jelly_ar_emails_equipa() {
	return (array) apply_filters( 'jelly_ar_emails_equipa', [ get_option( 'admin_email' ) ] );
}

/**
 * Responde ao pop-up e termina.
 */
function jelly_ar_registo_responder( $dados, $estado = 200 ) {
	wp_send_json( $dados, $estado );
}

/**
 * Os campos do pedido, limpos, e os erros de cada um — as mesmas regras que o
 * pop-up aplica no browser, verificadas outra vez aqui, porque o browser não
 * é de confiança.
 */
function jelly_ar_registo_campos() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado em jelly_ar_registar()
	$texto = function ( $campo, $maximo ) {
		$valor = isset( $_POST[ $campo ] ) ? sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) : '';

		return mb_substr( $valor, 0, $maximo );
	};

	/*
	 * O e-mail escrito, para saber se veio vazio: sanitize_email() devolve ''
	 * também a um endereço inválido, e a pessoa leria "preencha este campo"
	 * sobre um campo que preencheu.
	 */
	$escrito = $texto( 'email', 100 );

	$campos = [
		'first_name' => $texto( 'first_name', 100 ),
		'last_name'  => $texto( 'last_name', 100 ),
		'email'      => sanitize_email( $escrito ),
		'telefone'   => $texto( 'telefone', 30 ),
		'empresa'    => $texto( 'empresa', 150 ),
		'termos'     => ! empty( $_POST['termos'] ),
	];
	// phpcs:enable

	$erros = [];

	if ( '' === $campos['first_name'] ) {
		$erros['first_name'] = __( 'Preencha este campo.', 'jelly-area-reservada' );
	}
	if ( '' === $campos['last_name'] ) {
		$erros['last_name'] = __( 'Preencha este campo.', 'jelly-area-reservada' );
	}
	if ( '' === $escrito ) {
		$erros['email'] = __( 'Preencha este campo.', 'jelly-area-reservada' );
	} elseif ( ! is_email( $campos['email'] ) ) {
		$erros['email'] = __( 'Escreva um e-mail válido, como nome@empresa.pt.', 'jelly-area-reservada' );
	}
	if ( '' !== $campos['telefone'] && ! preg_match( '/^\+?[\d\s().-]{9,20}$/', $campos['telefone'] ) ) {
		$erros['telefone'] = __( 'Escreva um número de telefone válido.', 'jelly-area-reservada' );
	}
	if ( ! $campos['termos'] ) {
		$erros['termos'] = __( 'Tem de aceitar a Política de Privacidade.', 'jelly-area-reservada' );
	}

	return [ $campos, $erros ];
}

/**
 * Conta mais um pedido deste IP e diz se passou do limite. O IP guarda-se só
 * como hash, e só por uma hora.
 */
function jelly_ar_registo_excedido() {
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$chave = 'jelly_ar_registo_' . md5( $ip . wp_salt( 'nonce' ) );
	$conta = (int) get_transient( $chave );

	if ( $conta >= JELLY_AR_REGISTO_LIMITE ) {
		return true;
	}

	set_transient( $chave, $conta + 1, HOUR_IN_SECONDS );

	return false;
}

/**
 * Um nome de utilizador livre, a partir do e-mail: ninguém o escreve — entra-se
 * pelo e-mail —, mas o WordPress precisa de um.
 */
function jelly_ar_registo_login( $email ) {
	$base  = sanitize_user( strtok( $email, '@' ), true );
	$base  = '' !== $base ? mb_substr( $base, 0, 50 ) : 'associado';
	$login = $base;

	for ( $n = 2; username_exists( $login ); $n++ ) {
		$login = $base . $n;
	}

	return $login;
}

function jelly_ar_registar() {
	global $wpdb;

	if ( ! check_ajax_referer( 'jelly_ar_registo', '_wpnonce', false ) ) {
		jelly_ar_registo_responder( [ 'mensagem' => __( 'A página esteve aberta muito tempo. Recarregue-a e envie o pedido outra vez.', 'jelly-area-reservada' ) ], 403 );
	}

	/*
	 * O campo-armadilha: escondido de quem vê a página, preenchido por quem lê
	 * o HTML. Responde-se como a um pedido bom, para o robô não aprender nada.
	 */
	if ( ! empty( $_POST['jelly_ar_hp'] ) ) {
		jelly_ar_registo_responder( [ 'sucesso' => true ] );
	}

	if ( is_user_logged_in() ) {
		jelly_ar_registo_responder( [ 'mensagem' => __( 'Já tem sessão iniciada nesta conta.', 'jelly-area-reservada' ) ], 400 );
	}

	list( $campos, $erros ) = jelly_ar_registo_campos();

	if ( $erros ) {
		jelly_ar_registo_responder( [ 'erros' => $erros ], 422 );
	}

	if ( jelly_ar_registo_excedido() ) {
		jelly_ar_registo_responder( [ 'mensagem' => __( 'Foram feitos demasiados pedidos a partir desta ligação. Tente de novo daqui a uma hora.', 'jelly-area-reservada' ) ], 429 );
	}

	// Já há conta com este e-mail: a mesma resposta, e o aviso vai para o dono do e-mail.
	$existente = get_user_by( 'email', $campos['email'] );

	if ( $existente ) {
		jelly_ar_registo_avisar_existente( $existente );
		jelly_ar_registo_responder( [ 'sucesso' => true ] );
	}

	$user_id = wp_insert_user( [
		'user_login'           => jelly_ar_registo_login( $campos['email'] ),
		'user_email'           => $campos['email'],
		'user_pass'            => wp_generate_password( 32, true, true ),
		'first_name'           => $campos['first_name'],
		'last_name'            => $campos['last_name'],
		'display_name'         => $campos['first_name'] . ' ' . $campos['last_name'],
		'role'                 => 'apit_associado',
		'show_admin_bar_front' => 'false',
	] );

	if ( is_wp_error( $user_id ) ) {
		jelly_ar_registo_responder( [ 'mensagem' => __( 'Não foi possível enviar o pedido. Tente de novo daqui a pouco.', 'jelly-area-reservada' ) ], 500 );
	}

	$agora = current_time( 'mysql', true );
	$feito = $wpdb->insert( // phpcs:ignore WordPress.DB
		jelly_ar_tabela( 'associados' ),
		[
			'user_id'       => $user_id,
			'nome'          => $campos['first_name'],
			'apelido'       => $campos['last_name'],
			'telefone'      => jelly_ar_telefone( $campos['telefone'] ),
			'empresa'       => $campos['empresa'],
			'estado'        => 'pendente',
			'registado_em'  => $agora,
			'termos_em'     => $agora,
			'atualizado_em' => $agora,
		]
	);

	// Sem perfil, a conta não serve para nada: sai, para o e-mail poder tentar outra vez.
	if ( false === $feito ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );
		jelly_ar_registo_responder( [ 'mensagem' => __( 'Não foi possível enviar o pedido. Tente de novo daqui a pouco.', 'jelly-area-reservada' ) ], 500 );
	}

	jelly_ar_registo_avisar_equipa( $user_id, $campos );
	jelly_ar_registo_confirmar( $campos );

	jelly_ar_registo_responder( [ 'sucesso' => true ] );
}
add_action( 'wp_ajax_nopriv_jelly_ar_registo', 'jelly_ar_registar' );
add_action( 'wp_ajax_jelly_ar_registo', 'jelly_ar_registar' );

/* ---------- Os e-mails ---------- */

/*
 * Em texto simples, pelo wp_mail(), e sem remetente próprio: saem pelo plugin
 * de SMTP ativo, com a conta e o remetente que ele tiver configurados (o WP
 * Mail SMTP força o remetente). A AR não envia correio por outro caminho.
 */

/**
 * Se o correio do site sai autenticado por SMTP. Hoje sabe ler o WP Mail
 * SMTP: com o mailer "mail" ("Default (none)") o correio vai pela função
 * mail() do PHP, sem autenticação — e é o que o back-office avisa. Outro
 * plugin de SMTP pode responder pelo filtro jelly_ar_envio_autenticado.
 *
 * @return bool|null true autenticado, false não, null não se sabe dizer.
 */
function jelly_ar_envio_autenticado() {
	$resposta = null;

	// As opções do WP Mail SMTP pela classe dele, que também lê as constantes do wp-config.php.
	if ( class_exists( '\WPMailSMTP\Options' ) ) {
		$opcoes   = \WPMailSMTP\Options::init();
		$mailer   = (string) $opcoes->get( 'mail', 'mailer' );
		$resposta = '' !== $mailer && 'mail' !== $mailer;

		// O mailer "smtp" (Other SMTP) só é autenticado com a autenticação ligada.
		if ( 'smtp' === $mailer ) {
			$resposta = (bool) $opcoes->get( 'smtp', 'auth' );
		}
	} elseif ( ! has_action( 'phpmailer_init' ) ) {
		// Nenhum plugin mexe no envio: é a função mail() do PHP.
		$resposta = false;
	}

	return apply_filters( 'jelly_ar_envio_autenticado', $resposta );
}

function jelly_ar_registo_avisar_equipa( $user_id, $campos ) {
	$nome = $campos['first_name'] . ' ' . $campos['last_name'];
	$link = add_query_arg(
		[ 'page' => jelly_ar_admin_slug( 'utilizadores' ), 'utilizador' => $user_id ],
		admin_url( 'admin.php' )
	);

	$linhas = [
		__( 'Chegou um pedido de registo na Área Reservada:', 'jelly-area-reservada' ),
		'',
		/* translators: %s: nome */
		sprintf( __( 'Nome: %s', 'jelly-area-reservada' ), $nome ),
		/* translators: %s: e-mail */
		sprintf( __( 'E-mail: %s', 'jelly-area-reservada' ), $campos['email'] ),
		/* translators: %s: telefone */
		sprintf( __( 'Telefone: %s', 'jelly-area-reservada' ), '' !== $campos['telefone'] ? jelly_ar_telefone( $campos['telefone'] ) : '—' ),
		/* translators: %s: empresa */
		sprintf( __( 'Empresa: %s', 'jelly-area-reservada' ), '' !== $campos['empresa'] ? $campos['empresa'] : '—' ),
		'',
		__( 'Para aprovar ou rejeitar o pedido:', 'jelly-area-reservada' ),
		$link,
	];

	wp_mail(
		jelly_ar_emails_equipa(),
		/* translators: %s: nome */
		sprintf( __( 'Novo pedido de registo: %s', 'jelly-area-reservada' ), $nome ),
		implode( "\n", $linhas )
	);
}

function jelly_ar_registo_confirmar( $campos ) {
	$linhas = [
		/* translators: %s: nome próprio */
		sprintf( __( 'Olá %s,', 'jelly-area-reservada' ), $campos['first_name'] ),
		'',
		__( 'A APIT recebeu o seu pedido de acesso à Área Reservada.', 'jelly-area-reservada' ),
		__( 'Quando a APIT o aprovar, é enviado outro e-mail para definir a palavra-passe. A partir daí, a entrada na área reservada faz-se com este endereço de e-mail.', 'jelly-area-reservada' ),
		'',
		__( 'Se não foi você a fazer este pedido, pode ignorar esta mensagem.', 'jelly-area-reservada' ),
		'',
		'APIT',
		home_url( '/' ),
	];

	wp_mail( $campos['email'], __( 'Pedido de registo recebido', 'jelly-area-reservada' ), implode( "\n", $linhas ) );
}

/**
 * Alguém pediu registo com um e-mail que já tem conta. O pop-up disse "pedido
 * enviado" como a toda a gente; o dono do e-mail fica a saber o que se passa.
 */
function jelly_ar_registo_avisar_existente( $user ) {
	$estado = jelly_ar_so_associado( $user ) ? jelly_ar_associado_estado( $user->ID ) : '';

	if ( 'pendente' === $estado ) {
		$texto = __( 'O pedido de acesso já estava registado e continua à espera de aprovação. Não é preciso enviá-lo outra vez: é enviado um e-mail quando a APIT o aprovar.', 'jelly-area-reservada' );
	} else {
		/* translators: %s: endereço para recuperar a palavra-passe */
		$texto = sprintf( __( 'Já existe uma conta com este endereço de e-mail. Se não se lembra da palavra-passe, pode definir uma nova aqui: %s', 'jelly-area-reservada' ), wp_lostpassword_url() );
	}

	$linhas = [
		__( 'Olá,', 'jelly-area-reservada' ),
		'',
		__( 'A APIT recebeu um pedido de registo na Área Reservada com este endereço de e-mail.', 'jelly-area-reservada' ),
		$texto,
		'',
		__( 'Se não foi você a fazer este pedido, pode ignorar esta mensagem.', 'jelly-area-reservada' ),
		'',
		'APIT',
		home_url( '/' ),
	];

	wp_mail( $user->user_email, __( 'Pedido de registo na Área Reservada', 'jelly-area-reservada' ), implode( "\n", $linhas ) );
}
