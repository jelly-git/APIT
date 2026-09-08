<?php
/**
 * Hero background — the video, image or image slider behind the whole hero, in
 * place of the CSS gradient.
 *
 * The gradient stays painted on .apit-hero underneath, so it is what shows
 * while the video buffers, or if nothing here is filled in at all.
 *
 * Content comes from the ACF group on the page (Hero — fundo), except when the
 * shortcode is given attributes, which win. That override is what lets the
 * videos stay in the theme's assets folder until they are uploaded to the media
 * library, with nothing broken in between.
 *
 * Two galleries can be set — one landscape, one portrait for phones — and they
 * need not hold the same number of images, so they cannot be paired into one
 * <picture> per slide. Only the first slide of each is a <picture>, which the
 * browser resolves by media query and downloads once. Every other slide carries
 * its markup in a data attribute and is built by assets/js/hero-slider.js for
 * whichever breakpoint is actually in use — otherwise a phone would download
 * the desktop set too, at hero size.
 *
 * @var array $args video, imagem, autoplay, loop, controls
 */

$autoplay = filter_var( $args['autoplay'] ?? 'yes', FILTER_VALIDATE_BOOLEAN );
$loop     = filter_var( $args['loop'] ?? 'yes', FILTER_VALIDATE_BOOLEAN );
$controls = filter_var( $args['controls'] ?? 'no', FILTER_VALIDATE_BOOLEAN );

// The attribute is a filename or path; the field is an attachment ID. Both go
// through apit_media_url(), which resolves either.
$video_url = apit_media_url( $args['video'] ?? '', 'videos' );

if ( ! $video_url ) {
	$video_url = apit_media_url( (string) apit_campo( 'hero_fundo_video' ), 'videos' );
}

/*
 * The image attribute predates the fields and only ever held one file, so it
 * arrives as a one-image gallery.
 */
$imagens = [];

if ( ! empty( $args['imagem'] ) ) {
	$imagens = [ $args['imagem'] ];
} else {
	$imagens = array_values( array_filter( (array) apit_campo( 'hero_fundo_imagens' ) ) );
}

$imagens_mobile = array_values( array_filter( (array) apit_campo( 'hero_fundo_imagens_mobile' ) ) );

// Either gallery stands in for the other when only one is filled, so a section
// never ends up with no image at one breakpoint and images at the other.
if ( ! $imagens_mobile ) {
	$imagens_mobile = $imagens;
} elseif ( ! $imagens ) {
	$imagens = $imagens_mobile;
}

if ( ! $video_url && ! $imagens && ! $imagens_mobile ) {
	return;
}

$intervalo = apit_hero_intervalo( apit_campo( 'hero_fundo_intervalo' ) );

/*
 * The video's poster, and what shows under it on a phone. The first landscape
 * image serves as the poster because a video and a still are never both wanted
 * at the same size.
 */
$poster_url = $imagens ? apit_hero_imagem_url( $imagens[0] ) : '';
?>
<div class="apit-hero__media" <?php echo $controls ? '' : 'aria-hidden="true"'; ?>>
	<?php
	/*
	 * The images come before the video on purpose. A wide screen then has the
	 * video painted over them with no z-index of its own, and a phone — where
	 * the video is hidden — is left with exactly these.
	 *
	 * They are rendered whether or not there is a video, for that reason.
	 */
	if ( $imagens ) :
		$restantes = [
			'desktop' => array_filter( array_map( 'apit_hero_imagem_html', array_slice( $imagens, 1 ) ) ),
			'mobile'  => array_filter( array_map( 'apit_hero_imagem_html', array_slice( $imagens_mobile, 1 ) ) ),
		];

		// Only the sets with something to add need to reach the script, and an
		// empty data-restantes is what tells it there is no slider to build.
		$restantes = array_filter( $restantes );
		?>
		<div
			class="apit-hero__slider"
			data-intervalo="<?php echo esc_attr( $intervalo ); ?>"
			<?php if ( $restantes ) : ?>
				data-restantes="<?php echo esc_attr( wp_json_encode( $restantes ) ); ?>"
			<?php endif; ?>
		>
			<div class="apit-hero__slide is-ativo">
				<?php apit_hero_primeira_imagem( $imagens[0] ?? 0, $imagens_mobile[0] ?? 0 ); ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $video_url ) : ?>
		<?php // Autoplay only works muted, so it is set whenever autoplay is on. ?>
		<video
			class="apit-hero__video"
			<?php if ( $poster_url ) : ?>poster="<?php echo esc_url( $poster_url ); ?>"<?php endif; ?>
			<?php echo $autoplay ? 'autoplay muted playsinline' : ''; ?>
			<?php echo $loop ? 'loop' : ''; ?>
			<?php echo $controls ? 'controls' : ''; ?>
			preload="auto"
		>
			<source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
		</video>
	<?php endif; ?>
</div>
