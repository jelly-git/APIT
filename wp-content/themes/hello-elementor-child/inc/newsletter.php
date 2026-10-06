<?php
/**
 * A newsletter: o formulário do tema, entregue ao Gravity Forms.
 *
 * O formulário que o visitante vê é o do tema (template-parts/newsletter.php),
 * com o desenho que tem. Quem guarda a inscrição é o Gravity Forms, no
 * formulário "Newsletter": é lá que ficam as entradas, as notificações e, com o
 * Mailchimp Add-On, o envio para a audiência. GFAPI::submit_form() faz a
 * submissão completa — validação, entrada, notificações e feeds — como se o
 * formulário tivesse sido desenhado por ele.
 *
 * O que o back-office decide aparece no site: os obrigatórios, os placeholders,
 * o texto do consentimento e a mensagem de confirmação são lidos do formulário,
 * não escritos aqui.
 *
 * Os campos são encontrados pelo tipo (email, consentimento) e pelo rótulo
 * (Nome, Empresa), não pelo id: um campo apagado e criado de novo no
 * back-office muda de id, e o site não deve deixar de funcionar por isso.
 *
 * Duas entradas para o mesmo tratamento: a rota REST, que o script usa para
 * responder sem recarregar a página, e o admin-post, para quem não tem
 * JavaScript — o formulário funciona nos dois.
 */

defined( 'ABSPATH' ) || exit;

/**
 * O id do formulário "Newsletter" no Gravity Forms.
 *
 * Procurado pelo título, e só depois pelo id 2, que é o que tem no site local:
 * se o formulário chegar a produção importado à mão em vez de com a base de
 * dados, o id que lá recebe é o próximo livre, não necessariamente o 2.
 */
function apit_newsletter_form_id() {
	static $id = null;

	if ( null === $id ) {
		$id = 2;

		if ( class_exists( 'GFAPI' ) ) {
			foreach ( GFAPI::get_forms() as $form ) {
				if ( 'newsletter' === mb_strtolower( trim( (string) $form['title'] ) ) ) {
					$id = (int) $form['id'];
					break;
				}
			}
		}
	}

	return (int) apply_filters( 'apit_newsletter_form_id', $id );
}

/**
 * O formulário e os seus quatro campos, por papel. null sem Gravity Forms ou
 * sem o formulário.
 *
 * @return array|null form, nome, empresa, email, consentimento (GF_Field ou null)
 */
function apit_newsletter_formulario() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache ?: null;
	}

	$cache = false;

	if ( ! class_exists( 'GFAPI' ) ) {
		return null;
	}

	$form = GFAPI::get_form( apit_newsletter_form_id() );

	if ( ! $form || empty( $form['is_active'] ) || ! empty( $form['is_trash'] ) ) {
		return null;
	}

	$campos = [
		'form'          => $form,
		'nome'          => null,
		'empresa'       => null,
		'email'         => null,
		'consentimento' => null,
	];

	foreach ( $form['fields'] as $campo ) {
		$rotulo = mb_strtolower( trim( (string) $campo->label ) );

		if ( 'email' === $campo->type && ! $campos['email'] ) {
			$campos['email'] = $campo;
		} elseif ( 'consent' === $campo->type && ! $campos['consentimento'] ) {
			$campos['consentimento'] = $campo;
		} elseif ( 'text' === $campo->type && 'nome' === $rotulo ) {
			$campos['nome'] = $campo;
		} elseif ( 'text' === $campo->type && 'empresa' === $rotulo ) {
			$campos['empresa'] = $campo;
		}
	}

	// Sem email não há inscrição possível.
	if ( ! $campos['email'] ) {
		return null;
	}

	$cache = $campos;

	return $cache;
}

/**
 * O texto do consentimento, como está no back-office, com a ligação à política
 * de privacidade.
 *
 * Se o texto já trouxer a ligação, fica como está. Se não trouxer, as palavras
 * "Política de Privacidade" passam a ligação — é como o formulário sempre foi,
 * e o campo do Gravity Forms guarda texto simples.
 */
