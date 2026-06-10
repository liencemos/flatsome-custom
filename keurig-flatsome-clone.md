# Clone Keurig.com → Flatsome UX Builder (Chi Tiết)

> **Theme:** Flatsome | **Builder:** UX Builder | **WooCommerce:** Bắt buộc  
> Tài liệu này hướng dẫn từng section, shortcode, CSS tùy chỉnh để tái tạo giao diện Keurig.com.

---

## MỤC LỤC

1. [Cài đặt & Cấu hình ban đầu](#1-cài-đặt--cấu-hình-ban-đầu)
2. [Header & Navigation](#2-header--navigation)
3. [Announcement Bar (Top Banner)](#3-announcement-bar-top-banner)
4. [Hero Banner chính](#4-hero-banner-chính)
5. [Section: Shop By Category](#5-section-shop-by-category)
6. [Section: Featured Products](#6-section-featured-products)
7. [Section: Promo Banner đôi](#7-section-promo-banner-đôi)
8. [Section: Best Sellers K-Cup Pods](#8-section-best-sellers-k-cup-pods)
9. [Section: Sustainability Banner](#9-section-sustainability-banner)
10. [Section: Newsletter Subscribe](#10-section-newsletter-subscribe)
11. [Footer](#11-footer)
12. [CSS Tùy chỉnh toàn cục](#12-css-tùy-chỉnh-toàn-cục)

---

## 1. Cài Đặt & Cấu Hình Ban Đầu

### 1.1 Màu sắc thương hiệu
Vào **Flatsome → Theme Options → Colors**, thiết lập:

| Biến | Màu hex | Mô tả |
|---|---|---|
| Primary Color | `#009a44` | Xanh lá Keurig chính |
| Secondary Color | `#006b2d` | Xanh lá đậm hover |
| Accent Color | `#cc0000` | Đỏ cho badge/Deals |
| Dark Color | `#1a1a1a` | Chữ đậm |
| Light BG | `#f5f5f5` | Nền section sáng |

### 1.2 Font chữ
Vào **Flatsome → Theme Options → Fonts**:
- **Heading Font:** `Montserrat` (Google Fonts), Weight: 700, 600
- **Body Font:** `Open Sans`, Weight: 400, 600
- **Base Font Size:** 15px

### 1.3 Custom CSS – thêm vào Appearance → Customize → Additional CSS:
```css
:root {
  --keurig-green: #009a44;
  --keurig-green-dark: #006b2d;
  --keurig-red: #cc0000;
  --keurig-gray: #f5f5f5;
  --keurig-text: #1a1a1a;
}
```

---

## 2. Header & Navigation

### 2.1 Cấu hình Header trong Flatsome Header Builder

Vào **Flatsome → Header Builder**, kéo thả:

```
[Top Bar]
  Left: Free Shipping $35+
  Right: Support | Account | Cart

[Main Header]
  Left: Logo
  Center: Search Bar
  Right: Cart Icon + "Free Shipping $35+" text

[Nav Bar - Full Width]
  Menu: Coffee Makers | K-Cup Pods | Ground Coffee | Creamers | Accessories | Deals | Recipes | OUTLET
```

### 2.2 Shortcode Top Bar

Dán vào **Flatsome → Header → Top Bar → Left Content**:

```
[ux_text font_size="13px" text_color="#ffffff"]
  <i class="icon-truck"></i> Free Shipping $35+
[/ux_text]
```

Dán vào **Right Content**:
```
[ux_text font_size="13px" text_color="#ffffff"]
  <a href="/support" style="color:#fff;margin-right:15px;">Support</a>
  <a href="/account" style="color:#fff;">Account</a>
[/ux_text]
```

### 2.3 CSS Header

```css
/* Top bar nền xanh lá */
.header-top {
  background-color: var(--keurig-green) !important;
  color: #fff;
}

/* Nav bar trắng, chữ đậm */
.nav-dark .nav > li > a,
.nav > li > a {
  font-weight: 700;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #1a1a1a !important;
}

/* Deals link màu đỏ */
.nav > li.deals-nav > a {
  color: var(--keurig-red) !important;
}

/* OUTLET link nền đặc biệt */
.nav > li.outlet-nav > a {
  color: #b8860b !important;
  font-weight: 800;
}

/* Hover underline */
.nav > li > a:hover {
  color: var(--keurig-green) !important;
}

/* Search bar full width */
.header-search-form input[type="search"] {
  border-radius: 25px;
  border: 2px solid #ddd;
  padding: 10px 20px;
  font-size: 14px;
}
```

---

## 3. Announcement Bar (Top Banner)

Keurig có banner slider thông báo khuyến mãi xoay vòng. Dùng **UX Slider** hoặc plugin **Hello Bar**.

### Cách 1: Dùng UX Builder Shortcode

Tạo page/widget, dán shortcode sau:

```
[ux_slider timer="4000" arrows="false" bullets="false" height="44px" bg="#fff3cd"]

  [ux_banner height="44px" bg="#e8f5e9" text_color="dark"]
    [text_box width="100" position_x="50" position_y="50" text_align="center"]
      <p style="margin:0;font-size:13px;font-weight:600;">
        ☕ K-Duo® Gen 2 + 4 boxes for under $100 with Keurig Starter Kit.
        <a href="/starter-kit" style="color:#009a44;text-decoration:underline;font-weight:700;">SHOP NOW</a>
      </p>
    [/text_box]
  [/ux_banner]

  [ux_banner height="44px" bg="#e8f5e9" text_color="dark"]
    [text_box width="100" position_x="50" position_y="50" text_align="center"]
      <p style="margin:0;font-size:13px;font-weight:600;">
        🎉 Exclusive Offer: $10.99/box 1st Auto-Delivery. Use code:
        <strong style="color:#cc0000;">FIRSTPOUR</strong>
        <a href="/deals" style="color:#009a44;text-decoration:underline;margin-left:8px;">See Details</a>
      </p>
    [/text_box]
  [/ux_banner]

  [ux_banner height="44px" bg="#e8f5e9" text_color="dark"]
    [text_box width="100" position_x="50" position_y="50" text_align="center"]
      <p style="margin:0;font-size:13px;font-weight:600;">
        ☕ Coffee starting at $14.99/box. Use code:
        <strong style="color:#cc0000;">DAILYBREW</strong>
        <a href="/k-cup-pods" style="color:#009a44;text-decoration:underline;margin-left:8px;">Shop Now</a>
      </p>
    [/text_box]
  [/ux_banner]

[/ux_slider]
```

---

## 4. Hero Banner Chính

Keurig dùng full-width hero banner với hình máy pha cà phê + CTA nổi bật.

### Shortcode UX Builder:

```
[ux_banner height="600px" height__sm="350px" bg="YOUR_HERO_IMAGE_ID" bg_overlay="rgba(0,0,0,0.25)" bg_pos="center center" parallax="3"]

  [text_box width="55" width__sm="90" position_x="10" position_x__sm="50" position_y="50" text_align="left" text_align__sm="center" animation="fadeInLeft" animation_delay="200"]

    <p style="color:#fff;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;">
      NEW ARRIVAL
    </p>

    <h1 style="color:#fff;font-size:52px;font-weight:800;line-height:1.1;margin-bottom:16px;">
      Brew Your<br>Perfect Cup
    </h1>

    <p style="color:rgba(255,255,255,0.9);font-size:17px;margin-bottom:28px;max-width:420px;">
      The K-Duo® Gen 2 — brew a single cup or a full carafe, hot or iced.
    </p>

    <a href="/coffee-makers" class="button primary large" style="background:#009a44;color:#fff;border-radius:4px;padding:14px 36px;font-size:15px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-right:12px;display:inline-block;">
      SHOP COFFEE MAKERS
    </a>

    <a href="/k-cup-pods" class="button outline" style="border:2px solid #fff;color:#fff;border-radius:4px;padding:13px 28px;font-size:15px;font-weight:700;text-transform:uppercase;letter-spacing:1px;display:inline-block;">
      SHOP K-CUP® PODS
    </a>

  [/text_box]

[/ux_banner]
```

> **Lưu ý:** Thay `YOUR_HERO_IMAGE_ID` bằng ID ảnh upload lên Media Library. Keurig dùng ảnh máy K-Duo trên nền tối/ấm.

---

## 5. Section: Shop By Category

Keurig có row 4 category icons: Coffee Makers | K-Cup Pods | Ground Coffee | Accessories

### Shortcode:

```
[section label="Shop By Category" padding="60px" bg="#ffffff"]

  [row]
    [col span="12" span__sm="12" align="center"]
      <h2 style="font-size:28px;font-weight:800;color:#1a1a1a;margin-bottom:8px;">Shop By Category</h2>
      <p style="color:#666;font-size:15px;margin-bottom:40px;">Everything you need for the perfect brew</p>
    [/col]
  [/row]

  [row col_style="divide" col_divider_color="#eee" h_align="center"]

    [col span="3" span__sm="6" span__xs="6" align="center"]
      [ux_image id="COFFEE_MAKER_IMG_ID" width="160" height="160" image_size="thumbnail" link="/coffee-makers"]
      <h3 style="font-size:15px;font-weight:700;margin-top:16px;color:#1a1a1a;">Coffee Makers</h3>
      <a href="/coffee-makers" style="color:#009a44;font-size:13px;font-weight:600;text-decoration:none;">Shop Now →</a>
    [/col]

    [col span="3" span__sm="6" span__xs="6" align="center"]
      [ux_image id="KCUP_IMG_ID" width="160" height="160" image_size="thumbnail" link="/k-cup-pods"]
      <h3 style="font-size:15px;font-weight:700;margin-top:16px;color:#1a1a1a;">K-Cup® Pods</h3>
      <a href="/k-cup-pods" style="color:#009a44;font-size:13px;font-weight:600;text-decoration:none;">Shop Now →</a>
    [/col]

    [col span="3" span__sm="6" span__xs="6" align="center"]
      [ux_image id="GROUND_IMG_ID" width="160" height="160" image_size="thumbnail" link="/ground-coffee"]
      <h3 style="font-size:15px;font-weight:700;margin-top:16px;color:#1a1a1a;">Ground Coffee</h3>
      <a href="/ground-coffee" style="color:#009a44;font-size:13px;font-weight:600;text-decoration:none;">Shop Now →</a>
    [/col]

    [col span="3" span__sm="6" span__xs="6" align="center"]
      [ux_image id="ACCESSORIES_IMG_ID" width="160" height="160" image_size="thumbnail" link="/accessories"]
      <h3 style="font-size:15px;font-weight:700;margin-top:16px;color:#1a1a1a;">Accessories</h3>
      <a href="/accessories" style="color:#009a44;font-size:13px;font-weight:600;text-decoration:none;">Shop Now →</a>
    [/col]

  [/row]

[/section]
```

---

## 6. Section: Featured Products (Coffee Makers)

Dùng WooCommerce shortcode tích hợp trong Flatsome.

### Shortcode:

```
[section label="Featured Coffee Makers" padding="60px" bg="#f5f5f5"]

  [row]
    [col span="12" align="center"]
      <p style="color:#009a44;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;">OUR LINEUP</p>
      <h2 style="font-size:32px;font-weight:800;color:#1a1a1a;margin-bottom:8px;">Find Your Perfect Brewer</h2>
      <p style="color:#555;font-size:15px;margin-bottom:40px;">From single-serve to full carafes — there's a Keurig for every coffee lover.</p>
    [/col]
  [/row]

  [row]
    [col span="12"]
      [products limit="4" columns="4" category="coffee-makers" orderby="menu_order" order="ASC"]
    [/col]
  [/row]

  [row]
    [col span="12" align="center"]
      <a href="/coffee-makers" class="button primary large" style="background:#009a44;color:#fff;border-radius:4px;padding:14px 40px;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px;display:inline-block;margin-top:20px;">
        VIEW ALL COFFEE MAKERS
      </a>
    [/col]
  [/row]

[/section]
```

### CSS cho Product Cards (phong cách Keurig):

```css
/* Product card style */
.products .product {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0,0,0,0.07);
  transition: box-shadow 0.3s ease, transform 0.3s ease;
  padding-bottom: 20px;
}

.products .product:hover {
  box-shadow: 0 8px 30px rgba(0,0,0,0.13);
  transform: translateY(-4px);
}

/* Add to cart button */
.products .product .button {
  background: var(--keurig-green) !important;
  color: #fff !important;
  border-radius: 4px !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
  font-size: 12px !important;
}

.products .product .button:hover {
  background: var(--keurig-green-dark) !important;
}

/* Price */
.woocommerce .products .price {
  color: #1a1a1a;
  font-weight: 700;
  font-size: 18px;
}

.woocommerce .products del .amount {
  color: #999;
  font-size: 14px;
}

/* Sale badge */
.onsale {
  background: var(--keurig-red) !important;
  border-radius: 4px !important;
  font-weight: 700 !important;
  font-size: 11px !important;
}
```

---

## 7. Section: Promo Banner Đôi

Keurig thường dùng 2 banner cạnh nhau: "Deals" và "Auto-Delivery".

### Shortcode:

```
[section label="Promo Banners" padding="0"]

  [row padding="0" col_bg="transparent"]

    [col span="6" span__sm="12" padding="0"]
      [ux_banner height="350px" bg="PROMO_LEFT_IMG_ID" bg_overlay="rgba(0,80,30,0.55)" link="/deals"]
        [text_box width="80" position_x="50" position_y="50" text_align="center" animation="fadeIn"]
          <p style="color:rgba(255,255,255,0.85);font-size:12px;text-transform:uppercase;letter-spacing:2px;font-weight:600;margin-bottom:8px;">Limited Time</p>
          <h3 style="color:#fff;font-size:30px;font-weight:800;margin-bottom:12px;line-height:1.2;">Today's Best Deals</h3>
          <p style="color:rgba(255,255,255,0.85);font-size:14px;margin-bottom:22px;">Save big on coffee makers & K-Cup® pods</p>
          <a href="/deals" class="button white" style="border:2px solid #fff;color:#fff;background:transparent;padding:11px 28px;font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:1px;border-radius:4px;display:inline-block;">
            SHOP DEALS
          </a>
        [/text_box]
      [/ux_banner]
    [/col]

    [col span="6" span__sm="12" padding="0"]
      [ux_banner height="350px" bg="PROMO_RIGHT_IMG_ID" bg_overlay="rgba(0,0,0,0.40)" link="/auto-delivery"]
        [text_box width="80" position_x="50" position_y="50" text_align="center" animation="fadeIn" animation_delay="150"]
          <p style="color:rgba(255,255,255,0.85);font-size:12px;text-transform:uppercase;letter-spacing:2px;font-weight:600;margin-bottom:8px;">Subscribe & Save</p>
          <h3 style="color:#fff;font-size:30px;font-weight:800;margin-bottom:12px;line-height:1.2;">Auto-Delivery<br>& Save More</h3>
          <p style="color:rgba(255,255,255,0.85);font-size:14px;margin-bottom:22px;">$10.99/box on first order. Cancel anytime.</p>
          <a href="/auto-delivery" class="button" style="background:#009a44;color:#fff;padding:11px 28px;font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:1px;border-radius:4px;display:inline-block;border:none;">
            LEARN MORE
          </a>
        [/text_box]
      [/ux_banner]
    [/col]

  [/row]

[/section]
```

---

## 8. Section: Best Sellers K-Cup Pods

```
[section label="Best Seller K-Cup Pods" padding="60px" bg="#ffffff"]

  [row]
    [col span="12" align="center"]
      <p style="color:#009a44;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;">MOST LOVED</p>
      <h2 style="font-size:32px;font-weight:800;color:#1a1a1a;margin-bottom:8px;">Best Selling K-Cup® Pods</h2>
      <p style="color:#555;font-size:15px;margin-bottom:10px;">Over 75+ brands. Something for everyone.</p>
    [/col]
  [/row]

  [row]
    [col span="12"]
      [ux_product_tabs style="tabs" tabs="Best Sellers, Dark Roast, Light Roast, Decaf" tab_style="button" tab_align="center" padding_bottom="30px"]

        [ux_product_tab label="Best Sellers"]
          [products limit="5" columns="5" best_selling="true" category="k-cup-pods"]
        [/ux_product_tab]

        [ux_product_tab label="Dark Roast"]
          [products limit="5" columns="5" category="dark-roast"]
        [/ux_product_tab]

        [ux_product_tab label="Light Roast"]
          [products limit="5" columns="5" category="light-roast"]
        [/ux_product_tab]

        [ux_product_tab label="Decaf"]
          [products limit="5" columns="5" category="decaf"]
        [/ux_product_tab]

      [/ux_product_tabs]
    [/col]
  [/row]

  [row]
    [col span="12" align="center"]
      <a href="/k-cup-pods" class="button primary" style="background:#009a44;color:#fff;border-radius:4px;padding:13px 36px;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px;display:inline-block;margin-top:10px;">
        VIEW ALL K-CUP® PODS
      </a>
    [/col]
  [/row]

[/section]
```

> **Lưu ý:** `[ux_product_tabs]` là shortcode tùy chỉnh — nếu Flatsome chưa có, dùng plugin **WooCommerce Product Table** hoặc tạo 4 tabs thủ công bằng `[ux_tabbed]`.

### Cách thay thế bằng Tab thủ công:

```
[tabbed style="line" tab_style="line" tab_bg="#f5f5f5" tab_color="dark"]

  [tab title="Best Sellers"]
    [products limit="5" columns="5" best_selling="true" category="k-cup-pods"]
  [/tab]

  [tab title="Dark Roast"]
    [products limit="5" columns="5" category="dark-roast"]
  [/tab]

  [tab title="Light Roast"]
    [products limit="5" columns="5" category="light-roast"]
  [/tab]

  [tab title="Decaf"]
    [products limit="5" columns="5" category="decaf"]
  [/tab]

[/tabbed]
```

---

## 9. Section: Sustainability Banner

Keurig có section về cam kết bền vững (sustainability).

### Shortcode:

```
[section label="Sustainability" padding="80px" bg="#1a3c2a"]

  [row h_align="center"]

    [col span="5" span__sm="12" align="left" align__sm="center"]
      <p style="color:#7dcea0;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-bottom:10px;">OUR COMMITMENT</p>
      <h2 style="color:#ffffff;font-size:36px;font-weight:800;line-height:1.2;margin-bottom:20px;">
        Brewing a<br>Better Tomorrow
      </h2>
      <p style="color:rgba(255,255,255,0.78);font-size:15px;line-height:1.7;margin-bottom:30px;">
        We're committed to making 100% of our K-Cup® pods recyclable. From sustainable sourcing to reducing waste — every cup counts.
      </p>
      <a href="/sustainability" class="button" style="background:#009a44;color:#fff;border-radius:4px;padding:13px 32px;font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px;display:inline-block;">
        LEARN MORE
      </a>
    [/col]

    [col span="1" span__sm="12"]
    [/col]

    [col span="6" span__sm="12"]
      [row_inner]

        [col_inner span="6" span__sm="6" align="center"]
          [ux_icon icon="icon-leaf" size="48px" color="rgb(125,206,160)"]
          <h4 style="color:#fff;font-size:16px;font-weight:700;margin:12px 0 6px;">Recyclable Pods</h4>
          <p style="color:rgba(255,255,255,0.7);font-size:13px;">100% recyclable K-Cup® pods goal by 2025</p>
        [/col_inner]

        [col_inner span="6" span__sm="6" align="center"]
          [ux_icon icon="icon-droplet" size="48px" color="rgb(125,206,160)"]
          <h4 style="color:#fff;font-size:16px;font-weight:700;margin:12px 0 6px;">Water Conscious</h4>
          <p style="color:rgba(255,255,255,0.7);font-size:13px;">Brew only what you need, zero waste</p>
        [/col_inner]

        [col_inner span="6" span__sm="6" align="center"]
          [ux_icon icon="icon-sun" size="48px" color="rgb(125,206,160)"]
          <h4 style="color:#fff;font-size:16px;font-weight:700;margin:12px 0 6px;">Responsible Sourcing</h4>
          <p style="color:rgba(255,255,255,0.7);font-size:13px;">Coffee from certified sustainable farms</p>
        [/col_inner]

        [col_inner span="6" span__sm="6" align="center"]
          [ux_icon icon="icon-heart" size="48px" color="rgb(125,206,160)"]
          <h4 style="color:#fff;font-size:16px;font-weight:700;margin:12px 0 6px;">Community Impact</h4>
          <p style="color:rgba(255,255,255,0.7);font-size:13px;">Supporting coffee-growing communities</p>
        [/col_inner]

      [/row_inner]
    [/col]

  [/row]

[/section]
```

---

## 10. Section: Newsletter Subscribe

```
[section label="Newsletter" padding="60px" bg="#f0f7f2"]

  [row h_align="center"]
    [col span="8" span__sm="12" align="center"]

      <p style="color:#009a44;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;">STAY IN THE LOOP</p>
      <h2 style="font-size:30px;font-weight:800;color:#1a1a1a;margin-bottom:10px;">Get Exclusive Deals & Offers</h2>
      <p style="color:#555;font-size:15px;margin-bottom:30px;">
        Sign up and be the first to know about new products, promotions, and brewing tips.
      </p>

      [contact-form-7 id="YOUR_CF7_ID" title="Newsletter Signup"]

    [/col]
  [/row]

[/section]
```

### CSS cho form đăng ký:

```css
/* Newsletter form style - Keurig inspired */
.wpcf7-form input[type="email"] {
  border: 2px solid #ccc;
  border-radius: 4px 0 0 4px;
  padding: 14px 20px;
  font-size: 15px;
  width: 340px;
  max-width: 100%;
  outline: none;
  transition: border-color 0.2s;
}

.wpcf7-form input[type="email"]:focus {
  border-color: #009a44;
}

.wpcf7-form input[type="submit"] {
  background: #009a44;
  color: #fff;
  border: none;
  padding: 14px 30px;
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  border-radius: 0 4px 4px 0;
  cursor: pointer;
  transition: background 0.2s;
}

.wpcf7-form input[type="submit"]:hover {
  background: #006b2d;
}
```

> **Gợi ý:** Dùng plugin **Contact Form 7** hoặc **Mailchimp for WooCommerce** để kết nối email marketing.

---

## 11. Footer

### 11.1 Cấu hình Footer trong Flatsome

Vào **Flatsome → Footer Builder**, thiết lập 4 cột:

```
[Footer Top - 4 columns]

Col 1: Logo + tagline + social icons
Col 2: Coffee Makers | K-Cup Pods | Ground Coffee | Creamers | Accessories
Col 3: Help & Support | Track Order | Returns | FAQ | Contact Us
Col 4: About Keurig | Sustainability | Careers | Press | Investor Relations

[Footer Bottom - 1 row]
Left: © 2024 Keurig Dr Pepper Inc. All rights reserved.
Right: Privacy Policy | Terms of Use | Cookie Settings
```

### 11.2 Shortcode Footer Widgets

```
[ux_sidebar id="footer-col-1"]

  [ux_image id="LOGO_WHITE_ID" width="160"]

  <p style="color:rgba(255,255,255,0.65);font-size:14px;margin-top:16px;line-height:1.7;">
    Brewing better mornings since 1992. The world's favorite single-serve coffee experience.
  </p>

  [social_follow networks="facebook,twitter,instagram,youtube,pinterest" size="28" align="left" spacing="8px"]

[/ux_sidebar]
```

### 11.3 CSS Footer:

```css
/* Footer nền tối */
.footer-1 {
  background: #1a1a1a !important;
}

.footer-1 .widget-title {
  color: #ffffff !important;
  font-size: 14px !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 1.5px !important;
  border-bottom: 2px solid #009a44 !important;
  padding-bottom: 10px !important;
  margin-bottom: 18px !important;
}

.footer-1 a {
  color: rgba(255,255,255,0.65) !important;
  font-size: 14px !important;
  line-height: 2.2 !important;
  transition: color 0.2s;
}

.footer-1 a:hover {
  color: #7dcea0 !important;
}

/* Footer bottom bar */
.footer-2 {
  background: #111111 !important;
  border-top: 1px solid rgba(255,255,255,0.1) !important;
}

.footer-2 p,
.footer-2 a {
  color: rgba(255,255,255,0.45) !important;
  font-size: 12px !important;
}

.footer-2 a:hover {
  color: rgba(255,255,255,0.8) !important;
}
```

---

## 12. CSS Tùy Chỉnh Toàn Cục

Dán toàn bộ CSS này vào **Appearance → Customize → Additional CSS**:

```css
/* ===========================
   KEURIG CLONE - GLOBAL CSS
   =========================== */

/* Variables */
:root {
  --keurig-green: #009a44;
  --keurig-green-dark: #006b2d;
  --keurig-red: #cc0000;
  --keurig-gray: #f5f5f5;
  --keurig-text: #1a1a1a;
  --keurig-light-green: #e8f5e9;
}

/* === GLOBAL BUTTON OVERRIDE === */
.button.primary,
.woocommerce a.button,
.woocommerce button.button,
.woocommerce input.button {
  background: var(--keurig-green) !important;
  color: #fff !important;
  border-radius: 4px !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.8px !important;
  transition: background 0.25s ease, transform 0.2s ease !important;
}

.button.primary:hover,
.woocommerce a.button:hover {
  background: var(--keurig-green-dark) !important;
  transform: translateY(-1px);
}

/* === SECTIONS GLOBAL === */
.section {
  position: relative;
}

/* === TYPOGRAPHY === */
h1, h2, h3, h4, h5 {
  font-family: 'Montserrat', sans-serif;
  color: var(--keurig-text);
}

body {
  font-family: 'Open Sans', sans-serif;
  color: #444;
}

/* === PRODUCT GRID === */
.products .product .woocommerce-loop-product__title {
  font-size: 14px !important;
  font-weight: 600 !important;
  color: var(--keurig-text) !important;
  margin-bottom: 6px !important;
}

/* Star ratings */
.woocommerce .star-rating {
  color: #f5a623 !important;
}

/* === SALE BADGE === */
.woocommerce span.onsale {
  background: var(--keurig-red) !important;
  border-radius: 50px !important;
  font-weight: 700 !important;
  font-size: 11px !important;
  padding: 4px 10px !important;
  min-height: auto !important;
  min-width: auto !important;
  line-height: 1.4 !important;
}

/* === TABS === */
.tabbed-nav li a {
  font-weight: 600 !important;
  font-size: 14px !important;
  color: #555 !important;
  border-bottom: 3px solid transparent !important;
  transition: all 0.2s !important;
}

.tabbed-nav li.active a,
.tabbed-nav li a:hover {
  color: var(--keurig-green) !important;
  border-bottom-color: var(--keurig-green) !important;
}

/* === ANNOUNCEMENT BAR === */
.announcement-bar {
  background: var(--keurig-light-green);
  text-align: center;
  padding: 10px 20px;
  font-size: 13px;
  font-weight: 600;
  border-bottom: 1px solid rgba(0,154,68,0.2);
}

.announcement-bar a {
  color: var(--keurig-green);
  text-decoration: underline;
  font-weight: 700;
}

/* === BREADCRUMBS === */
.breadcrumb {
  font-size: 12px;
  color: #888;
  padding: 12px 0;
}

/* === RESPONSIVE === */
@media (max-width: 767px) {
  h1 { font-size: 28px !important; }
  h2 { font-size: 22px !important; }

  .ux-banner .text-box {
    padding: 20px !important;
  }

  /* Stack promo banners on mobile */
  .promo-dual .col {
    width: 100% !important;
  }
}

/* === SCROLL TO TOP BUTTON === */
#scroll-top {
  background: var(--keurig-green) !important;
  border-radius: 50% !important;
}

/* === LOADING ANIMATION === */
.loading-spinner {
  border-top-color: var(--keurig-green) !important;
}
```

---

## CHECKLIST TRIỂN KHAI

### Bước 1 — Cài đặt
- [ ] Cài Flatsome theme (phiên bản mới nhất)
- [ ] Kích hoạt WooCommerce
- [ ] Cài Contact Form 7 (cho newsletter)
- [ ] Cài **Flatsome UX Builder** (đã tích hợp sẵn)

### Bước 2 — Cấu hình nền tảng
- [ ] Thiết lập màu Primary = `#009a44`
- [ ] Upload font Montserrat + Open Sans
- [ ] Tạo menu điều hướng theo cấu trúc Keurig
- [ ] Upload logo (có cả bản trắng cho footer)

### Bước 3 — Tạo Categories WooCommerce
- [ ] `coffee-makers` — Coffee Makers
- [ ] `k-cup-pods` — K-Cup® Pods
- [ ] `ground-coffee` — Ground Coffee
- [ ] `creamers` — Creamers
- [ ] `accessories` — Accessories
- [ ] `dark-roast`, `light-roast`, `decaf` (sub-categories)

### Bước 4 — Upload ảnh
- [ ] Hero banner (1920×600px)
- [ ] Promo banner trái (960×350px)
- [ ] Promo banner phải (960×350px)
- [ ] Category icons (200×200px mỗi cái)
- [ ] Product images (800×800px, nền trắng)

### Bước 5 — Build từng section theo thứ tự
- [ ] Announcement Bar
- [ ] Hero Banner
- [ ] Shop By Category
- [ ] Featured Products
- [ ] Promo Banners đôi
- [ ] Best Sellers K-Cup tabs
- [ ] Sustainability Section
- [ ] Newsletter Section
- [ ] Header & Footer

### Bước 6 — CSS & Tối ưu
- [ ] Dán Global CSS vào Additional CSS
- [ ] Kiểm tra responsive mobile (360px, 768px, 1440px)
- [ ] Tối ưu ảnh với Smush hoặc ShortPixel
- [ ] Bật Flatsome lazy loading

---

## MẸO QUAN TRỌNG

| Vấn đề | Giải pháp |
|---|---|
| Shortcode không render | Kiểm tra UX Builder đã bật chưa trong Page Settings |
| Sản phẩm không hiện | Đảm bảo WooCommerce có sản phẩm + đúng category slug |
| Font không load | Vào Flatsome Options → Fonts → chọn "Google Fonts" |
| Ảnh background bị vỡ | Upload ảnh ≥1920px, dùng "center center" cho bg_pos |
| Tabs không hoạt động | Dùng shortcode `[tabbed]` native của Flatsome |
| Mobile bị vỡ layout | Thêm `span__sm` vào mọi `[col]` shortcode |
