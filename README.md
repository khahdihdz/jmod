# JohnCMS 7.1.0 – Phiên bản Việt hóa & Giao diện hiện đại

JohnCMS là một hệ thống quản lý nội dung (CMS) mã nguồn mở được thiết kế đặc biệt cho các website có giao diện nhẹ, tối ưu cho thiết bị di động và cộng đồng trực tuyến.

Phiên bản này được phát triển dựa trên **JohnCMS 7.1.0**, tập trung vào việc Việt hóa giao diện, hiện đại hóa thiết kế và cải thiện trải nghiệm sử dụng trên điện thoại, máy tính bảng và máy tính.

## ✨ Tính năng chính

- 🌐 Hỗ trợ đa ngôn ngữ.
- 🔐 Hệ thống có chú trọng đến bảo mật.
- 💬 Diễn đàn với các chức năng:
  - Ghim chủ đề.
  - Đóng/mở chủ đề.
  - Tạo bình chọn.
  - Đính kèm tệp trong bài viết.
  - Và nhiều chức năng quản lý khác.
- 🖼️ Album ảnh cá nhân.
- 📖 Guestbook cá nhân.
- 📚 Thư viện với cấu trúc danh mục không giới hạn cấp độ.
- ✍️ Cho phép thành viên đăng bài viết lên thư viện.
- 🛡️ Hỗ trợ kiểm duyệt bài viết.
- 📦 Hỗ trợ biên soạn sách FB.
- 📥 Khu vực tải xuống với danh mục không giới hạn cấp độ.
- ⭐ Hệ thống đánh giá và bình luận cho nội dung tải xuống.
- ✉️ Tin nhắn riêng (Private Mail) và hỗ trợ đính kèm tệp.
- 👤 Hệ thống người dùng và hồ sơ thành viên.
- 📱 Giao diện responsive cho điện thoại, tablet và máy tính.
- 🎨 Giao diện hiện đại theo phong cách Bootstrap, không phụ thuộc Bootstrap CDN.
- 📰 Trang chủ dạng blog với danh sách bài viết, tiêu đề, thumbnail và mô tả tự động.
- 🔗 URL thân thiện cho bài viết, ví dụ:
  ```text
  /news/123-tieu-de-bai-viet
  ```
- 🖼️ Thumbnail bài viết có thể được lấy tự động từ hình ảnh đầu tiên trong nội dung.
- 📝 Mô tả bài viết được tạo tự động từ nội dung bài.

## 🎨 Giao diện hiện đại

Phiên bản này sử dụng stylesheet chính tại:

```text
/styles.css
```

CSS được thiết kế theo phong cách **Bright Modern UI / Bootstrap-inspired**, bao phủ toàn bộ hệ thống và tương thích với các class CSS hiện có của JohnCMS.

Các thành phần được làm mới gồm:

- Header.
- Navigation và menu chính.
- Trang chủ.
- Danh sách bài viết.
- Blog card.
- Forum.
- Form và nút bấm.
- Thông báo hệ thống.
- Bảng dữ liệu.
- Phân trang.
- Hồ sơ người dùng.
- Album.
- Downloads.
- Library.
- Guestbook.
- Footer.
- Giao diện responsive.

## 📰 Trang chủ dạng Blog

Trang chủ có thể hiển thị nội dung từ **Main Menu** theo phong cách blog hiện đại.

Mỗi bài viết có thể bao gồm:

- Thumbnail tự động.
- Tiêu đề bài viết.
- Mô tả/trích dẫn tự động.
- Tác giả.
- Thời gian đăng.
- Liên kết **Đọc thêm**.

Nếu bài viết không có hình ảnh, hệ thống sử dụng vùng thumbnail mặc định thay thế.

## 🔗 URL thân thiện

Bài viết sử dụng URL thân thiện thay cho URL dạng query string.

URL cũ:

```text
/news/index.php?do=view&id=123
```

URL mới:

```text
/news/123-tieu-de-bai-viet
```

URL cũ vẫn có thể được chuyển hướng sang URL mới thông qua HTTP 301 để hạn chế ảnh hưởng đến các liên kết cũ và bookmark.

> Lưu ý: tính năng URL thân thiện yêu cầu máy chủ hỗ trợ `.htaccess` và `mod_rewrite` khi sử dụng Apache.

