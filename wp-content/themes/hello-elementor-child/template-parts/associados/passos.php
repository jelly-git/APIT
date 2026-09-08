<?php
/**
 * Associados — how-to-join steps.
 *
 * The numbers painted on the circles are the row's position in the list, not
 * a typed field: an editor reordering the steps in the back office (or
 * inserting a new one in the middle) then gets correct numbering for free,
 * instead of having to re-export artwork or edit a number sub-field to match.
 */
$passos = apit_campo( 'assoc_passos' );

$itens = [];

if ( is_array( $passos ) ) {
	foreach ( $passos as $passo ) {
		$titulo = trim( (string) ( $passo['titulo'] ?? '' ) );
		$texto  = trim( (string) ( $passo['texto'] ?? '' ) );

		if ( '' === $titulo && '' === $texto ) {
			continue;
		}

		$itens[] = [
			'imagem' => (int) ( $passo['imagem'] ?? 0 ),
			'titulo' => $titulo,
			'texto'  => $texto,
		];
	}
}

if ( ! $itens ) {
	return;
}
?>
<section class="assoc-passos">
	<ol class="assoc-passos__lista">
		<?php foreach ( $itens as $indice => $item ) : ?>
			<li class="assoc-passos__item">
				<div class="assoc-passos__circulo">
					<?php if ( $item['imagem'] ) : ?>
						<?php echo wp_get_attachment_image( $item['imagem'], 'medium', false, [ 'alt' => '' ] ); ?>
					<?php endif; ?>
					<span class="assoc-passos__numero"><?php echo esc_html( (string) ( $indice + 1 ) ); ?></span>
				</div>

				<?php if ( $item['titulo'] ) : ?>
					<h3 class="assoc-passos__titulo"><?php echo esc_html( $item['titulo'] ); ?></h3>
				<?php endif; ?>

				<?php if ( $item['texto'] ) : ?>
					<p class="assoc-passos__texto"><?php echo esc_html( $item['texto'] ); ?></p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
