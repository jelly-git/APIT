<?php
/**
 * Os eventos na ARU: os que aceitam marcações (os da Área Reservada) que vêm, e os
 * últimos que já passaram. Cada um leva à sua página na ARU
 * (templates/aru/evento.php), nunca ao calendário do site.
 *
 * @var array $aru    secao, user, pagina, evento.
 * @var array $pessoa jelly_ar_aru_pessoa().
 */

defined( 'ABSPATH' ) || exit;

$uid       = $aru['user']->ID;
$proximos  = jelly_ar_aru_eventos();
$passados  = jelly_ar_aru_eventos( true, 12 );
$estados   = jelly_ar_aru_estados();

// As marcações vivas do associado, por evento, para o cartão dizer "A sua marcação".
$minhas = [];
foreach ( jelly_ar_aru_marcacoes( $uid ) as $m ) {
	if ( $m['viva'] ) {
		$minhas[ $m['evento_id'] ] = $m;
	}
}

// Um cartão de evento: a data na cor da categoria, o título, as datas e o local, e o que há nele.
$cartao = function ( $e ) use ( $minhas, $estados ) {
	$docs  = count( jelly_ar_evento_documentos( $e['id'], true ) );
	$minha = $minhas[ $e['id'] ] ?? null;
	?>
	<li>
		<a class="aru-cartao-evento<?php echo $e['terminado'] ? ' is-passado' : ''; ?>" href="<?php echo esc_url( $e['url'] ); ?>">
			<span class="aru-cartao-evento__capa" style="--aru-cor-a: <?php echo esc_attr( $e['cores']['inicio'] ); ?>; --aru-cor-b: <?php echo esc_attr( $e['cores']['fim'] ); ?>;">
				<span class="aru-cartao-evento__dia"><?php echo (int) $e['dia_n']; ?></span>
				<span class="aru-cartao-evento__mes"><?php echo esc_html( $e['mes'] ); ?></span>
				<?php if ( $e['categoria_nome'] ) : ?>
					<span class="aru-cartao-evento__cat"><?php echo esc_html( $e['categoria_nome'] ); ?></span>
				<?php endif; ?>
			</span>
			<span class="aru-cartao-evento__corpo">
				<strong><?php echo esc_html( $e['titulo'] ); ?></strong>
				<small><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?php echo esc_html( $e['datas'] ); ?></small>
				<?php if ( $e['local'] ) : ?>
					<small><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?php echo esc_html( $e['local'] ); ?></small>
				<?php endif; ?>
				<span class="aru-cartao-evento__marcas">
					<?php if ( $minha ) : ?>
						<span class="aru-estado aru-estado--<?php echo esc_attr( $minha['estado'] ); ?>"><?php echo esc_html( $estados[ $minha['estado'] ] ); ?></span>
					<?php elseif ( $e['marcacoes'] && ! $e['terminado'] ) : ?>
						<span class="aru-marca"><i class="fa-solid fa-chair" aria-hidden="true"></i> <?php esc_html_e( 'Marcação de mesa', 'jelly-area-reservada' ); ?></span>
					<?php endif; ?>
					<?php if ( $docs ) : ?>
						<?php /* translators: %d: documentos do evento */ ?>
						<span class="aru-marca"><i class="fa-regular fa-file-lines" aria-hidden="true"></i> <?php echo esc_html( sprintf( _n( '%d documento', '%d documentos', $docs, 'jelly-area-reservada' ), $docs ) ); ?></span>
					<?php endif; ?>
				</span>
			</span>
		</a>
	</li>
	<?php
};
?>

<header class="aru-titulo">
	<h1><?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?></h1>
	<p><?php esc_html_e( 'Os eventos da APIT para os associados: as datas, a marcação de mesa e os documentos de cada um.', 'jelly-area-reservada' ); ?></p>
</header>

<section class="aru-cartao">
	<header class="aru-cartao__cabeca">
		<h2><?php esc_html_e( 'Próximos eventos', 'jelly-area-reservada' ); ?> <b class="aru-conta"><?php echo (int) count( $proximos ); ?></b></h2>
	</header>

	<?php if ( ! $proximos ) : ?>
		<div class="aru-vazio">
			<i class="fa-regular fa-calendar" aria-hidden="true"></i>
			<p><?php esc_html_e( 'Não há eventos marcados por agora. Quando houver, aparecem aqui.', 'jelly-area-reservada' ); ?></p>
		</div>
	<?php else : ?>
		<ul class="aru-cartoes-eventos">
			<?php array_map( $cartao, $proximos ); ?>
		</ul>
	<?php endif; ?>
</section>

<?php if ( $passados ) : ?>
	<section class="aru-cartao">
		<header class="aru-cartao__cabeca">
			<h2><?php esc_html_e( 'Eventos anteriores', 'jelly-area-reservada' ); ?></h2>
		</header>
		<ul class="aru-cartoes-eventos">
			<?php array_map( $cartao, $passados ); ?>
		</ul>
	</section>
<?php endif; ?>
