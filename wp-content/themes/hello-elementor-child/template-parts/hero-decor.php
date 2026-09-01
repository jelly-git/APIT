<?php
/**
 * Hero decoration — Figma node 9:19573
 *
 * Everything in the hero that has no native Elementor widget equivalent: the
 * blurred colour blobs and the Watch Portugal badge. The title, subtitle and
 * buttons live as Elementor widgets in the same container, so this layer is
 * absolutely positioned behind them; the media panel is its own widget too.
 */
$img = get_stylesheet_directory_uri() . '/assets/img/';
?>
<div class="hero-decor" aria-hidden="true">
	<div class="hero-decor__blobs">
		<img class="hero__blob hero__blob--1" src="<?php echo esc_url( $img . 'hero-ellipse-2.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--2" src="<?php echo esc_url( $img . 'hero-ellipse-2.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--3" src="<?php echo esc_url( $img . 'hero-ellipse-5.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--4" src="<?php echo esc_url( $img . 'hero-ellipse-5.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--5" src="<?php echo esc_url( $img . 'hero-ellipse-5.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--6" src="<?php echo esc_url( $img . 'hero-ellipse-1.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--7" src="<?php echo esc_url( $img . 'hero-ellipse-4.svg' ); ?>" alt="">
	</div>

	<div class="hero__watch-badge">
		<img src="<?php echo esc_url( $img . 'logo-watch-portugal-branco.png' ); ?>" alt="Watch Portugal" width="356" height="158">
	</div>

	<div class="hero__video">
		<span class="hero__play"><i class="fa-solid fa-circle-play"></i></span>
	</div>

</div>
