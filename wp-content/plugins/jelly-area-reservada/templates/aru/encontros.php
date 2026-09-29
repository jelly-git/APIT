<?php
/**
 * Os encontros na ARU: os publicados, do mais recente, em cartões com a capa
 * (a imagem, ou a do primeiro vídeo), a data, o título, o resumo e o número
 * de vídeos; cada um leva à sua página (templates/aru/encontro.php). Com a
 * procura (?q=) e, com mais de 10, a paginação (templates/aru/parte-paginacao.php).
 *
 * @var array $aru    secao, user.
 * @var array $pessoa jelly_ar_aru_pessoa().
 */

defined( 'ABSPATH' ) || exit;

$todos = jelly_ar_encontros_publicados();

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$procura    = isset( $_GET['q'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_GET['q'] ) ), 0, 100 ) : '';
$por_pagina = isset( $_GET['por_pagina'] ) ? absint( $_GET['por_pagina'] ) : 10;
$pagina     = isset( $_GET['pagina'] ) ? max( 1, absint( $_GET['pagina'] ) ) : 1;
// phpcs:enable

$por_pagina = in_array( $por_pagina, [ 10, 20, 50 ], true ) ? $por_pagina : 10;

// A procura: no título, no resumo e nos títulos dos vídeos, sem olhar a maiúsculas nem acentos.
$sem_acentos = function ( $t ) {
	return mb_strtolower( remove_accents( (string) $t ) );
};
$termo = $sem_acentos( $procura );
$lista = array_values( array_filter( $todos, function ( $e ) use ( $termo, $sem_acentos ) {
	return '' === $termo || false !== strpos( $sem_acentos( $e['titulo'] . ' ' . $e['resumo'] . ' ' . implode( ' ', wp_list_pluck( $e['videos'], 'titulo' ) ) ), $termo );
} ) );

$total    = count( $lista );
$paginas  = max( 1, (int) ceil( $total / $por_pagina ) );
$pagina   = min( $pagina, $paginas );
$primeiro = ( $pagina - 1 ) * $por_pagina;
$mostrar  = array_slice( $lista, $primeiro, $por_pagina );

$estado = [ 'q' => $procura, 'por_pagina' => 10 === $por_pagina ? '' : $por_pagina ];
$url    = function ( $args ) use ( $estado ) {
	$args = array_merge( $estado, $args );
	if ( isset( $args['pagina'] ) && 1 === (int) $args['pagina'] ) {
		unset( $args['pagina'] );
	}
	return add_query_arg( array_filter( $args, 'strlen' ), jelly_ar_area_url( 'encontros' ) );
};
?>

<header class="aru-titulo">
	<h1><?php esc_html_e( 'Encontros', 'jelly-area-reservada' ); ?></h1>
	<p><?php esc_html_e( 'Os encontros da APIT com os associados, e os vídeos de cada um.', 'jelly-area-reservada' ); ?></p>
</header>

<section class="aru-cartao">
	<?php if ( count( $todos ) > 1 ) : ?>
		<div class="aru-barra">
			<p class="aru-barra__conta">
				<?php
				if ( '' !== $procura ) {
					// Com a procura, quantos dos encontros ela deixou.
					/* translators: 1: encontrados, 2: total de encontros */
					echo esc_html( sprintf( _n( '%1$d de %2$d encontro', '%1$d de %2$d encontros', count( $todos ), 'jelly-area-reservada' ), $total, count( $todos ) ) );
				} else {
					/* translators: %d: número de encontros */
					echo esc_html( sprintf( _n( '%d encontro', '%d encontros', count( $todos ), 'jelly-area-reservada' ), count( $todos ) ) );
				}
				?>
			</p>
			<form class="aru-procura" method="get" action="<?php echo esc_url( jelly_ar_area_url( 'encontros' ) ); ?>" role="search">
				<?php if ( 10 !== $por_pagina ) : ?>
					<input type="hidden" name="por_pagina" value="<?php echo (int) $por_pagina; ?>">
				<?php endif; ?>
				<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
				<label class="screen-reader-text" for="aru-procura"><?php esc_html_e( 'Procurar encontros', 'jelly-area-reservada' ); ?></label>
				<input type="search" id="aru-procura" name="q" value="<?php echo esc_attr( $procura ); ?>" placeholder="<?php esc_attr_e( 'Procurar pelo título ou pelo vídeo', 'jelly-area-reservada' ); ?>">
			</form>
		</div>
	<?php endif; ?>

	<?php if ( ! $mostrar ) : ?>
		<div class="aru-vazio">
			<i class="fa-regular fa-circle-play" aria-hidden="true"></i>
			<p>
				<?php
				if ( '' !== $procura ) {
					/* translators: %s: o que se procurou */
					printf( esc_html__( 'Nenhum encontro corresponde a "%s".', 'jelly-area-reservada' ), esc_html( $procura ) );
				} else {
					esc_html_e( 'Ainda não há encontros publicados. Quando houver, aparecem aqui, com os vídeos.', 'jelly-area-reservada' );
				}
				?>
			</p>
		</div>
	<?php else : ?>
		<ul class="aru-cartoes-encontros">
			<?php foreach ( $mostrar as $e ) : ?>
				<?php $n = count( $e['videos'] ); ?>
				<li>
					<a class="aru-cartao-encontro" href="<?php echo esc_url( jelly_ar_aru_encontro_url( $e ) ); ?>">
						<span class="aru-cartao-encontro__capa<?php echo $e['capa'] ? '' : ' is-vazia'; ?>">
							<?php if ( $e['capa'] ) : ?>
								<img src="<?php echo esc_url( $e['capa'] ); ?>" alt="" loading="lazy">
							<?php endif; ?>
							<?php if ( $n ) : ?>
								<span class="aru-cartao-encontro__play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
								<?php /* translators: %d: número de vídeos */ ?>
								<span class="aru-cartao-encontro__videos"><i class="fa-solid fa-film" aria-hidden="true"></i> <?php echo esc_html( sprintf( _n( '%d vídeo', '%d vídeos', $n, 'jelly-area-reservada' ), $n ) ); ?></span>
							<?php endif; ?>
						</span>
						<span class="aru-cartao-encontro__corpo">
							<small><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?php echo esc_html( $e['data_texto'] ); ?></small>
							<strong><?php echo esc_html( $e['titulo'] ); ?></strong>
							<?php if ( $e['resumo'] ) : ?>
								<span class="aru-cartao-encontro__resumo"><?php echo esc_html( $e['resumo'] ); ?></span>
							<?php endif; ?>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php // Só com mais de 10 (o menor "Por página"): com menos, cabem todos numa página. ?>
	<?php if ( $total > 10 ) : ?>
		<?php
		$paginacao = [
			'total'      => $total,
			'pagina'     => $pagina,
			'paginas'    => $paginas,
			'por_pagina' => $por_pagina,
			'primeiro'   => $primeiro,
			'mostrados'  => count( $mostrar ),
			'url'        => $url,
			'escondidos' => [ 'q' => $procura ],
		];
		include JELLY_AR_DIR . 'templates/aru/parte-paginacao.php';
		?>
	<?php endif; ?>
</section>
