<?php
/**
 * Documentos: a lista, um documento (`documento=<id>`) e o formulário de um
 * novo (`novo=1`).
 *
 * Por onde a área se liga às outras:
 * - os documentos publicados aparecem aos associados na área reservada do
 *   site; os rascunhos, não;
 * - cada descarga fica registada com o associado que a fez, e o histórico do
 *   documento leva ao perfil dele nos Utilizadores;
 * - um documento pode estar em vários eventos, e aparece aos associados na
 *   página de cada um. Escolhe-se no cartão Eventos do documento ou no cartão
 *   Documentos do evento (templates/admin/eventos.php): os dois gravam a
 *   mesma tabela, jelly_ar_evento_documentos;
 * - o Calendário vai poder mostrar a publicação de um documento, quando essa
 *   área existir.
 */

defined( 'ABSPATH' ) || exit;

$documentos = jelly_ar_documentos_todos();
$categorias = jelly_ar_documento_categorias();
$id         = isset( $_GET['documento'] ) ? absint( $_GET['documento'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$novo       = ! empty( $_GET['novo'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$doc = $id ? jelly_ar_documento( $id ) : null;

/*
 * O que volta de guardar ou apagar: um aviso de sucesso, ou o erro que fez
 * o carregamento falhar.
 */
$aviso = isset( $_GET['aviso'] ) ? sanitize_key( wp_unslash( $_GET['aviso'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$erro  = isset( $_GET['erro'] ) ? sanitize_key( wp_unslash( $_GET['erro'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$maximo  = size_format( jelly_ar_documento_maximo() );
$avisos  = [
	'guardado'             => __( 'Documento guardado.', 'jelly-area-reservada' ),
	'atualizado'           => __( 'Dados do documento guardados.', 'jelly-area-reservada' ),
	'substituido'          => __( 'Dados guardados e ficheiro substituído. O anterior foi apagado.', 'jelly-area-reservada' ),
	'eventos'              => __( 'Eventos do documento guardados.', 'jelly-area-reservada' ),
	'apagado'              => __( 'Documento apagado, com o ficheiro e o histórico de descargas.', 'jelly-area-reservada' ),
	'categoria-criada'     => __( 'Categoria criada.', 'jelly-area-reservada' ),
	'categoria-atualizada' => __( 'Nome da categoria mudado.', 'jelly-area-reservada' ),
	'categoria-apagada'    => __( 'Categoria apagada.', 'jelly-area-reservada' ),
];
$erros   = [
	'campos'           => __( 'Falta o título ou a categoria.', 'jelly-area-reservada' ),
	'sem-ficheiro'     => __( 'Falta escolher o ficheiro.', 'jelly-area-reservada' ),
	/* translators: %s: tamanho máximo */
	'grande'           => sprintf( __( 'O ficheiro é maior do que o máximo aceite, %s.', 'jelly-area-reservada' ), $maximo ),
	'tipo'             => __( 'Esse tipo de ficheiro não é aceite. Use PDF, Word, Excel, PowerPoint ou ZIP.', 'jelly-area-reservada' ),
	'pasta'            => __( 'Não foi possível criar a pasta dos documentos no servidor.', 'jelly-area-reservada' ),
	'falhou'           => __( 'O ficheiro não foi guardado. Tente outra vez.', 'jelly-area-reservada' ),
	'categoria-nome'   => __( 'Escreva o nome da categoria.', 'jelly-area-reservada' ),
	'categoria-existe' => __( 'Já existe uma categoria com esse nome.', 'jelly-area-reservada' ),
	'categoria-em-uso' => __( 'Essa categoria tem documentos. Mude-os primeiro para outra categoria.', 'jelly-area-reservada' ),
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

// O título, a categoria e a descrição, iguais no formulário novo e na edição.
$campos = function ( $d ) use ( $categorias ) {
	?>
	<div class="jar-form">
		<label class="jar-campo jar-campo--largo">
			<span><?php esc_html_e( 'Título', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></span>
			<input type="text" name="titulo" value="<?php echo esc_attr( $d['titulo'] ?? '' ); ?>" required>
		</label>
		<label class="jar-campo jar-campo--largo">
			<span class="jar-campo__rotulo">
				<span><?php esc_html_e( 'Categoria', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></span>
				<a class="jar-link" href="<?php echo esc_url( jelly_ar_admin_url( 'documentos', [ 'categorias' => 1 ] ) ); ?>"><?php esc_html_e( 'Gerir categorias', 'jelly-area-reservada' ); ?></a>
			</span>
			<select name="categoria" required>
				<option value=""><?php esc_html_e( 'Escolher…', 'jelly-area-reservada' ); ?></option>
				<?php foreach ( $categorias as $c => $nome ) : ?>
					<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $c, $d['categoria'] ?? '' ); ?>><?php echo esc_html( $nome ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<label class="jar-campo jar-campo--largo">
			<span><?php esc_html_e( 'Descrição', 'jelly-area-reservada' ); ?></span>
			<textarea name="descricao" rows="3"><?php echo esc_textarea( $d['descricao'] ?? '' ); ?></textarea>
		</label>
	</div>
	<?php
};

/* ---------------------------------------------------------------- Categorias */

if ( ! empty( $_GET['categorias'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$lista_categorias = jelly_ar_categorias_com_contagem();
	?>
	<div class="jar-cabeca">
		<div>
			<a class="jar-voltar" href="<?php echo esc_url( jelly_ar_admin_url( 'documentos' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Documentos', 'jelly-area-reservada' ); ?></a>
			<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Categorias', 'jelly-area-reservada' ); ?></h1>
			<p class="jar-cabeca__intro"><?php esc_html_e( 'As categorias por que os associados filtram os documentos.', 'jelly-area-reservada' ); ?></p>
		</div>
	</div>

	<?php $mostrar_aviso(); ?>

	<div class="jar-grelha jar-grelha--2-1">
		<section class="jar-cartao jar-cartao--tabela">
			<table class="jar-tabela">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Categoria', 'jelly-area-reservada' ); ?></th>
						<th class="jar-tabela__num"><?php esc_html_e( 'Documentos', 'jelly-area-reservada' ); ?></th>
						<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $lista_categorias ) : ?>
						<tr><td colspan="3" class="jar-vazio"><?php esc_html_e( 'Ainda não há categorias.', 'jelly-area-reservada' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $lista_categorias as $c ) : ?>
						<?php $total = $c['reais'] + $c['exemplo']; ?>
						<tr data-jar-editavel>
							<td>
								<span class="jar-categoria-nome" data-jar-leitura>
									<strong data-jar-valor="nome"><?php echo esc_html( $c['nome'] ); ?></strong>
								</span>

								<?php // Mudar o nome, na própria linha; vai para jelly_ar_categoria_editar(). ?>
								<form class="jar-linha-edicao" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-jar-edicao data-jar-gravar hidden>
									<input type="hidden" name="action" value="jelly_ar_categoria_editar">
									<input type="hidden" name="categoria" value="<?php echo (int) $c['id']; ?>">
									<?php wp_nonce_field( 'jelly_ar_categoria_editar_' . $c['id'] ); ?>
									<label class="jar-campo">
										<span class="screen-reader-text"><?php esc_html_e( 'Nome da categoria', 'jelly-area-reservada' ); ?></span>
										<input type="text" name="nome" value="<?php echo esc_attr( $c['nome'] ); ?>" required maxlength="80">
									</label>
									<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
									<button type="submit" class="jar-btn jar-btn--pequeno"><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></button>
								</form>
							</td>
							<td class="jar-tabela__num">
								<?php if ( $total ) : ?>
									<a class="jar-link" href="<?php echo esc_url( jelly_ar_admin_url( 'documentos', [ 'categoria' => $c['slug'] ] ) ); ?>"><?php echo (int) $total; ?></a>
								<?php else : ?>
									0
								<?php endif; ?>
							</td>
							<td class="jar-tabela__fim">
								<span class="jar-acoes">
									<?php /* translators: %s: nome da categoria */ ?>
									<button type="button" class="jar-acao" data-jar-editar aria-label="<?php echo esc_attr( sprintf( __( 'Mudar o nome de %s', 'jelly-area-reservada' ), $c['nome'] ) ); ?>" title="<?php esc_attr_e( 'Mudar o nome', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i></button>

									<?php if ( $total ) : ?>
										<?php // Com documentos não se apaga: o botão diz porquê em vez de abrir a confirmação. ?>
										<button type="button" class="jar-acao" disabled title="<?php echo esc_attr( sprintf( _n( 'Tem %d documento: mude-o primeiro para outra categoria', 'Tem %d documentos: mude-os primeiro para outra categoria', $total, 'jelly-area-reservada' ), $total ) ); ?>"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
									<?php else : ?>
										<button
											type="button"
											class="jar-acao jar-acao--nao"
											data-jar-confirmar
											data-jar-form="jar-apagar-categoria-<?php echo (int) $c['id']; ?>"
											<?php /* translators: %s: nome da categoria */ ?>
											data-titulo="<?php echo esc_attr( sprintf( __( 'Apagar a categoria "%s"?', 'jelly-area-reservada' ), $c['nome'] ) ); ?>"
											data-texto="<?php esc_attr_e( 'Deixa de aparecer no filtro dos documentos. Não tem documentos, por isso nenhum fica sem categoria.', 'jelly-area-reservada' ); ?>"
											data-sim="<?php esc_attr_e( 'Apagar categoria', 'jelly-area-reservada' ); ?>"
											data-resultado=""
											aria-label="<?php echo esc_attr( sprintf( __( 'Apagar %s', 'jelly-area-reservada' ), $c['nome'] ) ); ?>"
											title="<?php esc_attr_e( 'Apagar', 'jelly-area-reservada' ); ?>"
										><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
										<form id="jar-apagar-categoria-<?php echo (int) $c['id']; ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
											<input type="hidden" name="action" value="jelly_ar_categoria_apagar">
											<input type="hidden" name="categoria" value="<?php echo (int) $c['id']; ?>">
											<?php wp_nonce_field( 'jelly_ar_categoria_apagar_' . $c['id'] ); ?>
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
			<?php // Vai para jelly_ar_categoria_criar(). ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="jelly_ar_categoria_criar">
				<?php wp_nonce_field( 'jelly_ar_categoria_criar' ); ?>
				<label class="jar-campo">
					<span><?php esc_html_e( 'Nome', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></span>
					<input type="text" name="nome" required maxlength="80" placeholder="<?php esc_attr_e( 'Ex.: Apoios', 'jelly-area-reservada' ); ?>">
				</label>
				<footer class="jar-cartao__pe">
					<button type="submit" class="jar-btn"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Criar categoria', 'jelly-area-reservada' ); ?></button>
				</footer>
			</form>
			<p class="jar-nota">
				<i class="fa-solid fa-circle-info" aria-hidden="true"></i>
				<?php esc_html_e( 'Uma categoria com documentos não se apaga. Mude primeiro os documentos para outra categoria.', 'jelly-area-reservada' ); ?>
			</p>
		</section>
	</div>
	<?php
	return;
endif;

/* ---------------------------------------------------------------- Novo */

if ( $novo ) :
	/*
	 * Vindo de um evento (o botão Novo documento do cartão Documentos dele):
	 * esse evento vem já escolhido, e o voltar e o cancelar levam a ele.
	 */
	$origem      = isset( $_GET['evento'] ) ? jelly_ar_evento( absint( $_GET['evento'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$novo_evts   = jelly_ar_eventos_todos();
	$sair        = $origem ? jelly_ar_admin_url( 'eventos', [ 'evento' => $origem['id'] ] ) : jelly_ar_admin_url( 'documentos' );
	usort( $novo_evts, function ( $a, $b ) {
		return strcmp( $b['inicio'], $a['inicio'] );
	} );
	?>
	<div class="jar-cabeca">
		<div>
			<a class="jar-voltar" href="<?php echo esc_url( $sair ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php echo esc_html( $origem ? $origem['titulo'] : __( 'Documentos', 'jelly-area-reservada' ) ); ?></a>
			<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Novo documento', 'jelly-area-reservada' ); ?></h1>
			<p class="jar-cabeca__intro"><?php esc_html_e( 'O ficheiro fica só para os associados: não tem endereço público.', 'jelly-area-reservada' ); ?></p>
		</div>
	</div>

	<?php $mostrar_aviso(); ?>

	<?php // Vai para jelly_ar_guardar_documento(), em inc/documentos-dados.php. ?>
	<form class="jar-cartao jar-cartao--estreito" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" data-jar-maximo="<?php echo (int) jelly_ar_documento_maximo(); ?>">
		<input type="hidden" name="action" value="jelly_ar_guardar_documento">
		<?php wp_nonce_field( 'jelly_ar_guardar_documento' ); ?>

		<?php // A zona de largar o ficheiro é um <label>: carregar nela abre o seletor. ?>
		<label class="jar-largar" data-jar-largar>
			<input type="file" name="ficheiro" accept=".pdf,.docx,.xlsx,.pptx,.zip" required>
			<i class="fa-solid fa-cloud-arrow-up jar-largar__icone" aria-hidden="true"></i>
			<strong data-jar-largar-nome><?php esc_html_e( 'Largue aqui o ficheiro, ou carregue para escolher', 'jelly-area-reservada' ); ?></strong>
			<?php /* translators: %s: tamanho máximo */ ?>
			<span><?php echo esc_html( sprintf( __( 'PDF, Word, Excel, PowerPoint ou ZIP, até %s', 'jelly-area-reservada' ), $maximo ) ); ?></span>
		</label>

		<?php $campos( [] ); ?>

		<?php if ( $novo_evts ) : ?>
			<?php
			/*
			 * Os eventos onde o documento aparece, já ao criar — o mesmo que o
			 * cartão Eventos da página do documento, depois. Opcional.
			 */
			?>
			<div class="jar-campo--largo jar-novo-eventos">
				<span class="jar-novo-eventos__titulo"><?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?> <small><?php esc_html_e( 'opcional — onde o documento aparece aos associados', 'jelly-area-reservada' ); ?></small></span>

				<div class="jar-filtro jar-evento-docs__procura" role="search">
					<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
					<label class="screen-reader-text" for="jar-novo-eventos-procura"><?php esc_html_e( 'Procurar eventos', 'jelly-area-reservada' ); ?></label>
					<input type="search" id="jar-novo-eventos-procura" placeholder="<?php esc_attr_e( 'Procurar por título, data ou local', 'jelly-area-reservada' ); ?>" data-jar-filtrar="jar-novo-eventos-lista" autocomplete="off">
				</div>

				<fieldset class="jar-opcoes jar-novo-eventos__lista" id="jar-novo-eventos-lista">
					<legend class="screen-reader-text"><?php esc_html_e( 'Eventos do documento', 'jelly-area-reservada' ); ?></legend>
					<?php foreach ( $novo_evts as $e ) : ?>
						<?php $quando = jelly_ar_intervalo_datas( $e['inicio'], $e['fim'] ) . ( $e['local'] ? ' · ' . $e['local'] : '' ); ?>
						<label class="jar-caixa" data-jar-filtrar-texto="<?php echo esc_attr( $e['titulo'] . ' ' . $quando ); ?>">
							<input type="checkbox" name="eventos[]" value="<?php echo (int) $e['id']; ?>" <?php checked( $origem && $origem['id'] === $e['id'] ); ?>>
							<span>
								<?php echo esc_html( $e['titulo'] ); ?>
								<small class="jar-evento-docs__meta"><?php echo esc_html( $quando . ( 'rascunho' === $e['estado'] ? ' · ' . __( 'Rascunho', 'jelly-area-reservada' ) : '' ) ); ?></small>
							</span>
						</label>
					<?php endforeach; ?>
					<p class="jar-evento-docs__nada" data-jar-filtrar-nada hidden><?php esc_html_e( 'Nenhum evento corresponde à procura.', 'jelly-area-reservada' ); ?></p>
				</fieldset>

				<?php
				/*
				 * Quantos eventos estão escolhidos, e quais — também os que a procura
				 * esconde. Escrito já para o que vem marcado; o assets/js/admin.js
				 * (data-jar-contar) acompanha as mudanças.
				 */
				$ja = $origem ? [ $origem['titulo'] ] : [];
				?>
				<p class="jar-novo-eventos__nota" data-jar-contar="jar-novo-eventos-lista" aria-live="polite">
					<i class="fa-solid fa-circle-info" aria-hidden="true"></i>
					<span data-jar-contar-texto>
						<?php
						echo esc_html(
							$ja
								/* translators: %s: título do evento */
								? sprintf( __( '1 evento escolhido: %s', 'jelly-area-reservada' ), $ja[0] )
								: __( 'Nenhum evento escolhido: o documento fica só na lista de Documentos.', 'jelly-area-reservada' )
						);
						?>
					</span>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( $origem ) : ?>
			<?php // Para, ao guardar, voltar ao evento de onde se veio (jelly_ar_guardar_documento()). ?>
			<input type="hidden" name="evento_origem" value="<?php echo (int) $origem['id']; ?>">
		<?php endif; ?>

		<fieldset class="jar-opcoes">
			<legend><?php esc_html_e( 'Publicação', 'jelly-area-reservada' ); ?></legend>
			<label class="jar-caixa"><input type="radio" name="estado" value="publicado" checked> <span><?php esc_html_e( 'Publicar já — fica visível para os associados', 'jelly-area-reservada' ); ?></span></label>
			<label class="jar-caixa"><input type="radio" name="estado" value="rascunho"> <span><?php esc_html_e( 'Guardar como rascunho', 'jelly-area-reservada' ); ?></span></label>
		</fieldset>

		<footer class="jar-cartao__pe">
			<a class="jar-btn jar-btn--contorno" href="<?php echo esc_url( $sair ); ?>"><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></a>
			<button type="submit" class="jar-btn"><?php esc_html_e( 'Guardar documento', 'jelly-area-reservada' ); ?></button>
		</footer>
	</form>
	<?php
	return;
endif;

/* ---------------------------------------------------------------- Documento */

if ( $doc ) :
	$descargas = jelly_ar_descargas_de( $doc, 5 );
	$publicado = 'publicado' === $doc['estado'];
	$real      = ! empty( $doc['real'] );
	?>
	<?php $mostrar_aviso(); ?>

	<div class="jar-cabeca">
		<div>
			<a class="jar-voltar" href="<?php echo esc_url( jelly_ar_admin_url( 'documentos' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Documentos', 'jelly-area-reservada' ); ?></a>
			<h1 class="jar-cabeca__titulo"><?php echo esc_html( $doc['titulo'] ); ?> <span data-jar-estado-perfil><?php jelly_ar_estado( $doc['estado'] ); ?></span></h1>
			<p class="jar-cabeca__intro"><?php echo esc_html( $categorias[ $doc['categoria'] ] ?? '' ); ?></p>
		</div>
		<div class="jar-cabeca__acoes">
			<button
				type="button"
				class="jar-btn jar-btn--discreto"
				data-jar-confirmar
				data-titulo="<?php esc_attr_e( 'Apagar este documento?', 'jelly-area-reservada' ); ?>"
				data-texto="<?php esc_attr_e( 'O ficheiro e o histórico de descargas são apagados de vez, e os associados deixam de o ver.', 'jelly-area-reservada' ); ?>"
				data-sim="<?php esc_attr_e( 'Apagar documento', 'jelly-area-reservada' ); ?>"
				data-resultado=""
				<?php echo $real ? 'data-jar-form="jar-apagar"' : ''; ?>
			><i class="fa-regular fa-trash-can" aria-hidden="true"></i> <?php esc_html_e( 'Apagar', 'jelly-area-reservada' ); ?></button>

			<?php if ( $real ) : ?>
				<?php // Enviado pela confirmação; vai para jelly_ar_apagar_documento(). ?>
				<form id="jar-apagar" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
					<input type="hidden" name="action" value="jelly_ar_apagar_documento">
					<input type="hidden" name="documento" value="<?php echo (int) $doc['id']; ?>">
					<?php wp_nonce_field( 'jelly_ar_apagar_documento_' . $doc['id'] ); ?>
				</form>
			<?php endif; ?>

			<?php if ( $publicado ) : ?>
				<button
					type="button"
					class="jar-btn jar-btn--contorno"
					data-jar-confirmar
					data-titulo="<?php esc_attr_e( 'Tirar da área reservada?', 'jelly-area-reservada' ); ?>"
					data-texto="<?php esc_attr_e( 'O documento passa a rascunho e os associados deixam de o ver. Pode voltar a publicá-lo quando quiser.', 'jelly-area-reservada' ); ?>"
					data-sim="<?php esc_attr_e( 'Passar a rascunho', 'jelly-area-reservada' ); ?>"
					data-resultado="rascunho"
				><?php esc_html_e( 'Passar a rascunho', 'jelly-area-reservada' ); ?></button>
			<?php else : ?>
				<button type="button" class="jar-btn" data-jar-decidir="publicado"><?php esc_html_e( 'Publicar', 'jelly-area-reservada' ); ?></button>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! $publicado ) : ?>
		<div class="jar-aviso jar-aviso--pendente">
			<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
			<p><?php esc_html_e( 'Rascunho: os associados ainda não veem este documento.', 'jelly-area-reservada' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="jar-grelha jar-grelha--1-2">
		<section class="jar-cartao jar-perfil jar-ficheiro-cartao">
			<span class="jar-ficheiro-cartao__icone jar-tipo--<?php echo esc_attr( $doc['tipo'] ); ?>"><i class="fa-solid <?php echo esc_attr( jelly_ar_icone_ficheiro( $doc['tipo'] ) ); ?>" aria-hidden="true"></i></span>
			<h2><?php echo esc_html( $doc['ficheiro'] ); ?></h2>
			<p><?php echo esc_html( strtoupper( $doc['tipo'] ) . ' · ' . size_format( $doc['tamanho'], 1 ) ); ?></p>

			<dl class="jar-dados">
				<dt><?php esc_html_e( 'Publicado', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo esc_html( $publicado ? $doc['data'] : '—' ); ?></dd>
				<dt><?php esc_html_e( 'Por', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo esc_html( $doc['autor'] ); ?></dd>
				<dt><?php esc_html_e( 'Descargas', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo (int) $doc['descargas']; ?></dd>
				<dt><?php esc_html_e( 'Visível para', 'jelly-area-reservada' ); ?></dt>
				<dd><?php esc_html_e( 'Associados', 'jelly-area-reservada' ); ?></dd>
			</dl>

			<div class="jar-botoes">
				<?php if ( $real ) : ?>
					<a class="jar-btn jar-btn--largo" href="<?php echo esc_url( jelly_ar_url_descarregar( $doc['id'] ) ); ?>"><i class="fa-solid fa-download" aria-hidden="true"></i> <?php esc_html_e( 'Descarregar', 'jelly-area-reservada' ); ?></a>
				<?php else : ?>
					<button type="button" class="jar-btn jar-btn--largo" disabled title="<?php esc_attr_e( 'Documento de exemplo, sem ficheiro', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-download" aria-hidden="true"></i> <?php esc_html_e( 'Descarregar', 'jelly-area-reservada' ); ?></button>
				<?php endif; ?>
			</div>
		</section>

		<div class="jar-pilha">
			<?php // Um erro ao guardar volta com `editar=1`: o cartão abre já em edição. ?>
			<section class="jar-cartao" data-jar-editavel<?php echo ! empty( $_GET['editar'] ) ? ' data-jar-abrir' : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>>
				<header class="jar-cartao__cabeca">
					<h2><?php esc_html_e( 'Dados do documento', 'jelly-area-reservada' ); ?></h2>
					<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-editar>
						<i class="fa-solid fa-pen" aria-hidden="true"></i> <?php esc_html_e( 'Editar', 'jelly-area-reservada' ); ?>
					</button>
				</header>

				<dl class="jar-leitura" data-jar-leitura>
					<div class="jar-campo--largo">
						<dt><?php esc_html_e( 'Título', 'jelly-area-reservada' ); ?></dt>
						<dd data-jar-valor="titulo"><?php echo esc_html( $doc['titulo'] ); ?></dd>
					</div>
					<div class="jar-campo--largo">
						<dt><?php esc_html_e( 'Categoria', 'jelly-area-reservada' ); ?></dt>
						<dd data-jar-valor="categoria"><?php echo esc_html( $categorias[ $doc['categoria'] ] ?? '—' ); ?></dd>
					</div>
					<div class="jar-campo--largo">
						<dt><?php esc_html_e( 'Descrição', 'jelly-area-reservada' ); ?></dt>
						<dd data-jar-valor="descricao"><?php echo esc_html( $doc['descricao'] ? $doc['descricao'] : '—' ); ?></dd>
					</div>
				</dl>

				<?php
				/*
				 * Num documento real, a edição grava (jelly_ar_editar_documento()),
				 * com o ficheiro novo, se vier. Nos de exemplo fica só no ecrã.
				 */
				?>
				<form
					data-jar-edicao
					hidden
					<?php if ( $real ) : ?>
						data-jar-gravar
						method="post"
						action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						enctype="multipart/form-data"
						data-jar-maximo="<?php echo (int) jelly_ar_documento_maximo(); ?>"
					<?php endif; ?>
				>
					<?php if ( $real ) : ?>
						<input type="hidden" name="action" value="jelly_ar_editar_documento">
						<input type="hidden" name="documento" value="<?php echo (int) $doc['id']; ?>">
						<?php wp_nonce_field( 'jelly_ar_editar_documento_' . $doc['id'] ); ?>
					<?php endif; ?>

					<?php $campos( $doc ); ?>

					<div class="jar-campo jar-campo--substituir">
						<span><?php esc_html_e( 'Substituir ficheiro', 'jelly-area-reservada' ); ?></span>
						<label class="jar-largar jar-largar--pequeno" data-jar-largar>
							<input type="file" name="ficheiro" accept=".pdf,.docx,.xlsx,.pptx,.zip">
							<i class="fa-solid fa-arrow-right-arrow-left jar-largar__icone" aria-hidden="true"></i>
							<?php /* translators: %s: nome do ficheiro atual */ ?>
							<strong data-jar-largar-nome><?php echo esc_html( sprintf( __( 'Fica %s. Largue aqui outro para o substituir.', 'jelly-area-reservada' ), $doc['ficheiro'] ) ); ?></strong>
							<?php /* translators: %s: tamanho máximo */ ?>
							<span><?php echo esc_html( sprintf( __( 'Opcional · PDF, Word, Excel, PowerPoint ou ZIP, até %s', 'jelly-area-reservada' ), $maximo ) ); ?></span>
						</label>
					</div>

					<footer class="jar-cartao__pe">
						<button type="button" class="jar-btn jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
						<button type="submit" class="jar-btn"><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></button>
					</footer>
				</form>
			</section>

			<?php
			/*
			 * Os eventos do documento, fora da edição dos dados — o espelho do
			 * cartão Documentos do evento. Os dois leem e gravam a mesma tabela
			 * (jelly_ar_evento_documentos): o que se marcou lá aparece aqui já
			 * marcado, e ao contrário. Um documento pode estar em vários eventos.
			 *
			 * Só nos documentos da AR; os de exemplo não têm onde guardar a ligação.
			 */
			?>
			<?php if ( $real ) : ?>
				<?php
				$doc_eventos = jelly_ar_documento_eventos( $doc['id'] );
				$marcados    = wp_list_pluck( $doc_eventos, 'id' );
				$eventos     = jelly_ar_eventos_todos();

				// Os que ainda vêm primeiro, do mais próximo; os que já passaram a seguir.
				$hoje = current_time( 'Ymd' );
				usort( $eventos, function ( $a, $b ) use ( $hoje ) {
					$pa = ( $a['fim'] ? $a['fim'] : $a['inicio'] ) < $hoje;
					$pb = ( $b['fim'] ? $b['fim'] : $b['inicio'] ) < $hoje;

					if ( $pa !== $pb ) {
						return $pa ? 1 : -1;
					}

					return $pa ? strcmp( $b['inicio'], $a['inicio'] ) : strcmp( $a['inicio'], $b['inicio'] );
				} );
				?>
				<section class="jar-cartao jar-cartao--tabela"<?php echo $eventos ? ' data-jar-editavel' : ''; ?>>
					<header class="jar-cartao__cabeca jar-cartao__cabeca--acao">
						<div>
							<h2><?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?></h2>
							<span class="jar-cartao__meta"><?php esc_html_e( 'Os associados veem este documento na página de cada um destes eventos', 'jelly-area-reservada' ); ?></span>
						</div>
						<?php if ( $eventos ) : ?>
							<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-editar>
								<i class="fa-solid fa-pen" aria-hidden="true"></i> <?php esc_html_e( 'Escolher eventos', 'jelly-area-reservada' ); ?>
							</button>
						<?php endif; ?>
					</header>

					<div data-jar-leitura>
						<?php if ( ! $doc_eventos ) : ?>
							<p class="jar-vazio"><?php esc_html_e( 'Este documento ainda não está em nenhum evento.', 'jelly-area-reservada' ); ?></p>
						<?php else : ?>
							<table class="jar-tabela">
								<tbody>
									<?php foreach ( $doc_eventos as $e ) : ?>
										<tr>
											<td>
												<a class="jar-link" href="<?php echo esc_url( jelly_ar_admin_url( 'eventos', [ 'evento' => $e['id'] ] ) ); ?>"><strong><?php echo esc_html( $e['titulo'] ); ?></strong></a>
												<small class="jar-escolha__meta"><?php echo esc_html( jelly_ar_intervalo_datas( $e['inicio'], $e['fim'] ) . ( $e['local'] ? ' · ' . $e['local'] : '' ) ); ?></small>
											</td>
											<td class="jar-tabela__fim"><?php jelly_ar_estado( $e['estado'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<?php if ( $eventos ) : ?>
						<?php // Vai para jelly_ar_documento_eventos_guardar(), em inc/documentos-dados.php. ?>
						<form class="jar-evento-docs" data-jar-edicao data-jar-gravar hidden method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="jelly_ar_documento_eventos">
							<input type="hidden" name="documento" value="<?php echo (int) $doc['id']; ?>">
							<?php wp_nonce_field( 'jelly_ar_documento_eventos_' . $doc['id'] ); ?>

							<div class="jar-filtro jar-evento-docs__procura" role="search">
								<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
								<label class="screen-reader-text" for="jar-eventos-procura"><?php esc_html_e( 'Procurar eventos', 'jelly-area-reservada' ); ?></label>
								<input type="search" id="jar-eventos-procura" placeholder="<?php esc_attr_e( 'Procurar por título, data ou local', 'jelly-area-reservada' ); ?>" data-jar-filtrar="jar-eventos-lista" autocomplete="off">
							</div>

							<fieldset class="jar-opcoes" id="jar-eventos-lista">
								<legend class="screen-reader-text"><?php esc_html_e( 'Eventos deste documento', 'jelly-area-reservada' ); ?></legend>
								<?php foreach ( $eventos as $e ) : ?>
									<?php $quando = jelly_ar_intervalo_datas( $e['inicio'], $e['fim'] ) . ( $e['local'] ? ' · ' . $e['local'] : '' ); ?>
									<label class="jar-caixa" data-jar-filtrar-texto="<?php echo esc_attr( $e['titulo'] . ' ' . $quando ); ?>">
										<input type="checkbox" name="eventos[]" value="<?php echo (int) $e['id']; ?>" <?php checked( in_array( $e['id'], $marcados, true ) ); ?>>
										<span>
											<?php echo esc_html( $e['titulo'] ); ?>
											<small class="jar-evento-docs__meta">
												<?php
												echo esc_html( $quando );
												if ( 'rascunho' === $e['estado'] ) {
													echo ' · ' . esc_html__( 'Rascunho', 'jelly-area-reservada' );
												}
												?>
											</small>
										</span>
									</label>
								<?php endforeach; ?>
								<p class="jar-evento-docs__nada" data-jar-filtrar-nada hidden><?php esc_html_e( 'Nenhum evento corresponde à procura.', 'jelly-area-reservada' ); ?></p>
							</fieldset>

							<footer class="jar-cartao__pe">
								<button type="button" class="jar-btn jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
								<button type="submit" class="jar-btn"><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></button>
							</footer>
						</form>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<section class="jar-cartao jar-cartao--tabela">
				<header class="jar-cartao__cabeca">
					<div>
						<h2><?php esc_html_e( 'Quem descarregou', 'jelly-area-reservada' ); ?></h2>
						<?php if ( $descargas ) : ?>
							<span class="jar-cartao__meta">
								<?php
								if ( count( $descargas ) < $doc['descargas'] ) {
									/* translators: 1: mostradas, 2: total de descargas */
									printf( esc_html__( 'Os %1$d mais recentes de %2$d', 'jelly-area-reservada' ), count( $descargas ), (int) $doc['descargas'] );
								} else {
									/* translators: %d: número de descargas */
									printf( esc_html( _n( '%d descarga', '%d descargas', (int) $doc['descargas'], 'jelly-area-reservada' ) ), (int) $doc['descargas'] );
								}
								?>
							</span>
						<?php endif; ?>
					</div>
					<?php if ( $descargas ) : ?>
						<a class="jar-btn jar-btn--pequeno jar-btn--contorno" href="<?php echo esc_url( jelly_ar_url_exportar_descargas( $doc['id'] ) ); ?>">
							<i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> <?php esc_html_e( 'Exportar todas', 'jelly-area-reservada' ); ?>
						</a>
					<?php endif; ?>
				</header>
				<?php if ( ! $descargas ) : ?>
					<p class="jar-vazio"><?php echo esc_html( $publicado ? __( 'Ainda ninguém o descarregou.', 'jelly-area-reservada' ) : __( 'Os rascunhos não se descarregam.', 'jelly-area-reservada' ) ); ?></p>
				<?php else : ?>
					<table class="jar-tabela">
						<thead><tr><th><?php esc_html_e( 'Associado', 'jelly-area-reservada' ); ?></th><th class="jar-col--empresa"><?php esc_html_e( 'Empresa', 'jelly-area-reservada' ); ?></th><th><?php esc_html_e( 'Data e hora', 'jelly-area-reservada' ); ?></th></tr></thead>
						<tbody>
							<?php foreach ( $descargas as $x ) : ?>
								<tr>
									<td>
										<a class="jar-pessoa" href="<?php echo esc_url( jelly_ar_admin_url( 'utilizadores', [ 'utilizador' => $x['utilizador'] ] ) ); ?>">
											<span class="jar-avatar"><?php echo esc_html( jelly_ar_iniciais( $x['nome'] ) ); ?></span>
											<strong><?php echo esc_html( $x['nome'] ); ?></strong>
										</a>
									</td>
									<td class="jar-col--empresa"><?php echo esc_html( $x['empresa'] ? $x['empresa'] : '—' ); ?></td>
									<td class="jar-tabela__num"><?php jelly_ar_data_hora( $x['quando'] ); ?></td>
								</tr>
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

$tabela   = jelly_ar_documentos_lista();
$filtro   = $tabela->get( 'estado' );
$pesquisa = $tabela->get( 'q' );

$resultado   = jelly_ar_documentos_filtrar( $documentos, $tabela->pedido() );
$encontrados = $resultado['encontrados'];
$lista       = $tabela->paginar( $resultado['lista'] );
$contagem    = array_count_values( array_column( $encontrados, 'estado' ) );

$todos   = array_count_values( array_column( $documentos, 'estado' ) );
$filtros = [
	''          => __( 'Todos', 'jelly-area-reservada' ),
	'publicado' => __( 'Publicados', 'jelly-area-reservada' ),
	'rascunho'  => __( 'Rascunhos', 'jelly-area-reservada' ),
];
$resumo  = [
	[ __( 'Documentos', 'jelly-area-reservada' ), count( $documentos ), 'fa-file-lines', 'azul', '' ],
	[ __( 'Publicados', 'jelly-area-reservada' ), $todos['publicado'] ?? 0, 'fa-eye', 'turquesa', 'publicado' ],
	[ __( 'Rascunhos', 'jelly-area-reservada' ), $todos['rascunho'] ?? 0, 'fa-eye-slash', 'roxo', 'rascunho' ],
	[ __( 'Descargas (30 dias)', 'jelly-area-reservada' ), 318, 'fa-download', 'magenta', null ],
];
?>
<div class="jar-cabeca">
	<div>
		<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Documentos', 'jelly-area-reservada' ); ?></h1>
		<p class="jar-cabeca__intro"><?php esc_html_e( 'Guias de mercado, formulários, apresentações e regulamentos, só para os associados.', 'jelly-area-reservada' ); ?></p>
	</div>
	<div class="jar-cabeca__acoes">
		<a class="jar-btn jar-btn--contorno" href="<?php echo esc_url( jelly_ar_admin_url( 'documentos', [ 'categorias' => 1 ] ) ); ?>"><i class="fa-solid fa-tags" aria-hidden="true"></i> <?php esc_html_e( 'Categorias', 'jelly-area-reservada' ); ?></a>
		<a class="jar-btn" href="<?php echo esc_url( jelly_ar_admin_url( 'documentos', [ 'novo' => 1 ] ) ); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Novo documento', 'jelly-area-reservada' ); ?></a>
	</div>
</div>

<?php $mostrar_aviso(); ?>

<div class="jar-numeros">
	<?php foreach ( $resumo as $r ) : ?>
		<?php
		$tag  = null === $r[4] ? 'div' : 'a';
		$href = null === $r[4] ? '' : ' href="' . esc_url( jelly_ar_admin_url( 'documentos', $r[4] ? [ 'estado' => $r[4] ] : [] ) ) . '"';
		?>
		<<?php echo esc_html( $tag ); ?> class="jar-cartao jar-numero"<?php echo $href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado acima ?>>
			<span class="jar-icone jar-icone--<?php echo esc_attr( $r[3] ); ?>"><i class="fa-solid <?php echo esc_attr( $r[2] ); ?>" aria-hidden="true"></i></span>
			<span class="jar-numero__rotulo"><?php echo esc_html( $r[0] ); ?></span>
			<strong class="jar-numero__valor"><?php echo (int) $r[1]; ?></strong>
		</<?php echo esc_html( $tag ); ?>>
	<?php endforeach; ?>
</div>

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

		<div class="jar-barra__filtros">
			<form class="jar-escolha" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" data-jar-auto>
				<?php $tabela->campos_escondidos( [ 'categoria' ] ); ?>
				<label class="screen-reader-text" for="jar-categoria"><?php esc_html_e( 'Categoria', 'jelly-area-reservada' ); ?></label>
				<select id="jar-categoria" name="categoria">
					<option value=""><?php esc_html_e( 'Todas as categorias', 'jelly-area-reservada' ); ?></option>
					<?php foreach ( $categorias as $c => $nome ) : ?>
						<option value="<?php echo esc_attr( $c ); ?>" <?php selected( $c, $tabela->get( 'categoria' ) ); ?>><?php echo esc_html( $nome ); ?></option>
					<?php endforeach; ?>
				</select>
				<noscript><button type="submit" class="jar-btn jar-btn--pequeno jar-btn--contorno"><?php esc_html_e( 'Aplicar', 'jelly-area-reservada' ); ?></button></noscript>
			</form>

			<form class="jar-filtro" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search" data-jar-pesquisa>
				<?php $tabela->campos_escondidos( [ 'q' ] ); ?>
				<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
				<label class="screen-reader-text" for="jar-pesquisa"><?php esc_html_e( 'Pesquisar documentos', 'jelly-area-reservada' ); ?></label>
				<input type="search" id="jar-pesquisa" name="q" value="<?php echo esc_attr( $pesquisa ); ?>" placeholder="<?php esc_attr_e( 'Título, descrição ou ficheiro', 'jelly-area-reservada' ); ?>">
			</form>
		</div>
	</div>

	<table class="jar-tabela">
		<thead>
			<tr>
				<?php
				$tabela->coluna( 'titulo', __( 'Documento', 'jelly-area-reservada' ) );
				$tabela->coluna( 'categoria', __( 'Categoria', 'jelly-area-reservada' ), 'jar-col--categoria' );
				$tabela->coluna( 'data', __( 'Data', 'jelly-area-reservada' ), 'jar-col--data' );
				?>
				<th class="jar-col--eventos jar-tabela__centro"><?php esc_html_e( 'Eventos', 'jelly-area-reservada' ); ?></th>
				<?php
				$tabela->coluna( 'descargas', __( 'Descargas', 'jelly-area-reservada' ), 'jar-col--descargas' );
				$tabela->coluna( 'estado', __( 'Estado', 'jelly-area-reservada' ) );
				?>
				<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $lista ) : ?>
				<tr>
					<td colspan="7" class="jar-vazio">
						<?php
						if ( '' !== $pesquisa ) {
							/* translators: %s: o que se pesquisou */
							printf( esc_html__( 'Nenhum documento corresponde a "%s".', 'jelly-area-reservada' ), esc_html( $pesquisa ) );
						} else {
							esc_html_e( 'Nenhum documento nesta lista.', 'jelly-area-reservada' );
						}
						?>
					</td>
				</tr>
			<?php endif; ?>
			<?php foreach ( $lista as $d ) : ?>
				<?php $abrir = jelly_ar_admin_url( 'documentos', [ 'documento' => $d['id'] ] ); ?>
				<tr data-jar-linha>
					<td>
						<a class="jar-ficheiro" href="<?php echo esc_url( $abrir ); ?>">
							<span class="jar-ficheiro__icone jar-tipo--<?php echo esc_attr( $d['tipo'] ); ?>"><i class="fa-solid <?php echo esc_attr( jelly_ar_icone_ficheiro( $d['tipo'] ) ); ?>" aria-hidden="true"></i></span>
							<span>
								<strong><?php echo esc_html( $d['titulo'] ); ?></strong>
								<small><?php echo esc_html( strtoupper( $d['tipo'] ) . ' · ' . size_format( $d['tamanho'], 1 ) ); ?></small>
							</span>
						</a>
					</td>
					<td class="jar-col--categoria"><?php echo esc_html( $categorias[ $d['categoria'] ] ?? '—' ); ?></td>
					<td class="jar-tabela__num jar-col--data"><?php jelly_ar_data_hora( $d['data'] ); ?></td>
					<?php // Os nomes dos eventos vão no visto: aparecem ao passar e o leitor de ecrã lê-os. ?>
					<?php /* translators: %s: títulos dos eventos */ ?>
					<td class="jar-col--eventos jar-tabela__centro"><?php jelly_ar_marca( ! empty( $d['eventos'] ), sprintf( __( 'Em: %s', 'jelly-area-reservada' ), implode( ', ', $d['eventos'] ?? [] ) ), __( 'Sem eventos', 'jelly-area-reservada' ) ); ?></td>
					<td class="jar-tabela__num jar-col--descargas"><?php echo (int) $d['descargas']; ?></td>
					<td data-jar-estado><?php jelly_ar_estado( $d['estado'] ); ?></td>
					<td class="jar-tabela__fim">
						<span class="jar-acoes">
							<?php if ( ! empty( $d['real'] ) ) : ?>
								<?php /* translators: %s: título do documento */ ?>
								<a class="jar-acao" href="<?php echo esc_url( jelly_ar_url_descarregar( $d['id'] ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Descarregar %s', 'jelly-area-reservada' ), $d['titulo'] ) ); ?>" title="<?php esc_attr_e( 'Descarregar', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-download" aria-hidden="true"></i></a>
							<?php endif; ?>
							<?php /* translators: %s: título do documento */ ?>
							<a class="jar-acao" href="<?php echo esc_url( $abrir ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Abrir %s', 'jelly-area-reservada' ), $d['titulo'] ) ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
						</span>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php $tabela->paginacao(); ?>
</section>
