# FORM / EDIT — Minimal Gallery cho Flatsome

FORM / EDIT là thiết kế mới theo hướng **Minimal Gallery**: lưới Thụy Sĩ, khoảng trắng rộng, ảnh sản phẩm lớn và đường kẻ mảnh. Giao diện chỉ có Light Mode. Trang chủ mở bằng một bố cục ảnh studio bất đối xứng, còn danh mục và trang sản phẩm hoạt động như một gallery. Nền trang và phông ảnh dùng trắng hoặc xám trung tính. Nội dung demo dùng tiếng Anh (Mỹ), tiền tệ USD.

| Token | Mã màu | Vai trò |
|---|---|---|
| Ink Black | `#000814` | Chữ trên nút vàng và họa tiết nhỏ |
| Prussian Blue | `#001D3D` | Mũi tên trong chỉ mục và họa tiết nhỏ |
| Oxford Navy | `#003566` | Logo, viền focus và họa tiết nhỏ |
| School Bus Yellow | `#FFC300` | Nút chính và dấu vuông chỉ mục |
| Gold | `#FFD60A` | Trạng thái hover và chi tiết hình in |

Typography trong **Flatsome → Theme Options → Style → Typography**: Inter Tight (tiêu đề), Inter (nội dung và menu), Bodoni Moda Italic (điểm nhấn biên tập). Bản demo tải Google Fonts qua CDN; có thể tắt tùy chọn CDN để Flatsome lưu font cục bộ.

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

Seed tạo 2 danh mục và 12 sản phẩm biến thể (6 áo, 6 mũ); áo có S/M/L/XL, mũ có One size, cả hai có Ink/Stone. Mỗi sản phẩm có ảnh chính, ảnh chi tiết và ảnh màu thay thế. Nếu đang dùng bản demo trước, giữ nguyên thư mục theme và chạy lại seed trên staging: SKU được đổi sang định dạng FORM / EDIT (ví dụ `FE-BLOCK-01-TEE`) mà không đổi ID hoặc URL sản phẩm. Seed giữ ảnh đã nhập trước đó trong Media Library để không làm hỏng các liên kết cũ. Seed không dành để đồng bộ nội dung đã chỉnh tay; script dừng nếu gặp slug trang, block, danh mục hoặc SKU trùng với dữ liệu không phải demo.

## Demo và vị trí chỉnh sửa

| Trang | Đường dẫn mặc định | Desktop | Mobile |
|---|---|---|---|
| Homepage | `/` | [Ảnh](previews/home-desktop-light.webp) | [Ảnh](previews/home-mobile-light.webp) |
| Category Page — Printed tees | `/product-category/t-shirts/` | [Ảnh](previews/category-desktop-light.webp) | [Ảnh](previews/category-mobile-light.webp) |
| Product Page — Block 01 Tee | `/product/carrier-wave/` | [Ảnh](previews/product-desktop-light.webp) | [Ảnh](previews/product-mobile-light.webp) |
| Shop | `/shop/` | [Ảnh](previews/shop-desktop-light.webp) | — |
| Cart | `/cart/` | [Ảnh](previews/cart-desktop-light.webp) | — |
| Mini-cart | Mở biểu tượng túi hàng | [Ảnh](previews/mini-cart-desktop-light.webp) | — |

- **Homepage:** Pages → Home → **Edit with UX Builder**. Dùng Section, Row, Column, Text, Image, Button và Products gốc; template Page – Full Width của Flatsome.
- **Category/Shop:** lưới hiển thị ảnh thứ hai khi rê chuột bằng thiết lập **Back Image – Fade In** của Flatsome. Mở **FORM / EDIT — Printed tees / Embroidered caps / Edition 01** trong **UX Blocks** để sửa tiêu đề, chỉ mục và nội dung biên tập. WooCommerce giữ breadcrumb, phân trang và sắp xếp gốc.
- **Product:** dùng layout **Stacked Right** của Flatsome: ảnh xếp dọc và cột thông tin cố định khi cuộn trên desktop. Không ghi đè template WooCommerce. Chỉnh ảnh, SKU, mô tả, thuộc tính và biến thể trong **Products**.
- **Cart:** seed chuyển block WooCommerce Cart cấp ngoài cùng sang shortcode gốc `[woocommerce_cart]` để bộ đếm giỏ của Flatsome cập nhật khi đổi số lượng hoặc xóa sản phẩm. Seed lưu toàn bộ nội dung trước lần chuyển đổi đầu tiên trong post meta `_signal_original_cart_content` (bao gồm các block con tùy chỉnh) để có thể khôi phục; các block bên ngoài block giỏ hàng và trang thanh toán không bị thay thế.
- **Header/Footer:** chỉnh bằng **Theme Options → Header** và **Footer**; menu nằm ở **Appearance → Menus**, chân trang nằm trong **UX Blocks → FORM / EDIT Footer**.
- `ux-builder/*.txt` là bản mã nguồn shortcode; seed điền các token `{{…}}` bằng URL và ID thực. Nếu dán thủ công, đổi token trước khi lưu. Trang sản phẩm dùng layout gốc nên không cần shortcode riêng.

Ảnh WebP sẵn trong `child-theme/solana-merch-child/assets/art/`. Có thể tạo lại bằng `python3 setup/make-art.py` (cần CairoSVG, Pillow và Liberation Sans). Đây là render minh họa studio, không phải ảnh chụp sản phẩm thật. Trước khi mở bán, thay hàng mẫu và thiết lập thanh toán, vận chuyển, thuế, tồn kho cùng các chính sách của cửa hàng.
