<?php
/**
 * Hero decoration — Figma node 9:19573
 *
 * Everything in the hero that has no native Elementor widget equivalent: the
 * Watch Portugal badge and the video teaser panel. The title, subtitle and
 * buttons live as Elementor widgets in the same container, so this layer sits
 * between the background media and the text.
 *
 * The panel plays the video set in "Home — vídeo do destaque"
 * (apit_video_destaque()). Without one it is what it always was: a dark shape
 * with a play glyph, hidden from screen readers because it does nothing.
 *
 * With one it is a link to the video, so it works with no JavaScript at all —
 * the file or the YouTube page opens in a new tab. assets/js/video-destaque.js
 * turns the click into the dialog below instead, and the video plays over the
 * page. The dialog is in the markup rather than built by the script so its
 * labels go through the same translation functions as everything else.
 *
 * @var array $args video — null, or the array apit_video_destaque() returns
 */
$img   = get_stylesheet_directory_uri() . '/assets/img/';
$video = $args['video'] ?? null;

// A link the site cannot embed still opens, just not in the dialog.
$no_dialogo = $video && '' !== $video['src'];
?>
<div class="hero-decor">
	<div class="hero__watch-badge" aria-hidden="true">
		<img src="<?php echo esc_url( $img . 'logo-watch-portugal-branco.png' ); ?>" alt="" width="356" height="158">
	</div>

	<?php if ( $video ) : ?>
		<a
			class="hero__video hero__video--com-video"
			href="<?php echo esc_url( $video['href'] ); ?>"
			target="_blank"
			rel="noopener"
			<?php if ( $no_dialogo ) : ?>
				data-video-tipo="<?php echo esc_attr( $video['tipo'] ); ?>"
				data-video-src="<?php echo esc_url( $video['src'] ); ?>"
				data-video-vertical="<?php echo $video['vertical'] ? '1' : '0'; ?>"
				aria-haspopup="dialog"
				aria-controls="apit-video-destaque"
			<?php endif; ?>
		>
			<?php
			if ( $video['capa'] ) {
				// Decorative: the link's name is the label below, not the picture.
				echo wp_get_attachment_image(
					$video['capa'],
					'large',
					false,
					[
						'class'   => 'hero__video-capa',
						'alt'     => '',
						'loading' => 'eager',
						'sizes'   => '(max-width: 1400px) 100vw, 329px',
					]
				);
			}
			?>
			<span class="hero__play" aria-hidden="true"><i class="fa-solid fa-circle-play"></i></span>
			<span class="screen-reader-text"><?php echo esc_html( $video['rotulo'] ); ?></span>
		</a>

		<?php if ( $no_dialogo ) : ?>
			<dialog class="apit-video-modal" id="apit-video-destaque" aria-label="<?php echo esc_attr( $video['rotulo'] ); ?>">
				<button type="button" class="apit-video-modal__fechar" aria-label="<?php esc_attr_e( 'Fechar o vídeo', 'apit' ); ?>">
					<i class="fa-solid fa-xmark" aria-hidden="true"></i>
				</button>
				<div class="apit-video-modal__palco"></div>
			</dialog>
		<?php endif; ?>
	<?php else : ?>
		<div class="hero__video" aria-hidden="true">
			<span class="hero__play"><i class="fa-solid fa-circle-play"></i></span>
		</div>
	<?php endif; ?>
</div>
