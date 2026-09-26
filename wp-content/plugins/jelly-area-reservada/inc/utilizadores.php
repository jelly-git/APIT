<?php
/**
 * A lista de utilizadores do back-office: filtro por estado, pesquisa e ordem.
 *
 * Num sítio só, porque a servem dois: o ecrã, que a mostra às páginas, e a
 * exportação, que a descarrega inteira — e as duas têm de dar as mesmas
 * pessoas pela mesma ordem.
 */

defined( 'ABSPATH' ) || exit;

// Por estado, a ordem é a do trabalho e não a do alfabeto: primeiro o que espera.
const JELLY_AR_ORDEM_ESTADOS = [ 'pendente' => 0, 'ativo' => 1, 'suspenso' => 2, 'rejeitado' => 3 ];

/**
 * A lista com o que o endereço pede. O Estado só se ordena em "Todos": num
 * separador de um estado só, todas as linhas têm o mesmo.
 */
function jelly_ar_utilizadores_lista() {
	return new Jelly_AR_Lista(
		'utilizadores',
		[ 'estado' => '' ],
		'nome',
		function ( $valores ) {
			$ordenaveis = [ 'nome', 'email', 'empresa', 'telefone' ];

			if ( '' === $valores['estado'] ) {
				$ordenaveis[] = 'estado';
			}

			return $ordenaveis;
		},
		[ 'estado' => array_keys( JELLY_AR_ORDEM_ESTADOS ) ]
	);
}

/**
 * Aplica a pesquisa, o filtro e a ordem. Devolve `encontrados` (só com a
 * pesquisa, para os números dos separadores) e `lista` (tudo aplicado).
 */
function jelly_ar_utilizadores_filtrar( $utilizadores, $pedido ) {
	/*
	 * A pesquisa procura no nome, e-mail, empresa e telefone. O telefone compara
	 * também só em algarismos, para "912 345" dar com "+351 912345678".
	 */
	$encontrados = $utilizadores;

	if ( '' !== $pedido['q'] ) {
		$digitos  = preg_replace( '/\D/', '', $pedido['q'] );
		$numerico = strlen( $digitos ) >= 3 && preg_match( '/^[\d\s+()-]+$/', $pedido['q'] );

		$encontrados = array_values( array_filter( $utilizadores, function ( $x ) use ( $pedido, $digitos, $numerico ) {
			return Jelly_AR_Lista::contem( $pedido['q'], [ $x['nome'] . ' ' . $x['apelido'], $x['email'], $x['empresa'], $x['telefone'] ] )
				|| ( $numerico && false !== strpos( preg_replace( '/\D/', '', $x['telefone'] ), $digitos ) );
		} ) );
	}

	$lista = $pedido['estado'] ? array_values( array_filter( $encontrados, function ( $x ) use ( $pedido ) {
		return $x['estado'] === $pedido['estado'];
	} ) ) : $encontrados;

	$ordenar = $pedido['ordenar'];
	$ordem   = $pedido['ordem'];

	usort( $lista, function ( $a, $b ) use ( $ordenar, $ordem ) {
		if ( 'estado' === $ordenar ) {
			$r = ( JELLY_AR_ORDEM_ESTADOS[ $a['estado'] ] ?? 9 ) <=> ( JELLY_AR_ORDEM_ESTADOS[ $b['estado'] ] ?? 9 );

			return 'desc' === $ordem ? -$r : $r;
		}

		if ( 'nome' === $ordenar ) {
			return Jelly_AR_Lista::comparar_texto( $a['nome'] . ' ' . $a['apelido'], $b['nome'] . ' ' . $b['apelido'], $ordem );
		}

		return Jelly_AR_Lista::comparar_texto( $a[ $ordenar ], $b[ $ordenar ], $ordem );
	} );

	return [
		'encontrados' => $encontrados,
		'lista'       => $lista,
	];
}

/**
 * Os associados a sério: o perfil na tabela jelly_ar_associados e o e-mail na
 * conta do WordPress, na forma que os ecrãs usam — a mesma dos de exemplo.
 * O id é o da conta do WordPress.
 */
