<?php
/**
 * Internacionalização — the full-bleed gradient band carrying the Watch
 * Portugal lockup.
 *
 * @var array $args titulo, texto, botao, url
 */
$titulo = trim( (string) ( $args['titulo'] ?? '' ) );
$texto  = trim( (string) ( $args['texto'] ?? '' ) );
$botao  = trim( (string) ( $args['botao'] ?? '' ) );
$url    = trim( (string) ( $args['url'] ?? '' ) );
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
				src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo-watch-portugal-branco.png' ); ?>"
				alt="Watch Portugal — Independent TV Producers"
				width="356"
				height="158"
			>
		</div>
	</div>
</section>
