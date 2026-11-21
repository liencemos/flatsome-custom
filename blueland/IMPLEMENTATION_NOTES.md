# Ghi chú triển khai storefront Pureland

Thư mục `blueland/` chứa storefront WooCommerce có thể chỉnh sửa bằng UX Builder, sử dụng Flatsome bản quyền làm parent theme và theme con `Pureland Child`. Dùng `DESIGN_SPEC.md` làm nguồn tham chiếu chính cho bố cục và các token giao diện.

## Cài đặt cục bộ

Trên một site WordPress đã cài WooCommerce, chạy:

```bash
bash blueland/setup/setup.sh /path/to/flatsome.zip
```

Script cài/kích hoạt Flatsome nếu cần, chép theme con và ảnh đã tạo vào WordPress, rồi seed dữ liệu. Có thể chạy lại script mà không tạo trùng nội dung đã seed. ZIP Flatsome là phần mềm có bản quyền do người dùng cung cấp; giữ file này bên ngoài repository.

Mã nguồn nội dung UX Builder nằm trong `ux-builder/`. Các shortcode tương ứng cũng được đưa vào trang chủ và UX Blocks: các section trang chủ, mega menu, bố cục sản phẩm, ô khuyến mãi danh mục, đánh giá mẫu, huy hiệu, CTA và footer.

## Cài đặt trên hosting thật

1. Sao lưu site, tạo staging và cài WordPress cùng WooCommerce. Cài Flatsome qua **Giao diện → Thêm giao diện → Tải giao diện lên** hoặc SFTP vào `wp-content/themes/flatsome/`; kích hoạt parent theme. Cài/kích hoạt child theme bằng cách tải ZIP `blueland-child` qua trang quản trị hoặc chép thư mục `blueland/child-theme/blueland-child/` bằng SFTP vào `wp-content/themes/blueland-child/`.
2. Chép `blueland/child-theme/blueland-child/assets/images/` vào `wp-content/uploads/blueland/`, `blueland/ux-builder/` vào `wp-content/uploads/ux-builder/`, và `blueland/setup/seed.php` vào `wp-content/uploads/blueland-seed/seed.php`. Bảo đảm PHP/web server có quyền đọc các tệp.
3. Có thể nạp cấu hình và dữ liệu mẫu bằng WP-CLI cài trên server (không dùng Docker):

   ```bash
   wp --path=/var/www/html eval-file /var/www/html/wp-content/uploads/blueland-seed/seed.php
   ```

   Để chạy toàn bộ `setup.sh` trực tiếp trên server, dùng `WP_CLI_CMD` làm lệnh WP-CLI và `WP_PATH` làm thư mục gốc WordPress mà user đang chạy script có quyền ghi:

   ```bash
   WP_CLI_CMD='wp --path=/var/www/html' \
   WP_PATH=/var/www/html \
   bash blueland/setup/setup.sh /path/to/flatsome.zip
   ```

   Script giữ chế độ Docker hiện tại làm mặc định. Chế độ hosting dùng `cp` vào `WP_PATH`, không gọi `docker cp`; `WP_CLI_CMD` được tách thành các đối số mà không dùng `eval`. Nếu đã cài Flatsome trong trang quản trị/SFTP thì script sẽ không cài lại parent theme.
4. Nạp nội dung UX Builder từ `ux-builder/*.txt` và `ux-builder/blocks/*.txt`: trong WordPress mở UX Blocks, tạo hoặc chỉnh từng block, chuyển sang chế độ shortcode/text và dán nội dung tệp tương ứng. Tạo/chỉnh trang Home bằng nội dung `ux-builder/homepage.txt`, đặt trang này làm trang chủ tĩnh, rồi mở bằng UX Builder để kiểm tra và lưu. Nếu dùng `seed.php`, các trang và UX Blocks demo được tạo tự động; vẫn có thể mở/chỉnh chúng trong UX Builder.
5. Hoàn tất cấu hình production, kiểm tra checkout và quyền truy cập trong staging trước khi chuyển traffic. Không đưa ZIP Flatsome, thông tin bí mật hoặc dữ liệu demo lên repository.

## Cấu hình storefront Hoa Kỳ

Seed đặt ngôn ngữ site là `en_US`, địa chỉ cửa hàng tại San Francisco, vị trí mặc định `US:CA`, chỉ bán và giao hàng trong Hoa Kỳ, tiền tệ USD với ký hiệu `$` bên trái, hai chữ số thập phân, đơn vị cân nặng pound, kích thước inch và múi giờ New York. Thuế WooCommerce được bật cùng các mức thuế mẫu: California 7.25%, New York 4% và Texas 6.25%. Vùng giao hàng United States có giao hàng miễn phí từ $50 và phí cố định $5.95.

Các mức thuế chỉ phục vụ kiểm thử. Trước khi đưa lên production, thay bảng mẫu và cấu hình dịch vụ thuế bán hàng được duy trì như WooCommerce Tax, TaxJar hoặc Avalara, đồng thời thiết lập đúng nexus và khai báo thuế.

