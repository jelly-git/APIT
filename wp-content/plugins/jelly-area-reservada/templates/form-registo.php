<?php
/**
 * Formulário de Registo.
 *
 * Enviado pelo pop-up para o admin-ajax (inc/registo.php), que cria o pedido
 * na Área Reservada.
 *
 * Nome, apelido, e-mail, telefone e empresa. Não há nome de utilizador nem
 * palavra-passe: a conta entra pelo e-mail, e a palavra-passe define-se
 * depois de a APIT aprovar o pedido — como hoje as credenciais só chegam
 * depois de a Direção aprovar a adesão. É isso que a nota e o estado final
 * dizem.
 */

defined( 'ABSPATH' ) || exit;

$privacidade = home_url( '/politica-privacidade/' );
?>
<h2 class="apit-ar__titulo" id="apit-ar-titulo-registo"><?php esc_html_e( 'Criar conta', 'jelly-area-reservada' ); ?></h2>
<p class="apit-ar__intro"><?php esc_html_e( 'Peça acesso aos conteúdos exclusivos da área reservada da APIT.', 'jelly-area-reservada' ); ?></p>

<?php
/*
 * O endereço do admin-ajax sem o domínio (/wp-admin/admin-ajax.php, ou
 * /apit/wp-admin/… no servidor): o pedido vai sempre para a origem da própria
 * página. Com o endereço completo, uma página aberta por outro endereço —
 * https, outra porta, um IP — fazia um pedido de outra origem, que o browser
 * envia sem os cookies e cuja resposta não deixa ler.
 */
