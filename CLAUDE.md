# Convenções deste repositório

## Mensagens de commit

**O assunto começa sempre pela versão do tema**, mesmo quando o commit não a
altera:

```
v0.32.1 Assunto em minúsculas depois da versão
```

A versão é a que fica em vigor **depois** deste commit: a nova quando o commit
muda código, a que já lá estava quando muda só documentação ou dados. Ler o
histórico é ver a que estado do tema cada linha pertence — sem isso, um commit
de documentação fica sem lugar na cronologia.

Onde a versão vive, e os três sítios têm de concordar:

| Ficheiro | Campo |
|---|---|
| `wp-content/themes/hello-elementor-child/style.css` | `Version:` |
| `wp-content/themes/hello-elementor-child/functions.php` | `APIT_CHILD_VERSION` |
| `CHANGELOG.md` | cabeçalho da versão |

**Confirmar sempre a versão depois de a mudar.** Um `sed` que não encontra o
padrão não falha — devolve sucesso e não escreve nada. Já aconteceu duas vezes
neste projecto, e as duas por outra sessão ter avançado a versão entretanto.
O mesmo vale para as entradas do `CHANGELOG.md`: houve oito commits seguidos sem
entrada nenhuma porque as âncoras do `sed` não casavam e ninguém foi ver.

Um prefixo a seguir à versão diz do que trata o commit quando não é código:

```
v0.32.1 DEPLOY: excluir a cache do Elementor dos uploads
```

O corpo explica **porquê**, não o quê — o diff já diz o quê. Em português, sem
acentos no assunto (o histórico é lido em terminais que os partem), acentos
normais no corpo.

## O que entra no git

Só código próprio: o tema filho e a documentação. Ficam de fora o núcleo do
WordPress, os plugins, `wp-content/uploads` e `wp-config.php`.

**As exportações da base de dados nunca entram** — contêm a tabela `wp_users` e
com ela o *hash* da palavra-passe do administrador. Vivem em
`Local Sites/apit/bd/`, com o `LEIA-ME.md` dessa pasta a registar cada uma.

## Outra sessão pode estar a editar este repositório

Verificar `git status` antes de preparar um commit e adicionar ao índice apenas
os ficheiros do trabalho em mãos, nunca `git add -A`.

## Subir para produção

O processo está no `DEPLOY.md` e é para seguir tal como está escrito, incluindo
a limpeza antes da exportação. Código e base de dados sobem em par: os dados do
Elementor dependem das classes CSS da versão do tema que os acompanha.
