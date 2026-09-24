#!/bin/bash
#
# Guarda o HTML servido de cada página publicada em tools/paginas/, que é o que
# a prova-servidor.php depois volta a passar pelo filtro das ligações.
#
#   bash tools/guardar-paginas.sh && wp eval-file tools/prova-servidor.php
#
# No Local o `wp` não está no PATH, e a chamada falhava sem se queixar — a prova
# seguinte lia uma pasta vazia e dizia que estava tudo bem. Daí o WP e o
# `set -e`: uma prova que não corre tem de dar erro, não um visto.
#
#   WP="bash caminho/para/wp.sh" bash tools/guardar-paginas.sh
#
set -euo pipefail

cd "$(dirname "$0")/.."

WP=${WP:-wp}

mkdir -p tools/paginas
rm -f tools/paginas/*.html

$WP post list --post_type=page,post --post_status=publish --field=url \
	| tr -d '\r' \
	| while read -r u; do
		[ -n "$u" ] || continue
		nome=$(echo "$u" | sed 's#https\?://[^/]*/##; s#/$##; s#/#_#g')
		curl -sf "$u" > "tools/paginas/${nome:-home}.html"
	done

n=$(ls tools/paginas/*.html 2>/dev/null | wc -l)

if [ "$n" -eq 0 ]; then
	echo "nenhuma página guardada — o WP-CLI não correu ou o site não responde" >&2
	exit 1
fi

echo "$n páginas guardadas"
