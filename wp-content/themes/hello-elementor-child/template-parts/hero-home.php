<?php
/**
 * Home hero section
 */
$img = get_stylesheet_directory_uri() . '/assets/img/';
?>
<section class="hero">
	<div class="hero__blobs" aria-hidden="true">
		<img class="hero__blob hero__blob--1" src="<?php echo esc_url( $img . 'hero-ellipse-2.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--2" src="<?php echo esc_url( $img . 'hero-ellipse-2.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--3" src="<?php echo esc_url( $img . 'hero-ellipse-5.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--4" src="<?php echo esc_url( $img . 'hero-ellipse-5.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--5" src="<?php echo esc_url( $img . 'hero-ellipse-5.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--6" src="<?php echo esc_url( $img . 'hero-ellipse-1.svg' ); ?>" alt="">
		<img class="hero__blob hero__blob--7" src="<?php echo esc_url( $img . 'hero-ellipse-4.svg' ); ?>" alt="">
	</div>

	<div class="hero__watermark" aria-hidden="true"><span>in</span><span>dex</span></div>

	<div class="apit-container hero__inner">
		<h1 class="hero__title">Associação de Produtores Independentes de Televisão</h1>
		<p class="hero__subtitle">Ligamos ideias, talento e indústria — de Portugal para o mundo, através da Watch Portugal.</p>

		<div class="hero__actions">
			<a href="<?php echo esc_url( get_permalink( get_page_by_path( 'sobre-apit' ) ) ); ?>" class="btn btn--solid">
				Conhecer a apit <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
			</a>
			<a href="<?php echo esc_url( get_permalink( get_page_by_path( 'associados' ) ) ); ?>" class="btn btn--outline">
				Quero ser associado <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
			</a>
		</div>

		<div class="hero__divider"></div>
	</div>

	<div class="hero__watch-badge">
		<span class="hero__watch-pill">Watch</span>
		<span class="hero__watch-pill hero__watch-pill--outline">PORTUGAL</span>
		<small>Independent TV Producers</small>
	</div>

	<div class="hero__video">
		<button class="hero__play" aria-label="<?php esc_attr_e( 'Reproduzir vídeo', 'apit' ); ?>">
			<i class="fa-solid fa-circle-play" aria-hidden="true"></i>
		</button>
	</div>
</section>
