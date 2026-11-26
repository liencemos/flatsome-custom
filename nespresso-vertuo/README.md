# Cửa hàng cà phê Vertuo · Flatsome + WooCommerce

Bản dựng cửa hàng tiếng Anh dùng danh mục Vertuo của Nespresso Mỹ và giao diện lấy cảm hứng từ Keurig: thanh thông báo vàng, đầu trang màu kem, khu vực giới thiệu xanh đậm, danh mục dạng thẻ và trang chi tiết có nút chọn cỡ gói. Thông số và lời mô tả sản phẩm hiển thị theo nguồn tiếng Anh; bảng tra cứu vẫn bằng tiếng Việt. Đây là demo không chính thức; không dùng logo, ảnh hoặc chương trình khuyến mãi của Keurig.

| Tệp | Công dụng |
|---|---|
| [THONG-SO.md](THONG-SO.md) | Bảng thông số tiếng Việt, mỗi SKU một dòng |
| [data/catalog.json](data/catalog.json) | 98 SKU, 16 nhóm, thứ tự và thông số từ trang danh mục và trang chi tiết |
| [data/woocommerce-products.csv](data/woocommerce-products.csv) | Giá theo đơn vị bán (hộp/combo/phụ kiện), thuộc tính hiển thị, danh mục và URL nguồn |
| [ux-builder/catalog.txt](ux-builder/catalog.txt) | Trang Flatsome dạng shortcode |
| [child-theme/vertuo-child/](child-theme/vertuo-child/) | Child theme, giao diện danh mục và bộ lọc/sắp xếp |

## Cài vào một WordPress demo

Sao lưu trước và dùng trên một bản WordPress thử nghiệm. Cần WP-CLI, Flatsome có giấy phép và WooCommerce. Chọn **USD** trong WooCommerce trước khi chạy. Lệnh có thể cài/kích hoạt WooCommerce và sẽ kích hoạt child theme; không đổi trang chủ hoặc đường dẫn tĩnh.

```sh
# WordPress có WP-CLI (có thể đặt WP_CLI_CMD nếu không dùng lệnh wp):
WP_PATH=/path/to/wordpress bash nespresso-vertuo/setup/setup.sh

# Stack Docker của repo có wrapper ~/wp-local/wp.sh:
bash nespresso-vertuo/setup/setup.sh /path/to/flatsome.zip
```

Đường dẫn ZIP có thể bỏ qua khi Flatsome đã cài. Đặt `WP_ADMIN` thành tên người dùng quản trị WordPress nếu khác `admin`. Dùng ngôn ngữ trang WordPress **English (United States)** cho chuỗi giao diện mặc định của Flatsome và WooCommerce. Sau cài đặt, script in URL trang **Vertuo Coffee Pods**. Chạy lại sẽ cập nhật thứ tự theo từng bộ sưu tập và tự đổi thông số tiếng Việt của gói cũ sang tiếng Anh mà không thay đổi giá hoặc tồn kho hiện có. Trang danh mục tiếng Việt chỉ tự chuyển sang bản tiếng Anh nếu chưa chỉnh sửa. Những SKU và trang đã nâng cấp không bị ghi đè mặc định. Để ghi đè toàn bộ các trường có trong CSV và nội dung trang danh mục, đặt `VERTUO_REFRESH=1` trước lệnh setup; hãy sao lưu trước.

Lệnh setup áp dụng cấu hình giao diện Flatsome mới một lần và cập nhật bố cục danh mục cũ nếu nội dung chưa được chỉnh sửa. Dùng URL trang được in ra để xem trang giới thiệu; mở mục **Coffee Pods** trên thanh điều hướng để vào chế độ danh mục.

Setup cài và kích hoạt **Contact Form 7**, tạo form **Contact Us** (trang riêng và mở trực tiếp từ footer), form **Newsletter Signup** ở footer và các trang hỗ trợ tiếng Anh. Chạy lại không ghi đè form và trang hiện có. Hai form mặc định gửi tới email quản trị WordPress; hãy kiểm tra mục **Contact → Contact Forms → Mail** và cấu hình gửi thư trước khi mở trang. Newsletter chỉ gửi yêu cầu đăng ký qua email, chưa tự ghi vào danh sách gửi thư. Rà soát nội dung mẫu về giao hàng, đổi trả, quyền riêng tư và điều khoản trước khi bán hàng.

