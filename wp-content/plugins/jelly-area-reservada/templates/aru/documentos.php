<?php
/**
 * Os documentos na ARU: os publicados, do mais recente, com a procura (?q=),
 * a categoria para filtrar (?categoria=<slug>), a paginação (?pagina=,
 * ?por_pagina=; templates/aru/parte-paginacao.php) e o botão para
 * descarregar.
 *
 * @var array $aru    secao, user.
 * @var array $pessoa jelly_ar_aru_pessoa().
 */

defined( 'ABSPATH' ) || exit;

$todos      = jelly_ar_aru_documentos();
$categorias = array_filter( array_unique( wp_list_pluck( $todos, 'categoria_nome', 'categoria' ) ) );

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$filtro     = isset( $_GET['categoria'] ) ? sanitize_key( wp_unslash( $_GET['categoria'] ) ) : '';
$procura    = isset( $_GET['q'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_GET['q'] ) ), 0, 100 ) : '';
$por_pagina = isset( $_GET['por_pagina'] ) ? absint( $_GET['por_pagina'] ) : 10;
$pagina     = isset( $_GET['pagina'] ) ? max( 1, absint( $_GET['pagina'] ) ) : 1;
// phpcs:enable

$filtro     = isset( $categorias[ $filtro ] ) ? $filtro : '';
$por_pagina = in_array( $por_pagina, [ 10, 20, 50 ], true ) ? $por_pagina : 10;

// A categoria e a procura: no título, na descrição e no nome do ficheiro, sem olhar a maiúsculas nem acentos.
$sem_acentos = function ( $t ) {
	return mb_strtolower( remove_accents( (string) $t ) );
};
$termo = $sem_acentos( $procura );
$lista = array_values( array_filter( $todos, function ( $d ) use ( $filtro, $termo, $sem_acentos ) {
	if ( $filtro && $d['categoria'] !== $filtro ) {
		return false;
	}

	return '' === $termo || false !== strpos( $sem_acentos( $d['titulo'] . ' ' . $d['descricao'] . ' ' . $d['ficheiro'] ), $termo );
} ) );

// A página pedida, dentro das que há.
$total    = count( $lista );
$paginas  = max( 1, (int) ceil( $total / $por_pagina ) );
$pagina   = min( $pagina, $paginas );
$primeiro = ( $pagina - 1 ) * $por_pagina;
$mostrar  = array_slice( $lista, $primeiro, $por_pagina );

// O endereço com o que está escolhido, mais o que muda.
$estado = [ 'categoria' => $filtro, 'q' => $procura, 'por_pagina' => 10 === $por_pagina ? '' : $por_pagina ];
$url    = function ( $args ) use ( $estado ) {
	$args = array_merge( $estado, $args );
	if ( isset( $args['pagina'] ) && 1 === (int) $args['pagina'] ) {
		unset( $args['pagina'] );
	}
	return add_query_arg( array_filter( $args, 'strlen' ), jelly_ar_area_url( 'documentos' ) );
};

$novo_desde = time() - 30 * DAY_IN_SECONDS;
?>

<header class="aru-titulo">
	<h1><?php esc_html_e( 'Documentos', 'jelly-area-reservada' ); ?></h1>
	<p><?php esc_html_e( 'Documentos exclusivos para os associados da APIT.', 'jelly-area-reservada' ); ?></p>
</header>

