<?php
/**
 * Um evento na ARU (/area-reservada/eventos/<slug>/).
 *
 * À esquerda, os horários de marcação — um dia por linha, os horários em fila
 * ao lado, 6 a 8 por linha —; por baixo, o resumo do evento e os documentos,
 * os publicados, para descarregar. À direita, só o resumo do que o associado
 * tem marcado (ou o convite para marcar), que acompanha a página ao rolar.
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
// As marcações do associado no evento, pelo dia: é uma por dia do evento.
$minhas  = $e['marcacoes'] ? jelly_ar_marcacoes_do_associado( $e['id'], $uid ) : [];
$estado  = $e['marcacoes'] ? jelly_ar_disponibilidade_estado( $e ) : null;
$aberta  = $estado && in_array( $estado['estado'], [ 'disponivel', 'completo' ], true );
$dias    = $aberta ? jelly_ar_aru_horarios( $e, $minhas ) : [];

// Ainda pode marcar: num dia sem marcação sua, com algum horário livre.
$pode_marcar = (bool) array_filter( $dias, function ( $d ) {
	return ! $d['tem'] && in_array( 'livre', wp_list_pluck( $d['horas'], 'estado' ), true );
} );
// Pela ordem da legenda: disponível, ocupado, a aguardar aprovação, a sua marcação.
$rotulos = [
	'livre'          => __( 'Disponível', 'jelly-area-reservada' ),
	'ocupado'        => __( 'Ocupado', 'jelly-area-reservada' ),
	'minha-pendente' => __( 'Aguarda aprovação', 'jelly-area-reservada' ),
	'minha'          => __( 'A sua marcação', 'jelly-area-reservada' ),
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
		<?php /* ---------- Os horários ---------- */ ?>
		<?php if ( $e['marcacoes'] ) : ?>
			<section class="aru-cartao">
				<header class="aru-cartao__cabeca">
					<h2><i class="fa-regular fa-clock" aria-hidden="true"></i> <?php esc_html_e( 'Horários de marcação', 'jelly-area-reservada' ); ?></h2>
				</header>

				<?php if ( $e['terminado'] ) : ?>
					<p class="aru-nota"><?php esc_html_e( 'O evento já terminou: as marcações fecharam.', 'jelly-area-reservada' ); ?></p>
				<?php elseif ( ! $aberta ) : ?>
					<p class="aru-nota"><?php esc_html_e( 'As mesas e os horários deste evento ainda não estão definidos. Quando estiverem, aparecem aqui.', 'jelly-area-reservada' ); ?></p>
				<?php elseif ( ! $dias ) : ?>
					<p class="aru-nota"><?php esc_html_e( 'Já não há horários por vir neste evento.', 'jelly-area-reservada' ); ?></p>
				<?php else : ?>
					<?php
					/*
					 * Num dia sem marcação sua, um horário livre abre o pop-up já com esse
					 * dia e essa hora (data-ar-dia, data-ar-hora; assets/js/area-reservada.js):
					 * falta só a mesa. Num dia em que já marcou, os horários só se veem —
					 * é uma por dia.
					 */
					?>
					<?php if ( $pode_marcar ) : ?>
						<p class="aru-nota"><?php esc_html_e( 'Escolha um horário disponível para pedir a marcação. Pode fazer uma marcação em cada dia do evento.', 'jelly-area-reservada' ); ?></p>
					<?php else : ?>
						<p class="aru-nota"><?php esc_html_e( 'Já não há dias em que possa marcar: é uma marcação por dia do evento. Os horários ficam aqui para consulta.', 'jelly-area-reservada' ); ?></p>
					<?php endif; ?>

					<?php // Um dia por linha: o dia à esquerda, os horários ao lado. ?>
					<div class="aru-dias">
						<?php foreach ( $dias as $d ) : ?>
							<div class="aru-dia<?php echo $d["tem"] ? " is-marcado" : ""; ?>">
								<h3 class="aru-dia__nome">
									<?php echo esc_html( ucfirst( jelly_ar_data( 'D, j M', strtotime( $d['dia'] ) ) ) ); ?>
									<?php if ( $d['tem'] ) : ?>
										<small class="<?php echo 'pendente' === $d['estado'] ? 'is-pendente' : ''; ?>"><?php echo esc_html( 'pendente' === $d['estado'] ? __( 'Por aprovar', 'jelly-area-reservada' ) : __( 'Já marcado', 'jelly-area-reservada' ) ); ?></small>
									<?php endif; ?>
								</h3>
								<ul class="aru-dia__horas">
									<?php foreach ( $d['horas'] as $h ) : ?>
										<?php $titulo = $d['nome'] . ', ' . $h['hora'] . ': ' . $rotulos[ $h['estado'] ]; ?>
										<li>
											<?php if ( ! $d['tem'] && 'livre' === $h['estado'] ) : ?>
												<?php /* translators: 1: dia, 2: hora */ ?>
												<a class="aru-slot aru-slot--livre" href="<?php echo esc_url( jelly_ar_url_marcacao( $e['id'] ) ); ?>" data-ar-dia="<?php echo esc_attr( $d['dia'] ); ?>" data-ar-hora="<?php echo esc_attr( $h['hora'] ); ?>" title="<?php echo esc_attr( sprintf( __( 'Marcar %1$s, %2$s', 'jelly-area-reservada' ), $d['nome'], $h['hora'] ) ); ?>">
													<?php echo esc_html( $h['hora'] ); ?>
													<span class="screen-reader-text"><?php echo esc_html( sprintf( __( 'Marcar %1$s, %2$s', 'jelly-area-reservada' ), $d['nome'], $h['hora'] ) ); ?></span>
												</a>
											<?php else : ?>
												<span class="aru-slot aru-slot--<?php echo esc_attr( $h['estado'] ); ?>" title="<?php echo esc_attr( $titulo ); ?>">
													<?php echo esc_html( $h['hora'] ); ?>
													<span class="screen-reader-text"><?php echo esc_html( $rotulos[ $h['estado'] ] ); ?></span>
												</span>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endforeach; ?>
					</div>

					<?php // O que cada cor quer dizer, uma vez, por baixo. ?>
					<ul class="aru-legenda" aria-hidden="true">
						<?php foreach ( $rotulos as $chave => $rotulo ) : ?>
							<li><span class="aru-slot aru-slot--<?php echo esc_attr( $chave ); ?> aru-slot--amostra"></span> <?php echo esc_html( $rotulo ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</section>
		<?php endif; ?>

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

	<?php /* ---------- O resumo do que está marcado ---------- */ ?>
	<?php if ( $e['marcacoes'] ) : ?>
		<aside class="aru-cartao aru-evento-resumo">
			<header class="aru-cartao__cabeca">
				<h2>
					<i class="fa-solid fa-chair" aria-hidden="true"></i> <?php esc_html_e( 'As minhas marcações', 'jelly-area-reservada' ); ?>
					<?php if ( count( $minhas ) > 1 ) : ?>
						<b class="aru-conta"><?php echo (int) count( $minhas ); ?></b>
					<?php endif; ?>
				</h2>
			</header>

			<?php // Uma por dia do evento: cada uma com o estado, o dia, a hora e a mesa. ?>
			<?php if ( $minhas ) : ?>
				<ul class="aru-minhas-evento">
					<?php foreach ( $minhas as $m ) : ?>
						<li>
							<span class="aru-estado aru-estado--<?php echo esc_attr( $m['estado'] ); ?>"><?php echo esc_html( $estados[ $m['estado'] ] ?? $m['estado'] ); ?></span>
							<strong><?php echo esc_html( ucfirst( jelly_ar_data( 'l, j \d\e F', strtotime( $m['dia'] ) ) ) ); ?></strong>
							<small><i class="fa-regular fa-clock" aria-hidden="true"></i> <?php echo esc_html( $m['hora'] ); ?> · <i class="fa-solid fa-chair" aria-hidden="true"></i> <?php echo esc_html( jelly_ar_mesa_nome( $m['mesa_id'] ) ); ?></small>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( in_array( 'pendente', wp_list_pluck( $minhas, 'estado' ), true ) ) : ?>
					<p class="aru-nota"><?php esc_html_e( 'As marcações por aprovar aguardam a confirmação da APIT, que será enviada por e-mail.', 'jelly-area-reservada' ); ?></p>
				<?php endif; ?>
			<?php elseif ( $e['terminado'] ) : ?>
				<p class="aru-nota"><?php esc_html_e( 'Não fez marcações neste evento, que já terminou.', 'jelly-area-reservada' ); ?></p>
			<?php elseif ( ! $aberta ) : ?>
				<p class="aru-nota"><?php esc_html_e( 'Ainda não se pode marcar: faltam as mesas ou os horários.', 'jelly-area-reservada' ); ?></p>
			<?php else : ?>
				<p class="aru-nota"><?php esc_html_e( 'Ainda não tem marcações neste evento. Pode fazer uma em cada dia; o pedido fica por aprovar até a equipa o confirmar.', 'jelly-area-reservada' ); ?></p>
			<?php endif; ?>

			<?php // Enquanto houver um dia em que ainda pode marcar: o botão. ?>
			<?php if ( $pode_marcar && ! $e['terminado'] ) : ?>
				<a class="aru-botao aru-evento-resumo__botao" href="<?php echo esc_url( jelly_ar_url_marcacao( $e['id'] ) ); ?>">
					<span><?php echo esc_html( $minhas ? __( 'Marcar noutro dia', 'jelly-area-reservada' ) : __( 'Marcar mesa', 'jelly-area-reservada' ) ); ?></span>
					<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
				</a>
			<?php elseif ( $aberta && ! $e['terminado'] && ! $minhas ) : ?>
				<p class="aru-nota"><?php esc_html_e( 'Todos os horários estão ocupados.', 'jelly-area-reservada' ); ?></p>
			<?php endif; ?>
		</aside>
	<?php endif; ?>
</div>
