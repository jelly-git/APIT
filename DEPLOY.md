# Deploy para o cPanel

> **Atenção:** o `public_html` desta conta aloja vários projetos lado a lado. O
> nosso vive em `public_html/apit`, e nada fora dessa pasta pode ser escrito.
> O `.cpanel.yml` está limitado a ela e aborta o deploy se não encontrar lá o
> `wp-config.php`, para que um caminho errado não espalhe ficheiros pelos
> outros projetos.

O git entrega **apenas código**. A base de dados e a pasta de uploads são
migrações únicas, feitas à mão — o repositório não as contém, por desenho:

| O que | Vem do git? | Porquê |
|---|---|---|
| Tema `hello-elementor-child` | Sim | é o nosso código |
| Plugin `jelly-area-reservada` | Sim | é o nosso código (Jelly): a Área Reservada, e a fonte dos eventos do calendário |
| Core do WordPress | Não | instala-se/actualiza-se no servidor |
| `wp-config.php` | Não | contém credenciais, e são outras no servidor |
| `wp-content/uploads` | Não | conteúdo, não código (31 MB) |
| Base de dados | Não | importá-la a cada deploy apagaria o site |

---

## Ambiente de destino (confirmado)

| | |
|---|---|
| URL | `https://dev.jellycode.agency/apit` |
| Caminho | `/home/agencydevjellyc/public_html/apit` |
| Base de dados | `agencydevjellyc_apit` |
| Prefixo das tabelas | `wp_` (igual ao local) |
| WordPress | 7.1 (igual ao local) |

O `siteurl` do servidor é `dev.jellycode.agency/apit`, não `jellycode.agency`.
Ambos os anfitriões servem a mesma pasta, mas o WordPress canoniza para o
primeiro — é esse que conta para a troca de URLs.

Estado a 1 de setembro de 2026: instalação limpa com o tema `twentytwentyfive`
activo, sem `hello-elementor` nem `elementor`. Foram instalados entretanto — o
site chegou a estar no ar na v0.22.7 —, pelo que a secção 1 abaixo só interessa
se houver que reinstalar.

A 24 de setembro de 2026 o servidor responde **401** a tudo, incluindo aos
ficheiros do tema: está atrás de autenticação HTTP. Nada do lado do servidor se
pode confirmar de fora sem essas credenciais, incluindo a versão no ar — a
verificação da secção 5 tem de ser feita já autenticado, no browser.

---

## 1. Pré-requisitos no servidor

O tema filho não funciona sozinho. Instalar antes do primeiro deploy:

| Componente | Versão local |
|---|---|
| WordPress core | 7.1 |
| Tema `hello-elementor` (pai) | 3.5.1 |
| Plugin `elementor` | 4.2.4 |
| Plugin `advanced-custom-fields-pro` | 6.8.10 |
| Plugin `gravityforms` | 3.1.2 |
| Plugin `wp-mail-smtp` | 4.9.0 |

O **WP Mail SMTP** é gratuito (`wp plugin install wp-mail-smtp --version=4.9.0
--activate`). **A configuração do envio é a de cada servidor e nunca viaja na
base de dados**: as opções `wp_mail_smtp*` (a configuração, a chave
`wp_mail_smtp_mail_key`, os contadores) e as tabelas `wp_wpmailsmtp_*` ficam de
fora da exportação, e as do servidor são guardadas e repostas na importação
(secção 3). Em produção o envio é o **PHP** (`mail()`), escolhido a 28 de
setembro de 2026 porque o SMTP estava a ser bloqueado no servidor: muda-se lá,
no WP Mail SMTP, e não aqui. Os e-mails da Área Reservada saem por ele.

Por SSH, se houver WP-CLI no servidor:

```bash
cd ~/public_html/apit
wp theme install hello-elementor --version=3.5.1
wp plugin install elementor --version=4.2.4 --activate
```

Sem WP-CLI, instalar pelo wp-admin em Aparência > Temas e Plugins > Adicionar.

### O Gravity Forms também é manual

Como o ACF Pro: é pago, não está no repositório do WordPress e o `.cpanel.yml`
só publica o tema. O ZIP descarrega-se da conta do cliente em gravityforms.com
e instala-se pelo wp-admin. **Sem ele o formulário da ficha de inscrição não
aparece** — a página Media Kit mostra o shortcode em texto, que foi como a
banda dos Associados esteve dois dias.