<section class="aru-cartao">
	<div class="aru-barra">
		<?php if ( count( $categorias ) > 1 ) : ?>
			<nav class="aru-filtros" aria-label="<?php esc_attr_e( 'Filtrar por categoria', 'jelly-area-reservada' ); ?>">
				<a class="aru-filtro<?php echo '' === $filtro ? ' is-atual' : ''; ?>" href="<?php echo esc_url( $url( [ 'categoria' => '' ] ) ); ?>"<?php echo '' === $filtro ? ' aria-current="page"' : ''; ?>>
					<?php esc_html_e( 'Todos', 'jelly-area-reservada' ); ?> <b><?php echo (int) count( $todos ); ?></b>
				</a>
				<?php foreach ( $categorias as $slug => $nome ) : ?>
					<a class="aru-filtro<?php echo $slug === $filtro ? ' is-atual' : ''; ?>" href="<?php echo esc_url( $url( [ 'categoria' => $slug ] ) ); ?>"<?php echo $slug === $filtro ? ' aria-current="page"' : ''; ?>>
						<?php echo esc_html( $nome ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php // A procura: mantém a categoria e o "Por página", e volta à primeira página. ?>
		<form class="aru-procura" method="get" action="<?php echo esc_url( jelly_ar_area_url( 'documentos' ) ); ?>" role="search">
			<?php if ( $filtro ) : ?>
				<input type="hidden" name="categoria" value="<?php echo esc_attr( $filtro ); ?>">
			<?php endif; ?>
			<?php if ( 10 !== $por_pagina ) : ?>
				<input type="hidden" name="por_pagina" value="<?php echo (int) $por_pagina; ?>">
			<?php endif; ?>
			<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
			<label class="screen-reader-text" for="aru-procura"><?php esc_html_e( 'Procurar documentos', 'jelly-area-reservada' ); ?></label>
			<input type="search" id="aru-procura" name="q" value="<?php echo esc_attr( $procura ); ?>" placeholder="<?php esc_attr_e( 'Procurar pelo título, descrição ou ficheiro', 'jelly-area-reservada' ); ?>">
		</form>
	</div>

	<?php if ( ! $mostrar ) : ?>
		<div class="aru-vazio">
			<i class="fa-regular fa-folder-open" aria-hidden="true"></i>
			<p>
				<?php
				if ( '' !== $procura ) {
					/* translators: %s: o que se procurou */
					printf( esc_html__( 'Nenhum documento corresponde a "%s".', 'jelly-area-reservada' ), esc_html( $procura ) );
				} elseif ( $filtro ) {
					esc_html_e( 'Não há documentos nesta categoria.', 'jelly-area-reservada' );
				} else {
					esc_html_e( 'Ainda não há documentos publicados.', 'jelly-area-reservada' );
				}
				?>
			</p>
		</div>
	<?php else : ?>
		<ul class="aru-documentos">
			<?php foreach ( $mostrar as $d ) : ?>
				<li class="aru-documento">
					<span class="aru-documento__icone aru-tipo--<?php echo esc_attr( $d['tipo'] ); ?>"><i class="fa-solid <?php echo esc_attr( jelly_ar_aru_icone( $d['tipo'] ) ); ?>" aria-hidden="true"></i></span>
					<span class="aru-documento__texto">
						<strong>
							<?php echo esc_html( $d['titulo'] ); ?>
							<?php if ( $d['ts'] >= $novo_desde ) : ?>
								<em class="aru-novo"><?php esc_html_e( 'Novo', 'jelly-area-reservada' ); ?></em>
							<?php endif; ?>
						</strong>
						<?php if ( $d['descricao'] ) : ?>
							<span><?php echo esc_html( $d['descricao'] ); ?></span>
						<?php endif; ?>
						<small><?php echo esc_html( implode( ' · ', array_filter( [ $d['categoria_nome'], strtoupper( $d['tipo'] ) . ' ' . size_format( $d['tamanho'], 1 ), $d['ts'] ? jelly_ar_data( 'j M Y', $d['ts'] ) : '' ] ) ) ); ?></small>
					</span>
					<?php /* translators: %s: título do documento */ ?>
					<a class="aru-botao aru-botao--pequeno" href="<?php echo esc_url( jelly_ar_url_descarregar( $d['id'] ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Descarregar %s', 'jelly-area-reservada' ), $d['titulo'] ) ); ?>">
						<i class="fa-solid fa-download" aria-hidden="true"></i> <span><?php esc_html_e( 'Descarregar', 'jelly-area-reservada' ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $total ) : ?>
		<?php
		$paginacao = [
			'total'      => $total,
			'pagina'     => $pagina,
			'paginas'    => $paginas,
			'por_pagina' => $por_pagina,
			'primeiro'   => $primeiro,
			'mostrados'  => count( $mostrar ),
			'url'        => $url,
			'escondidos' => [ 'categoria' => $filtro, 'q' => $procura ],
		];
		include JELLY_AR_DIR . 'templates/aru/parte-paginacao.php';
		?>
	<?php endif; ?>
</section>
