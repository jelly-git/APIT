<?php
/*
 * Cada página com hero tem a sua migalha?
 *
 * A migalha não está escrita em lado nenhum: o tema injecta-a no primeiro lugar
 * dentro da coluna do hero. É um bom negócio — uma página nova traz a migalha
 * por existir — mas tem um modo de falhar silencioso: uma página construída de
 * raiz, sem a classe da coluna, fica sem âncora e sem migalha, e nada se
 * queixa. É esse silêncio que esta prova quebra.
 *
 * A regra é simples e vale nos dois sentidos: quem tem âncora tem migalha, quem
 * não tem âncora não tem migalha. A segunda metade apanha o caso contrário —
 * uma migalha a aparecer onde não devia.
 *
 * Lê o HTML servido que o guardar-paginas.sh guardou, e não a base de dados: o
 * que interessa é o que o visitante recebe.
 *
 *   bash tools/guardar-paginas.sh && wp eval-file tools/prova-migalhas.php
 */

$dir       = __DIR__ . '/paginas';
$ficheiros = glob( $dir . '/*.html' );

if ( ! $ficheiros ) {
	WP_CLI::error( 'tools/paginas/ está vazia — correr tools/guardar-paginas.sh primeiro.' );
}

/*
 * As mesmas âncoras que o tema usa, lidas dele e não repetidas aqui: se um hero
 * novo acrescentar a sua, esta prova passa a cobri-lo sem ser tocada.
 */
$ancoras = function_exists( 'apit_breadcrumbs_ancoras' )
	? array_keys( apit_breadcrumbs_ancoras() )
	: array( 'pagina-hero__col', 'sobre-hero__col--texto' );

$com_hero = 0;
$com_migalha = 0;
$faltam   = array();
$sobram   = array();
$mal      = array();

foreach ( $ficheiros as $f ) {
	$html = file_get_contents( $f );
	$nome = basename( $f, '.html' );

	$tem_ancora = false;

	foreach ( $ancoras as $ancora ) {
		if ( preg_match( '#\sclass="[^"]*\b' . preg_quote( $ancora, '#' ) . '\b#', $html ) ) {
			$tem_ancora = true;
			break;
		}
	}

	$n = preg_match_all( '#<nav class="[a-z-]*__crumb"[^>]*>(.*?)</nav>#s', $html, $m );

	if ( $tem_ancora ) {
		$com_hero++;
	}

	if ( $n ) {
		$com_migalha++;
	}

	if ( $tem_ancora && ! $n ) {
		$faltam[] = $nome;
		continue;
	}

	if ( ! $tem_ancora && $n ) {
		$sobram[] = $nome;
		continue;
	}

	if ( ! $n ) {
		continue;
	}

	// Um caminho tem de ter mais do que um passo, e acabar onde o visitante está.
	$itens = preg_match_all( '#<a\s|<span aria-current#', $m[1][0] );

	if ( $itens < 2 ) {
		$mal[] = $nome . ' (só ' . $itens . ' item)';
	} elseif ( false === strpos( $m[1][0], 'aria-current' ) && false === strpos( $nome, 'apit-' ) && false === strpos( $nome, 'portugal-' ) ) {
		// Nos artigos o último item é a categoria, que é ligação — só as páginas fecham em aria-current.
		$mal[] = $nome . ' (sem aria-current no último item)';
	}
}

printf( "%d paginas lidas, %d com hero, %d com migalha\n", count( $ficheiros ), $com_hero, $com_migalha );

if ( $faltam ) {
	echo "\nCOM HERO E SEM MIGALHA:\n";
	foreach ( $faltam as $x ) { echo "  $x\n"; }
}

if ( $sobram ) {
	echo "\nMIGALHA SEM HERO:\n";
	foreach ( $sobram as $x ) { echo "  $x\n"; }
}

if ( $mal ) {
	echo "\nMIGALHA MAL FORMADA:\n";
	foreach ( $mal as $x ) { echo "  $x\n"; }
}

if ( $faltam || $sobram || $mal ) {
	WP_CLI::error( count( $faltam ) + count( $sobram ) + count( $mal ) . ' paginas com problema' );
}

echo "todas as paginas com hero trazem a sua migalha\n";
