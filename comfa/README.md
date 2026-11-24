# Comfa: giao diện kiểu bombas.com cho Flatsome + WooCommerce

Child theme Flatsome dựng lại bố cục và trải nghiệm mua hàng của bombas.com cho shop áo in, mũ, hoodie và tất. Tên thương hiệu, logo, ảnh và nội dung là dữ liệu mẫu tự tạo, không dùng tài sản của Bombas.

Ảnh xem trước nằm trong `previews/`. Chi tiết thiết kế: `DESIGN_SPEC.md`.

## Cấu trúc

| Thư mục | Nội dung |
| --- | --- |
| `child-theme/comfa-child/` | Child theme: CSS, JS và các hook WooCommerce. Không ghi đè template nào của Flatsome hay WooCommerce. |
| `ux-builder/` | Nội dung UX Builder: trang chủ, UX Block (footer, mega menu, khối giới thiệu ở trang sản phẩm, 404) và các trang Mission, Help & FAQ, Shipping & Returns, Size Guide, Contact, Privacy Policy, Terms of Service, Return & Refund Policy, DMCA, Intellectual Property Claim Form. |
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
- **Trang giỏ hàng (Bag):** dùng nguyên template Cart của Flatsome, chỉ đổi bố cục bằng CSS và hook. Tự cập nhật khi đổi số lượng, khối tổng tiền bám theo khi cuộn, số thứ tự các bước, dòng cam kết dưới ô mã giảm giá: Flatsome > Theme Options > WooCommerce > Cart. Nội dung giỏ trống: UX Block `bag-empty`. Mục "Goes well with": Cross-sells của từng sản phẩm (Linked Products).
- **Trang thanh toán (Checkout):** trang WooCommerce dùng shortcode `[woocommerce_checkout]`. Bố cục theo mẫu Checkout Glossier/Dewier: email tách khỏi địa chỉ, các bước Information → Shipping → Payment có phần xem lại thông tin và nút chỉnh sửa; mã giảm giá và tiến độ miễn phí vận chuyển nằm trong tóm tắt đơn. Desktop có hai cột; mobile mở tóm tắt bằng nút “Show order summary”. Các biểu mẫu, vận chuyển, mã giảm giá, cổng thanh toán và trang xác nhận vẫn do WooCommerce xử lý. Không cần plugin checkout hay thiết lập Theme Options riêng.
- **Màu, font, header, layout danh mục và sản phẩm:** Flatsome > Theme Options.
- **Dải chữ chạy trên trang chủ:** shortcode `[cf_ticker text="Câu 1|Câu 2"]`. **Hàng "Shop by Colour":** shortcode `[cf_color_nav]`, tự lấy các màu đang có sản phẩm và dẫn tới trang Shop đã lọc theo màu đó.
- **Sản phẩm:** sản phẩm biến thể chuẩn của WooCommerce với ba thuộc tính Pack, Color, Size. Giá gói là giá khuyến mãi của biến thể; nhãn "10% Pack Savings" được tính từ đó. Gói mặc định của mỗi sản phẩm là gói rẻ nhất (Single); thẻ sản phẩm hiện giá này, và sắp xếp, lọc theo giá cũng tính theo giá thấp nhất. Ô màu và ô size dùng tính năng Swatches có sẵn của Flatsome.
- **Sản phẩm in theo yêu cầu (một thiết kế, nhiều loại sản phẩm):** sản phẩm biến thể với ba thuộc tính Product (`pa_style`, 14 loại: Unisex T-Shirt, Hoodie, Sweatshirt, Ceramic Mug ...), Color (`pa_color`) và Size (`pa_size`, XS–5XL và 11oz/15oz). Mỗi biến thể là một tổ hợp loại × màu × size có thật, giá = giá của loại + phụ thu size (2XL +$2, 3XL +$3, 4XL +$4, 5XL +$5, 15oz +$2). Cả ba thuộc tính chạy hoàn toàn trên cơ chế Attributes/Swatches của Flatsome: kiểu swatch đặt ở Products → Attributes (Product = Image, Color = Color, Size = Label), kích thước và hình dạng ô đặt ở Swatch size / Swatch shape của từng thuộc tính, bố cục nhãn và màu viền ô đang chọn đặt ở Theme Options → WooCommerce → Product Page → Swatches. Lựa chọn không đi cùng các lựa chọn khác được WooCommerce vô hiệu và Flatsome gạch chéo; bấm vào ô gạch chéo sẽ chuyển sang lựa chọn đó và giữ lại những lựa chọn còn hợp lệ. Để cơ chế này chạy với thiết kế có hàng trăm biến thể, theme nâng ngưỡng `woocommerce_ajax_variation_threshold` lên 1000; các dữ liệu ảnh và giá trùng lặp chỉ lưu một lần cho mỗi form và được khôi phục trước khi WooCommerce chạy. Thêm hay xóa biến thể trong wp-admin là đủ, không có dữ liệu riêng nào của theme cần cập nhật. Danh sách loại, màu, size và giá của dữ liệu mẫu nằm trong `styles` và `designs` của `setup/catalog.json`.
- **Nhãn "In Demand" / "New"** và dòng Product Highlight: custom field `_cf_badge` và `_cf_highlight` của sản phẩm. Ghi chú dưới size: `_cf_size_notes`.
- **Ngưỡng freeship, mã chào mừng, dòng chữ trên thanh khuyến mãi:** filter `cf_settings` (xem `inc/setup.php`).
- **Thêm thiết kế in mẫu:** thêm ảnh thiết kế một màu (nền đen, nét trắng) vào `setup/art-src/print/`, khai báo trong `designs` của `setup/catalog.json`, rồi chạy `make-art.py` và `setup.sh`.
- **Thêm màu sản phẩm mẫu:** sửa `setup/catalog.json`, chạy `python3 comfa/setup/make-art.py` (cần Pillow và numpy) rồi chạy lại `setup.sh`.

