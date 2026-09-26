<?php
/**
 * "Esqueceu-se da palavra-passe?": o e-mail da conta, e o servidor
 * (jelly_ar_recuperar(), inc/sessao.php) envia a ligação para definir uma
 * nova. A resposta é a mesma haja ou não conta com esse e-mail.
 */

defined( 'ABSPATH' ) || exit;
?>
<h2 class="apit-ar__titulo" id="apit-ar-titulo-recuperar"><?php esc_html_e( 'Recuperar palavra-passe', 'jelly-area-reservada' ); ?></h2>
<p class="apit-ar__intro"><?php esc_html_e( 'É enviada para o e-mail da conta uma ligação para definir uma nova palavra-passe.', 'jelly-area-reservada' ); ?></p>

<form class="apit-ar__form" data-ar-form="recuperar" method="post" action="<?php echo esc_attr( wp_make_link_relative( admin_url( 'admin-ajax.php' ) ) ); ?>" novalidate>
	<input type="hidden" name="action" value="jelly_ar_recuperar">
	<?php wp_nonce_field( 'jelly_ar_recuperar', '_wpnonce', false ); ?>

	<div class="apit-ar__campo">
		<label for="apit-ar-recuperar-email"><?php esc_html_e( 'E-mail', 'jelly-area-reservada' ); ?></label>
		<input
			type="email"
			id="apit-ar-recuperar-email"
			name="email"
			autocomplete="email"
			placeholder="<?php esc_attr_e( 'nome@empresa.pt', 'jelly-area-reservada' ); ?>"
			required
		>
	</div>

	<button type="submit" class="btn btn--solid apit-ar__submeter">
		<span data-ar-rotulo><?php esc_html_e( 'Enviar ligação', 'jelly-area-reservada' ); ?></span>
		<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
	</button>

	<p class="apit-ar__aviso" data-ar-aviso role="alert" hidden></p>
</form>

<div class="apit-ar__sucesso" data-ar-sucesso role="status" hidden>
	<span class="apit-ar__sucesso-icone" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
	<h2 class="apit-ar__sucesso-titulo" tabindex="-1" data-ar-sucesso-foco><?php esc_html_e( 'Pedido enviado', 'jelly-area-reservada' ); ?></h2>
	<p class="apit-ar__sucesso-texto">
		<?php
		printf(
			/* translators: %s: e-mail */
			esc_html__( 'Se existir uma conta ativa associada a %s, será enviada uma ligação para definir uma nova palavra-passe. A ligação é válida durante 24 horas.', 'jelly-area-reservada' ),
			'<strong data-ar-sucesso-email></strong>'
		);
		?>
	</p>
	<button type="button" class="btn btn--solid apit-ar__submeter" data-ar-fechar><?php esc_html_e( 'Fechar', 'jelly-area-reservada' ); ?></button>
</div>

<div class="apit-ar__rodape">
	<p><a href="#area-reservada" class="apit-ar__link"><?php esc_html_e( 'Voltar ao login', 'jelly-area-reservada' ); ?></a></p>
</div>