?>
<form class="apit-ar__form" data-ar-form="registo" method="post" action="<?php echo esc_attr( wp_make_link_relative( admin_url( 'admin-ajax.php' ) ) ); ?>" novalidate>
	<input type="hidden" name="action" value="jelly_ar_registo">
	<?php wp_nonce_field( 'jelly_ar_registo', '_wpnonce', false ); ?>

	<?php
	/*
	 * O campo-armadilha (inc/registo.php): fora do ecrã, fora do Tab e do
	 * preenchimento automático. Uma pessoa não o vê; um robô preenche-o.
	 */
	?>
	<div class="apit-ar__armadilha" aria-hidden="true">
		<label for="apit-ar-registo-hp"><?php esc_html_e( 'Deixe este campo vazio', 'jelly-area-reservada' ); ?></label>
		<input type="text" id="apit-ar-registo-hp" name="jelly_ar_hp" value="" tabindex="-1" autocomplete="off" data-lpignore="true" data-1p-ignore data-bwignore>
	</div>

	<div class="apit-ar__grelha">
		<div class="apit-ar__campo">
			<label for="apit-ar-registo-nome"><?php esc_html_e( 'Nome', 'jelly-area-reservada' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="text"
				id="apit-ar-registo-nome"
				name="first_name"
				autocomplete="given-name"
				placeholder="<?php esc_attr_e( 'Ex.: João', 'jelly-area-reservada' ); ?>"
				required
			>
		</div>

		<div class="apit-ar__campo">
			<label for="apit-ar-registo-apelido"><?php esc_html_e( 'Apelido', 'jelly-area-reservada' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="text"
				id="apit-ar-registo-apelido"
				name="last_name"
				autocomplete="family-name"
				placeholder="<?php esc_attr_e( 'Ex.: Silva', 'jelly-area-reservada' ); ?>"
				required
			>
		</div>

		<div class="apit-ar__campo apit-ar__campo--largo">
			<label for="apit-ar-registo-email"><?php esc_html_e( 'E-mail', 'jelly-area-reservada' ); ?> <span aria-hidden="true">*</span></label>
			<input
				type="email"
				id="apit-ar-registo-email"
				name="email"
				autocomplete="email"
				placeholder="<?php esc_attr_e( 'nome@empresa.pt', 'jelly-area-reservada' ); ?>"
				required
			>
		</div>

		<div class="apit-ar__campo">
			<label for="apit-ar-registo-telefone"><?php esc_html_e( 'Telefone', 'jelly-area-reservada' ); ?></label>
			<input
				type="tel"
				id="apit-ar-registo-telefone"
				name="telefone"
				autocomplete="tel"
				placeholder="<?php esc_attr_e( '+351 912345678', 'jelly-area-reservada' ); ?>"
			>
		</div>

		<div class="apit-ar__campo">
			<label for="apit-ar-registo-empresa"><?php esc_html_e( 'Empresa', 'jelly-area-reservada' ); ?></label>
			<input
				type="text"
				id="apit-ar-registo-empresa"
				name="empresa"
				autocomplete="organization"
				placeholder="<?php esc_attr_e( 'Ex.: Produtora Exemplo', 'jelly-area-reservada' ); ?>"
			>
		</div>
	</div>

	<div class="apit-ar__campo apit-ar__campo--caixa">
		<label class="apit-ar__caixa">
			<input type="checkbox" id="apit-ar-registo-termos" name="termos" value="1" required>
			<span>
				<?php
				printf(
					/* translators: %s: link para a política de privacidade */
					esc_html__( 'Leu e aceita a %s.', 'jelly-area-reservada' ),
					'<a href="' . esc_url( $privacidade ) . '" class="apit-ar__link" target="_blank" rel="noopener">' . esc_html__( 'Política de Privacidade', 'jelly-area-reservada' ) . '</a>'
				);
				?>
			</span>
		</label>
	</div>

	<p class="apit-ar__nota">
		<i class="fa-solid fa-circle-info" aria-hidden="true"></i>
		<?php esc_html_e( 'Depois de a APIT aprovar o pedido, é enviado um e-mail para definir a palavra-passe.', 'jelly-area-reservada' ); ?>
	</p>

	<button type="submit" class="btn btn--solid apit-ar__submeter">
		<span data-ar-rotulo><?php esc_html_e( 'Criar conta', 'jelly-area-reservada' ); ?></span>
		<i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
	</button>

	<p class="apit-ar__aviso" data-ar-aviso role="alert" hidden></p>
</form>

<?php
/*
 * O fim do registo. O painel ganha `is-enviado`, que tira o título, a
 * introdução e o rodapé do formulário: o que fica é só isto. Os três passos
 * dizem onde o pedido está e o que falta, e o e-mail, para onde vai a
 * mensagem — é o que a pessoa precisa de saber para esperar por ela.
 */
?>
<div class="apit-ar__sucesso" data-ar-sucesso role="status" hidden>
	<span class="apit-ar__sucesso-icone" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
	<h2 class="apit-ar__sucesso-titulo" tabindex="-1" data-ar-sucesso-foco><?php esc_html_e( 'Pedido enviado', 'jelly-area-reservada' ); ?></h2>
	<p class="apit-ar__sucesso-texto">
		<?php
		printf(
			/* translators: %s: e-mail de quem pediu o registo */
			esc_html__( 'A APIT vai analisar o pedido. Depois de aprovado, é enviado para %s um e-mail para definir a palavra-passe.', 'jelly-area-reservada' ),
			'<strong data-ar-sucesso-email></strong>'
		);
		?>
	</p>

	<ol class="apit-ar__passos">
		<li class="is-feito"><span class="apit-ar__passo-marca" aria-hidden="true"><i class="fa-solid fa-check"></i></span> <?php esc_html_e( 'Pedido enviado', 'jelly-area-reservada' ); ?></li>
		<li class="is-atual"><span class="apit-ar__passo-marca" aria-hidden="true">2</span> <?php esc_html_e( 'Aprovação pela APIT', 'jelly-area-reservada' ); ?></li>
		<li><span class="apit-ar__passo-marca" aria-hidden="true">3</span> <?php echo esc_html( str_replace( '-', "\u{2011}", __( 'Definição da palavra-passe', 'jelly-area-reservada' ) ) ); // Hífen que não parte a palavra. ?></li>
	</ol>

	<button type="button" class="btn btn--solid apit-ar__submeter" data-ar-fechar><?php esc_html_e( 'Fechar', 'jelly-area-reservada' ); ?></button>
</div>

<div class="apit-ar__rodape">
	<p>
		<?php esc_html_e( 'Já tem conta?', 'jelly-area-reservada' ); ?>
		<a href="#area-reservada" class="apit-ar__link"><?php esc_html_e( 'Fazer login', 'jelly-area-reservada' ); ?></a>
	</p>
</div>
