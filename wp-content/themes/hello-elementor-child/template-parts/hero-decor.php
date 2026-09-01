<?php
/**
 * Hero decoration — Figma node 9:19573
 *
 * Everything in the hero that has no native Elementor widget equivalent: the
 * Watch Portugal badge and the video teaser panel. The title, subtitle and
 * buttons live as Elementor widgets in the same container, so this layer sits
 * between the background media and the text.
 */
$img = get_stylesheet_directory_uri() . '/assets/img/';
?>
<div class="hero-decor" aria-hidden="true">
	<div class="hero__watch-badge">
		<img src="<?php echo esc_url( $img . 'logo-watch-portugal-branco.png' ); ?>" alt="Watch Portugal" width="356" height="158">
	</div>

	<div class="hero__video">
		<span class="hero__play"><i class="fa-solid fa-circle-play"></i></span>
	</div>

</div>
