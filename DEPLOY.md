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
| `wp-content/uploads` | Não | conteúdo, não código (5,5 MB) |
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
activo. O `hello-elementor` e o `elementor` **não estão instalados** (ambos
devolvem 404), pelo que o tema filho ainda não pode arrancar.

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

No Local, botão direito no site *apit* > **Open site shell**, e depois:

```bash
cd "C:\Users\faust\Local Sites\apit"

# troca os URLs e escreve o ficheiro, sem tocar na base de dados local.
# --precise porque os dados do Elementor estão serializados.
wp search-replace "http://apit.local" "https://dev.jellycode.agency/apit" \
    --all-tables --precise --export=bd-sem-cabecalho.sql

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
} > apit-bd-servidor.sql

rm bd-sem-cabecalho.sql

# confirmar antes de subir
grep -c "apit.local" apit-bd-servidor.sql     # 0
grep -c "CREATE TABLE" apit-bd-servidor.sql   # 13
```

Referência da exportação verificada a 3 de setembro de 2026: 13 tabelas,
747 linhas, 344 KB, 79 endereços do servidor e nenhum local. Foi importada numa
base de dados de teste e reproduziu as 747 linhas tabela a tabela. Um ficheiro
muito menor é sinal de exportação incompleta.

Os `apit-bd.sql` e `apit-bd-para-servidor.sql` em `Local Sites/apit/` são de
1 de setembro e **estão obsoletos** — não têm a página Sobre a APIT, a equipa,
os órgãos sociais nem as categorias de eventos. Não servem para subir.

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