A chave de licença é do cliente e põe-se em Formulários > Definições. Só serve
para actualizações e add-ons: os formulários funcionam sem ela.

O formulário em si **viaja na base de dados**, nas tabelas `wp_gf_*` — não é
preciso recriá-lo no servidor.

**A newsletter** usa o formulário "Newsletter" (id 2 no local). O desenho é o do
tema (`template-parts/newsletter.php`), que entrega a inscrição ao Gravity Forms
por `inc/newsletter.php`; o tema encontra o formulário pelo título, pelo que um
formulário importado à mão com outro id também serve. Sem o formulário no
servidor, o bloco aparece mas responde que a inscrição não está disponível. As
inscrições ficam em `wp_gf_entry*`, que a exportação não leva (secção 3.2): as de
produção ficam em produção.

O envio para o Mailchimp é o **Mailchimp Add-On** do Gravity Forms, instalado à
mão como o próprio Gravity Forms. A chave de API e a audiência põem-se no
back-office de cada site (Formulários › Definições › Mailchimp) e o *feed* no
formulário Newsletter. As inscrições feitas antes do *feed* existir não passam
sozinhas: exportam-se em CSV (Formulários › Importar/Exportar) e importam-se na
audiência.

### ACF Pro é manual, nas duas pontas

O ACF Pro é licenciado e por isso não está no repositório — o `.gitignore`
exclui os plugins. Não vem no deploy: tem de ser instalado e actualizado à mão
no servidor, e a versão tem de ser **a mesma** do local, senão os grupos de
campos podem não sincronizar.

Os **grupos de campos** já vêm no git, em
`wp-content/themes/hello-elementor-child/acf-json/`. O ACF lê-os dessa pasta em
cada pedido, pelo que um campo criado no local passa a existir no servidor no
mesmo commit que o template que o usa.

Por isso: **não criar nem editar grupos de campos no wp-admin do servidor.** O
ACF gravaria o ficheiro na pasta do tema no servidor, e o deploy seguinte —
que apaga e recopia o tema — levava a alteração consigo. Criar sempre no local
e enviar por git.

A chave de licença serve para as actualizações, não para funcionar. Se a
licença tiver limite de sites, é o servidor que interessa activar.

---

## 2. Ligar o git ao cPanel (uma vez)

O repositório é público, pelo que não são precisas credenciais.

1. cPanel > **Git™ Version Control** > **Create**
2. Ligar **Clone a Repository**
3. **Clone URL:** `https://github.com/jelly-git/APIT.git`
4. **Repository Path:** `repositories/APIT`
5. **Branch:** `master`
6. **Create**

O cPanel clona para `~/repositories/APIT` e lê o `.cpanel.yml` do repositório,
que copia o tema para `~/public_html/apit/wp-content/themes/`.

---

## 3. Base de dados

> **A exportação faz-se no momento de subir, não antes.** Um commit não exporta
> a base de dados e não existe processo automático que o faça: páginas, equipa,
> órgãos sociais, categorias, campos ACF e multimédia vivem só na base de dados.
> Um `.sql` de ontem não tem o trabalho de hoje, e importá-lo apagaria-o.

No Local, botão direito no site *apit* > **Open site shell**.

### 3.0 As ligações internas ficam guardadas como caminhos

O servidor serve o site de uma subpasta — `dev.jellycode.agency/apit` — e é isso
que torna `/contactos/` uma armadilha: funciona no local, que está na raiz, e no
servidor resolve para `dev.jellycode.agency/contactos/`, fora do WordPress. A
migalha `href="/"` de sete páginas apontava exactamente para aí.

Quem trata disto é o tema, em `inc/links.php`. A página acabada passa por um
filtro que prefixa os `href` e `src` que começam por uma única barra com o
caminho onde o WordPress vive — `/apit` no servidor, nada na raiz de um
domínio. **Os valores guardados ficam como caminhos** e nunca nomeiam um
domínio: mudar de alojamento não pede `search-replace`, e uma ligação escrita
amanhã no painel do Elementor fica coberta sem ninguém saber que isto existe.

O que o filtro deixa em paz: `//cdn.exemplo.com`, endereços completos,
âncoras, `mailto:`, caminhos verdadeiramente relativos e o que já esteja sob
`/apit` — para não levar o prefixo duas vezes.

