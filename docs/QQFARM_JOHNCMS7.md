# Tích hợp QQFarm Việt hóa với JohnCMS 7.1.0

Tài liệu này mô tả cách chuẩn bị tích hợp gói `qqfarm_vietcadao.zip` vào repository **khahdihdz/jmod** (JohnCMS 7.1.0). Đây là hướng dẫn tích hợp, không phải khẳng định rằng mã game gốc đã tương thích sẵn với JohnCMS.

## Kết quả kiểm tra gói game

Gói QQFarm là mã PHP/Flash đời cũ (khoảng năm 2012). Cấu trúc có `games/qqfarm/core/`, các API dành cho Discuz X và vBulletin, các file cấu hình game và client Flash `.swf`. Vì vậy không thể chỉ chép nguyên gói vào JohnCMS rồi coi như đã tích hợp.

Các khác biệt cần xử lý:
- Adapter đăng nhập hiện tại của game nhắm tới Discuz X/vBulletin, không dùng session JohnCMS.
- Schema người chơi và các bảng game phải được cài đặt độc lập; cần xác minh SQL gốc trước khi chạy.
- Client SWF không chạy nguyên bản trong trình duyệt hiện đại nếu không có môi trường tương thích Flash.
- Mã PHP từ năm 2012 cần rà soát các API đã bị loại bỏ, SQL injection, xác thực yêu cầu và quyền ghi file.
- JohnCMS 7.1.0 yêu cầu môi trường PHP cũ hơn PHP 8 theo tài liệu dự án; không nên hạ PHP của website đang hoạt động chỉ để chạy game.

## Kiến trúc tích hợp đề xuất

1. Đặt game trong một thư mục module riêng, ví dụ `modules/qqfarm/` hoặc `qqfarm/`, không ghi đè các file lõi JohnCMS.
2. Viết adapter JohnCMS riêng để xác định người dùng đăng nhập từ bootstrap/session chính thức của đúng bản JohnCMS 7.1.0. Không tự đọc cookie hoặc tin vào `uid` do client gửi.
3. Dùng ID thành viên JohnCMS làm khóa liên kết người chơi; tạo bản ghi game bằng thao tác idempotent khi người chơi mở game lần đầu.
4. Tách cấu hình database khỏi mã nguồn; không commit mật khẩu database.
5. Tạo installer/migration có kiểm tra bảng tồn tại, transaction khi khả dụng, và chế độ báo lỗi. Không chạy file migration công khai sau khi cài xong.
6. Chỉ bật các endpoint cần thiết; kiểm tra session, CSRF/token, quyền truy cập và validate toàn bộ đầu vào.
7. Dùng client HTML5 thay thế hoặc môi trường Flash tương thích đã được kiểm thử nếu cần chơi trên trình duyệt hiện đại.

## Quy trình cài đặt an toàn (chưa phải installer tự động)

1. Sao lưu toàn bộ mã nguồn và database JohnCMS.
2. Giải nén gói QQFarm vào thư mục làm việc ngoài webroot và kiểm tra toàn bộ PHP/SQL trước khi triển khai.
3. Xác định SQL schema gốc, encoding (một số file cũ có thể dùng GBK) và các điểm gọi API Discuz/VBB.
4. Chuyển đổi từng điểm tích hợp sang adapter JohnCMS; không include trực tiếp API Discuz/VBB.
5. Cài bảng game bằng migration đã rà soát trên bản sao database.
6. Kiểm tra đăng nhập, tạo người chơi, thao tác game, quyền truy cập chéo người dùng và log lỗi trong môi trường staging.
7. Chỉ triển khai production sau khi xác nhận PHP version, database version và client game hoạt động.

## Tiêu chí nghiệm thu

- Khách chưa đăng nhập không thể thực hiện hành động thay đổi dữ liệu.
- Người chơi không thể sửa ID để truy cập dữ liệu người khác.
- Tạo người chơi lần đầu không tạo bản ghi trùng khi tải lại.
- Không có thông tin bí mật trong repository hoặc thông báo lỗi công khai.
- Các truy vấn dùng prepared statements hoặc cơ chế tương đương.
- Installer không chạy lại được công khai sau khi hoàn tất.
- Giao diện và client game được kiểm thử trên thiết bị mục tiêu.

## Trạng thái

**Chưa xác nhận tích hợp runtime.** Cần kiểm tra các file SQL, bootstrap/session của JohnCMS 7.1.0 và toàn bộ luồng API của game trước khi viết adapter chạy thật. Không được coi tài liệu này là bằng chứng game đã cài đặt thành công.
