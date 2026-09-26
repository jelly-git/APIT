<?php
/**
 * Calendário section (home) — Figma nodes 13:19729 / 13:19650 / 16:19769 / 16:19789
 *
 * Card anatomy, per the design: the card is a left-to-right gradient in the
 * category colour, with the category pill straddling its top edge, white title
 * and subtitle, a location pill, an optional action button, and a date badge
 * pinned to the bottom-right corner over an offset dark square.
 */
/*
 * One component in three shapes, because the card is identical on the Home, on
 * Internacionalização and on the Calendário page — only the count and whether
 * the row scrolls or wraps changes. The defaults are the Home's, so the bare
 * shortcode there behaves exactly as it did.
 *
 * @var array $args layout, limite, acao, etiqueta
 */
$layout   = 'grelha' === ( $args['layout'] ?? '' ) ? 'grelha' : 'carrossel';
$etiqueta = trim( (string) ( $args['etiqueta'] ?? '' ) );
$acao_pag = trim( (string) ( $args['acao'] ?? '' ) );

// The carousel scrolls, so it is not limited to what fits in one view; the grid
// shows what it is given. Zero or a stray value falls back to the old default
// rather than fetching nothing.
$limite = (int) ( $args['limite'] ?? 0 );

if ( $limite < 1 ) {
	$limite = 12;
} elseif ( 'carrossel' === $layout ) {
	// A carousel asked for 3 still needs more than 3 to have somewhere to
	// scroll to; the visible count is a CSS matter, not a query one.
	$limite = max( $limite, 12 );
}

/*
 * The events come only from the Área Reservada back-office (the
 * jelly-area-reservada plugin): it decides which ones show — published, on
 * the site, not past their last day —, in what order, and hands them over
 * with their dates written out, colours and button. The theme only draws the
 * card. Without the plugin there is no source, and the section stays out.
 */
if ( ! function_exists( 'jelly_ar_eventos_calendario_pagina' ) ) {
	return;
}

/*
 * The grid is the Calendário page: `limite` is how many go on a page, and the
 * rest are on the next ones — with 13 events and a limit of 12, the thirteenth
 * used to be simply missing. The page number rides on `pg`, the parameter the
 * news archive already uses, so the two read the same. The carousel scrolls
 * and has "Calendário completo" for the rest, so it takes the first ones only.
 */
$var_pag = 'pg';
$pagina  = 1;
$paginas = 1;

