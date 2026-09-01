<?php
/**
 * Colour per category.
 *
 * Each calendar and news category gets a brand colour, which drives the card's
 * accent (date strip, pill) and the gradient tint behind it. The map is keyed by
 * a sanitised category name so both event meta (free text) and post categories
 * (terms) resolve through the same table.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Category name (or slug) => brand colour.
 */
function apit_categoria_cores() {
	$cores = [
		// Categories as they appear on the mockup cards.
		'evento-apit'          => '#2ec6b0', // turquesa
		'evento-internacional' => '#f41892', // magenta
		'stand-apit'           => '#4a85c8', // azul

		// News categories.
		'institucional'        => '#f41892',
		'mercados-feiras'      => '#4a85c8',
		'mercados'             => '#4a85c8',
		'setor'                => '#8048a6', // roxo
		'internacional'        => '#2ec6b0',
	];

	/**
	 * Lets the mapping be adjusted without editing the theme.
	 */
	return apply_filters( 'apit_categoria_cores', $cores );
}

/**
 * Resolves a category label to its colour, falling back to magenta.
 */
function apit_cor_categoria( $categoria ) {
	if ( ! $categoria ) {
		return '#f41892';
	}

	$cores = apit_categoria_cores();
	$chave = sanitize_title( $categoria );

	return $cores[ $chave ] ?? '#f41892';
}

/**
 * Inline custom-property declaration so a card can tint itself from PHP.
 * Returns e.g. --apit-cat: #4a85c8;
 */
function apit_cor_categoria_style( $categoria ) {
	return '--apit-cat: ' . esc_attr( apit_cor_categoria( $categoria ) ) . ';';
}
