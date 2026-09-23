<?php
/**
 * Todos os Associados — the label, a line of copy and the grid of logos.
 *
 * One repeater row per member, in the order the grid shows them. A row without
 * a logo is skipped rather than drawn as an empty cell: the grid is five across
 * and a hole in it reads as a mistake, not as a member still to come.
 *
 * The name is the image's alt, and it is the row's own field rather than the
 * attachment's: the member is what the row describes, and the same file could
 * in principle be reused. When the name is empty the attachment's alt stands in.
 *
 * A site address turns the logo into a link that opens in a new tab — the
 * visitor is leaving the APIT for a member's own site and should not lose the
 * list on the way.
 *
 * Content comes from the ACF group on the page (Todos os Associados — conteúdos).
 */
$label  = trim( (string) apit_campo( 'assoc_lista_label' ) );
$texto  = trim( (string) apit_campo( 'assoc_lista_texto' ) );
$linhas = apit_campo( 'assoc_lista' );

$associados = [];

if ( is_array( $linhas ) ) {
	foreach ( $linhas as $linha ) {
		$logo = (int) ( $linha['logo'] ?? 0 );

		if ( ! $logo || ! wp_attachment_is_image( $logo ) ) {
			continue;
		}

		$nome = trim( (string) ( $linha['nome'] ?? '' ) );

		if ( '' === $nome ) {
			$nome = trim( (string) get_post_meta( $logo, '_wp_attachment_image_alt', true ) );
		}

		$associados[] = [
			'logo' => $logo,
			'nome' => $nome,
			'url'  => trim( (string) ( $linha['url'] ?? '' ) ),
		];
	}
}

if ( ! $associados ) {
	return;
}
?>
<section class="assoc-lista">
	<?php if ( $label ) : ?>
		<p class="apit-secao__etiqueta assoc-lista__etiqueta"><?php echo esc_html( $label ); ?></p>
	<?php endif; ?>

	<?php if ( $texto ) : ?>
		<p class="assoc-lista__texto"><?php echo nl2br( esc_html( $texto ) ); ?></p>
	<?php endif; ?>

	<ul class="assoc-lista__grelha">
		<?php foreach ( $associados as $associado ) : ?>
			<?php
			$imagem = wp_get_attachment_image(
				$associado['logo'],
				'full',
				false,
				[
					'class'   => 'assoc-lista__logo',
					'alt'     => $associado['nome'],
					'loading' => 'lazy',
				]
			);
			?>
			<li class="assoc-lista__item">
				<?php if ( $associado['url'] ) : ?>
					<a class="assoc-lista__link" href="<?php echo esc_url( $associado['url'] ); ?>" target="_blank" rel="noopener">
						<?php echo $imagem; // phpcs:ignore WordPress.Security.EscapeOutput -- core-built markup. ?>
						<span class="screen-reader-text"><?php esc_html_e( '(abre num novo separador)', 'apit' ); ?></span>
					</a>
				<?php else : ?>
					<?php echo $imagem; // phpcs:ignore WordPress.Security.EscapeOutput -- core-built markup. ?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
