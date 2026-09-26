<?php
/**
 * Definir a palavra-passe: é aqui que chega a ligação dos e-mails de
 * aprovação e de recuperação (/?ar-chave=…&ar-conta=…#area-reservada-senha).
 *
 * A chave verifica-se já ao desenhar, para quem chega com uma ligação expirada
 * ler isso logo, e não depois de escrever a palavra-passe. O servidor verifica
 * outra vez ao gravar (jelly_ar_definir_senha(), inc/sessao.php).
 */

defined( 'ABSPATH' ) || exit;

$pedido = jelly_ar_senha_pedida();
?>
<h2 class="apit-ar__titulo" id="apit-ar-titulo-senha"><?php esc_html_e( 'Definir palavra-passe', 'jelly-area-reservada' ); ?></h2>

<?php if ( ! is_array( $pedido ) ) : ?>
	<?php // Sem ligação, ou com uma que já não serve. ?>
	<p class="apit-ar__intro">
		<?php
		echo esc_html(
			null === $pedido
				? __( 'A palavra-passe define-se a partir da ligação enviada por e-mail, depois de o pedido de acesso ser aprovado.', 'jelly-area-reservada' )
				: __( 'Esta ligação expirou ou já foi utilizada. Por motivos de segurança, cada ligação é válida durante 24 horas e só uma vez.', 'jelly-area-reservada' )
		);
		?>
	</p>
	<a class="btn btn--solid apit-ar__submeter" href="#area-reservada-recuperar">
		<?php esc_html_e( 'Pedir uma nova ligação', 'jelly-area-reservada' ); ?>
		<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
	</a>
	<div class="apit-ar__rodape">
		<p><a href="#area-reservada" class="apit-ar__link"><?php esc_html_e( 'Voltar ao login', 'jelly-area-reservada' ); ?></a></p>
	</div>
	<?php return; ?>
<?php endif; ?>

<p class="apit-ar__intro">
	<?php
	printf(
		/* translators: %s: e-mail */
		esc_html__( 'A nova palavra-passe da conta %s.', 'jelly-area-reservada' ),
		'<strong>' . esc_html( $pedido['user']->user_email ) . '</strong>'
	);
	?>
</p>

<form class="apit-ar__form" data-ar-form="senha" method="post" action="<?php echo esc_attr( wp_make_link_relative( admin_url( 'admin-ajax.php' ) ) ); ?>" novalidate>
	<input type="hidden" name="action" value="jelly_ar_senha">
	<input type="hidden" name="chave" value="<?php echo esc_attr( $pedido['chave'] ); ?>">
	<input type="hidden" name="conta" value="<?php echo esc_attr( $pedido['conta'] ); ?>">
	<?php wp_nonce_field( 'jelly_ar_senha', '_wpnonce', false ); ?>

	<?php // O e-mail, escondido, para o gestor de palavras-passe do browser a guardar na conta certa. ?>
	<input type="email" name="usuario" value="<?php echo esc_attr( $pedido['user']->user_email ); ?>" autocomplete="username" hidden>

	<div class="apit-ar__campo">
		<label for="apit-ar-senha-nova"><?php esc_html_e( 'Nova palavra-passe', 'jelly-area-reservada' ); ?></label>
		<div class="apit-ar__senha">
			<input type="password" id="apit-ar-senha-nova" name="senha" autocomplete="new-password" minlength="<?php echo (int) JELLY_AR_SENHA_MINIMO; ?>" required>
			<button type="button" class="apit-ar__ver-senha" data-ar-ver-senha aria-label="<?php esc_attr_e( 'Mostrar palavra-passe', 'jelly-area-reservada' ); ?>" aria-pressed="false">
				<i class="fa-regular fa-eye" aria-hidden="true"></i>
			</button>
		</div>
	</div>

	<div class="apit-ar__campo">
		<label for="apit-ar-senha-repetir"><?php esc_html_e( 'Repetir a palavra-passe', 'jelly-area-reservada' ); ?></label>
		<div class="apit-ar__senha">
			<input type="password" id="apit-ar-senha-repetir" name="senha2" autocomplete="new-password" required>
			<button type="button" class="apit-ar__ver-senha" data-ar-ver-senha aria-label="<?php esc_attr_e( 'Mostrar palavra-passe', 'jelly-area-reservada' ); ?>" aria-pressed="false">
				<i class="fa-regular fa-eye" aria-hidden="true"></i>
			</button>
		</div>
	</div>

	<p class="apit-ar__nota">
		<i class="fa-solid fa-circle-info" aria-hidden="true"></i>
		<?php
		/* translators: %d: número de caracteres */
		printf( esc_html__( 'Pelo menos %d caracteres. Uma frase curta, fácil de lembrar e difícil de adivinhar, é uma boa palavra-passe.', 'jelly-area-reservada' ), (int) JELLY_AR_SENHA_MINIMO );
		?>
	</p>

	<button type="submit" class="btn btn--solid apit-ar__submeter">
		<span data-ar-rotulo><?php esc_html_e( 'Guardar palavra-passe', 'jelly-area-reservada' ); ?></span>
		<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
	</button>

	<p class="apit-ar__aviso" data-ar-aviso role="alert" hidden></p>
</form>

<div class="apit-ar__sucesso" data-ar-sucesso role="status" hidden>
	<span class="apit-ar__sucesso-icone" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
	<h2 class="apit-ar__sucesso-titulo" tabindex="-1" data-ar-sucesso-foco><?php esc_html_e( 'Palavra-passe definida', 'jelly-area-reservada' ); ?></h2>
	<p class="apit-ar__sucesso-texto"><?php esc_html_e( 'A conta está pronta. O acesso à Área Reservada faz-se com o e-mail e a nova palavra-passe.', 'jelly-area-reservada' ); ?></p>
	<a class="btn btn--solid apit-ar__submeter" href="#area-reservada" data-ar-entrar-com="<?php echo esc_attr( $pedido['user']->user_email ); ?>">
		<?php esc_html_e( 'Entrar', 'jelly-area-reservada' ); ?>
		<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
	</a>
</div>
