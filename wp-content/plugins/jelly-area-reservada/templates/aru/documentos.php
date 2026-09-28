<?php
/**
 * Os documentos na ARU: os publicados, do mais recente, com a categoria para
 * filtrar (?categoria=<slug>) e o botão para descarregar.
 *
 * @var array $aru    secao, user.
 * @var array $pessoa jelly_ar_aru_pessoa().
 */

defined( 'ABSPATH' ) || exit;

$todos      = jelly_ar_aru_documentos();
$categorias = array_filter( array_unique( wp_list_pluck( $todos, 'categoria_nome', 'categoria' ) ) );
$filtro     = isset( $_GET['categoria'] ) ? sanitize_key( wp_unslash( $_GET['categoria'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filtro     = isset( $categorias[ $filtro ] ) ? $filtro : '';
$lista      = $filtro ? array_values( array_filter( $todos, function ( $d ) use ( $filtro ) {
	return $d['categoria'] === $filtro;
} ) ) : $todos;
$novo_desde = time() - 30 * DAY_IN_SECONDS;
?>

<header class="aru-titulo">
	<h1><?php esc_html_e( 'Documentos', 'jelly-area-reservada' ); ?></h1>
	<p><?php esc_html_e( 'Documentos exclusivos para os associados da APIT.', 'jelly-area-reservada' ); ?></p>
</header>

<section class="aru-cartao">
	<?php if ( count( $categorias ) > 1 ) : ?>
		<nav class="aru-filtros" aria-label="<?php esc_attr_e( 'Filtrar por categoria', 'jelly-area-reservada' ); ?>">
			<a class="aru-filtro<?php echo '' === $filtro ? ' is-atual' : ''; ?>" href="<?php echo esc_url( jelly_ar_area_url( 'documentos' ) ); ?>"<?php echo '' === $filtro ? ' aria-current="page"' : ''; ?>>
				<?php esc_html_e( 'Todos', 'jelly-area-reservada' ); ?> <b><?php echo (int) count( $todos ); ?></b>
			</a>
			<?php foreach ( $categorias as $slug => $nome ) : ?>
				<a class="aru-filtro<?php echo $slug === $filtro ? ' is-atual' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'categoria', $slug, jelly_ar_area_url( 'documentos' ) ) ); ?>"<?php echo $slug === $filtro ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $nome ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( ! $lista ) : ?>
		<div class="aru-vazio">
			<i class="fa-regular fa-folder-open" aria-hidden="true"></i>
			<p><?php esc_html_e( 'Ainda não há documentos publicados.', 'jelly-area-reservada' ); ?></p>
		</div>
	<?php else : ?>
		<ul class="aru-documentos">
			<?php foreach ( $lista as $d ) : ?>
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
</section>
