<?php
/**
 * A página de resultados.
 *
 * Sem este ficheiro os resultados saíam pelo `index.php` do tema pai — sem
 * hero, sem cartões, sem nada do desenho do site. Era isso que acontecia, e por
 * isso a pesquisa parecia partida mesmo quando funcionava.
 *
 * Os resultados vêm agrupados por tipo e não misturados por relevância. Com
 * quatro tipos tão diferentes — um PDF, uma notícia, um mercado, uma página —
 * saber primeiro *que género de coisa* se encontrou vale mais do que a ordem:
 * quem procura "anuário" quer o documento, e quer vê-lo junto dos outros
 * documentos.
 *
 * Cada linha diz o mesmo de todas: o que é, como se chama, e o que a distingue
 * das parecidas. Reaproveitar os cartões das Notícias, dos Documentos e do
 * Calendário tinha sido o primeiro plano, mas cada um traz a sua folha de
 * estilo e a sua forma; três formas empilhadas numa lista leem-se pior do que
 * uma repetida.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$termo  = get_search_query();
$grupos = apit_pesquisa_agrupada( $termo );
$total  = 0;

foreach ( $grupos as $g ) {
	$total += count( $g['resultados'] );
}
?>

<section class="apit-pagina-hero hero--pesquisa e-no-lazyload pesquisa-hero">
	<?php echo do_shortcode( '[apit_wordmark texto="PESQ UISA"]' ); ?>

	<div class="pagina-hero__col">
		<h1 class="pagina-hero__titulo pesquisa-hero__titulo">
			<?php
			if ( $termo ) {
				printf(
					/* translators: %s: o que foi procurado */
					esc_html__( 'Resultados para “%s”', 'apit' ),
					esc_html( $termo )
				);
			} else {
				esc_html_e( 'Pesquisar', 'apit' );
			}
			?>
		</h1>

		<p class="pagina-hero__texto pesquisa-hero__conta">
			<?php
			if ( ! $termo ) {
				esc_html_e( 'Escreva o que procura — documentos, notícias, mercados ou páginas do site.', 'apit' );
			} elseif ( $total ) {
				printf(
					esc_html( _n( '%s resultado', '%s resultados', $total, 'apit' ) ),
					esc_html( number_format_i18n( $total ) )
				);
			} else {
				esc_html_e( 'Sem resultados.', 'apit' );
			}
			?>
		</p>

		<form class="pesquisa-hero__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="pesquisa-pagina"><?php esc_html_e( 'Pesquisar no site', 'apit' ); ?></label>
			<input
				type="search"
				id="pesquisa-pagina"
				name="s"
				value="<?php echo esc_attr( $termo ); ?>"
				placeholder="<?php esc_attr_e( 'anuário, conecta, estatutos…', 'apit' ); ?>"
			>
			<button type="submit" class="pesquisa-hero__submit">
				<?php esc_html_e( 'Pesquisar', 'apit' ); ?>
			</button>
		</form>
	</div>
</section>

<section class="apit-secao apit-secao--clara pesquisa-resultados">
	<div class="apit-container">
		<?php if ( $grupos ) : ?>

			<?php foreach ( $grupos as $tipo => $grupo ) : ?>
				<div class="pesquisa-grupo">
					<h2 class="apit-secao__etiqueta pesquisa-grupo__titulo">
						<?php echo esc_html( $grupo['etiqueta'] ); ?>
						<span class="pesquisa-grupo__conta"><?php echo esc_html( count( $grupo['resultados'] ) ); ?></span>
					</h2>

					<ul class="pesquisa-lista">
						<?php foreach ( $grupo['resultados'] as $r ) : ?>
							<li class="pesquisa-item pesquisa-item--<?php echo esc_attr( str_replace( 'apit_', '', $r['tipo'] ) ); ?>">
								<a
									class="pesquisa-item__ligacao"
									href="<?php echo esc_url( $r['url'] ); ?>"
									<?php echo $r['externo'] ? 'target="_blank" rel="noopener"' : ''; ?>
								>
									<span class="pesquisa-item__titulo"><?php echo esc_html( $r['titulo'] ); ?></span>

									<?php if ( $r['contexto'] ) : ?>
										<span class="pesquisa-item__contexto"><?php echo esc_html( $r['contexto'] ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>

		<?php else : ?>

			<?php
			/*
			 * Um beco sem saída é o pior que uma pesquisa faz. Se não há
			 * resultados, ficam as quatro portas do site à vista.
			 */
			$saidas = array(
				'documentos'  => __( 'Documentos', 'apit' ),
				'noticias'    => __( 'Notícias', 'apit' ),
				'calendario'  => __( 'Calendário', 'apit' ),
				'associados'  => __( 'Associados', 'apit' ),
			);
			?>
			<div class="pesquisa-vazio">
				<p class="pesquisa-vazio__texto">
					<?php esc_html_e( 'Talvez esteja numa destas secções:', 'apit' ); ?>
				</p>

				<ul class="pesquisa-vazio__saidas">
					<?php foreach ( $saidas as $slug => $nome ) : ?>
						<?php $pagina = get_page_by_path( $slug ); ?>
						<?php if ( $pagina ) : ?>
							<li>
								<a class="btn btn--outline btn--escuro" href="<?php echo esc_url( get_permalink( $pagina ) ); ?>">
									<?php echo esc_html( $nome ); ?>
									<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
								</a>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>

		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
