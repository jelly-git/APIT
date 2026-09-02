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
