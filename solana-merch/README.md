# Signal Supply — giao diện Flatsome cho áo in và mũ

Signal Supply dùng bố cục dạng chỉ mục, nhãn monospace, đường viền mảnh và các mảng nền tối lấy cảm hứng từ [Solana AI Design Language](https://solana.ai/docs). Logo, hình minh họa và họa tiết sản phẩm là thiết kế riêng. Nội dung demo dùng tiếng Anh (Mỹ); tiền tệ demo là USD.

| Token | Mã màu | Vai trò |
|---|---|---|
| Ink Black | `#000814` | Nền chính |
| Prussian Blue | `#001D3D` | Bề mặt thẻ, chân trang |
| Oxford Navy | `#003566` | Viền, ô lưới và bề mặt tương tác |
| School Bus Yellow | `#FFC300` | Nút chính và nhấn mạnh |
| Gold | `#FFD60A` | Tiêu đề, chữ và họa tiết |

Typography trong **Flatsome → Theme Options → Style → Typography**: Space Grotesk (tiêu đề), Manrope (nội dung và menu), IBM Plex Mono (nhãn kỹ thuật). Bản demo tải Google Fonts qua CDN; có thể tắt tùy chọn CDN để Flatsome lưu font cục bộ.

## Cài trên website thử nghiệm

Yêu cầu: WordPress, WooCommerce, Flatsome có bản quyền, WP-CLI và thư viện ảnh WordPress hỗ trợ WebP. Không kèm theme cha. Sao lưu hoặc dùng staging: seed thay trang chủ, menu, thiết lập Header/Footer và các tùy chọn theme, tên website, tiền tệ và chế độ hiển thị cửa hàng.

1. Cài Flatsome và WooCommerce. Giữ theme cha trong `wp-content/themes/flatsome/`.
2. Chép **thư mục** `child-theme/solana-merch-child/` vào `wp-content/themes/`. Không đổi tên thư mục.
3. Nếu permalink đang là **Plain**, chọn **Settings → Permalinks → Post name**. Sau đó từ thư mục WordPress, chạy:

   ```sh
   wp plugin activate woocommerce
   wp theme activate solana-merch-child
   wp eval-file /path/to/solana-merch/setup/seed.php
   wp rewrite flush
   wp eval-file /path/to/solana-merch/setup/check-demo.php
   ```

   Có thể dùng `--path=/path/to/wordpress` nếu chạy nơi khác. Giữ `setup/` và `ux-builder/` trong cùng gói khi chạy seed. Nếu site dùng Apache và gặp 404, lưu lại **Settings → Permalinks** để WordPress cập nhật cấu hình rewrite.

Seed tạo 2 danh mục và 12 sản phẩm biến thể (6 áo, 6 mũ); áo có S/M/L/XL, mũ có One size, cả hai có Ink/Navy. Mỗi sản phẩm có ảnh chính, ảnh chi tiết và ảnh màu thay thế. Chạy lại cập nhật nội dung demo đã seed, không phải cách đồng bộ nội dung đã chỉnh tay; script dừng nếu gặp slug trang, block, danh mục hoặc SKU trùng với dữ liệu không phải demo.

## Demo và vị trí chỉnh sửa

| Trang | Đường dẫn mặc định | Desktop | Mobile |
|---|---|---|---|
| Homepage | `/` | [Ảnh](previews/home-desktop.webp) | [Ảnh](previews/home-mobile.webp) |
| Category Page — Graphic tees | `/product-category/t-shirts/` | [Ảnh](previews/category-desktop.webp) | [Ảnh](previews/category-mobile.webp) |
| Product Page — Carrier Wave Tee | `/product/carrier-wave/` | [Ảnh](previews/product-desktop.webp) | [Ảnh](previews/product-mobile.webp) |
| Shop | `/shop/` | [Ảnh](previews/shop-desktop.webp) | — |
| Cart | `/cart/` | [Ảnh](previews/cart-desktop.webp) | — |

- **Homepage:** Pages → Home → **Edit with UX Builder**. Dùng Section, Row, Column, Text, Image, Button và Products gốc; template Page – Full Width của Flatsome.
- **Category/Shop:** sản phẩm, breadcrumb, phân trang và sắp xếp do WooCommerce/Flatsome hiển thị. Mở các khối **Signal Supply — Graphic tees / Field caps / The collection** trong **UX Blocks** để sửa banner với UX Builder. Đổi block của từng danh mục tại **Products → Categories → Top Content**; đổi block Shop hoặc bật sidebar/bộ lọc trong **Theme Options → WooCommerce → Product Catalog**.
- **Product:** dùng template Flatsome **WooCommerce → Product Page → Product Layout → No Sidebar**, không có template WooCommerce ghi đè. Chỉnh ảnh, SKU, mô tả, thuộc tính và biến thể trong **Products**. WooCommerce xử lý chọn biến thể, tồn kho, giỏ hàng và thanh toán.
- **Header/Footer:** chỉnh bằng **Theme Options → Header** và **Footer**; menu gốc nằm ở Appearance → Menus, chân trang nội dung nằm trong **UX Blocks → Signal Supply Footer**.
- `ux-builder/*.txt` là bản mã nguồn shortcode; seed điền các token `{{…}}` bằng URL và ID thực. Nếu dán thủ công, đổi token trước khi lưu. Trang sản phẩm dùng layout gốc nên không cần shortcode riêng.

Ảnh WebP sẵn trong `child-theme/solana-merch-child/assets/art/`. Có thể tạo lại bằng `python3 setup/make-art.py` (cần CairoSVG, Pillow và DejaVu Sans Mono). Ảnh là minh họa thiết kế, không phải ảnh chụp sản phẩm thật. Trước khi mở bán, thay hàng mẫu và thiết lập thanh toán, vận chuyển, thuế, tồn kho cùng các chính sách của cửa hàng.