function apit_newsletter_texto_consentimento( $campo ) {
	$texto = $campo ? trim( (string) $campo->checkboxLabel ) : '';

	if ( '' === $texto ) {
		$texto = __( 'Aceito os termos da Política de Privacidade', 'apit' );
	}

	$texto = wp_kses( $texto, [ 'a' => [ 'href' => [], 'target' => [], 'rel' => [] ] ] );

	if ( false === stripos( $texto, '<a' ) ) {
		// A página que o WordPress tem como política de privacidade e, sem ela, a do endereço de sempre.
		$pagina = (int) get_option( 'wp_page_for_privacy_policy' );
		$pagina = $pagina ? $pagina : (int) ( get_page_by_path( 'politica-privacidade' )->ID ?? 0 );
		$url    = $pagina ? get_permalink( $pagina ) : '';

		if ( $url ) {
			$texto = preg_replace(
				'~Política de Privacidade~iu',
				'<a href="' . esc_url( $url ) . '">$0</a>',
				$texto,
				1
			);
		}
	}

	return $texto;
}

/**
 * Faz a inscrição.
 *
 * @param array $dados nome, empresa, email, consentimento, e o campo-armadilha.
 * @return array ok (bool), mensagem (texto), erros (por campo do tema)
 */
function apit_newsletter_inscrever( array $dados ) {
	$f = apit_newsletter_formulario();

	if ( ! $f ) {
		return [
			'ok'       => false,
			'mensagem' => __( 'A inscrição não está disponível de momento. Tente mais tarde.', 'apit' ),
			'erros'    => [],
		];
	}

	/*
	 * O campo-armadilha: invisível para quem lê a página, preenchido por quem
	 * preenche tudo o que encontra. Responde-se como se tivesse corrido bem,
	 * para não ensinar o robô a contornar, e nada é guardado.
	 */
	if ( '' !== trim( (string) ( $dados['apit_site'] ?? '' ) ) ) {
		return [
			'ok'       => true,
			'mensagem' => apit_newsletter_confirmacao( $f['form'] ),
			'erros'    => [],
		];
	}

	$valores = [];

	foreach ( [ 'nome', 'empresa', 'email' ] as $papel ) {
		if ( $f[ $papel ] ) {
			$valores[ 'input_' . $f[ $papel ]->id ] = sanitize_text_field( wp_unslash( (string) ( $dados[ $papel ] ?? '' ) ) );
		}
	}

	/*
	 * O consentimento do Gravity Forms são três entradas: a caixa (.1), o texto
	 * que a pessoa aceitou (.2) e a revisão desse texto (.3). As duas últimas
	 * são do formulário, não do visitante — vão daqui, para a entrada guardar
	 * exactamente o texto que estava no site quando a pessoa o aceitou.
	 */
	if ( $f['consentimento'] ) {
		$id = $f['consentimento']->id;

		if ( ! empty( $dados['consentimento'] ) ) {
			$valores[ 'input_' . $id . '_1' ] = '1';
		}

		$valores[ 'input_' . $id . '_2' ] = wp_strip_all_tags( (string) $f['consentimento']->checkboxLabel );
		// O que o próprio campo do Gravity Forms põe num campo escondido ao desenhar-se.
		$valores[ 'input_' . $id . '_3' ] = class_exists( 'GFFormsModel' ) ? (string) GFFormsModel::get_latest_form_revisions_id( $f['form']['id'] ) : '';
	}

	/*
	 * O estado que o formulário do Gravity Forms leva num campo escondido: uma
	 * assinatura das escolhas de cada campo, que ele confere para que uma escolha
	 * não possa ser forjada no browser. A caixa do consentimento é um desses
	 * campos, e sem a assinatura todas as inscrições falhavam nela — "Selecção
	 * inválida", com a caixa marcada. Feita aqui como o formulário a faz ao
	 * desenhar-se.
	 */
	if ( class_exists( 'GFFormDisplay' ) || is_readable( GFCommon::get_base_path() . '/form_display.php' ) ) {
		require_once GFCommon::get_base_path() . '/form_display.php';
		$valores[ 'state_' . $f['form']['id'] ] = GFFormDisplay::get_state( $f['form'], [] );
	}

	$resultado = GFAPI::submit_form( $f['form']['id'], $valores );

	if ( is_wp_error( $resultado ) ) {
		return [
			'ok'       => false,
			'mensagem' => __( 'Não foi possível concluir a inscrição. Tente mais tarde.', 'apit' ),
			'erros'    => [],
		];
	}

	if ( empty( $resultado['is_valid'] ) ) {
		// As mensagens do Gravity Forms vêm pelo id do campo; o tema fala por papel.
		$erros = [];

		foreach ( (array) ( $resultado['validation_messages'] ?? [] ) as $campo_id => $mensagem ) {
			foreach ( [ 'nome', 'empresa', 'email', 'consentimento' ] as $papel ) {
				if ( $f[ $papel ] && (int) $f[ $papel ]->id === (int) $campo_id ) {
					$erros[ $papel ] = html_entity_decode( wp_strip_all_tags( $mensagem ), ENT_QUOTES, 'UTF-8' );
				}
			}
		}

		return [
			'ok'       => false,
			'mensagem' => $erros ? implode( ' ', array_unique( $erros ) ) : __( 'Verifique os campos e tente de novo.', 'apit' ),
			'erros'    => $erros,
		];
	}

	return [
		'ok'       => true,
		'mensagem' => wp_strip_all_tags( (string) ( $resultado['confirmation_message'] ?? '' ) ) ?: apit_newsletter_confirmacao( $f['form'] ),
		'erros'    => [],
	];
}

