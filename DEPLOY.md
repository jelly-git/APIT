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
| Plugin `advanced-custom-fields-pro` | 6.8.9 |

Por SSH, se houver WP-CLI no servidor:

```bash
cd ~/public_html/apit
wp theme install hello-elementor --version=3.5.1
wp plugin install elementor --version=4.2.4 --activate
```

Sem WP-CLI, instalar pelo wp-admin em Aparência > Temas e Plugins > Adicionar.

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

### 3.0 As ligações internas têm de ser absolutas

O servidor serve o site de uma subpasta — `dev.jellycode.agency/apit` — e é isso
que torna as ligações relativas à raiz uma armadilha. `/contactos/` funciona
perfeitamente no local, que está na raiz, e no servidor resolve para
`dev.jellycode.agency/contactos/`: 404, ou pior, outro projecto da mesma conta.
A migalha `href="/"` das sete páginas apontava exactamente para aí.

Guardar sempre o endereço completo (`http://apit.local/...`), que o
`search-replace` da exportação depois converte. Para confirmar antes de exportar,
o teste é o HTML servido e não a base de dados:

```bash
for p in "" associados/ calendario/ documentos/ noticias/ internacionalizacao/ \
         sobre-apit/ contactos/ estatutos/ associados/todos-os-associados/; do
  n=$(curl -s "http://apit.local/$p" | grep -oE 'href="/[^"]*"' | grep -v '^href="//' | sort -u | wc -l)
  printf "  %-34s %s\n" "${p:-home}" "$n"
done
```

Todas as linhas têm de dar `0`. A 23 de setembro eram 51 ligações em três
formatos diferentes, e cada formato precisou da sua passagem: `link.url` nos
botões do Elementor, atributos dentro dos shortcodes (`url=`, mas também `acao=`
no calendário) e `href=` no HTML dos editores de texto — este último é o que
guarda as migalhas, e foi o que sobrou depois das duas primeiras passagens.

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

### 3.2 Exportar

```bash
cd "C:\Users\faust\Local Sites\apit"

# troca os URLs e escreve o ficheiro, sem tocar na base de dados local.
# --precise porque os dados do Elementor estão serializados.
wp search-replace "http://apit.local" "https://dev.jellycode.agency/apit" \
    --all-tables --precise --export=bd-sem-cabecalho.sql

# O comando acima NÃO apanha os endereços guardados dentro do _elementor_data.
# Esse campo é JSON, o JSON escapa as barras e o dump escapa depois as barras
# invertidas, pelo que o ficheiro leva `http:\\/\\/apit.local\\/...`. A 22 de
# setembro foram assim os dois botões da ficha de inscrição, que teriam ido para
# produção a apontar para a máquina local. Feito em PHP e não em sed: o padrão é
# feito de barras invertidas e passá-lo por uma shell intacto já falhou três
# vezes neste projecto, num caso parecido no acf_site_health.
php -r '$f="bd-sem-cabecalho.sql"; $b=chr(92).chr(92)."/";
  $s=file_get_contents($f);
  $s=str_replace("http:".$b.$b."apit.local", "https:".$b.$b."dev.jellycode.agency".$b."apit", $s);
  file_put_contents($f,$s);
  echo substr_count($s,"apit.local")." apit.local que restam\n";'

# as tabelas do WordPress declaram datas 0000-00-00 por omissão, que um MySQL
# em modo estrito recusa com "Invalid default value for 'comment_date'".
# O ficheiro exportado não traz a instrução que desliga esse modo.
{
  echo "SET @OLD_SQL_MODE = @@SQL_MODE;"
  echo "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';"
  echo "SET NAMES utf8mb4;"
  echo "SET FOREIGN_KEY_CHECKS = 0;"
  cat bd-sem-cabecalho.sql
  echo "SET FOREIGN_KEY_CHECKS = 1;"
  echo "SET SQL_MODE = @OLD_SQL_MODE;"
} > apit-bd-para-servidor.sql

rm bd-sem-cabecalho.sql

# confirmar antes de subir
grep -c "apit.local" apit-bd-para-servidor.sql     # 0
grep -c "autosave-v1" apit-bd-para-servidor.sql    # 0
grep -c "CREATE TABLE" apit-bd-para-servidor.sql   # 13
grep -c "'_elementor_css'" apit-bd-para-servidor.sql  # 0

# arquivar a cópia versionada, com a versão lida do próprio tema
VERSAO=$(sed -n 's/^Version: //p' app/public/wp-content/themes/hello-elementor-child/style.css | tr -d '\r')
cp apit-bd-para-servidor.sql "bd/apit-bd-v$VERSAO-$(date +%F).sql"
```

