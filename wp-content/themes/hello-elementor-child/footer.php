<?php
/**
 * Footer - APIT
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	<footer class="apit-footer">
		<div class="apit-container apit-footer__main">
			<div class="apit-footer__brand">
				<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo-apit.png' ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="250" height="141">
			</div>

			<nav class="apit-footer__links" aria-label="<?php esc_attr_e( 'Links do rodapé', 'apit' ); ?>">
				<?php
				wp_nav_menu( [
					'theme_location' => 'footer-links',
					'container'      => false,
					'menu_class'     => 'apit-footer__links-menu',
					'fallback_cb'    => false,
				] );
				?>
			</nav>

			<div class="apit-footer__contact">
				<p>Av. Fernando Pessoa, 11 1º Sala 4<br>1990-108 Lisboa</p>
				<p><a href="mailto:geral@apitv.com" class="apit-footer__email">geral@apitv.com</a></p>
			</div>

			<?php
			/*
			 * A sibling of the contact block, not a child of it. On a wide screen
			 * it sits under the email as though it were inside; on a phone the
			 * design pairs it with the Watch Portugal mark instead, and a child
			 * could not leave its parent's row. The grid areas in style.css place
			 * it either way.
			 */
			?>
			<div class="apit-footer__social">
				<a href="<?php echo apit_child_social_url( 'instagram' ); ?>" aria-label="Instagram"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
				<a href="<?php echo apit_child_social_url( 'x_twitter' ); ?>" aria-label="X (Twitter)"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a>
				<a href="<?php echo apit_child_social_url( 'facebook' ); ?>" aria-label="Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
				<a href="<?php echo apit_child_social_url( 'linkedin' ); ?>" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
			</div>

			<div class="apit-footer__watch">
				<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo-watch-portugal-preto.png' ); ?>" alt="Watch Portugal" width="261" height="116">
			</div>
		</div>

		<div class="apit-container apit-footer__bottom">
			<p class="apit-footer__copy">&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> APIT - Todos os direitos reservados</p>
			<?php // The slashes are markup, not text, so they can be dropped when the links stack on a phone. ?>
			<p class="apit-footer__legal">
				<a href="#">Política de Privacidade</a>
				<span class="apit-footer__sep" aria-hidden="true">/</span>
				<a href="#">Termos e Condições</a>
				<span class="apit-footer__sep" aria-hidden="true">/</span>
				<a href="#">Livro de Reclamações</a>
			</p>
			<a href="https://jelly.pt" class="apit-footer__credit" target="_blank" rel="noopener">
				<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo-jelly.svg' ); ?>" alt="Jelly" width="61" height="23">
			</a>
		</div>
	</footer>

<?php wp_footer(); ?>
</body>
</html>
