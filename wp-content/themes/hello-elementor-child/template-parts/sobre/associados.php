<?php
/**
 * Associados.
 *
 * The design had a logo carousel here. The client chose to drop the logos, so
 * the section is the label, an optional line of text and the link through to
 * the full list — which is why it reads as a slim band rather than a block.
 *
 * Content comes from the ACF group on the page (Sobre a APIT › Associados).
 */
$texto = trim( (string) apit_campo( 'sobre_assoc_texto' ) );
$botao = trim( (string) apit_campo( 'sobre_assoc_botao' ) );
$url   = trim( (string) apit_campo( 'sobre_assoc_url' ) );
?>
<section class="sobre-associados">
	<div class="apit-container sobre-associados__interior">
		<div class="sobre-associados__texto">
			<p class="sobre-rotulo"><?php esc_html_e( 'Associados', 'apit' ); ?></p>
			<?php if ( $texto ) : ?>
				<p class="sobre-associados__descricao"><?php echo esc_html( $texto ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $botao ) : ?>
			<a class="btn btn--outline btn--escuro" href="<?php echo esc_url( $url ? $url : '#' ); ?>">
				<?php echo esc_html( $botao ); ?>
				<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
			</a>
		<?php endif; ?>
	</div>
</section>