function jelly_ar_utilizadores_reais() {
	global $wpdb;

	$a  = jelly_ar_tabela( 'associados' );
	$ac = jelly_ar_tabela( 'acessos' );

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$linhas = $wpdb->get_results(
		"SELECT a.*, u.user_email, ap.display_name AS aprovador,
			(SELECT COUNT(*) FROM {$ac} x WHERE x.user_id = a.user_id) AS acessos,
			(SELECT MAX(x.criado_em) FROM {$ac} x WHERE x.user_id = a.user_id) AS ultimo
		FROM {$a} a
		INNER JOIN {$wpdb->users} u ON u.ID = a.user_id
		LEFT JOIN {$wpdb->users} ap ON ap.ID = a.aprovado_por"
	);

	$data = function ( $gmt ) {
		return $gmt ? get_date_from_gmt( $gmt, 'd/m/Y H:i' ) : '';
	};

	return array_map( function ( $l ) use ( $data ) {
		return [
			'id'           => (int) $l->user_id,
			'nome'         => $l->nome,
			'apelido'      => $l->apelido,
			'empresa'      => $l->empresa,
			'email'        => $l->user_email,
			'telefone'     => $l->telefone,
			'estado'       => $l->estado,
			'registo'      => $data( $l->registado_em ),
			'aprovado'     => $data( $l->aprovado_em ),
			'aprovado_por' => (string) $l->aprovador,
			'ultimo'       => $data( $l->ultimo ),
			'acessos'      => (int) $l->acessos,
			'real'         => true,
		];
	}, $linhas );
}

/**
 * Os utilizadores: os associados a sério e, enquanto JELLY_AR_EXEMPLO for
 * true, os de exemplo a seguir, com ids a partir de 900000 para nunca darem
 * com uma conta verdadeira.
 */
function jelly_ar_utilizadores_todos() {
	$reais = jelly_ar_utilizadores_reais();

	if ( ! JELLY_AR_EXEMPLO ) {
		return $reais;
	}

	require_once JELLY_AR_DIR . 'inc/admin-exemplo.php';

	return array_merge( $reais, jelly_ar_exemplo_utilizadores() );
}

/**
 * Os acessos de um utilizador, do mais recente para o mais antigo: da tabela,
 * se for real; dos exemplos, se não.
 */
function jelly_ar_acessos_de( $u, $limite = 0 ) {
	$linhas = jelly_ar_obter_acessos( $u['id'] );

	return $limite ? array_slice( $linhas, 0, $limite ) : $linhas;
}

/* ---------- Aprovar, rejeitar, suspender ---------- */

/*
 * As decisões sobre um associado, no perfil e na lista. Só para os reais: os
 * de exemplo continuam a mudar só no ecrã (assets/js/admin.js).
 *
 * De cada estado, só se sai para os que fazem sentido:
 *   pendente  → ativo (aprovar) ou rejeitado
 *   rejeitado → ativo (aprovar)
 *   ativo     → suspenso
 *   suspenso  → ativo (reativar)
 * e de qualquer um se pode apagar: sai tudo o que é da pessoa (a conta, o
 * perfil, os acessos, as descargas e as marcações), de vez.
 *
 * Aprovar envia o e-mail para definir a palavra-passe; rejeitar avisa a pessoa.
 * Suspender, reativar e apagar não enviam nada.
 */
const JELLY_AR_DECISOES = [
	'pendente'  => [ 'ativo', 'rejeitado', 'apagar' ],
	'rejeitado' => [ 'ativo', 'apagar' ],
	'ativo'     => [ 'suspenso', 'apagar' ],
	'suspenso'  => [ 'ativo', 'apagar' ],
];

function jelly_ar_url_decidir( $user_id, $para ) {
	return [
		'action'     => 'jelly_ar_utilizador_estado',
		'utilizador' => (int) $user_id,
		'para'       => $para,
		'nonce'      => 'jelly_ar_utilizador_estado_' . (int) $user_id . '_' . $para,
	];
}

/**
 * Um formulário escondido que muda o estado, para um botão o enviar pelo
 * atributo form (o id que devolve). Assim os botões ficam onde estão no ecrã.
 */
function jelly_ar_form_decidir( $user_id, $para ) {
	$f  = jelly_ar_url_decidir( $user_id, $para );
	$id = 'jar-decidir-' . (int) $user_id . '-' . $para;
	?>
	<form id="<?php echo esc_attr( $id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
		<input type="hidden" name="action" value="<?php echo esc_attr( $f['action'] ); ?>">
		<input type="hidden" name="utilizador" value="<?php echo (int) $user_id; ?>">
		<input type="hidden" name="para" value="<?php echo esc_attr( $para ); ?>">
		<?php wp_nonce_field( $f['nonce'] ); ?>
	</form>
	<?php
	return $id;
}

