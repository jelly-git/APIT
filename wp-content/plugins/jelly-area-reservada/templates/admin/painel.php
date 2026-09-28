<?php
/**
 * Painel: a entrada do back-office (inc/painel.php).
 *
 * De cima para baixo, pela ordem em que se age: os números do que pede
 * atenção; os alertas; o que está por decidir e a agenda da semana; e, ao
 * lado, os próximos eventos, a atividade recente e os números do mês.
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

<div class="jar-painel">
	<div class="jar-painel__principal">
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
							<span class="jar-painel__texto">
								<strong><?php echo esc_html( $m['nome'] ? $m['nome'] : '—' ); ?></strong>
								<?php /* translators: 1: evento, 2: dia, 3: hora, 4: mesa */ ?>
								<small><?php echo esc_html( sprintf( __( 'Marcação · %1$s · %2$s %3$s · %4$s', 'jelly-area-reservada' ), $m['evento'], $dia_curto( $m['dia'] ), $m['hora'], $m['mesa'] ) ); ?></small>
							</span>
							<span class="jar-painel__quando"><?php echo esc_html( $m['pedido'] ); ?></span>
							<a class="jar-btn jar-btn--pequeno" href="<?php echo esc_url( jelly_ar_admin_url( 'marcacoes', [ 'evento' => $m['evento_id'] ] ) ); ?>"><?php esc_html_e( 'Decidir', 'jelly-area-reservada' ); ?></a>
						</li>
					<?php endforeach; ?>
					<?php foreach ( array_slice( $p['registos'], 0, 5 ) as $u ) : ?>
						<li>
							<span class="jar-painel__tipo jar-painel__tipo--registo"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
							<span class="jar-painel__texto">
								<strong><?php echo esc_html( trim( $u['nome'] . ' ' . $u['apelido'] ) ); ?></strong>
								<?php /* translators: %s: empresa ou e-mail */ ?>
								<small><?php echo esc_html( sprintf( __( 'Registo · %s', 'jelly-area-reservada' ), $u['empresa'] ? $u['empresa'] : $u['email'] ) ); ?></small>
							</span>
							<span class="jar-painel__quando"><?php echo esc_html( $u['registo'] ); ?></span>
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

	<aside class="jar-painel__lado">
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
									<?php if ( $total ) : ?>
										<?php /* translators: 1: marcações, 2: horários no total */ ?>
										<span class="jar-painel__barra" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%1$d de %2$d horários marcados', 'jelly-area-reservada' ), $feito, $total ) ); ?>"><span style="width: <?php echo (int) $pct; ?>%"></span></span>
									<?php endif; ?>
								</span>
								<span class="jar-estado jar-estado--<?php echo esc_attr( $x['estado']['estado'] ); ?>"><?php echo esc_html( $textos[ $x['estado']['estado'] ] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<?php
		/* ---------- Os últimos 30 dias: o gráfico e os números ---------- */

		/*
		 * O gráfico em SVG, desenhado aqui: 30 dias, uma linha para os pedidos
		 * de marcação e outra para os acessos, na mesma escala.
		 */
		$serie   = $p['atividade30']['dias'];
		$maximo  = max( 1, max( array_map( 'max', array_map( 'array_values', $serie ) ) ) );
		$largura = 300;
		$altura  = 110;
		$pontos  = function ( $chave ) use ( $serie, $maximo, $largura, $altura ) {
			$r = [];
			$i = 0;
			$n = count( $serie ) - 1;
			foreach ( $serie as $v ) {
				$r[] = round( $i * $largura / $n, 1 ) . ',' . round( $altura - 6 - ( $v[ $chave ] / $maximo ) * ( $altura - 16 ), 1 );
				$i++;
			}

			return $r;
		};
		$linha_marcacoes = $pontos( 'marcacoes' );
		$linha_acessos   = $pontos( 'acessos' );
		$dias_serie      = array_keys( $serie );

		// "▲ 20%" contra os 30 dias anteriores; "novo" quando antes não havia nenhum.
		$comparar = function ( $agora, $antes ) {
			if ( $agora === $antes ) {
				return [ 'igual', __( 'igual ao mês anterior', 'jelly-area-reservada' ) ];
			}
			if ( ! $antes ) {
				return [ 'sobe', __( 'novo este mês', 'jelly-area-reservada' ) ];
			}
			$pct = (int) round( 100 * ( $agora - $antes ) / $antes );

			/* translators: %d: percentagem */
			return [ $pct > 0 ? 'sobe' : 'desce', sprintf( __( '%d%% face ao mês anterior', 'jelly-area-reservada' ), abs( $pct ) ) ];
		};
		$antes    = $p['atividade30']['anterior'];
		$numeros  = [
			[ __( 'Pedidos de marcação', 'jelly-area-reservada' ), $p['numeros']['marcacoes'], $antes['marcacoes'] ],
			[ __( 'Acessos dos associados', 'jelly-area-reservada' ), $p['numeros']['acessos'], $antes['acessos'] ],
			[ __( 'Registos novos', 'jelly-area-reservada' ), $p['numeros']['registos'], $antes['registos'] ],
			[ __( 'Descargas de documentos', 'jelly-area-reservada' ), $p['numeros']['descargas'], $antes['descargas'] ],
		];
		?>
		<section class="jar-cartao">
			<header class="jar-cartao__cabeca">
				<div>
					<h2><?php esc_html_e( 'Últimos 30 dias', 'jelly-area-reservada' ); ?></h2>
					<span class="jar-cartao__meta"><?php esc_html_e( 'Comparado com os 30 dias anteriores', 'jelly-area-reservada' ); ?></span>
				</div>
			</header>

			<figure class="jar-painel__grafico">
				<svg viewBox="0 0 <?php echo (int) $largura; ?> <?php echo (int) $altura; ?>" preserveAspectRatio="none" role="img" aria-label="<?php esc_attr_e( 'Pedidos de marcação e acessos dos associados, por dia, nos últimos 30 dias', 'jelly-area-reservada' ); ?>">
					<defs>
						<linearGradient id="jar-grafico-area" x1="0" y1="0" x2="0" y2="1">
							<stop offset="0" stop-color="#f41892" stop-opacity="0.22"></stop>
							<stop offset="1" stop-color="#f41892" stop-opacity="0"></stop>
						</linearGradient>
					</defs>
					<?php foreach ( [ 0.25, 0.5, 0.75 ] as $g ) : ?>
						<line class="jar-painel__grelha-linha" x1="0" x2="<?php echo (int) $largura; ?>" y1="<?php echo esc_attr( round( 6 + $g * ( $altura - 16 ), 1 ) ); ?>" y2="<?php echo esc_attr( round( 6 + $g * ( $altura - 16 ), 1 ) ); ?>"></line>
					<?php endforeach; ?>
					<polygon fill="url(#jar-grafico-area)" points="<?php echo esc_attr( '0,' . ( $altura - 6 ) . ' ' . implode( ' ', $linha_marcacoes ) . ' ' . $largura . ',' . ( $altura - 6 ) ); ?>"></polygon>
					<polyline class="jar-painel__serie jar-painel__serie--acessos" points="<?php echo esc_attr( implode( ' ', $linha_acessos ) ); ?>"></polyline>
					<polyline class="jar-painel__serie jar-painel__serie--marcacoes" points="<?php echo esc_attr( implode( ' ', $linha_marcacoes ) ); ?>"></polyline>
				</svg>
				<figcaption class="jar-painel__eixo">
					<span><?php echo esc_html( $dia_curto( $dias_serie[0] ) ); ?></span>
					<span><?php echo esc_html( $dia_curto( $dias_serie[14] ) ); ?></span>
					<span><?php esc_html_e( 'Hoje', 'jelly-area-reservada' ); ?></span>
				</figcaption>
				<ul class="jar-painel__legenda">
					<li><span class="jar-painel__ponto jar-painel__ponto--marcacoes"></span> <?php esc_html_e( 'Pedidos de marcação', 'jelly-area-reservada' ); ?></li>
					<li><span class="jar-painel__ponto jar-painel__ponto--acessos"></span> <?php esc_html_e( 'Acessos', 'jelly-area-reservada' ); ?></li>
				</ul>
			</figure>

			<dl class="jar-painel__numeros">
				<?php foreach ( $numeros as $n ) : ?>
					<?php $c = $comparar( (int) $n[1], (int) $n[2] ); ?>
					<div>
						<dt><?php echo esc_html( $n[0] ); ?></dt>
						<dd><?php echo (int) $n[1]; ?></dd>
						<dd class="jar-painel__variacao jar-painel__variacao--<?php echo esc_attr( $c[0] ); ?>">
							<i class="fa-solid <?php echo esc_attr( 'sobe' === $c[0] ? 'fa-arrow-trend-up' : ( 'desce' === $c[0] ? 'fa-arrow-trend-down' : 'fa-minus' ) ); ?>" aria-hidden="true"></i>
							<?php echo esc_html( $c[1] ); ?>
						</dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</section>

		<?php /* ---------- Atividade recente ---------- */ ?>
		<section class="jar-cartao jar-cartao--tabela">
			<header class="jar-cartao__cabeca">
				<div>
					<h2><?php esc_html_e( 'Atividade recente', 'jelly-area-reservada' ); ?></h2>
					<span class="jar-cartao__meta"><?php esc_html_e( 'Marcações, mesas e horários', 'jelly-area-reservada' ); ?></span>
				</div>
			</header>
			<?php if ( ! $p['atividade'] ) : ?>
				<p class="jar-vazio"><?php esc_html_e( 'Ainda não há atividade registada.', 'jelly-area-reservada' ); ?></p>
			<?php else : ?>
				<ol class="jar-painel__atividade">
					<?php foreach ( $p['atividade'] as $h ) : ?>
						<li>
							<span class="jar-painel__icone"><i class="fa-solid <?php echo esc_attr( $icones[ $h['acao'] ] ?? 'fa-circle' ); ?>" aria-hidden="true"></i></span>
							<span class="jar-painel__texto">
								<strong><?php echo esc_html( $h['rotulo'] ); ?></strong>
								<small><?php echo esc_html( $h['resumo'] ); ?></small>
								<small class="jar-painel__autor"><?php echo esc_html( $h['autor'] . ' · ' . $h['quando'] . ( $h['evento'] ? ' · ' . $h['evento'] : '' ) ); ?></small>
							</span>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</section>
	</aside>
</div>
