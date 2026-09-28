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
 * Conta mais um pedido deste IP e diz se passou do limite, numa hora
 * (jelly_ar_excedido(), inc/sessao.php).
 */
function jelly_ar_registo_excedido() {
	return jelly_ar_excedido( 'registo', JELLY_AR_REGISTO_LIMITE, HOUR_IN_SECONDS );
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
 * Pelo wp_mail(), com o desenho do site e sem endereço de remetente próprio:
 * saem pelo plugin de SMTP ativo, com a conta e o remetente que ele tiver
 * configurados (o WP Mail SMTP força o remetente). A AR não envia correio por
 * outro caminho. O desenho e o envio estão em inc/emails.php.
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

/*
 * Os três e-mails do registo, com o desenho do site (inc/emails.php e
 * templates/email.php).
 */

function jelly_ar_registo_avisar_equipa( $user_id, $campos ) {
	$nome = $campos['first_name'] . ' ' . $campos['last_name'];

	// O slug da página vem de inc/admin.php, que o admin-ajax carrega; fora dele, o de sempre.
	$link = add_query_arg(
		[ 'page' => function_exists( 'jelly_ar_admin_slug' ) ? jelly_ar_admin_slug( 'utilizadores' ) : 'jelly-ar-utilizadores', 'utilizador' => $user_id ],
		admin_url( 'admin.php' )
	);

	jelly_ar_enviar_email(
		jelly_ar_emails_equipa(),
		/* translators: %s: nome */
		sprintf( __( 'Novo pedido de acesso à Área Reservada: %s', 'jelly-area-reservada' ), $nome ),
		[
			'titulo'     => __( 'Novo pedido de acesso', 'jelly-area-reservada' ),
			/* translators: %s: nome */
			'previa'     => sprintf( __( '%s solicitou acesso à Área Reservada.', 'jelly-area-reservada' ), $nome ),
			'paragrafos' => [
				esc_html__( 'Foi recebido um novo pedido de acesso à Área Reservada, que aguarda análise no back-office.', 'jelly-area-reservada' ),
			],
			'dados'      => [
				__( 'Nome', 'jelly-area-reservada' )     => $nome,
				__( 'E-mail', 'jelly-area-reservada' )   => $campos['email'],
				__( 'Telefone', 'jelly-area-reservada' ) => '' !== $campos['telefone'] ? jelly_ar_telefone( $campos['telefone'] ) : '',
				__( 'Empresa', 'jelly-area-reservada' )  => $campos['empresa'],
			],
			'botao'      => [
				'texto' => __( 'Analisar pedido', 'jelly-area-reservada' ),
				'url'   => $link,
			],
		]
	);
}

function jelly_ar_registo_confirmar( $campos ) {
	jelly_ar_enviar_email(
		$campos['email'],
		__( 'Pedido de acesso à Área Reservada recebido — APIT', 'jelly-area-reservada' ),
		[
			'titulo'     => __( 'Pedido de acesso recebido', 'jelly-area-reservada' ),
			'previa'     => __( 'O pedido de acesso à Área Reservada da APIT encontra-se em análise.', 'jelly-area-reservada' ),
			'paragrafos' => [
				jelly_ar_email_saudacao( $campos['first_name'] . ' ' . $campos['last_name'] ),
				esc_html__( 'A APIT — Associação de Produtores Independentes de Televisão acusa a receção do pedido de acesso à Área Reservada, que se encontra em análise.', 'jelly-area-reservada' ),
				sprintf(
					/* translators: %s: e-mail */
					esc_html__( 'Após a aprovação, será enviada para %s uma mensagem com as indicações para definir a palavra-passe de acesso.', 'jelly-area-reservada' ),
					'<strong>' . esc_html( $campos['email'] ) . '</strong>'
				),
			],
			'passos'     => 2,
			'nota'       => __( 'Caso este pedido não tenha sido efetuado pelo titular deste endereço de e-mail, esta mensagem pode ser ignorada. Sem aprovação, não é concedido qualquer acesso.', 'jelly-area-reservada' ),
		]
	);
}

/**
 * Alguém pediu registo com um e-mail que já tem conta. O pop-up disse "pedido
 * enviado" como a toda a gente; o dono do e-mail fica a saber o que se passa.
 */
function jelly_ar_registo_avisar_existente( $user ) {
	$perfil   = jelly_ar_so_associado( $user ) ? jelly_ar_associado( $user->ID ) : null;
	$pendente = $perfil && 'pendente' === $perfil->estado;
	$nome     = $perfil ? $perfil->nome . ' ' . $perfil->apelido : $user->display_name;
	$args     = [
		'titulo'     => __( 'Pedido de acesso', 'jelly-area-reservada' ),
		'previa'     => __( 'Foi recebido um pedido de acesso à Área Reservada associado a este endereço de e-mail.', 'jelly-area-reservada' ),
		'paragrafos' => [
			jelly_ar_email_saudacao( $nome ),
			esc_html__( 'Foi recebido um pedido de acesso à Área Reservada da APIT associado a este endereço de e-mail, que já dispõe de uma conta.', 'jelly-area-reservada' ),
		],
		'nota'       => __( 'Caso este pedido não tenha sido efetuado pelo titular deste endereço, esta mensagem pode ser ignorada. A conta não sofreu qualquer alteração.', 'jelly-area-reservada' ),
	];

	if ( $pendente ) {
		$args['paragrafos'][] = esc_html__( 'O pedido anterior encontra-se ainda em análise, pelo que não é necessário submetê-lo novamente. Após a aprovação, será enviada uma mensagem com as indicações para definir a palavra-passe.', 'jelly-area-reservada' );
		$args['passos']       = 2;
	} else {
		$ligacao = jelly_ar_ligacao_senha( $user );

		$args['paragrafos'][] = esc_html__( 'Caso não se recorde da palavra-passe, é possível definir uma nova através da ligação abaixo.', 'jelly-area-reservada' );

		if ( $ligacao ) {
			$args['botao'] = [
				'texto' => __( 'Definir nova palavra-passe', 'jelly-area-reservada' ),
				'url'   => $ligacao,
			];
		}
	}

	jelly_ar_enviar_email( $user->user_email, __( 'Pedido de acesso à Área Reservada — APIT', 'jelly-area-reservada' ), $args );
}
