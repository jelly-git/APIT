<?php
/**
 * Header - APIT
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo = get_stylesheet_directory_uri() . '/assets/img/logo-branco.svg';

// The social row appears twice below — in the desktop top bar and in the mobile
// menu — and comes from apit_redes_sociais_html(), in functions.php.
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'apit' ); ?></a>

<header class="apit-header">
	<div class="apit-header__topbar">
		<nav class="apit-header__topbar-nav" aria-label="<?php esc_attr_e( 'Links secundários', 'apit' ); ?>">
			<?php
			wp_nav_menu( [
				'theme_location' => 'top-bar',
				'container'      => false,
				'menu_class'     => 'apit-header__topbar-menu',
				'fallback_cb'    => false,
			] );
			?>
		</nav>

		<div class="apit-header__topbar-right">
			<div class="apit-header__lang">
				<a href="#" class="is-active">PT</a>
				<a href="#">EN</a>
			</div>
			<div class="apit-header__social"><?php apit_redes_sociais_html(); ?></div>
		</div>
	</div>

	<div class="apit-header__main">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="apit-header__logo">
			<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>">
		</a>

		<div class="apit-header__main-right">
			<nav class="apit-header__nav" aria-label="<?php esc_attr_e( 'Menu principal', 'apit' ); ?>">
				<?php
				wp_nav_menu( [
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'apit-header__menu',
					'fallback_cb'    => false,
				] );
				?>
				<?php // The glass comes from assets/img/icon-lupa.svg, drawn by CSS. ?>
				<button
					class="apit-header__search-toggle"
					aria-label="<?php esc_attr_e( 'Pesquisar', 'apit' ); ?>"
					aria-expanded="false"
					aria-controls="apit-pesquisa"
				></button>
			</nav>

			<a href="#" class="apit-header__cta">
				<?php esc_html_e( 'Área Reservada', 'apit' ); ?>
				<i class="fa-solid fa-user" aria-hidden="true"></i>
			</a>

			<?php // Shown below the desktop breakpoint; opens the panel further down. ?>
			<button
				class="apit-header__burger"
				aria-label="<?php esc_attr_e( 'Abrir menu', 'apit' ); ?>"
				aria-expanded="false"
				aria-controls="apit-menu-mobile"
			>
				<i class="fa-solid fa-bars" aria-hidden="true"></i>
			</button>
		</div>
	</div>

	<?php
	/*
	 * A faixa da pesquisa, dentro do cabeçalho e por baixo dele. Nasce com
	 * `hidden`, que a tira também da árvore de acessibilidade; o JavaScript
	 * abre-a. Sem JavaScript não aparece — e não faz falta, porque o formulário
	 * dela levaria à mesma página de resultados a que a lupa leva de qualquer
	 * maneira.
	 */
	?>
	<div class="apit-pesquisa" id="apit-pesquisa" hidden>
		<form class="apit-pesquisa__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="apit-pesquisa-campo"><?php esc_attr_e( 'Pesquisar no site', 'apit' ); ?></label>
			<input
				type="search"
				id="apit-pesquisa-campo"
				class="apit-pesquisa__campo"
				name="s"
				autocomplete="off"
				placeholder="<?php esc_attr_e( 'anuário, conecta, estatutos…', 'apit' ); ?>"
			>
			<button type="button" class="apit-pesquisa__fechar" aria-label="<?php esc_attr_e( 'Fechar pesquisa', 'apit' ); ?>">
				<i class="fa-solid fa-xmark" aria-hidden="true"></i>
			</button>
		</form>

		<?php // O JavaScript escreve aqui. `aria-live` faz o leitor de ecrã anunciar a contagem. ?>
		<div class="apit-pesquisa__resultados" aria-live="polite"></div>
	</div>
</header>

<?php // Full-screen mobile menu. Hidden until the burger opens it. ?>
<div class="apit-menu-mobile" id="apit-menu-mobile" hidden>
	<div class="apit-menu-mobile__topo">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="apit-menu-mobile__logo">
			<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>">
		</a>

		<div class="apit-menu-mobile__acoes">
			<button class="apit-menu-mobile__pesquisa" aria-label="<?php esc_attr_e( 'Pesquisar', 'apit' ); ?>"></button>
			<button class="apit-menu-mobile__fechar" aria-label="<?php esc_attr_e( 'Fechar menu', 'apit' ); ?>">
				<i class="fa-solid fa-xmark" aria-hidden="true"></i>
			</button>
		</div>
	</div>

	<nav class="apit-menu-mobile__principal" aria-label="<?php esc_attr_e( 'Menu principal', 'apit' ); ?>">
		<?php
		wp_nav_menu( [
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'apit-menu-mobile__lista',
			'fallback_cb'    => false,
		] );
		?>
	</nav>

	<a href="#" class="apit-menu-mobile__cta">
		<?php esc_html_e( 'Área Reservada', 'apit' ); ?>
		<i class="fa-solid fa-user" aria-hidden="true"></i>
	</a>

	<nav class="apit-menu-mobile__secundario" aria-label="<?php esc_attr_e( 'Links secundários', 'apit' ); ?>">
		<?php
		wp_nav_menu( [
			'theme_location' => 'top-bar',
			'container'      => false,
			'menu_class'     => 'apit-menu-mobile__lista-secundaria',
			'fallback_cb'    => false,
		] );
		?>
	</nav>

	<div class="apit-menu-mobile__rodape">
		<div class="apit-menu-mobile__lang">
			<a href="#" class="is-active">PT</a>
			<a href="#">EN</a>
		</div>
		<div class="apit-menu-mobile__social"><?php apit_redes_sociais_html(); ?></div>
	</div>
</div>
