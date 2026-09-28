<?php
/**
 * Painel: a entrada do back-office (inc/painel.php).
 *
 * De cima para baixo: os números do que pede atenção e os alertas; e, em
 * filas com os cartões à mesma altura, os últimos 30 dias ao lado dos
 * próximos eventos, o que está por decidir ao lado da semana, e a atividade
 * recente a toda a largura.
 */

defined( 'ABSPATH' ) || exit;

$p   = jelly_ar_painel_dados();
$eu  = wp_get_current_user();
$ola = (int) current_time( 'G' ) < 13 ? __( 'Bom dia', 'jelly-area-reservada' ) : ( (int) current_time( 'G' ) < 20 ? __( 'Boa tarde', 'jelly-area-reservada' ) : __( 'Boa noite', 'jelly-area-reservada' ) );
$nome = $eu->first_name ? $eu->first_name : $eu->display_name;

$marcacoes_semana = 0;
foreach ( $p['agenda'] as $lista ) {
	$marcacoes_semana += count( $lista );
}

// "qua, 7 out".
$dia_curto = function ( $ymd ) {
	$d = DateTime::createFromFormat( '!Y-m-d', $ymd );

	return $d ? ucfirst( jelly_ar_data( 'D, j M', $d->getTimestamp() ) ) : $ymd;
};

$icones = [
	'marcacao-criada'    => 'fa-calendar-plus',
	'marcacao-pedida'    => 'fa-paper-plane',
	'marcacao-mudada'    => 'fa-arrow-right-arrow-left',
	'marcacao-removida'  => 'fa-trash-can',
	'marcacao-aprovada'  => 'fa-circle-check',
	'marcacao-rejeitada' => 'fa-circle-xmark',
	'horarios-gravados'  => 'fa-clock',
	'mesa-criada'        => 'fa-plus',
	'mesa-alterada'      => 'fa-pen',
	'mesa-apagada'       => 'fa-trash-can',
];

// Os alertas: o que está mal configurado e impede a área de funcionar como devia.
$alertas = [];
if ( false === $p['smtp'] ) {
	$alertas[] = [ 'fa-envelope-circle-check', __( 'Os e-mails da Área Reservada não saem autenticados: podem ir para o spam ou não chegar.', 'jelly-area-reservada' ), admin_url( 'admin.php?page=wp-mail-smtp' ), __( 'Configurar o SMTP', 'jelly-area-reservada' ) ];
}
foreach ( $p['sem_grelha'] as $s ) {
	$alertas[] = [
		'fa-table-cells-large',
		/* translators: %s: evento */
		sprintf( 'mesas' === $s['falta'] ? __( '%s aceita marcações, mas ainda não tem mesas.', 'jelly-area-reservada' ) : __( '%s aceita marcações, mas ainda não tem horários.', 'jelly-area-reservada' ), $s['titulo'] ),
		jelly_ar_admin_url( 'mesas', [ 'evento' => $s['id'] ] ),
		'mesas' === $s['falta'] ? __( 'Criar mesas', 'jelly-area-reservada' ) : __( 'Definir horários', 'jelly-area-reservada' ),
	];
}
if ( JELLY_AR_EXEMPLO ) {
	$alertas[] = [ 'fa-flask', __( 'Os dados de exemplo continuam ligados: as listas de Utilizadores e Documentos misturam-nos com os reais.', 'jelly-area-reservada' ), '', '' ];
}
?>
<div class="jar-cabeca">
	<div>
		<p class="jar-painel__data"><?php echo esc_html( ucfirst( jelly_ar_data( 'l, j \d\e F \d\e Y', strtotime( current_time( 'Y-m-d' ) ) ) ) ); ?></p>
		<?php /* translators: 1: saudação, 2: nome */ ?>
		<h1 class="jar-cabeca__titulo"><?php echo esc_html( sprintf( __( '%1$s, %2$s', 'jelly-area-reservada' ), $ola, $nome ) ); ?></h1>
		<p class="jar-cabeca__intro"><?php esc_html_e( 'O que está por decidir e o que vem a seguir na Área Reservada.', 'jelly-area-reservada' ); ?></p>
	</div>
</div>

