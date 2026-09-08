<?php
/**
 * The document library, grouped by area — Anuários, Brochuras, Estudos.
 *
 * The groups come from the areas that actually have documents, not from a list
 * written here: a fourth area is then a term the client adds in wp-admin and it
 * appears on the page by itself. An empty area is skipped rather than printing
 * a heading over nothing.
 *
 * The cover is the featured image and the file is the ACF field on each
 * document, so this page needs no fields of its own.
 *
 * The download link carries the `download` attribute so a PDF saves instead of
 * opening in the browser's viewer, which is what the button in the design says
 * it does. `rel="noopener"` goes with it because the browser may still hand the
 * file to a new context.
 */
$areas = apit_get_areas_documento();

if ( ! $areas ) {
	return;
}

$tem_conteudo = false;
ob_start();

foreach ( $areas as $area ) {
	$documentos = apit_get_documentos( $area->term_id );

	if ( ! $documentos ) {
		continue;
	}

	$tem_conteudo = true;
	?>
	<div class="documentos__grupo">
		<h2 class="apit-secao__etiqueta documentos__area"><?php echo esc_html( $area->name ); ?></h2>

		<ul class="documentos__lista">
			<?php
			foreach ( $documentos as $doc ) :
				$ficheiro = apit_media_url( (string) apit_campo( 'documento_ficheiro', $doc->ID ), 'img' );
				?>
				<li class="documentos__item">
					<?php if ( has_post_thumbnail( $doc->ID ) ) : ?>
						<div class="documentos__capa">
							<?php echo get_the_post_thumbnail( $doc->ID, 'medium_large', [ 'alt' => '' ] ); ?>
						</div>
					<?php endif; ?>

					<h3 class="documentos__titulo"><?php echo esc_html( get_the_title( $doc ) ); ?></h3>

					<?php if ( $ficheiro ) : ?>
						<a class="documentos__download" href="<?php echo esc_url( $ficheiro ); ?>" download rel="noopener">
							<?php esc_html_e( 'Download', 'apit' ); ?>
						</a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

$html = ob_get_clean();

if ( ! $tem_conteudo ) {
	return;
}
?>
<section class="documentos">
	<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from escaped parts. ?>
</section>