if ( 'grelha' === $layout ) {
	$pedida  = isset( $_GET[ $var_pag ] ) ? absint( $_GET[ $var_pag ] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a page number, not an action.
	$lote    = jelly_ar_eventos_calendario_pagina( $limite, $pedida );
	$eventos = $lote['eventos'];
	$pagina  = $lote['pagina'];
	$paginas = $lote['paginas'];
} else {
	$eventos = jelly_ar_eventos_calendario( $limite );
}

if ( ! $eventos ) {
	return;
}

/*
 * The carousel is a section inside a page about something else, so it needs a
 * heading to say what it is. The grid is the Calendário page itself, and the
 * page title above it already says "Calendário" — a second one underneath was
 * saying it twice, which the design does not.
 *
 * So the default belongs to the carousel only. A grid that does want a heading
 * still gets one by passing `etiqueta` in the shortcode.
 */
if ( '' === $etiqueta && 'carrossel' === $layout ) {
	$etiqueta = __( 'Calendário', 'apit' );
}

// The arrows belong to a carousel. A grid wraps and has nothing to scroll.
$mostrar_setas = 'carrossel' === $layout;
?>
<section class="calendario calendario--<?php echo esc_attr( $layout ); ?>"<?php echo 'grelha' === $layout ? ' id="calendario"' : ''; ?>>
	<div class="apit-container">
		<?php
		/*
		 * The head row only exists if it has something in it. Without this, the
		 * grid — no heading, no arrows, no button — printed an empty h2 and an
		 * empty flex row, which left a gap above the cards and gave a screen
		 * reader a heading with nothing in it.
		 */
		if ( $etiqueta || $mostrar_setas || $acao_pag ) :
			?>
		<div class="calendario__head">
			<?php if ( $etiqueta ) : ?>
				<h2 class="calendario__title"><?php echo esc_html( $etiqueta ); ?></h2>
			<?php endif; ?>
			<div class="calendario__nav">
				<?php if ( $mostrar_setas ) : ?>
					<button class="calendario__arrow" data-dir="prev" aria-label="<?php esc_attr_e( 'Eventos anteriores', 'apit' ); ?>">
						<i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
					</button>
					<button class="calendario__arrow" data-dir="next" aria-label="<?php esc_attr_e( 'Eventos seguintes', 'apit' ); ?>">
						<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
					</button>
				<?php endif; ?>

				<?php if ( $acao_pag ) : ?>
					<?php // Dark outline: the calendar sits on a light section, where the white one only showed on hover. ?>
					<a class="btn btn--outline btn--escuro calendario__acao" href="<?php echo esc_url( $acao_pag ); ?>">
						<?php esc_html_e( 'Calendário completo', 'apit' ); ?>
						<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php endif; ?>

		<ul class="calendario__track">
			<?php foreach ( $eventos as $evento ) : ?>
				<?php
				// Everything below comes ready from the plugin (jelly_ar_eventos_calendario()).
				$timestamp = $evento['inicio'] ? strtotime( $evento['inicio'] ) : false;
				$acao      = $evento['botao'];
				?>
				<?php // O id é o destino dos resultados de pesquisa: /calendario/#evento-123. ?>
				<li class="calendario__item" id="evento-<?php echo (int) $evento['id']; ?>" style="<?php echo esc_attr( sprintf( '--apit-cat-inicio: %s; --apit-cat-fim: %s;', $evento['cores']['inicio'], $evento['cores']['fim'] ) ); ?>">
					<article class="evento-card">
						<?php if ( $evento['categoria'] ) : ?>
							<span class="evento-card__categoria"><?php echo esc_html( $evento['categoria'] ); ?></span>
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
							<h3 class="evento-card__title"><?php echo esc_html( $evento['titulo'] ); ?></h3>

							<?php // "6–9 out 2026": from the event's start and end, never typed into the summary. ?>
							<?php if ( $evento['datas'] ) : ?>
								<p class="evento-card__meta evento-card__datas"><?php echo esc_html( $evento['datas'] ); ?></p>
							<?php endif; ?>

							<?php if ( $evento['resumo'] ) : ?>
								<p class="evento-card__meta evento-card__resumo"><?php echo esc_html( $evento['resumo'] ); ?></p>
							<?php endif; ?>

							<?php
							/*
							 * The button shows only on events where members can book
							 * a table, and leads to the booking (or to the login
							 * pop-up) — both decided by the plugin.
							 */
							$mostrar_acao = is_array( $acao ) && ! empty( $acao['texto'] ) && ! empty( $acao['url'] );
							?>

							<?php if ( $evento['local'] || $mostrar_acao ) : ?>
								<div class="evento-card__actions">
									<?php if ( $evento['local'] ) : ?>
										<span class="evento-pill evento-pill--local">
											<i class="fa-solid fa-location-dot" aria-hidden="true"></i>
											<?php echo esc_html( $evento['local'] ); ?>
										</span>
									<?php endif; ?>

									<?php if ( $mostrar_acao ) : ?>
										<a class="evento-pill evento-pill--acao" href="<?php echo esc_url( $acao['url'] ); ?>">
											<?php echo esc_html( $acao['texto'] ); ?>
										</a>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>

						<?php // The badge keeps the day the event starts; the full span is in the line under the title. ?>
						<?php if ( $timestamp ) : ?>
							<time class="evento-card__date" datetime="<?php echo esc_attr( gmdate( 'Y-m-d', $timestamp ) ); ?>">
								<span class="evento-card__month"><?php echo esc_html( date_i18n( 'F', $timestamp ) ); ?></span>
								<span class="evento-card__day"><?php echo esc_html( date_i18n( 'd', $timestamp ) ); ?></span>
							</time>
						<?php endif; ?>
					</article>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php if ( $paginas > 1 ) : ?>
			<nav class="calendario-paginacao" aria-label="<?php esc_attr_e( 'Páginas do calendário', 'apit' ); ?>">
				<?php
				/*
				 * The same pagination as the news archive: paginate_links on the
				 * page's own clean address, so no stray query argument is carried
				 * into the links (see template-parts/noticias/resultados.php). The
				 * fragment brings the visitor back to the grid, not the top of the
				 * page.
				 */
				$sem_extras = function () {
					return get_permalink();
				};

				add_filter( 'get_pagenum_link', $sem_extras, 99 );

				echo paginate_links( [ // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links escapes its own output.
					'base'         => add_query_arg( $var_pag, '%#%', get_permalink() ),
					'format'       => '',
					'current'      => $pagina,
					'total'        => $paginas,
					'mid_size'     => 1,
					'prev_text'    => esc_html__( 'Anterior', 'apit' ),
					'next_text'    => esc_html__( 'Seguinte', 'apit' ),
					'add_fragment' => '#calendario',
				] );

				remove_filter( 'get_pagenum_link', $sem_extras, 99 );
				?>
			</nav>
		<?php endif; ?>
	</div>
</section>
