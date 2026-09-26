<?php
/**
 * Utilizadores: a lista, o perfil de um deles (`utilizador=<id>`) e o
 * formulário de um novo (`novo=1`).
 *
 * Por onde a área se liga às outras:
 * - os pedidos chegam do formulário de registo do pop-up do site, com o estado
 *   "por aprovar" e a data e hora do registo;
 * - aprovar guarda a data e hora e quem aprovou, e envia o e-mail para definir
 *   a palavra-passe; só então se entra no login do mesmo pop-up;
 * - cada entrada fica na tabela de acessos, que o perfil mostra e a exportação
 *   descarrega;
 * - as marcações de mesa do utilizador aparecem no perfil e levam às
 *   Aprovações, quando essa área existir.
 */

defined( 'ABSPATH' ) || exit;

$utilizadores = jelly_ar_utilizadores_todos();
$id           = isset( $_GET['utilizador'] ) ? absint( $_GET['utilizador'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$novo         = ! empty( $_GET['novo'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$u = null;
if ( $id ) {
	$u = current( array_filter( $utilizadores, function ( $x ) use ( $id ) {
		return $x['id'] === $id;
	} ) );
}

/*
 * O que volta de uma decisão (jelly_ar_utilizador_estado()): um aviso de
 * sucesso, ou o erro. Aprovar sem o e-mail sair é um erro: sem ele, a pessoa
 * não tem como definir a palavra-passe.
 */
$aviso   = isset( $_GET['aviso'] ) ? sanitize_key( wp_unslash( $_GET['aviso'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$erro    = isset( $_GET['erro'] ) ? sanitize_key( wp_unslash( $_GET['erro'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$avisos  = [
	'aprovado'  => __( 'Registo aprovado. Foi enviado o e-mail para definir a palavra-passe.', 'jelly-area-reservada' ),
	'rejeitado' => __( 'Pedido rejeitado. Foi enviado um e-mail a avisar.', 'jelly-area-reservada' ),
	'suspenso'  => __( 'Acesso suspenso: o login da área reservada recusa este e-mail até ser reativado.', 'jelly-area-reservada' ),
	'reativado' => __( 'Acesso reativado. A palavra-passe continua a mesma.', 'jelly-area-reservada' ),
	'apagado'   => __( 'Pedido apagado, com a conta e os dados do registo.', 'jelly-area-reservada' ),
];
$erros   = [
	'aprovado-sem-email' => __( 'Registo aprovado, mas o e-mail para definir a palavra-passe não saiu. Confirme o envio no WP Mail SMTP; a pessoa pode pedir outra ligação em "Esqueceu-se da palavra-passe?".', 'jelly-area-reservada' ),
	'decisao'            => __( 'Essa mudança não é possível a partir do estado atual.', 'jelly-area-reservada' ),
];
$mostrar_aviso = function () use ( $aviso, $erro, $avisos, $erros ) {
	if ( isset( $avisos[ $aviso ] ) ) {
		printf( '<div class="jar-aviso jar-aviso--sucesso" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><p>%s</p></div>', esc_html( $avisos[ $aviso ] ) );
	}

	$e = $erros[ $erro ] ?? ( $erros[ $aviso ] ?? '' );
	if ( $e ) {
		printf( '<div class="jar-aviso jar-aviso--suspenso" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><p>%s</p></div>', esc_html( $e ) );
	}
};

// Os cinco campos do registo, iguais aos do formulário do site.
$campos = [
	'nome'     => [ __( 'Nome', 'jelly-area-reservada' ), 'text', true, '' ],
	'apelido'  => [ __( 'Apelido', 'jelly-area-reservada' ), 'text', true, '' ],
	'email'    => [ __( 'E-mail', 'jelly-area-reservada' ), 'email', true, 'jar-campo--largo' ],
	'telefone' => [ __( 'Telefone', 'jelly-area-reservada' ), 'tel', false, '' ],
	'empresa'  => [ __( 'Empresa', 'jelly-area-reservada' ), 'text', false, '' ],
];

$formulario = function ( $u ) use ( $campos ) {
	?>
	<div class="jar-form">
		<?php foreach ( $campos as $nome => $c ) : ?>
			<label class="jar-campo <?php echo esc_attr( $c[3] ); ?>">
				<span><?php echo esc_html( $c[0] ); ?><?php echo $c[2] ? ' <i aria-hidden="true">*</i>' : ''; ?></span>
				<input
					type="<?php echo esc_attr( $c[1] ); ?>"
					name="<?php echo esc_attr( $nome ); ?>"
					value="<?php echo esc_attr( $u[ $nome ] ?? '' ); ?>"
					<?php echo 'telefone' === $nome ? 'placeholder="+351 912345678"' : ''; ?>
					<?php echo $c[2] ? 'required' : ''; ?>
				>
			</label>
		<?php endforeach; ?>
	</div>
	<?php
};

/* ---------------------------------------------------------------- Novo */

if ( $novo ) :
	?>
	<div class="jar-cabeca">
		<div>
			<a class="jar-voltar" href="<?php echo esc_url( jelly_ar_admin_url( 'utilizadores' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Utilizadores', 'jelly-area-reservada' ); ?></a>
			<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Novo utilizador', 'jelly-area-reservada' ); ?></h1>
			<p class="jar-cabeca__intro"><?php esc_html_e( 'Para dar acesso a alguém sem esperar que se registe no site.', 'jelly-area-reservada' ); ?></p>
		</div>
	</div>

	<form class="jar-cartao jar-cartao--estreito" onsubmit="return false">
		<header class="jar-cartao__cabeca"><h2><?php esc_html_e( 'Dados', 'jelly-area-reservada' ); ?></h2></header>
		<?php $formulario( [] ); ?>
		<label class="jar-caixa">
			<input type="checkbox" checked>
			<span><?php esc_html_e( 'Enviar já o e-mail para definir a palavra-passe', 'jelly-area-reservada' ); ?></span>
		</label>
		<footer class="jar-cartao__pe">
			<a class="jar-btn jar-btn--contorno" href="<?php echo esc_url( jelly_ar_admin_url( 'utilizadores' ) ); ?>"><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></a>
			<button type="submit" class="jar-btn"><?php esc_html_e( 'Criar utilizador', 'jelly-area-reservada' ); ?></button>
		</footer>
	</form>
	<?php
	return;
endif;

/* ---------------------------------------------------------------- Perfil */

if ( $u ) :
	$completo  = $u['nome'] . ' ' . $u['apelido'];
	$marcacoes = jelly_ar_exemplo_marcacoes_de( $u['id'] );
	$acessos   = jelly_ar_acessos_de( $u );

	/*
	 * O que se pode fazer em cada estado. Suspender e rejeitar pedem
	 * confirmação e ficam discretos: são o contrário do que se faz todos os
	 * dias, e não devem estar ao alcance de um clique distraído.
	 */
	/*
	 * Num utilizador real, cada botão envia o seu formulário escondido
	 * (jelly_ar_form_decidir(), inc/utilizadores.php) — os de confirmação, depois
	 * de confirmados. Nos de exemplo, mudam só o ecrã.
	 */
	$real      = ! empty( $u['real'] );
	$form      = function ( $para ) use ( $real, $u ) {
		return $real ? jelly_ar_form_decidir( $u['id'], $para ) : '';
	};
	$confirmar = function ( $rotulo, $titulo, $texto, $sim, $resultado, $classe = 'jar-btn--discreto', $para = '' ) use ( $form ) {
		$id = $para ? $form( $para ) : '';
		printf(
			'<button type="button" class="jar-btn %1$s" data-jar-confirmar data-titulo="%2$s" data-texto="%3$s" data-sim="%4$s" data-resultado="%5$s"%6$s>%7$s</button>',
			esc_attr( $classe ),
			esc_attr( $titulo ),
			esc_attr( $texto ),
			esc_attr( $sim ),
			esc_attr( $resultado ),
			$id ? ' data-jar-form="' . esc_attr( $id ) . '"' : '',
			wp_kses( $rotulo, [ 'i' => [ 'class' => true, 'aria-hidden' => true ] ] )
		);
	};
	$botao     = function ( $rotulo, $para ) use ( $form ) {
		$id = $form( $para );
		printf(
			'<button %1$s class="jar-btn">%2$s</button>',
			$id ? 'type="submit" form="' . esc_attr( $id ) . '"' : 'type="button" data-jar-decidir="' . esc_attr( $para ) . '"',
			esc_html( $rotulo )
		);
	};
	?>
	<div class="jar-cabeca">
		<div>
			<a class="jar-voltar" href="<?php echo esc_url( jelly_ar_admin_url( 'utilizadores' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Utilizadores', 'jelly-area-reservada' ); ?></a>
			<h1 class="jar-cabeca__titulo"><?php echo esc_html( $completo ); ?> <span data-jar-estado-perfil><?php jelly_ar_estado( $u['estado'] ); ?></span></h1>
			<p class="jar-cabeca__intro"><?php echo esc_html( $u['empresa'] ? $u['empresa'] : __( 'Sem empresa indicada', 'jelly-area-reservada' ) ); ?></p>
		</div>
		<div class="jar-cabeca__acoes">
			<?php
			switch ( $u['estado'] ) {
				case 'pendente':
					$confirmar(
						__( 'Rejeitar', 'jelly-area-reservada' ),
						__( 'Rejeitar este pedido?', 'jelly-area-reservada' ),
						/* translators: %s: nome */
						sprintf( __( '%s não vai ter acesso à área reservada.', 'jelly-area-reservada' ), $completo ),
						__( 'Rejeitar pedido', 'jelly-area-reservada' ),
						'rejeitado',
						'jar-btn--contorno',
						'rejeitado'
					);
					$botao( __( 'Aprovar registo', 'jelly-area-reservada' ), 'ativo' );
					break;

				case 'ativo':
					$confirmar(
						'<i class="fa-solid fa-ban" aria-hidden="true"></i> ' . esc_html__( 'Suspender acesso', 'jelly-area-reservada' ),
						__( 'Suspender o acesso?', 'jelly-area-reservada' ),
						/* translators: %s: nome */
						sprintf( __( '%s deixa de conseguir entrar na área reservada até o acesso ser reativado. Os dados e o histórico ficam guardados.', 'jelly-area-reservada' ), $completo ),
						__( 'Suspender acesso', 'jelly-area-reservada' ),
						'suspenso',
						'jar-btn--discreto',
						'suspenso'
					);
					break;

				case 'suspenso':
					$botao( __( 'Reativar acesso', 'jelly-area-reservada' ), 'ativo' );
					break;

				case 'rejeitado':
					$confirmar(
						'<i class="fa-regular fa-trash-can" aria-hidden="true"></i> ' . esc_html__( 'Apagar pedido', 'jelly-area-reservada' ),
						__( 'Apagar este pedido?', 'jelly-area-reservada' ),
						__( 'O pedido e os dados do registo são apagados de vez.', 'jelly-area-reservada' ),
						__( 'Apagar', 'jelly-area-reservada' ),
						'',
						'jar-btn--discreto',
						'apagar'
					);
					$botao( __( 'Aprovar registo', 'jelly-area-reservada' ), 'ativo' );
					break;
			}
			?>
		</div>
	</div>

	<?php $mostrar_aviso(); ?>

	<?php if ( 'pendente' === $u['estado'] ) : ?>
		<div class="jar-aviso jar-aviso--pendente">
			<i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
			<p>
				<?php
				/* translators: %s: data e hora do registo */
				printf( esc_html__( 'Pediu acesso no site a %s. Ao aprovar, recebe um e-mail para definir a palavra-passe e passa a poder entrar com o e-mail.', 'jelly-area-reservada' ), esc_html( $u['registo'] ) );
				?>
			</p>
		</div>
	<?php elseif ( 'suspenso' === $u['estado'] ) : ?>
		<div class="jar-aviso jar-aviso--suspenso">
			<i class="fa-solid fa-ban" aria-hidden="true"></i>
			<p><?php esc_html_e( 'O acesso está suspenso: o login da área reservada recusa este e-mail até ser reativado.', 'jelly-area-reservada' ); ?></p>
		</div>
	<?php elseif ( 'rejeitado' === $u['estado'] ) : ?>
		<div class="jar-aviso jar-aviso--suspenso">
			<i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
			<p><?php esc_html_e( 'O pedido de registo foi rejeitado. A pessoa não tem acesso à área reservada.', 'jelly-area-reservada' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="jar-grelha jar-grelha--1-2">
		<section class="jar-cartao jar-perfil">
			<span class="jar-avatar jar-avatar--grande"><?php echo esc_html( jelly_ar_iniciais( $completo ) ); ?></span>
			<h2><?php echo esc_html( $completo ); ?></h2>
			<p><?php echo esc_html( $u['email'] ); ?></p>

			<dl class="jar-dados">
				<dt><?php esc_html_e( 'Registo', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo esc_html( $u['registo'] ); ?></dd>
				<dt><?php esc_html_e( 'Aprovado', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo esc_html( $u['aprovado'] ? $u['aprovado'] : '—' ); ?></dd>
				<?php if ( $u['aprovado_por'] ) : ?>
					<dt><?php esc_html_e( 'Aprovado por', 'jelly-area-reservada' ); ?></dt>
					<dd><?php echo esc_html( $u['aprovado_por'] ); ?></dd>
				<?php endif; ?>
				<dt><?php esc_html_e( 'Último acesso', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo esc_html( $u['ultimo'] ? $u['ultimo'] : '—' ); ?></dd>
				<dt><?php esc_html_e( 'Acessos', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo (int) $u['acessos']; ?></dd>
			</dl>

			<?php if ( in_array( $u['estado'], [ 'ativo', 'suspenso' ], true ) ) : ?>
				<button type="button" class="jar-btn jar-btn--contorno jar-btn--largo">
					<i class="fa-solid fa-key" aria-hidden="true"></i>
					<?php esc_html_e( 'Enviar e-mail de nova palavra-passe', 'jelly-area-reservada' ); ?>
				</button>
			<?php endif; ?>
		</section>

		<div class="jar-pilha">
			<?php
			/*
			 * Os dados leem-se; só se editam depois de carregar em Editar, e o
			 * Guardar e o Cancelar vivem aqui, junto dos campos que afetam.
			 */
			?>
			<section class="jar-cartao" data-jar-editavel>
				<header class="jar-cartao__cabeca">
					<h2><?php esc_html_e( 'Dados do registo', 'jelly-area-reservada' ); ?></h2>
					<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-editar>
						<i class="fa-solid fa-pen" aria-hidden="true"></i> <?php esc_html_e( 'Editar', 'jelly-area-reservada' ); ?>
					</button>
				</header>

				<dl class="jar-leitura" data-jar-leitura>
					<?php foreach ( $campos as $nome => $c ) : ?>
						<div class="<?php echo esc_attr( $c[3] ); ?>">
							<dt><?php echo esc_html( $c[0] ); ?></dt>
							<dd data-jar-valor="<?php echo esc_attr( $nome ); ?>"><?php echo esc_html( $u[ $nome ] ? $u[ $nome ] : '—' ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>

				<form data-jar-edicao hidden>
					<?php $formulario( $u ); ?>
					<footer class="jar-cartao__pe">
						<button type="button" class="jar-btn jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
						<button type="submit" class="jar-btn"><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></button>
					</footer>
				</form>
			</section>

			<section class="jar-cartao jar-cartao--tabela">
				<header class="jar-cartao__cabeca">
					<div>
						<h2><?php esc_html_e( 'Histórico de acessos', 'jelly-area-reservada' ); ?></h2>
						<?php if ( $u['acessos'] ) : ?>
							<span class="jar-cartao__meta">
								<?php
								/* translators: %d: número total de acessos */
								printf( esc_html__( 'Os 5 mais recentes de %d', 'jelly-area-reservada' ), (int) $u['acessos'] );
								?>
							</span>
						<?php endif; ?>
					</div>
					<?php if ( $u['acessos'] ) : ?>
						<a class="jar-btn jar-btn--pequeno jar-btn--contorno" href="<?php echo esc_url( jelly_ar_url_exportar_acessos( $u['id'] ) ); ?>">
							<i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> <?php esc_html_e( 'Exportar todos', 'jelly-area-reservada' ); ?>
						</a>
					<?php endif; ?>
				</header>
				<?php if ( ! $acessos ) : ?>
					<p class="jar-vazio"><?php esc_html_e( 'Ainda não entrou na área reservada.', 'jelly-area-reservada' ); ?></p>
				<?php else : ?>
					<table class="jar-tabela">
						<thead><tr><th><?php esc_html_e( 'Data e hora', 'jelly-area-reservada' ); ?></th><th><?php esc_html_e( 'IP', 'jelly-area-reservada' ); ?></th><th><?php esc_html_e( 'Dispositivo', 'jelly-area-reservada' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( array_slice( $acessos, 0, 5 ) as $a ) : ?>
								<tr><td class="jar-tabela__num"><?php echo esc_html( $a['quando'] ); ?></td><td class="jar-tabela__num"><?php echo esc_html( $a['ip'] ); ?></td><td><?php echo esc_html( $a['dispositivo'] ); ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>

			<section class="jar-cartao jar-cartao--tabela">
				<header class="jar-cartao__cabeca">
					<h2><?php esc_html_e( 'Marcações de mesas', 'jelly-area-reservada' ); ?></h2>
					<span class="jar-link is-em-breve" title="<?php esc_attr_e( 'Chega com a área das Aprovações', 'jelly-area-reservada' ); ?>"><?php esc_html_e( 'Ver em Aprovações', 'jelly-area-reservada' ); ?> <em><?php esc_html_e( 'em breve', 'jelly-area-reservada' ); ?></em></span>
				</header>
				<?php if ( ! $marcacoes ) : ?>
					<p class="jar-vazio"><?php esc_html_e( 'Sem marcações.', 'jelly-area-reservada' ); ?></p>
				<?php else : ?>
					<table class="jar-tabela">
						<thead><tr><th><?php esc_html_e( 'Evento', 'jelly-area-reservada' ); ?></th><th><?php esc_html_e( 'Mesa', 'jelly-area-reservada' ); ?></th><th><?php esc_html_e( 'Data e hora', 'jelly-area-reservada' ); ?></th><th><?php esc_html_e( 'Estado', 'jelly-area-reservada' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( $marcacoes as $m ) : ?>
								<tr><td><?php echo esc_html( $m['evento'] ); ?></td><td><?php echo esc_html( $m['mesa'] ); ?></td><td class="jar-tabela__num"><?php echo esc_html( $m['quando'] ); ?></td><td><?php jelly_ar_estado( $m['estado'] ); ?></td></tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>
		</div>
	</div>
	<?php
	return;
endif;

/* ---------------------------------------------------------------- Lista */

// O estado da lista vem do endereço; a exportação lê o mesmo (inc/lista.php).
$tabela   = jelly_ar_utilizadores_lista();
$filtro   = $tabela->get( 'estado' );
$pesquisa = $tabela->get( 'q' );

// A pesquisa, o filtro e a ordem são os mesmos que a exportação usa.
$resultado   = jelly_ar_utilizadores_filtrar( $utilizadores, $tabela->pedido() );
$encontrados = $resultado['encontrados'];
$lista       = $tabela->paginar( $resultado['lista'] );

// Os números dos separadores contam o que a pesquisa encontrou.
$contagem = array_count_values( array_column( $encontrados, 'estado' ) );

$filtros  = [
	''          => __( 'Todos', 'jelly-area-reservada' ),
	'ativo'     => __( 'Ativos', 'jelly-area-reservada' ),
	'pendente'  => __( 'Por aprovar', 'jelly-area-reservada' ),
	'suspenso'  => __( 'Suspensos', 'jelly-area-reservada' ),
	'rejeitado' => __( 'Rejeitados', 'jelly-area-reservada' ),
];
$todos       = array_count_values( array_column( $utilizadores, 'estado' ) );
$por_aprovar = $todos['pendente'] ?? 0;
$resumo      = [
	[ __( 'Utilizadores', 'jelly-area-reservada' ), count( $utilizadores ), 'fa-user-group', 'azul', '' ],
	[ __( 'Com acesso', 'jelly-area-reservada' ), $todos['ativo'] ?? 0, 'fa-user-check', 'turquesa', 'ativo' ],
	[ __( 'Por aprovar', 'jelly-area-reservada' ), $por_aprovar, 'fa-hourglass-half', 'magenta', 'pendente' ],
	[ __( 'Acessos (30 dias)', 'jelly-area-reservada' ), 147, 'fa-right-to-bracket', 'roxo', null ],
];
?>
<div class="jar-cabeca">
	<div>
		<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Utilizadores', 'jelly-area-reservada' ); ?></h1>
		<p class="jar-cabeca__intro"><?php esc_html_e( 'Quem tem acesso à área reservada, e os pedidos de registo feitos no site.', 'jelly-area-reservada' ); ?></p>
	</div>
	<div class="jar-cabeca__acoes">
		<?php // Exporta a lista como está — filtro, pesquisa e ordem —, mas inteira, sem páginas. ?>
		<a class="jar-btn jar-btn--contorno" href="<?php echo esc_url( jelly_ar_url_exportar_utilizadores( $tabela->pedido() ) ); ?>" title="<?php esc_attr_e( 'A lista como está filtrada, com todas as páginas', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> <?php esc_html_e( 'Exportar utilizadores', 'jelly-area-reservada' ); ?></a>
		<a class="jar-btn" href="<?php echo esc_url( jelly_ar_admin_url( 'utilizadores', [ 'novo' => 1 ] ) ); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Novo utilizador', 'jelly-area-reservada' ); ?></a>
	</div>
</div>

<div class="jar-numeros">
	<?php foreach ( $resumo as $r ) : ?>
		<?php
		$tag  = null === $r[4] ? 'div' : 'a';
		$href = null === $r[4] ? '' : ' href="' . esc_url( jelly_ar_admin_url( 'utilizadores', $r[4] ? [ 'estado' => $r[4] ] : [] ) ) . '"';
		?>
		<<?php echo esc_html( $tag ); ?> class="jar-cartao jar-numero"<?php echo $href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado acima ?>>
			<span class="jar-icone jar-icone--<?php echo esc_attr( $r[3] ); ?>"><i class="fa-solid <?php echo esc_attr( $r[2] ); ?>" aria-hidden="true"></i></span>
			<span class="jar-numero__rotulo"><?php echo esc_html( $r[0] ); ?></span>
			<strong class="jar-numero__valor"><?php echo (int) $r[1]; ?></strong>
		</<?php echo esc_html( $tag ); ?>>
	<?php endforeach; ?>
</div>

<?php $mostrar_aviso(); ?>

<?php if ( $por_aprovar && 'pendente' !== $filtro ) : ?>
	<a class="jar-aviso jar-aviso--pendente jar-aviso--link" href="<?php echo esc_url( jelly_ar_admin_url( 'utilizadores', [ 'estado' => 'pendente' ] ) ); ?>">
		<i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
		<p>
			<?php
			/* translators: %d: número de pedidos */
			printf( esc_html( _n( '%d pedido de registo feito no site está à espera de aprovação.', '%d pedidos de registo feitos no site estão à espera de aprovação.', $por_aprovar, 'jelly-area-reservada' ) ), (int) $por_aprovar );
			?>
		</p>
		<span class="jar-link"><?php esc_html_e( 'Rever', 'jelly-area-reservada' ); ?> <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></span>
	</a>
<?php endif; ?>

<section class="jar-cartao jar-cartao--tabela">
	<div class="jar-barra">
		<nav class="jar-separadores" aria-label="<?php esc_attr_e( 'Filtrar por estado', 'jelly-area-reservada' ); ?>">
			<?php foreach ( $filtros as $f => $rotulo ) : ?>
				<?php $n = '' === $f ? count( $encontrados ) : ( $contagem[ $f ] ?? 0 ); ?>
				<a class="jar-separador<?php echo $f === $filtro ? ' is-atual' : ''; ?>" href="<?php echo esc_url( $tabela->url( [ 'estado' => $f ] ) ); ?>"<?php echo $f === $filtro ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $rotulo ); ?> <b><?php echo (int) $n; ?></b>
				</a>
			<?php endforeach; ?>
		</nav>

		<?php // Um formulário GET: a pesquisa é feita no servidor, em todas as páginas. ?>
		<form class="jar-filtro" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search" data-jar-pesquisa>
			<?php $tabela->campos_escondidos( [ 'q' ] ); ?>
			<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
			<label class="screen-reader-text" for="jar-pesquisa"><?php esc_html_e( 'Pesquisar utilizadores', 'jelly-area-reservada' ); ?></label>
			<input type="search" id="jar-pesquisa" name="q" value="<?php echo esc_attr( $pesquisa ); ?>" placeholder="<?php esc_attr_e( 'Nome, e-mail, empresa ou telefone', 'jelly-area-reservada' ); ?>">
		</form>
	</div>

	<table class="jar-tabela">
		<thead>
			<tr>
				<?php
				$tabela->coluna( 'nome', __( 'Utilizador', 'jelly-area-reservada' ) );
				$tabela->coluna( 'email', __( 'E-mail', 'jelly-area-reservada' ), 'jar-col--email' );
				$tabela->coluna( 'empresa', __( 'Empresa', 'jelly-area-reservada' ), 'jar-col--empresa' );
				$tabela->coluna( 'telefone', __( 'Telefone', 'jelly-area-reservada' ), 'jar-col--telefone' );
				$tabela->coluna( 'estado', __( 'Estado', 'jelly-area-reservada' ) );
				?>
				<th class="jar-col--registo"><?php esc_html_e( 'Registo', 'jelly-area-reservada' ); ?></th>
				<th class="jar-col--ultimo"><?php esc_html_e( 'Último acesso', 'jelly-area-reservada' ); ?></th>
				<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $lista ) : ?>
				<tr>
					<td colspan="8" class="jar-vazio">
						<?php
						if ( '' !== $pesquisa ) {
							/* translators: %s: o que se pesquisou */
							printf( esc_html__( 'Ninguém corresponde a "%s".', 'jelly-area-reservada' ), esc_html( $pesquisa ) );
						} else {
							esc_html_e( 'Ninguém nesta lista.', 'jelly-area-reservada' );
						}
						?>
					</td>
				</tr>
			<?php endif; ?>
			<?php foreach ( $lista as $x ) : ?>
				<?php
				$completo = $x['nome'] . ' ' . $x['apelido'];
				$perfil   = jelly_ar_admin_url( 'utilizadores', [ 'utilizador' => $x['id'] ] );
				?>
				<tr data-jar-linha>
					<td>
						<a class="jar-pessoa" href="<?php echo esc_url( $perfil ); ?>">
							<span class="jar-avatar"><?php echo esc_html( jelly_ar_iniciais( $completo ) ); ?></span>
							<strong><?php echo esc_html( $completo ); ?></strong>
						</a>
					</td>
					<td class="jar-col--email"><a class="jar-email" href="mailto:<?php echo esc_attr( $x['email'] ); ?>" title="<?php echo esc_attr( $x['email'] ); ?>"><?php echo esc_html( $x['email'] ); ?></a></td>
					<td class="jar-col--empresa"><?php echo esc_html( $x['empresa'] ? $x['empresa'] : '—' ); ?></td>
					<td class="jar-tabela__num jar-col--telefone"><?php echo esc_html( $x['telefone'] ? $x['telefone'] : '—' ); ?></td>
					<td data-jar-estado><?php jelly_ar_estado( $x['estado'] ); ?></td>
					<td class="jar-tabela__num jar-col--registo"><?php jelly_ar_data_hora( $x['registo'] ); ?></td>
					<td class="jar-tabela__num jar-col--ultimo"><?php jelly_ar_data_hora( $x['ultimo'] ); ?></td>
					<td class="jar-tabela__fim">
						<span class="jar-acoes">
							<?php if ( 'pendente' === $x['estado'] ) : ?>
								<?php
								/*
								 * Num utilizador real, aprovar e rejeitar gravam (jelly_ar_utilizador_estado(),
								 * em inc/utilizadores.php): o visto envia o formulário, e o X envia-o depois da
								 * confirmação. Nos de exemplo mudam só o ecrã.
								 */
								$real     = ! empty( $x['real'] );
								$aprovar  = $real ? jelly_ar_form_decidir( $x['id'], 'ativo' ) : '';
								$rejeitar = $real ? jelly_ar_form_decidir( $x['id'], 'rejeitado' ) : '';
								?>
								<button
									<?php echo $real ? 'type="submit" form="' . esc_attr( $aprovar ) . '"' : 'type="button" data-jar-decidir="ativo"'; ?>
									class="jar-acao jar-acao--sim"
									aria-label="<?php echo esc_attr( sprintf( __( 'Aprovar %s', 'jelly-area-reservada' ), $completo ) ); ?>"
									title="<?php esc_attr_e( 'Aprovar', 'jelly-area-reservada' ); ?>"
								><i class="fa-solid fa-check" aria-hidden="true"></i></button>
								<button
									type="button"
									class="jar-acao jar-acao--nao"
									<?php echo $real ? 'data-jar-form="' . esc_attr( $rejeitar ) . '"' : ''; ?>
									data-jar-confirmar
									data-titulo="<?php esc_attr_e( 'Rejeitar este pedido?', 'jelly-area-reservada' ); ?>"
									data-texto="<?php echo esc_attr( sprintf( __( '%s não vai ter acesso à área reservada.', 'jelly-area-reservada' ), $completo ) ); ?>"
									data-sim="<?php esc_attr_e( 'Rejeitar pedido', 'jelly-area-reservada' ); ?>"
									data-resultado="rejeitado"
									aria-label="<?php echo esc_attr( sprintf( __( 'Rejeitar %s', 'jelly-area-reservada' ), $completo ) ); ?>"
									title="<?php esc_attr_e( 'Rejeitar', 'jelly-area-reservada' ); ?>"
								><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
							<?php endif; ?>
							<a class="jar-acao" href="<?php echo esc_url( $perfil ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Abrir o perfil de %s', 'jelly-area-reservada' ), $completo ) ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
						</span>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php $tabela->paginacao(); ?>
</section>
