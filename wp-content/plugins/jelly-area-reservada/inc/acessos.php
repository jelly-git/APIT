<?php
/**
 * Registo de acessos: cada login de um associado fica numa linha da tabela
 * jelly_ar_acessos, com data e hora, IP e browser.
 *
 * Só os associados — a equipa da APIT a entrar no wp-admin não é um acesso à
 * área reservada.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Quem pode entrar no back-office: só utilizadores com o papel de
 * administrador do WordPress.
 *
 * É o papel que se verifica, e não uma permissão como manage_options: uma
 * permissão pode ser dada a outro papel por um plugin qualquer, e o papel
 * não. A permissão é exigida também, para um administrador a quem ela tenha
 * sido tirada não passar. Não há filtro que alargue isto — de propósito.
 *
 * Todas as portas passam por aqui: o menu, as páginas, as exportações e a
 * descarga dos documentos. Vive neste ficheiro, carregado sempre, e não no do
 * back-office, porque a descarga também serve o site.
 */
function jelly_ar_e_administrador( $user = null ) {
	$user = $user instanceof WP_User ? $user : wp_get_current_user();

	return $user->exists()
		&& in_array( 'administrator', (array) $user->roles, true )
		&& user_can( $user, 'manage_options' );
}

function jelly_ar_e_associado( $user ) {
	return $user instanceof WP_User && in_array( 'apit_associado', (array) $user->roles, true );
}

function jelly_ar_registar_acesso( $login, $user ) {
	if ( ! jelly_ar_e_associado( $user ) ) {
		return;
	}

	global $wpdb;

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		jelly_ar_tabela_acessos(),
		[
			'user_id'    => $user->ID,
			'criado_em'  => current_time( 'mysql', true ),
			'ip'         => substr( $ip, 0, 45 ),
			'user_agent' => substr( $ua, 0, 255 ),
		],
		[ '%d', '%s', '%s', '%s' ]
	);
}
add_action( 'wp_login', 'jelly_ar_registar_acesso', 10, 2 );

/**
 * O dispositivo de um acesso, para ler: "Chrome · Windows", "Safari · iPhone".
 *
 * O browser anuncia-se num texto comprido (o user agent) em que quase todos se
 * dizem "Mozilla" e "Safari" por compatibilidade, e por isso a ordem conta:
 * o Edge e o Opera dizem também "Chrome", e o Chrome diz também "Safari".
 * Guarda-se o texto inteiro (jelly_ar_registar_acesso()) e traduz-se só ao
 * mostrar, para uma regra melhor valer também para os acessos antigos.
 *
 * O Windows 11 anuncia-se como "Windows NT 10.0", tal como o 10: não se
 * distinguem, e fica só "Windows".
 */
function jelly_ar_dispositivo( $ua ) {
	$ua = (string) $ua;

	// Um texto que não é um user agent (os dados de exemplo) fica como está.
	if ( '' === $ua || false === stripos( $ua, 'mozilla/' ) && false === stripos( $ua, 'opera/' ) ) {
		return '' === $ua ? '—' : $ua;
	}

	$browsers = [
		'Edg/'            => 'Edge',
		'EdgiOS/'         => 'Edge',
		'EdgA/'           => 'Edge',
		'OPR/'            => 'Opera',
		'Opera'           => 'Opera',
		'SamsungBrowser/' => 'Samsung Internet',
		'Firefox/'        => 'Firefox',
		'FxiOS/'          => 'Firefox',
		'CriOS/'          => 'Chrome',
		'Chrome/'         => 'Chrome',
		'Version/'        => 'Safari',
	];
	$browser  = __( 'Browser', 'jelly-area-reservada' );
	foreach ( $browsers as $marca => $nome ) {
		if ( false !== strpos( $ua, $marca ) ) {
			$browser = $nome;
			break;
		}
	}

	// O iPad recente diz-se "Macintosh"; só o toque o distingue, e esse não vem no texto.
	$sistemas = [
		'iPhone'    => 'iPhone',
		'iPad'      => 'iPad',
		'Android'   => 'Android',
		'CrOS'      => 'ChromeOS',
		'Windows'   => 'Windows',
		'Macintosh' => 'macOS',
		'Linux'     => 'Linux',
	];
	$sistema  = '';
	foreach ( $sistemas as $marca => $nome ) {
		if ( false !== strpos( $ua, $marca ) ) {
			$sistema = $nome;
			break;
		}
	}

	return $sistema ? $browser . ' · ' . $sistema : $browser;
}

/**
 * O telefone como fica guardado e mostrado: indicativo, um espaço, e o número
 * seguido — "+351 912345678". Sem indicativo, assume-se o português.
 */
function jelly_ar_telefone( $telefone ) {
	$escrito = trim( (string) $telefone );
	$digitos = preg_replace( '/\D/', '', $escrito );

	if ( '' === $digitos ) {
		return '';
	}

	$internacional = 0 === strpos( $escrito, '+' );

	// "00351…" é o mesmo que "+351…".
	if ( ! $internacional && 0 === strpos( $digitos, '00' ) ) {
		$internacional = true;
		$digitos       = substr( $digitos, 2 );
	}

	if ( ! $internacional ) {
		return '+351 ' . $digitos;
	}

	$tamanho = jelly_ar_tamanho_indicativo( $digitos );

	return '+' . substr( $digitos, 0, $tamanho ) . ' ' . substr( $digitos, $tamanho );
}

/**
 * Quantos algarismos tem o indicativo em que o número começa. Os indicativos
 * nunca são prefixo uns dos outros, o que torna isto uma questão de tabela:
 * 1 e 7 têm um algarismo, estes de dois, e todos os outros três (+351 incluído).
 */
function jelly_ar_tamanho_indicativo( $digitos ) {
	if ( in_array( $digitos[0], [ '1', '7' ], true ) ) {
		return 1;
	}

	$de_dois = [
		'20', '27', '30', '31', '32', '33', '34', '36', '39', '40', '41', '43', '44', '45', '46', '47', '48', '49',
		'51', '52', '53', '54', '55', '56', '57', '58', '60', '61', '62', '63', '64', '65', '66', '81', '82', '84',
		'86', '90', '91', '92', '93', '94', '95', '98',
	];

	return in_array( substr( $digitos, 0, 2 ), $de_dois, true ) ? 2 : 3;
}
