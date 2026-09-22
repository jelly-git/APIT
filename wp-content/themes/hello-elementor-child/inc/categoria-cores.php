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
		 * News categories only. The event categories used to live here too,
		 * until they became a taxonomy whose colours the client picks — see
		 * apit_cores_evento() below.
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

/* -------------------------------------------------------------------------
 * Event categories
 * ---------------------------------------------------------------------- */

/**
 * Default gradient stops, used when a category has no colours set and when an
 * event has no category at all. The end stop is the grey every card ended on
 * before the stops became editable.
 */
function apit_cores_evento_padrao() {
	return [
		'inicio' => '#f41892',
		'fim'    => '#e9edf0',
	];
}

/**
 * The gradient stops and name for an event's category.
 *
 * The design keeps the gradient's geometry — its angle and where the stops sit
 * — common to every card. Only the two colours change, which is why the
 * category owns two colours and nothing more.
 *
 * An event takes one category; if more than one is somehow attached, the first
 * wins rather than the card rendering something undefined.
 */
function apit_cores_evento( $post_id ) {
	$padrao = apit_cores_evento_padrao();
	$termos = get_the_terms( $post_id, 'apit_categoria_evento' );

	if ( is_wp_error( $termos ) || ! $termos ) {
		return $padrao + [ 'nome' => '' ];
	}

	$termo  = $termos[0];
	$inicio = apit_campo( 'apit_cat_cor_inicio', 'term_' . $termo->term_id );
	$fim    = apit_campo( 'apit_cat_cor_fim', 'term_' . $termo->term_id );

	return [
		'inicio' => $inicio ? $inicio : $padrao['inicio'],
		'fim'    => $fim ? $fim : $padrao['fim'],
		'nome'   => $termo->name,
	];
}

/**
 * The two stops as custom properties, so the card's gradient stays in CSS and
 * only its colours come from PHP.
 */
function apit_estilo_evento( array $cores ) {
	return sprintf(
		'--apit-cat-inicio: %s; --apit-cat-fim: %s;',
		$cores['inicio'],
		$cores['fim']
	);
}
