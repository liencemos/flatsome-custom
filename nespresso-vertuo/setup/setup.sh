#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CHILD_DIR="$ROOT_DIR/child-theme/vertuo-child"
CONTAINER="${WP_CONTAINER:-wp-local-wordpress-1}"
FLATSOME_ZIP="${1:-}"
WP_ADMIN="${WP_ADMIN:-admin}"
REFRESH="${VERTUO_REFRESH:-0}"

if [[ -n "${WP_PATH:-}" ]]; then
	WP_ROOT="$(realpath "$WP_PATH")"
	[[ -d "$WP_ROOT/wp-content" ]] || { echo "Invalid WordPress root: $WP_ROOT" >&2; exit 1; }
	read -r -a WP_CLI <<< "${WP_CLI_CMD:-wp}"
	wp_cli() { "${WP_CLI[@]}" --path="$WP_ROOT" "$@"; }
	copy_to_wp() {
		mkdir -p "$WP_ROOT/$2"
		cp -a "$1/." "$WP_ROOT/$2/"
	}
else
	WP_ROOT=/var/www/html
	WP_LOCAL_DIR="${WP_LOCAL_DIR:-$HOME/wp-local}"
	[[ -x "$WP_LOCAL_DIR/wp.sh" ]] || { echo "Set WP_PATH or provide $WP_LOCAL_DIR/wp.sh." >&2; exit 1; }
	wp_cli() { "$WP_LOCAL_DIR/wp.sh" "$@"; }
	copy_to_wp() {
		docker exec "$CONTAINER" mkdir -p "$WP_ROOT/$2"
		docker cp "$1/." "$CONTAINER:$WP_ROOT/$2/"
	}
fi

wp_cli core is-installed

if ! wp_cli theme is-installed flatsome; then
	if [[ -z "$FLATSOME_ZIP" || ! -f "$FLATSOME_ZIP" ]]; then
		echo 'Install the licensed Flatsome parent theme or supply its ZIP.' >&2
		exit 1
	fi
	if [[ -n "${WP_PATH:-}" ]]; then
		wp_cli theme install "$(realpath "$FLATSOME_ZIP")"
	else
		zip_path="$(docker exec "$CONTAINER" mktemp --suffix=.zip)"
		trap 'docker exec "$CONTAINER" rm -f "$zip_path"' EXIT
		docker cp "$FLATSOME_ZIP" "$CONTAINER:$zip_path"
		docker exec "$CONTAINER" chmod a+r "$zip_path"
		wp_cli theme install "$zip_path"
	fi
fi

if ! wp_cli plugin is-installed woocommerce; then
	wp_cli plugin install woocommerce --activate
elif ! wp_cli plugin is-active woocommerce; then
	wp_cli plugin activate woocommerce
fi

if [[ "$(wp_cli option get woocommerce_currency)" != USD ]]; then
	echo 'The catalog prices are in USD. Set the demo store currency to USD before import.' >&2
	exit 1
fi

copy_to_wp "$CHILD_DIR" wp-content/themes/vertuo-child
if [[ -z "${WP_PATH:-}" ]]; then
	docker exec "$CONTAINER" chown -R www-data:www-data "$WP_ROOT/wp-content/themes/vertuo-child"
fi

if [[ -n "${WP_PATH:-}" ]]; then
	wp_cli eval-file "$ROOT_DIR/setup/import-products.php" "$REFRESH" --user="$WP_ADMIN"
else
	stage="$(docker exec "$CONTAINER" mktemp -d)"
	trap 'docker exec "$CONTAINER" rm -rf "$stage"; if [[ -n "${zip_path:-}" ]]; then docker exec "$CONTAINER" rm -f "$zip_path"; fi' EXIT
	docker exec "$CONTAINER" chmod a+rx "$stage"
	docker exec "$CONTAINER" mkdir "$stage/setup" "$stage/data"
	docker cp "$ROOT_DIR/setup/import-products.php" "$CONTAINER:$stage/setup/import-products.php"
	docker cp "$ROOT_DIR/data/woocommerce-products.csv" "$CONTAINER:$stage/data/woocommerce-products.csv"
	docker cp "$ROOT_DIR/data/catalog.json" "$CONTAINER:$stage/data/catalog.json"
	wp_cli eval-file "$stage/setup/import-products.php" "$REFRESH" --user="$WP_ADMIN"
fi

wp_cli theme activate vertuo-child
page_id="$(wp_cli post list --post_type=page --post_status=publish --name=vertuo-coffee-pods --format=ids)"
if [[ -z "$page_id" ]]; then
	page_id="$(wp_cli post create --post_type=page --post_status=publish --post_title='Vertuo Coffee Pods' --post_name=vertuo-coffee-pods --page_template=page-blank.php --porcelain - < "$ROOT_DIR/ux-builder/catalog.txt")"
elif [[ "$REFRESH" == 1 ]] || [[ "$(wp_cli post get "$page_id" --field=post_title)" == 'Cà phê Vertuo' && "$(wp_cli eval "echo hash( 'sha256', rtrim( get_post_field( 'post_content', (int) '$page_id', 'raw' ), \"\\r\\n\" ) );")" == 'e782272439ea19d9058332cdd8b061138b1c2852fcade4ff5d8005fbbcdb8efc' ]]; then
	wp_cli post update "$page_id" --post_title='Vertuo Coffee Pods' --page_template=page-blank.php --post_content="$(< "$ROOT_DIR/ux-builder/catalog.txt")"
fi

wp_cli eval "echo get_permalink( (int) '$page_id' ) . PHP_EOL;"
