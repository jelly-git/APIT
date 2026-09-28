<?php
/**
 * Os meus dados, na ARU: o nome e o e-mail (só para ler), o telefone e a
 * empresa (para mudar), e a palavra-passe. Os formulários vão a
 * inc/aru-perfil.php, e voltam aqui com ?aviso= ou ?erro=.
 *
 * @var array $aru    secao, user, pagina, evento.
 * @var array $pessoa jelly_ar_aru_pessoa().
 */

defined( 'ABSPATH' ) || exit;

$user   = $aru['user'];
$perfil = jelly_ar_associado( $user->ID );

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$aviso = isset( $_GET['aviso'] ) ? sanitize_key( wp_unslash( $_GET['aviso'] ) ) : '';
$erro  = isset( $_GET['erro'] ) ? sanitize_key( wp_unslash( $_GET['erro'] ) ) : '';
// phpcs:enable

$avisos = [
	'dados' => __( 'Os dados foram guardados.', 'jelly-area-reservada' ),
	'senha' => __( 'A palavra-passe foi alterada. Foi enviado um e-mail a confirmar a mudança.', 'jelly-area-reservada' ),
];
$erros  = [
	'telefone'        => __( 'O número de telefone não parece válido. Os dados não foram guardados.', 'jelly-area-reservada' ),
	'sem-perfil'      => __( 'Esta conta não tem dados de associado para alterar.', 'jelly-area-reservada' ),
	'senha-atual'     => __( 'A palavra-passe atual não está correta.', 'jelly-area-reservada' ),
	/* translators: %d: número de caracteres */
	'senha-curta'     => sprintf( __( 'A nova palavra-passe tem de ter pelo menos %d caracteres.', 'jelly-area-reservada' ), JELLY_AR_SENHA_MINIMO ),
	'senha-email'     => __( 'A palavra-passe não pode ser o próprio e-mail.', 'jelly-area-reservada' ),
	'senha-diferente' => __( 'As duas palavras-passe novas não são iguais.', 'jelly-area-reservada' ),
	'senha-falhou'    => __( 'Não foi possível mudar a palavra-passe. Tente de novo daqui a pouco.', 'jelly-area-reservada' ),
	'tentativas'      => __( 'Foram feitas demasiadas tentativas. É possível tentar de novo daqui a uma hora.', 'jelly-area-reservada' ),
];

// Cada aviso aparece no cartão a que diz respeito.
$e_da_senha = 0 === strpos( $erro, 'senha' ) || 'tentativas' === $erro || 'senha' === $aviso;

$mensagem = function ( $cartao ) use ( $aviso, $erro, $avisos, $erros, $e_da_senha ) {
	if ( ( 'senha' === $cartao ) !== $e_da_senha ) {
		return;
	}

	if ( isset( $avisos[ $aviso ] ) ) {
		printf( '<p class="aru-aviso aru-aviso--sucesso" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> %s</p>', esc_html( $avisos[ $aviso ] ) );
	} elseif ( isset( $erros[ $erro ] ) ) {
		printf( '<p class="aru-aviso aru-aviso--erro" role="alert"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> %s</p>', esc_html( $erros[ $erro ] ) );
	}
};
?>

<header class="aru-titulo">
	<h1><?php esc_html_e( 'Os meus dados', 'jelly-area-reservada' ); ?></h1>
	<p><?php esc_html_e( 'Os dados da sua conta na Área Reservada. Para mudar o nome ou o e-mail, contacte a APIT.', 'jelly-area-reservada' ); ?></p>
</header>

