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

	foreach ( [ 'botao', 'botao2' ] as $b ) {
		if ( ! empty( $args[ $b ]['url'] ) ) {
			$linhas[] = $args[ $b ]['texto'] . ': ' . $args[ $b ]['url'];
			$linhas[] = '';
		}
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

/**
 * A palavra-passe foi definida. Na primeira vez (a conta ainda não entrou
 * nenhuma vez), é o fim do registo: os três passos concluídos, e os botões para
 * entrar e para o site. Depois disso, é o aviso de segurança de que a
 * palavra-passe mudou — quem não a mudou fica a saber.
 */
function jelly_ar_email_senha_definida( $user, $perfil ) {
	global $wpdb;

	$primeira = 0 === (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . jelly_ar_tabela( 'acessos' ) . ' WHERE user_id = %d', $user->ID ) ); // phpcs:ignore WordPress.DB
	$nome     = $perfil->nome . ' ' . $perfil->apelido;
	$contacto = '<a href="mailto:geral@apitv.com" style="color:#f41892;text-decoration:none;">geral@apitv.com</a>';
	$entrar   = [
		'texto' => __( 'Entrar na Área Reservada', 'jelly-area-reservada' ),
		'url'   => home_url( '/#area-reservada' ),
	];

	if ( $primeira ) {
		return jelly_ar_enviar_email(
			$user->user_email,
			__( 'Conta ativada — Área Reservada APIT', 'jelly-area-reservada' ),
			[
				'titulo'     => __( 'Conta ativada', 'jelly-area-reservada' ),
				'previa'     => __( 'O registo na Área Reservada da APIT está concluído.', 'jelly-area-reservada' ),
				'paragrafos' => [
					jelly_ar_email_saudacao( $nome ),
					esc_html__( 'O registo na Área Reservada da APIT está concluído: o pedido foi aprovado e a palavra-passe definida. A conta encontra-se ativa.', 'jelly-area-reservada' ),
					sprintf(
						/* translators: %s: e-mail */
						esc_html__( 'O acesso faz-se com o endereço %s e a palavra-passe escolhida, a partir do botão «Área Reservada» no topo do site.', 'jelly-area-reservada' ),
						'<strong>' . esc_html( $user->user_email ) . '</strong>'
					),
				],
				'passos'     => 4,
				'botao'      => $entrar,
				'botao2'     => [
					'texto' => __( 'Visitar o site', 'jelly-area-reservada' ),
					'url'   => home_url( '/' ),
				],
				'nota'       => __( 'Caso a palavra-passe não tenha sido definida pelo titular da conta, a APIT deve ser contactada de imediato através do endereço geral@apitv.com.', 'jelly-area-reservada' ),
			]
		);
	}

	return jelly_ar_enviar_email(
		$user->user_email,
		__( 'Palavra-passe alterada — Área Reservada APIT', 'jelly-area-reservada' ),
		[
			'titulo'     => __( 'Palavra-passe alterada', 'jelly-area-reservada' ),
			'previa'     => __( 'A palavra-passe de acesso à Área Reservada da APIT foi alterada.', 'jelly-area-reservada' ),
			'paragrafos' => [
				jelly_ar_email_saudacao( $nome ),
				sprintf(
					/* translators: %s: e-mail */
					esc_html__( 'A palavra-passe de acesso à Área Reservada da APIT, associada ao endereço %s, foi alterada.', 'jelly-area-reservada' ),
					'<strong>' . esc_html( $user->user_email ) . '</strong>'
				),
				sprintf(
					/* translators: %s: e-mail da APIT */
					esc_html__( 'Caso esta alteração não tenha sido feita pelo titular da conta, a APIT deve ser contactada de imediato através do endereço %s.', 'jelly-area-reservada' ),
					$contacto
				),
			],
			'botao'      => $entrar,
		]
	);
}

/* ---------- As marcações de mesa ---------- */

/**
 * Um dia e uma hora de marcação por extenso, para os e-mails: "7 de outubro de
 * 2026, das 10:00 às 10:30".
 */
function jelly_ar_email_quando( $dia, $hora, $intervalo ) {
	$d   = DateTime::createFromFormat( '!Y-m-d', $dia );
	$fim = jelly_ar_minutos( $hora ) + $intervalo;

	return sprintf(
		/* translators: 1: dia, 2: hora de início, 3: hora de fim */
		__( '%1$s, das %2$s às %3$s', 'jelly-area-reservada' ),
		// Os meses em minúscula, como se escrevem em português ("7 de outubro").
		$d ? mb_strtolower( wp_date( 'j \d\e F \d\e Y', $d->getTimestamp() ) ) : $dia,
		$hora,
		sprintf( '%02d:%02d', intdiv( $fim, 60 ), $fim % 60 )
	);
}

