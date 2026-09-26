<?php
/**
 * O modelo dos e-mails da Área Reservada: o cabeçalho com o logótipo e a faixa
 * no degradé da marca, o cartão com o conteúdo e o rodapé da APIT.
 *
 * Feito com tabelas e estilos em cada elemento, que é o que os programas de
 * e-mail leem: o Outlook ignora quase todo o CSS moderno, o Gmail tira o
 * <style> em algumas versões, e nenhum mostra SVG. Onde um programa não mostra
 * o degradé, fica o magenta por baixo dele.
 *
 * Um tema pode ter o seu em <tema>/area-reservada/email.php (jelly_ar_template).
 *
 * $args (inc/emails.php):
 *   titulo     o título do e-mail
 *   paragrafos parágrafos, já em HTML seguro
 *   dados      [ rótulo => valor ] numa tabela (opcional)
 *   passos     o passo em que o pedido está, 1 a 3 (opcional)
 *   botao      [ 'texto' => …, 'url' => … ] (opcional)
 *   nota       uma linha pequena por baixo (opcional)
 *   previa     o texto que o programa de e-mail mostra ao lado do assunto
 *   logo       o endereço da imagem do logótipo: cid:apit-logo no envio (a
 *              imagem vai embutida no e-mail), o endereço normal nas
 *              pré-visualizações
 */

defined( 'ABSPATH' ) || exit;

$a = wp_parse_args(
	$args,
	[
		'titulo'     => '',
		'paragrafos' => [],
		'dados'      => [],
		'passos'     => 0,
		'botao'      => null,
		'nota'       => '',
		'previa'     => '',
		'logo'       => jelly_ar_email_logo_url(),
	]
);

$logo     = $a['logo'];
$site     = home_url( '/' );
$fonte    = "'Omnes', 'Segoe UI', Helvetica, Arial, sans-serif";
$preto    = '#1b2a33';
$suave    = '#5f6b72';
$linha    = '#e3e8ec';
$fundo    = '#f4f9ff';
$magenta  = '#f41892';
$turquesa = '#2ec6b0';
$passos   = [
	1 => __( 'Pedido enviado', 'jelly-area-reservada' ),
	2 => __( 'Aprovação pela APIT', 'jelly-area-reservada' ),
	3 => __( 'Definição da palavra-passe', 'jelly-area-reservada' ),
];
?>
<!doctype html>
<html lang="pt-PT">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="x-apple-disable-message-reformatting">
	<title><?php echo esc_html( $a['titulo'] ); ?></title>
