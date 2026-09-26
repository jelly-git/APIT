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
 * Category slug => brand colour, for a category with no colour of its own.
 *
 * Since the categories carry a colour field, this is the starting point rather
 * than the rule: it is what the three categories the design named look like
 * until someone changes them in wp-admin.
 */
function apit_categoria_cores() {
	$cores = [
		/*
		 * News categories only. The event categories used to live here too;
		 * they and their gradient colours are now managed in the Área
		 * Reservada back-office, which hands the calendar each card's colours.
		 */
		'institucional'        => '#f41892',
		'mercados-feiras'      => '#4a85c8',
		'mercados'             => '#4a85c8',
		'setor'                => '#2ec6b0', // turquesa
		'internacional'        => '#2ec6b0',
	];

	/**
	 * Lets the mapping be adjusted without editing the theme.
	 */
	return apply_filters( 'apit_categoria_cores', $cores );
}

/**
 * The category term behind a label, whether the label is a slug or a name.
 *
 * The cards pass the name they print, and a name is not always its own slug:
 * "Mercados &amp; Feiras" sanitises to "mercados-amp-feiras", which matches
 * neither the term's slug nor the table above — that category was falling back
 * to magenta with a colour of its own sitting right there.
 *
 * Null when nothing matches, which is the case for the calendar's older
 * free-text categories.
 */
function apit_categoria_termo( $categoria ) {
	$termo = get_term_by( 'slug', sanitize_title( $categoria ), 'category' );

	if ( ! $termo ) {
		$termo = get_term_by( 'name', wp_specialchars_decode( $categoria, ENT_QUOTES ), 'category' );
	}

	return ( $termo && ! is_wp_error( $termo ) ) ? $termo : null;
}

/**
 * Resolves a category label to its colour, falling back to magenta.
 *
 * The client's own choice first: the colour field on the category itself, which
 * is what an editor sees and can change. The table above is what a category
 * with no colour set gets, so the three the design named keep looking right
 * without anyone having to open them; and magenta is what is left for a
 * category that is in neither.
 */
function apit_cor_categoria( $categoria ) {
	if ( ! $categoria ) {
		return '#f41892';
	}

	$cores = apit_categoria_cores();
	$termo = apit_categoria_termo( $categoria );

	if ( $termo ) {
		$cor = apit_campo( 'apit_noticia_cor', 'term_' . $termo->term_id );

		if ( is_string( $cor ) && '' !== trim( $cor ) ) {
			return trim( $cor );
		}

		if ( isset( $cores[ $termo->slug ] ) ) {
			return $cores[ $termo->slug ];
		}
	}

	return $cores[ sanitize_title( $categoria ) ] ?? '#f41892';
}

/**
 * Inline custom-property declaration so a card can tint itself from PHP.
 * Returns e.g. --apit-cat: #4a85c8;
 */
function apit_cor_categoria_style( $categoria ) {
	return '--apit-cat: ' . esc_attr( apit_cor_categoria( $categoria ) ) . ';';
}
