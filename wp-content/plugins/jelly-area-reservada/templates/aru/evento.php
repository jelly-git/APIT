<?php
/**
 * Um evento na ARU (/area-reservada/eventos/<id>/): as datas, o local e o
 * resumo; a marcação de mesa — a do associado, se a tiver, e os horários de
 * cada dia —; e os documentos do evento, os publicados, para descarregar.
 *
 * Só chegam aqui os eventos da Área Reservada (jelly_ar_aru_evento()).
 *
 * @var array $aru    secao, user, pagina, evento.
 * @var array $pessoa jelly_ar_aru_pessoa().
 */

defined( 'ABSPATH' ) || exit;

$e       = $aru['evento'];
$uid     = $aru['user']->ID;
$estados = jelly_ar_aru_estados();
$docs    = jelly_ar_evento_documentos( $e['id'], true );
$minha   = $e['marcacoes'] ? jelly_ar_marcacao_do_associado( $e['id'], $uid ) : null;
$estado  = $e['marcacoes'] ? jelly_ar_disponibilidade_estado( $e ) : null;
$aberta  = $estado && in_array( $estado['estado'], [ 'disponivel', 'completo' ], true );
$dias    = $aberta ? jelly_ar_aru_horarios( $e, $minha ) : [];
$rotulos = [
	'livre'   => __( 'Disponível', 'jelly-area-reservada' ),
	'ocupado' => __( 'Ocupado', 'jelly-area-reservada' ),
	'minha'   => __( 'A sua marcação', 'jelly-area-reservada' ),
];
?>

<a class="aru-voltar" href="<?php echo esc_url( jelly_ar_area_url( 'eventos' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?></a>

<section class="aru-evento-topo" style="--aru-cor-a: <?php echo esc_attr( $e['cores']['inicio'] ); ?>; --aru-cor-b: <?php echo esc_attr( $e['cores']['fim'] ); ?>;">
	<span class="aru-evento-topo__data">
		<strong><?php echo (int) $e['dia_n']; ?></strong>
		<small><?php echo esc_html( $e['mes'] ); ?></small>
	</span>
	<div class="aru-evento-topo__texto">
		<?php if ( $e['categoria_nome'] ) : ?>
			<span class="aru-evento-topo__cat"><?php echo esc_html( $e['categoria_nome'] ); ?></span>
		<?php endif; ?>
		<h1><?php echo esc_html( $e['titulo'] ); ?></h1>
		<ul class="aru-evento-topo__factos">
			<li><i class="fa-regular fa-calendar" aria-hidden="true"></i> <?php echo esc_html( $e['datas'] ); ?></li>
			<?php if ( $e['local'] ) : ?>
				<li><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?php echo esc_html( $e['local'] ); ?></li>
			<?php endif; ?>
			<?php if ( $e['terminado'] ) : ?>
				<li><i class="fa-solid fa-flag-checkered" aria-hidden="true"></i> <?php esc_html_e( 'Já terminou', 'jelly-area-reservada' ); ?></li>
			<?php endif; ?>
		</ul>
	</div>
</section>

