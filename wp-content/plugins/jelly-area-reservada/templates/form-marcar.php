<?php
/**
 * A marcação de mesa num evento, pelo botão do cartão do calendário
 * (#area-reservada-marcar-<id>).
 *
 * O painel nasce vazio: o assets/js/area-reservada.js pede ao servidor o que é
 * do evento (jelly_ar_marcacao_dados(), inc/marcacoes.php) e mostra uma das
 * partes de baixo — a escolha do dia, da hora e da mesa; a marcação que o
 * associado já tem neste evento; ou um aviso. Sem sessão, o pop-up passa ao
 * login e volta aqui depois de entrar.
 */

defined( 'ABSPATH' ) || exit;

$ajax = wp_make_link_relative( admin_url( 'admin-ajax.php' ) );
?>
<div class="apit-ar__marcar" data-ar-marcar data-ar-ajax="<?php echo esc_attr( $ajax ); ?>">
	<p class="apit-ar__sobretitulo"><?php esc_html_e( 'Marcação de mesa', 'jelly-area-reservada' ); ?></p>
	<h2 class="apit-ar__titulo" id="apit-ar-titulo-marcar" tabindex="-1" data-ar-marcar-titulo></h2>
	<p class="apit-ar__intro apit-ar__marcar-evento" data-ar-marcar-evento></p>

	<?php // Enquanto o servidor responde. ?>
	<p class="apit-ar__carregar" data-ar-marcar-carregar role="status">
		<span class="apit-ar__roda" aria-hidden="true"></span>
		<?php esc_html_e( 'A carregar os horários disponíveis…', 'jelly-area-reservada' ); ?>
	</p>

	<?php // Um aviso em vez da escolha: o evento fechado, ou uma sessão que não é de associado. ?>
	<div class="apit-ar__aviso" data-ar-marcar-mensagem hidden></div>

	<form class="apit-ar__form apit-ar__escolha" method="post" action="<?php echo esc_attr( $ajax ); ?>" data-ar-marcar-form hidden novalidate>
		<input type="hidden" name="action" value="jelly_ar_marcacao_pedir">
		<input type="hidden" name="evento" value="">
		<input type="hidden" name="_wpnonce" value="">

		<fieldset class="apit-ar__passo">
			<legend><span class="apit-ar__passo-n" aria-hidden="true">1</span> <?php esc_html_e( 'Dia', 'jelly-area-reservada' ); ?></legend>
			<div class="apit-ar__opcoes apit-ar__opcoes--dias" data-ar-marcar-dias></div>
		</fieldset>

		<fieldset class="apit-ar__passo">
			<legend><span class="apit-ar__passo-n" aria-hidden="true">2</span> <?php esc_html_e( 'Hora', 'jelly-area-reservada' ); ?></legend>
			<div class="apit-ar__opcoes apit-ar__opcoes--horas" data-ar-marcar-horas></div>
		</fieldset>

		<fieldset class="apit-ar__passo">
			<legend><span class="apit-ar__passo-n" aria-hidden="true">3</span> <?php esc_html_e( 'Mesa', 'jelly-area-reservada' ); ?></legend>
			<p class="apit-ar__vazio" data-ar-marcar-sem-hora><?php esc_html_e( 'Escolha uma hora para ver as mesas livres.', 'jelly-area-reservada' ); ?></p>
			<div class="apit-ar__mesas" data-ar-marcar-mesas></div>
		</fieldset>

		<div class="apit-ar__aviso is-erro" data-ar-marcar-erro role="alert" hidden></div>

		<div class="apit-ar__marcar-fim">
			<p class="apit-ar__marcar-resumo" data-ar-marcar-resumo aria-live="polite"></p>
			<button type="submit" class="btn btn--solid apit-ar__submeter" data-ar-marcar-enviar disabled>
				<span data-ar-rotulo><?php esc_html_e( 'Pedir marcação', 'jelly-area-reservada' ); ?></span>
				<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
			</button>
		</div>

		<p class="apit-ar__nota">
			<i class="fa-solid fa-circle-info" aria-hidden="true"></i>
			<?php esc_html_e( 'O pedido fica a aguardar aprovação da APIT. A confirmação segue por e-mail. É possível uma marcação por evento.', 'jelly-area-reservada' ); ?>
		</p>
	</form>

	<?php
	/*
	 * O pedido enviado, ou a marcação que já existia neste evento, por ordem de
	 * leitura: o estado (a faixa de cima, na cor dele), a marcação (o cartão com
	 * a data, as horas e a mesa), o caminho do pedido e as ações. O evento não
	 * se repete: está no título do painel. O JavaScript escolhe o estado e
	 * preenche o cartão.
	 */
	?>
	<div class="apit-ar__marcada" data-ar-marcar-feita hidden>
		<div class="apit-ar__marcada-estado" data-ar-marcar-estado role="status">
			<span class="apit-ar__marcada-icone" aria-hidden="true"><i class="fa-solid fa-check" data-ar-marcar-icone></i></span>
			<div>
				<h3 class="apit-ar__marcada-titulo" tabindex="-1" data-ar-marcar-feita-titulo></h3>
				<p class="apit-ar__marcada-texto" data-ar-marcar-feita-texto></p>
			</div>
		</div>

		<div class="apit-ar__bilhete">
			<div class="apit-ar__bilhete-data" aria-hidden="true">
				<span data-ar-bilhete-mes></span>
				<strong data-ar-bilhete-dia></strong>
				<span data-ar-bilhete-ano></span>
			</div>
			<div class="apit-ar__bilhete-info">
				<span class="apit-ar__bilhete-semana" data-ar-bilhete-semana></span>
				<strong class="apit-ar__bilhete-horas"><i class="fa-regular fa-clock" aria-hidden="true"></i> <span data-ar-bilhete-horas></span></strong>
				<span class="apit-ar__bilhete-linha"><i class="fa-solid fa-chair" aria-hidden="true"></i> <span data-ar-bilhete-mesa></span></span>
				<span class="apit-ar__bilhete-linha" data-ar-bilhete-local><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <span></span></span>
			</div>
		</div>

		<ol class="apit-ar__passos apit-ar__passos--compacto" data-ar-marcar-passos aria-label="<?php esc_attr_e( 'Estado do pedido', 'jelly-area-reservada' ); ?>">
			<li data-ar-passo="1"><span class="apit-ar__passo-marca" aria-hidden="true">1</span> <?php esc_html_e( 'Pedido enviado', 'jelly-area-reservada' ); ?></li>
			<li data-ar-passo="2"><span class="apit-ar__passo-marca" aria-hidden="true">2</span> <?php esc_html_e( 'Aprovação pela APIT', 'jelly-area-reservada' ); ?></li>
			<li data-ar-passo="3"><span class="apit-ar__passo-marca" aria-hidden="true">3</span> <?php esc_html_e( 'Mesa confirmada', 'jelly-area-reservada' ); ?></li>
		</ol>

		<div class="apit-ar__marcada-acoes">
			<button type="button" class="btn btn--solid apit-ar__submeter" data-ar-fechar><?php esc_html_e( 'Fechar', 'jelly-area-reservada' ); ?></button>
			<p class="apit-ar__marcada-contacto">
				<?php
				printf(
					/* translators: %s: e-mail da APIT */
					esc_html__( 'Para alterar ou cancelar a marcação, a APIT deve ser contactada através do endereço %s.', 'jelly-area-reservada' ),
					'<a class="apit-ar__link" href="mailto:geral@apitv.com">geral@apitv.com</a>'
				);
				?>
			</p>
		</div>
	</div>
</div>
