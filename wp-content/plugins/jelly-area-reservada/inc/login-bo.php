<?php
/**
 * A página de entrada do back-office (wp-login.php) com o aspeto do pop-up da
 * Área Reservada — o que os associados veem para entrar e definir a
 * palavra-passe: à esquerda o painel de arte (o degradé da marca, o logótipo
 * branco e a frase), à direita o formulário, com os campos e o botão do
 * pop-up e a letra do site (assets/css/login.css).
 *
 * Só muda o aspeto: o formulário continua a ser o do WordPress, com tudo o
 * que ele faz (a sessão, a recuperação, o medidor da palavra-passe). Serve a
 * equipa — os associados entram pelo pop-up, e quem chega aqui com uma
 * ligação de palavra-passe de associado é levado para ele
 * (jelly_ar_senha_no_site(), inc/sessao.php).
 */

defined( 'ABSPATH' ) || exit;

function jelly_ar_login_estilos() {
	// A mesma letra do site (o tema carrega-a com este nome; aqui o tema não corre).
	wp_enqueue_style( 'apit-omnes-font', 'https://use.typekit.net/uqy3rtf.css', [], null );
	wp_enqueue_style( 'jelly-ar-login', JELLY_AR_URL . 'assets/css/login.css', [ 'login' ], jelly_ar_versao_ficheiro( 'assets/css/login.css' ) );

	// O logótipo branco do painel de arte, o mesmo do pop-up (inc/modal.php).
	$logo = jelly_ar_logo_url();
	if ( $logo ) {
		wp_add_inline_style( 'jelly-ar-login', '.login h1.wp-login-logo a{background-image:url(' . esc_url( $logo ) . ')}' );
	}
}
add_action( 'login_enqueue_scripts', 'jelly_ar_login_estilos' );

// O logótipo leva ao site, e não ao wordpress.org; e diz o nome do site.
add_filter( 'login_headerurl', function () {
	return home_url( '/' );
} );
add_filter( 'login_headertext', function () {
	return get_bloginfo( 'name' );
} );

// O seletor de idioma por baixo do cartão não faz sentido aqui: o back-office é em português.
add_filter( 'login_display_language_dropdown', '__return_false' );

/**
 * O título e a frase por cima do formulário, como no pop-up, conforme o que
 * a página está a fazer; a mensagem do WordPress, se houver, fica por baixo.
 */
function jelly_ar_login_cabeca( $mensagem ) {
	$acao = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$textos = [
		'lostpassword' => [ __( 'Recuperar a palavra-passe', 'jelly-area-reservada' ), __( 'Escreva o nome de utilizador ou o e-mail: chega uma ligação para definir uma nova.', 'jelly-area-reservada' ) ],
		'retrievepassword' => [ __( 'Recuperar a palavra-passe', 'jelly-area-reservada' ), __( 'Escreva o nome de utilizador ou o e-mail: chega uma ligação para definir uma nova.', 'jelly-area-reservada' ) ],
		'rp'           => [ __( 'Definir a palavra-passe', 'jelly-area-reservada' ), __( 'Escolha a nova palavra-passe da conta.', 'jelly-area-reservada' ) ],
		'resetpass'    => [ __( 'Definir a palavra-passe', 'jelly-area-reservada' ), __( 'Escolha a nova palavra-passe da conta.', 'jelly-area-reservada' ) ],
	];
	$t = $textos[ $acao ] ?? [ __( 'Back-office', 'jelly-area-reservada' ), __( 'A entrada da equipa da APIT na gestão do site e da Área Reservada.', 'jelly-area-reservada' ) ];

	// O WordPress põe a sua explicação da recuperação nesta mesma mensagem: a nossa frase substitui-a.
	if ( in_array( $acao, [ 'lostpassword', 'retrievepassword' ], true ) ) {
		$mensagem = '';
	}

	return sprintf( '<div class="jar-login__cabeca"><h2>%1$s</h2><p>%2$s</p></div>', esc_html( $t[0] ), esc_html( $t[1] ) ) . $mensagem;
}
add_filter( 'login_message', 'jelly_ar_login_cabeca' );
