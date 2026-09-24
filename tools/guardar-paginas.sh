#!/bin/bash
#
# Guarda o HTML servido de cada página publicada em tools/paginas/, que é o que
# a prova-servidor.php depois volta a passar pelo filtro das ligações.
#
#   bash tools/guardar-paginas.sh && wp eval-file tools/prova-servidor.php
#
cd "$(dirname "$0")/.." || exit 1
mkdir -p tools/paginas
rm -f tools/paginas/*.html

wp post list --post_type=page,post --post_status=publish --field=url 2>/dev/null \
	| tr -d '\r' \
	| while read -r u; do
		nome=$(echo "$u" | sed 's#https\?://[^/]*/##; s#/$##; s#/#_#g')
		curl -s "$u" > "tools/paginas/${nome:-home}.html"
	done

echo "$(ls tools/paginas/*.html 2>/dev/null | wc -l) páginas guardadas"
