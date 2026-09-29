<?php
/**
 * Encontros: a lista, um encontro (`encontro=<id>`) e o formulário de um novo
 * (`novo=1`).
 *
 * Um encontro é como um artigo de notícias, com vídeos do YouTube; só os
 * associados o veem, na Área Reservada (inc/encontros-dados.php). Os vídeos
 * ficam "não listados" no YouTube: o endereço só está aqui.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$id       = isset( $_GET['encontro'] ) ? absint( $_GET['encontro'] ) : 0;
$novo     = ! empty( $_GET['novo'] );
$editar   = isset( $_GET['editar'] ) ? sanitize_key( wp_unslash( $_GET['editar'] ) ) : '';
$aviso    = isset( $_GET['aviso'] ) ? sanitize_key( wp_unslash( $_GET['aviso'] ) ) : '';
$erro     = isset( $_GET['erro'] ) ? sanitize_key( wp_unslash( $_GET['erro'] ) ) : '';
// phpcs:enable
$encontro = $id ? jelly_ar_encontro( $id ) : null;

$avisos = [
	'criado'     => __( 'Encontro criado.', 'jelly-area-reservada' ),
	'atualizado' => __( 'Encontro guardado.', 'jelly-area-reservada' ),
	'videos'     => __( 'Vídeos guardados.', 'jelly-area-reservada' ),
	'publicado'  => __( 'Encontro publicado: os associados já o veem na Área Reservada.', 'jelly-area-reservada' ),
	'rascunho'   => __( 'Encontro passado a rascunho: saiu da Área Reservada.', 'jelly-area-reservada' ),
	'lixo'       => __( 'Encontro enviado para o lixo: saiu das listas e da Área Reservada, e fica guardado para se poder recuperar.', 'jelly-area-reservada' ),
];
$erros  = [
	'campos' => __( 'Falta o título ou a data.', 'jelly-area-reservada' ),
	'video'  => __( 'Um dos links não é de um vídeo do YouTube. Cole o link do vídeo, por exemplo https://youtu.be/…', 'jelly-area-reservada' ),
	'falhou' => __( 'O encontro não foi guardado. Tente outra vez.', 'jelly-area-reservada' ),
];

$mostrar_aviso = function () use ( $aviso, $erro, $avisos, $erros ) {
	if ( isset( $avisos[ $aviso ] ) ) {
		printf( '<div class="jar-aviso jar-aviso--sucesso" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><p>%s</p></div>', esc_html( $avisos[ $aviso ] ) );
	}
	if ( isset( $erros[ $erro ] ) ) {
		printf( '<div class="jar-aviso jar-aviso--suspenso" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><p>%s</p></div>', esc_html( $erros[ $erro ] ) );
	}
};

// A capa: a imagem do encontro, ou a miniatura do primeiro vídeo; sem as duas, o degradé com o ícone.
$capa = function ( $e, $grande = false ) {
	$classe = 'jar-encontro-capa' . ( $grande ? ' jar-encontro-capa--grande' : '' );

	if ( $e['capa'] ) {
		printf( '<span class="%1$s"><img src="%2$s" alt="" loading="lazy"></span>', esc_attr( $classe ), esc_url( $e['capa'] ) );
		return;
	}

	printf( '<span class="%s is-vazia" aria-hidden="true"><i class="fa-solid fa-circle-play"></i></span>', esc_attr( $classe ) );
};

/*
 * Os dados do encontro, iguais no novo e na edição. A imagem escolhe-se na
 * biblioteca do WordPress (assets/js/admin.js, data-jar-imagem).
 */