Nada disto se vê no local, onde o caminho do site é vazio e o filtro sai logo.
A verificação é fazer de conta que o site está na subpasta, e é dupla:

```bash
# 1. os casos do filtro, um a um
wp eval-file prova-prefixo.php

# 2. as páginas servidas, passadas de novo pelo filtro com o home_url da
#    subpasta — sai o que um visitante em /apit clicaria
wp eval-file prova-servidor.php
```

A segunda tem de dar **todas as ligações internas sob `/apit`**. A 24 de
setembro eram 11 e passaram todas.

### 3.0.1 E cada página com hero traz a sua migalha?

As migalhas não estão escritas em página nenhuma: o tema calcula-as e injecta-as
no primeiro lugar dentro da coluna do hero. Uma página nova traz a migalha por
existir — mas uma página construída de raiz, sem a classe dessa coluna, fica sem
âncora e sem migalha, e **nada se queixa**. É esse silêncio que esta prova
quebra:

```bash
bash tools/guardar-paginas.sh
wp eval-file tools/prova-migalhas.php
```

O `guardar-paginas.sh` chama `wp`. Fora do "Open site shell" do Local, onde o
`wp` não existe, aceita outro na variável `WP`: `WP=/caminho/para/wp bash
tools/guardar-paginas.sh`.

Tem de dar tantas migalhas quantas as páginas com hero, e a prova nomeia as que
falharem. A 25 de setembro eram 11 de 11, de 17 páginas lidas. Verifiquei que
falha quando deve: com a migalha apagada de uma página, dá erro e diz qual.

A regra vale nos dois sentidos — quem tem âncora tem migalha, quem não tem
âncora não tem migalha —, o que também apanha uma migalha a aparecer onde não
devia.

> Houve duas passagens a fazer o contrário disto — converter tudo para
> endereços completos, e deixar o `search-replace` da exportação tratar deles.
> Funcionava, mas punha o domínio dentro das páginas e fazia um shortcode ler
> ao contrário de todos os outros. Os dois mecanismos não convivem: ficou o
> filtro, que é o que não escreve o domínio em lado nenhum.

### 3.1 Limpar o que não deve viajar

Correr **antes** de exportar, ainda na pasta do site:

```bash
# Um autosave do Elementor mais recente do que a própria página é oferecido no
# editor e substitui o layout ao gravar. Escrever o _elementor_data por código
# não actualiza o post_modified, pelo que qualquer autosave parece mais recente
# do que a página — foi o que aconteceu à Home a 1 de setembro.
wp post list --post_type=revision --field=post_name --format=csv | grep autosave
wp eval 'global $wpdb; foreach ( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE \"%autosave%\"" ) as $id ) { wp_delete_post( $id, true ); }'

# Caches do Elementor e do ACF, incluindo avisos de actualização. Regeneram-se.
wp transient delete --all

# Instantâneo de diagnóstico do ACF: guarda o URL local com as barras
# escapadas (http:\\/\\/apit.local), forma que o search-replace não apanha.
# O ACF reconstrói-o no primeiro acesso ao wp-admin.
wp option delete acf_site_health

# Registo de erros de JavaScript do editor do Elementor. É diagnóstico da
# máquina local e guarda caminhos com apit.local dentro de dados serializados.
wp option delete elementor_log

# O diagnóstico do último envio falhado do WP Mail SMTP: a conversa com o
# servidor de correio, com o IP e o nome (apit.local) desta máquina. Não
# viaja, mas é o que o WP Mail SMTP mostra para se perceber o erro — por isso,
# em vez de o apagar aqui, tira-se só do ficheiro exportado (secção 3.2).
wp option get wp_mail_smtp_email_sending_debug >/dev/null 2>&1 && echo "há diagnóstico do SMTP: tirar do ficheiro"

# Cache do HTML já renderizado de cada página. Regenera-se, e duplica o
# conteúdo — incluindo os URLs escapados que a secção seguinte tem de tratar.
wp eval 'global $wpdb; echo $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = \"_elementor_element_cache\"" ) . " caches\n";'

# CSS por página do Elementor. O `_elementor_css` de cada página diz "o CSS está
# no ficheiro uploads/elementor/css/post-<id>.css" — e no servidor esse ficheiro
# é o que lá ficou da última vez, com o layout antigo. A 23 de setembro a
# Internacionalização refeita subiu assim sem o padding dos contentores, sem o
# degradé dos cartões e sem o fundo da Área Reservada. Sem esta meta, o
# Elementor gera o ficheiro de novo no primeiro acesso a cada página.
wp eval '\Elementor\Plugin::$instance->files_manager->clear_cache(); global $wpdb; echo $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = \"_elementor_css\"" ) . " _elementor_css\n";'

# Caches do WordPress.org: feeds do painel, block patterns e traduções.
# O `wp transient delete --all` acima não apanha os de nível de site, e eram
# 894 KB dos 1,47 MB que a exportação de 22 de setembro tinha a mais.
wp eval 'global $wpdb; echo $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE \"_site_transient_%\"" ) . " transients de site\n";'
```

