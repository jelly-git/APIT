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

/**
 * O ficheiro do logótipo, para ir embutido no e-mail (cid:apit-logo). Um
 * logótipo por endereço só aparece se o programa de e-mail o conseguir ir
 * buscar — e não consegue a um servidor com autenticação HTTP, como o de
 * testes, nem quando o programa bloqueia imagens externas. Embutido, vai com a
 * mensagem.
 */
function jelly_ar_email_logo_ficheiro() {
	$logo = get_stylesheet_directory() . '/assets/img/logo-apit.png';

	return (string) apply_filters( 'jelly_ar_email_logo_ficheiro', file_exists( $logo ) ? $logo : '' );
}

function jelly_ar_email_morada() {
	return (string) apply_filters( 'jelly_ar_email_morada', 'Av. Fernando Pessoa, 11 1º Sala 4, 1990-108 Lisboa · geral@apitv.com' );
}

/**
 * A saudação, o primeiro parágrafo de cada e-mail: formal e sem supor o
 * género, como a APIT escreve aos associados.
 */
function jelly_ar_email_saudacao( $nome ) {
	/* translators: %s: nome completo */
	return esc_html( sprintf( __( 'Caro(a) %s,', 'jelly-area-reservada' ), trim( $nome ) ) );
}

/*
 * A nota comum aos e-mails com uma ligação para a palavra-passe.
 */
function jelly_ar_email_nota_ligacao() {
	return __( 'Por motivos de segurança, esta ligação é válida durante 24 horas e só pode ser utilizada uma vez. Findo esse prazo, pode ser solicitada uma nova em «Esqueceu-se da palavra-passe?», na Área Reservada.', 'jelly-area-reservada' );
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
	// O logótipo vai embutido na mensagem, e o HTML aponta para ele pelo cid.
	$ficheiro = jelly_ar_email_logo_ficheiro();

	if ( $ficheiro ) {
		$args['logo'] = 'cid:apit-logo';
	}

	$html  = jelly_ar_email_html( $args );
	$texto = jelly_ar_email_texto( $args );

	$nome = function () {
		return 'APIT';
	};
	$alternativa = function ( $phpmailer ) use ( $texto, $ficheiro ) {
		$phpmailer->AltBody = $texto; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		if ( $ficheiro ) {
			$phpmailer->addEmbeddedImage( $ficheiro, 'apit-logo', 'apit.png', 'base64', 'image/png' );
		}
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
 * Uma palavra-passe nova: pedida pela equipa (o botão do perfil) ou pelo
 * próprio associado ("Esqueceu-se da palavra-passe?").
 *
 * @return bool Se o e-mail saiu.
 */
function jelly_ar_email_nova_senha( $user, $perfil ) {
	$ligacao = jelly_ar_ligacao_senha( $user );

	if ( ! $ligacao ) {
		return false;
	}

	return jelly_ar_enviar_email(
		$user->user_email,
		__( 'Redefinição da palavra-passe — Área Reservada APIT', 'jelly-area-reservada' ),
		[
			'titulo'     => __( 'Redefinição da palavra-passe', 'jelly-area-reservada' ),
			'previa'     => __( 'Ligação para definir uma nova palavra-passe de acesso à Área Reservada da APIT.', 'jelly-area-reservada' ),
			'paragrafos' => [
				jelly_ar_email_saudacao( $perfil->nome . ' ' . $perfil->apelido ),
				sprintf(
					/* translators: %s: e-mail */
					esc_html__( 'Foi solicitada a definição de uma nova palavra-passe de acesso à Área Reservada da APIT, associada ao endereço %s.', 'jelly-area-reservada' ),
					'<strong>' . esc_html( $user->user_email ) . '</strong>'
				),
				esc_html__( 'Para a definir, basta seguir a ligação abaixo. Até lá, a palavra-passe atual mantém-se válida.', 'jelly-area-reservada' ),
			],
			'botao'      => [
				'texto' => __( 'Definir nova palavra-passe', 'jelly-area-reservada' ),
				'url'   => $ligacao,
			],
			'nota'       => jelly_ar_email_nota_ligacao() . ' ' . __( 'Caso este pedido não tenha sido efetuado pelo titular da conta, esta mensagem pode ser ignorada.', 'jelly-area-reservada' ),
		]
	);
}

/**
 * O pedido foi aprovado: o e-mail com a ligação para definir a palavra-passe.
 *
 * @return bool Se o e-mail saiu. Sem ele, a pessoa não tem como entrar.
 */
function jelly_ar_email_aprovado( $user, $perfil ) {
	$ligacao = jelly_ar_ligacao_senha( $user );

	if ( ! $ligacao ) {
		return false;
	}

	return jelly_ar_enviar_email(
		$user->user_email,
		__( 'Acesso à Área Reservada aprovado — APIT', 'jelly-area-reservada' ),
		[
			'titulo'     => __( 'Acesso aprovado', 'jelly-area-reservada' ),
			'previa'     => __( 'O pedido de acesso à Área Reservada da APIT foi aprovado.', 'jelly-area-reservada' ),
			'paragrafos' => [
				jelly_ar_email_saudacao( $perfil->nome . ' ' . $perfil->apelido ),
				esc_html__( 'O pedido de acesso à Área Reservada da APIT foi aprovado.', 'jelly-area-reservada' ),
				sprintf(
					/* translators: %s: e-mail */
					esc_html__( 'Para concluir a ativação da conta, falta apenas definir a palavra-passe através da ligação abaixo. O acesso passa a ser feito com o endereço %s e a palavra-passe escolhida.', 'jelly-area-reservada' ),
					'<strong>' . esc_html( $user->user_email ) . '</strong>'
				),
			],
			'passos'     => 3,
			'botao'      => [
				'texto' => __( 'Definir palavra-passe', 'jelly-area-reservada' ),
				'url'   => $ligacao,
			],
			'nota'       => jelly_ar_email_nota_ligacao(),
		]
	);
}

/**
 * O pedido foi rejeitado: um aviso curto, com o contacto da APIT.
 */
function jelly_ar_email_rejeitado( $user, $perfil ) {
	return jelly_ar_enviar_email(
		$user->user_email,
		__( 'Pedido de acesso à Área Reservada — APIT', 'jelly-area-reservada' ),
		[
			'titulo'     => __( 'Pedido de acesso', 'jelly-area-reservada' ),
			'previa'     => __( 'Informação sobre o pedido de acesso à Área Reservada da APIT.', 'jelly-area-reservada' ),
			'paragrafos' => [
				jelly_ar_email_saudacao( $perfil->nome . ' ' . $perfil->apelido ),
				esc_html__( 'Após análise, o pedido de acesso à Área Reservada da APIT não foi aprovado.', 'jelly-area-reservada' ),
				sprintf(
					/* translators: %s: e-mail da APIT */
					esc_html__( 'Para qualquer esclarecimento, a APIT encontra-se disponível através do endereço %s.', 'jelly-area-reservada' ),
					'<a href="mailto:geral@apitv.com" style="color:#f41892;text-decoration:none;">geral@apitv.com</a>'
				),
			],
		]
	);
}
