<?php
/*
 * The pages as the server would send them.
 *
 * Each page was fetched from the local site, where the filter is idle because
 * WordPress sits at the root. Running that same HTML back through
 * apit_prefixar_links() with home_url pretending to be the subfolder is exactly
 * what the server does on the way out — so what comes out here is what a
 * visitor at dev.jellycode.agency/apit would click.
 *
 * Anything internal that does not start with /apit would be a 404 there.
 */
add_filter( 'home_url', function () { return 'https://dev.jellycode.agency/apit/'; }, 999 );

$dir      = __DIR__ . '/paginas';
$ficheiros = glob( $dir . '/*.html' );

// Uma pasta vazia dava "0 ligações, nenhuma fora da subpasta" — um visto
// por não ter olhado para nada. Corre-se guardar-paginas.sh primeiro.
if ( ! $ficheiros ) {
	WP_CLI::error( 'tools/paginas/ está vazia — correr tools/guardar-paginas.sh primeiro.' );
}

$maus = array();
$bons = array();

foreach ( $ficheiros as $f ) {
	$html = apit_prefixar_links( file_get_contents( $f ) );

	preg_match_all( '~\s(?:href|src)="([^"]+)"~i', $html, $m );

	foreach ( array_unique( $m[1] ) as $u ) {
		// external, protocol-relative, anchors and schemes are not ours
		if ( preg_match( '~^(https?:)?//|^(#|mailto:|tel:|data:|javascript:)~i', $u ) || '' === $u ) {
			continue;
		}
		if ( 0 !== strpos( $u, '/' ) ) {
			continue; // a genuinely relative path, resolved against the page
		}
		if ( 0 === strpos( $u, '/apit/' ) || '/apit' === $u ) {
			$bons[ $u ] = true;
		} else {
			$maus[ $u ][] = basename( $f );
		}
	}
}

printf( "%d paginas lidas, %d ligacoes internas sob /apit\n", count( $ficheiros ), count( $bons ) );

if ( $maus ) {
	echo "\nFORA DA SUBPASTA — dariam 404 no servidor:\n";
	foreach ( $maus as $u => $onde ) {
		printf( "  %-52s %s\n", $u, implode( ', ', array_unique( $onde ) ) );
	}
} else {
	echo "nenhuma ligacao fora da subpasta\n";
}

echo "\namostra:\n";
$a = array_keys( $bons );
sort( $a );
foreach ( array_slice( $a, 0, 12 ) as $u ) { echo "  $u\n"; }
