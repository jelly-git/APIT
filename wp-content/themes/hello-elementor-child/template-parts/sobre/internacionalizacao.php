<?php
/**
 * Internacionalização — the full-bleed gradient band carrying the Watch
 * Portugal lockup.
 *
 * Used on Sobre a APIT, on the Calendário page and on Documentos. The copy is
 * the same on all three, so the fields are always read from Sobre a APIT
 * whichever page is rendering: one source and one screen to edit it in, rather
 * than the same paragraph typed three times and drifting apart.
 *
 * That is also why this is the band those pages use instead of a second
 * implementation — the block already existed here, styled and editable.
 */
$origem = get_page_by_path( 'sobre-apit' );
$origem = $origem ? $origem->ID : null;

$titulo = trim( (string) apit_campo( 'sobre_inter_titulo', $origem ) );
$texto  = trim( (string) apit_campo( 'sobre_inter_texto', $origem ) );
$botao  = trim( (string) apit_campo( 'sobre_inter_botao', $origem ) );
$url    = trim( (string) apit_campo( 'sobre_inter_url', $origem ) );

// Falls back to the copy that ships with the theme, so the band is never left
// with an empty half if the field is cleared.
$marca = apit_media_url( (string) apit_campo( 'sobre_inter_marca', $origem ), 'img' );

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
