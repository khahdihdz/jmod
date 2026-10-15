# QQFarm Việt hóa cho JMod / JohnCMS 7.1

Đây là bản tích hợp PHP/HTML chạy trực tiếp trong phiên đăng nhập JohnCMS. Hiện đã có vòng lặp chơi thử trồng cây/thu hoạch; **chưa phải bản chuyển đổi đầy đủ QQFarm 6.2 gốc** và không dùng lại client Flash.

## Cài đặt

1. Sao lưu mã nguồn và cơ sở dữ liệu website.
2. Đưa thư mục `qqfarm/` vào thư mục gốc cài đặt JohnCMS.
3. Mở phpMyAdmin, chọn đúng database JohnCMS rồi chạy `qqfarm/install.sql` để tạo các bảng module.
4. Nếu đã có bản QQFarm cũ, **không cần chạy lại `install.sql` để nâng cấp**. Chọn đúng database và chạy lần lượt `upgrade-v2.sql`, `upgrade-v3.sql`, `upgrade-v4.sql`, `upgrade-v5.sql`. Các script nâng cấp đã được thiết kế để chạy lại không báo lỗi do cột/bảng đã tồn tại; v2 sẽ báo trạng thái nếu thiếu bảng nền.
5. Đăng nhập website rồi mở `https://ten-mien-cua-ban/qqfarm/`.
6. Thử nhận thưởng ngày, gieo cây, chờ cây chín rồi thu hoạch.

### Nâng cấp database theo phiên bản

- **Bản mới hoàn toàn:** chạy `install.sql` một lần.
- **Bản cũ có `qqfarm_players` và chưa có XP:** chạy `upgrade-v2.sql`; script tự bỏ qua nếu cột `experience` đã tồn tại.
- **Thêm nhiệm vụ hằng ngày:** chạy `upgrade-v3.sql`; tạo bảng nếu chưa có.
- **Thêm thú nuôi:** chạy `upgrade-v4.sql`; tạo bảng nếu chưa có.
- **Mở rộng nông trại:** chạy `upgrade-v5.sql`; thêm cột `unlocked_plots` nếu chưa có, giữ nguyên dữ liệu người chơi.
- Hãy chọn đúng database trong phpMyAdmin trước khi import. Không xóa dữ liệu người chơi trong quá trình nâng cấp.

## Chức năng hiện có

- Dùng bootstrap, session và tài khoản đăng nhập của JohnCMS 7.1.
- Tự tạo hồ sơ người chơi với 100 xu và sáu ô đất ban đầu; có thể mua thêm tối đa 12 ô bằng xu.
- Thưởng đăng nhập mỗi ngày: 20 xu, giới hạn bằng cập nhật có điều kiện ở database.
- Nhiệm vụ hằng ngày: thu hoạch 3 lần, nhận một lần 50 xu và 20 XP; tiến độ lưu theo ngày trong bảng `qqfarm_daily_missions`.
- Thú nuôi: chọn một trong ba bạn đồng hành — Mèo Nông Trại (150 xu, quà 10 xu/ngày), Thỏ Đồng (220 xu, quà 15 xu/ngày) hoặc Cáo Đỏ (350 xu, quà 20 xu/ngày). Mỗi tài khoản chỉ có một thú; lượt nhận quà được khóa và ghi nhận trong database.
- Hệ thống kinh nghiệm/cấp độ: cà rốt +5 XP, lúa mì +10 XP, ngô +15 XP, dâu tây +25 XP, bí đỏ +40 XP; mỗi 100 XP tăng một cấp. XP và xu được cộng trong cùng giao dịch thu hoạch.
- Chợ có 5 loại cây; dâu tây mở khóa ở cấp 2, bí đỏ mở khóa ở cấp 4. Điều kiện cấp được kiểm tra phía máy chủ khi gieo, không chỉ ẩn trên giao diện.
- Mở rộng ruộng: bắt đầu với 6 ô, tối đa 12 ô; ô tiếp theo giá 200 xu và mỗi ô sau tăng thêm 100 xu. Giao dịch khóa hồ sơ, trừ xu và tạo ô đất trong cùng transaction.
- Giao diện nông trại dạng lưới responsive, co giãn cho desktop và điện thoại (CSS tại `farm.css`).
- Năm loại cây thử nghiệm: cà rốt (10 xu / 60 giây / thu 18 xu), lúa mì (20 xu / 120 giây / thu 35 xu), ngô (35 xu / 240 giây / thu 60 xu), dâu tây (60 xu / 360 giây / thu 105 xu, mở cấp 2), bí đỏ (100 xu / 600 giây / thu 175 xu, mở cấp 4).
- Gieo cây trừ xu trong transaction; thu hoạch kiểm tra thời gian chín và quyền sở hữu ô đất phía máy chủ.
- Prepared statements, token chống CSRF, và ràng buộc unique cho mỗi ô đất của từng người dùng.

## Lưu ý quan trọng

- Các loại cây, thời gian và giá hiện là dữ liệu chơi thử được định nghĩa trong `index.php`, chưa được nhập từ dữ liệu game gốc.
- Hệ thống thú nuôi hiện là phiên bản cơ bản gồm chọn một thú và nhận quà hằng ngày; cửa hàng đầy đủ, vật phẩm, hệ thống bạn bè/thăm nông trại và API Discuz!/vBulletin chưa được chuyển đổi.
- SWF gốc không được sử dụng; giao diện hiện là HTML thuần tương thích trình duyệt hiện đại.
- Nên thử trên bản staging trước khi đưa lên website thật. Chưa thể xác nhận tương thích runtime cho đến khi chạy thử trên môi trường PHP/database thực tế của site.
- Bản JMod này yêu cầu PHP 5.6–7.x theo bootstrap hiện có; không nâng PHP của toàn website chỉ để chạy module.

## Gỡ cài đặt

Sao lưu dữ liệu trước. Xóa thư mục `qqfarm/`; chỉ khi chắc chắn không cần dữ liệu nữa mới xóa thủ công các bảng `qqfarm_players`, `qqfarm_plots`, `qqfarm_daily_missions` và `qqfarm_pets`.
