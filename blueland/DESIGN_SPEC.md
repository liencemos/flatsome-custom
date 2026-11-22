# Blueland → Flatsome: Design Spec (đo từ blueland.com, viewport 1440)

Ảnh tham chiếu (trên VM, không commit): /home/ubuntu/bl/shots/
home_0..4.png, coll_0..1.png, prod_0..4.png (desktop 1425px, mỗi ảnh 1700px cao),
m_home_cols.png / m_coll_cols.png / m_prod_cols.png (mobile 375px), x_montage.png (mega menu, search overlay, quick add hover, cart drawer).

## 1. Tokens
| Token | Giá trị | Dùng cho |
|---|---|---|
| --bl-navy | #0b1057 | chữ chính, heading |
| --bl-navy-70 | #444a7b | chữ phụ (mô tả card category) |
| --bl-navy-50 | #5e6493 | giá gạch, số review |
| --bl-blue | #133cd1 | primary: nút, link, giá sale, footer bg, section reviews bg |
| --bl-blue-dark | #0033a7 | hover nút, text nút size |
| --bl-sky | #a0ddff | nút submit newsletter, dải trên footer |
| --bl-sky-95 | #e1f2ff | nền section USP |
| --bl-sky-98 | #f0f7ff | nền thanh danh mục phụ, press, card plan active |
| --bl-sky-99 | #eff5ff | nền certifications, PDP "Safe Home" |
| --bl-red | #e71a4e | thanh thông báo, badge GIFTABLE, promo bar PDP |
| --bl-plum | #910048 | "Holiday Shop" link, promo tile trong lưới category |
| --bl-mint | #eaffe9 | badge "% KIT SAVINGS" |
| --bl-badge-sky | #a0ddff | badge BESTSELLER (chữ navy) |
| --bl-border | #d6dbeb | viền select, input |
| --bl-border-2 | #9da3bd | viền card plan chưa chọn |
| --bl-img-bg | #f2f2f2 | nền ảnh sản phẩm (ảnh vuông) |

Font: gốc Sailec (body/heading, 400/500) + GT-Pressura Bold (eyebrow/label/nút, uppercase, ls 1px).
Thay thế: **DM Sans** 400/500/700 (Sailec) + **Barlow Semi Condensed** 600/700 (GT-Pressura).
Body: 17px / 1.42, màu navy. Heading weight 500, không uppercase.

Type scale:
- H1 hero 48/52.8 w500 trắng; H1 category 40/48; H2 section 40/48 (mobile 28/34); PDP title 32/41.6
- Sub-heading section 20/30 w400 (hero sub 22/33)
- Card title 17/23.5 w500; mô tả card 17/25.5 navy-70
- Eyebrow / label (Pressura): 17px w700 uppercase ls1px blue (vd "PRESS COVERAGE", "STARTER KITS →")
- USP title Pressura 20px uppercase blue; footer col title Pressura 20px uppercase trắng
- Badge: Pressura 14px w700 uppercase ls .5px, pill radius 999, pad 6px 12px (h≈22)
- Review quote 26/33.8 trắng

Bo góc: ảnh/card 8px; nút & badge pill 9999px; select/input form 4px; card plan 8px; box nổi (banner lifestyle) 16px.
Container: max 1233px nội dung (lề 96px ở 1425), header padding 0 48px, footer padding 40px 48px.
Breakpoints gốc: 768 / 1024 / 1280.

## 2. Nút
- Primary: bg blue, chữ trắng, Pressura 18px w700 uppercase ls1px, pad 16px 56px, h48, radius pill. Hover: blue-dark.
- Outline: trong suốt, viền 1px blue, chữ blue, pad 12px 32px (h40) hoặc 16px 56px (h48).
- Text link-arrow: Pressura uppercase underline + icon →.
- Quick Add (card): nút primary full-width hiện khi hover, đè đáy ảnh (cách đáy 12px, ngang 12px).