// "Mesa 1 · Stand APIT, junto à entrada".
function jelly_ar_email_mesa( $mesa ) {
	return $mesa['nome'] . ( $mesa['localizacao'] ? ' · ' . $mesa['localizacao'] : '' );
}

/**
 * Uma marcação de mesa feita, cancelada ou mudada pela equipa.
 *
 * @param int    $user_id O associado.
 * @param string $tipo    marcada, cancelada ou mudada.
 * @param array  $evento  O evento (jelly_ar_evento()).
 * @param array  $mesa    A mesa (jelly_ar_mesas()); na mudada, a nova.
 * @param string $dia     Y-m-d; na mudada, o novo.
 * @param string $hora    H:i; na mudada, a nova.
 * @param array  $antes   Só na mudada: [ 'mesa' => …, 'dia' => …, 'hora' => … ].
 * @return bool Se o e-mail saiu.
 */
function jelly_ar_email_marcacao( $user_id, $tipo, $evento, $mesa, $dia, $hora, $antes = [] ) {
	$user   = get_userdata( $user_id );
	$perfil = jelly_ar_associado( $user_id );

	if ( ! $user || ! $perfil ) {
		return false;
	}

	$horarios  = jelly_ar_horarios( $evento['id'] );
	$intervalo = $horarios[ $dia ]['intervalo'] ?? JELLY_AR_INTERVALO_OMISSAO;
	$titulo    = '<strong>' . esc_html( $evento['titulo'] ) . '</strong>';
	$dados = [
		__( 'Evento', 'jelly-area-reservada' ) => $evento['titulo'] . ( $evento['local'] ? ' · ' . $evento['local'] : '' ),
		__( 'Data', 'jelly-area-reservada' )   => jelly_ar_email_quando( $dia, $hora, $intervalo ),
		__( 'Mesa', 'jelly-area-reservada' )   => jelly_ar_email_mesa( $mesa ),
	];

	$textos = [
		'marcada'   => [
			'assunto'  => __( 'Marcação de mesa confirmada — APIT', 'jelly-area-reservada' ),
			'titulo'   => __( 'Marcação confirmada', 'jelly-area-reservada' ),
			'previa'   => __( 'Foi efetuada uma marcação de mesa, já confirmada pela APIT.', 'jelly-area-reservada' ),
			/* translators: %s: evento */
			'texto'    => __( 'A APIT efetuou uma marcação de mesa no evento %s. A marcação encontra-se confirmada, com os dados abaixo.', 'jelly-area-reservada' ),
		],
		'cancelada' => [
			'assunto'  => __( 'Marcação de mesa cancelada — APIT', 'jelly-area-reservada' ),
			'titulo'   => __( 'Marcação cancelada', 'jelly-area-reservada' ),
			'previa'   => __( 'Uma marcação de mesa foi cancelada pela APIT.', 'jelly-area-reservada' ),
			/* translators: %s: evento */
			'texto'    => __( 'A marcação de mesa no evento %s, com os dados abaixo, foi cancelada pela APIT.', 'jelly-area-reservada' ),
		],
		'mudada'    => [
			'assunto'  => __( 'Marcação de mesa alterada — APIT', 'jelly-area-reservada' ),
			'titulo'   => __( 'Marcação alterada', 'jelly-area-reservada' ),
			'previa'   => __( 'Uma marcação de mesa foi alterada pela APIT.', 'jelly-area-reservada' ),
			/* translators: %s: evento */
			'texto'    => __( 'A marcação de mesa no evento %s foi alterada pela APIT. Os novos dados são os seguintes.', 'jelly-area-reservada' ),
		],
		// Os três passos do pedido feito pelo associado no site.
		'pedida'    => [
			'assunto'  => __( 'Pedido de marcação de mesa recebido — APIT', 'jelly-area-reservada' ),
			'titulo'   => __( 'Pedido de marcação recebido', 'jelly-area-reservada' ),
			'previa'   => __( 'O pedido de marcação de mesa foi recebido e aguarda aprovação da APIT.', 'jelly-area-reservada' ),
			/* translators: %s: evento */
			'texto'    => __( 'Foi recebido o pedido de marcação de mesa no evento %s, com os dados abaixo. O pedido aguarda aprovação da APIT; a confirmação segue por e-mail.', 'jelly-area-reservada' ),
		],
		'aprovada'  => [
			'assunto'  => __( 'Marcação de mesa aprovada — APIT', 'jelly-area-reservada' ),
			'titulo'   => __( 'Marcação aprovada', 'jelly-area-reservada' ),
			'previa'   => __( 'O pedido de marcação de mesa foi aprovado pela APIT.', 'jelly-area-reservada' ),
			/* translators: %s: evento */
			'texto'    => __( 'O pedido de marcação de mesa no evento %s foi aprovado. A marcação encontra-se confirmada, com os dados abaixo.', 'jelly-area-reservada' ),
		],
		'rejeitada' => [
			'assunto'  => __( 'Pedido de marcação de mesa — APIT', 'jelly-area-reservada' ),
			'titulo'   => __( 'Pedido de marcação não aprovado', 'jelly-area-reservada' ),
			'previa'   => __( 'Informação sobre o pedido de marcação de mesa.', 'jelly-area-reservada' ),
			/* translators: %s: evento */
			'texto'    => __( 'Após análise, o pedido de marcação de mesa no evento %s, com os dados abaixo, não foi aprovado. O horário pode ser escolhido de novo no calendário do site, entre os que se encontrem disponíveis.', 'jelly-area-reservada' ),
		],
	];

	if ( ! isset( $textos[ $tipo ] ) ) {
		return false;
	}

	if ( 'mudada' === $tipo && $antes ) {
		$dados[ __( 'Anteriormente', 'jelly-area-reservada' ) ] = jelly_ar_email_quando( $antes['dia'], $antes['hora'], $intervalo ) . ' · ' . jelly_ar_email_mesa( $antes['mesa'] );
	}

	$t = $textos[ $tipo ];

	return jelly_ar_enviar_email(
		$user->user_email,
		$t['assunto'],
		[
			'titulo'     => $t['titulo'],
			'previa'     => $t['previa'],
			'paragrafos' => [
				jelly_ar_email_saudacao( $perfil->nome . ' ' . $perfil->apelido ),
				sprintf( esc_html( $t['texto'] ), $titulo ),
			],
			'dados'      => $dados,
			'botao'      => [
				'texto' => __( 'Entrar na Área Reservada', 'jelly-area-reservada' ),
				'url'   => home_url( '/#area-reservada' ),
			],
			'nota'       => __( 'Para qualquer esclarecimento, a APIT encontra-se disponível através do endereço geral@apitv.com.', 'jelly-area-reservada' ),
		]
	);
}

