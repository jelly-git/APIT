<?php
/**
 * Hero decoration — Figma node 9:19573
 *
 * Everything in the hero that has no native Elementor widget equivalent: the
 * blurred colour blobs, the "index" watermark, the Watch Portugal badge and the
 * video panel. The title, subtitle and buttons live as Elementor widgets in the
 * same container, so this layer is absolutely positioned behind them.
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

	<div class="hero__watermark"><span>in</span><span>dex</span></div>

	<?php
	/*
	 * TODO: replace with the white Watch Portugal artwork (Figma node 83:3098).
	 * The only file on disk is the dark footer variant (node 19:20212), which
	 * has a filled background and so cannot be recoloured with a CSS filter —
	 * the Figma download for the hero variant hit the plan's rate limit. Until
	 * then the badge is rebuilt with CSS to match the design.
	 */
	?>
	<div class="hero__watch-badge">
		<span class="hero__watch-pill">Watch</span>
		<span class="hero__watch-pill hero__watch-pill--outline">PORTUGAL</span>
		<small>Independent TV Producers</small>
	</div>

	<div class="hero__video">
		<span class="hero__play"><i class="fa-solid fa-circle-play"></i></span>
	</div>
</div>
