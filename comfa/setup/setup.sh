#!/usr/bin/env bash
# Install the Comfa child theme and demo content.
#
# Local Docker stack (~/wp-local):   bash comfa/setup/setup.sh
# Any host with WP-CLI:              WP_CLI_CMD='wp --path=/var/www/html' WP_PATH=/var/www/html bash comfa/setup/setup.sh
#
# Flatsome (licensed parent theme) and WooCommerce must already be installed.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONTAINER="${WP_CONTAINER:-wp-local-wordpress-1}"

if [[ -n "${WP_CLI_CMD:-}" ]]; then
	if [[ -z "${WP_PATH:-}" || ! -d "$WP_PATH/wp-content" ]]; then
		echo "Set WP_PATH to the WordPress root when using WP_CLI_CMD." >&2
		exit 1
	fi
	read -r -a WP_CLI_ARGS <<< "$WP_CLI_CMD"
	wp_cli() { "${WP_CLI_ARGS[@]}" "$@"; }
	copy_to_wp() { mkdir -p "$2" && cp -a "$1"/. "$2/"; }
else
	WP_CLI="${WP_LOCAL_DIR:-$HOME/wp-local}/wp.sh"
	if [[ ! -x "$WP_CLI" ]] || ! docker inspect "$CONTAINER" >/dev/null 2>&1; then
		echo "Local stack not found. Set WP_CLI_CMD and WP_PATH to install on another host." >&2
		exit 1
	fi
	wp_cli() { "$WP_CLI" "$@"; }
	copy_to_wp() {
		docker exec "$CONTAINER" mkdir -p "$2"
		docker cp "$1"/. "$CONTAINER:$2/"
		docker exec "$CONTAINER" chown -R www-data:www-data "$2"
	}
fi

wp_cli theme is-installed flatsome || { echo "Install the Flatsome parent theme first." >&2; exit 1; }
wp_cli plugin is-active woocommerce || { echo "Install and activate WooCommerce first." >&2; exit 1; }

# Sites can move the themes and uploads folders, so ask WordPress where they are.
THEMES_DIR="$(wp_cli eval 'echo get_theme_root();')"
DEMO_DIR="$(wp_cli eval 'echo wp_upload_dir()["basedir"];')/comfa-demo"

copy_to_wp "$ROOT_DIR/child-theme/comfa-child" "$THEMES_DIR/comfa-child"
copy_to_wp "$ROOT_DIR/demo-images" "$DEMO_DIR"
copy_to_wp "$ROOT_DIR/ux-builder" "$DEMO_DIR/ux-builder"
SEED_TMP="$(mktemp -d)"
cp "$ROOT_DIR/setup/seed.php" "$ROOT_DIR/setup/check-demo.php" "$ROOT_DIR/setup/catalog.json" "$SEED_TMP/"
copy_to_wp "$SEED_TMP" "$DEMO_DIR/seed"
rm -rf "$SEED_TMP"

wp_cli theme activate comfa-child
# Without plugins: WP-Optimize ends the command from its permalink hook, before the option is saved.
wp_cli --skip-plugins --skip-themes eval "update_option( 'permalink_structure', '/%postname%/' );"
[[ "$(wp_cli option get permalink_structure)" == "/%postname%/" ]] || { echo "Could not switch permalinks to /%postname%/." >&2; exit 1; }
wp_cli eval-file "$DEMO_DIR/seed/seed.php"
wp_cli rewrite flush
wp_cli eval-file "$DEMO_DIR/seed/check-demo.php"
echo "Comfa storefront installed."