<div class="aru-evento-grelha">
	<div class="aru-evento-grelha__principal">
		<?php if ( $e['resumo'] ) : ?>
			<section class="aru-cartao">
				<header class="aru-cartao__cabeca">
					<h2><i class="fa-regular fa-file-lines" aria-hidden="true"></i> <?php esc_html_e( 'Sobre o evento', 'jelly-area-reservada' ); ?></h2>
				</header>
				<div class="aru-texto"><?php echo wp_kses_post( wpautop( $e['resumo'] ) ); ?></div>
			</section>
		<?php endif; ?>

		<?php /* ---------- Os documentos do evento ---------- */ ?>
		<section class="aru-cartao">
			<header class="aru-cartao__cabeca">
				<h2><i class="fa-solid fa-folder-open" aria-hidden="true"></i> <?php esc_html_e( 'Documentos do evento', 'jelly-area-reservada' ); ?> <?php if ( $docs ) : ?><b class="aru-conta"><?php echo (int) count( $docs ); ?></b><?php endif; ?></h2>
			</header>

			<?php if ( ! $docs ) : ?>
				<div class="aru-vazio">
					<i class="fa-regular fa-folder-open" aria-hidden="true"></i>
					<p><?php esc_html_e( 'Este evento ainda não tem documentos.', 'jelly-area-reservada' ); ?></p>
				</div>
			<?php else : ?>
				<ul class="aru-documentos">
					<?php foreach ( $docs as $d ) : ?>
						<li class="aru-documento">
							<span class="aru-documento__icone aru-tipo--<?php echo esc_attr( $d['tipo'] ); ?>"><i class="fa-solid <?php echo esc_attr( jelly_ar_aru_icone( $d['tipo'] ) ); ?>" aria-hidden="true"></i></span>
							<span class="aru-documento__texto">
								<strong><?php echo esc_html( $d['titulo'] ); ?></strong>
								<?php if ( $d['descricao'] ) : ?>
									<span><?php echo esc_html( $d['descricao'] ); ?></span>
								<?php endif; ?>
								<small><?php echo esc_html( strtoupper( $d['tipo'] ) . ' · ' . size_format( $d['tamanho'], 1 ) ); ?></small>
							</span>
							<?php /* translators: %s: título do documento */ ?>
							<a class="aru-botao aru-botao--pequeno" href="<?php echo esc_url( jelly_ar_url_descarregar( $d['id'] ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Descarregar %s', 'jelly-area-reservada' ), $d['titulo'] ) ); ?>">
								<i class="fa-solid fa-download" aria-hidden="true"></i> <span><?php esc_html_e( 'Descarregar', 'jelly-area-reservada' ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</div>

	<?php /* ---------- A marcação de mesa ---------- */ ?>
	<?php if ( $e['marcacoes'] ) : ?>
		<section class="aru-cartao aru-evento-marcacao">
			<header class="aru-cartao__cabeca">
				<h2><i class="fa-solid fa-chair" aria-hidden="true"></i> <?php esc_html_e( 'Marcação de mesa', 'jelly-area-reservada' ); ?></h2>
			</header>

			<?php if ( $minha ) : ?>
				<div class="aru-evento-marcacao__minha">
					<span class="aru-estado aru-estado--<?php echo esc_attr( $minha['estado'] ); ?>"><?php echo esc_html( $estados[ $minha['estado'] ] ?? $minha['estado'] ); ?></span>
					<strong><?php echo esc_html( ucfirst( jelly_ar_data( 'l, j \d\e F', strtotime( $minha['dia'] ) ) ) . ' · ' . $minha['hora'] ); ?></strong>
					<small><i class="fa-solid fa-chair" aria-hidden="true"></i> <?php echo esc_html( jelly_ar_mesa_nome( $minha['mesa_id'] ) ); ?></small>
				</div>
			<?php endif; ?>

			<?php if ( $e['terminado'] ) : ?>
				<p class="aru-nota"><?php esc_html_e( 'O evento já terminou: as marcações fecharam.', 'jelly-area-reservada' ); ?></p>
			<?php elseif ( ! $aberta ) : ?>
				<p class="aru-nota"><?php esc_html_e( 'As mesas e os horários deste evento ainda não estão definidos. Quando estiverem, pode marcar aqui.', 'jelly-area-reservada' ); ?></p>
			<?php else : ?>
				<?php if ( ! $minha ) : ?>
					<p class="aru-nota">
						<?php
						echo esc_html(
							'completo' === $estado['estado']
								? __( 'Todos os horários estão ocupados.', 'jelly-area-reservada' )
								: __( 'Escolha a mesa e a hora; o pedido fica por aprovar até a equipa o confirmar.', 'jelly-area-reservada' )
						);
						?>
					</p>
				<?php endif; ?>

				<?php if ( 'completo' !== $estado['estado'] || $minha ) : ?>
					<a class="aru-botao aru-evento-marcacao__botao" href="<?php echo esc_url( jelly_ar_url_marcacao( $e['id'] ) ); ?>">
						<span><?php echo esc_html( $minha ? __( 'Ver a minha marcação', 'jelly-area-reservada' ) : __( 'Marcar mesa', 'jelly-area-reservada' ) ); ?></span>
						<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<?php foreach ( $dias as $d ) : ?>
					<div class="aru-evento-marcacao__dia">
						<h3><?php echo esc_html( $d['nome'] ); ?></h3>
						<ul class="aru-horarios__lista">
							<?php foreach ( $d['horas'] as $h ) : ?>
								<li class="aru-hora aru-hora--<?php echo esc_attr( $h['estado'] ); ?>">
									<strong><?php echo esc_html( $h['hora'] ); ?></strong>
									<span><?php echo esc_html( $rotulos[ $h['estado'] ] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</div>