**Esta limpeza tem de ser o comando imediatamente antes da exportação.** Os
caches voltam sozinhos: cada arranque do WordPress — e o `search-replace` é um —
pode regenerá-los. A 25 de setembro o ficheiro saiu com 1,73 MB porque a
limpeza tinha sido feita alguns comandos antes; **455 KB eram só o
`_site_transient_t15s-registry-gforms`**, o registo de traduções do Gravity
Forms, que voltou pelo meio. Se o ficheiro sair muito acima de 1,2 MB, é aqui
que se procura:

```bash
wp eval 'global $wpdb; foreach ( $wpdb->get_results( "SELECT option_name, ROUND(LENGTH(option_value)/1024,1) kb FROM {$wpdb->options} ORDER BY LENGTH(option_value) DESC LIMIT 8" ) as $r ) printf( "  %-52s %s KB\n", $r->option_name, $r->kb );'
```

Os três que mais pesam e que regeneram sozinhos:

| Opção | Peso | O que é |
|---|---|---|
| `_site_transient_t15s-registry-gforms` | 455 KB | Traduções do Gravity Forms, do WordPress.org |
| `gform_version_info` | 61 KB | Versões e add-ons, lido do site do Gravity Forms |
| `_transient_GFCache_*` | 61 KB | Cache interna do Gravity Forms |

Num só comando, a correr logo antes do `search-replace`:

```bash
wp eval 'global $wpdb;
  \Elementor\Plugin::$instance->files_manager->clear_cache();
  $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN (\"_elementor_css\",\"_elementor_element_cache\",\"_elementor_page_assets\")" );
  $n = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE \"_site_transient_%\" OR option_name LIKE \"_transient_%\" OR option_name = \"gform_version_info\" OR option_name LIKE \"elementor_atomic_cache%\" OR option_name = \"_elementor_assets_data\"" );
  foreach ( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE \"%autosave%\"" ) as $id ) { wp_delete_post( $id, true ); }
  echo "$n opcoes de cache apagadas\n";'
```

### 3.2 Exportar

> **Os dados da Área Reservada nunca vão para produção, e o ficheiro nunca os
> altera lá.** Associados, pedidos de registo, marcações, eventos, mesas,
> horários, documentos, acessos, descargas e o registo de ações vivem em
> produção e são geridos no back-office de produção. O que existe aqui no local
> é de teste e de demonstração (utilizadores `demo-*`, marcações "demo gráfico",
> acessos de `192.0.2.x`) e fica aqui.
>
> Até à v0.38.0 a exportação levava as tabelas `wp_jelly_ar_*` com
> `DROP TABLE IF EXISTS`: importá-la apagava tudo o que estivesse na AR em
> produção e punha no lugar o que estivesse no local. Desde 28 de setembro
> ficam de fora, e o mesmo para os utilizadores.

O que fica fora do ficheiro, e porquê:

| Fica fora | Porquê |
|---|---|
| as tabelas `wp_jelly_ar_*` | são a Área Reservada |
| `wp_users`, `wp_usermeta` | os associados são utilizadores; e os administradores de produção são os de produção |
| `wp_gf_entry*`, `wp_gf_draft_submissions` | as respostas aos formulários (Media Kit) recebidas em produção |
| `wp_wpmailsmtp_*` (`debug_events`, `tasks_meta`) | o envio de e-mail é o de cada servidor |
| as opções `wp_mail_smtp*` e os transientes dele | a configuração do envio de produção (o PHP) fica como está: guardadas antes e repostas depois, como as da AR |
| `wp_gf_addon_feed` e as opções `gravityformsaddon_gravityformsmailchimp*` | a ligação ao Mailchimp (feed, chave de API, audiência) é a de cada site e configura-se no back-office dele; as opções do servidor são guardadas antes e repostas depois |
| as opções `jelly_ar_*` e os transientes da AR | a `wp_options` é substituída inteira; estas são guardadas antes e repostas depois, com o valor que tinham no servidor |
| os termos `jelly_ar_doc_categoria` | restos órfãos de uma versão antiga do plugin |

