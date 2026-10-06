<?php
/**
 * Newsletter section (home) — Figma node 19:19941
 *
 * The markup is the theme's, so the design stays as it is; the subscription is
 * handed to the Gravity Forms "Newsletter" form (inc/newsletter.php), which
 * keeps the entry, sends the notifications and, with the Mailchimp Add-On, adds
 * the address to the audience.
 *
 * Which fields are required, their placeholders, the consent text and the
 * confirmation all come from that form, so they are edited in the back office.
 * Without Gravity Forms or the form the section still renders, with the texts
 * it always had, and the submission answers that it is not available.
 *
 * It posts to admin-post.php, which works with no JavaScript;
 * assets/js/newsletter.js sends the same data to the REST route instead and
 * answers in place.
 */
$f = function_exists( 'apit_newsletter_formulario' ) ? apit_newsletter_formulario() : null;

$campo = static function ( $papel, $rotulo, $obrigatorio ) use ( $f ) {
	$c = $f[ $papel ] ?? null;

	return [
		'placeholder' => $c && '' !== trim( (string) $c->placeholder ) ? $c->placeholder : $rotulo,
		'obrigatorio' => $c ? (bool) $c->isRequired : $obrigatorio,
	];
};

$nome    = $campo( 'nome', __( 'Nome', 'apit' ), true );
$empresa = $campo( 'empresa', __( 'Empresa', 'apit' ), false );
$email   = $campo( 'email', __( 'Email', 'apit' ), true );

// The result of a submission made without JavaScript comes back in the address.
$estado = isset( $_GET['newsletter'] ) ? sanitize_key( $_GET['newsletter'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only picks which message to show.

wp_enqueue_script( 'apit-newsletter' );
?>
<?php // No id here: the Elementor widget around it carries id="newsletter", the menu's /#newsletter. ?>
<section class="newsletter">
	<div class="apit-container newsletter__inner">
		<?php
		/*
		 * No <br> here: the line break comes from a max-width in ch units, so it
		 * happens after "Subscrever" at every size instead of being frozen into
		 * the markup — which is what broke the title on a phone.
		 */
		?>
		<h2 class="newsletter__title">Subscrever APIT News</h2>

		<form class="newsletter__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" data-rota="<?php echo esc_url( rest_url( 'apit/v1/newsletter' ) ); ?>" novalidate>
			<input type="hidden" name="action" value="apit_newsletter">

			<?php
			/*
			 * The trap: hidden from people, filled by bots that fill everything.
			 * Off-screen rather than display:none, which some bots know to skip.
			 */
			?>
			<div class="newsletter__armadilha" aria-hidden="true">
				<label>Website <input type="text" name="apit_site" tabindex="-1" autocomplete="off"></label>
			</div>

			<div class="newsletter__row">
				<label class="newsletter__field">
					<span class="screen-reader-text"><?php esc_html_e( 'Nome', 'apit' ); ?></span>
					<input type="text" name="nome" autocomplete="name" placeholder="<?php echo esc_attr( $nome['placeholder'] ); ?>"<?php echo $nome['obrigatorio'] ? ' required' : ''; ?>>
				</label>
				<label class="newsletter__field">
					<span class="screen-reader-text"><?php esc_html_e( 'Empresa', 'apit' ); ?></span>
					<input type="text" name="empresa" autocomplete="organization" placeholder="<?php echo esc_attr( $empresa['placeholder'] ); ?>"<?php echo $empresa['obrigatorio'] ? ' required' : ''; ?>>
				</label>
			</div>

			<label class="newsletter__field newsletter__field--full">
				<span class="screen-reader-text"><?php esc_html_e( 'Email', 'apit' ); ?></span>
				<input type="email" name="email" autocomplete="email" placeholder="<?php echo esc_attr( $email['placeholder'] ); ?>"<?php echo $email['obrigatorio'] ? ' required' : ''; ?>>
			</label>

			<div class="newsletter__row newsletter__row--bottom">
				<label class="newsletter__consent">
					<input type="checkbox" name="consentimento" value="1" required>
					<span>
						<?php
						if ( function_exists( 'apit_newsletter_texto_consentimento' ) ) {
							echo apit_newsletter_texto_consentimento( $f['consentimento'] ?? null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- passed through wp_kses.
						} else {
							echo esc_html__( 'Aceito os termos da Política de Privacidade', 'apit' );
						}
						?>
					</span>
				</label>

				<button type="submit" class="btn btn--solid newsletter__submit">
					<?php esc_html_e( 'Subscrever', 'apit' ); ?> <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
				</button>
			</div>

			<p class="newsletter__estado<?php echo 'erro' === $estado ? ' is-erro' : ''; ?>" role="status" aria-live="polite"<?php echo $estado ? '' : ' hidden'; ?>>
				<?php
				if ( 'ok' === $estado ) {
					echo esc_html( $f ? apit_newsletter_confirmacao( $f['form'] ) : '' );
				} elseif ( 'erro' === $estado ) {
					esc_html_e( 'Não foi possível concluir a inscrição. Verifique os campos e tente de novo.', 'apit' );
				}
				?>
			</p>
		</form>
	</div>
</section>
