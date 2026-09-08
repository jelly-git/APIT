<?php
/**
 * Internacionalização — the stand invitation, paired with the numbered
 * steps for joining it.
 *
 * The two columns are read together here rather than split into separate
 * template parts because the steps only make sense next to the pitch they
 * belong to, and both draw on the same ACF group.
 *
 * Content comes from the ACF group on the page (Internacionalização › Stand).
 */
$titulo         = trim( (string) apit_campo( 'inter_stand_titulo' ) );
$texto          = trim( (string) apit_campo( 'inter_stand_texto' ) );
$botao          = trim( (string) apit_campo( 'inter_stand_botao' ) );
$url            = trim( (string) apit_campo( 'inter_stand_url' ) );
$passos_titulo  = trim( (string) apit_campo( 'inter_stand_passos_titulo' ) );
$linhas_passos  = apit_campo( 'inter_stand_passos' );

$passos = [];

if ( is_array( $linhas_passos ) ) {
	foreach ( $linhas_passos as $linha ) {
		$passo_texto = trim( (string) ( $linha['texto'] ?? '' ) );

		if ( '' !== $passo_texto ) {
			$passos[] = $passo_texto;
		}
	}
}

if ( ! $titulo && ! $texto && ! $passos ) {
	return;
}

// The button needs both a label and a destination; without a URL there is nowhere to send anyone.
$mostrar_acao = $botao && $url;
?>
<section class="inter-stand">
	<div class="inter-stand__col inter-stand__col--texto">
		<?php if ( $titulo ) : ?>
			<h2 class="inter-stand__titulo"><?php echo esc_html( $titulo ); ?></h2>
		<?php endif; ?>

		<?php if ( $texto ) : ?>
			<p class="inter-stand__texto"><?php echo nl2br( esc_html( $texto ) ); ?></p>
		<?php endif; ?>

		<?php if ( $mostrar_acao ) : ?>
			<a class="btn btn--outline btn--claro" href="<?php echo esc_url( $url ); ?>">
				<?php echo esc_html( $botao ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( $passos ) : ?>
		<div class="inter-stand__col inter-stand__col--passos">
			<?php if ( $passos_titulo ) : ?>
				<h3 class="inter-stand__passos-titulo"><?php echo esc_html( $passos_titulo ); ?></h3>
			<?php endif; ?>

			<ol class="inter-stand__passos">
				<?php foreach ( $passos as $passo ) : ?>
					<li class="inter-stand__passo"><?php echo esc_html( $passo ); ?></li>
				<?php endforeach; ?>
			</ol>
		</div>
	<?php endif; ?>
</section>
