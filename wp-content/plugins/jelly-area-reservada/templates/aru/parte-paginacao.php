<?php
/**
 * A paginação de uma lista da ARU, com o aspeto da do back-office
 * (Jelly_AR_Lista::paginacao(), inc/lista.php): "1–10 de 27", o "Por página"
 * e as páginas, com a atual a escuro.
 *
 * @var array $paginacao total, pagina, paginas, por_pagina, primeiro, mostrados,
 *                       url (callable: [ args ] => endereço), escondidos
 *                       (os parâmetros a manter no formulário do "Por página").
 */

defined( 'ABSPATH' ) || exit;

$pg      = $paginacao;
$numeros = [];

for ( $p = 1; $p <= $pg['paginas']; $p++ ) {
	if ( 1 === $p || $pg['paginas'] === $p || abs( $p - $pg['pagina'] ) <= 2 ) {
		$numeros[] = $p;
	} elseif ( '…' !== end( $numeros ) ) {
		$numeros[] = '…';
	}
}
?>
<div class="aru-paginacao">
	<p class="aru-paginacao__contagem">
		<?php
		if ( $pg['total'] ) {
			/* translators: 1: primeiro mostrado, 2: último mostrado, 3: total */
			printf( esc_html__( '%1$d–%2$d de %3$d', 'jelly-area-reservada' ), (int) ( $pg['primeiro'] + 1 ), (int) ( $pg['primeiro'] + $pg['mostrados'] ), (int) $pg['total'] );
		}
		?>
	</p>

	<form class="aru-paginacao__tamanho" method="get" action="" data-aru-auto>
		<?php foreach ( $pg['escondidos'] as $nome => $valor ) : ?>
			<?php if ( '' !== (string) $valor ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $nome ); ?>" value="<?php echo esc_attr( $valor ); ?>">
			<?php endif; ?>
		<?php endforeach; ?>
		<label for="aru-por-pagina"><?php esc_html_e( 'Por página', 'jelly-area-reservada' ); ?></label>
		<select id="aru-por-pagina" name="por_pagina">
			<?php foreach ( [ 10, 20, 50 ] as $t ) : ?>
				<option value="<?php echo (int) $t; ?>" <?php selected( $t, $pg['por_pagina'] ); ?>><?php echo (int) $t; ?></option>
			<?php endforeach; ?>
		</select>
		<noscript><button type="submit" class="aru-botao aru-botao--pequeno"><?php esc_html_e( 'Aplicar', 'jelly-area-reservada' ); ?></button></noscript>
	</form>

	<?php if ( $pg['paginas'] > 1 ) : ?>
		<nav class="aru-paginas" aria-label="<?php esc_attr_e( 'Páginas', 'jelly-area-reservada' ); ?>">
			<?php if ( $pg['pagina'] > 1 ) : ?>
				<a class="aru-pagina" href="<?php echo esc_url( call_user_func( $pg['url'], [ 'pagina' => $pg['pagina'] - 1 ] ) ); ?>" aria-label="<?php esc_attr_e( 'Página anterior', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
			<?php else : ?>
				<span class="aru-pagina is-inativa" aria-hidden="true"><i class="fa-solid fa-chevron-left"></i></span>
			<?php endif; ?>

			<?php foreach ( $numeros as $p ) : ?>
				<?php if ( '…' === $p ) : ?>
					<span class="aru-pagina is-reticencias" aria-hidden="true">…</span>
				<?php elseif ( $p === $pg['pagina'] ) : ?>
					<span class="aru-pagina is-atual" aria-current="page"><?php echo (int) $p; ?></span>
				<?php else : ?>
					<?php /* translators: %d: número da página */ ?>
					<a class="aru-pagina" href="<?php echo esc_url( call_user_func( $pg['url'], [ 'pagina' => $p ] ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Página %d', 'jelly-area-reservada' ), $p ) ); ?>"><?php echo (int) $p; ?></a>
				<?php endif; ?>
			<?php endforeach; ?>

			<?php if ( $pg['pagina'] < $pg['paginas'] ) : ?>
				<a class="aru-pagina" href="<?php echo esc_url( call_user_func( $pg['url'], [ 'pagina' => $pg['pagina'] + 1 ] ) ); ?>" aria-label="<?php esc_attr_e( 'Página seguinte', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
			<?php else : ?>
				<span class="aru-pagina is-inativa" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
</div>
