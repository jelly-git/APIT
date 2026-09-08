<?php
/**
 * Associados — benefits list.
 *
 * Each item is a portrait-less circle: just an icon or small illustration, a
 * title and a line of text. An item is only worth printing once it has
 * something to say, so a row left with an image but no copy (or vice versa,
 * a title typed but the text forgotten) is skipped rather than shown half
 * empty — the editor sees the gap immediately instead of it reaching the
 * front end.
 */
$label      = trim( (string) apit_campo( 'assoc_benef_label' ) );
$beneficios = apit_campo( 'assoc_beneficios' );

$itens = [];

if ( is_array( $beneficios ) ) {
	foreach ( $beneficios as $beneficio ) {
		$titulo = trim( (string) ( $beneficio['titulo'] ?? '' ) );
		$texto  = trim( (string) ( $beneficio['texto'] ?? '' ) );

		if ( '' === $titulo && '' === $texto ) {
			continue;
		}

		$itens[] = [
			'imagem' => (int) ( $beneficio['imagem'] ?? 0 ),
			'titulo' => $titulo,
			'texto'  => $texto,
		];
	}
}

if ( ! $itens ) {
	return;
}
?>
<section class="assoc-beneficios">
	<?php if ( $label ) : ?>
		<p class="apit-secao__etiqueta"><?php echo esc_html( $label ); ?></p>
	<?php endif; ?>

	<ul class="assoc-beneficios__lista">
		<?php foreach ( $itens as $item ) : ?>
			<li class="assoc-beneficios__item">
				<?php if ( $item['imagem'] ) : ?>
					<div class="assoc-beneficios__circulo">
						<?php echo wp_get_attachment_image( $item['imagem'], 'medium', false, [ 'alt' => '' ] ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $item['titulo'] ) : ?>
					<h3 class="assoc-beneficios__titulo"><?php echo esc_html( $item['titulo'] ); ?></h3>
				<?php endif; ?>

				<?php if ( $item['texto'] ) : ?>
					<p class="assoc-beneficios__texto"><?php echo esc_html( $item['texto'] ); ?></p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
