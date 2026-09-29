<?php
/**
 * Os meus dados, na ARU. À esquerda, o perfil — o que não se muda aqui: as
 * iniciais, o nome, o e-mail, a empresa e desde quando é associado. À direita,
 * num cartão, o que se edita: os contactos (o telefone e a empresa) e a
 * palavra-passe. Os formulários vão a inc/aru-perfil.php, e voltam aqui com
 * ?aviso= ou ?erro=.
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

<?php
// Desde quando é associado: da aprovação (ou, sem ela, do registo).
$desde = $perfil ? ( $perfil->aprovado_em ? $perfil->aprovado_em : $perfil->registado_em ) : '';
$desde = $desde ? jelly_ar_data( 'F \d\e Y', strtotime( $desde . ' UTC' ) ) : '';
?>

<header class="aru-titulo">
	<h1><?php esc_html_e( 'Os meus dados', 'jelly-area-reservada' ); ?></h1>
	<p><?php esc_html_e( 'Os dados da conta na Área Reservada. Para mudar o nome ou o e-mail, a APIT deve ser contactada.', 'jelly-area-reservada' ); ?></p>
</header>

<div class="aru-perfil">
	<?php /* ---------- O perfil: o que não se muda aqui ---------- */ ?>
	<aside class="aru-cartao aru-perfil__cartao">
		<span class="aru-perfil__foto" aria-hidden="true"><?php echo esc_html( $pessoa['iniciais'] ); ?></span>
		<h2 class="aru-perfil__nome"><?php echo esc_html( $pessoa['nome'] ); ?></h2>
		<p class="aru-perfil__email"><?php echo esc_html( $user->user_email ); ?></p>

		<ul class="aru-perfil__factos">
			<?php if ( $perfil && $perfil->empresa ) : ?>
				<li><i class="fa-solid fa-building" aria-hidden="true"></i> <?php echo esc_html( $perfil->empresa ); ?></li>
			<?php endif; ?>
			<?php if ( $desde ) : ?>
				<?php /* translators: %s: mês e ano */ ?>
				<li><i class="fa-regular fa-calendar-check" aria-hidden="true"></i> <?php echo esc_html( sprintf( __( 'Associado desde %s', 'jelly-area-reservada' ), $desde ) ); ?></li>
			<?php endif; ?>
		</ul>

		<p class="aru-perfil__nota"><i class="fa-solid fa-lock" aria-hidden="true"></i> <?php esc_html_e( 'O nome e o e-mail identificam a conta e só a APIT os pode mudar.', 'jelly-area-reservada' ); ?></p>
	</aside>

	<?php /* ---------- O que se edita: os contactos e a palavra-passe ---------- */ ?>
	<section class="aru-cartao aru-perfil__edicao">
		<div class="aru-perfil__parte" id="jar-dados">
			<header class="aru-cartao__cabeca">
				<h2><i class="fa-regular fa-id-card" aria-hidden="true"></i> <?php esc_html_e( 'Contactos', 'jelly-area-reservada' ); ?></h2>
			</header>

			<?php $mensagem( 'dados' ); ?>

			<?php if ( $perfil ) : ?>
				<form class="aru-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="jelly_ar_perfil">
					<?php wp_nonce_field( 'jelly_ar_perfil' ); ?>

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
							<span><?php esc_html_e( 'Guardar', 'jelly-area-reservada' ); ?></span>
							<i class="fa-solid fa-check" aria-hidden="true"></i>
						</button>
					</div>
				</form>
			<?php else : ?>
				<p class="aru-nota"><?php esc_html_e( 'Esta conta não tem dados de associado para alterar.', 'jelly-area-reservada' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="aru-perfil__parte" id="jar-senha">
			<header class="aru-cartao__cabeca">
				<h2><i class="fa-solid fa-key" aria-hidden="true"></i> <?php esc_html_e( 'Palavra-passe', 'jelly-area-reservada' ); ?></h2>
			</header>

			<?php $mensagem( 'senha' ); ?>

			<form class="aru-form aru-form--tres" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="jelly_ar_perfil_senha">
				<?php wp_nonce_field( 'jelly_ar_perfil_senha' ); ?>
				<?php // Para o gestor de palavras-passe do browser saber de que conta é. ?>
				<input type="hidden" name="username" value="<?php echo esc_attr( $user->user_email ); ?>" autocomplete="username">

				<div class="aru-campo">
					<label for="aru-atual"><?php esc_html_e( 'Atual', 'jelly-area-reservada' ); ?></label>
					<input type="password" id="aru-atual" name="atual" required autocomplete="current-password">
				</div>

				<div class="aru-campo">
					<label for="aru-senha"><?php esc_html_e( 'Nova', 'jelly-area-reservada' ); ?></label>
					<input type="password" id="aru-senha" name="senha" required minlength="<?php echo (int) JELLY_AR_SENHA_MINIMO; ?>" autocomplete="new-password" aria-describedby="aru-senha-regra">
				</div>

				<div class="aru-campo">
					<label for="aru-senha2"><?php esc_html_e( 'Confirmar a nova', 'jelly-area-reservada' ); ?></label>
					<input type="password" id="aru-senha2" name="senha2" required minlength="<?php echo (int) JELLY_AR_SENHA_MINIMO; ?>" autocomplete="new-password">
				</div>

				<?php /* translators: %d: número de caracteres */ ?>
				<p class="aru-nota aru-form__inteira" id="aru-senha-regra"><?php echo esc_html( sprintf( __( 'A nova palavra-passe tem pelo menos %d caracteres e não pode ser o e-mail.', 'jelly-area-reservada' ), JELLY_AR_SENHA_MINIMO ) ); ?></p>

				<div class="aru-form__acoes">
					<button type="submit" class="aru-botao">
						<span><?php esc_html_e( 'Alterar palavra-passe', 'jelly-area-reservada' ); ?></span>
						<i class="fa-solid fa-key" aria-hidden="true"></i>
					</button>
				</div>
			</form>
		</div>
	</section>
</div>
