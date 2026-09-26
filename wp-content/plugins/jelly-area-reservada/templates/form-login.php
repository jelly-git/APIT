<?php
/**
 * Formulário de Login.
 *
 * E-mail e palavra-passe. Os `name` são já os do wp_signon(), para que ligar isto ao servidor seja
 * acrescentar o tratamento e não mexer no desenho. Nesta fase o JavaScript
 * valida e mostra o estado final; nada sai do browser.
 */

defined( 'ABSPATH' ) || exit;
?>
<h2 class="apit-ar__titulo" id="apit-ar-titulo-login"><?php esc_html_e( 'Área Reservada', 'jelly-area-reservada' ); ?></h2>
<p class="apit-ar__intro"><?php esc_html_e( 'Aceda à sua conta para consultar conteúdos exclusivos.', 'jelly-area-reservada' ); ?></p>

<form class="apit-ar__form" data-ar-form="login" method="post" action="#" novalidate>
	<div class="apit-ar__campo">
		<?php // Entra-se pelo e-mail; o wp_signon() aceita-o no mesmo user_login. ?>
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
				placeholder="<?php esc_attr_e( 'Introduza a sua palavra-passe', 'jelly-area-reservada' ); ?>"
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
			<span><?php esc_html_e( 'Lembrar-me', 'jelly-area-reservada' ); ?></span>
		</label>

		<?php // Sem destino nesta fase: a recuperação chega com o módulo dos utilizadores. ?>
		<a href="#" class="apit-ar__link" data-ar-em-breve><?php esc_html_e( 'Esqueceu-se da palavra-passe?', 'jelly-area-reservada' ); ?></a>
	</div>

	<button type="submit" class="btn btn--solid apit-ar__submeter">
		<?php esc_html_e( 'Entrar', 'jelly-area-reservada' ); ?>
		<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
	</button>

	<p class="apit-ar__aviso" data-ar-aviso role="status" hidden></p>
</form>

<div class="apit-ar__rodape">
	<p>
		<?php esc_html_e( 'Ainda não tem conta?', 'jelly-area-reservada' ); ?>
		<a href="#area-reservada-registo" class="apit-ar__link"><?php esc_html_e( 'Criar conta', 'jelly-area-reservada' ); ?></a>
	</p>
	<p>
		<?php esc_html_e( 'Precisa de ajuda?', 'jelly-area-reservada' ); ?>
		<a href="<?php echo esc_url( home_url( '/contactos/' ) ); ?>" class="apit-ar__link"><?php esc_html_e( 'Contacte-nos', 'jelly-area-reservada' ); ?></a>
	</p>
</div>
