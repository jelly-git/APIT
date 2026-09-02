<?php
/**
 * Equipa APIT — the row of portraits.
 *
 * The portraits are round crops on a gradient. Where the uploaded file is a
 * cut-out the circle behind it supplies the gradient; where the gradient is
 * already baked into the image the circle simply never shows.
 */
$membros = apit_get_equipa();

if ( ! $membros ) {
	return;
}
?>
<section class="sobre-equipa">
	<div class="apit-container">
		<p class="sobre-rotulo sobre-rotulo--centro"><?php esc_html_e( 'Equipa APIT', 'apit' ); ?></p>

		<ul class="sobre-equipa__lista">
			<?php foreach ( $membros as $indice => $membro ) : ?>
				<li class="sobre-equipa__item">
					<span class="sobre-equipa__retrato sobre-equipa__retrato--<?php echo (int) ( $indice % 4 ) + 1; ?>">
						<?php if ( has_post_thumbnail( $membro ) ) : ?>
							<?php echo get_the_post_thumbnail( $membro, 'medium_large', [ 'alt' => esc_attr( get_the_title( $membro ) ) ] ); ?>
						<?php endif; ?>
					</span>
					<h3 class="sobre-equipa__nome"><?php echo esc_html( get_the_title( $membro ) ); ?></h3>
					<?php $cargo = get_post_meta( $membro->ID, 'apit_equipa_cargo', true ); ?>
					<?php if ( $cargo ) : ?>
						<p class="sobre-equipa__cargo"><?php echo esc_html( $cargo ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