<div class="jar-numeros">
	<?php
	foreach (
		[
			[ __( 'Marcações por aprovar', 'jelly-area-reservada' ), count( $p['pedidos'] ), 'fa-hourglass-half', 'roxo', jelly_ar_admin_url( 'marcacoes' ) ],
			[ __( 'Registos por aprovar', 'jelly-area-reservada' ), count( $p['registos'] ), 'fa-user-clock', 'magenta', jelly_ar_admin_url( 'utilizadores', [ 'estado' => 'pendente' ] ) ],
			[ __( 'Marcações em 7 dias', 'jelly-area-reservada' ), $marcacoes_semana, 'fa-calendar-days', 'azul', jelly_ar_admin_url( 'calendario' ) ],
			[ __( 'Associados ativos', 'jelly-area-reservada' ), $p['numeros']['ativos'], 'fa-user-group', 'turquesa', jelly_ar_admin_url( 'utilizadores', [ 'estado' => 'ativo' ] ) ],
		] as $n
	) :
		?>
		<a class="jar-cartao jar-numero" href="<?php echo esc_url( $n[4] ); ?>">
			<span class="jar-icone jar-icone--<?php echo esc_attr( $n[3] ); ?>"><i class="fa-solid <?php echo esc_attr( $n[2] ); ?>" aria-hidden="true"></i></span>
			<span class="jar-numero__rotulo"><?php echo esc_html( $n[0] ); ?></span>
			<strong class="jar-numero__valor"><?php echo (int) $n[1]; ?></strong>
		</a>
	<?php endforeach; ?>
</div>

