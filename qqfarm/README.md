# QQFarm Việt hóa cho JMod / JohnCMS 7.1

Đây là bản tích hợp PHP/HTML chạy trực tiếp trong phiên đăng nhập JohnCMS. Hiện đã có vòng lặp chơi thử trồng cây/thu hoạch; **chưa phải bản chuyển đổi đầy đủ QQFarm 6.2 gốc** và không dùng lại client Flash.

## Cài đặt

1. Sao lưu mã nguồn và cơ sở dữ liệu website.
2. Đưa thư mục `qqfarm/` vào thư mục gốc cài đặt JohnCMS.
3. Mở phpMyAdmin, chọn đúng database của JohnCMS, chạy toàn bộ `qqfarm/install.sql`. Script chỉ tạo hai bảng riêng của module: `qqfarm_players` và `qqfarm_plots`.
4. Đăng nhập website rồi mở `https://ten-mien-cua-ban/qqfarm/`.
5. Thử nhận thưởng ngày, gieo cây, chờ cây chín rồi thu hoạch.

## Chức năng hiện có

- Dùng bootstrap, session và tài khoản đăng nhập của JohnCMS 7.1.
- Tự tạo hồ sơ người chơi với 100 xu và sáu ô đất.
- Thưởng đăng nhập mỗi ngày: 20 xu, giới hạn bằng cập nhật có điều kiện ở database.
- Giao diện nông trại dạng lưới responsive, co giãn cho desktop và điện thoại (CSS tại `farm.css`).
- Ba loại cây thử nghiệm: cà rốt (10 xu / 60 giây / thu 18 xu), lúa mì (20 xu / 120 giây / thu 35 xu), ngô (35 xu / 240 giây / thu 60 xu).
- Gieo cây trừ xu trong transaction; thu hoạch kiểm tra thời gian chín và quyền sở hữu ô đất phía máy chủ.
- Prepared statements, token chống CSRF, và ràng buộc unique cho mỗi ô đất của từng người dùng.

## Lưu ý quan trọng

- Các loại cây, thời gian và giá hiện là dữ liệu chơi thử được định nghĩa trong `index.php`, chưa được nhập từ dữ liệu game gốc.
- Chưa chuyển đổi hệ thống vật nuôi, cửa hàng đầy đủ, vật phẩm, nhiệm vụ, bạn bè, quà tặng, kinh nghiệm/cấp độ hoặc API Discuz!/vBulletin.
- SWF gốc không được sử dụng; giao diện hiện là HTML thuần tương thích trình duyệt hiện đại.
- Nên thử trên bản staging trước khi đưa lên website thật. Chưa thể xác nhận tương thích runtime cho đến khi chạy thử trên môi trường PHP/database thực tế của site.
- Bản JMod này yêu cầu PHP 5.6–7.x theo bootstrap hiện có; không nâng PHP của toàn website chỉ để chạy module.

## Gỡ cài đặt

Sao lưu dữ liệu trước. Xóa thư mục `qqfarm/`; chỉ khi chắc chắn không cần dữ liệu nữa mới xóa thủ công hai bảng `qqfarm_players` và `qqfarm_plots`.