## ⚙️ Yêu cầu hệ thống

- PHP **5.6 trở lên**.
- MySQL **5.5 trở lên**.
- Máy chủ hỗ trợ `.htaccess`.
- Apache cần hỗ trợ `mod_rewrite` nếu sử dụng URL thân thiện.
- Composer để cài đặt các dependency từ mã nguồn repository.

> Khuyến nghị sử dụng phiên bản PHP mới hơn nếu môi trường triển khai và mã nguồn của dự án cho phép, đồng thời kiểm tra khả năng tương thích trước khi nâng cấp phiên bản PHP của website đang chạy.

## 🚀 Cài đặt từ repository

1. Cài đặt [Composer](https://getcomposer.org/) trên máy chủ hoặc máy tính dùng để triển khai.
2. Đảm bảo máy chủ có kết nối Internet.
3. Clone hoặc tải mã nguồn JohnCMS về máy chủ.
4. Mở Terminal tại thư mục dự án và chạy:

   ```bash
   composer install
   ```

5. Truy cập địa chỉ:

   ```text
   https://ten-mien-cua-ban/install
   ```

6. Trình cài đặt JohnCMS sẽ xuất hiện. Thực hiện theo các bước được hướng dẫn.
7. Sau khi cài đặt hoàn tất, **bắt buộc xóa thư mục `/install`** khỏi máy chủ.
8. Kiểm tra quyền ghi của các thư mục mà hệ thống yêu cầu trong quá trình cài đặt.

## 📦 Cài đặt từ gói phân phối

1. Giải nén gói JohnCMS vào máy chủ.
2. Tải toàn bộ mã nguồn lên hosting hoặc VPS.
3. Mở trình duyệt và truy cập:

   ```text
   https://ten-mien-cua-ban/install
   ```

4. Làm theo hướng dẫn của trình cài đặt.
5. Sau khi cài đặt thành công, **bắt buộc xóa thư mục `/install`**.
6. Kiểm tra website, đăng nhập khu vực quản trị và hoàn tất cấu hình ban đầu.

## 🇻🇳 Việt hóa

Bản này hướng tới việc sử dụng JohnCMS 7.1.0 với giao diện và nội dung tiếng Việt.

Các thư mục ngôn ngữ của hệ thống và từng module nằm trong các thư mục `locale` tương ứng, ví dụ:

```text
system/locale/vi/
forum/locale/vi/
news/locale/vi/
users/locale/vi/
profile/locale/vi/
registration/locale/vi/
```

Khi chỉnh sửa bản dịch, nên giữ nguyên cấu trúc của file locale và chỉ thay đổi chuỗi hiển thị cần Việt hóa.

## 📁 Cấu trúc chính

```text
admin/          Khu vực quản trị
album/          Album ảnh
forum/          Diễn đàn
guestbook/      Guestbook
help/           Trợ giúp
library/        Thư viện
mail/           Tin nhắn riêng
news/           Tin tức / bài viết
profile/        Hồ sơ người dùng
registration/   Đăng ký
system/         Hệ thống lõi
users/          Người dùng
styles.css      CSS giao diện hiện đại
```

## 🛠️ Phát triển tùy biến

Khi tùy biến giao diện, ưu tiên chỉnh sửa:

```text
/styles.css
```

Thay vì chỉnh sửa trực tiếp CSS của từng module, giúp giao diện được quản lý tập trung và dễ bảo trì hơn.

Đối với thay đổi PHP, nên giữ nguyên cấu trúc dữ liệu và API nội bộ của JohnCMS khi có thể để hạn chế ảnh hưởng đến các module hiện có.

## 📄 Giấy phép

JohnCMS là phần mềm mã nguồn mở. Vui lòng xem file [`LICENSE.md`](LICENSE.md) để biết thông tin đầy đủ về giấy phép và điều kiện sử dụng.

## ❤️ Đóng góp

Bạn có thể đóng góp cho dự án bằng cách:

- Báo cáo lỗi.
- Đề xuất tính năng.
- Cải thiện bản dịch tiếng Việt.
- Cải thiện giao diện.
- Gửi pull request.
- Kiểm thử trên các phiên bản PHP và môi trường hosting khác nhau.

---

**JohnCMS 7.1.0 – Việt hóa, hiện đại hóa và tối ưu trải nghiệm trên thiết bị di động.**
