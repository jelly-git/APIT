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

## 1. Pré-requisitos no servidor

O tema filho não funciona sozinho. Instalar antes do primeiro deploy:

| Componente | Versão local |
|---|---|
| WordPress core | 7.1 |
| Tema `hello-elementor` (pai) | 3.5.1 |
| Plugin `elementor` | 4.2.4 |

Por SSH, se houver WP-CLI no servidor:

```bash
cd ~/public_html/apit
wp theme install hello-elementor --version=3.5.1
wp plugin install elementor --version=4.2.4 --activate
```

Sem WP-CLI, instalar pelo wp-admin em Aparência > Temas e Plugins > Adicionar.

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

## 3. Base de dados (uma vez)

O ficheiro exportado está em `Local Sites/apit/apit-bd.sql` (1,4 MB).

**Atenção:** isto substitui tudo o que estiver na base de dados de destino.

1. cPanel > **MySQL Databases** — tomar nota do nome da base de dados, do
   utilizador e da palavra-passe que o `wp-config.php` do servidor já usa.
2. cPanel > **phpMyAdmin** > escolher essa base de dados > **Importar** >
   carregar `apit-bd.sql`.

Depois é obrigatório trocar os URLs: o WordPress e sobretudo o Elementor
guardam endereços absolutos, e sem esta troca a Home não renderiza.

Por SSH, substituindo `apitv.com` pelo domínio real:

```bash
cd ~/public_html/apit
wp search-replace 'http://apit.local' 'https://apitv.com' --all-tables --precise --report-changed-only
wp cache flush
wp elementor flush-css
```

O `--precise` é necessário porque os dados do Elementor estão serializados em
JSON dentro de `wp_postmeta`; sem ele os URLs lá dentro não são substituídos.

Sem SSH, instalar o plugin **Better Search Replace** e correr a mesma troca com
"Run as dry run" desligado, em todas as tabelas.

---

## 4. Uploads (uma vez)

Copiar `wp-content/uploads/` do site local para o servidor, por FTP/SFTP ou pelo
File Manager. São os ficheiros da biblioteca de multimédia — os logótipos vivem
no tema e vão pelo git, mas as imagens destacadas das notícias não.

---

## 5. Deploys seguintes

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
