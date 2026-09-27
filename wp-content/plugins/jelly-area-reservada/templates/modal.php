<?php
/**
 * O pop-up: painel de arte à esquerda, formulários à direita.
 *
 * Nasce com `hidden`, que o tira também da árvore de acessibilidade; o
 * JavaScript abre-o. Sem JavaScript os links ficam em #area-reservada, que não
 * leva a lado nenhum — nesta fase não há outro sítio para onde levar.
 */

defined( 'ABSPATH' ) || exit;

$logo = jelly_ar_logo_url();
?>
<div class="apit-ar" id="apit-ar" hidden>
	<div class="apit-ar__fundo" data-ar-fechar></div>

	<div
		class="apit-ar__dialogo"
		role="dialog"
		aria-modal="true"
		aria-labelledby="apit-ar-titulo-login"
		tabindex="-1"
	>
		<button type="button" class="apit-ar__fechar" data-ar-fechar aria-label="<?php esc_attr_e( 'Fechar', 'jelly-area-reservada' ); ?>">
			<i class="fa-solid fa-xmark" aria-hidden="true"></i>
		</button>

		<div class="apit-ar__arte">
			<?php if ( $logo ) : ?>
				<img class="apit-ar__logo" src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>">
			<?php else : ?>
				<span class="apit-ar__logo apit-ar__logo--texto"><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>

			<p class="apit-ar__slogan">
				<?php esc_html_e( 'Juntos fazemos chegar mais longe o audiovisual português.', 'jelly-area-reservada' ); ?>
			</p>
		</div>

		<div class="apit-ar__conteudo">
			<section class="apit-ar__painel" data-ar-painel="login">
				<?php jelly_ar_template( 'form-login' ); ?>
			</section>

			<section class="apit-ar__painel" data-ar-painel="registo" hidden>
				<?php jelly_ar_template( 'form-registo' ); ?>
			</section>

			<section class="apit-ar__painel" data-ar-painel="recuperar" hidden>
				<?php jelly_ar_template( 'form-recuperar' ); ?>
			</section>

			<section class="apit-ar__painel" data-ar-painel="senha" hidden>
				<?php jelly_ar_template( 'form-senha' ); ?>
			</section>

			<?php // A marcação de mesa, pelo botão dos cartões do calendário (#area-reservada-marcar-<id>). ?>
			<section class="apit-ar__painel" data-ar-painel="marcar" hidden>
				<?php jelly_ar_template( 'form-marcar' ); ?>
			</section>
		</div>
	</div>
</div>