<div class="aru-perfil">
	<?php /* ---------- Os dados ---------- */ ?>
	<section class="aru-cartao" id="jar-dados">
		<header class="aru-cartao__cabeca">
			<h2><i class="fa-regular fa-id-card" aria-hidden="true"></i> <?php esc_html_e( 'Dados pessoais', 'jelly-area-reservada' ); ?></h2>
		</header>

		<?php $mensagem( 'dados' ); ?>

		<form class="aru-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="jelly_ar_perfil">
			<?php wp_nonce_field( 'jelly_ar_perfil' ); ?>

			<div class="aru-campo">
				<label for="aru-nome"><?php esc_html_e( 'Nome', 'jelly-area-reservada' ); ?></label>
				<input type="text" id="aru-nome" value="<?php echo esc_attr( $pessoa['nome'] ); ?>" readonly aria-describedby="aru-fixos">
			</div>

			<div class="aru-campo">
				<label for="aru-email"><?php esc_html_e( 'E-mail', 'jelly-area-reservada' ); ?></label>
				<input type="email" id="aru-email" value="<?php echo esc_attr( $user->user_email ); ?>" readonly aria-describedby="aru-fixos">
			</div>

			<p class="aru-nota aru-form__inteira" id="aru-fixos"><i class="fa-solid fa-lock" aria-hidden="true"></i> <?php esc_html_e( 'O nome e o e-mail identificam a conta e só a APIT os pode mudar.', 'jelly-area-reservada' ); ?></p>

			<?php if ( $perfil ) : ?>
				<div class="aru-campo">
					<label for="aru-telefone"><?php esc_html_e( 'Telefone', 'jelly-area-reservada' ); ?></label>
					<input type="tel" id="aru-telefone" name="telefone" value="<?php echo esc_attr( $perfil->telefone ); ?>" maxlength="30" pattern="\+?[\d\s().\-]{9,20}" autocomplete="tel" placeholder="+351 912 345 678">
				</div>

				<div class="aru-campo">
					<label for="aru-empresa"><?php esc_html_e( 'Empresa', 'jelly-area-reservada' ); ?></label>
					<input type="text" id="aru-empresa" name="empresa" value="<?php echo esc_attr( $perfil->empresa ); ?>" maxlength="150" autocomplete="organization">
				</div>

				<div class="aru-form__acoes">
					<button type="submit" class="aru-botao">
						<span><?php esc_html_e( 'Guardar dados', 'jelly-area-reservada' ); ?></span>
						<i class="fa-solid fa-check" aria-hidden="true"></i>
					</button>
				</div>
			<?php endif; ?>
		</form>
	</section>

	<?php /* ---------- A palavra-passe ---------- */ ?>
	<section class="aru-cartao" id="jar-senha">
		<header class="aru-cartao__cabeca">
			<h2><i class="fa-solid fa-key" aria-hidden="true"></i> <?php esc_html_e( 'Palavra-passe', 'jelly-area-reservada' ); ?></h2>
		</header>

		<?php $mensagem( 'senha' ); ?>

		<form class="aru-form aru-form--coluna" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="jelly_ar_perfil_senha">
			<?php wp_nonce_field( 'jelly_ar_perfil_senha' ); ?>
			<?php // Para o gestor de palavras-passe do browser saber de que conta é. ?>
			<input type="hidden" name="username" value="<?php echo esc_attr( $user->user_email ); ?>" autocomplete="username">

			<div class="aru-campo">
				<label for="aru-atual"><?php esc_html_e( 'Palavra-passe atual', 'jelly-area-reservada' ); ?></label>
				<input type="password" id="aru-atual" name="atual" required autocomplete="current-password">
			</div>

			<div class="aru-campo">
				<label for="aru-senha"><?php esc_html_e( 'Nova palavra-passe', 'jelly-area-reservada' ); ?></label>
				<input type="password" id="aru-senha" name="senha" required minlength="<?php echo (int) JELLY_AR_SENHA_MINIMO; ?>" autocomplete="new-password" aria-describedby="aru-senha-regra">
				<?php /* translators: %d: número de caracteres */ ?>
				<small id="aru-senha-regra"><?php echo esc_html( sprintf( __( 'Pelo menos %d caracteres.', 'jelly-area-reservada' ), JELLY_AR_SENHA_MINIMO ) ); ?></small>
			</div>

			<div class="aru-campo">
				<label for="aru-senha2"><?php esc_html_e( 'Confirmar a nova palavra-passe', 'jelly-area-reservada' ); ?></label>
				<input type="password" id="aru-senha2" name="senha2" required minlength="<?php echo (int) JELLY_AR_SENHA_MINIMO; ?>" autocomplete="new-password">
			</div>

			<div class="aru-form__acoes">
				<button type="submit" class="aru-botao">
					<span><?php esc_html_e( 'Alterar palavra-passe', 'jelly-area-reservada' ); ?></span>
					<i class="fa-solid fa-key" aria-hidden="true"></i>
				</button>
			</div>
		</form>
	</section>
</div>
