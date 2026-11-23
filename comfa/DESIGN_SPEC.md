# Comfa design spec

Comfa is a Flatsome + WooCommerce storefront for printed tees, caps, hoodies and socks. Its layout and shopping UX follow the structure of bombas.com (captured November 2026, desktop 1440 px and mobile 390 px). Brand name, logo, photography, product data and copy are original demo content; nothing from Bombas is bundled.

## Tokens

| Token | Value | Used for |
| --- | --- | --- |
| Page | `#fdfcf8` | body and header background |
| Ink | `#000814` | text, dark buttons, button outlines |
| Navy | `#213858` top bar, `#003566` secondary buttons, links, savings and badges |
| Cream | `#ecebdc` | collection banner, mobile chips, story band |
| Card | `#f3f3f1` | product image background |
| Soft | `#f8f8f4` | Quick View button, size buttons, info card |
| Yellow | `#ffc300`, hover `#ffd60a` | Add to Bag, checkout and hero buttons, hero sticker, headline underline |
| Giving bands | `#e9b94d` / `#b2c693` | yellow and green home bands |
| Accents | red `#d4240d` promo line, orange `#f04e23` wavy underline, sky `#a0c7ff` sign-up button |
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

**Home (UX Builder page):** category chips → cream hero (copy left, image right, round offer sticker) → four style tiles → scrolling ticker strip → product rail → "Shop by Colour" circles linking to the colour filter → product rail (intro column + four cards each) → six shape tiles → yellow giving band with blob counter → green "Comfort Beyond Tees" tiles → dark footer with sign-up, link columns, guarantee and tag chips.

**Product card:** grey 3:4 image, badge pill, Flatsome colour swatches (swap the image on click), name, price with struck regular price and "10% Pack Savings", "Unisex · Midweight" line, full-width Quick View.

**Collection page:** cream banner with breadcrumb, serif title and sub-collection tiles; left filter sidebar (weight, size, colour, pack, price); toolbar with item count and sorting; three-column grid (two on mobile, filters in Flatsome's off-canvas drawer).

**Product page:** info column left, two-up image grid right (slider on mobile). Order: breadcrumb, serif title, price line, first-order promo, pack segmented control, colour circles, two-line size buttons with Size Guide lightbox, navy Add to Bag, "N Bought = N Given", info card (rating, shipping, highlight, details). Below: editable story band, giving band, reviews with score header, related products.

**Bag and checkout:** off-canvas mini bag and cart page show free-shipping progress and the giving count; classic WooCommerce cart and checkout styled to match.

**Policy and FAQ pages (UX Builder pages):** one rounded white card. Cream left column with serif title, subtitle and a numbered contents list that stays in view while scrolling; right column with the document, sub-headings marked by a yellow bar. The FAQ groups questions in Flatsome accordions with a yellow +/− marker. On mobile the contents list sits above the document.
