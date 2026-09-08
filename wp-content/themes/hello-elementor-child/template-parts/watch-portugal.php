<?php
/**
 * The Watch Portugal lockup, in the outline the surrounding section needs.
 *
 * Two files rather than one recoloured by CSS: the artwork is an outlined
 * lettering with a rule under it, and a mask would flatten the outline into a
 * solid shape. Both live in the theme because they are brand assets that must
 * travel with the code — a section rendering without its logo because someone
 * emptied a field is not a state worth allowing.
 *
 * @var array $args variante — "branco" for a coloured band, "preto" otherwise
 */
$variante = 'branco' === ( $args['variante'] ?? 'preto' ) ? 'branco' : 'preto';

$ficheiro = get_stylesheet_directory() . '/assets/img/logo-watch-portugal-' . $variante . '.png';

if ( ! is_file( $ficheiro ) ) {
	return;
}

$url = get_stylesheet_directory_uri() . '/assets/img/logo-watch-portugal-' . $variante . '.png';
?>
<div class="apit-watch-portugal apit-watch-portugal--<?php echo esc_attr( $variante ); ?>">
	<img src="<?php echo esc_url( $url ); ?>" alt="Watch Portugal — Independent TV Producers" width="356" height="158">
</div>
