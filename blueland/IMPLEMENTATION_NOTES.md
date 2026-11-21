# Ghi chú triển khai storefront Pureland

Thư mục `blueland/` chứa storefront WooCommerce có thể chỉnh sửa bằng UX Builder, sử dụng Flatsome bản quyền làm parent theme và theme con `Pureland Child`. Dùng `DESIGN_SPEC.md` làm nguồn tham chiếu chính cho bố cục và các token giao diện.

## Cài đặt cục bộ

Trên một site WordPress đã cài WooCommerce, chạy:

```bash
bash blueland/setup/setup.sh /path/to/flatsome.zip
```

Script cài/kích hoạt Flatsome nếu cần, chép theme con và ảnh đã tạo vào WordPress, rồi seed dữ liệu. Có thể chạy lại script mà không tạo trùng nội dung đã seed. ZIP Flatsome là phần mềm có bản quyền do người dùng cung cấp; giữ file này bên ngoài repository.

Mã nguồn nội dung UX Builder nằm trong `ux-builder/`. Các shortcode tương ứng cũng được đưa vào trang chủ và UX Blocks: các section trang chủ, mega menu, bố cục sản phẩm, ô khuyến mãi danh mục, đánh giá mẫu, huy hiệu, CTA và footer.

## Cấu hình storefront Hoa Kỳ

Seed đặt ngôn ngữ site là `en_US`, địa chỉ cửa hàng tại San Francisco, vị trí mặc định `US:CA`, chỉ bán và giao hàng trong Hoa Kỳ, tiền tệ USD với ký hiệu `$` bên trái, hai chữ số thập phân, đơn vị cân nặng pound, kích thước inch và múi giờ New York. Thuế WooCommerce được bật cùng các mức thuế mẫu: California 7.25%, New York 4% và Texas 6.25%. Vùng giao hàng United States có giao hàng miễn phí từ $50 và phí cố định $5.95.

Các mức thuế chỉ phục vụ kiểm thử. Trước khi đưa lên production, thay bảng mẫu và cấu hình dịch vụ thuế bán hàng được duy trì như WooCommerce Tax, TaxJar hoặc Avalara, đồng thời thiết lập đúng nexus và khai báo thuế.

## Tích hợp production và dữ liệu demo

- Autoship hiện chỉ là lựa chọn trên storefront, giảm 20% giá trong giỏ và ghi metadata vào đơn hàng. Tính năng này **không** tự tạo đơn định kỳ; cần WooCommerce Subscriptions hoặc dịch vụ tương đương để xử lý thanh toán định kỳ thật.
- Điểm thưởng trên trang sản phẩm chỉ để hiển thị minh họa; không có hệ thống cộng/trừ điểm ở phía sau.
- Form newsletter lưu email gửi lên trong một WordPress option. Trước khi ra mắt, thay handler bằng dịch vụ email production (ví dụ Mailchimp) và áp dụng chính sách đồng thuận, lưu trữ phù hợp.
- Sản phẩm, đánh giá, ảnh và dấu hiệu chứng nhận là nội dung demo chung. Đánh giá seed được tạo thành comment WooCommerce để kiểm thử UX, không được trình bày như đánh giá khách hàng thật hoặc đã xác minh ở production. Hình chứng nhận chỉ là placeholder, không phải tuyên bố chứng nhận bên thứ ba.
- Form liên hệ hiện là mẫu demo cục bộ. Trước khi ra mắt, thay bằng tích hợp chăm sóc khách hàng được phê duyệt.
- Ảnh sản phẩm và lifestyle được tạo cục bộ bằng `setup/make-placeholders.py`; thay bằng ảnh production có giấy phép phù hợp.
- Terms, Privacy, Returns và nội dung pháp lý khác chỉ là bản nháp cho demo cục bộ, không phải tư vấn pháp lý. Nhờ luật sư rà soát chính sách production, gồm các yêu cầu bảo vệ quyền riêng tư theo từng tiểu bang.
