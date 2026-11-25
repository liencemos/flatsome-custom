# Cửa hàng cà phê Vertuo · Flatsome + WooCommerce

Bản dựng cửa hàng tiếng Anh dựa trên bố cục danh mục Vertuo của Nespresso Mỹ: dải giới thiệu, banner lớn, bộ lọc bộ sưu tập, sắp xếp và lưới sản phẩm WooCommerce. Thông số và lời mô tả sản phẩm hiển thị theo nguồn tiếng Anh; bảng tra cứu vẫn bằng tiếng Việt. Đây là demo, không phải website chính thức của Nespresso. Nội dung banner được viết mới; không sao chép hình ảnh, logo hoặc chương trình ưu đãi đang chạy.

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

Đường dẫn ZIP có thể bỏ qua khi Flatsome đã cài. Đặt `WP_ADMIN` thành tên người dùng quản trị WordPress nếu khác `admin`. Dùng ngôn ngữ trang WordPress **English (United States)** cho chuỗi giao diện mặc định của Flatsome và WooCommerce. Sau cài đặt, script in URL trang **Vertuo Coffee Pods**. Chạy lại sẽ cập nhật thứ tự theo từng bộ sưu tập và tự đổi thông số tiếng Việt của gói cũ sang tiếng Anh mà không thay đổi giá hoặc tồn kho hiện có. Trang danh mục có tiêu đề cũ **Cà phê Vertuo** cũng được thay bằng bản tiếng Anh. Những SKU và trang đã nâng cấp không bị ghi đè mặc định. Để ghi đè toàn bộ các trường có trong CSV và nội dung trang danh mục, đặt `VERTUO_REFRESH=1` trước lệnh setup; hãy sao lưu trước.

**Cài thủ công:** kích hoạt child theme; nhập CSV tại **Products → Import** với các cột mặc định; tạo trang **Vertuo Coffee Pods** với mẫu **Page - Full Width** và dán `ux-builder/catalog.txt` trong trình soạn thảo mã trước khi chỉnh sửa bằng UX Builder. CSV thủ công dùng thứ tự chung của sản phẩm; để lấy đúng thứ tự riêng theo từng bộ sưu tập, chạy `setup/setup.sh`.

## Giá, ảnh và dữ liệu chưa công bố

- WooCommerce nhập giá niêm yết chuẩn, không phát lại ưu đãi Black Friday, giá đăng ký hoặc giảm giá trực tiếp từ Nespresso. Giá quan sát được lưu trong JSON và metadata CSV.
- Sản phẩm viên cà phê tự tạo thuộc tính WooCommerce dùng chung **Pack Size** với các mức **50**, **100**, **150**, **200**. Đây là thuộc tính, không phải biến thể mua hàng; không thay đổi giá và quy cách từ nguồn. Combo và phụ kiện giữ nguyên.
- Dung tích trên cửa hàng giữ cách ghi của Nespresso, gồm cả ml và oz khi nguồn có ghi cả hai. Các quy đổi trong bảng thông số tiếng Việt không thay thế số đo trên cửa hàng.
- Không đính kèm hoặc hotlink ảnh sản phẩm. WooCommerce dùng ảnh giữ chỗ mặc định; URL ảnh nguồn nằm trong JSON và metadata CSV để thay bằng ảnh đã có quyền sử dụng.
- Tình trạng tồn kho chỉ là dữ liệu tại thời điểm quét; không đồng bộ. Không dùng dữ liệu này như tồn kho thực tế.
- Dữ liệu trống là thông số không áp dụng hoặc nguồn chưa công bố. Một SKU xuất hiện trong nhiều bộ sưu tập chỉ tạo một sản phẩm WooCommerce và một dòng trong bảng tóm tắt.
