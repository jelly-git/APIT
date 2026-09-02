<?php
/**
 * ACF Pro glue.
 *
 * Field groups live as JSON in the theme's acf-json folder, not in the
 * database. ACF writes them there whenever a group is saved in wp-admin and
 * reads them from there on every request, so a field created locally travels
 * to the server in the same commit as the template that uses it. Without this,
 * a new field would exist only on the machine it was created on.
 */

defined( 'ABSPATH' ) || exit;

function apit_acf_pasta_json() {
	return get_stylesheet_directory() . '/acf-json';
}

add_filter( 'acf/settings/save_json', 'apit_acf_pasta_json' );

/**
 * Replaces ACF's default load path rather than adding to it, so the theme's
 * folder is the only source and there is no chance of two copies of a group
 * disagreeing.
 */
function apit_acf_carregar_json( $caminhos ) {
	unset( $caminhos[0] );

	$caminhos[] = apit_acf_pasta_json();

	return $caminhos;
}
add_filter( 'acf/settings/load_json', 'apit_acf_carregar_json' );

/**
 * Reads a field, and keeps working when ACF is not installed.
 *
 * The plugin is licensed and therefore not in the repository, so a deploy can
 * land on a site where it is missing or deactivated. Returning null there means
 * the sections render empty instead of taking the whole page down with a fatal.
 */
function apit_campo( $nome, $post_id = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	return get_field( $nome, $post_id ?? get_the_ID() );
}

/**
 * The link fields, which are plain text so a relative path can be stored.
 *
 * ACF's url type insists on a scheme, which rejects "/contactos/". A relative
 * path is the value we actually want: it follows the site when it moves domain,
 * while a full URL would have to be found and replaced in the database.
 */
function apit_campos_de_link() {
	// Keys, not names: the Documentos repeater's sub-field is simply called
	// "url", and a name filter would then catch every field called url anywhere.
	return [
		'field_evento_acao_url',
		'field_sobre_doc_url',
		'field_sobre_assoc_url',
		'field_sobre_inter_url',
	];
}

/**
 * Accepts what a link can legitimately be, and rejects the rest — so dropping
 * ACF's url validation does not mean dropping validation.
 *
 * Anything with a scheme goes through wp_allowed_protocols, which is what keeps
 * "javascript:" out.
 */
function apit_validar_link( $valido, $valor ) {
	// Leave ACF's own "required" check to decide about an empty value.
	if ( true !== $valido || '' === trim( (string) $valor ) ) {
		return $valido;
	}

	$valor = trim( (string) $valor );

	// Relative to the site root, an anchor, or a query — all fine as they are.
	if ( in_array( $valor[0], [ '/', '#', '?' ], true ) ) {
		return true;
	}

	// Protocol-relative, e.g. //exemplo.pt/pagina
	if ( 0 === strpos( $valor, '//' ) ) {
		return true;
	}

	if ( preg_match( '#^([a-z][a-z0-9+.-]*):#i', $valor, $m ) ) {
		$esquema = strtolower( $m[1] );

		if ( in_array( $esquema, wp_allowed_protocols(), true ) ) {
			return true;
		}

		return sprintf(
			/* translators: %s: the URL scheme the editor typed. */
			__( 'O esquema "%s:" não é permitido.', 'apit' ),
			$esquema
		);
	}

	return __( 'Indique um caminho interno começado por "/" (ex.: /contactos/) ou um endereço completo com https://.', 'apit' );
}

foreach ( apit_campos_de_link() as $apit_campo_link ) {
	add_filter( 'acf/validate_value/key=' . $apit_campo_link, 'apit_validar_link', 10, 2 );
}

unset( $apit_campo_link );

/**
 * Fills the Órgão dropdown from the órgãos registered in post-types.php, so
 * adding a fourth órgão there makes it selectable here without the field group
 * being touched.
 */
function apit_acf_opcoes_orgao( $campo ) {
	$campo['choices'] = [];

	foreach ( apit_get_orgaos() as $slug => $orgao ) {
		$campo['choices'][ $slug ] = $orgao['nome'];
	}

	return $campo;
}
add_filter( 'acf/load_field/name=apit_orgao_social_orgao', 'apit_acf_opcoes_orgao' );

/*
 * The event category used to be free text, and this file carried a filter that
 * listed the categories with a colour mapped in PHP. Both are gone: the
 * category is a taxonomy term now, and its colours are two fields on the term.
 */