Quando as tabelas da AR ainda não existem no servidor, o plugin cria-as vazias
no primeiro acesso ao wp-admin (`jelly_ar_verificar_instalacao`). A instalação
pode correr quantas vezes for: o `dbDelta` só acrescenta o que falta, as
migrações só correm se a coluna antiga lá estiver, e as categorias iniciais só
se não houver nenhuma. **Os eventos do calendário público vêm dessas tabelas**:
são criados no back-office de produção, não vão daqui.

```bash
cd "C:\Users\faust\Local Sites\apit"

# As tabelas a exportar: todas as do prefixo menos as da tabela acima. Lista
# explícita e não --all-tables, que levava a AR. O --all-tables-with-prefix
# continua preciso: sem ele o WP-CLI só aceita as tabelas que o WordPress
# conhece, e as do Gravity Forms caíam em silêncio — o formulário do Media Kit
# ficava fora sem aviso nenhum.
TABELAS=$(wp db tables --all-tables-with-prefix --format=csv | tr ',' '\n' | tr -d '\r' \
  | grep -v -E '^wp_(jelly_ar_.*|users|usermeta|wpmailsmtp_.*|gf_entry|gf_entry_meta|gf_entry_notes|gf_draft_submissions|gf_addon_feed)$')

# troca os URLs e escreve o ficheiro, sem tocar na base de dados local.
# --precise porque os dados do Elementor estão serializados.
wp search-replace "http://apit.local" "https://dev.jellycode.agency/apit" \
    $TABELAS --all-tables-with-prefix --precise --export=bd-sem-cabecalho.sql

# O resto é texto: os endereços escapados dentro do JSON do Elementor, que o
# comando acima não apanha; as linhas da AR que estão em tabelas partilhadas;
# as opções do WP Mail SMTP e do Mailchimp; e o cabeçalho (SQL_MODE, e
# guardar/repor as opções da AR, do SMTP e do Mailchimp que estiverem no servidor). O porquê de cada um está no próprio script.
php app/public/tools/exportacao-servidor.php bd-sem-cabecalho.sql apit-bd-para-servidor.sql
rm bd-sem-cabecalho.sql

# confirmar antes de subir
F=apit-bd-para-servidor.sql
grep -c "apit.local" $F                                    # 0
grep -c "autosave-v1" $F                                   # 0
grep -c "email_sending_debug" $F                           # 0
grep -c "'_elementor_css'" $F                              # 0
grep -v apit_ar_opcoes $F | grep -c "jelly_ar"             # 0
grep -v apit_ar_opcoes $F | grep -c "wp_mail_smtp"         # 0
grep -c "wpmailsmtp" $F                                   # 0
grep -v apit_ar_opcoes $F | grep -c "gravityformsmailchimp" # 0
grep -c "wp_gf_addon_feed" $F                             # 0
grep -c -E 'TABLE[^`]*`wp_(jelly_ar_|users|usermeta)' $F   # 0
grep -c -E "demo-|example\.test|192\.0\.2\." $F            # 0
grep -c "home_video_rotulo', 'DEMO" $F                # 0: o vídeo DEMO da Home sai no script
grep -c "CREATE TABLE" $F                                  # 19

# arquivar a cópia versionada, com a versão lida do próprio tema
VERSAO=$(sed -n 's/^Version: //p' app/public/wp-content/themes/hello-elementor-child/style.css | tr -d '\r')
cp $F "bd/apit-bd-v$VERSAO-$(date +%F).sql"
```

As 19 tabelas: 11 do WordPress, 4 do Gravity Forms (o formulário), 4 do Action
Scheduler e a `wp_e_events` do Elementor.

### 3.3 Provar por importação

A prova é importar o ficheiro numa base de dados temporária que faça de
produção — com as tabelas da AR e os utilizadores já lá dentro — e ver que
saem da importação exactamente como entraram:

