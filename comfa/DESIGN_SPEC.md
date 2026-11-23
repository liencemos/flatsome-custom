# Comfa design spec

Comfa is a Flatsome + WooCommerce storefront for printed tees, caps, hoodies and socks. Its layout and shopping UX follow the structure of bombas.com (captured November 2026, desktop 1440 px and mobile 390 px). Brand name, logo, photography, product data and copy are original demo content; nothing from Bombas is bundled.

## Tokens

| Token | Value | Used for |
| --- | --- | --- |
| Page | `#fdfcf8` | body and header background |
| Ink | `#030302` | text, dark buttons (`#1b1b1b`) |
| Navy | `#213858` top bar, `#1a2235` hero, `#1f3557` Add to Bag and checkout |
| Cream | `#ecebdc` | collection banner, mobile chips, story band |
| Card | `#f3f3f1` | product image background |
| Soft | `#f8f8f4` | Quick View button, size buttons, info card |
| Yellow / Green | `#e9b94d` / `#b2c693` | giving bands |
| Accents | blue `#3860be` savings and badges, red `#d4240d` promo line, orange `#f04e23` wavy underline, sky `#a0c7ff` sign-up button |
| Footer | `#1b1b1b`, tag chips `#312b2b` |
| Radius | 12 px tiles and buttons, 8 px cards and chips |

## Type

| Role | Font | Notes |
| --- | --- | --- |
| Display | Bowlby One | uppercase, line-height 0.82–0.92; hero 96 px, statement 64 px, tiles 35 px |
| Body / UI | Outfit 400, 700, 800 | nav 14 px / 800, product name 16 px / 700 |
| Accent serif | Fraunces 300 | section titles 32–36 px, product title 34 px, giving lines |

Outfit and Fraunces are set in Flatsome > Theme Options > Typography; Bowlby One is loaded by the child theme.

## Page structure

**Header:** two-line navy promo bar, cream sticky header with wordmark left, centred navigation, Login / search / bag right. T-Shirts and Caps open full-width mega menus (UX Blocks). Mobile: menu and search left, wordmark centre, account and bag right.

**Home (UX Builder page):** category chips → navy split hero → four style tiles → "One Bought = One Given" statement → two product rails (intro column + four cards) → six shape tiles → yellow giving band with blob counter → green "Comfort Beyond Tees" tiles → dark footer with sign-up, link columns, guarantee and tag chips.

**Product card:** grey 3:4 image, badge pill, Flatsome colour swatches (swap the image on click), name, price with struck regular price and "10% Pack Savings", "Unisex · Midweight" line, full-width Quick View.

**Collection page:** cream banner with breadcrumb, serif title and sub-collection tiles; left filter sidebar (weight, size, colour, pack, price); toolbar with item count and sorting; three-column grid (two on mobile, filters in Flatsome's off-canvas drawer).

**Product page:** info column left, two-up image grid right (slider on mobile). Order: breadcrumb, serif title, price line, first-order promo, pack segmented control, colour circles, two-line size buttons with Size Guide lightbox, navy Add to Bag, "N Bought = N Given", info card (rating, shipping, highlight, details). Below: editable story band, giving band, reviews with score header, related products.

**Bag and checkout:** off-canvas mini bag and cart page show free-shipping progress and the giving count; classic WooCommerce cart and checkout styled to match.
