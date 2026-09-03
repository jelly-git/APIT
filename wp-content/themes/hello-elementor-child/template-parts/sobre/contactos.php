<?php
/**
 * Contactos — the address block beside a live map.
 *
 * The map is Google's own embed endpoint driven by the address string. It
 * needs no API key and no plugin, and because the URL is built at render time
 * it survives the site moving domain. Elementor's free Google Maps widget
 * would work too, but it sits inside a container and could not bleed to the
 * right edge the way the design has it.
 *
 * Content comes from the ACF group on the page (Sobre a APIT › Contactos).
 */
$titulo   = trim( (string) apit_campo( 'sobre_contactos_titulo' ) );
$endereco = trim( (string) apit_campo( 'sobre_contactos_endereco' ) );
$email    = trim( (string) apit_campo( 'sobre_contactos_email' ) );
$telefone = trim( (string) apit_campo( 'sobre_contactos_telefone' ) );
$nota     = trim( (string) apit_campo( 'sobre_contactos_nota' ) );

$mapa = $endereco
	? add_query_arg(
		[
			// The address is a textarea, so its line breaks become the commas
			// the query needs.
			'q'      => rawurlencode( trim( preg_replace( '/\s*\R\s*/', ', ', $endereco ) ) ),
			'z'      => 16,
			'output' => 'embed',
		],
		'https://www.google.com/maps'
	)
	: '';
?>
<section class="sobre-contactos">
	<div class="sobre-contactos__interior">
		<div class="sobre-contactos__info">
			<div class="sobre-contactos__info-interior">
				<?php if ( $titulo ) : ?>
					<h2 class="sobre-contactos__titulo"><?php echo esc_html( $titulo ); ?></h2>
				<?php endif; ?>

				<div class="sobre-contactos__dados">
					<?php if ( $endereco ) : ?>
						<p class="sobre-contactos__endereco"><?php echo nl2br( esc_html( $endereco ) ); ?></p>
					<?php endif; ?>

					<?php if ( $email ) : ?>
						<p><a class="sobre-contactos__email" href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
					<?php endif; ?>

					<?php if ( $telefone ) : ?>
						<p class="sobre-contactos__telefone">
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $telefone ) ); ?>"><?php echo esc_html( $telefone ); ?></a>
						</p>
					<?php endif; ?>

					<?php if ( $nota ) : ?>
						<p class="sobre-contactos__nota"><?php echo esc_html( $nota ); ?></p>
					<?php endif; ?>

					<div class="sobre-contactos__social">
						<?php apit_redes_sociais_html(); ?>
					</div>
				</div>
			</div>
		</div>

		<?php if ( $mapa ) : ?>
			<div class="sobre-contactos__mapa">
				<iframe
					src="<?php echo esc_url( $mapa ); ?>"
					title="<?php echo esc_attr( sprintf( __( 'Mapa: %s', 'apit' ), $endereco ) ); ?>"
					loading="lazy"
					referrerpolicy="no-referrer-when-downgrade"
					allowfullscreen
				></iframe>
			</div>
		<?php endif; ?>
	</div>
</section>
