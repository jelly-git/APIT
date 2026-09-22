<?php
/**
 * Associados — how-to-join steps.
 *
 * The number is part of the artwork the client uploads, so nothing is drawn
 * over it here. It used to be printed from the row's position, on the
 * assumption that the circles would arrive plain — that way reordering the
 * steps renumbered them for free. The images came with their numbers already
 * on them and the two were showing on top of each other.
 *
 * The cost of this is that reordering the steps in the back office now means
 * re-exporting the circles.
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
		<?php foreach ( $itens as $item ) : ?>
			<li class="assoc-passos__item">
				<div class="assoc-passos__circulo">
					<?php if ( $item['imagem'] ) : ?>
						<?php echo wp_get_attachment_image( $item['imagem'], 'medium', false, [ 'alt' => '' ] ); ?>
					<?php endif; ?>
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
