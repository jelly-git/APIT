<?php
/**
 * Moldura do back-office: navegação à esquerda, barra de topo e a área em que
 * se está. Cada área é um template em templates/admin/<chave>.php.
 */

defined( 'ABSPATH' ) || exit;

$chave   = $args['chave'];
$paginas = jelly_ar_admin_paginas();
$eu      = wp_get_current_user();
$logo    = jelly_ar_logo_url();
$nome    = $eu->first_name ? trim( $eu->first_name . ' ' . $eu->last_name ) : $eu->display_name;

/*
 * O que está à espera de decisão, para o contador ao lado de cada área: os
 * registos por aprovar nos Utilizadores, e os pedidos de marcação por aprovar
 * nas Aprovações.
 */
global $wpdb;
$contadores = [
	'utilizadores' => [
		count( array_filter( jelly_ar_utilizadores_todos(), function ( $u ) {
			return 'pendente' === $u['estado'];
		} ) ),
		__( 'Registos por aprovar', 'jelly-area-reservada' ),
	],
	'marcacoes'    => [
		(int) $wpdb->get_var( "SELECT COUNT(*) FROM " . jelly_ar_tabela( 'marcacoes' ) . " WHERE estado = 'pendente' AND ocupa = 1" ), // phpcs:ignore WordPress.DB
		__( 'Pedidos de marcação por aprovar', 'jelly-area-reservada' ),
	],
];
?>
<div class="jar" data-jar>
	<aside class="jar__lado">
		<a class="jar__marca" href="<?php echo esc_url( admin_url( 'admin.php?page=jelly-ar' ) ); ?>">
			<?php if ( $logo ) : ?>
				<img src="<?php echo esc_url( $logo ); ?>" alt="<?php bloginfo( 'name' ); ?>">
			<?php endif; ?>
			<span><?php esc_html_e( 'Área Reservada', 'jelly-area-reservada' ); ?></span>
		</a>

		<nav class="jar__nav" aria-label="<?php esc_attr_e( 'Back-office da Área Reservada', 'jelly-area-reservada' ); ?>">
			<?php
			// O título de cada grupo (jelly_ar_admin_grupos()) vai por cima da primeira área dele.
			$grupos = jelly_ar_admin_grupos();
			$grupo  = null;
			?>
			<?php foreach ( $paginas as $c => $p ) : ?>
				<?php if ( ! empty( $p['grupo'] ) && $p['grupo'] !== $grupo ) : ?>
					<span class="jar__nav-grupo"><?php echo esc_html( $grupos[ $p['grupo'] ] ); ?></span>
				<?php endif; ?>
				<?php $grupo = $p['grupo'] ?? ''; ?>
				<?php if ( $p['pronta'] ) : ?>
					<a
						class="jar__nav-item<?php echo $c === $chave ? ' is-atual' : ''; ?>"
						href="<?php echo esc_url( jelly_ar_admin_url( $c ) ); ?>"
						<?php echo $c === $chave ? 'aria-current="page"' : ''; ?>
					>
						<i class="fa-solid <?php echo esc_attr( $p['icone'] ); ?>" aria-hidden="true"></i>
						<span><?php echo esc_html( $p['titulo'] ); ?></span>
						<?php if ( ! empty( $contadores[ $c ][0] ) ) : ?>
							<b class="jar__contador" title="<?php echo esc_attr( $contadores[ $c ][1] ); ?>"><?php echo (int) $contadores[ $c ][0]; ?></b>
						<?php endif; ?>
					</a>
				<?php else : ?>
					<span class="jar__nav-item is-em-breve" aria-disabled="true">
						<i class="fa-solid <?php echo esc_attr( $p['icone'] ); ?>" aria-hidden="true"></i>
						<span><?php echo esc_html( $p['titulo'] ); ?></span>
						<em><?php esc_html_e( 'Em breve', 'jelly-area-reservada' ); ?></em>
					</span>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>

		<div class="jar__lado-fim">
			<a class="jar__nav-item" href="<?php echo esc_url( home_url( '/#area-reservada' ) ); ?>" target="_blank" rel="noopener">
				<i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
				<span><?php esc_html_e( 'Ver o site', 'jelly-area-reservada' ); ?></span>
			</a>
			<a class="jar__nav-item" href="<?php echo esc_url( admin_url() ); ?>">
				<i class="fa-brands fa-wordpress" aria-hidden="true"></i>
				<span><?php esc_html_e( 'Voltar ao WordPress', 'jelly-area-reservada' ); ?></span>
			</a>
		</div>
	</aside>

	<div class="jar__principal">
		<header class="jar__topo">
			<button type="button" class="jar__menu" data-jar-menu aria-label="<?php esc_attr_e( 'Abrir menu', 'jelly-area-reservada' ); ?>">
				<i class="fa-solid fa-bars" aria-hidden="true"></i>
			</button>

			<?php
			/*
			 * O aviso dos dados de exemplo só onde os há, e a dizer o que lá é
			 * verdade: os Utilizadores são todos de exemplo; nos Documentos,
			 * os de exemplo convivem com os carregados a sério; os Eventos são
			 * os do site e não levam aviso.
			 */
			$exemplo = [
				'utilizadores' => __( 'Pré-visualização — utilizadores de exemplo, nada é gravado.', 'jelly-area-reservada' ),
				'documentos'   => __( 'Há documentos de exemplo, sem ficheiro. Os que carregar são guardados a sério.', 'jelly-area-reservada' ),
			];
			?>
			<?php if ( JELLY_AR_EXEMPLO && isset( $exemplo[ $chave ] ) ) : ?>
				<p class="jar__exemplo">
					<i class="fa-solid fa-flask" aria-hidden="true"></i>
					<?php echo esc_html( $exemplo[ $chave ] ); ?>
				</p>
			<?php endif; ?>

			<div class="jar__eu">
				<?php echo get_avatar( $eu->ID, 40, '', '', [ 'class' => 'jar__avatar' ] ); ?>
				<div>
					<strong><?php echo esc_html( $nome ); ?></strong>
					<span><?php esc_html_e( 'Equipa APIT', 'jelly-area-reservada' ); ?></span>
				</div>
			</div>
		</header>

		<main class="jar__conteudo">
			<?php
			/*
			 * O envio dos e-mails da AR (pedidos de registo, aprovações,
			 * marcações), em todas as áreas, enquanto houver alguma coisa a
			 * dizer: o envio não é autenticado (o PHP), o último e-mail da AR
			 * falhou (jelly_ar_email_ultimo_erro()), ou acabou de se fazer um
			 * teste (jelly_ar_email_teste()). Com o botão para enviar um e-mail da
			 * AR de teste, para se ver o caminho que os associados recebem.
			 */
			$envio_ok  = jelly_ar_envio_autenticado();
			$envio_err = jelly_ar_email_ultimo_erro();
			$teste     = isset( $_GET['email_teste'] ) ? jelly_ar_email_teste_resultado() : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$smtp_url  = admin_url( 'admin.php?page=wp-mail-smtp' );
			?>
			<?php if ( false === $envio_ok || $envio_err || $teste ) : ?>
				<div class="jar-aviso jar-aviso--pendente jar-envio" role="status">
					<i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i>
					<div>
						<?php if ( $teste ) : ?>
							<p><strong>
								<?php
								if ( 'ok' === $teste['resultado'] ) {
									/* translators: %s: endereço de e-mail */
									echo esc_html( sprintf( __( 'O e-mail de teste foi entregue ao servidor de correio para %s. Se não chegar em alguns minutos (veja também o spam), o problema está na entrega: o remetente e o domínio.', 'jelly-area-reservada' ), $teste['para'] ) );
								} elseif ( 'email' === $teste['resultado'] ) {
									esc_html_e( 'Escreva um endereço de e-mail válido para o teste.', 'jelly-area-reservada' );
								} else {
									/* translators: %s: endereço de e-mail */
									echo esc_html( sprintf( __( 'O e-mail de teste para %s não saiu. O erro está abaixo.', 'jelly-area-reservada' ), $teste['para'] ) );
								}
								?>
							</strong></p>
						<?php endif; ?>

						<?php if ( $envio_err ) : ?>
							<p>
								<?php
								/* translators: 1: data e hora, 2: destinatário, 3: erro */
								echo esc_html( sprintf( __( 'O último e-mail da área reservada que falhou: %1$s, para %2$s — %3$s', 'jelly-area-reservada' ), $envio_err['quando'], $envio_err['para'], $envio_err['erro'] ? $envio_err['erro'] : __( 'sem mensagem de erro', 'jelly-area-reservada' ) ) );
								?>
							</p>
						<?php endif; ?>

						<?php if ( false === $envio_ok ) : ?>
							<p>
								<?php
								if ( 'mail' === jelly_ar_email_mailer() ) {
									printf(
										/* translators: %s: ligação às definições do WP Mail SMTP */
										esc_html__( 'Os e-mails saem pelo PHP (o envio "Default (none)" do WP Mail SMTP), sem SMTP autenticado. Saem, mas podem ser recusados ou ir para o spam se o domínio do remetente (From Email) não autorizar este servidor. Remetente e envio em %s.', 'jelly-area-reservada' ),
										'<a class="jar-link" href="' . esc_url( $smtp_url ) . '">' . esc_html__( 'WP Mail SMTP → Definições', 'jelly-area-reservada' ) . '</a>'
									);
								} elseif ( function_exists( 'wp_mail_smtp' ) ) {
									printf(
										/* translators: %s: ligação às definições do WP Mail SMTP */
										esc_html__( 'O envio por SMTP está sem autenticação. Credenciais em %s.', 'jelly-area-reservada' ),
										'<a class="jar-link" href="' . esc_url( $smtp_url ) . '">' . esc_html__( 'WP Mail SMTP → Definições', 'jelly-area-reservada' ) . '</a>'
									);
								} else {
									esc_html_e( 'Os e-mails saem pela função mail() do PHP: não há nenhum plugin de SMTP ativo.', 'jelly-area-reservada' );
								}
								?>
							</p>
						<?php endif; ?>

						<form class="jar-envio__teste" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="jelly_ar_email_teste">
							<input type="hidden" name="voltar" value="<?php echo esc_url( remove_query_arg( 'email_teste' ) ); ?>">
							<?php wp_nonce_field( 'jelly_ar_email_teste' ); ?>
							<label class="screen-reader-text" for="jar-envio-para"><?php esc_html_e( 'Endereço para o teste', 'jelly-area-reservada' ); ?></label>
							<input type="email" id="jar-envio-para" name="para" value="<?php echo esc_attr( $eu->user_email ); ?>" required>
							<button type="submit" class="jar-btn jar-btn--pequeno jar-btn--contorno"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> <?php esc_html_e( 'Enviar e-mail de teste da AR', 'jelly-area-reservada' ); ?></button>
						</form>
					</div>
				</div>
			<?php endif; ?>

			<?php
			/*
			 * Os e-mails das marcações saem depois de a página responder
			 * (jelly_ar_email_depois()); os que falharam avisam-se aqui, na
			 * página seguinte, uma vez.
			 */
			$falhados = jelly_ar_emails_falhados();
			?>
			<?php if ( $falhados ) : ?>
				<div class="jar-aviso jar-aviso--pendente" role="alert">
					<i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i>
					<?php /* translators: %d: número de e-mails */ ?>
					<p><?php echo esc_html( sprintf( _n( '%d e-mail de uma marcação não foi enviado: a alteração está gravada, mas o associado não foi avisado. Convém confirmar a configuração do SMTP.', '%d e-mails de marcações não foram enviados: as alterações estão gravadas, mas esses associados não foram avisados. Convém confirmar a configuração do SMTP.', $falhados, 'jelly-area-reservada' ), $falhados ) ); ?></p>
				</div>
			<?php endif; ?>

			<?php jelly_ar_template( 'admin/' . $chave ); ?>
		</main>
	</div>

	<?php
	/*
	 * A confirmação das ações que não se desfazem com um clique — suspender,
	 * rejeitar, apagar. Uma só para a página: o botão que a abre traz o título,
	 * o texto e o rótulo do botão de confirmar em data-*.
	 */
	?>
	<div class="jar-confirmar" data-jar-confirmacao hidden>
		<div class="jar-confirmar__fundo" data-jar-confirmar-nao></div>
		<div class="jar-confirmar__caixa" role="alertdialog" aria-modal="true" aria-labelledby="jar-confirmar-titulo" aria-describedby="jar-confirmar-texto">
			<span class="jar-confirmar__icone"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></span>
			<h2 id="jar-confirmar-titulo"></h2>
			<p id="jar-confirmar-texto"></p>
			<div class="jar-confirmar__acoes">
				<button type="button" class="jar-btn jar-btn--contorno" data-jar-confirmar-nao><?php esc_html_e( 'Cancelar', 'jelly-area-reservada' ); ?></button>
				<button type="button" class="jar-btn jar-btn--perigo" data-jar-confirmar-sim></button>
			</div>
		</div>
	</div>
</div>
