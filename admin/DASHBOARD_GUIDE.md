# 📊 HƯỚNG DẪN SỬ DỤNG DASHBOARD THỐNG KÊ

## 📌 Giới Thiệu
Dashboard thống kê là công cụ giúp bạn theo dõi doanh thu, tỷ lệ lấp đầy phòng chiếu, và hiệu suất phim theo tháng.

## 🎯 Bắt Đầu Sử Dụng

### Cách truy cập Dashboard:

1. **Từ menu Admin**
   - Truy cập: `/admin/index.php`
   - Nhấp vào "Dashboard Thống Kê"

2. **Từ trang Quản Lý Suất Chiếu**
   - Truy cập: `/admin/suat_chieu.php`
   - Nhấp vào nút "📊 Dashboard Thống Kê" (thanh công cụ màu tím)

3. **URL trực tiếp**
   - Dashboard tháng hiện tại: `/admin/dashboard.php`
   - Dashboard nâng cao (chọn tháng): `/admin/dashboard_advanced.php`

## 📋 Các Chức Năng Chính

### 1. 📊 Dashboard Thống Kê (dashboard_advanced.php)
Đây là phiên bản chính với đầy đủ tính năng.

#### Tính Năng:
- **💰 Doanh Thu Tháng**: Hiển thị tổng doanh thu và so sánh % với tháng trước
- **🎫 Vé Bán**: Tổng vé bán được và giá trung bình
- **🎬 Suất Chiếu**: Số suất chiếu trong tháng
- **🏆 Phim #1**: Phim có doanh thu cao nhất kèm poster
- **📈 Biểu Đồ Doanh Thu Theo Ngày**: Đường biểu đồ hàng ngày
- **🏅 Biểu Đồ Top 5 Phim**: So sánh doanh thu 5 phim hàng đầu
- **📍 Bảng Tỷ Lệ Lấp Đầy**: Chi tiết từng phòng chiếu

#### Cách Sử Dụng Bộ Lọc:
```
1. Chọn Tháng (1-12)
2. Chọn Năm (2020-hiện tại)
3. Nhấp nút "📅 Xem"
```

### 2. 📊 Dashboard Cơ Bản (dashboard.php)
Hiển thị doanh thu tháng hiện tại realtime.

**Lưu ý**: Tự động hiển thị tháng/năm hiện tại

### 3. 📱 API Thống Kê (statistics_api.php)

#### Endpoint:
```
GET /admin/statistics_api.php?month=3&year=2026
```

#### Tham Số:
- `month`: Tháng (1-12), mặc định = tháng hiện tại
- `year`: Năm, mặc định = năm hiện tại

#### Ví Dụ Truy Vấn:
```
/admin/statistics_api.php?month=3&year=2026
/admin/statistics_api.php (tháng hiện tại)
/admin/statistics_api.php?month=12&year=2025
```

#### Phản Hồi JSON:
```json
{
  "month": 3,
  "year": 2026,
  "period": "Tháng 3/2026",
  "kpi": {
    "total_revenue": 1500000,
    "total_tickets": 50,
    "total_showtimes": 8,
    "avg_price_per_ticket": 30000
  },
  "daily_data": [...],
  "top_5_movies": [...],
  "occupancy_by_room": [...],
  "top_movie": {...}
}
```

## 📊 Các Chỉ Số Chính (KPI)

### 1. Doanh Thu Tháng (₫)
- **Định Nghĩa**: Tổng tiền bán vé trong tháng
- **Công Thức**: ∑(giá vé × số vé bán)
- **So Sánh**: % thay đổi với tháng trước (📈 tăng / 📉 giảm)

### 2. Vé Bán (Tấn)
- **Định Nghĩa**: Tổng số vé đã bán
- **Thêm**: Giá vé trung bình = Doanh thu / Vé bán

### 3. Suất Chiếu (Cái)
- **Định Nghĩa**: Tổng số suất chiếu trong tháng
- **Khoảng Thời Gian**: Hiển thị từ ngày - đến ngày

