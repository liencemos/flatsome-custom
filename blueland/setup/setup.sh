#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 || ! -f "$1" ]]; then
	echo "Usage: $0 /path/to/flatsome.zip" >&2
	exit 2
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SETUP_DIR="$ROOT_DIR/setup"
CHILD_DIR="$ROOT_DIR/child-theme/blueland-child"
FLATSOME_ZIP="$(realpath "$1")"
WP_DIR="${WP_LOCAL_DIR:-$HOME/wp-local}"
WP_CLI="$WP_DIR/wp.sh"
CONTAINER="wp-local-wordpress-1"

if [[ ! -x "$WP_CLI" ]]; then
	echo "WP-CLI wrapper not found or not executable: $WP_CLI" >&2
	exit 1
fi
if ! docker inspect "$CONTAINER" >/dev/null 2>&1; then
	echo "WordPress container is not running: $CONTAINER" >&2
	exit 1
fi

python3 "$SETUP_DIR/make-placeholders.py"
docker exec "$CONTAINER" mkdir -p /var/www/html/wp-content/uploads/blueland-seed /var/www/html/wp-content/uploads/ux-builder/blocks /var/www/html/wp-content/themes/blueland-child /var/www/html/wp-content/uploads/blueland

if ! "$WP_CLI" core is-installed >/dev/null 2>&1; then
	echo "WordPress core is not installed. Run wp core install before setup.sh." >&2
	exit 1
fi
if ! "$WP_CLI" plugin is-installed woocommerce >/dev/null 2>&1; then
	"$WP_CLI" plugin install woocommerce --activate
elif ! "$WP_CLI" plugin is-active woocommerce >/dev/null 2>&1; then
	"$WP_CLI" plugin activate woocommerce
fi

if ! "$WP_CLI" theme is-installed flatsome >/dev/null 2>&1; then
	FLATSOME_DIR="$WP_DIR/flatsome-install"
	mkdir -p "$FLATSOME_DIR"
	unzip -oq "$FLATSOME_ZIP" -d "$FLATSOME_DIR"
	if [[ ! -f "$FLATSOME_DIR/flatsome/style.css" ]]; then
		echo "Flatsome theme files were not found at the root of the supplied ZIP." >&2
		exit 1
	fi
	docker cp "$FLATSOME_DIR/flatsome" "$CONTAINER:/var/www/html/wp-content/themes/flatsome"
	docker exec "$CONTAINER" chown -R www-data:www-data /var/www/html/wp-content/themes/flatsome
	"$WP_CLI" theme activate flatsome
fi

docker cp "$CHILD_DIR/." "$CONTAINER:/var/www/html/wp-content/themes/blueland-child/"
docker cp "$CHILD_DIR/assets/images/." "$CONTAINER:/var/www/html/wp-content/uploads/blueland/"
docker cp "$SETUP_DIR/seed.php" "$CONTAINER:/var/www/html/wp-content/uploads/blueland-seed/seed.php"
docker cp "$ROOT_DIR/ux-builder/." "$CONTAINER:/var/www/html/wp-content/uploads/ux-builder/"
docker exec "$CONTAINER" chown -R www-data:www-data /var/www/html/wp-content/themes/blueland-child /var/www/html/wp-content/uploads/blueland

"$WP_CLI" theme activate blueland-child
"$WP_CLI" language core install en_US --activate
"$WP_CLI" eval-file /var/www/html/wp-content/uploads/blueland-seed/seed.php
"$WP_CLI" option update permalink_structure '/%postname%/'
"$WP_CLI" rewrite flush --hard
"$WP_CLI" cache flush >/dev/null

echo "Pureland storefront setup complete."
