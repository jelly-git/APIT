<?php
/**
 * Formulário de Login.
 *
 * E-mail e palavra-passe, enviados pelo pop-up para o admin-ajax
 * (jelly_ar_entrar(), inc/sessao.php). Um acesso por aprovar, suspenso ou
 * rejeitado não entra, e a mensagem diz porquê.
 *
 * Com a sessão já iniciada, o painel mostra quem entrou e a saída, em vez do
 * formulário.
 */

defined( 'ABSPATH' ) || exit;

$ajax = wp_make_link_relative( admin_url( 'admin-ajax.php' ) );
?>
<h2 class="apit-ar__titulo" id="apit-ar-titulo-login"><?php esc_html_e( 'Área Reservada', 'jelly-area-reservada' ); ?></h2>

<?php if ( is_user_logged_in() ) : ?>
	<?php $eu = wp_get_current_user(); ?>
	<p class="apit-ar__intro">
		<?php
		/* translators: %s: nome */
		printf( esc_html__( 'Sessão iniciada como %s.', 'jelly-area-reservada' ), '<strong>' . esc_html( $eu->display_name ) . '</strong>' );
		?>
	</p>
	<a class="btn btn--solid apit-ar__submeter" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
		<?php esc_html_e( 'Terminar sessão', 'jelly-area-reservada' ); ?>
		<i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
	</a>
	<?php return; ?>
<?php endif; ?>

<p class="apit-ar__intro"><?php esc_html_e( 'Acesso reservado aos associados da APIT.', 'jelly-area-reservada' ); ?></p>

<form class="apit-ar__form" data-ar-form="login" method="post" action="<?php echo esc_attr( $ajax ); ?>" novalidate>
	<input type="hidden" name="action" value="jelly_ar_entrar">
	<?php wp_nonce_field( 'jelly_ar_entrar', '_wpnonce', false ); ?>

	<div class="apit-ar__campo">
		<?php // Entra-se pelo e-mail; o servidor procura a conta por ele. ?>
		<label for="apit-ar-login-email"><?php esc_html_e( 'E-mail', 'jelly-area-reservada' ); ?></label>
		<input
			type="email"
			id="apit-ar-login-email"
			name="user_login"
			autocomplete="username"
			placeholder="<?php esc_attr_e( 'nome@empresa.pt', 'jelly-area-reservada' ); ?>"
			required
		>
	</div>

	<div class="apit-ar__campo">
		<label for="apit-ar-login-senha"><?php esc_html_e( 'Palavra-passe', 'jelly-area-reservada' ); ?></label>
		<div class="apit-ar__senha">
			<input
				type="password"
				id="apit-ar-login-senha"
				name="user_pass"
				autocomplete="current-password"
				required
			>
			<button type="button" class="apit-ar__ver-senha" data-ar-ver-senha aria-label="<?php esc_attr_e( 'Mostrar palavra-passe', 'jelly-area-reservada' ); ?>" aria-pressed="false">
				<i class="fa-regular fa-eye" aria-hidden="true"></i>
			</button>
		</div>
	</div>

	<div class="apit-ar__linha">
		<label class="apit-ar__caixa">
			<input type="checkbox" name="remember" value="forever">
			<span><?php esc_html_e( 'Manter a sessão iniciada', 'jelly-area-reservada' ); ?></span>
		</label>

		<a href="#area-reservada-recuperar" class="apit-ar__link"><?php esc_html_e( 'Esqueceu-se da palavra-passe?', 'jelly-area-reservada' ); ?></a>
	</div>

	<button type="submit" class="btn btn--solid apit-ar__submeter">
		<span data-ar-rotulo><?php esc_html_e( 'Entrar', 'jelly-area-reservada' ); ?></span>
		<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
	</button>

	<p class="apit-ar__aviso" data-ar-aviso role="alert" hidden></p>
</form>

<div class="apit-ar__rodape">
	<p>
		<?php esc_html_e( 'Ainda não tem conta?', 'jelly-area-reservada' ); ?>
		<a href="#area-reservada-registo" class="apit-ar__link"><?php esc_html_e( 'Criar conta', 'jelly-area-reservada' ); ?></a>
	</p>
	<p>
		<?php esc_html_e( 'Precisa de ajuda?', 'jelly-area-reservada' ); ?>
		<a href="<?php echo esc_url( home_url( '/contactos/' ) ); ?>" class="apit-ar__link"><?php esc_html_e( 'Contactar a APIT', 'jelly-area-reservada' ); ?></a>
	</p>
</div>