## 3. Header (tổng cao 155px desktop)
1. Announcement bar 40px, bg red, chữ trắng 13px, slider 3 thông điệp, mũi tên ‹ › 2 bên, link gạch chân.
2. Main nav 65px trắng, pad 0 48px: trái "Shop ▾" (mega) + "Holiday Shop" (plum); giữa logo (≈174×40); phải "About Us ▾", "Our Mission ▾", icon search, account ▾, cart (badge số).
3. Sub-nav 50px bg sky-98: link 13px w500 navy cách nhau 48px, căn giữa; vạch đứng phân tách nhóm (Starter Kits/Refills/Bestsellers | Dish/Laundry/Toilet/Hand Soap/Spray Cleaners/Extras); active = gạch chân.
- Mega "Shop": dropdown trắng radius 8, shadow nhẹ, 2 cột (cột 1 nền sky-98: Shop All (blue), Bestsellers, Starter Kits, Refills, Holiday Shop (plum), 1 Year Bundles [badge STOCK UP]; cột 2: Dish, Laundry, Toilet, Hand Soap [NEW SCENTS], Spray Cleaners, Extras).
- Dropdown About/Mission: 1 cột đơn giản.
- Search: overlay trượt xuống dưới header, ô input gạch chân, "Popular Products" lưới 3 cột thumbnail + sao + giá.
- Mobile: hamburger trái, logo giữa, search + cart phải; sub-nav cuộn ngang.
- Sticky header khi cuộn.

## 4. Cart drawer (off-canvas phải, ~420px)
Tiêu đề "Your Cart (n)", dải promo đỏ, **thanh tiến độ free-shipping** ($50), item: ảnh 72px, tên, giá gạch + giá sale, stepper − 1 +, xoá ×; "You may also like" slider mini; Subtotal; nút CHECKOUT primary full; ghi chú thuế.

## 5. Homepage (theo thứ tự, chiều cao desktop)
1. Hero 745px: ảnh full-bleed, overlay tối nhẹ đáy, text căn giữa đáy: H1 48 + sub 22 + nút primary "SHOP NOW".
2. Shop By Category (605): H2 + sub 20px navy-70; slider 5-6 item/hàng (ảnh vuông 210, radius 8, nền xám), dưới: label Pressura blue "STARTER KITS →" + mô tả 17px navy-70; mũi tên ‹ ›.
3. USP (507) bg sky-95: H2 "4,000,000+ Homes Have Made the Switch"; 4 cột icon line-art blue 160px, title Pressura blue uppercase, mô tả.
4. Bestsellers (789): H2 + sub; slider 4 card (284px); nút outline "SHOP ALL" giữa.
5. Press (480) bg sky-98: eyebrow "PRESS COVERAGE", quote H2 blue đổi theo logo; hàng 4 logo xám-blue, logo active có gạch dưới.
6. Lifestyle banner (795): ảnh full-bleed, box trắng-sky (sky-98) radius 16, bên phải (x≈829, w460), H2 + text + nút outline.
7. Reviews (711) bg blue: H2 trắng; slider quote (card viền trắng mờ radius 12, w800, slide 2 bên mờ), sao trắng, "– Name on PRODUCT" link uppercase gạch chân; dots + pause.
8. How It Works (713): 2 cột 50/50 — ảnh trái full, text phải (H2, p 20/30, nút outline).
9. Autoship (713): not implemented — removed by request. Reference measurement: 2 cột đảo — text trái (H2, p, list ✓ 4 dòng, nút outline), ảnh phải.
10. Certifications (626) bg sky-99: eyebrow + H2 blue; 8 logo 104px + caption 17 w500 blue; link "LEARN ABOUT OUR CERTIFICATIONS →".
11. Instagram (732): H2 trái + link "FOLLOW ALONG ON INSTAGRAM →"; slider ảnh 301×376 tràn phải.
12. CTA (272): nền trời mây (sky), H2 blue "Start Your Journey" + nút primary.