</head>
<body style="margin:0;padding:0;background:<?php echo esc_attr( $fundo ); ?>;">
	<?php // O texto que o programa de e-mail mostra ao lado do assunto, escondido no corpo. ?>
	<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;"><?php echo esc_html( $a['previa'] ); ?></div>

	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?php echo esc_attr( $fundo ); ?>;">
		<tr>
			<td align="center" style="padding:32px 16px;">
				<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:4px;overflow:hidden;">

					<?php // O cabeçalho: o logótipo sobre branco, e a faixa do degradé da marca. ?>
					<tr>
						<td style="padding:32px 40px 28px;">
							<a href="<?php echo esc_url( $site ); ?>" style="text-decoration:none;">
								<?php if ( $logo ) : ?>
									<img src="<?php echo esc_url( $logo, [ 'cid', 'http', 'https' ] ); ?>" width="150" alt="APIT" style="display:block;width:150px;max-width:150px;height:auto;border:0;">
								<?php else : ?>
									<span style="font-family:<?php echo esc_attr( $fonte ); ?>;font-size:28px;font-weight:700;color:<?php echo esc_attr( $magenta ); ?>;">APIT</span>
								<?php endif; ?>
							</a>
						</td>
					</tr>
					<tr>
						<td height="6" style="height:6px;line-height:6px;font-size:6px;background-color:<?php echo esc_attr( $magenta ); ?>;background-image:linear-gradient(100deg,#f41892 0%,#d2359f 22%,#2ec6b0 46%,#4a85c8 70%,#8048a6 100%);">&nbsp;</td>
					</tr>

					<?php // O conteúdo. ?>
					<tr>
						<td style="padding:40px 40px 8px;font-family:<?php echo esc_attr( $fonte ); ?>;color:<?php echo esc_attr( $preto ); ?>;">
							<h1 style="margin:0 0 20px;font-family:<?php echo esc_attr( $fonte ); ?>;font-size:28px;line-height:1.2;font-weight:500;color:<?php echo esc_attr( $preto ); ?>;"><?php echo esc_html( $a['titulo'] ); ?></h1>

							<?php foreach ( $a['paragrafos'] as $p ) : ?>
								<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:<?php echo esc_attr( $preto ); ?>;"><?php echo wp_kses( $p, [ 'strong' => [], 'a' => [ 'href' => [], 'style' => [] ], 'br' => [] ] ); ?></p>
							<?php endforeach; ?>
						</td>
					</tr>

					<?php if ( $a['dados'] ) : ?>
						<tr>
							<td style="padding:8px 40px 16px;">
								<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid <?php echo esc_attr( $linha ); ?>;">
									<?php foreach ( $a['dados'] as $rotulo => $valor ) : ?>
										<tr>
											<td style="padding:12px 16px 12px 0;border-bottom:1px solid <?php echo esc_attr( $linha ); ?>;font-family:<?php echo esc_attr( $fonte ); ?>;font-size:13px;letter-spacing:0.6px;text-transform:uppercase;color:<?php echo esc_attr( $suave ); ?>;white-space:nowrap;vertical-align:top;"><?php echo esc_html( $rotulo ); ?></td>
											<td style="padding:12px 0;border-bottom:1px solid <?php echo esc_attr( $linha ); ?>;font-family:<?php echo esc_attr( $fonte ); ?>;font-size:15px;color:<?php echo esc_attr( $preto ); ?>;"><?php echo esc_html( '' !== (string) $valor ? $valor : '—' ); ?></td>
										</tr>
									<?php endforeach; ?>
								</table>
							</td>
						</tr>
					<?php endif; ?>

					<?php
					/*
					 * Os três passos do pedido, como no pop-up: o feito em turquesa,
					 * o atual em magenta, o que falta a cinzento.
					 */
					?>
					<?php if ( $a['passos'] ) : ?>
						<tr>
							<td style="padding:16px 40px 8px;">
								<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
									<tr>
										<?php foreach ( $passos as $n => $rotulo ) : ?>
											<?php
											$feito = $n < $a['passos'];
											$atual = $n === $a['passos'];
											$cor   = $feito ? $turquesa : ( $atual ? $magenta : '#b8c0c5' );
											?>
											<td width="33%" align="center" valign="top" style="padding:0 4px;font-family:<?php echo esc_attr( $fonte ); ?>;">
												<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
													<tr>
														<td width="34" height="34" align="center" valign="middle" style="width:34px;height:34px;border-radius:17px;border:2px solid <?php echo esc_attr( $cor ); ?>;background:<?php echo esc_attr( $feito ? $turquesa : '#ffffff' ); ?>;font-size:15px;font-weight:700;color:<?php echo esc_attr( $feito ? '#ffffff' : $cor ); ?>;"><?php echo $feito ? '&#10003;' : (int) $n; ?></td>
													</tr>
												</table>
												<p style="margin:10px 0 0;font-size:13px;line-height:1.35;color:<?php echo esc_attr( $feito || $atual ? $preto : $suave ); ?>;"><?php echo esc_html( $rotulo ); ?></p>
											</td>
										<?php endforeach; ?>
									</tr>
								</table>
							</td>
						</tr>
					<?php endif; ?>

					<?php // O botão, feito para o Outlook também: uma célula com cor, e o link dentro. ?>
					<?php if ( ! empty( $a['botao']['url'] ) ) : ?>
						<tr>
							<td align="left" style="padding:24px 40px 8px;">
								<table role="presentation" cellpadding="0" cellspacing="0" border="0">
									<tr>
										<td align="center" bgcolor="<?php echo esc_attr( $magenta ); ?>" style="border-radius:200px;background:<?php echo esc_attr( $magenta ); ?>;">
											<a href="<?php echo esc_url( $a['botao']['url'] ); ?>" style="display:inline-block;padding:15px 32px;font-family:<?php echo esc_attr( $fonte ); ?>;font-size:14px;font-weight:700;letter-spacing:0.6px;text-transform:uppercase;color:#ffffff;text-decoration:none;border-radius:200px;"><?php echo esc_html( $a['botao']['texto'] ); ?></a>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					<?php endif; ?>

					<tr>
						<td style="padding:24px 40px 40px;font-family:<?php echo esc_attr( $fonte ); ?>;">
							<?php if ( $a['nota'] ) : ?>
								<p style="margin:0;padding-top:20px;border-top:1px solid <?php echo esc_attr( $linha ); ?>;font-size:13px;line-height:1.55;color:<?php echo esc_attr( $suave ); ?>;"><?php echo esc_html( $a['nota'] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<?php // O rodapé, fora do cartão. ?>
				<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">
					<tr>
						<td align="center" style="padding:24px 24px 0;font-family:<?php echo esc_attr( $fonte ); ?>;font-size:12px;line-height:1.6;color:<?php echo esc_attr( $suave ); ?>;">
							<?php esc_html_e( 'APIT — Associação de Produtores Independentes de Televisão', 'jelly-area-reservada' ); ?><br>
							<?php echo esc_html( jelly_ar_email_morada() ); ?><br>
							<a href="<?php echo esc_url( $site ); ?>" style="color:<?php echo esc_attr( $magenta ); ?>;text-decoration:none;"><?php echo esc_html( wp_parse_url( $site, PHP_URL_HOST ) ); ?></a>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
