#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 || ! -f "$1" ]]; then
	echo "Usage: $0 /path/to/flatsome.zip" >&2
	exit 2
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SETUP_DIR="$ROOT_DIR/setup"
CHILD_DIR="$ROOT_DIR/child-theme/blueland-child"
PLUGIN_DIR="$(dirname "$ROOT_DIR")/plugins/modern-checkout-for-woocommerce"
FLATSOME_ZIP="$(realpath "$1")"
WP_DIR="${WP_LOCAL_DIR:-$HOME/wp-local}"
WP_CLI="$WP_DIR/wp.sh"
CONTAINER="wp-local-wordpress-1"

if [[ -n "${WP_CLI_CMD:-}" ]]; then
	read -r -a WP_CLI_ARGS <<< "$WP_CLI_CMD"
	if [[ ${#WP_CLI_ARGS[@]} -eq 0 || -z "${WP_PATH:-}" || ! -d "$WP_PATH" ]]; then
		echo "Set WP_CLI_CMD and an existing WP_PATH for filesystem installation." >&2
		exit 1
	fi
	wp_cli() { "${WP_CLI_ARGS[@]}" "$@"; }
	copy_to_wp() {
		local source="$1" destination="$2"
		mkdir -p "$WP_PATH/$destination"
		cp -a "$source"/. "$WP_PATH/$destination/"
	}
	SEED_PATH="$WP_PATH/wp-content/uploads/blueland-seed/seed.php"
else
	if [[ ! -x "$WP_CLI" ]]; then
		echo "WP-CLI wrapper not found or not executable: $WP_CLI" >&2
		exit 1
	fi
	if ! docker inspect "$CONTAINER" >/dev/null 2>&1; then
		echo "WordPress container is not running: $CONTAINER" >&2
		exit 1
	fi
	wp_cli() { "$WP_CLI" "$@"; }
	copy_to_wp() {
		local source="$1" destination="$2"
		docker cp "$source"/. "$CONTAINER:/var/www/html/$destination/"
	}
	SEED_PATH="/var/www/html/wp-content/uploads/blueland-seed/seed.php"
	docker exec "$CONTAINER" mkdir -p /var/www/html/wp-content/uploads/blueland-seed /var/www/html/wp-content/uploads/ux-builder/blocks /var/www/html/wp-content/themes/blueland-child /var/www/html/wp-content/plugins/modern-checkout-for-woocommerce /var/www/html/wp-content/uploads/blueland
fi

python3 "$SETUP_DIR/make-placeholders.py"

if ! wp_cli core is-installed >/dev/null 2>&1; then
	echo "WordPress core is not installed. Run wp core install before setup.sh." >&2
	exit 1
fi
if ! wp_cli plugin is-installed woocommerce >/dev/null 2>&1; then
	wp_cli plugin install woocommerce --activate
elif ! wp_cli plugin is-active woocommerce >/dev/null 2>&1; then
	wp_cli plugin activate woocommerce
fi

if ! wp_cli theme is-installed flatsome >/dev/null 2>&1; then
	FLATSOME_DIR="$WP_DIR/flatsome-install"
	mkdir -p "$FLATSOME_DIR"
	unzip -oq "$FLATSOME_ZIP" -d "$FLATSOME_DIR"
	if [[ ! -f "$FLATSOME_DIR/flatsome/style.css" ]]; then
		echo "Flatsome theme files were not found at the root of the supplied ZIP." >&2
		exit 1
	fi
	if [[ -n "${WP_CLI_CMD:-}" ]]; then
		mkdir -p "$WP_PATH/wp-content/themes/flatsome"
		cp -a "$FLATSOME_DIR/flatsome"/. "$WP_PATH/wp-content/themes/flatsome/"
	else
		docker cp "$FLATSOME_DIR/flatsome" "$CONTAINER:/var/www/html/wp-content/themes/flatsome"
		docker exec "$CONTAINER" chown -R www-data:www-data /var/www/html/wp-content/themes/flatsome
	fi
	wp_cli theme activate flatsome
fi

copy_to_wp "$CHILD_DIR" wp-content/themes/blueland-child
copy_to_wp "$PLUGIN_DIR" wp-content/plugins/modern-checkout-for-woocommerce
copy_to_wp "$CHILD_DIR/assets/images" wp-content/uploads/blueland
if [[ -n "${WP_CLI_CMD:-}" ]]; then
	mkdir -p "$(dirname "$SEED_PATH")"
	cp "$SETUP_DIR/seed.php" "$SEED_PATH"
else
	docker cp "$SETUP_DIR/seed.php" "$CONTAINER:$SEED_PATH"
fi
copy_to_wp "$ROOT_DIR/ux-builder" wp-content/uploads/ux-builder
if [[ -z "${WP_CLI_CMD:-}" ]]; then
	docker exec "$CONTAINER" chown -R www-data:www-data /var/www/html/wp-content/themes/blueland-child /var/www/html/wp-content/plugins/modern-checkout-for-woocommerce /var/www/html/wp-content/uploads/blueland
fi

if ! wp_cli plugin is-active modern-checkout-for-woocommerce >/dev/null 2>&1; then
	wp_cli plugin activate modern-checkout-for-woocommerce
fi
wp_cli theme activate blueland-child
wp_cli language core install en_US --activate
wp_cli eval-file "$SEED_PATH"
wp_cli media regenerate --yes
# Set via PHP so Git Bash on Windows does not rewrite the leading "/" into a filesystem path.
wp_cli eval "update_option( 'permalink_structure', '/%postname%/' ); flush_rewrite_rules( true );"
wp_cli rewrite flush --hard
wp_cli cache flush >/dev/null

echo "Pureland storefront setup complete."
