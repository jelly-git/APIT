<?php
/**
 * Documentos Institucionais — the buttons in the hero's right column.
 *
 * A repeater rather than two fixed buttons: the design has Estatutos and
 * Regulamento Interno, but a third document should not need a code change.
 *
 * Each row links to an uploaded file if it has one, and falls back to a plain
 * URL otherwise — so a document can point at a page while its PDF is pending.
 *
 * Content comes from the ACF group on the page (Sobre a APIT › Documentos).
 */
$documentos = apit_campo( 'sobre_documentos' );

if ( ! is_array( $documentos ) || ! $documentos ) {
	return;
}
?>
<div class="sobre-hero__doc-botoes">
	<?php foreach ( $documentos as $documento ) : ?>
		<?php
		$etiqueta = trim( (string) ( $documento['etiqueta'] ?? '' ) );

		if ( '' === $etiqueta ) {
			continue;
		}

		$ficheiro = $documento['ficheiro'] ?? null;
		$e_anexo  = is_array( $ficheiro ) && ! empty( $ficheiro['url'] );
		$destino  = $e_anexo ? $ficheiro['url'] : trim( (string) ( $documento['url'] ?? '' ) );
		?>
		<a
			class="btn btn--outline btn--escuro"
			href="<?php echo esc_url( $destino ? $destino : '#' ); ?>"
			<?php // A download opens alongside the page; an internal link replaces it. ?>
			<?php echo $e_anexo ? 'target="_blank" rel="noopener"' : ''; ?>
		>
			<?php echo esc_html( $etiqueta ); ?>
			<i class="fa-regular fa-file-lines" aria-hidden="true"></i>
		</a>
	<?php endforeach; ?>
</div>
