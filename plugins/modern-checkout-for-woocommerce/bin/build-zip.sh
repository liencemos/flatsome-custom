#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
	echo "Usage: $0 /path/to/modern-checkout-for-woocommerce.zip" >&2
	exit 2
fi

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DESTINATION="$(realpath -m "$1")"
mkdir -p "$(dirname "$DESTINATION")"
rm -f "$DESTINATION"
cd "$(dirname "$PLUGIN_DIR")"
zip -qr "$DESTINATION" "$(basename "$PLUGIN_DIR")" \
	-x '*/.git/*' '*/.DS_Store' '*/vendor/*' '*/node_modules/*'
printf 'Built %s\n' "$DESTINATION"
