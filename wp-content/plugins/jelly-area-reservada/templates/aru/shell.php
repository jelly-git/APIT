<?php
/**
 * A moldura da ARU (inc/aru.php): o documento inteiro, com a barra de topo, o
 * menu à esquerda e a página pedida ao meio. Sem o cabeçalho e o rodapé do
 * site, mas com wp_head() e wp_footer(), para as folhas do tema e o pop-up da
 * marcação.
 *
 * @var array $aru secao, user.
 */

defined( 'ABSPATH' ) || exit;

$pessoa = jelly_ar_aru_pessoa( $aru['user'] );
$menu   = jelly_ar_aru_menu();
$secao  = $aru['secao'];
$logo   = get_stylesheet_directory() . '/assets/img/logo-apit.png';
$logo   = file_exists( $logo ) ? get_stylesheet_directory_uri() . '/assets/img/logo-apit.png' : '';

// Um item do menu: uma ligação, ou "Em breve" enquanto a página não existe.
$item = function ( $chave, $m, $classe ) use ( $secao ) {
	$atual = $chave === $secao;

	if ( '' === $m['url'] ) {
		printf(
			'<span class="%1$s is-em-breve" aria-disabled="true"><i class="fa-solid %2$s" aria-hidden="true"></i><span>%3$s</span><em>%4$s</em></span>',
			esc_attr( $classe ),
			esc_attr( $m['icone'] ),
			esc_html( $m['titulo'] ),
			esc_html__( 'Em breve', 'jelly-area-reservada' )
		);
		return;
	}

	printf(
		'<a class="%1$s%2$s" href="%3$s"%4$s><i class="fa-solid %5$s" aria-hidden="true"></i><span>%6$s</span>%7$s</a>',
		esc_attr( $classe ),
		$atual ? ' is-atual' : '',
		esc_url( $m['url'] ),
		$atual ? ' aria-current="page"' : '',
		esc_attr( $m['icone'] ),
		esc_html( $m['titulo'] ),
		! empty( $m['fora'] ) ? '<i class="fa-solid fa-arrow-up-right-from-square aru-menu__fora" aria-hidden="true"></i>' : ''
	);
};
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="aru">
	<header class="aru-topo">
		<a class="aru-topo__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php esc_attr_e( 'Voltar ao site', 'jelly-area-reservada' ); ?>">
			<?php if ( $logo ) : ?>
				<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="150" height="50">
			<?php else : ?>
				<strong><?php bloginfo( 'name' ); ?></strong>
			<?php endif; ?>
		</a>

		<span class="aru-topo__area"><?php esc_html_e( 'Área Reservada', 'jelly-area-reservada' ); ?></span>

		<div class="aru-topo__direita">
			<a class="aru-topo__site" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i>
				<span><?php esc_html_e( 'Voltar ao site', 'jelly-area-reservada' ); ?></span>
			</a>

			<?php if ( $pessoa['equipa'] ) : ?>
				<a class="aru-topo__site" href="<?php echo esc_url( admin_url( 'admin.php?page=jelly-ar' ) ); ?>">
					<i class="fa-solid fa-gauge" aria-hidden="true"></i>
					<span><?php esc_html_e( 'Back-office', 'jelly-area-reservada' ); ?></span>
				</a>
			<?php endif; ?>

			<div class="aru-pessoa">
				<span class="aru-pessoa__texto">
					<strong><?php echo esc_html( $pessoa['nome'] ); ?></strong>
					<?php if ( $pessoa['empresa'] ) : ?>
						<small><?php echo esc_html( $pessoa['empresa'] ); ?></small>
					<?php endif; ?>
				</span>
				<span class="aru-pessoa__foto" aria-hidden="true"><?php echo esc_html( $pessoa['iniciais'] ); ?></span>
			</div>

			<a class="aru-topo__sair" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
				<i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
				<span><?php esc_html_e( 'Terminar sessão', 'jelly-area-reservada' ); ?></span>
			</a>
		</div>
	</header>

	<aside class="aru-lado">
		<nav class="aru-menu" aria-label="<?php esc_attr_e( 'Área Reservada', 'jelly-area-reservada' ); ?>">
			<?php foreach ( $menu as $chave => $m ) : ?>
				<?php $item( $chave, $m, 'aru-menu__item' ); ?>
			<?php endforeach; ?>
		</nav>

		<p class="aru-lado__frase"><?php esc_html_e( 'Juntos fazemos chegar mais longe o audiovisual português.', 'jelly-area-reservada' ); ?></p>
	</aside>

	<main class="aru-principal" id="conteudo">
		<?php include JELLY_AR_DIR . 'templates/aru/' . $secao . '.php'; ?>
	</main>

	<footer class="aru-rodape">
		<span><?php bloginfo( 'name' ); ?></span>
		<span><?php esc_html_e( 'Área Reservada', 'jelly-area-reservada' ); ?></span>
		<span><?php echo esc_html( current_time( 'Y' ) ); ?></span>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