Footer được lưu trong WordPress: vào **UX Blocks → Footer → Edit with UX Builder** để sửa bố cục, nội dung và từng liên kết. Flatsome chọn block tại **Appearance → Customize → Footer → Custom Footer Block**. Mẫu cài đặt nằm ở `ux-builder/footer.txt`; chạy lại setup không ghi đè block đã sửa hoặc block khác đang được chọn. Giữ lớp `vertuo-footer-contact` trên hàng form để không hiện form hai lần trên trang Contact Us.

Kiểm tra trên WordPress đã cài gói bằng `wp eval-file nespresso-vertuo/setup/check-support.php contact` và chạy lại với `newsletter`. Các lệnh kiểm tra phản hồi CF7 mà không gửi email thật. Dùng tham số `footer` để kiểm tra form dự phòng trong footer.

**Cài thủ công:** kích hoạt child theme; nhập CSV tại **Products → Import** với các cột mặc định; tạo trang **Vertuo Coffee Pods** với mẫu **Page - Full Width** và dán `ux-builder/catalog.txt` trong trình soạn thảo mã trước khi chỉnh sửa bằng UX Builder. Để áp dụng cấu hình Flatsome, tạo biến thể Pack Size và lấy đúng thứ tự riêng theo từng bộ sưu tập, chạy `setup/setup.sh`; chỉ nhập CSV không tạo biến thể.

## Giá, ảnh và dữ liệu chưa công bố

- WooCommerce nhập giá niêm yết chuẩn, không phát lại ưu đãi Black Friday, giá đăng ký hoặc giảm giá trực tiếp từ Nespresso. Giá quan sát được lưu trong JSON và metadata CSV.
- Setup tạo 4 biến thể mua hàng **50 Pods, 100 Pods, 150 Pods, 200 Pods** cho mỗi loại viên cà phê (mặc định **50 Pods**). Giá biến thể = giá niêm yết hộp gốc ÷ số viên trong hộp × số viên chọn, làm tròn đến cent. Danh mục hiện các nút dẫn đến trang sản phẩm với cỡ gói đã chọn. Combo và phụ kiện vẫn là sản phẩm đơn giản. Chạy lại setup để nâng cấp bản cũ; giá biến thể đã có chỉ được đặt lại khi dùng `VERTUO_REFRESH=1`.
- Dung tích trên cửa hàng giữ cách ghi của Nespresso, gồm cả ml và oz khi nguồn có ghi cả hai. Các quy đổi trong bảng thông số tiếng Việt không thay thế số đo trên cửa hàng.
- Setup tải ảnh sản phẩm từ URL nguồn trong JSON vào **Media Library** và đặt làm ảnh đại diện; không hotlink hoặc commit tệp ảnh. Setup giữ ảnh đại diện đã có và tái sử dụng ảnh đã tải. Nếu tải lỗi, WooCommerce dùng ảnh giữ chỗ; chạy lại setup để thử lại. Đặt `VERTUO_SKIP_IMAGES=1` để không tải ảnh; nhập CSV thủ công cũng không tải ảnh.
- Ảnh sản phẩm thuộc bản quyền của Nespresso hoặc chủ sở hữu tương ứng. Chỉ dùng cho demo nếu được phép; cần có quyền sử dụng trước khi đưa ảnh lên cửa hàng công khai.
- Tình trạng tồn kho chỉ là dữ liệu tại thời điểm quét; không đồng bộ. Không dùng dữ liệu này như tồn kho thực tế.
- Dữ liệu trống là thông số không áp dụng hoặc nguồn chưa công bố. Một SKU xuất hiện trong nhiều bộ sưu tập chỉ tạo một sản phẩm WooCommerce và một dòng trong bảng tóm tắt.
