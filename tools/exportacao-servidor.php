<?php
/**
 * Transforma a exportação crua no ficheiro a subir (DEPLOY.md, secção 3.2).
 *
 *   php app/public/tools/exportacao-servidor.php bd-sem-cabecalho.sql apit-bd-para-servidor.sql
 *
 * Corre com o PHP simples, fora do WordPress: só mexe em texto.
 *
 * A Área Reservada nunca vai no ficheiro. As tabelas dela, os utilizadores e
 * as respostas aos formulários já ficam de fora na própria exportação, pela
 * lista de tabelas; o que este script trata é o que está em tabelas partilhadas
 * e por isso não sai com elas:
 *
 *  - as opções `jelly_ar_*` e os transientes da AR, na wp_options. O ficheiro
 *    substitui a wp_options inteira, pelo que as do servidor são guardadas numa
 *    tabela temporária antes e repostas no fim — com o valor que lá tinham;
 *  - os termos da taxonomia `jelly_ar_doc_categoria`, restos órfãos de uma
 *    versão antiga do plugin, que já não a regista;
 *  - a configuração do envio de e-mail (as opções `wp_mail_smtp*` do WP Mail
 *    SMTP e os transientes dele): o envio é o de cada servidor — em produção
 *    é o PHP, porque o SMTP era bloqueado lá —, e nunca muda com uma versão
 *    da base de dados. Como as da AR, as do servidor são guardadas antes e
 *    repostas no fim. Vai com elas o diagnóstico do último envio falhado, com
 *    o IP e o nome desta máquina.
 *
 * Os dados de demonstração nunca vão (regra do cliente, 28 de setembro). Os da
 * AR já ficam de fora com as tabelas dela; aqui sai o que não está nelas:
 *
 *  - o vídeo do painel da Home, quando é de demonstração. Os campos
 *    `home_video_*` estão na wp_postmeta da Home, que vai inteira com o resto
 *    do conteúdo; um vídeo de demonstração é o que tem o texto do botão
 *    (`home_video_rotulo`) a começar por "DEMO". Saem todos os campos do vídeo
 *    dessa página, e em produção o painel fica decorativo, como sem vídeo.
 *
 * E os endereços escapados dentro do JSON do Elementor (`http:\\/\\/apit.local`),
 * que o search-replace não apanha. Feito aqui e não em sed: o padrão é feito de
 * barras invertidas e passá-lo por uma shell intacto já falhou três vezes.
 */

if ( $argc < 3 ) {
	fwrite( STDERR, "uso: php exportacao-servidor.php <exportacao crua> <ficheiro a subir>\n" );
	exit( 1 );
}

$s = file_get_contents( $argv[1] );

if ( false === $s ) {
	fwrite( STDERR, "não li {$argv[1]}\n" );
	exit( 1 );
}

$b = chr( 92 ) . chr( 92 ) . '/';
$s = str_replace( 'http:' . $b . $b . 'apit.local', 'https:' . $b . $b . 'dev.jellycode.agency' . $b . 'apit', $s );

// Os ids dos termos órfãos, lidos da wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy).
preg_match_all( "~^\('\d+', '(\d+)', 'jelly_ar_[^']*',~m", $s, $m );

// As páginas cujo vídeo do painel é de demonstração (wp_postmeta: meta_id, post_id, meta_key, meta_value).
preg_match_all( "~^\('\d+', '(\d+)', 'home_video_rotulo', 'DEMO~m", $s, $demo );

$fora = array(
	'wp_options'       => "~^\('\d+', '(jelly_ar_[^']*|_transient_[^']*jelly_ar_[^']*|wp_mail_smtp[^']*|_transient_[^']*wp_mail_smtp[^']*)',~",
	'wp_term_taxonomy' => "~^\('\d+', '\d+', 'jelly_ar_[^']*',~",
	'wp_terms'         => $m[1] ? "~^\('(" . implode( '|', $m[1] ) . ")', ~" : null,
	'wp_postmeta'      => $demo[1] ? "~^\('\d+', '(" . implode( '|', array_unique( $demo[1] ) ) . ")', '_?home_video_[a-z]+',~" : null,
);

/*
 * O search-replace --export escreve um registo por linha, cada um acabado em
 * vírgula e o último do INSERT em ponto e vírgula. Tirar o último obriga o
 * anterior a fechar o INSERT.
 */
$linhas = explode( "\n", $s );
$tabela = '';
$conta  = array();

foreach ( $linhas as $i => $l ) {
	if ( preg_match( '~^INSERT INTO `([a-z_]+)`~', $l, $t ) ) {
		$tabela = $t[1];
		continue;
	}

	if ( '' === $tabela || empty( $fora[ $tabela ] ) || ! preg_match( $fora[ $tabela ], $l ) ) {
		continue;
	}

	if ( ';' === substr( rtrim( $l ), -1 ) ) {
		for ( $j = $i - 1; ! isset( $linhas[ $j ] ); $j-- );
		$linhas[ $j ] = preg_replace( '~,\s*$~', ';', $linhas[ $j ] );
	}

	unset( $linhas[ $i ] );
	$conta[ $tabela ] = ( $conta[ $tabela ] ?? 0 ) + 1;
}

$s = implode( "\n", $linhas );

/*
 * O cabeçalho: as tabelas do WordPress declaram datas 0000-00-00 por omissão,
 * que um MySQL em modo estrito recusa com "Invalid default value for
 * 'comment_date'", e a exportação não traz a instrução que desliga esse modo.
 *
 * A tabela temporária vive enquanto dura a ligação, e a importação do
 * phpMyAdmin corre o ficheiro todo numa só.
 */
$inicio = "SET @OLD_SQL_MODE = @@SQL_MODE;\n"
	. "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n"
	. "SET NAMES utf8mb4;\n"
	. "SET FOREIGN_KEY_CHECKS = 0;\n"
	. "-- As opcoes da Area Reservada e as do envio de e-mail (WP Mail SMTP) que\n"
	. "-- estiverem no servidor ficam como estao: a wp_options e substituida\n"
	. "-- abaixo, e estas linhas voltam no fim.\n"
	. "CREATE TEMPORARY TABLE apit_ar_opcoes SELECT option_name, option_value, autoload FROM wp_options WHERE option_name LIKE 'jelly\\_ar\\_%' OR option_name LIKE '\\_transient\\_%jelly\\_ar\\_%' OR option_name LIKE 'wp\\_mail\\_smtp%' OR option_name LIKE '\\_transient\\_%wp\\_mail\\_smtp%';\n";

$fim = "INSERT INTO wp_options (option_name, option_value, autoload) SELECT option_name, option_value, autoload FROM apit_ar_opcoes ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), autoload = VALUES(autoload);\n"
	. "DROP TEMPORARY TABLE apit_ar_opcoes;\n"
	. "SET FOREIGN_KEY_CHECKS = 1;\n"
	. "SET SQL_MODE = @OLD_SQL_MODE;\n";

file_put_contents( $argv[2], $inicio . $s . "\n" . $fim );

foreach ( $conta as $t => $n ) {
	echo "$t: $n linhas tiradas\n";
}

echo substr_count( $s, 'apit.local' ) . " apit.local que restam\n";
