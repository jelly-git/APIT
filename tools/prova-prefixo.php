<?php
/*
 * Does apit_prefixar_links() actually do the job on the server?
 *
 * It cannot be seen locally: the site sits at the root of apit.local, the
 * site's path is "", and the filter returns early. So home_url is filtered to
 * pretend the site lives under /apit, which is what the server does, and the
 * cases that matter are run through it.
 */
add_filter( 'home_url', function () { return 'https://dev.jellycode.agency/apit/'; }, 999 );

WP_CLI::log( 'caminho do site: "' . apit_caminho_do_site() . "\"\n" );

$casos = [
	'<a href="/contactos/">'                         => '<a href="/apit/contactos/">',
	'<a href="/">'                                   => '<a href="/apit/">',
	'<a href="/associados/todos-os-associados/">'     => '<a href="/apit/associados/todos-os-associados/">',
	'<img src="/wp-content/uploads/a.png">'           => '<img src="/apit/wp-content/uploads/a.png">',
	'<a href="/contactos/?x=1#y">'                    => '<a href="/apit/contactos/?x=1#y">',

	// já prefixado — não pode levar /apit outra vez
	'<a href="/apit/contactos/">'                    => '<a href="/apit/contactos/">',
	'<a href="/apit/">'                              => '<a href="/apit/">',
	'<a href="/apit">'                               => '<a href="/apit">',

	// não são ligações internas à raiz — ficam como estão
	'<a href="//cdn.exemplo.com/x.js">'              => '<a href="//cdn.exemplo.com/x.js">',
	'<a href="https://exemplo.com/">'                => '<a href="https://exemplo.com/">',
	'<a href="#associados">'                         => '<a href="#associados">',
	'<a href="mailto:geral@apit.pt">'                => '<a href="mailto:geral@apit.pt">',
	'<a href="contactos/">'                          => '<a href="contactos/">',

	// uma armadilha: /apitico/ começa por "apit" mas não é a subpasta
	'<a href="/apitico/">'                           => '<a href="/apit/apitico/">',
];

$maus = 0;
foreach ( $casos as $entrada => $esperado ) {
	$obtido = apit_prefixar_links( $entrada );
	$ok     = ( $obtido === $esperado );
	if ( ! $ok ) { $maus++; }
	WP_CLI::log( sprintf( '  %s %-46s -> %s', $ok ? 'ok  ' : 'MAU!', $entrada, $obtido ) );
	if ( ! $ok ) { WP_CLI::log( sprintf( '       esperado: %s', $esperado ) ); }
}

WP_CLI::log( "\n" . ( $maus ? "$maus casos errados" : 'todos os casos passam' ) );
