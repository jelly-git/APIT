<?php
/**
 * Hero background — the video (or a still image) behind the whole hero, in
 * place of the CSS gradient.
 *
 * The gradient stays painted on .apit-hero underneath, so it is what shows
 * while the video buffers or if it fails to load.
 *
 * @var array $args video, imagem, autoplay, loop, controls
 */
$video    = $args['video'] ?? '';
$imagem   = $args['imagem'] ?? '';
$autoplay = filter_var( $args['autoplay'] ?? 'yes', FILTER_VALIDATE_BOOLEAN );
$loop     = filter_var( $args['loop'] ?? 'yes', FILTER_VALIDATE_BOOLEAN );
$controls = filter_var( $args['controls'] ?? 'no', FILTER_VALIDATE_BOOLEAN );

$video_url  = apit_media_url( $video, 'videos' );
$imagem_url = apit_media_url( $imagem, 'img' );

if ( ! $video_url && ! $imagem_url ) {
	return;
}
?>
<div class="apit-hero__media" <?php echo $controls ? '' : 'aria-hidden="true"'; ?>>
	<?php if ( $video_url ) : ?>
		<?php // Autoplay only works muted, so it is set whenever autoplay is on. ?>
		<video
			class="apit-hero__video"
			<?php if ( $imagem_url ) : ?>poster="<?php echo esc_url( $imagem_url ); ?>"<?php endif; ?>
			<?php echo $autoplay ? 'autoplay muted playsinline' : ''; ?>
			<?php echo $loop ? 'loop' : ''; ?>
			<?php echo $controls ? 'controls' : ''; ?>
			preload="auto"
		>
			<source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
		</video>
	<?php else : ?>
		<img class="apit-hero__poster" src="<?php echo esc_url( $imagem_url ); ?>" alt="">
	<?php endif; ?>
</div>