## Trước khi mở bán

- Thay ảnh, mô tả, đánh giá mẫu và các trang chính sách bằng nội dung thật.
- Số "250,000+ Items Given" và chương trình "One Bought = One Given" là nội dung mẫu; chỉ giữ nếu shop thật sự có chương trình này.
- Form đăng ký nhận tin lưu mỗi email thành một mục trong menu Subscribers của wp-admin và gọi action `cf_newsletter_signup`; cần nối với dịch vụ email của bạn (kể cả bước xác nhận email nếu cần).
- Form báo cáo vi phạm sở hữu trí tuệ (`/ip-claim/`, shortcode `[cf_ip_claim_form]`) lưu mỗi báo cáo thành một mục trong menu IP Claims của wp-admin (chỉ quản trị viên xem được) và gửi email tới địa chỉ quản trị của site; đổi người nhận bằng filter `cf_ip_claim_recipient`. Cần cấu hình SMTP để email được gửi đi.
- Privacy Policy, Terms of Service, Return & Refund Policy và DMCA là văn bản mẫu: điền các chỗ trong `[ngoặc vuông]` (tên pháp nhân, địa chỉ, quốc gia, ngày cập nhật), thay email `privacy@example.com` / `legal@example.com`, sửa điều kiện đổi trả theo chính sách thật và nhờ người có chuyên môn rà soát.
- `seed.php` đặt trang Privacy Policy mẫu làm trang chính sách bảo mật trong Settings → Privacy, trừ khi shop đã chọn một trang đã xuất bản. Bản nháp "Privacy Policy" mặc định của WordPress được chuyển sang đường dẫn `privacy-policy-draft` (vẫn là bản nháp). Link "Privacy" ở footer chỉ hiện khi trang được chọn đã xuất bản. Trong nội dung trang, dùng `[cf_privacy_link text="Privacy Policy"]` để link luôn trỏ tới trang được chọn (trang Terms of Service dùng cách này).
- Giá, phụ thu size, bảng màu và số đo trong Size Guide của sản phẩm in theo yêu cầu là dữ liệu mẫu; thay bằng số liệu của xưởng in bạn dùng. Một thiết kế bán trên cả 14 loại có khoảng 950 biến thể.
- Cấu hình vận chuyển và cổng thanh toán thật. Dữ liệu mẫu bật COD, phí giao $6, freeship từ $75 và mã `HELLO10` giảm 10%.