function jelly_ar_utilizador_estado() {
	global $wpdb;

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verificado a seguir, com o id e a decisão
	$id   = isset( $_POST['utilizador'] ) ? absint( $_POST['utilizador'] ) : 0;
	$para = isset( $_POST['para'] ) ? sanitize_key( wp_unslash( $_POST['para'] ) ) : '';
	// phpcs:enable

	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( 'jelly_ar_utilizador_estado_' . $id . '_' . $para );

	$perfil = jelly_ar_associado( $id );
	$user   = get_userdata( $id );

	if ( ! $perfil || ! $user ) {
		wp_die( esc_html__( 'Esse utilizador não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	$voltar = function ( $args ) use ( $id ) {
		wp_safe_redirect( jelly_ar_admin_url( 'utilizadores', $args + [ 'utilizador' => $id ] ) );
		exit;
	};

	if ( ! in_array( $para, JELLY_AR_DECISOES[ $perfil->estado ] ?? [], true ) ) {
		$voltar( [ 'erro' => 'decisao' ] );
	}

	$tabela = jelly_ar_tabela( 'associados' );

	/*
	 * Apagar: sai tudo o que é da pessoa — as tabelas da AR e a conta do
	 * WordPress. Uma conta que seja também administrador não se apaga daqui: é
	 * da equipa, e a sua saída faz-se no WordPress.
	 */
	if ( 'apagar' === $para ) {
		if ( jelly_ar_e_administrador( $user ) ) {
			$voltar( [ 'erro' => 'apagar-admin' ] );
		}

		require_once ABSPATH . 'wp-admin/includes/user.php';

		foreach ( [ 'acessos', 'descargas', 'marcacoes' ] as $t ) {
			$wpdb->delete( jelly_ar_tabela( $t ), [ 'user_id' => $id ], [ '%d' ] ); // phpcs:ignore WordPress.DB
		}
		$wpdb->delete( $tabela, [ 'user_id' => $id ], [ '%d' ] ); // phpcs:ignore WordPress.DB
		wp_delete_user( $id );

		wp_safe_redirect( jelly_ar_admin_url( 'utilizadores', [ 'aviso' => 'apagado' ] ) );
		exit;
	}

	$agora  = current_time( 'mysql', true );
	$campos = [ 'estado' => $para, 'atualizado_em' => $agora ];

	// Aprovar um pedido guarda quando e quem; reativar um suspenso não mexe na aprovação.
	if ( 'ativo' === $para && in_array( $perfil->estado, [ 'pendente', 'rejeitado' ], true ) ) {
		$campos['aprovado_em']  = $agora;
		$campos['aprovado_por'] = get_current_user_id();
	}

	$wpdb->update( $tabela, $campos, [ 'user_id' => $id ] ); // phpcs:ignore WordPress.DB

	$aviso = [ 'ativo' => 'suspenso' === $perfil->estado ? 'reativado' : 'aprovado', 'rejeitado' => 'rejeitado', 'suspenso' => 'suspenso' ][ $para ];

	if ( 'aprovado' === $aviso ) {
		$aviso = jelly_ar_email_aprovado( $user, $perfil ) ? 'aprovado' : 'aprovado-sem-email';
	} elseif ( 'rejeitado' === $aviso ) {
		jelly_ar_email_rejeitado( $user, $perfil );
	}

	$voltar( [ 'aviso' => $aviso ] );
}
add_action( 'admin_post_jelly_ar_utilizador_estado', 'jelly_ar_utilizador_estado' );

/**
 * O botão "Enviar e-mail de nova palavra-passe" do perfil: só para um associado
 * real com o acesso ativo — um suspenso não entraria, e um pedido por aprovar
 * recebe a ligação ao ser aprovado.
 */
function jelly_ar_utilizador_senha() {
	$id = isset( $_POST['utilizador'] ) ? absint( $_POST['utilizador'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( ! jelly_ar_e_administrador() ) {
		wp_die( esc_html__( 'Esta área é só para administradores.', 'jelly-area-reservada' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( 'jelly_ar_utilizador_senha_' . $id );

	$perfil = jelly_ar_associado( $id );
	$user   = get_userdata( $id );

	if ( ! $perfil || ! $user ) {
		wp_die( esc_html__( 'Esse utilizador não existe.', 'jelly-area-reservada' ), '', [ 'response' => 404 ] );
	}

	if ( 'ativo' !== $perfil->estado ) {
		$aviso = [ 'erro' => 'senha-estado' ];
	} else {
		$aviso = jelly_ar_email_nova_senha( $user, $perfil ) ? [ 'aviso' => 'senha' ] : [ 'erro' => 'senha-sem-email' ];
	}

	wp_safe_redirect( jelly_ar_admin_url( 'utilizadores', $aviso + [ 'utilizador' => $id ] ) );
	exit;
}
add_action( 'admin_post_jelly_ar_utilizador_senha', 'jelly_ar_utilizador_senha' );
