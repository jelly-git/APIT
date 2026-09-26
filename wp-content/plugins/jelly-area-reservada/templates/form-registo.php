<?php
/**
 * Formulário de Registo.
 *
 * Nome, apelido, e-mail, telefone e empresa. Não há nome de utilizador nem
 * palavra-passe: a conta entra pelo e-mail, e a palavra-passe define-se
 * depois de a APIT aprovar o pedido — como hoje as credenciais só chegam
 * depois de a Direção aprovar a adesão. É isso que a nota e o estado final
 * dizem.
 */

defined( 'ABSPATH' ) || exit;

$privacidade = home_url( '/politica-privacidade/' );
?>
<h2 class="apit-ar__titulo" id="apit-ar-titulo-registo"><?php esc_html_e( 'Criar conta', 'jelly-area-reservada' ); ?></h2>
<p class="apit-ar__intro"><?php esc_html_e( 'Peça acesso aos conteúdos exclusivos da área reservada da APIT.', 'jelly-area-reservada' ); ?></p>

<form class="apit-ar__form" data-ar-form="registo" method="post" action="#" novalidate>
	<div class="apit-ar__grelha">
		<div class="apit-ar__campo">
			<label for="apit-ar-registo-nome"><?php esc_html_e( 'Nome', 'jelly-area-reservada' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="text"
				id="apit-ar-registo-nome"
				name="first_name"
				autocomplete="given-name"
				placeholder="<?php esc_attr_e( 'Ex.: João', 'jelly-area-reservada' ); ?>"
				required
			>
		</div>

		<div class="apit-ar__campo">
			<label for="apit-ar-registo-apelido"><?php esc_html_e( 'Apelido', 'jelly-area-reservada' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="text"
				id="apit-ar-registo-apelido"
				name="last_name"
				autocomplete="family-name"
				placeholder="<?php esc_attr_e( 'Ex.: Silva', 'jelly-area-reservada' ); ?>"
				required
			>
		</div>

		<div class="apit-ar__campo apit-ar__campo--largo">
			<label for="apit-ar-registo-email"><?php esc_html_e( 'E-mail', 'jelly-area-reservada' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="email"
				id="apit-ar-registo-email"
				name="email"
				autocomplete="email"
				placeholder="<?php esc_attr_e( 'nome@empresa.pt', 'jelly-area-reservada' ); ?>"
				required
			>
		</div>

		<div class="apit-ar__campo">
			<label for="apit-ar-registo-telefone"><?php esc_html_e( 'Telefone', 'jelly-area-reservada' ); ?></label>
			<input
				type="tel"
				id="apit-ar-registo-telefone"
				name="telefone"
				autocomplete="tel"
				placeholder="<?php esc_attr_e( '+351 912345678', 'jelly-area-reservada' ); ?>"
			>
		</div>

		<div class="apit-ar__campo">
			<label for="apit-ar-registo-empresa"><?php esc_html_e( 'Empresa', 'jelly-area-reservada' ); ?></label>
			<input
				type="text"
				id="apit-ar-registo-empresa"
				name="empresa"
				autocomplete="organization"
				placeholder="<?php esc_attr_e( 'Ex.: Produtora Exemplo', 'jelly-area-reservada' ); ?>"
			>
		</div>
	</div>

	<div class="apit-ar__campo apit-ar__campo--caixa">
		<label class="apit-ar__caixa">
			<input type="checkbox" id="apit-ar-registo-termos" name="termos" value="1" required>
			<span>
				<?php
				printf(
					/* translators: %s: link para a política de privacidade */
					esc_html__( 'Li e aceito a %s.', 'jelly-area-reservada' ),
					'<a href="' . esc_url( $privacidade ) . '" class="apit-ar__link" target="_blank" rel="noopener">' . esc_html__( 'Política de Privacidade', 'jelly-area-reservada' ) . '</a>'
				);
				?>
			</span>
		</label>
	</div>

	<p class="apit-ar__nota">
		<i class="fa-solid fa-circle-info" aria-hidden="true"></i>
		<?php esc_html_e( 'Depois de a APIT aprovar o pedido, recebe um e-mail para definir a palavra-passe.', 'jelly-area-reservada' ); ?>
	</p>

	<button type="submit" class="btn btn--solid apit-ar__submeter">
		<?php esc_html_e( 'Criar conta', 'jelly-area-reservada' ); ?>
		<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
	</button>
</form>

<div class="apit-ar__sucesso" data-ar-sucesso role="status" tabindex="-1" hidden>
	<i class="fa-solid fa-circle-check apit-ar__sucesso-icone" aria-hidden="true"></i>
	<h3 class="apit-ar__sucesso-titulo"><?php esc_html_e( 'Pedido enviado', 'jelly-area-reservada' ); ?></h3>
	<p><?php esc_html_e( 'Quando a APIT aprovar o seu acesso, recebe um e-mail para definir a palavra-passe.', 'jelly-area-reservada' ); ?></p>
	<button type="button" class="btn btn--outline apit-ar__voltar" data-ar-fechar><?php esc_html_e( 'Fechar', 'jelly-area-reservada' ); ?></button>
</div>

<div class="apit-ar__rodape">
	<p>
		<?php esc_html_e( 'Já tem conta?', 'jelly-area-reservada' ); ?>
		<a href="#area-reservada" class="apit-ar__link"><?php esc_html_e( 'Fazer login', 'jelly-area-reservada' ); ?></a>
	</p>
</div>
