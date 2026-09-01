<?php
/**
 * Header - APIT
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo = get_stylesheet_directory_uri() . '/assets/img/logo-branco.svg';

/**
 * The social row is repeated in the desktop top bar and in the mobile menu.
 */
$apit_social = function () {
	$redes = [
		'instagram' => [ 'Instagram', 'fa-instagram' ],
		'x_twitter' => [ 'X (Twitter)', 'fa-x-twitter' ],
		'facebook'  => [ 'Facebook', 'fa-facebook-f' ],
		'linkedin'  => [ 'LinkedIn', 'fa-linkedin-in' ],
	];

	foreach ( $redes as $chave => $rede ) {
		printf(
			'<a href="%s" aria-label="%s"><i class="fa-brands %s" aria-hidden="true"></i></a>',
			apit_child_social_url( $chave ),
			esc_attr( $rede[0] ),
			esc_attr( $rede[1] )
		);
	}
};
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
			<div class="apit-header__social"><?php $apit_social(); ?></div>
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
				<button class="apit-header__search-toggle" aria-label="<?php esc_attr_e( 'Pesquisar', 'apit' ); ?>">
					<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
				</button>
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
</header>

<?php // Full-screen mobile menu. Hidden until the burger opens it. ?>
<div class="apit-menu-mobile" id="apit-menu-mobile" hidden>
	<div class="apit-menu-mobile__topo">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="apit-menu-mobile__logo">
			<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>">
		</a>

		<div class="apit-menu-mobile__acoes">
			<button class="apit-menu-mobile__pesquisa" aria-label="<?php esc_attr_e( 'Pesquisar', 'apit' ); ?>">
				<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
			</button>
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
		<div class="apit-menu-mobile__social"><?php $apit_social(); ?></div>
	</div>
</div>
