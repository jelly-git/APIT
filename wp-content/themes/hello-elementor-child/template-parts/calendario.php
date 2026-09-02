<?php
/**
 * Calendário section (home) — Figma nodes 13:19729 / 13:19650 / 16:19769 / 16:19789
 *
 * Card anatomy, per the design: the card is a left-to-right gradient in the
 * category colour, with the category pill straddling its top edge, white title
 * and subtitle, a location pill, an optional action button, and a date badge
 * pinned to the bottom-right corner over an offset dark square.
 */
// The carousel scrolls, so it is not limited to what fits in one view.
$eventos = apit_get_proximos_eventos( 12 );

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
				$data       = get_post_meta( $evento->ID, 'apit_evento_data', true );
				$local      = get_post_meta( $evento->ID, 'apit_evento_local', true );
				$acao_texto = get_post_meta( $evento->ID, 'apit_evento_acao_texto', true );
				$acao_url   = get_post_meta( $evento->ID, 'apit_evento_acao_url', true );
				$timestamp  = $data ? strtotime( $data ) : false;
				// Name and both gradient stops come from the category term.
				$cores      = apit_cores_evento( $evento->ID );
				?>
				<li class="calendario__item" style="<?php echo esc_attr( apit_estilo_evento( $cores ) ); ?>">
					<article class="evento-card">
						<?php if ( $cores['nome'] ) : ?>
							<span class="evento-card__categoria"><?php echo esc_html( $cores['nome'] ); ?></span>
						<?php endif; ?>

						<div class="evento-card__body">
							<?php
							/*
							 * Plain text, not a link: there is no single-event page,
							 * so linking the title led to an unstyled dead end. The
							 * only way out of a card is the action button, and that
							 * only appears when it has somewhere to go.
							 */
							?>
							<h3 class="evento-card__title"><?php echo esc_html( get_the_title( $evento ) ); ?></h3>

							<?php if ( $evento->post_excerpt ) : ?>
								<p class="evento-card__meta"><?php echo esc_html( $evento->post_excerpt ); ?></p>
							<?php endif; ?>

							<?php // The button needs both a label and a destination; without a URL there is nowhere to send anyone. ?>
							<?php $mostrar_acao = $acao_texto && $acao_url; ?>

							<?php if ( $local || $mostrar_acao ) : ?>
								<div class="evento-card__actions">
									<?php if ( $local ) : ?>
										<span class="evento-pill evento-pill--local">
											<i class="fa-solid fa-location-dot" aria-hidden="true"></i>
											<?php echo esc_html( $local ); ?>
										</span>
									<?php endif; ?>

									<?php if ( $mostrar_acao ) : ?>
										<a class="evento-pill evento-pill--acao" href="<?php echo esc_url( $acao_url ); ?>">
											<?php echo esc_html( $acao_texto ); ?>
										</a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>

						<?php if ( $timestamp ) : ?>
							<?php // The meta is stored as Ymd, which is not a valid datetime attribute. ?>
							<time class="evento-card__date" datetime="<?php echo esc_attr( $timestamp ? gmdate( 'Y-m-d', $timestamp ) : $data ); ?>">
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
