<?php
/**
 * Header - APIT
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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
			<div class="apit-header__social">
				<a href="<?php echo apit_child_social_url( 'instagram' ); ?>" aria-label="Instagram"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
				<a href="<?php echo apit_child_social_url( 'x_twitter' ); ?>" aria-label="X (Twitter)"><i class="fa-brands fa-x-twitter" aria-hidden="true"></i></a>
				<a href="<?php echo apit_child_social_url( 'facebook' ); ?>" aria-label="Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
				<a href="<?php echo apit_child_social_url( 'linkedin' ); ?>" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
			</div>
		</div>
	</div>

	<div class="apit-header__main">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="apit-header__logo">
			<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo-branco.svg' ); ?>" alt="<?php bloginfo( 'name' ); ?>">
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
		</div>
	</div>
</header>
