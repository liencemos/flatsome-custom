# Comfa: giao diện kiểu bombas.com cho Flatsome + WooCommerce

Child theme Flatsome dựng lại bố cục và trải nghiệm mua hàng của bombas.com cho shop áo in, mũ, hoodie và tất. Tên thương hiệu, logo, ảnh và nội dung là dữ liệu mẫu tự tạo, không dùng tài sản của Bombas.

Ảnh xem trước nằm trong `previews/`. Chi tiết thiết kế: `DESIGN_SPEC.md`.

## Cấu trúc

| Thư mục | Nội dung |
| --- | --- |
| `child-theme/comfa-child/` | Child theme: CSS, JS và các hook WooCommerce. Không ghi đè template nào của Flatsome hay WooCommerce. |
| `ux-builder/` | Nội dung UX Builder: trang chủ, UX Block (footer, mega menu, khối giới thiệu ở trang sản phẩm, 404) và các trang Mission, Help, Shipping & Returns, Size Guide, Contact. |
| `demo-images/` | Ảnh mẫu (sản phẩm theo từng màu, ảnh lifestyle, logo). |
| `setup/` | `setup.sh`, `seed.php`, `check-demo.php`, `catalog.json` và `make-art.py` (tạo lại ảnh sản phẩm theo màu). |

## Cài đặt

Cần có sẵn theme Flatsome (bản có license, không kèm trong repo) và plugin WooCommerce đã kích hoạt. Nên cài trên site mới: script đổi tên site, tiền tệ sang USD, trang chủ và Theme Options.

```sh
WP_CLI_CMD='wp --path=/var/www/html' WP_PATH=/var/www/html bash comfa/setup/setup.sh
```

Script chép child theme, ảnh và nội dung UX Builder vào site, kích hoạt `comfa-child`, chạy `seed.php` rồi `check-demo.php`. Chạy lại được nhiều lần: sản phẩm khớp theo SKU, còn trang và UX Block bạn đã sửa thì được giữ nguyên.

## Chỉnh sửa

- **Trang chủ, footer, mega menu, 404, khối giới thiệu sản phẩm (`pdp-story`):** sửa bằng UX Builder.
- **Màu, font, header, layout danh mục và sản phẩm:** Flatsome > Theme Options.
- **Sản phẩm:** sản phẩm biến thể chuẩn của WooCommerce với ba thuộc tính Pack, Color, Size. Giá gói là giá khuyến mãi của biến thể; nhãn "10% Pack Savings" được tính từ đó. Ô màu và ô size dùng tính năng Swatches có sẵn của Flatsome.
- **Nhãn "In Demand" / "New"** và dòng Product Highlight: custom field `_cf_badge` và `_cf_highlight` của sản phẩm. Ghi chú dưới size: `_cf_size_notes`.
- **Ngưỡng freeship, mã chào mừng, dòng chữ trên thanh khuyến mãi:** filter `cf_settings` (xem `inc/setup.php`).
- **Thêm màu sản phẩm mẫu:** sửa `setup/catalog.json`, chạy `python3 comfa/setup/make-art.py` (cần Pillow và numpy) rồi chạy lại `setup.sh`.

## Trước khi mở bán

- Thay ảnh, mô tả, đánh giá mẫu và các trang chính sách bằng nội dung thật.
- Số "250,000+ Items Given" và chương trình "One Bought = One Given" là nội dung mẫu; chỉ giữ nếu shop thật sự có chương trình này.
- Form đăng ký nhận tin lưu mỗi email thành một mục trong menu Subscribers của wp-admin và gọi action `cf_newsletter_signup`; cần nối với dịch vụ email của bạn (kể cả bước xác nhận email nếu cần).
- Xuất bản trang chính sách bảo mật và chọn nó trong Settings → Privacy; link "Privacy" ở footer chỉ hiện khi trang này đã xuất bản.
- Cấu hình vận chuyển và cổng thanh toán thật. Dữ liệu mẫu bật COD, phí giao $6, freeship từ $75 và mã `HELLO10` giảm 10%.