### 4. Tỷ Lệ Lấp Đầy (%)
- **Công Thức**: (Vé bán / Tổng ghế) × 100
- **Mã Màu**:
  - 🟢 Xanh (>=70%): Lấp đầy cao - Tốt
  - 🟡 Vàng (40-70%): Lấp đầy trung bình - Cần chú ý
  - 🔴 Đỏ (<40%): Lấp đầy thấp - Cần quảng bá

### 5. Phim Doanh Thu #1
- **Định Nghĩa**: Phim có doanh thu cao nhất tháng
- **Hiển Thị**: Poster, tên phim, doanh thu, vé bán, giá trung bình

## 📈 Cách Đọc Biểu Đồ

### Biểu Đồ Doanh Thu Theo Ngày
- **Trục X**: Ngày (định dạng dd/mm)
- **Trục Y**: Doanh thu (₫)
- **Đường Màu**: Xu hướng doanh thu hàng ngày
- **Dấu Chấm**: Điểm dữ liệu mỗi ngày

### Biểu Đồ Top 5 Phim
- **Trục X**: Doanh thu (₫)
- **Trục Y**: Tên phim
- **Thanh Có Màu**: Mỗi phim một màu khác nhau
- **Chiều Dài**: Càng dài = Doanh thu càng cao

### Bảng Tỷ Lệ Lấp Đầy
| Cột | Ý Nghĩa |
|-----|---------|
| Rạp | Tên rạp chiếu |
| Phòng | Tên phòng chiếu |
| Suất Chiếu | Số suất chiếu trong tháng |
| Vé Bán | Số vé đã bán |
| Tỷ Lệ | Thanh tiến độ + % |

## 🎯 Các Tình Huống Sử Dụng

### 1️⃣ Admin Muốn Xem Doanh Thu Tháng 3/2026
```
- Truy cập: /admin/dashboard_advanced.php
- Chọn: Tháng 3, Năm 2026
- Nhấp: "📅 Xem"
```

### 2️⃣ Admin Muốn So Sánh Doanh Thu 2 Tháng
```
- Vào dashboard, chọn Tháng 1 → Xem
- Ghi nhớ con số
- Quay lại, chọn Tháng 2 → Xem
- So sánh %
```

### 3️⃣ Admin Muốn Kiểm Tra Phòng Nào Lấp Đầy Cao
```
- Cuộn xuống phần "Tỷ Lệ Lấp Đầy Phòng Chiếu"
- Tìm phòng có % cao nhất
```

### 4️⃣ Tích Hợp với Hệ Thống Khác
```
- Sử dụng API: /admin/statistics_api.php
- Parse JSON response
- Xử lý dữ liệu
```

## 💡 Mẹo & Lưu Ý

✅ **Hãy làm:**
- Kiểm tra dashboard hàng ngày để theo dõi doanh thu
- Sử dụng so sánh tháng để phát hiện xu hướng
- Chú ý phòng có tỷ lệ lấp đầy thấp
- Quảng bá phim doanh thu cao

❌ **Tránh:**
- Không xóa dữ liệu trong các bảng liên quan
- Không chỉnh sửa trực tiếp dữ liệu trong DB
- Không cập nhật giá vé trong suất chiếu đã bán

## 🔧 Xử Lý Sự Cố

### Dashboard Hiển Thị Trắng
**Nguyên Nhân**: Lỗi kết nối DB
**Cách Fix**: 
- Kiểm tra `/config/db.php`
- Khởi động XAMPP MySQL

### Biểu Đồ Không Hiển Thị
**Nguyên Nhân**: Không có dữ liệu hoặc lỗi JavaScript
**Cách Fix**:
- Kiểm tra trình duyệt console (F12)
- Làm mới trang (Ctrl + F5)

### Dữ Liệu Không Cập Nhật
**Nguyên Nhân**: Cache trình duyệt
**Cách Fix**:
- Xóa cache (Ctrl + Shift + Delete)
- Hoặc dùng Incognito Mode

## 📞 Hỗ Trợ
Nếu có vấn đề, liên hệ admin hoặc kiểm tra file log.

---

**Phiên Bản**: 1.0.0  
**Cập Nhật**: Tháng 3, 2026  
**Tác Giả**: Dev Team