/**
 * Um pedido de marcação novo, feito por um associado no site: o aviso à
 * equipa, com a ligação para as Aprovações.
 */
function jelly_ar_email_marcacao_equipa( $user_id, $evento, $mesa, $dia, $hora ) {
	$perfil = jelly_ar_associado( $user_id );
	$user   = get_userdata( $user_id );

	if ( ! $perfil || ! $user ) {
		return false;
	}

	$nome      = trim( $perfil->nome . ' ' . $perfil->apelido );
	$horarios  = jelly_ar_horarios( $evento['id'] );
	$intervalo = $horarios[ $dia ]['intervalo'] ?? JELLY_AR_INTERVALO_OMISSAO;
	// O slug da página vem de inc/admin.php, que o admin-ajax carrega; fora dele, o de sempre.
	$link = add_query_arg( [ 'page' => function_exists( 'jelly_ar_admin_slug' ) ? jelly_ar_admin_slug( 'marcacoes' ) : 'jelly-ar-marcacoes' ], admin_url( 'admin.php' ) );

	return jelly_ar_enviar_email(
		jelly_ar_emails_equipa(),
		/* translators: 1: nome, 2: evento */
		sprintf( __( 'Novo pedido de marcação de mesa: %1$s — %2$s', 'jelly-area-reservada' ), $nome, $evento['titulo'] ),
		[
			'titulo'     => __( 'Novo pedido de marcação', 'jelly-area-reservada' ),
			/* translators: %s: nome */
			'previa'     => sprintf( __( '%s pediu uma marcação de mesa.', 'jelly-area-reservada' ), $nome ),
			'paragrafos' => [
				esc_html__( 'Foi recebido um novo pedido de marcação de mesa, que aguarda aprovação no back-office.', 'jelly-area-reservada' ),
			],
			'dados'      => [
				__( 'Associado', 'jelly-area-reservada' ) => $nome . ( $perfil->empresa ? ' · ' . $perfil->empresa : '' ),
				__( 'E-mail', 'jelly-area-reservada' )    => $user->user_email,
				__( 'Evento', 'jelly-area-reservada' )    => $evento['titulo'],
				__( 'Data', 'jelly-area-reservada' )      => jelly_ar_email_quando( $dia, $hora, $intervalo ),
				__( 'Mesa', 'jelly-area-reservada' )      => jelly_ar_email_mesa( $mesa ),
			],
			'botao'      => [
				'texto' => __( 'Analisar pedido', 'jelly-area-reservada' ),
				'url'   => $link,
			],
		]
	);
}
