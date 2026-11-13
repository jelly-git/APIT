<?php
/**
 * O diálogo onde se pede um documento: Nome, Empresa e Email.
 *
 * Está em todas as páginas e fechado; o script (assets/js/pedido-documento.js)
 * abre-o quando se clica num botão com data-pedir ou num link "#pedir-…", e
 * preenche o título com o do documento. O envio vai para a rota REST de
 * inc/documentos-pedido.php.
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog class="pedido" id="apit-pedido" aria-labelledby="apit-pedido-titulo">
	<form class="pedido__form" method="post" novalidate data-rota="<?php echo esc_url( rest_url( 'apit/v1/pedido-documento' ) ); ?>">
		<button type="button" class="pedido__fechar" data-fechar aria-label="<?php esc_attr_e( 'Fechar', 'apit' ); ?>"></button>

		<p class="pedido__etiqueta"><?php esc_html_e( 'Download', 'apit' ); ?></p>
		<h2 class="pedido__titulo" id="apit-pedido-titulo"></h2>

		<?php
		/*
		 * Os títulos dos documentos que não estão numa lista — os Estatutos, que
		 * se abrem por "#pedir-estatutos" no menu e nos botões do Elementor.
		 * Estão aqui como texto, e não no script, para o Weglot os traduzir com o
		 * resto da página: o título escrito no JavaScript ficava "Estatutos" na
		 * versão inglesa. O script lê-os pela chave.
		 */
		?>
		<span data-pedido-titulo="estatutos" hidden><?php esc_html_e( 'Estatutos', 'apit' ); ?></span>

		<p class="pedido__texto"><?php esc_html_e( 'Para descarregar o documento, preencha os seus dados.', 'apit' ); ?></p>

		<input type="hidden" name="chave" value="">

		<div class="pedido__campo">
			<label for="apit-pedido-nome"><?php esc_html_e( 'Nome', 'apit' ); ?></label>
			<input type="text" id="apit-pedido-nome" name="nome" autocomplete="name" required>
			<span class="pedido__erro" data-erro="nome" hidden></span>
		</div>

		<div class="pedido__campo">
			<label for="apit-pedido-empresa"><?php esc_html_e( 'Empresa', 'apit' ); ?></label>
			<input type="text" id="apit-pedido-empresa" name="empresa" autocomplete="organization" required>
			<span class="pedido__erro" data-erro="empresa" hidden></span>
		</div>

		<div class="pedido__campo">
			<label for="apit-pedido-email"><?php esc_html_e( 'Email', 'apit' ); ?></label>
			<input type="email" id="apit-pedido-email" name="email" autocomplete="email" required>
			<span class="pedido__erro" data-erro="email" hidden></span>
		</div>

		<?php // A armadilha para robôs: fora do ecrã e fora da ordem do teclado. ?>
		<div class="pedido__armadilha" aria-hidden="true">
			<label for="apit-pedido-site"><?php esc_html_e( 'Não preencher', 'apit' ); ?></label>
			<input type="text" id="apit-pedido-site" name="apit_site" tabindex="-1" autocomplete="off">
		</div>

		<div class="pedido__campo pedido__campo--consentimento">
			<label class="pedido__consentimento">
				<input type="checkbox" name="consentimento" value="1" required>
				<span><?php echo apit_newsletter_texto_consentimento( null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- já passa por wp_kses. ?></span>
			</label>
			<span class="pedido__erro" data-erro="consentimento" hidden></span>
		</div>

		<p class="pedido__estado" role="status" aria-live="polite" hidden></p>

		<button type="submit" class="pedido__submit"><?php esc_html_e( 'Descarregar', 'apit' ); ?></button>
	</form>
</dialog>