/**
 * A mensagem de confirmação definida no formulário, em texto simples.
 */
function apit_newsletter_confirmacao( $form ) {
	foreach ( (array) ( $form['confirmations'] ?? [] ) as $c ) {
		if ( ! empty( $c['isDefault'] ) && 'message' === ( $c['type'] ?? '' ) && ! empty( $c['message'] ) ) {
			return trim( wp_strip_all_tags( GFCommon::replace_variables( $c['message'], $form, [] ) ) );
		}
	}

	return __( 'Inscrição recebida. Obrigado.', 'apit' );
}

/**
 * O email repetido, dito como se diz numa newsletter.
 *
 * O campo Email do formulário não aceita duplicados, e a mensagem do Gravity
 * Forms é a genérica dos campos únicos: "Este campo é para um registo único e
 * '…' já foram usados".
 */
function apit_newsletter_email_repetido( $mensagem, $form ) {
	if ( (int) ( $form['id'] ?? 0 ) !== apit_newsletter_form_id() ) {
		return $mensagem;
	}

	return __( 'Este email já está inscrito na APIT News.', 'apit' );
}
add_filter( 'gform_duplicate_message', 'apit_newsletter_email_repetido', 10, 2 );

/**
 * A rota que o script do formulário chama.
 */
function apit_newsletter_rota() {
	register_rest_route( 'apit/v1', '/newsletter', [
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( $pedido ) {
			$r = apit_newsletter_inscrever( (array) $pedido->get_params() );

			return new WP_REST_Response( $r, $r['ok'] ? 200 : 422 );
		},
	] );
}
add_action( 'rest_api_init', 'apit_newsletter_rota' );

/**
 * Sem JavaScript: o formulário vai para o admin-post e volta à página de onde
 * veio, com o resultado no endereço e a âncora no formulário.
 */
function apit_newsletter_sem_js() {
	$r = apit_newsletter_inscrever( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- público, como qualquer inscrição; protegido pelo honeypot e pelo Gravity Forms.

	$volta = wp_get_referer() ?: home_url( '/' );
	$volta = remove_query_arg( 'newsletter', $volta );
	$volta = add_query_arg( 'newsletter', $r['ok'] ? 'ok' : 'erro', $volta );

	wp_safe_redirect( strtok( $volta, '#' ) . '#newsletter' );
	exit;
}
add_action( 'admin_post_nopriv_apit_newsletter', 'apit_newsletter_sem_js' );
add_action( 'admin_post_apit_newsletter', 'apit_newsletter_sem_js' );