Seed dùng đơn vị ngày `F j, Y`, giờ `g:i a`, tuần bắt đầu Chủ Nhật và locale WooCommerce Hoa Kỳ cho trường địa chỉ/tiểu bang/ZIP trong checkout. Trang Terms, Privacy (có nhắc CCPA) và Returns (miễn phí trong 100 ngày) là bản nháp hướng thị trường Mỹ; không bật cookie notice vì không cần cho storefront US mẫu. Cần luật sư rà soát và xác nhận chính sách trước khi bán thật.

## Tích hợp production và dữ liệu demo

- Autoship hiện chỉ là lựa chọn trên storefront, giảm 20% giá trong giỏ và ghi metadata vào đơn hàng. Tính năng này **không** tự tạo đơn định kỳ; cần WooCommerce Subscriptions hoặc dịch vụ tương đương để xử lý thanh toán định kỳ thật.
- Điểm thưởng trên trang sản phẩm chỉ để hiển thị minh họa; không có hệ thống cộng/trừ điểm ở phía sau.
- Form newsletter lưu email gửi lên trong một WordPress option. Trước khi ra mắt, thay handler bằng dịch vụ email production (ví dụ Mailchimp) và áp dụng chính sách đồng thuận, lưu trữ phù hợp.
- Sản phẩm, đánh giá, ảnh và dấu hiệu chứng nhận là nội dung demo chung. Đánh giá seed được tạo thành comment WooCommerce để kiểm thử UX, không được trình bày như đánh giá khách hàng thật hoặc đã xác minh ở production. Hình chứng nhận chỉ là placeholder, không phải tuyên bố chứng nhận bên thứ ba.
- Form liên hệ hiện là mẫu demo cục bộ. Trước khi ra mắt, thay bằng tích hợp chăm sóc khách hàng được phê duyệt.
- Ảnh sản phẩm và lifestyle được tạo cục bộ bằng `setup/make-placeholders.py`; thay bằng ảnh production có giấy phép phù hợp.
- Terms, Privacy, Returns và nội dung pháp lý khác chỉ là bản nháp cho demo cục bộ, không phải tư vấn pháp lý. Nhờ luật sư rà soát chính sách production, gồm các yêu cầu bảo vệ quyền riêng tư theo từng tiểu bang.

## Rà soát tính năng

| Tính năng | Trạng thái | Ghi chú/plugin đề xuất |
|---|---|---|
| Mega menu | Demo | Có menu/UX Block; rà soát accessibility và nội dung trước khi dùng thật. |
| Search | Demo | Tìm kiếm WooCommerce cơ bản; có thể bổ sung SearchWP nếu cần tìm kiếm nâng cao. |
| Cart drawer + free shipping bar | Demo | Drawer và ngưỡng giao miễn phí $50 là giao diện mẫu; kiểm tra lại cùng phí ship/thực thuế. |
| Quick add | Demo | Thêm nhanh sản phẩm đơn giản; biến thể chuyển sang trang/chọn tuỳ chọn. |
| Filters/sort | Demo | Bộ lọc danh mục/loại và sắp xếp; thử với taxonomy và catalog production. |
| Product badges | Demo | Huy hiệu dựa trên taxonomy/meta mẫu; rà soát nhãn và điều kiện hiển thị. |
| Autoship | Cần plugin | Chọn mua định kỳ cần WooCommerce Subscriptions hoặc dịch vụ subscription tương đương. |
| Rewards | Chưa tích hợp | Có thể dùng WooCommerce Points and Rewards hoặc YITH WooCommerce Points and Rewards. |
| Reviews with photos | Chưa tích hợp | Có thể dùng CusRev (Customer Reviews for WooCommerce) hoặc Judge.me; xin phép dùng ảnh/review. |
| Instagram feed | Placeholder | Tích hợp Smash Balloon Instagram Feed và kết nối tài khoản được uỷ quyền. |
| Newsletter | Demo | Form hiện lưu dữ liệu cục bộ; dùng Mailchimp for WooCommerce và quy trình đồng ý nhận email. |
| Sales tax | Mức mẫu | Dùng WooCommerce Tax, TaxJar hoặc Avalara; cấu hình nexus và xác nhận nghĩa vụ thuế. |
| Payments | Chưa cấu hình production | Tích hợp WooCommerce Payments, Stripe và/hoặc PayPal; xác thực webhook và hoàn tiền. |
| Shipping labels | Chưa tích hợp | Có thể dùng WooCommerce Shipping hoặc ShipStation; xác nhận hãng vận chuyển và khu vực giao hàng. |
| SEO | Chưa tích hợp | Có thể dùng Rank Math hoặc Yoast SEO; khai báo metadata, sitemap và redirects. |
| Speed | Cần tối ưu production | Thiết lập cache trang/object theo hosting, CDN, nén ảnh/WebP và kiểm thử Core Web Vitals. |
| Accessibility | Cần rà soát | Kiểm tra bàn phím/focus, tương phản, nhãn biểu mẫu, heading order, alt text, zoom và screen reader; kiểm thử thủ công cùng công cụ tự động. |