1. criar `apit_prova` e copiar para lá, da BD local, as tabelas `wp_jelly_ar_*`,
   `wp_users`, `wp_usermeta`, `wp_gf_entry*` e `wp_wpmailsmtp_*`; criar a
   `wp_options` com uma `jelly_ar_db_version` de valor **diferente** do local
   (por exemplo `9`) e uma `wp_mail_smtp` como a de produção (o envio pelo
   PHP, `mailer` = `mail`), para se ver quais sobrevivem;
2. tirar `CHECKSUM TABLE` e a contagem de cada uma;
3. importar `apit-bd-para-servidor.sql`;
4. repetir o `CHECKSUM TABLE`: **zero diferenças**; a `jelly_ar_db_version`
   continua `9`, e a `wp_mail_smtp` continua a de produção;
5. contagens de `wp_posts`, `wp_postmeta`, `wp_options` e `wp_gf_form` iguais às
   do local, e nenhum `post_content` a diferir fora da troca de URLs;
6. decodificar o `_elementor_data` de cada página já importado, lendo por
   `mysqli` e não pela saída do cliente `mysql`, que escapa as barras e faz
   falhar JSON que está bom. Os que falham têm de ser os mesmos que falham no
   local, e hoje são só os que estão vazios;
7. apagar `apit_prova`.

Referência de 28 de setembro de 2026 (v0.55.2): 20 tabelas, 2357 linhas,
1,12 MB, 349 endereços do servidor e nenhum local; 17 tabelas protegidas sem
uma diferença. Um ficheiro muito menor é sinal de exportação incompleta — foi
assim que se deu pelas tabelas do Gravity Forms em falta; um muito maior é
sinal de que os caches do WordPress.org voltaram (em 22 de setembro eram 894 KB
dos 1,47 MB iniciais).

### Onde ficam os ficheiros

**`Local Sites/apit/apit-bd-para-servidor.sql` é sempre o ficheiro a subir.** O
nome não muda, para que este documento nunca aponte para um `.sql` errado.

O histórico fica em `Local Sites/apit/bd/`, uma exportação por versão do tema,
com o `LEIA-ME.md` dessa pasta a dizer o estado de cada uma. Base de dados e
código sobem em par: os dados do Elementor gravados na base de dados dependem
das classes CSS que o tema dessa versão define.

**As exportações até à v0.38.0 não servem para subir**: levam as tabelas da
Área Reservada e os utilizadores, e importá-las apagava os de produção.

Nada disto entra no git. As exportações antigas contêm a `wp_users`, e com ela
o *hash* da palavra-passe do administrador, e as feitas até à v0.60.1 a
configuração do WP Mail SMTP, com a palavra-passe do correio cifrada.

> **Substitui** o conteúdo do site em `agencydevjellyc_apit` — páginas,
> notícias, multimédia, menus, opções, o formulário — **e mais nada**. A Área
> Reservada, os utilizadores e as respostas aos formulários ficam como estão
> em produção. O acesso ao wp-admin continua a ser o de produção.
>
> Os posts levam o autor com o id do local (`1`, o `Jelly-APIT`). Se em
> produção esse id não existir, as páginas aparecem sem autor no wp-admin —
> não partem.

1. phpMyAdmin > base de dados `agencydevjellyc_apit`
2. **Importar** > carregar `apit-bd-para-servidor.sql` > **Executar**

O ficheiro traz `DROP TABLE IF EXISTS` nas 20 tabelas que leva, pelo que não é
preciso esvaziar nada antes — e não se deve: esvaziar a base de dados levava a
AR com ela.

**Não usar `wp db export` nem `--all-tables`** como alternativa: levam as tabelas
da AR e os utilizadores.

---

## 4. Uploads (uma vez)

Copiar `wp-content/uploads/` do site local para o servidor, por FTP/SFTP ou pelo
File Manager. São os ficheiros da biblioteca de multimédia — os logótipos vivem
no tema e vão pelo git, mas as imagens destacadas das notícias não.

**Menos a pasta `uploads/elementor/`.** É a cache de CSS por página do Elementor,
regenera-se sozinha no servidor, e pelo menos um dos ficheiros tem `apit.local`
escrito lá dentro — copiada, mandava o servidor buscar folhas de estilo a esta
máquina. É a mesma cache cujo `_elementor_css` a secção 3.1 apaga da base de
dados, e pela mesma razão.

