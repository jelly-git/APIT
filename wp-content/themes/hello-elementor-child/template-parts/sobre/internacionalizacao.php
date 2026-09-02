<?php
/**
 * Internacionalização — the full-bleed gradient band carrying the Watch
 * Portugal lockup.
 *
 * Content comes from the ACF group on the page
 * (Sobre a APIT › Internacionalização).
 */
$titulo = trim( (string) apit_campo( 'sobre_inter_titulo' ) );
$texto  = trim( (string) apit_campo( 'sobre_inter_texto' ) );
$botao  = trim( (string) apit_campo( 'sobre_inter_botao' ) );
$url    = trim( (string) apit_campo( 'sobre_inter_url' ) );

// Falls back to the copy that ships with the theme, so the band is never left
// with an empty half if the field is cleared.
$marca = apit_media_url( (string) apit_campo( 'sobre_inter_marca' ), 'img' );

if ( ! $marca ) {
	$marca = get_stylesheet_directory_uri() . '/assets/img/logo-watch-portugal-branco.png';
}
?>
<section class="sobre-inter">
	<div class="apit-container sobre-inter__interior">
		<div class="sobre-inter__texto">
			<?php if ( $titulo ) : ?>
				<h2 class="sobre-inter__titulo"><?php echo esc_html( $titulo ); ?></h2>
			<?php endif; ?>

			<?php if ( $texto ) : ?>
				<p class="sobre-inter__descricao"><?php echo esc_html( $texto ); ?></p>
			<?php endif; ?>

			<?php if ( $botao ) : ?>
				<a class="btn btn--outline" href="<?php echo esc_url( $url ? $url : '#' ); ?>">
					<?php echo esc_html( $botao ); ?>
					<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
				</a>
			<?php endif; ?>
		</div>

		<div class="sobre-inter__marca">
			<img
				src="<?php echo esc_url( $marca ); ?>"
				alt="Watch Portugal — Independent TV Producers"
			>
		</div>
	</div>
</section>
