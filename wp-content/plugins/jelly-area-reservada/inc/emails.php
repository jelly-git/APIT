<?php
/**
 * Os e-mails da Área Reservada, com o desenho do site (templates/email.php).
 *
 * Saem pelo wp_mail() — e por ele pelo plugin de SMTP ativo, com o remetente
 * que esse plugin força. Daqui só se põe o nome, "APIT", em vez do "WordPress"
 * por omissão; o endereço fica com o SMTP, que é quem o autentica.
 *
 * Cada e-mail leva duas versões: a HTML e uma em texto simples (o AltBody),
 * que os programas sem HTML mostram e que conta a favor nos filtros de spam.
 */

defined( 'ABSPATH' ) || exit;

/**
 * O logótipo do e-mail. Tem de ser PNG: nenhum programa de e-mail mostra SVG.
 * Por omissão, o logótipo a cores do tema; sem ele, o e-mail escreve "APIT".
 */
function jelly_ar_email_logo_url() {
	$logo = get_stylesheet_directory() . '/assets/img/logo-apit.png';
	$url  = file_exists( $logo ) ? get_stylesheet_directory_uri() . '/assets/img/logo-apit.png' : '';

	return (string) apply_filters( 'jelly_ar_email_logo_url', $url );
}

function jelly_ar_email_morada() {
	return (string) apply_filters( 'jelly_ar_email_morada', 'Av. Fernando Pessoa, 11 1º Sala 4, 1990-108 Lisboa · geral@apitv.com' );
}

/**
 * O e-mail em HTML, pelo modelo.
 */
function jelly_ar_email_html( $args ) {
	ob_start();
	jelly_ar_template( 'email', $args );

	return ob_get_clean();
}

/**
 * O mesmo e-mail em texto simples: o título, os parágrafos, os dados, o
 * botão como endereço e a nota.
 */
function jelly_ar_email_texto( $args ) {
	$texto = function ( $html ) {
		return trim( wp_specialchars_decode( wp_strip_all_tags( $html ), ENT_QUOTES ) );
	};

	$linhas = [ $args['titulo'] ?? '', '' ];

	foreach ( $args['paragrafos'] ?? [] as $p ) {
		$linhas[] = $texto( $p );
		$linhas[] = '';
	}

	foreach ( $args['dados'] ?? [] as $rotulo => $valor ) {
		$linhas[] = $rotulo . ': ' . ( '' !== (string) $valor ? $valor : '—' );
	}

	if ( ! empty( $args['dados'] ) ) {
		$linhas[] = '';
	}

	if ( ! empty( $args['botao']['url'] ) ) {
		$linhas[] = $args['botao']['texto'] . ': ' . $args['botao']['url'];
		$linhas[] = '';
	}

	if ( ! empty( $args['nota'] ) ) {
		$linhas[] = $args['nota'];
		$linhas[] = '';
	}

	$linhas[] = 'APIT — ' . home_url( '/' );

	return implode( "\n", $linhas );
}

/**
 * Envia um e-mail da AR. $para pode ser um endereço ou uma lista.
 *
 * @return bool O que o wp_mail() devolver.
 */
function jelly_ar_enviar_email( $para, $assunto, $args ) {
	$html  = jelly_ar_email_html( $args );
	$texto = jelly_ar_email_texto( $args );

	$nome = function () {
		return 'APIT';
	};
	$alternativa = function ( $phpmailer ) use ( $texto ) {
		$phpmailer->AltBody = $texto; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	};

	add_filter( 'wp_mail_from_name', $nome );
	add_action( 'phpmailer_init', $alternativa );

	$enviado = wp_mail( $para, $assunto, $html, [ 'Content-Type: text/html; charset=UTF-8' ] );

	// Só para este e-mail: os outros do site não mudam.
	remove_filter( 'wp_mail_from_name', $nome );
	remove_action( 'phpmailer_init', $alternativa );

	return $enviado;
}

/* ---------- A decisão sobre um pedido ---------- */

/**
 * O pedido foi aprovado: o e-mail com a ligação para definir a palavra-passe.
 * A ligação é a da recuperação do WordPress (wp-login.php?action=rp), válida
 * durante um dia; depois disso pede-se outra em "Esqueceu-se da palavra-passe?".
 *
 * @return bool Se o e-mail saiu. Sem ele, a pessoa não tem como entrar.
 */
function jelly_ar_email_aprovado( $user, $perfil ) {
	$chave = get_password_reset_key( $user );

	if ( is_wp_error( $chave ) ) {
		return false;
	}

	$ligacao = network_site_url( 'wp-login.php?action=rp&key=' . $chave . '&login=' . rawurlencode( $user->user_login ), 'login' );

	return jelly_ar_enviar_email(
		$user->user_email,
		__( 'Pedido de acesso aprovado', 'jelly-area-reservada' ),
		[
			/* translators: %s: nome próprio */
			'titulo'     => sprintf( __( 'Olá %s,', 'jelly-area-reservada' ), $perfil->nome ),
			'previa'     => __( 'O acesso à Área Reservada da APIT foi aprovado. Falta definir a palavra-passe.', 'jelly-area-reservada' ),
			'paragrafos' => [
				esc_html__( 'A APIT aprovou o pedido de acesso à Área Reservada.', 'jelly-area-reservada' ),
				sprintf(
					/* translators: %s: e-mail */
					esc_html__( 'Falta só definir a palavra-passe, no botão abaixo. A partir daí, a entrada na área reservada faz-se com o endereço %s e essa palavra-passe.', 'jelly-area-reservada' ),
					'<strong>' . esc_html( $user->user_email ) . '</strong>'
				),
			],
			'passos'     => 3,
			'botao'      => [
				'texto' => __( 'Definir palavra-passe', 'jelly-area-reservada' ),
				'url'   => $ligacao,
			],
			'nota'       => __( 'A ligação é válida durante 24 horas. Depois disso, é possível pedir outra em "Esqueceu-se da palavra-passe?", no login.', 'jelly-area-reservada' ),
		]
	);
}

/**
 * O pedido foi rejeitado: um aviso curto, com o contacto da APIT.
 */
function jelly_ar_email_rejeitado( $user, $perfil ) {
	return jelly_ar_enviar_email(
		$user->user_email,
		__( 'Pedido de acesso à Área Reservada', 'jelly-area-reservada' ),
		[
			/* translators: %s: nome próprio */
			'titulo'     => sprintf( __( 'Olá %s,', 'jelly-area-reservada' ), $perfil->nome ),
			'previa'     => __( 'Sobre o pedido de acesso à Área Reservada da APIT.', 'jelly-area-reservada' ),
			'paragrafos' => [
				esc_html__( 'O pedido de acesso à Área Reservada não foi aprovado.', 'jelly-area-reservada' ),
				sprintf(
					/* translators: %s: e-mail da APIT */
					esc_html__( 'Para saber mais, a APIT está disponível em %s.', 'jelly-area-reservada' ),
					'<a href="mailto:geral@apitv.com" style="color:#f41892;text-decoration:none;">geral@apitv.com</a>'
				),
			],
		]
	);
}
