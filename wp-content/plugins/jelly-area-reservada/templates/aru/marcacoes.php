<?php
/**
 * As marcações do associado na ARU.
 *
 * Primeiro as suas próximas marcações, agrupadas por evento: o evento em
 * cabeça (título, datas, local, e a ligação para a página dele) e, por baixo,
 * uma linha por marcação — o dia, a hora, a mesa e o estado. Depois, os
 * eventos onde ainda pode marcar (num dia sem marcação sua; é uma por dia),
 * em cartões pequenos. No fim, o histórico, agrupado da mesma maneira.
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

// Os eventos onde o associado ainda pode marcar: num dia sem marcação sua (é uma por dia).
$abertos = array_values( array_filter( jelly_ar_aru_eventos(), function ( $e ) use ( $uid ) {
	return jelly_ar_aru_pode_marcar( $e, $uid );
} ) );

/*
 * As marcações de uma lista agrupadas por evento, pela ordem em que aparecem:
 * [ evento_id => [ evento, lista ] ]. O evento vem da ARU; um que já lá não
 * esteja fica só com o título e as cores da marcação.
 */
$agrupar = function ( $lista ) {
	$grupos = [];

	foreach ( $lista as $m ) {
		if ( ! isset( $grupos[ $m['evento_id'] ] ) ) {
			$e = jelly_ar_aru_evento( $m['evento_id'] );

			$grupos[ $m['evento_id'] ] = [
				'evento' => $e ? $e : [ 'titulo' => $m['evento'], 'cores' => $m['cores'], 'datas' => '', 'local' => '', 'url' => '', 'dia_n' => $m['dia_n'], 'mes' => $m['mes'] ],
				'lista'  => [],
			];
		}

		$grupos[ $m['evento_id'] ]['lista'][] = $m;
	}

	return $grupos;
};

// Um grupo: o evento em cabeça e as marcações por baixo, uma por linha.
$grupo = function ( $g ) use ( $estados ) {
	$e = $g['evento'];
	?>
	<article class="aru-grupo">
		<header class="aru-grupo__cabeca">
			<span class="aru-data" style="--aru-cor-a: <?php echo esc_attr( $e['cores']['inicio'] ); ?>; --aru-cor-b: <?php echo esc_attr( $e['cores']['fim'] ); ?>;">
				<strong><?php echo (int) $e['dia_n']; ?></strong>
				<small><?php echo esc_html( $e['mes'] ); ?></small>
			</span>
			<div class="aru-grupo__evento">
				<h3><?php echo esc_html( $e['titulo'] ); ?></h3>
				<?php if ( $e['datas'] || $e['local'] ) : ?>
					<small><?php echo esc_html( implode( ' · ', array_filter( [ $e['datas'], $e['local'] ] ) ) ); ?></small>
				<?php endif; ?>
			</div>
			<?php if ( $e['url'] ) : ?>
				<a class="aru-ligacao" href="<?php echo esc_url( $e['url'] ); ?>"><?php esc_html_e( 'Ver evento', 'jelly-area-reservada' ); ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
			<?php endif; ?>
		</header>

		<ul class="aru-grupo__lista">
			<?php foreach ( $g['lista'] as $m ) : ?>
				<li class="aru-linha<?php echo $m['viva'] && $m['futura'] ? '' : ' is-passada'; ?>">
					<span class="aru-linha__dia"><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?php echo esc_html( $m['data'] ); ?></span>
					<span class="aru-linha__hora"><i class="fa-regular fa-clock" aria-hidden="true"></i> <?php echo esc_html( $m['hora'] ); ?></span>
					<span class="aru-linha__mesa"><i class="fa-solid fa-chair" aria-hidden="true"></i> <?php echo esc_html( implode( ' · ', array_filter( [ $m['mesa'], $m['localizacao'] ] ) ) ); ?></span>
					<span class="aru-estado aru-estado--<?php echo esc_attr( $m['estado'] ); ?>"><?php echo esc_html( $estados[ $m['estado'] ] ?? $m['estado'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</article>
	<?php
};
?>

<header class="aru-titulo">
	<h1><?php esc_html_e( 'Marcações', 'jelly-area-reservada' ); ?></h1>
	<p><?php esc_html_e( 'As marcações de mesa nos eventos da APIT. As marcações por aprovar aguardam a confirmação da APIT, que será enviada por e-mail.', 'jelly-area-reservada' ); ?></p>
</header>

<?php /* ---------- As minhas próximas marcações ---------- */ ?>
<section class="aru-cartao">
	<header class="aru-cartao__cabeca">
		<h2><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> <?php esc_html_e( 'As minhas próximas marcações', 'jelly-area-reservada' ); ?> <?php if ( $proximas ) : ?><b class="aru-conta"><?php echo (int) count( $proximas ); ?></b><?php endif; ?></h2>
	</header>

	<?php if ( ! $proximas ) : ?>
		<div class="aru-vazio">
			<i class="fa-regular fa-calendar" aria-hidden="true"></i>
			<p><?php esc_html_e( 'Ainda não tem marcações por vir. Os eventos com marcação aberta estão abaixo.', 'jelly-area-reservada' ); ?></p>
		</div>
	<?php else : ?>
		<div class="aru-grupos">
			<?php array_map( $grupo, $agrupar( $proximas ) ); ?>
		</div>
	<?php endif; ?>
</section>

<?php /* ---------- Os eventos onde ainda pode marcar ---------- */ ?>
<?php if ( $abertos ) : ?>
	<section class="aru-cartao">
		<header class="aru-cartao__cabeca">
			<div>
				<h2><i class="fa-solid fa-chair" aria-hidden="true"></i> <?php esc_html_e( 'Eventos com marcação aberta', 'jelly-area-reservada' ); ?></h2>
				<p class="aru-cartao__meta"><?php esc_html_e( 'Eventos com dias em que ainda pode marcar mesa: uma marcação por dia do evento.', 'jelly-area-reservada' ); ?></p>
			</div>
		</header>
		<ul class="aru-abertos">
			<?php foreach ( $abertos as $e ) : ?>
				<li>
					<a class="aru-aberto" href="<?php echo esc_url( $e['url'] ); ?>">
						<span class="aru-data" style="--aru-cor-a: <?php echo esc_attr( $e['cores']['inicio'] ); ?>; --aru-cor-b: <?php echo esc_attr( $e['cores']['fim'] ); ?>;">
							<strong><?php echo (int) $e['dia_n']; ?></strong>
							<small><?php echo esc_html( $e['mes'] ); ?></small>
						</span>
						<span class="aru-aberto__texto">
							<strong><?php echo esc_html( $e['titulo'] ); ?></strong>
							<small><?php echo esc_html( implode( ' · ', array_filter( [ $e['datas'], $e['local'] ] ) ) ); ?></small>
							<em><?php esc_html_e( 'Escolher horário', 'jelly-area-reservada' ); ?> <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></em>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<?php /* ---------- O histórico ---------- */ ?>
<?php if ( $passadas ) : ?>
	<section class="aru-cartao aru-historico">
		<header class="aru-cartao__cabeca">
			<div>
				<h2><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> <?php esc_html_e( 'Histórico', 'jelly-area-reservada' ); ?> <b class="aru-conta"><?php echo (int) count( $passadas ); ?></b></h2>
				<p class="aru-cartao__meta"><?php esc_html_e( 'As marcações que já passaram e as que foram canceladas ou não aprovadas.', 'jelly-area-reservada' ); ?></p>
			</div>
		</header>
		<div class="aru-grupos">
			<?php array_map( $grupo, $agrupar( $passadas ) ); ?>
		</div>
	</section>
<?php endif; ?>
