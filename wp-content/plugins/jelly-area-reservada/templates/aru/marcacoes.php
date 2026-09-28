<?php
/**
 * As marcações do associado na ARU: as que vêm e as que já passaram, e os
 * eventos onde ainda pode marcar mesa.
 *
 * @var array $aru    secao, user.
 * @var array $pessoa jelly_ar_aru_pessoa().
 */

defined( 'ABSPATH' ) || exit;

$uid       = $aru['user']->ID;
$marcacoes = jelly_ar_aru_marcacoes( $uid );
$estados   = jelly_ar_aru_estados();
$proximas  = array_values( array_filter( $marcacoes, function ( $m ) {
	return $m['futura'] && $m['viva'];
} ) );
// O histórico: as que já passaram e as que não chegaram a ficar (canceladas ou não aprovadas).
$passadas  = array_values( array_filter( $marcacoes, function ( $m ) {
	return ! ( $m['futura'] && $m['viva'] );
} ) );

// Os eventos com marcação abertos onde o associado ainda não tem marcação (uma por evento).
$tem     = wp_list_pluck( array_filter( $marcacoes, function ( $m ) {
	return in_array( $m['estado'], [ 'pendente', 'aprovada' ], true );
} ), 'evento_id' );
$abertos = array_values( array_filter( jelly_ar_aru_eventos(), function ( $e ) use ( $tem ) {
	return $e['marcacoes'] && ! in_array( $e['id'], $tem, true ) && 'disponivel' === jelly_ar_disponibilidade_estado( $e )['estado'];
} ) );
?>

<header class="aru-titulo">
	<h1><?php esc_html_e( 'Marcações', 'jelly-area-reservada' ); ?></h1>
	<p><?php esc_html_e( 'As suas marcações de mesa nos eventos da APIT. Um pedido fica por aprovar até a equipa o confirmar; nessa altura recebe um e-mail.', 'jelly-area-reservada' ); ?></p>
</header>

<?php if ( $abertos ) : ?>
	<section class="aru-cartao">
		<header class="aru-cartao__cabeca">
			<h2><i class="fa-solid fa-chair" aria-hidden="true"></i> <?php esc_html_e( 'Pode marcar mesa em', 'jelly-area-reservada' ); ?></h2>
		</header>
		<ul class="aru-eventos aru-eventos--linha">
			<?php foreach ( $abertos as $e ) : ?>
				<li>
					<a class="aru-evento" href="<?php echo esc_url( jelly_ar_url_marcacao( $e['id'] ) ); ?>">
						<span class="aru-data" style="--aru-cor-a: <?php echo esc_attr( $e['cores']['inicio'] ); ?>; --aru-cor-b: <?php echo esc_attr( $e['cores']['fim'] ); ?>;">
							<strong><?php echo (int) $e['dia_n']; ?></strong>
							<small><?php echo esc_html( $e['mes'] ); ?></small>
						</span>
						<span class="aru-evento__texto">
							<strong><?php echo esc_html( $e['titulo'] ); ?></strong>
							<small><?php echo esc_html( implode( ' · ', array_filter( [ $e['datas'], $e['local'] ] ) ) ); ?></small>
						</span>
						<span class="aru-botao aru-botao--pequeno"><span><?php esc_html_e( 'Marcar mesa', 'jelly-area-reservada' ); ?></span> <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<section class="aru-cartao">
	<header class="aru-cartao__cabeca">
		<h2><?php esc_html_e( 'Próximas', 'jelly-area-reservada' ); ?> <b class="aru-conta"><?php echo (int) count( $proximas ); ?></b></h2>
	</header>
	<?php if ( ! $proximas ) : ?>
		<div class="aru-vazio">
			<i class="fa-regular fa-calendar" aria-hidden="true"></i>
			<p><?php esc_html_e( 'Não tem marcações por vir.', 'jelly-area-reservada' ); ?></p>
		</div>
	<?php else : ?>
		<ul class="aru-lista">
			<?php foreach ( $proximas as $m ) : ?>
				<?php include JELLY_AR_DIR . 'templates/aru/parte-marcacao.php'; ?>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>

<?php if ( $passadas ) : ?>
	<section class="aru-cartao">
		<header class="aru-cartao__cabeca">
			<h2><?php esc_html_e( 'Histórico', 'jelly-area-reservada' ); ?> <b class="aru-conta"><?php echo (int) count( $passadas ); ?></b></h2>
		</header>
		<ul class="aru-lista">
			<?php foreach ( $passadas as $m ) : ?>
				<?php include JELLY_AR_DIR . 'templates/aru/parte-marcacao.php'; ?>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>