$campos_dados = function ( $e ) {
	?>
	<div class="jar-form">
		<label class="jar-campo jar-campo--largo">
			<span><?php esc_html_e( 'Título', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></span>
			<input type="text" name="titulo" value="<?php echo esc_attr( $e['titulo'] ?? '' ); ?>" required>
		</label>

		<label class="jar-campo">
			<span><?php esc_html_e( 'Data do encontro', 'jelly-area-reservada' ); ?> <i aria-hidden="true">*</i></span>
			<input type="date" name="data" value="<?php echo esc_attr( $e['data'] ?? '' ); ?>" required>
			<small class="jar-campo__ajuda"><?php esc_html_e( 'O dia em que decorreu. Os encontros aparecem do mais recente para o mais antigo.', 'jelly-area-reservada' ); ?></small>
		</label>

		<div class="jar-campo">
			<span><?php esc_html_e( 'Imagem', 'jelly-area-reservada' ); ?></span>
			<div class="jar-imagem" data-jar-imagem data-inicial="<?php echo (int) ( $e['imagem_id'] ?? 0 ); ?>" data-inicial-url="<?php echo esc_url( $e['imagem'] ?? '' ); ?>">
				<input type="hidden" name="imagem_id" value="<?php echo (int) ( $e['imagem_id'] ?? 0 ); ?>" data-jar-imagem-id>
				<span class="jar-imagem__previa" data-jar-imagem-previa>
					<?php if ( ! empty( $e['imagem'] ) ) : ?>
						<img src="<?php echo esc_url( $e['imagem'] ); ?>" alt="">
					<?php endif; ?>
				</span>
				<span class="jar-imagem__botoes">
					<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-imagem-escolher data-titulo="<?php esc_attr_e( 'Imagem do encontro', 'jelly-area-reservada' ); ?>" data-botao="<?php esc_attr_e( 'Usar esta imagem', 'jelly-area-reservada' ); ?>"><i class="fa-regular fa-image" aria-hidden="true"></i> <?php esc_html_e( 'Escolher imagem', 'jelly-area-reservada' ); ?></button>
					<button type="button" class="jar-btn jar-btn--pequeno jar-btn--discreto" data-jar-imagem-tirar<?php echo empty( $e['imagem'] ) ? ' hidden' : ''; ?>><?php esc_html_e( 'Tirar', 'jelly-area-reservada' ); ?></button>
				</span>
			</div>
			<small class="jar-campo__ajuda"><?php esc_html_e( 'Opcional. Sem imagem, a capa é a do primeiro vídeo.', 'jelly-area-reservada' ); ?></small>
		</div>

		<label class="jar-campo jar-campo--largo">
			<span><?php esc_html_e( 'Resumo', 'jelly-area-reservada' ); ?></span>
			<textarea name="resumo" rows="2" placeholder="<?php esc_attr_e( 'Uma ou duas frases, para a lista dos encontros', 'jelly-area-reservada' ); ?>"><?php echo esc_textarea( $e['resumo'] ?? '' ); ?></textarea>
		</label>

		<label class="jar-campo jar-campo--largo">
			<span><?php esc_html_e( 'Texto', 'jelly-area-reservada' ); ?></span>
			<textarea name="texto" rows="8" placeholder="<?php esc_attr_e( 'O que se falou, quem participou…', 'jelly-area-reservada' ); ?>"><?php echo esc_textarea( $e['texto'] ?? '' ); ?></textarea>
			<small class="jar-campo__ajuda"><?php esc_html_e( 'Uma linha em branco começa um parágrafo novo.', 'jelly-area-reservada' ); ?></small>
		</label>
	</div>
	<?php
};

/*
 * Os vídeos: uma linha por vídeo, pela ordem em que se mostram, com as setas
 * para mudar a ordem e o X para tirar; "+ Vídeo" acrescenta uma linha do
 * modelo (assets/js/admin.js, data-jar-videos). O link valida-se já no
 * browser (pattern) e outra vez no servidor (jelly_ar_youtube_id()).
 */
$campos_videos = function ( $videos ) {
	$linha = function ( $v ) {
		?>
		<li class="jar-video" data-jar-video>
			<span class="jar-video__mini" data-jar-video-mini>
				<?php if ( $v ) : ?>
					<img src="<?php echo esc_url( $v['miniatura'] ); ?>" alt="">
				<?php else : ?>
					<i class="fa-brands fa-youtube" aria-hidden="true"></i>
				<?php endif; ?>
			</span>
			<input type="hidden" name="videos[id][]" value="<?php echo (int) ( $v['id'] ?? 0 ); ?>">
			<label class="jar-campo">
				<span><?php esc_html_e( 'Link do YouTube', 'jelly-area-reservada' ); ?></span>
				<input type="text" name="videos[url][]" value="<?php echo esc_attr( $v['url'] ?? '' ); ?>" placeholder="https://youtu.be/…" pattern=".*(youtu\.be/|youtube(-nocookie)?\.com/).*|[A-Za-z0-9_\-]{11}" title="<?php esc_attr_e( 'O link de um vídeo do YouTube', 'jelly-area-reservada' ); ?>" data-jar-video-url>
			</label>
			<label class="jar-campo">
				<span><?php esc_html_e( 'Título', 'jelly-area-reservada' ); ?></span>
				<input type="text" name="videos[titulo][]" value="<?php echo esc_attr( $v['titulo'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Vazio: o título do vídeo no YouTube', 'jelly-area-reservada' ); ?>">
			</label>
			<span class="jar-video__acoes">
				<button type="button" class="jar-acao" data-jar-video-subir aria-label="<?php esc_attr_e( 'Subir', 'jelly-area-reservada' ); ?>" title="<?php esc_attr_e( 'Subir', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
				<button type="button" class="jar-acao" data-jar-video-descer aria-label="<?php esc_attr_e( 'Descer', 'jelly-area-reservada' ); ?>" title="<?php esc_attr_e( 'Descer', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
				<button type="button" class="jar-acao" data-jar-video-tirar aria-label="<?php esc_attr_e( 'Tirar este vídeo', 'jelly-area-reservada' ); ?>" title="<?php esc_attr_e( 'Tirar este vídeo', 'jelly-area-reservada' ); ?>"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
			</span>
		</li>
		<?php
	};
	?>
	<div class="jar-videos" data-jar-videos>
		<ol class="jar-videos__lista" data-jar-videos-lista>
			<?php
			foreach ( $videos ? $videos : [ null ] as $v ) {
				$linha( $v );
			}
			?>
		</ol>
		<template data-jar-video-modelo><?php $linha( null ); ?></template>
		<button type="button" class="jar-btn jar-btn--pequeno jar-btn--discreto jar-btn--criar" data-jar-video-mais><i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Vídeo', 'jelly-area-reservada' ); ?></button>
		<small class="jar-campo__ajuda"><?php esc_html_e( 'No YouTube, os vídeos devem estar como "Não listado": não aparecem em pesquisas, e só quem tem o link os vê.', 'jelly-area-reservada' ); ?></small>
	</div>
	<?php
};

/* ---------------------------------------------------------------- Novo */

if ( $novo ) :
	?>
	<div class="jar-cabeca">
		<div>
			<a class="jar-voltar" href="<?php echo esc_url( jelly_ar_admin_url( 'encontros' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Encontros', 'jelly-area-reservada' ); ?></a>
			<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Novo encontro', 'jelly-area-reservada' ); ?></h1>
			<p class="jar-cabeca__intro"><?php esc_html_e( 'Só os associados o veem, na Área Reservada.', 'jelly-area-reservada' ); ?></p>
		</div>
	</div>

	<?php $mostrar_aviso(); ?>

	<form class="jar-cartao jar-cartao--estreito" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="jelly_ar_encontro_guardar">
		<input type="hidden" name="encontro" value="0">
		<?php wp_nonce_field( 'jelly_ar_encontro_guardar_0' ); ?>

		<?php $campos_dados( [] ); ?>

		<fieldset class="jar-opcoes">
			<legend><?php esc_html_e( 'Vídeos', 'jelly-area-reservada' ); ?></legend>
			<?php $campos_videos( [] ); ?>
		</fieldset>

		<fieldset class="jar-opcoes">
			<legend><?php esc_html_e( 'Publicação', 'jelly-area-reservada' ); ?></legend>
			<label class="jar-caixa"><input type="radio" name="estado" value="publicado" checked> <span><?php esc_html_e( 'Publicar já', 'jelly-area-reservada' ); ?></span></label>
			<label class="jar-caixa"><input type="radio" name="estado" value="rascunho"> <span><?php esc_html_e( 'Guardar como rascunho', 'jelly-area-reservada' ); ?></span></label>
		</fieldset>

		<footer class="jar-cartao__pe">
			<a class="jar-btn jar-btn--contorno" href="<?php echo esc_url( jelly_ar_admin_url( 'encontros' ) ); ?>"><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></a>
			<button type="submit" class="jar-btn"><?php esc_html_e( 'Criar encontro', 'jelly-area-reservada' ); ?></button>
		</footer>
	</form>
	<?php
	return;
endif;

/* ---------------------------------------------------------------- Encontro */

if ( $encontro ) :
	$publicado = 'publicado' === $encontro['estado'];
	$estado    = function ( $acao ) use ( $encontro ) {
		?>
		<form id="jar-encontro-<?php echo esc_attr( $acao ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
			<input type="hidden" name="action" value="jelly_ar_encontro_estado">
			<input type="hidden" name="encontro" value="<?php echo (int) $encontro['id']; ?>">
			<input type="hidden" name="estado" value="<?php echo esc_attr( $acao ); ?>">
			<?php wp_nonce_field( 'jelly_ar_encontro_estado_' . $encontro['id'] ); ?>
		</form>
		<?php
	};
	// O formulário de cada cartão: os dados ou os vídeos, para jelly_ar_encontro_guardar() (inc/encontros.php).
	$abrir_form = function ( $parte ) use ( $encontro ) {
		?>
		<form data-jar-edicao data-jar-gravar hidden method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="jelly_ar_encontro_guardar">
			<input type="hidden" name="encontro" value="<?php echo (int) $encontro['id']; ?>">
			<input type="hidden" name="parte" value="<?php echo esc_attr( $parte ); ?>">
			<?php wp_nonce_field( 'jelly_ar_encontro_guardar_' . $encontro['id'] ); ?>
		<?php
	};
	$fechar_form = function () {
		?>
			<footer class="jar-cartao__pe">
				<button type="button" class="jar-btn jar-btn--contorno" data-jar-cancelar><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
				<button type="submit" class="jar-btn"><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></button>
			</footer>
		</form>
		<?php
	};
	$n_videos = count( $encontro['videos'] );
	?>
	<div class="jar-cabeca">
		<div>
			<a class="jar-voltar" href="<?php echo esc_url( jelly_ar_admin_url( 'encontros' ) ); ?>"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> <?php esc_html_e( 'Encontros', 'jelly-area-reservada' ); ?></a>
			<h1 class="jar-cabeca__titulo"><?php echo esc_html( $encontro['titulo'] ); ?> <span data-jar-estado-perfil><?php jelly_ar_estado( $encontro['estado'] ); ?></span></h1>
			<?php /* translators: %d: número de vídeos */ ?>
			<p class="jar-cabeca__intro"><?php echo esc_html( $encontro['data_texto'] . ' · ' . sprintf( _n( '%d vídeo', '%d vídeos', $n_videos, 'jelly-area-reservada' ), $n_videos ) ); ?></p>
		</div>
		<div class="jar-cabeca__acoes">
			<button
				type="button"
				class="jar-btn jar-btn--discreto"
				data-jar-confirmar
				data-jar-form="jar-encontro-lixo"
				data-titulo="<?php esc_attr_e( 'Enviar este encontro para o lixo?', 'jelly-area-reservada' ); ?>"
				data-texto="<?php esc_attr_e( 'Sai da Área Reservada. Fica guardado no lixo, com os vídeos, de onde ainda se pode recuperar.', 'jelly-area-reservada' ); ?>"
				data-sim="<?php esc_attr_e( 'Enviar para o lixo', 'jelly-area-reservada' ); ?>"
				data-resultado=""
			><i class="fa-regular fa-trash-can" aria-hidden="true"></i> <?php esc_html_e( 'Enviar para o lixo', 'jelly-area-reservada' ); ?></button>
			<?php $estado( 'lixo' ); ?>

			<?php if ( $publicado ) : ?>
				<button
					type="button"
					class="jar-btn jar-btn--contorno"
					data-jar-confirmar
					data-jar-form="jar-encontro-rascunho"
					data-titulo="<?php esc_attr_e( 'Passar a rascunho?', 'jelly-area-reservada' ); ?>"
					data-texto="<?php esc_attr_e( 'O encontro sai da Área Reservada até voltar a ser publicado.', 'jelly-area-reservada' ); ?>"
					data-sim="<?php esc_attr_e( 'Passar a rascunho', 'jelly-area-reservada' ); ?>"
					data-resultado=""
				><?php esc_html_e( 'Passar a rascunho', 'jelly-area-reservada' ); ?></button>
				<?php $estado( 'rascunho' ); ?>
			<?php else : ?>
				<button type="submit" form="jar-encontro-publicar" class="jar-btn"><?php esc_html_e( 'Publicar', 'jelly-area-reservada' ); ?></button>
				<?php $estado( 'publicar' ); ?>
			<?php endif; ?>
		</div>
	</div>

	<?php $mostrar_aviso(); ?>

	<?php if ( ! $publicado ) : ?>
		<div class="jar-aviso jar-aviso--pendente">
			<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>
			<p><?php esc_html_e( 'Rascunho: não aparece na Área Reservada.', 'jelly-area-reservada' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="jar-grelha jar-grelha--1-2">
		<section class="jar-cartao jar-perfil jar-encontro-cartao">
			<?php $capa( $encontro, true ); ?>
			<h2><?php echo esc_html( $encontro['titulo'] ); ?></h2>
			<p><?php echo esc_html( $encontro['data_texto'] ); ?></p>

			<dl class="jar-dados">
				<dt><?php esc_html_e( 'Vídeos', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo (int) $n_videos; ?></dd>
				<dt><?php esc_html_e( 'Publicado em', 'jelly-area-reservada' ); ?></dt>
				<dd><?php echo esc_html( $encontro['publicado_em'] ? jelly_ar_data( 'j M Y', strtotime( get_date_from_gmt( $encontro['publicado_em'] ) ) ) : '—' ); ?></dd>
			</dl>
		</section>

		<div class="jar-pilha">
			<?php // Um erro ao guardar volta com `editar=<parte>`: esse cartão abre já em edição. ?>
			<section class="jar-cartao" data-jar-editavel<?php echo 'dados' === $editar ? ' data-jar-abrir' : ''; ?>>
				<header class="jar-cartao__cabeca">
					<h2><?php esc_html_e( 'Dados do encontro', 'jelly-area-reservada' ); ?></h2>
					<button type="button" class="jar-btn jar-btn--pequeno jar-btn--contorno" data-jar-editar>
						<i class="fa-solid fa-pen" aria-hidden="true"></i> <?php esc_html_e( 'Editar', 'jelly-area-reservada' ); ?>
					</button>
				</header>

				<dl class="jar-leitura" data-jar-leitura>
					<div class="jar-campo--largo">
						<dt><?php esc_html_e( 'Título', 'jelly-area-reservada' ); ?></dt>
						<dd><?php echo esc_html( $encontro['titulo'] ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Data', 'jelly-area-reservada' ); ?></dt>
						<dd><?php echo esc_html( $encontro['data_texto'] ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Imagem', 'jelly-area-reservada' ); ?></dt>
						<dd><?php echo esc_html( $encontro['imagem'] ? __( 'Escolhida', 'jelly-area-reservada' ) : __( 'A do primeiro vídeo', 'jelly-area-reservada' ) ); ?></dd>
					</div>
					<div class="jar-campo--largo">
						<dt><?php esc_html_e( 'Resumo', 'jelly-area-reservada' ); ?></dt>
						<dd><?php echo esc_html( $encontro['resumo'] ? $encontro['resumo'] : '—' ); ?></dd>
					</div>
					<div class="jar-campo--largo">
						<dt><?php esc_html_e( 'Texto', 'jelly-area-reservada' ); ?></dt>
						<dd class="jar-texto"><?php echo $encontro['texto'] ? wp_kses_post( wpautop( $encontro['texto'] ) ) : '—'; ?></dd>
					</div>
				</dl>

				<?php $abrir_form( 'dados' ); ?>
					<?php $campos_dados( $encontro ); ?>
				<?php $fechar_form(); ?>
			</section>

			<section class="jar-cartao" data-jar-editavel<?php echo 'videos' === $editar ? ' data-jar-abrir' : ''; ?>>
				<header class="jar-cartao__cabeca jar-cartao__cabeca--acao">
					<div>
						<h2><?php esc_html_e( 'Vídeos', 'jelly-area-reservada' ); ?></h2>
						<span class="jar-cartao__meta"><?php esc_html_e( 'Pela ordem em que os associados os veem', 'jelly-area-reservada' ); ?></span>
					</div>
					<?php // Cheio (cor-de-rosa) enquanto o encontro não tem vídeos, para chamar a atenção; em contorno depois. ?>
					<button type="button" class="jar-btn jar-btn--pequeno<?php echo $n_videos ? ' jar-btn--contorno' : ''; ?>" data-jar-editar>
						<i class="fa-solid fa-pen" aria-hidden="true"></i> <?php echo esc_html( $n_videos ? __( 'Editar vídeos', 'jelly-area-reservada' ) : __( 'Juntar vídeos', 'jelly-area-reservada' ) ); ?>
					</button>
				</header>

				<div data-jar-leitura>
					<?php if ( ! $n_videos ) : ?>
						<p class="jar-vazio"><?php esc_html_e( 'Este encontro ainda não tem vídeos.', 'jelly-area-reservada' ); ?></p>
					<?php else : ?>
						<ol class="jar-videos-leitura">
							<?php foreach ( $encontro['videos'] as $v ) : ?>
								<li>
									<img src="<?php echo esc_url( $v['miniatura'] ); ?>" alt="" loading="lazy">
									<span>
										<strong><?php echo esc_html( $v['titulo'] ? $v['titulo'] : __( 'Sem título', 'jelly-area-reservada' ) ); ?></strong>
										<a class="jar-link" href="<?php echo esc_url( $v['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Ver no YouTube', 'jelly-area-reservada' ); ?> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
									</span>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</div>

				<?php $abrir_form( 'videos' ); ?>
					<?php $campos_videos( $encontro['videos'] ); ?>
				<?php $fechar_form(); ?>
			</section>
		</div>
	</div>
	<?php
	return;
endif;

/* ---------------------------------------------------------------- Lista */

$encontros = jelly_ar_encontros();
$tabela    = jelly_ar_encontros_lista();
$resultado = jelly_ar_encontros_filtrar( $encontros, $tabela->pedido() );
$lista     = $tabela->paginar( $resultado['lista'] );
$contagem  = $resultado['contagem'];
$separador = $tabela->get( 'estado' );
$pesquisa  = $tabela->get( 'q' );
$publicos  = array_filter( $encontros, function ( $e ) {
	return 'publicado' === $e['estado'];
} );

$separadores = [
	'todos'     => __( 'Todos', 'jelly-area-reservada' ),
	'publicado' => __( 'Publicados', 'jelly-area-reservada' ),
	'rascunho'  => __( 'Rascunhos', 'jelly-area-reservada' ),
];
$resumo = [
	[ __( 'Encontros publicados', 'jelly-area-reservada' ), count( $publicos ), 'fa-people-group', 'azul' ],
	[ __( 'Rascunhos', 'jelly-area-reservada' ), count( $encontros ) - count( $publicos ), 'fa-pen-to-square', 'roxo' ],
	[ __( 'Vídeos publicados', 'jelly-area-reservada' ), array_sum( array_map( function ( $e ) {
		return count( $e['videos'] );
	}, $publicos ) ), 'fa-circle-play', 'magenta' ],
];
?>
<div class="jar-cabeca">
	<div>
		<h1 class="jar-cabeca__titulo"><?php esc_html_e( 'Encontros', 'jelly-area-reservada' ); ?></h1>
		<p class="jar-cabeca__intro"><?php esc_html_e( 'Os encontros publicados aparecem na Área Reservada, com os vídeos. Não aparecem no site.', 'jelly-area-reservada' ); ?></p>
	</div>
	<div class="jar-cabeca__acoes">
		<a class="jar-btn" href="<?php echo esc_url( jelly_ar_admin_url( 'encontros', [ 'novo' => 1 ] ) ); ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> <?php esc_html_e( 'Novo encontro', 'jelly-area-reservada' ); ?></a>
	</div>
</div>

<?php $mostrar_aviso(); ?>

<div class="jar-numeros jar-numeros--tres">
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
		<nav class="jar-separadores" aria-label="<?php esc_attr_e( 'Filtrar por estado', 'jelly-area-reservada' ); ?>">
			<?php foreach ( $separadores as $s => $rotulo ) : ?>
				<?php $n = 'todos' === $s ? count( $resultado['encontrados'] ) : ( $contagem[ $s ] ?? 0 ); ?>
				<a class="jar-separador<?php echo $s === $separador ? ' is-atual' : ''; ?>" href="<?php echo esc_url( $tabela->url( [ 'estado' => $s ] ) ); ?>"<?php echo $s === $separador ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $rotulo ); ?> <b><?php echo (int) $n; ?></b>
				</a>
			<?php endforeach; ?>
		</nav>

		<div class="jar-barra__filtros">
			<form class="jar-filtro" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search" data-jar-pesquisa>
				<?php $tabela->campos_escondidos( [ 'q' ] ); ?>
				<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
				<label class="screen-reader-text" for="jar-pesquisa"><?php esc_html_e( 'Pesquisar encontros', 'jelly-area-reservada' ); ?></label>
				<input type="search" id="jar-pesquisa" name="q" value="<?php echo esc_attr( $pesquisa ); ?>" placeholder="<?php esc_attr_e( 'Título, resumo ou vídeo', 'jelly-area-reservada' ); ?>">
			</form>
		</div>
	</div>

	<table class="jar-tabela">
		<thead>
			<tr>
				<?php
				$tabela->coluna( 'titulo', __( 'Encontro', 'jelly-area-reservada' ) );
				$tabela->coluna( 'data', __( 'Data', 'jelly-area-reservada' ), 'jar-col--data' );
				?>
				<th class="jar-col--videos jar-tabela__centro"><?php esc_html_e( 'Vídeos', 'jelly-area-reservada' ); ?></th>
				<th class="jar-col--estado"><?php esc_html_e( 'Estado', 'jelly-area-reservada' ); ?></th>
				<th class="jar-tabela__fim"><span class="screen-reader-text"><?php esc_html_e( 'Ações', 'jelly-area-reservada' ); ?></span></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! $lista ) : ?>
				<tr>
					<td colspan="5" class="jar-vazio">
						<?php
						if ( '' !== $pesquisa ) {
							/* translators: %s: o que se pesquisou */
							printf( esc_html__( 'Nenhum encontro corresponde a "%s".', 'jelly-area-reservada' ), esc_html( $pesquisa ) );
						} elseif ( ! $encontros ) {
							esc_html_e( 'Ainda não há encontros. O primeiro cria-se em "Novo encontro".', 'jelly-area-reservada' );
						} else {
							esc_html_e( 'Nenhum encontro nesta lista.', 'jelly-area-reservada' );
						}
						?>
					</td>
				</tr>
			<?php endif; ?>
			<?php foreach ( $lista as $e ) : ?>
				<?php $abrir = jelly_ar_admin_url( 'encontros', [ 'encontro' => $e['id'] ] ); ?>
				<tr data-jar-linha>
					<td>
						<a class="jar-ficheiro" href="<?php echo esc_url( $abrir ); ?>">
							<?php $capa( $e ); ?>
							<span>
								<strong><?php echo esc_html( $e['titulo'] ); ?></strong>
								<?php if ( $e['resumo'] ) : ?>
									<small class="jar-encontro-resumo"><?php echo esc_html( $e['resumo'] ); ?></small>
								<?php endif; ?>
							</span>
						</a>
					</td>
					<td class="jar-tabela__num jar-col--data"><?php echo esc_html( $e['data_texto'] ); ?></td>
					<td class="jar-col--videos jar-tabela__centro jar-tabela__num"><?php echo $e['videos'] ? (int) count( $e['videos'] ) : '—'; ?></td>
					<td class="jar-col--estado"><?php jelly_ar_estado( $e['estado'] ); ?></td>
					<td class="jar-tabela__fim">
						<?php /* translators: %s: título do encontro */ ?>
						<a class="jar-acao" href="<?php echo esc_url( $abrir ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Abrir %s', 'jelly-area-reservada' ), $e['titulo'] ) ); ?>"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php $tabela->paginacao(); ?>
</section>