Referência da exportação verificada a 22 de setembro de 2026: 13 tabelas,
1415 linhas, 510 KB, 228 endereços do servidor e nenhum local. Foi importada
numa base de dados de teste e reproduziu as 1415 linhas tabela a tabela, sem uma
única opção ou post a diferir do local. Um ficheiro muito menor é sinal de
exportação incompleta; um muito maior é sinal de que os caches do WordPress.org
voltaram — em 22 de setembro eram 894 KB dos 1,47 MB iniciais.

A prova que fecha a exportação é decodificar o `_elementor_data` de cada página
já dentro da base de dados importada. Se a passagem pelos URLs escapados tivesse
partido uma string, é aqui que se vê. O `#33` dá erro de JSON porque tem o campo
vazio — está assim também no local, não é da exportação.

### Onde ficam os ficheiros

**`Local Sites/apit/apit-bd-para-servidor.sql` é sempre o ficheiro a subir.** O
nome não muda, para que este documento nunca aponte para um `.sql` errado.

O histórico fica em `Local Sites/apit/bd/`, uma exportação por versão do tema,
com o `LEIA-ME.md` dessa pasta a dizer o estado de cada uma. Base de dados e
código sobem em par: os dados do Elementor gravados na base de dados dependem
das classes CSS que o tema dessa versão define.

As exportações antigas que lá estão não servem para subir. As da **v0.17.0** não
têm a página Sobre a APIT, a equipa, os órgãos sociais nem as categorias de
eventos, e a que tem os URLs trocados não traz o cabeçalho `SQL_MODE`; a da
**v0.22.10** não tem a Internacionalização, as Notícias, os documentos nem a
banda dos Associados.

Nada disto entra no git: os ficheiros contêm a tabela `wp_users`, e com ela o
*hash* da palavra-passe do administrador.

> **Substitui tudo** o que estiver em `agencydevjellyc_apit`, incluindo os
> utilizadores. Depois da importação o acesso ao wp-admin passa a ser o do site
> local: utilizador `Jelly-APIT`, com a palavra-passe definida no Local — não a
> que usa hoje no servidor.

1. phpMyAdmin > base de dados `agencydevjellyc_apit`
2. **Importar** > carregar `apit-bd-para-servidor.sql` > **Executar**

O ficheiro traz `DROP TABLE IF EXISTS` em cada tabela, pelo que não é preciso
esvaziar a base de dados antes.

Em alternativa, exportar com os URLs locais (`wp db export`) e trocá-los já no
servidor, depois de importar. Dá o mesmo resultado, mas deixa o site com os
endereços errados no intervalo entre a importação e a troca:

```bash
cd ~/public_html/apit
wp search-replace http://apit.local https://dev.jellycode.agency/apit --all-tables --precise
```

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

---

## 5. Deploys — sempre em dois passos

> **`Deploy HEAD Commit` sozinho não traz nada de novo.** O cPanel publica o
> HEAD do **clone que está no servidor**, não o do GitHub. Sem actualizar esse
> clone primeiro, o deploy repõe fielmente a versão antiga, e o *Commit Date*
> mostra a data do clone em vez da de hoje.
>
> Ordem correcta, tanto na primeira subida como nas seguintes:
> **Update from Remote** → **Deploy HEAD Commit**.

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
- Se o site local mudar de conteúdo (páginas, eventos, notícias) esse conteúdo
  **não** viaja no git. Repetir o passo 3 substituiria o que estiver no servidor.
- O `wp-config.php` no servidor está com permissões **0666** (escrita para
  todos). Deve ser `0644`, ou `0600` se o PHP correr como o dono da conta:
  `chmod 644 ~/public_html/apit/wp-config.php`
- A conta tem `~/.wp-cli`, pelo que o WP-CLI está provavelmente instalado —
  confirmar com `wp --info`.
- Confirmar a versão de PHP do cPanel em **MultiPHP Manager**: o site local corre
  em PHP 7.4 pelo Local, mas o tema não usa nada específico dessa versão.
