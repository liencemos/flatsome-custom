# Meredithzone: Shoptimizer + Elementor + WooCommerce storefront

A beauty storefront modelled on the layout and motion of glossier.com, built as a
Shoptimizer child theme. It ships its own brand name (Meredithzone), generated placeholder
artwork and free fonts (DM Sans, DM Mono), so no third-party logo, photography or
licensed typeface is included.

## Requirements

- WordPress 6.4+, PHP 7.4+
- Shoptimizer 2.10 (parent theme, not included)
- WooCommerce and Elementor (free)

## Contents

| Path | Purpose |
| --- | --- |
| `child-theme/dewier-child/` | Child theme: header, footer, product card, product page, bag drawer, Elementor widgets, CSS and JS |
| `elementor/home.json` | Home page template for Elementor > Templates > Import |
| `elementor/help.json` | Help page template (search hero and FAQ) |
| `setup/catalog.json` | 35 demo products and their categories |
| `setup/seed.php` | Creates categories, products, variations, reviews and store settings |
| `setup/seed-site.php` | Creates menus, content pages and the Elementor home and help pages |
| `setup/rebrand.php` | Renames the visible brand of a site seeded before it was called Meredithzone |
| `setup/make-placeholders.py` | Regenerates the placeholder images (needs Pillow) |

## Install

1. Install and activate Shoptimizer, WooCommerce and Elementor.
2. Copy `child-theme/dewier-child` to `wp-content/themes/` and activate it.
3. Copy `setup/` to the server and run, from that directory:

   ```
   wp eval-file seed.php
   wp eval-file seed-site.php
   ```

Both seed scripts can be run again. Products are matched by SKU and keep their stock
settings, menus are rebuilt, and the existing home and help page layouts are kept unless
you run `wp eval-file seed-site.php reset-home` or `reset-help`. On a site seeded while
the brand was still Dewier, `wp eval-file rebrand.php` renames the site title, products,
categories, menus and Elementor text (`rebrand.php dry-run` lists them first); slugs,
file names and the `dewier-child` folder are kept. `seed.php` adds a demo
shipping zone ($5 flat rate, free from $40) only when the store has no shipping methods
at all.

## Editing

- Home page sections are Elementor widgets in the **Meredithzone** category: Hero, Gift strip,
  Product carousel, Promo cards, Story and Image carousel.
- The help page (`/help/`) uses three more widgets: Help Search, FAQ and Help Contact.
  Questions are rows of the FAQ widget, grouped by their Topic field; the topic list with
  its question counts, the mobile topic menu and the search suggestions are built from
  them. The topic shortcuts under the search box are a list of topic names in the Help
  Search widget; a name that matches no topic on the page is not shown. The bundled
  questions and answers are sample text, to be replaced with the shop's own policies.
- The header and footer live in the child theme because Elementor free has no theme
  builder. Menus are under Appearance > Menus (Primary plus five footer locations).
- Announcement bar messages: filter `dewier_promo_messages`.
- The bag's free-shipping bar reads the minimum amount of the WooCommerce free shipping
  method in the shopper's zone, and is hidden when the zone has none.
- The cart page (`/cart/`) is styled for the `[woocommerce_cart]` shortcode; `seed.php`
  replaces WooCommerce's default Cart block with it. Quantities update the totals
  without an Update button.
- The checkout page (`/checkout/`) is styled for the `[woocommerce_checkout]` shortcode;
  `seed.php` replaces the Checkout block the same way. It is split into Information,
  Shipping (only when the cart needs shipping) and Payment steps beside an order summary,
  which folds behind a "Show order summary" bar on phones. Fields, shipping rates,
  coupons and payment gateways are still WooCommerce's own, so gateway plugins render
  inside the Payment step.
- CommerceKit (optional, ships with Shoptimizer): `inc/commercekit.php` wires Ajax Search,
  Wishlist, Waitlist, Stock Meter and Sticky Add to Cart into the Meredithzone header, cards and
  product page; `seed-site.php` switches those modules on and creates the `/wishlist/` page
  when the plugin is active. Each one can be turned off again under CommerceKit in wp-admin.
  Its free-shipping bar, swatches, badges and gallery stay off, as Meredithzone has its own.
- Email popup (`inc/newsletter.php`): shows once, 8 seconds in or when the pointer leaves
  the window, never on Cart, Checkout or My account; any `#newsletter` link opens it too.
  Each new email gets a single-use, non-stacking 10% WooCommerce coupon (`MZ10-XXXXXX`),
  shown in the popup and emailed. Emails are listed under WooCommerce > Subscribers with
  an Export CSV link. Filters: `dewier_newsletter_discount`, `dewier_newsletter_popup`.
- Review photos (`inc/reviews.php`): the review form takes up to 3 JPG, PNG or WebP photos
  of 5 MB each; the server checks the real file type. Photos show only once the review is
  approved, and are deleted with it. The product page adds a rating summary with star
  bars and a customer photo strip; photos open in a lightbox.
- Recently viewed (`inc/recent.php`): the browser keeps the last 9 product IDs in
  localStorage, so cached pages stay shared. The strip shows on product pages and Cart.
- Quiz (`/quiz/`, widget Routine Quiz or shortcode `[dewier_quiz]`): four questions,
  then up to four products with Add to bag. Shade products open on the shade nearest the
  chosen skin tone. Questions and product rules: filters `dewier_quiz_questions` and
  `dewier_quiz_rules` in `inc/quiz.php`. `seed-site.php` creates the page.
- Product badges: one per line in the `_dewier_badges` product meta, as `promo:Text`
  (blue) or `value:Text` (pink). Card subtitle: `_dewier_subtitle`.

## Button motion

Buttons use four background layers as borders. At rest only the bottom and right edges
are drawn as solid 1px lines. On hover all four edges become 5px dashes that travel
around the button (`dw-march`, 1.5s linear, infinite) and stop when the pointer leaves.
Carousel arrows, Add to bag and the bag drawer buttons share the same rule. The motion
is switched off under `prefers-reduced-motion`.
