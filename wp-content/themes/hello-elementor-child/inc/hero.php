<?php
/**
 * Hero background helpers — the video, image and slider behind the hero.
 *
 * Kept out of shortcodes.php because none of this is about shortcodes: it turns
 * the values of the "Hero — fundo" field group into markup, and the shortcode
 * is only one of its callers.
 */

defined( 'ABSPATH' ) || exit;

/**
 * How long each slide stays, in milliseconds.
 *
 * An empty field is 4000 rather than 0, and anything under a second is raised:
 * a mistyped 40 would flicker rather than slide. ACF's own min/max guard the
 * form, but not a value already stored, nor one set by code.
 */
function apit_hero_intervalo( $valor ) {
	$ms = (int) $valor;

	if ( $ms <= 0 ) {
		$ms = 4000;
	}

	return max( 1000, min( 30000, $ms ) );
}

/**
 * Whether a reference is an attachment, as opposed to the filename the
 * shortcode attribute holds.
 */
function apit_hero_e_anexo( $ref ) {
	return is_numeric( $ref ) && (int) $ref > 0;
}

/**
 * The URL of one image, from an attachment ID or from a filename in the theme.
 */
function apit_hero_imagem_url( $ref ) {
	if ( is_array( $ref ) ) {
		$ref = $ref['ID'] ?? ( $ref['id'] ?? '' );
	}

	return apit_media_url( (string) $ref, 'img' );
}

/**
 * One <img> for a slide.
 *
 * An attachment goes through wp_get_attachment_image so it carries srcset and
 * sizes — worth having when the file is a full-width hero image. The alt is
 * empty on purpose: this is a background, and the hero's real heading is the
 * page's h1.
 */
function apit_hero_imagem_html( $ref ) {
	if ( apit_hero_e_anexo( $ref ) ) {
		return wp_get_attachment_image(
			(int) $ref,
			'full',
			false,
			[
				'class' => 'apit-hero__poster',
				'alt'   => '',
				'sizes' => '100vw',
			]
		);
	}

	$url = apit_hero_imagem_url( $ref );

	return $url
		? sprintf( '<img class="apit-hero__poster" src="%s" alt="">', esc_url( $url ) )
		: '';
}

/**
 * Prints the first slide.
 *
 * A <picture> when the two galleries start on different images, so the browser
 * resolves the breakpoint itself and downloads one file — before any script has
 * run. Every later slide is built by JS, which is why only this one gets the
 * treatment.
 */
function apit_hero_primeira_imagem( $desktop, $mobile ) {
	$html_desktop = apit_hero_imagem_html( $desktop );

	if ( ! $html_desktop ) {
		echo apit_hero_imagem_html( $mobile ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from escaped parts.
		return;
	}

	$mesma = ! $mobile || (string) $mobile === (string) $desktop;

	if ( $mesma ) {
		echo $html_desktop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}

	$srcset_mobile = apit_hero_e_anexo( $mobile )
		? wp_get_attachment_image_srcset( (int) $mobile, 'full' )
		: '';

	// Without srcset — a theme file rather than an attachment — the plain URL
	// still works as a single-candidate srcset.
	if ( ! $srcset_mobile ) {
		$srcset_mobile = apit_hero_imagem_url( $mobile );
	}

	printf(
		'<picture><source media="(max-width: 768px)" srcset="%s" sizes="100vw">%s</picture>',
		esc_attr( $srcset_mobile ),
		$html_desktop // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}
