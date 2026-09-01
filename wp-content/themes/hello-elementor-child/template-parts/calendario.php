<?php
/**
 * Calendário section (home) — Figma nodes 13:19729 / 13:19650 / 16:19769 / 16:19789
 */
$eventos = apit_get_proximos_eventos( 4 );

if ( ! $eventos ) {
	return;
}
?>
<section class="calendario">
	<div class="apit-container">
		<div class="calendario__head">
			<h2 class="calendario__title">Calendário</h2>
			<div class="calendario__nav">
				<button class="calendario__arrow" data-dir="prev" aria-label="<?php esc_attr_e( 'Eventos anteriores', 'apit' ); ?>">
					<i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
				</button>
				<button class="calendario__arrow" data-dir="next" aria-label="<?php esc_attr_e( 'Eventos seguintes', 'apit' ); ?>">
					<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
				</button>
			</div>
		</div>

		<ul class="calendario__track">
			<?php foreach ( $eventos as $evento ) : ?>
				<?php
				$data      = get_post_meta( $evento->ID, 'apit_evento_data', true );
				$categoria = get_post_meta( $evento->ID, 'apit_evento_categoria', true );
				$etiqueta  = get_post_meta( $evento->ID, 'apit_evento_etiqueta', true );
				$extra     = get_post_meta( $evento->ID, 'apit_evento_extra', true );
				$timestamp = $data ? strtotime( $data ) : false;
				?>
				<li class="calendario__item" style="<?php echo esc_attr( apit_cor_categoria_style( $categoria ) ); ?>">
					<?php if ( $categoria ) : ?>
						<span class="pill pill--categoria"><?php echo esc_html( $categoria ); ?></span>
					<?php endif; ?>

					<article class="evento-card">
						<div class="evento-card__body">
							<h3 class="evento-card__title">
								<a href="<?php echo esc_url( get_permalink( $evento ) ); ?>"><?php echo esc_html( get_the_title( $evento ) ); ?></a>
							</h3>

							<?php if ( $evento->post_excerpt ) : ?>
								<p class="evento-card__meta"><?php echo esc_html( $evento->post_excerpt ); ?></p>
							<?php endif; ?>

							<div class="evento-card__pills">
								<?php if ( $etiqueta ) : ?>
									<span class="pill"><?php echo esc_html( $etiqueta ); ?></span>
								<?php endif; ?>
								<?php if ( $extra ) : ?>
									<span class="pill"><?php echo esc_html( $extra ); ?></span>
								<?php endif; ?>
							</div>
						</div>

						<?php if ( $timestamp ) : ?>
							<time class="evento-card__date" datetime="<?php echo esc_attr( $data ); ?>">
								<span class="evento-card__month"><?php echo esc_html( date_i18n( 'F', $timestamp ) ); ?></span>
								<span class="evento-card__day"><?php echo esc_html( date_i18n( 'd', $timestamp ) ); ?></span>
							</time>
						<?php endif; ?>
					</article>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