<?php if ( $alertas ) : ?>
	<section class="jar-cartao jar-painel__alertas" aria-label="<?php esc_attr_e( 'Alertas', 'jelly-area-reservada' ); ?>">
		<?php foreach ( $alertas as $a ) : ?>
			<div class="jar-painel__alerta">
				<i class="fa-solid <?php echo esc_attr( $a[0] ); ?>" aria-hidden="true"></i>
				<p><?php echo esc_html( $a[1] ); ?></p>
				<?php if ( $a[2] ) : ?>
					<a class="jar-btn jar-btn--pequeno jar-btn--contorno" href="<?php echo esc_url( $a[2] ); ?>"><?php echo esc_html( $a[3] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</section>
<?php endif; ?>


<?php
/*
 * Em filas, cada uma com os cartões à mesma altura: a atividade do mês ao
 * lado dos próximos eventos; o que está por decidir ao lado da semana; e a
 * atividade recente a toda a largura.
 */
?>
<div class="jar-painel">
	<div class="jar-painel__fila jar-painel__fila--2-1">
		<?php
		/*
		 * ---------- A atividade do mês: o gráfico e os totais ----------
		 *
		 * Tudo vem de jelly_ar_painel_mes() (inc/painel.php). O gráfico
		 * desenha-se no browser (assets/js/admin.js, data-jar-grafico), e as
		 * setas trazem outro mês por Ajax (data-jar-painel-mes), sem recarregar
		 * a página: o título, os totais, as setas e o gráfico mudam no lugar.
		 * Sem JavaScript, as setas são ligações normais.
		 */
		$c        = $p['mes'];
		$url_mes  = function ( $mes ) use ( $c ) {
			return jelly_ar_admin_url( 'painel', $mes && $mes !== $c['atual'] ? [ 'mes' => $mes ] : [] ) . '#jar-painel-mes';
		};
		$totais = [
			[ 'marcacoes', __( 'Pedidos de marcação', 'jelly-area-reservada' ), 'fa-calendar-check', 'magenta' ],
			[ 'acessos', __( 'Acessos dos associados', 'jelly-area-reservada' ), 'fa-right-to-bracket', 'roxo' ],
			[ 'registos', __( 'Registos novos', 'jelly-area-reservada' ), 'fa-user-plus', 'azul' ],
			[ 'descargas', __( 'Descargas de documentos', 'jelly-area-reservada' ), 'fa-download', 'turquesa' ],
		];
		?>
		<section class="jar-cartao" id="jar-painel-mes" data-jar-painel-mes data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'jelly_ar_painel_mes' ) ); ?>">
			<header class="jar-cartao__cabeca jar-cartao__cabeca--acao">
				<div>
					<h2 data-jar-painel-mes-titulo><?php echo esc_html( $c['titulo'] ); ?></h2>
					<span class="jar-cartao__meta"><?php esc_html_e( 'Do dia 1 até hoje, ou ao fim do mês. Passe o rato no gráfico para ver cada dia.', 'jelly-area-reservada' ); ?></span>
				</div>
				<span class="jar-painel__meses">
					<a class="jar-acao" href="<?php echo esc_url( $url_mes( $c['anterior'] ) ); ?>" data-jar-painel-ir="<?php echo esc_attr( $c['anterior'] ); ?>" data-jar-painel-seta="anterior" aria-label="<?php esc_attr_e( 'Mês anterior', 'jelly-area-reservada' ); ?>" title="<?php esc_attr_e( 'Mês anterior', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
					<?php // O seguinte só até ao mês de hoje; no mês de hoje fica desligado. ?>
					<a class="jar-acao" href="<?php echo esc_url( $c['seguinte'] ? $url_mes( $c['seguinte'] ) : '#jar-painel-mes' ); ?>" data-jar-painel-ir="<?php echo esc_attr( $c['seguinte'] ); ?>" data-jar-painel-seta="seguinte" <?php echo $c['seguinte'] ? '' : 'aria-disabled="true"'; ?> aria-label="<?php esc_attr_e( 'Mês seguinte', 'jelly-area-reservada' ); ?>" title="<?php echo esc_attr( $c['seguinte'] ? __( 'Mês seguinte', 'jelly-area-reservada' ) : __( 'Já é o mês atual', 'jelly-area-reservada' ) ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
				</span>
			</header>

			<div class="jar-painel__trinta">
				<figure class="jar-grafico" data-jar-grafico>
					<?php // A legenda é também o interruptor de cada linha. ?>
					<div class="jar-grafico__legenda" role="group" aria-label="<?php esc_attr_e( 'Mostrar ou esconder', 'jelly-area-reservada' ); ?>">
						<?php foreach ( $c['grafico']['series'] as $s ) : ?>
							<button type="button" class="jar-grafico__serie" data-jar-grafico-serie="<?php echo esc_attr( $s['chave'] ); ?>" aria-pressed="true" style="--jar-serie: <?php echo esc_attr( $s['cor'] ); ?>;">
								<span aria-hidden="true"></span> <?php echo esc_html( $s['nome'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>
					<div class="jar-grafico__area" data-jar-grafico-area tabindex="0" role="img" aria-label="<?php esc_attr_e( 'Pedidos de marcação e acessos dos associados, por dia, neste mês. Use as setas para percorrer os dias.', 'jelly-area-reservada' ); ?>">
						<div class="jar-grafico__dica" data-jar-grafico-dica hidden></div>
					</div>
					<script type="application/json" data-jar-grafico-dados><?php echo wp_json_encode( $c['grafico'], JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
				</figure>

				<ul class="jar-painel__totais">
					<?php foreach ( $totais as $t ) : ?>
						<li>
							<span class="jar-icone jar-icone--<?php echo esc_attr( $t[3] ); ?>"><i class="fa-solid <?php echo esc_attr( $t[2] ); ?>" aria-hidden="true"></i></span>
							<span>
								<strong data-jar-painel-total="<?php echo esc_attr( $t[0] ); ?>"><?php echo (int) $c['totais'][ $t[0] ]; ?></strong>
								<small><?php echo esc_html( $t[1] ); ?></small>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php /* ---------- Próximos eventos ---------- */ ?>
		<section class="jar-cartao jar-cartao--tabela">
			<header class="jar-cartao__cabeca">
				<div>
					<h2><?php esc_html_e( 'Próximos eventos', 'jelly-area-reservada' ); ?></h2>
					<span class="jar-cartao__meta"><?php esc_html_e( 'Com marcações de mesa', 'jelly-area-reservada' ); ?></span>
				</div>
			</header>
			<?php if ( ! $p['proximos'] ) : ?>
				<p class="jar-vazio"><?php esc_html_e( 'Nenhum evento por vir aceita marcações.', 'jelly-area-reservada' ); ?></p>
			<?php else : ?>
				<ul class="jar-painel__eventos">
					<?php foreach ( $p['proximos'] as $x ) : ?>
						<?php
						$e     = $x['evento'];
						$total = (int) $x['resumo']['lugares'];
						$feito = (int) ( $x['resumo']['confirmadas'] + $x['resumo']['pendentes'] );
						$pct   = $total ? min( 100, (int) round( 100 * $feito / $total ) ) : 0;
						$textos = [
							/* translators: %d: horários livres */
							'disponivel' => sprintf( _n( '%d horário livre', '%d horários livres', $x['estado']['livres'], 'jelly-area-reservada' ), $x['estado']['livres'] ),
							'completo'   => __( 'Completo', 'jelly-area-reservada' ),
							'terminado'  => __( 'Terminado', 'jelly-area-reservada' ),
							'sem-grelha' => __( 'Sem grelha', 'jelly-area-reservada' ),
						];
						?>
						<li>
							<a href="<?php echo esc_url( jelly_ar_admin_url( 'mesas', [ 'evento' => $e['id'] ] ) ); ?>">
								<span class="jar-evento-capa" style="--jar-cat-inicio: <?php echo esc_attr( $e['cores']['inicio'] ); ?>; --jar-cat-fim: <?php echo esc_attr( $e['cores']['fim'] ); ?>;" aria-hidden="true"></span>
								<span class="jar-painel__texto">
									<strong><?php echo esc_html( $e['titulo'] ); ?></strong>
									<small><?php echo esc_html( jelly_ar_intervalo_datas( $e['inicio'], $e['fim'] ) . ( $e['local'] ? ' · ' . $e['local'] : '' ) ); ?></small>
									<?php // A disponibilidade e a ocupação por baixo do nome, para o nome não se cortar num cartão estreito. ?>
									<span class="jar-painel__ocupacao">
										<span class="jar-estado jar-estado--<?php echo esc_attr( $x['estado']['estado'] ); ?>"><?php echo esc_html( $textos[ $x['estado']['estado'] ] ); ?></span>
										<?php if ( $total ) : ?>
											<?php /* translators: 1: marcações, 2: horários no total */ ?>
											<span class="jar-painel__barra" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%1$d de %2$d horários marcados', 'jelly-area-reservada' ), $feito, $total ) ); ?>"><span style="width: <?php echo (int) $pct; ?>%"></span></span>
										<?php endif; ?>
									</span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</div>

	<div class="jar-painel__fila jar-painel__fila--1-1">
		<?php /* ---------- Por decidir ---------- */ ?>
		<section class="jar-cartao jar-cartao--tabela">
			<header class="jar-cartao__cabeca">
				<div>
					<h2><?php esc_html_e( 'Por decidir', 'jelly-area-reservada' ); ?></h2>
					<span class="jar-cartao__meta"><?php esc_html_e( 'Os pedidos mais antigos primeiro: são os que esperam há mais tempo.', 'jelly-area-reservada' ); ?></span>
				</div>
			</header>

			<?php if ( ! $p['pedidos'] && ! $p['registos'] ) : ?>
				<div class="jar-painel__vazio">
					<span class="jar-icone jar-icone--turquesa"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
					<p><strong><?php esc_html_e( 'Nada por decidir', 'jelly-area-reservada' ); ?></strong><?php esc_html_e( 'Não há pedidos de marcação nem registos à espera.', 'jelly-area-reservada' ); ?></p>
				</div>
			<?php else : ?>
				<ul class="jar-painel__lista">
					<?php foreach ( array_slice( array_reverse( $p['pedidos'] ), 0, 5 ) as $m ) : ?>
						<li>
							<span class="jar-painel__tipo jar-painel__tipo--marcacao"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span>
							<?php // Como no Calendário: o nome, a empresa (discreta) e a mesa (entre os dois), com o evento, o dia e a hora. ?>
							<span class="jar-painel__texto jar-painel__pessoa">
								<strong><?php echo esc_html( $m['nome'] ? $m['nome'] : '—' ); ?></strong>
								<?php if ( $m['empresa'] ) : ?>
									<small><?php echo esc_html( $m['empresa'] ); ?></small>
								<?php endif; ?>
								<span class="jar-painel__mesa">
									<i class="fa-solid fa-chair" aria-hidden="true"></i>
									<?php echo esc_html( $m['mesa'] ); ?>
								</span>
								<?php // O evento, o dia e a hora, e quando chegou o pedido: uma linha discreta por baixo. ?>
								<?php /* translators: 1: evento, 2: dia, 3: hora, 4: data do pedido */ ?>
								<small class="jar-painel__detalhe"><?php echo esc_html( sprintf( __( '%1$s · %2$s, %3$s · pedido a %4$s', 'jelly-area-reservada' ), $m['evento'], $dia_curto( $m['dia'] ), $m['hora'], $m['pedido'] ) ); ?></small>
							</span>
							<a class="jar-btn jar-btn--pequeno" href="<?php echo esc_url( jelly_ar_admin_url( 'marcacoes', [ 'evento' => $m['evento_id'] ] ) ); ?>"><?php esc_html_e( 'Ver pedido', 'jelly-area-reservada' ); ?></a>
						</li>
					<?php endforeach; ?>
					<?php foreach ( array_slice( $p['registos'], 0, 5 ) as $u ) : ?>
						<li>
							<span class="jar-painel__tipo jar-painel__tipo--registo"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
							<span class="jar-painel__texto jar-painel__pessoa">
								<strong><?php echo esc_html( trim( $u['nome'] . ' ' . $u['apelido'] ) ); ?></strong>
								<small><?php echo esc_html( $u['empresa'] ? $u['empresa'] : $u['email'] ); ?></small>
								<span class="jar-painel__mesa">
									<i class="fa-solid fa-user-plus" aria-hidden="true"></i>
									<?php esc_html_e( 'Pedido de registo', 'jelly-area-reservada' ); ?>
								</span>
								<?php /* translators: %s: data do pedido */ ?>
								<small class="jar-painel__detalhe"><?php echo esc_html( sprintf( __( 'pedido a %s', 'jelly-area-reservada' ), $u['registo'] ) ); ?></small>
							</span>
							<a class="jar-btn jar-btn--pequeno" href="<?php echo esc_url( jelly_ar_admin_url( 'utilizadores', [ 'utilizador' => $u['id'] ] ) ); ?>"><?php esc_html_e( 'Analisar', 'jelly-area-reservada' ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( count( $p['pedidos'] ) > 5 || count( $p['registos'] ) > 5 ) : ?>
					<footer class="jar-painel__mais">
						<?php if ( count( $p['pedidos'] ) > 5 ) : ?>
							<?php /* translators: %d: número de pedidos */ ?>
							<a class="jar-link" href="<?php echo esc_url( jelly_ar_admin_url( 'marcacoes' ) ); ?>"><?php echo esc_html( sprintf( __( 'Ver os %d pedidos de marcação', 'jelly-area-reservada' ), count( $p['pedidos'] ) ) ); ?></a>
						<?php endif; ?>
						<?php if ( count( $p['registos'] ) > 5 ) : ?>
							<?php /* translators: %d: número de registos */ ?>
							<a class="jar-link" href="<?php echo esc_url( jelly_ar_admin_url( 'utilizadores', [ 'estado' => 'pendente' ] ) ); ?>"><?php echo esc_html( sprintf( __( 'Ver os %d registos', 'jelly-area-reservada' ), count( $p['registos'] ) ) ); ?></a>
						<?php endif; ?>
					</footer>
				<?php endif; ?>
			<?php endif; ?>
		</section>

		<?php /* ---------- Agenda dos próximos 7 dias ---------- */ ?>
		<section class="jar-cartao jar-cartao--tabela">
			<header class="jar-cartao__cabeca jar-cartao__cabeca--acao">
				<div>
					<h2><?php esc_html_e( 'Próximos 7 dias', 'jelly-area-reservada' ); ?></h2>
					<span class="jar-cartao__meta"><?php esc_html_e( 'As marcações de mesa, dia a dia.', 'jelly-area-reservada' ); ?></span>
				</div>
				<a class="jar-btn jar-btn--pequeno jar-btn--contorno" href="<?php echo esc_url( jelly_ar_admin_url( 'calendario' ) ); ?>"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> <?php esc_html_e( 'Calendário', 'jelly-area-reservada' ); ?></a>
			</header>

			<?php if ( ! $p['agenda'] ) : ?>
				<p class="jar-vazio"><?php esc_html_e( 'Não há marcações nos próximos 7 dias.', 'jelly-area-reservada' ); ?></p>
			<?php else : ?>
				<div class="jar-painel__agenda">
					<?php foreach ( $p['agenda'] as $dia => $lista ) : ?>
						<div class="jar-painel__dia">
							<h3><?php echo esc_html( $dia_curto( $dia ) ); ?> <b><?php echo (int) count( $lista ); ?></b></h3>
							<ul>
								<?php foreach ( array_slice( $lista, 0, 6 ) as $m ) : ?>
									<li class="jar-painel__marcacao--<?php echo esc_attr( $m['estado'] ); ?>">
										<span class="jar-painel__hora"><?php echo esc_html( $m['hora'] ); ?></span>
										<span class="jar-painel__texto">
											<strong><?php echo esc_html( $m['quem'] ? $m['quem'] : __( 'Associado removido', 'jelly-area-reservada' ) ); ?></strong>
											<small><?php echo esc_html( implode( ' · ', array_filter( [ $m['mesa'], $m['evento'] ] ) ) ); ?></small>
										</span>
										<?php if ( 'pendente' === $m['estado'] ) : ?>
											<span class="jar-estado jar-estado--pendente"><?php esc_html_e( 'Por aprovar', 'jelly-area-reservada' ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
								<?php if ( count( $lista ) > 6 ) : ?>
									<?php /* translators: %d: marcações que não se mostram */ ?>
									<li class="jar-painel__resto"><a class="jar-link" href="<?php echo esc_url( jelly_ar_admin_url( 'calendario', [ 'mes' => substr( $dia, 0, 7 ) ] ) ); ?>"><?php echo esc_html( sprintf( __( 'e mais %d', 'jelly-area-reservada' ), count( $lista ) - 6 ) ); ?></a></li>
								<?php endif; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	</div>

	<?php /* ---------- Atividade recente: a toda a largura, em tabela ---------- */ ?>
	<section class="jar-cartao jar-cartao--tabela jar-painel__atividade-cartao">
		<header class="jar-cartao__cabeca">
			<div>
				<h2><?php esc_html_e( 'Atividade recente', 'jelly-area-reservada' ); ?></h2>
				<span class="jar-cartao__meta"><?php esc_html_e( 'As últimas ações nas marcações, nas mesas e nos horários de todos os eventos.', 'jelly-area-reservada' ); ?></span>
			</div>
		</header>
		<?php if ( ! $p['atividade'] ) : ?>
			<p class="jar-vazio"><?php esc_html_e( 'Ainda não há atividade registada.', 'jelly-area-reservada' ); ?></p>
		<?php else : ?>
			<table class="jar-tabela">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Ação', 'jelly-area-reservada' ); ?></th>
						<th><?php esc_html_e( 'Detalhe', 'jelly-area-reservada' ); ?></th>
						<th class="jar-col--evento"><?php esc_html_e( 'Evento', 'jelly-area-reservada' ); ?></th>
						<th class="jar-col--por"><?php esc_html_e( 'Por', 'jelly-area-reservada' ); ?></th>
						<th class="jar-col--data"><?php esc_html_e( 'Quando', 'jelly-area-reservada' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $p['atividade'] as $h ) : ?>
						<tr>
							<td class="jar-painel__acao"><span class="jar-painel__icone"><i class="fa-solid <?php echo esc_attr( $icones[ $h['acao'] ] ?? 'fa-circle' ); ?>" aria-hidden="true"></i></span> <?php echo esc_html( $h['rotulo'] ); ?></td>
							<td><?php echo esc_html( $h['resumo'] ); ?></td>
							<td class="jar-col--evento"><?php echo esc_html( $h['evento'] ? $h['evento'] : '—' ); ?></td>
							<td class="jar-col--por"><?php echo esc_html( $h['autor'] ); ?></td>
							<td class="jar-tabela__num jar-col--data"><?php jelly_ar_data_hora( $h['quando'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>
</div>