Estado a 24 de setembro de 2026: 219 ficheiros, 31 MB, correspondentes a 91
anexos na base de dados — todos com o ficheiro no sítio, verificado. O valor de
5,5 MB na tabela do topo é de 1 de setembro e ficou para trás.

### A pasta da Área Reservada

`uploads/jelly-area-reservada/` guarda os documentos que os administradores
carregam no back-office. **Ao contrário do resto dos uploads, não é para copiar
por cima da do servidor** depois de lá haver documentos: os que forem carregados
em produção só existem lá, e a tabela `wp_jelly_ar_documentos` aponta para eles.
Na primeira subida não há nada a copiar (a 26 de setembro só tem os dois
ficheiros de proteção): o plugin cria a pasta sozinho, já fechada, no primeiro
carregamento.

A pasta está fechada ao público por um `.htaccess`, que o Apache do cPanel lê.
A prova, depois da subida, é pedir um ficheiro dela diretamente no browser: tem
de dar **403**. Num servidor nginx o `.htaccess` não conta e é precisa uma regra
na configuração, como a que está no Local (`conf/nginx/includes/restrictions.conf.hbs`):

```nginx
location ^~ /wp-content/uploads/jelly-area-reservada/ { deny all; }
```

---

## 5. Deploys — sempre em dois passos

> **`Deploy HEAD Commit` sozinho não traz nada de novo.** O cPanel publica o
> HEAD do **clone que está no servidor**, não o do GitHub. Sem actualizar esse
> clone primeiro, o deploy repõe fielmente a versão antiga, e o *Commit Date*
> mostra a data do clone em vez da de hoje.
>
> Ordem correcta, tanto na primeira subida como nas seguintes:
> **Update from Remote** → **Deploy HEAD Commit**.

> **Dados de demonstração nunca sobem — nem pelo código.** Os exemplos embutidos
> no plugin da Área Reservada (`inc/admin-exemplo.php`: documentos, a
> utilizadora e as descargas de exemplo) só se ligam onde
> `WP_ENVIRONMENT_TYPE` é `local`, como no `wp-config.php` do Local
> (`JELLY_AR_EXEMPLO`, em `jelly-area-reservada.php`). O `wp-config.php` de
> produção não pode definir `WP_ENVIRONMENT_TYPE` como `local`. Qualquer outro
> dado de demonstração (utilizadores, marcações, acessos, estatísticas) vive
> só na base de dados local, que a exportação da secção 3 deixa de fora.

Para confirmar que versão está no ar, ver o código-fonte do site: a folha de
estilos do tema traz a versão no endereço, `style.css?ver=0.21.10`. Outro sinal,
mais visível: nomes de shortcode em texto cru na página (`[apit_equipa]`,
`[apit_contactos]`) significam que o tema no servidor é anterior ao commit que
os criou.

Depois do primeiro arranque, cada actualização é:

```bash
git add -A
git commit -m "vX.Y.Z Descrição"
git push
```

E no cPanel: **Git™ Version Control > Manage > Pull or Deploy > Update from
Remote**, depois **Deploy HEAD Commit**.

Por SSH, sem passar pela interface:

```bash
cd ~/repositories/APIT && git pull && /usr/local/cpanel/scripts/cpanel_deploy
```

---

## Notas

- A pasta do tema é reconstruída de raiz em cada deploy, para que ficheiros
  apagados no git também desapareçam do servidor.
- Se o site local mudar de conteúdo (páginas, notícias) esse conteúdo **não**
  viaja no git. Repetir o passo 3 substituiria o conteúdo do servidor — mas
  nunca a Área Reservada, os eventos do calendário, que vivem nela, nem os
  utilizadores (ver 3.2).
- O `wp-config.php` no servidor está com permissões **0666** (escrita para
  todos). Deve ser `0644`, ou `0600` se o PHP correr como o dono da conta:
  `chmod 644 ~/public_html/apit/wp-config.php`
- A conta tem `~/.wp-cli`, pelo que o WP-CLI está provavelmente instalado —
  confirmar com `wp --info`.
- Confirmar a versão de PHP do cPanel em **MultiPHP Manager**: o site local corre
  em PHP 7.4 pelo Local, mas o tema não usa nada específico dessa versão.
