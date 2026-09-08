<?php
/**
 * Internacionalização — the list of supporting bodies/programmes.
 *
 * Each card carries its own two-colour gradient chosen by the client per
 * entry, so it cannot live in a static stylesheet the way the rest of the
 * section's colours do. The picked colours are written out as CSS custom
 * properties on the item's style attribute, and the stylesheet reads them
 * to build the gradient — falling back to its own default when a colour
 * was left empty, which is why a missing colour is simply omitted here
 * rather than guessed at.
 *
 * Content comes from the ACF group on the page (Internacionalização › Apoios).
 */
$label  = trim( (string) apit_campo( 'inter_apoios_label' ) );
$linhas = apit_campo( 'inter_apoios' );

$apoios = [];

if ( is_array( $linhas ) ) {
	foreach ( $linhas as $linha ) {
		$titulo = trim( (string) ( $linha['titulo'] ?? '' ) );
		$texto  = trim( (string) ( $linha['texto'] ?? '' ) );

		if ( '' === $titulo && '' === $texto ) {
			continue;
		}

		$apoios[] = [
			'titulo'     => $titulo,
			'texto'      => $texto,
			'cor_inicio' => trim( (string) ( $linha['cor_inicio'] ?? '' ) ),
			'cor_fim'    => trim( (string) ( $linha['cor_fim'] ?? '' ) ),
		];
	}
}

if ( ! $apoios ) {
	return;
}
?>
<section class="inter-apoios">
	<?php if ( $label ) : ?>
		<p class="apit-secao__etiqueta"><?php echo esc_html( $label ); ?></p>
	<?php endif; ?>

	<ul class="inter-apoios__lista">
		<?php foreach ( $apoios as $apoio ) : ?>
			<?php
			$estilo = [];

			if ( $apoio['cor_inicio'] ) {
				$estilo[] = '--apit-apoio-inicio:' . $apoio['cor_inicio'];
			}

			if ( $apoio['cor_fim'] ) {
				$estilo[] = '--apit-apoio-fim:' . $apoio['cor_fim'];
			}
			?>
			<li class="inter-apoios__item"<?php echo $estilo ? ' style="' . esc_attr( implode( ';', $estilo ) ) . '"' : ''; ?>>
				<?php if ( $apoio['titulo'] ) : ?>
					<h3 class="inter-apoios__titulo"><?php echo esc_html( $apoio['titulo'] ); ?></h3>
				<?php endif; ?>

				<?php if ( $apoio['texto'] ) : ?>
					<p class="inter-apoios__texto"><?php echo esc_html( $apoio['texto'] ); ?></p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
