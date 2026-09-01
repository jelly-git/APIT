<?php
/**
 * Newsletter section (home) — Figma node 19:19941
 *
 * The markup is a plain form so a mail plugin (or a REST handler) can take it
 * over later; there is no submit handler wired up yet.
 */
?>
<section class="newsletter">
	<div class="apit-container newsletter__inner">
		<h2 class="newsletter__title">Subscrever APIT News</h2>

		<form class="newsletter__form" action="#" method="post">
			<div class="newsletter__row">
				<label class="newsletter__field">
					<span class="screen-reader-text">Nome</span>
					<input type="text" name="apit_nome" placeholder="Nome" required>
				</label>
				<label class="newsletter__field">
					<span class="screen-reader-text">Empresa</span>
					<input type="text" name="apit_empresa" placeholder="Empresa">
				</label>
			</div>

			<label class="newsletter__field newsletter__field--full">
				<span class="screen-reader-text">Email</span>
				<input type="email" name="apit_email" placeholder="Email" required>
			</label>

			<div class="newsletter__row newsletter__row--bottom">
				<label class="newsletter__consent">
					<input type="checkbox" name="apit_consentimento" required>
					<span>Aceito os termos da <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'politica-privacidade' ) ) ); ?>">Política de Privacidade</a></span>
				</label>

				<button type="submit" class="btn btn--solid newsletter__submit">
					Subscrever <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
				</button>
			</div>
		</form>
	</div>
</section>