## 6. Footer (603) bg blue, chữ trắng, viền trên 6px sky
Cột 1 (416px): H3 26px "Even our emails are cleaner", p, form email (input pill trắng h44 + nút pill sky có mũi tên → đè bên phải), note 13px, social 5 icon. Cột 2-4: SHOP / ABOUT / HELP (title Pressura 20 uppercase, link 17px cách 28px). Dưới: select tiền tệ, 2 logo chứng nhận, dòng copyright + email + Terms + Privacy (14px).
Mobile: cột link thành accordion (+/−).

## 7. Category (PLP)
- Pad trên 72px. H1 40 + mô tả 20/30.
- Toolbar: trái "12 Items" (navy-50); phải dropdown "Product Category ▾" | "Product Type ▾" (text button, ngăn bởi vạch) + select "Featured" (240×48, viền border, radius 4). Dưới: hr 2px border.
- Lưới 4 cột, gap 32px (mobile 2 cột, gap 16).
- Ô đầu tiên = promo tile (bg plum, chữ trắng 22 w500 căn giữa, note italic 13).
- Product card: ảnh vuông nền img-bg radius 8; badges top-left (stack ngang, gap 4/8); Quick Add hover; dưới: sao blue 14px + "4.7" Pressura blue + "(58,945)" navy-50; hàng tên (w500) trái — giá phải (gạch navy-50 + sale blue w500 + "+" ); dòng "with 30% auto-applied" 13px blue căn phải; swatch tròn 16px + "| Scent: …" 13px.
- Đoạn SEO cuối lưới, max 900px, giữa, link gạch chân.
- Mobile: filter/sort thành nút "Filter" mở off-canvas.

## 8. Product (PDP)
- Section pad 56px 48px, 2 cột 50/50 gap 24.
- Trái: thumbnail dọc 76px (gap 14), ảnh chính 565 vuông; badges trên ảnh (góc phải-trên); gallery sticky.
- Phải (pad-left 48): eyebrow category Pressura 13 uppercase; H1 32; sao + count; hr; "What's Included In Your First Order" (w500) + list •; hr; promo bar đỏ (accordion) radius 4; reference plan-card presentation (not implemented — removed by request); "Today's Total" 20 w500 + giá; nút ADD TO CART primary full-width; box Rewards (bg sky-98 pill) "Earn 161 points"; note "Free 100-Day Returns | Free Shipping over $50" 14 navy-50; hr; "Popular Add-On" slider card ngang với nút pill outline "ADD | $12 $9.60"; tabs gạch chân Overview / Ingredients / Sustainability.
- Anchor bar sticky 50px bg sky-95: Our Values / How It Works / Recommendations / Reviews.
- Sections: "What's In Your Kit" (bg #f7fafc, 4 cột ảnh + label Pressura + mô tả, căn giữa); How It Works (2 cột, text trái với bước số tròn blue 24px FILL/DROP/CLEAN, ảnh phải); "Safe Home, Cleaner Planet" (bg sky-99, tab gạch chân, box trắng radius 16 list ✕ 2 cột, hàng logo, ảnh radius 8 phải); "You'll Also Love…" slider 4 card; Reviews (tổng điểm + biểu đồ thanh 5→1, summary, ảnh khách, chip chủ đề, nút Filters / Write a Review pill viền 2px navy, danh sách review 2 cột: box trái thông tin người mua bg #eff3f6, phải sao + tiêu đề + nội dung).
- Mobile: sticky Add-to-cart bar đáy.

## 9. Page template chung
Hero đơn giản (bg sky-98, eyebrow + H1 40 + sub 20, căn giữa, pad 72px) → nội dung max 800px (body 17/1.6, h2 32, h3 24, link blue gạch chân) → CTA "Start Your Journey" chung (UX Block) → footer.
