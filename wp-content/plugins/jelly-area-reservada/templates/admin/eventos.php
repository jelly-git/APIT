<?php
/**
 * Eventos: a lista, um evento (`evento=<id>`) e o formulário de um novo
 * (`novo=1`).
 *
 * Os eventos são os do calendário do site (inc/eventos.php). Por onde a área
 * se liga às outras:
 * - "Onde aparece" decide se o evento está no calendário do site, só na área
 *   reservada, ou nos dois — o tema respeita-o no calendário e na pesquisa;
 * - um evento que aceita marcações ganha mesas e horários de 30 minutos, na
 *   área Mesas e horários, e os pedidos dos associados vão às Aprovações —
 *   ambas por chegar;
 * - o Calendário da área reservada mostra os eventos que lá aparecem.
 */

defined( 'ABSPATH' ) || exit;

$categorias = jelly_ar_evento_categorias();
$onde_nomes = jelly_ar_evento_onde_nomes();
$id         = isset( $_GET['evento'] ) ? absint( $_GET['evento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$novo       = ! empty( $_GET['novo'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$evento     = $id ? jelly_ar_evento( $id ) : null;
$aviso      = isset( $_GET['aviso'] ) ? sanitize_key( wp_unslash( $_GET['aviso'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$erro       = isset( $_GET['erro'] ) ? sanitize_key( wp_unslash( $_GET['erro'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// As categorias, com as cores, geridas aqui mesmo (inc/eventos-categorias.php).
$gerir_categorias = jelly_ar_admin_url( 'eventos', [ 'categorias' => 1 ] );

$avisos = [
	'criado'     => __( 'Evento criado.', 'jelly-area-reservada' ),
	'atualizado' => __( 'Evento guardado. O calendário do site já mostra as mudanças.', 'jelly-area-reservada' ),
	'publicado'  => __( 'Evento publicado.', 'jelly-area-reservada' ),
	'rascunho'   => __( 'Evento passado a rascunho: saiu do calendário do site e da área reservada.', 'jelly-area-reservada' ),
	'documentos' => __( 'Documentos do evento guardados.', 'jelly-area-reservada' ),
	'documento-novo' => __( 'Documento carregado e ligado a este evento.', 'jelly-area-reservada' ),
	'lixo'       => __( 'Evento enviado para o lixo: saiu das listas e do site, e fica guardado para se poder recuperar.', 'jelly-area-reservada' ),
	'categoria-criada'     => __( 'Categoria criada.', 'jelly-area-reservada' ),
	'categoria-atualizada' => __( 'Categoria guardada. Os cartões do calendário do site já usam as cores novas.', 'jelly-area-reservada' ),
	'categoria-apagada'    => __( 'Categoria apagada.', 'jelly-area-reservada' ),
];
$erros  = [
	'campos' => __( 'Falta o título, a data de início, a categoria ou onde aparece.', 'jelly-area-reservada' ),
	'datas'  => __( 'A data de fim não pode ser antes da de início.', 'jelly-area-reservada' ),
	'falhou' => __( 'O evento não foi guardado. Tente outra vez.', 'jelly-area-reservada' ),
	'categoria-nome'   => __( 'Escreva o nome da categoria.', 'jelly-area-reservada' ),
	'categoria-existe' => __( 'Já existe uma categoria com esse nome.', 'jelly-area-reservada' ),
	'categoria-em-uso' => __( 'Essa categoria tem eventos. Mude-os primeiro para outra categoria.', 'jelly-area-reservada' ),
	'categoria-falhou' => __( 'A categoria não foi guardada. Tente outra vez.', 'jelly-area-reservada' ),
];

$mostrar_aviso = function () use ( $aviso, $erro, $avisos, $erros ) {
	if ( isset( $avisos[ $aviso ] ) ) {
		printf( '<div class="jar-aviso jar-aviso--sucesso" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><p>%s</p></div>', esc_html( $avisos[ $aviso ] ) );
	}
	if ( isset( $erros[ $erro ] ) ) {
		printf( '<div class="jar-aviso jar-aviso--suspenso" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><p>%s</p></div>', esc_html( $erros[ $erro ] ) );
	}
};

// Onde aparece, como etiqueta: um ícone para o site, outro para a área, os dois juntos.
$onde = function ( $valor, $longo = false ) use ( $onde_nomes ) {
	$icones = [
		'site'      => '<i class="fa-solid fa-globe" aria-hidden="true"></i>',
		'reservada' => '<i class="fa-solid fa-lock" aria-hidden="true"></i>',
		'ambos'     => '<i class="fa-solid fa-globe" aria-hidden="true"></i><i class="fa-solid fa-lock" aria-hidden="true"></i>',
	];
	$curtos = [
		'site'      => __( 'Site', 'jelly-area-reservada' ),
		'reservada' => __( 'Área reservada', 'jelly-area-reservada' ),
		'ambos'     => __( 'Site e Área reservada', 'jelly-area-reservada' ),
	];

	printf(
		'<span class="jar-onde jar-onde--%1$s" title="%2$s">%3$s %4$s</span>',
		esc_attr( $valor ),
		esc_attr( $onde_nomes[ $valor ] ),
		$icones[ $valor ], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- marcação fixa acima
		esc_html( $longo ? $onde_nomes[ $valor ] : $curtos[ $valor ] )
	);
};

// A capa: o gradiente da categoria, o mesmo do cartão do calendário. Só a cor, sem texto.
$capa = function ( $e, $grande = false ) {
	printf(
		'<span class="jar-evento-capa%1$s" style="--jar-cat-inicio: %2$s; --jar-cat-fim: %3$s;" aria-hidden="true"></span>',
		$grande ? ' jar-evento-capa--grande' : '',
		esc_attr( $e['cores']['inicio'] ),
		esc_attr( $e['cores']['fim'] )
	);
};

/*
 * Os campos do evento, iguais no novo e na edição. As datas vêm como Ymd e o
 * <input type="date"> quer Y-m-d.
 */
$campos = function ( $e ) use ( $categorias, $onde_nomes, $gerir_categorias ) {
	$data = function ( $ymd ) {
		return $ymd ? substr( $ymd, 0, 4 ) . '-' . substr( $ymd, 4, 2 ) . '-' . substr( $ymd, 6, 2 ) : '';
	};
	$fim  = ( $e['fim'] ?? '' ) !== ( $e['inicio'] ?? '' ) ? $e['fim'] : '';
	?>
	<div class="jar-form">
		<label class="jar-campo jar-campo--largo">
			<span><?php esc_html_e( 'Título', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></span>
			<input type="text" name="titulo" value="<?php echo esc_attr( $e['titulo'] ?? '' ); ?>" required>
		</label>

		<?php
		/*
		 * As datas do próprio evento — quando decorre. Não adiam nada: o
		 * evento publicado vê-se logo. O início dá a data do cartão; o fim,
		 * se houver, faz o cartão mostrar o intervalo e deixa o evento no
		 * calendário até ao fim desse dia.
		 */
		?>
		<label class="jar-campo">
			<span><?php esc_html_e( 'Início do evento', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></span>
			<input type="date" name="inicio" value="<?php echo esc_attr( $data( $e['inicio'] ?? '' ) ); ?>" required data-jar-inicio>
			<small class="jar-campo__ajuda"><?php esc_html_e( 'A data que aparece no cartão do evento. O evento fica visível logo que é publicado.', 'jelly-area-reservada' ); ?></small>
		</label>
		<label class="jar-campo">
			<span><?php esc_html_e( 'Fim do evento', 'jelly-area-reservada' ); ?></span>
			<input type="date" name="fim" value="<?php echo esc_attr( $data( $fim ) ); ?>" data-jar-fim>
			<small class="jar-campo__ajuda"><?php esc_html_e( 'Opcional. Com fim, o cartão mostra as datas do início ao fim (ex.: 6–9 out.) e o evento sai das listagens no fim desse dia. Sem fim, sai no fim do dia de início.', 'jelly-area-reservada' ); ?></small>
		</label>

		<label class="jar-campo">
			<span class="jar-campo__rotulo">
				<span><?php esc_html_e( 'Categoria', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></span>
				<a class="jar-link" href="<?php echo esc_url( $gerir_categorias ); ?>"><?php esc_html_e( 'Gerir categorias', 'jelly-area-reservada' ); ?></a>
			</span>
			<select name="categoria" required>
				<option value=""><?php esc_html_e( 'Escolher…', 'jelly-area-reservada' ); ?></option>
				<?php foreach ( $categorias as $c ) : ?>
					<option value="<?php echo (int) $c['id']; ?>" <?php selected( $c['id'], $e['categoria_id'] ?? 0 ); ?>><?php echo esc_html( $c['nome'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="jar-campo">
			<span><?php esc_html_e( 'Local', 'jelly-area-reservada' ); ?></span>
			<input type="text" name="local" value="<?php echo esc_attr( $e['local'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Ex.: Cannes', 'jelly-area-reservada' ); ?>">
		</label>

		<label class="jar-campo jar-campo--largo">
			<span><?php esc_html_e( 'Resumo', 'jelly-area-reservada' ); ?></span>
			<textarea name="resumo" rows="2" placeholder="<?php esc_attr_e( 'A linha que aparece por baixo do título no cartão do calendário', 'jelly-area-reservada' ); ?>"><?php echo esc_textarea( $e['resumo'] ?? '' ); ?></textarea>
		</label>

	</div>

	<fieldset class="jar-opcoes">
		<legend><?php esc_html_e( 'Onde aparece', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></legend>
		<?php foreach ( $onde_nomes as $valor => $nome ) : ?>
			<label class="jar-caixa">
				<input type="radio" name="onde" value="<?php echo esc_attr( $valor ); ?>" <?php checked( $valor, $e['onde'] ?? 'ambos' ); ?> required>
				<span><?php echo esc_html( $nome ); ?></span>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<fieldset class="jar-opcoes">
		<legend><?php esc_html_e( 'Marcações', 'jelly-area-reservada' ); ?></legend>
		<label class="jar-caixa">
			<input type="checkbox" name="marcacoes" value="1" <?php checked( ! empty( $e['marcacoes'] ) ); ?> data-jar-revela="jar-botao-texto">
			<span><?php esc_html_e( 'Os associados podem marcar mesas neste evento', 'jelly-area-reservada' ); ?></span>
		</label>

		<?php
		/*
		 * O botão do cartão no calendário do site existe só com as marcações
		 * ligadas, e leva à marcação (ou ao login) — o link não se escreve.
		 * Num evento novo, o texto vem já preenchido; num que já existe, fica
		 * o que tiver, e vazio vale "Fazer inscrição".
		 *
		 * Só se vê com as marcações ligadas; escondido, o texto continua a ser
		 * enviado e guardado, para voltar igual se as marcações voltarem.
		 */
		?>
		<label class="jar-campo jar-campo--botao" id="jar-botao-texto"<?php echo empty( $e['marcacoes'] ) ? ' hidden' : ''; ?>>
			<span><?php esc_html_e( 'Texto do botão no calendário', 'jelly-area-reservada' ); ?></span>
			<input type="text" name="acao_texto" value="<?php echo esc_attr( $e['acao_texto'] ?? __( 'Fazer inscrição', 'jelly-area-reservada' ) ); ?>" placeholder="<?php esc_attr_e( 'Ex.: Fazer inscrição', 'jelly-area-reservada' ); ?>" maxlength="30">
			<small><?php esc_html_e( 'Aparece no cartão do evento só com as marcações ligadas, e leva à marcação de mesa.', 'jelly-area-reservada' ); ?></small>
		</label>
	</fieldset>
	<?php
};

/* ---------------------------------------------------------------- Categorias */

if ( ! empty( $_GET['categorias'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lista_categorias = jelly_ar_evento_categorias_com_contagem();
	$padrao           = jelly_ar_evento_categoria_cores( 0 );

	// A amostra do gradiente, como no cartão do calendário; o JS atualiza-a ao mudar as cores.
	$amostra = function ( $cores ) {
		printf(
			'<span class="jar-gradiente" data-jar-amostra style="--jar-cat-inicio: %1$s; --jar-cat-fim: %2$s;" aria-hidden="true"></span>',
			esc_attr( $cores['inicio'] ),
			esc_attr( $cores['fim'] )
		);
	};

	// As duas cores, com o gradiente ao lado a mostrar o resultado.
	$cores = function ( $valores ) use ( $amostra ) {
		?>
		<span class="jar-cores" data-jar-cores>
			<label class="jar-cor">
				<span><?php esc_html_e( 'Início', 'jelly-area-reservada' ); ?></span>
				<input type="color" name="cor_inicio" value="<?php echo esc_attr( $valores['inicio'] ); ?>" data-jar-cor="inicio">
			</label>
			<label class="jar-cor">
				<span><?php esc_html_e( 'Fim', 'jelly-area-reservada' ); ?></span>
				<input type="color" name="cor_fim" value="<?php echo esc_attr( $valores['fim'] ); ?>" data-jar-cor="fim">
			</label>
			<?php $amostra( $valores ); ?>
		</span>
		<?php
	};
	?>
	<div class="jar-cabeca">
		<div>
			<a class="jar-voltar" href="<?php echo esc_url( jelly_ar_admin_url( 'eventos' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?></a>
			<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Categorias de eventos', 'jelly-area-reservada' ); ?></h1>
			<p class="jar-cabeca__intro"><?php esc_html_e( 'Cada categoria dá o gradiente aos cartões do calendário do site: uma cor de início e outra de fim.', 'jelly-area-reservada' ); ?></p>
		</div>
	</div>

	<?php $mostrar_aviso(); ?>

	<div class="jar-grelha jar-grelha--2-1">
		<section class="jar-cartao jar-cartao--tabela">
			<table class="jar-tabela">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Categoria', 'jelly-area-reservada' ); ?></th>
						<th class="jar-tabela__num"><?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?></th>
						<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $lista_categorias ) : ?>
						<tr><td colspan="3" class="jar-vazio"><?php esc_html_e( 'Ainda não há categorias.', 'jelly-area-reservada' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $lista_categorias as $c ) : ?>
						<tr data-jar-editavel>
							<td>
								<span class="jar-categoria-nome" data-jar-leitura>
									<?php $amostra( $c['cores'] ); ?>
									<strong><?php echo esc_html( $c['nome'] ); ?></strong>
								</span>

								<?php // O nome e as cores, na própria linha; vai para jelly_ar_evento_categoria_editar(). ?>
								<form class="jar-linha-edicao" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-jar-edicao data-jar-gravar hidden>
									<input type="hidden" name="action" value="jelly_ar_evento_categoria_editar">
									<input type="hidden" name="categoria" value="<?php echo (int) $c['id']; ?>">
									<?php wp_nonce_field( 'jelly_ar_evento_categoria_editar_' . $c['id'] ); ?>
									<label class="jar-campo">
										<span class="screen-reader-text"><?php esc_html_e( 'Nome da categoria', 'jelly-area-reservada' ); ?></span>
										<input type="text" name="nome" value="<?php echo esc_attr( $c['nome'] ); ?>" required maxlength="80">
									</label>
									<?php $cores( $c['cores'] ); ?>
									<span class="jar-linha-edicao__botoes">
										<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
										<button type="submit" class="jar-btn jar-btn--pequeno"><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></button>
									</span>
								</form>
							</td>
							<td class="jar-tabela__num">
								<?php if ( $c['eventos'] ) : ?>
									<a class="jar-link" href="<?php echo esc_url( jelly_ar_admin_url( 'eventos', [ 'categoria' => $c['slug'], 'quando' => 'todos' ] ) ); ?>"><?php echo (int) $c['eventos']; ?></a>
								<?php else : ?>
									0
								<?php endif; ?>
							</td>
							<td class="jar-tabela__fim">
								<span class="jar-acoes">
									<?php /* translators: %s: nome da categoria */ ?>
									<button type="button" class="jar-acao" data-jar-editar aria-label="<?php echo esc_attr( sprintf( __( 'Editar %s', 'jelly-area-reservada' ), $c['nome'] ) ); ?>" title="<?php esc_attr_e( 'Editar', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>

									<?php if ( $c['eventos'] ) : ?>
										<?php // Com eventos não se apaga: o botão diz porquê em vez de abrir a confirmação. ?>
										<button type="button" class="jar-acao" disabled title="<?php echo esc_attr( sprintf( _n( 'Tem %d evento: mude-o primeiro para outra categoria', 'Tem %d eventos: mude-os primeiro para outra categoria', $c['eventos'], 'jelly-area-reservada' ), $c['eventos'] ) ); ?>"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
									<?php else : ?>
										<button
											type="button"
											class="jar-acao jar-acao--nao"
											data-jar-confirmar
											data-jar-form="jar-apagar-categoria-<?php echo (int) $c['id']; ?>"
											<?php /* translators: %s: nome da categoria */ ?>
											data-titulo="<?php echo esc_attr( sprintf( __( 'Apagar a categoria "%s"?', 'jelly-area-reservada' ), $c['nome'] ) ); ?>"
											data-texto="<?php esc_attr_e( 'Deixa de estar disponível nos eventos. Nenhum evento a usa, por isso nenhum fica sem categoria.', 'jelly-area-reservada' ); ?>"
											data-sim="<?php esc_attr_e( 'Apagar categoria', 'jelly-area-reservada' ); ?>"
											data-resultado=""
											aria-label="<?php echo esc_attr( sprintf( __( 'Apagar %s', 'jelly-area-reservada' ), $c['nome'] ) ); ?>"
											title="<?php esc_attr_e( 'Apagar', 'jelly-area-reservada' ); ?>"
										><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
										<form id="jar-apagar-categoria-<?php echo (int) $c['id']; ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
											<input type="hidden" name="action" value="jelly_ar_evento_categoria_apagar">
											<input type="hidden" name="categoria" value="<?php echo (int) $c['id']; ?>">
											<?php wp_nonce_field( 'jelly_ar_evento_categoria_apagar_' . $c['id'] ); ?>
										</form>
									<?php endif; ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<section class="jar-cartao">
			<header class="jar-cartao__cabeca"><h2><?php esc_html_e( 'Nova categoria', 'jelly-area-reservada' ); ?></h2></header>
			<?php // Vai para jelly_ar_evento_categoria_criar(). ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="jelly_ar_evento_categoria_criar">
				<?php wp_nonce_field( 'jelly_ar_evento_categoria_criar' ); ?>
				<label class="jar-campo">
					<span><?php esc_html_e( 'Nome', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></span>
					<input type="text" name="nome" required maxlength="80" placeholder="<?php esc_attr_e( 'Ex.: Workshop', 'jelly-area-reservada' ); ?>">
				</label>
				<div class="jar-campo jar-campo--cores">
					<span><?php esc_html_e( 'Cores do cartão', 'jelly-area-reservada' ); ?></span>
					<?php $cores( $padrao ); ?>
				</div>
				<footer class="jar-cartao__pe">
					<button type="submit" class="jar-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Criar categoria', 'jelly-area-reservada' ); ?></button>
				</footer>
			</form>
			<p class="jar-nota">
				<i class="fa-solid fa-circle-info" aria-hidden="true"></i>
				<?php esc_html_e( 'Uma categoria com eventos não se apaga — contam também os rascunhos e os que estão no lixo. Mude primeiro os eventos para outra categoria.', 'jelly-area-reservada' ); ?>
			</p>
		</section>
	</div>
	<?php
	return;
endif;

/* ---------------------------------------------------------------- Novo */

if ( $novo ) :
	?>
	<div class="jar-cabeca">
		<div>
			<a class="jar-voltar" href="<?php echo esc_url( jelly_ar_admin_url( 'eventos' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?></a>
			<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Novo evento', 'jelly-area-reservada' ); ?></h1>
			<p class="jar-cabeca__intro"><?php esc_html_e( 'Um evento só, para o calendário do site e para a área reservada: escolha onde aparece.', 'jelly-area-reservada' ); ?></p>
		</div>
	</div>

	<?php $mostrar_aviso(); ?>

	<form class="jar-cartao jar-cartao--estreito" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="jelly_ar_evento_guardar">
		<input type="hidden" name="evento" value="0">
		<?php wp_nonce_field( 'jelly_ar_evento_guardar_0' ); ?>

		<?php $campos( [] ); ?>

		<fieldset class="jar-opcoes">
			<legend><?php esc_html_e( 'Publicação', 'jelly-area-reservada' ); ?></legend>
			<label class="jar-caixa"><input type="radio" name="estado" value="publicado" checked> <span><?php esc_html_e( 'Publicar já', 'jelly-area-reservada' ); ?></span></label>
			<label class="jar-caixa"><input type="radio" name="estado" value="rascunho"> <span><?php esc_html_e( 'Guardar como rascunho', 'jelly-area-reservada' ); ?></span></label>
		</fieldset>

		<footer class="jar-cartao__pe">
			<a class="jar-btn jar-btn--contorno" href="<?php echo esc_url( jelly_ar_admin_url( 'eventos' ) ); ?>"><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></a>
			<button type="submit" class="jar-btn"><?php esc_html_e( 'Criar evento', 'jelly-area-reservada' ); ?></button>
		</footer>
	</form>
	<?php
	return;
endif;

/* ---------------------------------------------------------------- Evento */

if ( $evento ) :
	$publicado = 'publicado' === $evento['estado'];
	$estado    = function ( $acao ) use ( $evento ) {
		?>
		<form id="jar-evento-<?php echo esc_attr( $acao ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
			<input type="hidden" name="action" value="jelly_ar_evento_estado">
			<input type="hidden" name="evento" value="<?php echo (int) $evento['id']; ?>">
			<input type="hidden" name="estado" value="<?php echo esc_attr( $acao ); ?>">
			<?php wp_nonce_field( 'jelly_ar_evento_estado_' . $evento['id'] ); ?>
		</form>
		<?php
	};
	?>
	<div class="jar-cabeca">
		<div>
			<a class="jar-voltar" href="<?php echo esc_url( jelly_ar_admin_url( 'eventos' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?></a>
			<h1 class="jar-cabeca__titulo"><?php echo esc_html( $evento['titulo'] ); ?> <span data-jar-estado-perfil><?php jelly_ar_estado( $evento['estado'] ); ?></span></h1>
			<p class="jar-cabeca__intro"><?php echo esc_html( jelly_ar_intervalo_datas( $evento['inicio'], $evento['fim'] ) . ( $evento['local'] ? ' · ' . $evento['local'] : '' ) ); ?></p>
		</div>
		<div class="jar-cabeca__acoes">
			<button
				type="button"
				class="jar-btn jar-btn--discreto"
				data-jar-confirmar
				data-jar-form="jar-evento-lixo"
				data-titulo="<?php esc_attr_e( 'Enviar este evento para o lixo?', 'jelly-area-reservada' ); ?>"
				data-texto="<?php esc_attr_e( 'Sai do calendário do site e da área reservada. Fica guardado no lixo, de onde ainda se pode recuperar.', 'jelly-area-reservada' ); ?>"
				data-sim="<?php esc_attr_e( 'Enviar para o lixo', 'jelly-area-reservada' ); ?>"
				data-resultado=""
			><i class="fa-regular fa-trash-can" aria-hidden="true"></i> <?php esc_html_e( 'Enviar para o lixo', 'jelly-area-reservada' ); ?></button>
			<?php $estado( 'lixo' ); ?>

			<?php if ( $publicado ) : ?>
				<button
					type="button"
					class="jar-btn jar-btn--contorno"
					data-jar-confirmar
					data-jar-form="jar-evento-rascunho"
					data-titulo="<?php esc_attr_e( 'Passar a rascunho?', 'jelly-area-reservada' ); ?>"
					data-texto="<?php esc_attr_e( 'O evento sai do calendário do site e da área reservada até voltar a ser publicado.', 'jelly-area-reservada' ); ?>"
					data-sim="<?php esc_attr_e( 'Passar a rascunho', 'jelly-area-reservada' ); ?>"
					data-resultado=""
				><?php esc_html_e( 'Passar a rascunho', 'jelly-area-reservada' ); ?></button>
				<?php $estado( 'rascunho' ); ?>
			<?php else : ?>
				<button type="submit" form="jar-evento-publicar" class="jar-btn"><?php esc_html_e( 'Publicar', 'jelly-area-reservada' ); ?></button>
				<?php $estado( 'publicar' ); ?>
			<?php endif; ?>
		</div>
	</div>

	<?php $mostrar_aviso(); ?>

	<?php if ( ! $publicado ) : ?>
		<div class="jar-aviso jar-aviso--pendente">
			<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
			<p><?php esc_html_e( 'Rascunho: não aparece no calendário do site nem na área reservada.', 'jelly-area-reservada' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="jar-grelha jar-grelha--1-2">
		<section class="jar-cartao jar-perfil jar-evento-cartao">
			<?php $capa( $evento, true ); ?>
			<h2><?php echo esc_html( $evento['titulo'] ); ?></h2>
			<p><?php echo esc_html( $evento['categoria_nome'] ? $evento['categoria_nome'] : __( 'Sem categoria', 'jelly-area-reservada' ) ); ?></p>

			<dl class="jar-dados">
				<dt><?php esc_html_e( 'Datas', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo esc_html( jelly_ar_intervalo_datas( $evento['inicio'], $evento['fim'] ) ); ?></dd>
				<dt><?php esc_html_e( 'Local', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo esc_html( $evento['local'] ? $evento['local'] : '—' ); ?></dd>
				<dt><?php esc_html_e( 'Onde aparece', 'jelly-area-reservada' ); ?></dt>
				<dd><?php $onde( $evento['onde'] ); ?></dd>
				<dt><?php esc_html_e( 'Marcações', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo esc_html( $evento['marcacoes'] ? __( 'Aceita', 'jelly-area-reservada' ) : __( 'Não aceita', 'jelly-area-reservada' ) ); ?></dd>
			</dl>
		</section>

		<div class="jar-pilha">
			<?php // Um erro ao guardar volta com `editar=1`: o cartão abre já em edição. ?>
			<section class="jar-cartao" data-jar-editavel<?php echo ! empty( $_GET['editar'] ) ? ' data-jar-abrir' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>>
				<header class="jar-cartao__cabeca">
					<h2><?php esc_html_e( 'Dados do evento', 'jelly-area-reservada' ); ?></h2>
					<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-editar>
						<i class="fa-solid fa-pen" aria-hidden="true"></i> <?php esc_html_e( 'Editar', 'jelly-area-reservada' ); ?>
					</button>
				</header>

				<dl class="jar-leitura" data-jar-leitura>
					<div class="jar-campo--largo">
						<dt><?php esc_html_e( 'Título', 'jelly-area-reservada' ); ?></dt>
						<dd><?php echo esc_html( $evento['titulo'] ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Datas', 'jelly-area-reservada' ); ?></dt>
						<dd><?php echo esc_html( jelly_ar_intervalo_datas( $evento['inicio'], $evento['fim'] ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Categoria', 'jelly-area-reservada' ); ?></dt>
						<dd><?php echo esc_html( $evento['categoria_nome'] ? $evento['categoria_nome'] : '—' ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Local', 'jelly-area-reservada' ); ?></dt>
						<dd><?php echo esc_html( $evento['local'] ? $evento['local'] : '—' ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Onde aparece', 'jelly-area-reservada' ); ?></dt>
						<dd><?php echo esc_html( $onde_nomes[ $evento['onde'] ] ); ?></dd>
					</div>
					<div class="jar-campo--largo">
						<dt><?php esc_html_e( 'Resumo', 'jelly-area-reservada' ); ?></dt>
						<dd><?php echo esc_html( $evento['resumo'] ? $evento['resumo'] : '—' ); ?></dd>
					</div>
					<div class="jar-campo--largo">
						<dt><?php esc_html_e( 'Marcações', 'jelly-area-reservada' ); ?></dt>
						<dd>
							<?php
							// O mesmo que o calendário mostra: só com as marcações ligadas.
							if ( $evento['marcacoes'] ) {
								// O mesmo desenho do aviso de baixo, com o visto e a cor de "tudo certo".
								printf(
									'<span class="jar-atencao jar-atencao--ok"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> %s</span>',
									esc_html__( 'O evento aceita marcações', 'jelly-area-reservada' )
								);
							} else {
								/*
								 * Um aviso e não um erro: é uma escolha válida, mas quer
								 * dizer que o cartão fica sem botão — por isso destaca-se.
								 */
								printf(
									'<span class="jar-atencao"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> %s</span>',
									esc_html__( 'O evento não aceita marcações', 'jelly-area-reservada' )
								);
							}
							?>
						</dd>
					</div>
				</dl>

				<?php // Vai para jelly_ar_evento_guardar(), em inc/eventos.php. ?>
				<form data-jar-edicao data-jar-gravar hidden method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="jelly_ar_evento_guardar">
					<input type="hidden" name="evento" value="<?php echo (int) $evento['id']; ?>">
					<?php wp_nonce_field( 'jelly_ar_evento_guardar_' . $evento['id'] ); ?>
					<?php $campos( $evento ); ?>
					<footer class="jar-cartao__pe">
						<button type="button" class="jar-btn jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
						<button type="submit" class="jar-btn"><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></button>
					</footer>
				</form>
			</section>

			<?php
			/*
			 * As mesas e os horários do evento, em resumo; configuram-se na área
			 * Mesas e horários (templates/admin/mesas.php). Sem marcações, o
			 * cartão diz como as ligar.
			 */
			$mesas_resumo = jelly_ar_mesas_resumo( $evento['id'] );
			?>
			<section class="jar-cartao">
				<header class="jar-cartao__cabeca jar-cartao__cabeca--acao">
					<div>
						<h2><?php esc_html_e( 'Mesas e horários', 'jelly-area-reservada' ); ?></h2>
						<?php if ( $evento['marcacoes'] ) : ?>
							<span class="jar-cartao__meta"><?php esc_html_e( 'Onde e quando os associados podem marcar', 'jelly-area-reservada' ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $evento['marcacoes'] ) : ?>
						<?php // Cheio (cor-de-rosa) enquanto faltam mesas ou horários, para chamar a atenção; em contorno com a grelha pronta. ?>
						<a class="jar-btn jar-btn--pequeno<?php echo $mesas_resumo['mesas'] && $mesas_resumo['dias'] ? ' jar-btn--contorno' : ''; ?>" href="<?php echo esc_url( jelly_ar_admin_url( 'mesas', [ 'evento' => $evento['id'] ] ) ); ?>">
							<i class="fa-solid fa-table-cells-large" aria-hidden="true"></i> <?php esc_html_e( 'Gerir mesas e horários', 'jelly-area-reservada' ); ?>
						</a>
					<?php endif; ?>
				</header>
				<?php if ( ! $evento['marcacoes'] ) : ?>
					<p class="jar-vazio jar-vazio--esquerda"><?php esc_html_e( 'Este evento não aceita marcações. Para os associados poderem marcar mesas, é necessário editar os dados do evento e ligar as marcações.', 'jelly-area-reservada' ); ?></p>
				<?php elseif ( ! $mesas_resumo['mesas'] || ! $mesas_resumo['dias'] ) : ?>
					<p class="jar-vazio jar-vazio--esquerda"><?php esc_html_e( 'Este evento aceita marcações, mas ainda não tem mesas e horários: sem eles, os associados não têm onde marcar.', 'jelly-area-reservada' ); ?></p>
				<?php else : ?>
					<ul class="jar-mesas-resumo">

						<li><strong><?php echo (int) $mesas_resumo['mesas']; ?></strong> <?php echo esc_html( _n( 'mesa', 'mesas', $mesas_resumo['mesas'], 'jelly-area-reservada' ) ); ?></li>
						<li><strong><?php echo (int) $mesas_resumo['dias']; ?></strong> <?php echo esc_html( _n( 'dia com horário', 'dias com horário', $mesas_resumo['dias'], 'jelly-area-reservada' ) ); ?></li>
						<li><strong><?php echo (int) $mesas_resumo['lugares']; ?></strong> <?php echo esc_html( JELLY_AR_LUGARES ? __( 'lugares para marcar', 'jelly-area-reservada' ) : __( 'horários para marcar', 'jelly-area-reservada' ) ); ?></li>
						<li><strong><?php echo (int) ( $mesas_resumo['confirmadas'] + $mesas_resumo['pendentes'] ); ?></strong> <?php esc_html_e( 'marcações', 'jelly-area-reservada' ); ?></li>
					</ul>
				<?php endif; ?>
			</section>

			<?php
			/*
			 * Os documentos do evento: o associado vê-os na página do evento, na
			 * área reservada. Escolhem-se aqui ou, do outro lado, no cartão Eventos
			 * de cada documento: os dois gravam a mesma tabela
			 * (jelly_ar_evento_documentos), e um documento pode estar em vários
			 * eventos.
			 *
			 * A lista e a procura são só dos documentos criados na Área Reservada
			 * (jelly_ar_documentos): os documentos públicos do site (o tipo
			 * apit_documento do tema) não entram, e os de exemplo também não,
			 * porque não têm onde guardar a ligação.
			 */
			$docs_evento = jelly_ar_evento_documentos( $evento['id'] );
			$docs_ids    = wp_list_pluck( $docs_evento, 'id' );
			$docs_todos  = jelly_ar_documentos_reais();
			$doc_cats    = jelly_ar_documento_categorias();
			usort( $docs_todos, function ( $a, $b ) {
				return strcasecmp( remove_accents( $a['titulo'] ), remove_accents( $b['titulo'] ) );
			} );
			?>
			<section class="jar-cartao jar-cartao--tabela"<?php echo $docs_todos ? ' data-jar-editavel' : ''; ?>>
				<header class="jar-cartao__cabeca jar-cartao__cabeca--acao">
					<div>
						<h2><?php esc_html_e( 'Documentos', 'jelly-area-reservada' ); ?></h2>
						<span class="jar-cartao__meta"><?php esc_html_e( 'Os associados veem os publicados na página deste evento', 'jelly-area-reservada' ); ?></span>
					</div>
					<span class="jar-cartao__botoes">
						<?php
						/*
						 * Um documento novo, carregado já para este evento: o formulário
						 * Novo documento abre com ele escolhido e, ao guardar, volta aqui.
						 */
						?>
						<a class="jar-btn jar-btn--pequeno jar-btn--contorno" href="<?php echo esc_url( jelly_ar_admin_url( 'documentos', [ 'novo' => 1, 'evento' => $evento['id'] ] ) ); ?>">
							<i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Novo documento', 'jelly-area-reservada' ); ?>
						</a>
						<?php if ( $docs_todos ) : ?>
							<?php // Cheio (cor-de-rosa) enquanto o evento não tem documentos, para chamar a atenção; em contorno depois. ?>
							<button type="button" class="jar-btn jar-btn--pequeno<?php echo $docs_evento ? ' jar-btn--contorno' : ''; ?>" data-jar-editar>
								<i class="fa-solid fa-pen" aria-hidden="true"></i> <?php esc_html_e( 'Escolher documentos', 'jelly-area-reservada' ); ?>
							</button>
						<?php endif; ?>
					</span>
				</header>

				<div data-jar-leitura>
					<?php if ( ! $docs_evento ) : ?>
						<p class="jar-vazio">
							<?php
							echo esc_html(
								$docs_todos
									? __( 'Este evento ainda não tem documentos.', 'jelly-area-reservada' )
									: __( 'Ainda não há documentos carregados. Depois de carregados em Documentos, escolhem-se aqui.', 'jelly-area-reservada' )
							);
							?>
						</p>
					<?php else : ?>
						<table class="jar-tabela">
							<tbody>
								<?php foreach ( $docs_evento as $d ) : ?>
									<tr>
										<td>
											<a class="jar-ficheiro" href="<?php echo esc_url( jelly_ar_admin_url( 'documentos', [ 'documento' => $d['id'] ] ) ); ?>">
												<span class="jar-ficheiro__icone jar-tipo--<?php echo esc_attr( $d['tipo'] ); ?>"><i class="fa-solid <?php echo esc_attr( jelly_ar_icone_ficheiro( $d['tipo'] ) ); ?>" aria-hidden="true"></i></span>
												<span>
													<strong><?php echo esc_html( $d['titulo'] ); ?></strong>
													<small><?php echo esc_html( ( $doc_cats[ $d['categoria'] ] ?? '—' ) . ' · ' . strtoupper( $d['tipo'] ) ); ?></small>
												</span>
											</a>
										</td>
										<td class="jar-tabela__fim"><?php jelly_ar_estado( $d['estado'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<?php if ( $docs_todos ) : ?>
					<?php // Vai para jelly_ar_evento_documentos_guardar(), em inc/documentos-dados.php. ?>
					<form class="jar-evento-docs" data-jar-edicao data-jar-gravar hidden method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="jelly_ar_evento_documentos">
						<input type="hidden" name="evento" value="<?php echo (int) $evento['id']; ?>">
						<?php wp_nonce_field( 'jelly_ar_evento_documentos_' . $evento['id'] ); ?>

						<?php
						/*
						 * A procura filtra a lista no browser, pelo título e pela
						 * categoria (assets/js/admin.js). Sem `name`: não vai com o
						 * formulário. Os escondidos pela procura continuam marcados
						 * ou não, e gravam-se como estão.
						 */
						?>
						<div class="jar-filtro jar-evento-docs__procura" role="search">
							<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
							<label class="screen-reader-text" for="jar-docs-procura"><?php esc_html_e( 'Procurar documentos', 'jelly-area-reservada' ); ?></label>
							<input type="search" id="jar-docs-procura" placeholder="<?php esc_attr_e( 'Procurar por título ou categoria', 'jelly-area-reservada' ); ?>" data-jar-filtrar="jar-docs-lista" autocomplete="off">
						</div>

						<fieldset class="jar-opcoes" id="jar-docs-lista">
							<legend class="screen-reader-text"><?php esc_html_e( 'Documentos deste evento', 'jelly-area-reservada' ); ?></legend>
							<?php foreach ( $docs_todos as $d ) : ?>
								<?php $outros = array_values( array_diff( $d['eventos'], [ $evento['titulo'] ] ) ); ?>
								<label class="jar-caixa" data-jar-filtrar-texto="<?php echo esc_attr( $d['titulo'] . ' ' . ( $doc_cats[ $d['categoria'] ] ?? '' ) ); ?>">
									<input type="checkbox" name="documentos[]" value="<?php echo (int) $d['id']; ?>" <?php checked( in_array( $d['id'], $docs_ids, true ) ); ?>>
									<span>
										<?php echo esc_html( $d['titulo'] ); ?>
										<small class="jar-evento-docs__meta">
											<?php
											echo esc_html( $doc_cats[ $d['categoria'] ] ?? '—' );
											if ( 'rascunho' === $d['estado'] ) {
												echo ' · ' . esc_html__( 'Rascunho', 'jelly-area-reservada' );
											}
											// Os outros eventos onde o documento já está: fica nesses e neste.
											if ( $outros ) {
												/* translators: %s: títulos dos outros eventos */
												echo ' · ' . esc_html( sprintf( __( 'Também em: %s', 'jelly-area-reservada' ), implode( ', ', $outros ) ) );
											}
											?>
										</small>
									</span>
								</label>
							<?php endforeach; ?>
							<p class="jar-evento-docs__nada" data-jar-filtrar-nada hidden><?php esc_html_e( 'Nenhum documento corresponde à procura.', 'jelly-area-reservada' ); ?></p>
						</fieldset>

						<footer class="jar-cartao__pe">
							<button type="button" class="jar-btn jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
							<button type="submit" class="jar-btn"><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></button>
						</footer>
					</form>
				<?php endif; ?>
			</section>
		</div>
	</div>
	<?php
	return;
endif;

/* ---------------------------------------------------------------- Lista */

$eventos     = jelly_ar_eventos_todos();
$tabela      = jelly_ar_eventos_lista();
$resultado   = jelly_ar_eventos_filtrar( $eventos, $tabela->pedido() );
$lista       = $tabela->paginar( $resultado['lista'] );
$contagem    = $resultado['contagem'];
$quando      = $tabela->get( 'quando' );
$pesquisa    = $tabela->get( 'q' );
$hoje        = current_time( 'Ymd' );
$proximos    = array_filter( $eventos, function ( $e ) use ( $hoje ) {
	return $e['fim'] >= $hoje && 'publicado' === $e['estado'];
} );
$so_reservada = array_filter( $eventos, function ( $e ) {
	return 'site' !== $e['onde'];
} );

// Os eventos com documentos, para a coluna Documentos: uma consulta para a lista toda.
$com_documentos = jelly_ar_eventos_com_documentos();

// Os eventos com a grelha pronta (mesas e horários), para a coluna Mesas e horários.
$com_grelha = jelly_ar_eventos_com_grelha();

$com_marcacoes = array_filter( $proximos, function ( $e ) {
	return $e['marcacoes'];
} );

$separadores = [
	'proximos' => __( 'Próximos', 'jelly-area-reservada' ),
	'passados' => __( 'Passados', 'jelly-area-reservada' ),
	'todos'    => __( 'Todos', 'jelly-area-reservada' ),
];
$resumo = [
	[ __( 'Próximos eventos', 'jelly-area-reservada' ), count( $proximos ), 'fa-calendar-days', 'azul' ],
	[ __( 'Na área reservada', 'jelly-area-reservada' ), count( $so_reservada ), 'fa-lock', 'roxo' ],
	[ __( 'Com marcações', 'jelly-area-reservada' ), count( $com_marcacoes ), 'fa-table-cells-large', 'magenta' ],
	[ __( 'Categorias', 'jelly-area-reservada' ), count( $categorias ), 'fa-tags', 'turquesa' ],
];
?>
<div class="jar-cabeca">
	<div>
		<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?></h1>
		<p class="jar-cabeca__intro"><?php esc_html_e( 'Os eventos do calendário do site e da área reservada — os mesmos, cada um com o sítio onde aparece.', 'jelly-area-reservada' ); ?></p>
	</div>
	<div class="jar-cabeca__acoes">
		<a class="jar-btn jar-btn--contorno" href="<?php echo esc_url( $gerir_categorias ); ?>"><i class="fa-solid fa-tags" aria-hidden="true"></i> <?php esc_html_e( 'Categorias', 'jelly-area-reservada' ); ?></a>
		<a class="jar-btn" href="<?php echo esc_url( jelly_ar_admin_url( 'eventos', [ 'novo' => 1 ] ) ); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Novo evento', 'jelly-area-reservada' ); ?></a>
	</div>
</div>

<?php $mostrar_aviso(); ?>

<div class="jar-numeros">
	<?php foreach ( $resumo as $r ) : ?>
		<div class="jar-cartao jar-numero">
			<span class="jar-icone jar-icone--<?php echo esc_attr( $r[3] ); ?>"><i class="fa-solid <?php echo esc_attr( $r[2] ); ?>" aria-hidden="true"></i></span>
			<span class="jar-numero__rotulo"><?php echo esc_html( $r[0] ); ?></span>
			<strong class="jar-numero__valor"><?php echo (int) $r[1]; ?></strong>
		</div>
	<?php endforeach; ?>
</div>

<section class="jar-cartao jar-cartao--tabela">
	<div class="jar-barra">
		<nav class="jar-separadores" aria-label="<?php esc_attr_e( 'Filtrar por data', 'jelly-area-reservada' ); ?>">
			<?php foreach ( $separadores as $s => $rotulo ) : ?>
				<?php $n = 'todos' === $s ? count( $resultado['encontrados'] ) : ( $contagem[ $s ] ?? 0 ); ?>
				<a class="jar-separador<?php echo $s === $quando ? ' is-atual' : ''; ?>" href="<?php echo esc_url( $tabela->url( [ 'quando' => $s ] ) ); ?>"<?php echo $s === $quando ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $rotulo ); ?> <b><?php echo (int) $n; ?></b>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="jar-barra__filtros">
			<form class="jar-escolha" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" data-jar-auto>
				<?php $tabela->campos_escondidos( [ 'onde' ] ); ?>
				<label class="screen-reader-text" for="jar-onde"><?php esc_html_e( 'Onde aparece', 'jelly-area-reservada' ); ?></label>
				<select id="jar-onde" name="onde">
					<?php // Sem filtro. Leva o nome do filtro, para não se ler como a opção "No site e na área reservada". ?>
					<option value=""><?php esc_html_e( 'Onde aparece: todos', 'jelly-area-reservada' ); ?></option>
					<?php foreach ( $onde_nomes as $valor => $nome ) : ?>
						<option value="<?php echo esc_attr( $valor ); ?>" <?php selected( $valor, $tabela->get( 'onde' ) ); ?>><?php echo esc_html( $nome ); ?></option>
					<?php endforeach; ?>
				</select>
			</form>

			<form class="jar-escolha" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" data-jar-auto>
				<?php $tabela->campos_escondidos( [ 'categoria' ] ); ?>
				<label class="screen-reader-text" for="jar-categoria"><?php esc_html_e( 'Categoria', 'jelly-area-reservada' ); ?></label>
				<select id="jar-categoria" name="categoria">
					<option value=""><?php esc_html_e( 'Todas as categorias', 'jelly-area-reservada' ); ?></option>
					<?php foreach ( $categorias as $slug => $c ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $tabela->get( 'categoria' ) ); ?>><?php echo esc_html( $c['nome'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</form>

			<form class="jar-filtro" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search" data-jar-pesquisa>
				<?php $tabela->campos_escondidos( [ 'q' ] ); ?>
				<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
				<label class="screen-reader-text" for="jar-pesquisa"><?php esc_html_e( 'Pesquisar eventos', 'jelly-area-reservada' ); ?></label>
				<input type="search" id="jar-pesquisa" name="q" value="<?php echo esc_attr( $pesquisa ); ?>" placeholder="<?php esc_attr_e( 'Título ou local', 'jelly-area-reservada' ); ?>">
			</form>
		</div>
	</div>

	<table class="jar-tabela">
		<thead>
			<tr>
				<?php
				$tabela->coluna( 'titulo', __( 'Evento', 'jelly-area-reservada' ) );
				$tabela->coluna( 'data', __( 'Datas', 'jelly-area-reservada' ), 'jar-col--data' );
				$tabela->coluna( 'local', __( 'Local', 'jelly-area-reservada' ), 'jar-col--local' );
				?>
				<th><?php esc_html_e( 'Onde aparece', 'jelly-area-reservada' ); ?></th>
				<th class="jar-col--marcacoes jar-tabela__centro"><?php esc_html_e( 'Aceita marcações', 'jelly-area-reservada' ); ?></th>
				<th class="jar-col--grelha jar-tabela__centro"><?php esc_html_e( 'Mesas e horários', 'jelly-area-reservada' ); ?></th>
				<th class="jar-col--documentos jar-tabela__centro"><?php esc_html_e( 'Documentos', 'jelly-area-reservada' ); ?></th>
				<th class="jar-col--estado"><?php esc_html_e( 'Estado', 'jelly-area-reservada' ); ?></th>
				<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $lista ) : ?>
				<tr>
					<td colspan="9" class="jar-vazio">
						<?php
						if ( '' !== $pesquisa ) {
							/* translators: %s: o que se pesquisou */
							printf( esc_html__( 'Nenhum evento corresponde a "%s".', 'jelly-area-reservada' ), esc_html( $pesquisa ) );
						} else {
							esc_html_e( 'Nenhum evento nesta lista.', 'jelly-area-reservada' );
						}
						?>
					</td>
				</tr>
			<?php endif; ?>
			<?php foreach ( $lista as $e ) : ?>
				<?php $abrir = jelly_ar_admin_url( 'eventos', [ 'evento' => $e['id'] ] ); ?>
				<tr data-jar-linha>
					<td>
						<a class="jar-ficheiro" href="<?php echo esc_url( $abrir ); ?>">
							<?php $capa( $e ); ?>
							<span>
								<strong><?php echo esc_html( $e['titulo'] ); ?></strong>
								<small><?php echo esc_html( $e['categoria_nome'] ? $e['categoria_nome'] : __( 'Sem categoria', 'jelly-area-reservada' ) ); ?></small>
							</span>
						</a>
					</td>
					<td class="jar-tabela__num jar-col--data"><?php echo esc_html( jelly_ar_intervalo_datas( $e['inicio'], $e['fim'] ) ); ?></td>
					<td class="jar-col--local"><?php echo esc_html( $e['local'] ? $e['local'] : '—' ); ?></td>
					<td><?php $onde( $e['onde'] ); ?></td>
					<td class="jar-col--marcacoes jar-tabela__centro"><?php jelly_ar_marca( $e['marcacoes'], __( 'Aceita marcações', 'jelly-area-reservada' ), __( 'Não aceita marcações', 'jelly-area-reservada' ) ); ?></td>
					<?php
					// O visto: mesas e horários prontos. O traço diz o que falta, ao passar.
					$grelha = in_array( $e['id'], $com_grelha, true );
					$falta  = $e['marcacoes'] ? __( 'Sem mesas ou sem horários: os associados não têm onde marcar', 'jelly-area-reservada' ) : __( 'Não aceita marcações', 'jelly-area-reservada' );
					?>
					<td class="jar-col--grelha jar-tabela__centro"><?php jelly_ar_marca( $grelha, __( 'Com mesas e horários', 'jelly-area-reservada' ), $falta ); ?></td>
					<td class="jar-col--documentos jar-tabela__centro"><?php jelly_ar_marca( in_array( $e['id'], $com_documentos, true ), __( 'Tem documentos', 'jelly-area-reservada' ), __( 'Sem documentos', 'jelly-area-reservada' ) ); ?></td>
					<td class="jar-col--estado"><?php jelly_ar_estado( $e['estado'] ); ?></td>
					<td class="jar-tabela__fim">
						<?php /* translators: %s: nome do evento */ ?>
						<a class="jar-acao" href="<?php echo esc_url( $abrir ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Abrir %s', 'jelly-area-reservada' ), $e['titulo'] ) ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php $tabela->paginacao(); ?>
</section>
