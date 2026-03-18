# 📊 SHE Dashboard - Quản Lý Thống Kê Rạp Chiếu Phim

## 🎯 Mục Đích
Tạo hệ thống dashboard thống kê doanh thu tháng, tỷ lệ lấp đầy phòng chiếu, và phim doanh thu top 1.

## 📂 Các File Đã Tạo

### 1. **dashboard.php** (Dashboard Cơ Bản)
- **Vị trí**: `/admin/dashboard.php`
- **Mục đích**: Hiển thị thống kê tháng hiện tại realtime
- **Tính năng**:
  - 💰 Doanh thu tháng hiện tại
  - 🎫 Tổng vé bán
  - 🎬 Tổng suất chiếu
  - 🏆 Phim doanh thu #1 (hiển thị poster, thông tin)
  - 📈 Biểu đồ doanh thu theo ngày (Chart.js)
  - 🏅 Biểu đồ top 5 phim
  - 📍 Bảng tỷ lệ lấp đầy phòng chiếu (với progress bar)
- **Sử dụng**: Truy cập trực tiếp hoặc từ menu admin

### 2. **dashboard_advanced.php** (Dashboard Nâng Cao)
- **Vị trí**: `/admin/dashboard_advanced.php`
- **Mục đích**: Dashboard với khả năng chọn tháng/năm
- **Tính năng Bổ Sung**:
  - 🔄 Bộ lọc tháng/năm
  - 📊 So sánh doanh thu với tháng trước (% thay đổi)
  - ✨ Giao diện đẹp mắt
  - 📱 Responsive (tương thích mobile)
  - ⚡ Hiệu suất tối ưu
- **Khuyến Nghị**: Sử dụng file này thay vì dashboard.php

### 3. **statistics_api.php** (API JSON)
- **Vị trí**: `/admin/statistics_api.php`
- **Mục đích**: Cung cấp dữ liệu dạng JSON cho tích hợp
- **Cách Sử Dụng**:
  ```
  GET /admin/statistics_api.php?month=3&year=2026
  ```
- **Trả Về**:
  ```json
  {
    "period": "Tháng 3/2026",
    "kpi": {
      "total_revenue": 1500000,
      "total_tickets": 50,
      "total_showtimes": 8
    },
    "daily_data": [...],
    "top_5_movies": [...],
    "occupancy_by_room": [...],
    "top_movie": {...}
  }
  ```

### 4. **admin/index.php** (Menu Admin Chính)
- **Vị trí**: `/admin/index.php`
- **Mục đích**: Dashboard menu tập hợp tất cả chức năng admin
- **Tính năng**:
  - 📊 Quick stats (phim, suất chiếu, vé bán, khách hàng, doanh thu tháng)
  - 🎯 Menu điều hướng các chức năng:
    - Dashboard Thống Kê
    - Quản Lý Người Dùng
    - Quản Lý Phim
    - Quản Lý Suất Chiếu
    - Quản Lý Phòng Chiếu
    - Cấu Hình Ghế
    - API Thống Kê
  - 👤 Thông tin user + logout

### 5. **DASHBOARD_GUIDE.md** (Hướng Dẫn Chi Tiết)
- **Vị trí**: `/admin/DASHBOARD_GUIDE.md`
- **Nội dung**:
  - Cách truy cập dashboard
  - Giải thích các chỉ số KPI
  - Hướng dẫn sử dụng từng tính năng
  - Cách đọc biểu đồ
  - Tình huống sử dụng thực tế
  - Xử lý sự cố

## 📊 Các Chỉ Số Thống Kê (KPI)

### 1. **💰 Doanh Thu Tháng**
- Tính: `SUM(giá vé × số vé bán)` trong tháng
- So sánh: % thay đổi với tháng trước
- Biểu thị: Trạng thái kinh doanh chung

### 2. **🎫 Vé Bán**
- Tính: COUNT(vé đã bán)
- Thêm: Giá vé trung bình = Doanh thu / Vé bán

### 3. **🎬 Suất Chiếu**
- Tính: COUNT(DISTINCT suất chiếu) trong tháng

### 4. **🏆 Phim #1**
- Phim có doanh thu cao nhất
- Hiển thị: Poster, tên phim, doanh thu, vé bán, giá tb

### 5. **📍 Tỷ Lệ Lấp Đầy Phòng**
- Công thức: `(Vé bán / Tổng ghế) × 100`
- Mã Màu:
  - 🟢 Xanh (≥70%): Lấp đầy cao
  - 🟡 Vàng (40-70%): Lấp đầy trung bình
  - 🔴 Đỏ (<40%): Lấp đầy thấp

## 🚀 Cách Bắt Đầu

### Truy Cập Dashboard:
1. **Menu Admin**: `/admin/index.php` → "Dashboard Thống Kê"
2. **Trực tiếp**: `/admin/dashboard_advanced.php`
3. **Từ Quản Lý Suất Chiếu**: `/admin/suat_chieu.php` → "📊 Dashboard Thống Kê"

### Chọn Tháng/Năm:
```
1. Dashboard mở → Chọn tháng (1-12)
2. Chọn năm (2020-hiện tại)
3. Nhấp "📅 Xem"
```

### Lấy Dữ Liệu JSON:
```
GET /admin/statistics_api.php?month=3&year=2026
```

## 📈 Biểu Đồ (Được Sử Dụng)

### 1. **Line Chart** - Doanh Thu Theo Ngày
- Thư viện: Chart.js v3.9.1
- Hiển thị: Xu hướng doanh thu hàng ngày
- Tương tác: Hover để xem chi tiết

### 2. **Bar Chart** - Top 5 Phim
- Thư viện: Chart.js v3.9.1
- Hiển thị: So sánh doanh thu 5 phim hàng đầu
- Tương tác: Hover để xem chi tiết

### 3. **Progress Bar** - Tỷ Lệ Lấp Đầy
- Hiển thị: Thanh tiến độ + % cụ thể
- Mã Màu: Tùy theo mức độ lấp đầy

## 🔧 Cấu Trúc Dữ Liệu

### Bảng Liên Quan:
- **ve** (vé): id, user_id, suat_chieu_id, ghe_id
- **suat_chieu** (suất chiếu): id, phim_id, phong_id, ngay, gio, gia
- **phim** (phim): id, ten_phim, the_loai, mo_ta, poster
- **phong_chieu** (phòng): id, rap_id, ten_phong
- **rap** (rạp): id, ten_rap, dia_chi, thanh_pho
- **ghe** (ghế): id, phong_id, ten_ghe

### Query Chính:
```sql
-- Doanh thu tháng
SELECT SUM(sc.gia) FROM ve 
JOIN suat_chieu sc ON ve.suat_chieu_id = sc.id 
WHERE MONTH(sc.ngay) = ? AND YEAR(sc.ngay) = ?

-- Top movie
SELECT p.ten_phim, SUM(sc.gia) as revenue 
FROM phim p 
LEFT JOIN suat_chieu sc ON p.id = sc.phim_id 
LEFT JOIN ve ON ve.suat_chieu_id = sc.id 
GROUP BY p.id 
ORDER BY revenue DESC LIMIT 1

-- Occupancy rate
SELECT (COUNT(ve.id) / COUNT(ghe.id) * 100) as rate 
FROM phong_chieu pc 
LEFT JOIN ghe ON ghe.phong_id = pc.id 
LEFT JOIN suat_chieu sc ON pc.id = sc.phong_id 
LEFT JOIN ve ON ve.suat_chieu_id = sc.id
```

## 🎨 Giao Diện

### Màu Sắc:
- **Primary**: `#667eea` (Xanh lam)
- **Secondary**: `#764ba2` (Tím)
- **Success**: `#22c55e` (Xanh lá)
- **Warning**: `#fbbf24` (Vàng)
- **Danger**: `#ef4444` (Đỏ)

### Responsive Design:
- ✅ Desktop (1200px+)
- ✅ Tablet (768-1024px)
- ✅ Mobile (< 768px)

## 📝 Ghi Chú

### Ưu Điểm:
✅ Dễ sử dụng  
✅ Dữ liệu realtime  
✅ Biểu đồ đẹp mắt  
✅ API JSON tiện lợi  
✅ Responsive design  
✅ So sánh theo tháng  

### Cần Cải Thiện:
⚠️ Thêm export PDF/Excel  
⚠️ Thêm so sánh theo năm  
⚠️ Thêm biểu đồ tròn (Pie Chart)  
⚠️ Cách đặt cảnh báo doanh thu thấp  

## 📞 Liên Hệ & Hỗ Trợ
Nếu có vấn đề, tham khảo [DASHBOARD_GUIDE.md](DASHBOARD_GUIDE.md) hoặc liên hệ dev team.

---

**Phiên Bản**: 1.0.0  
**Ngày Phát Hành**: Tháng 3, 2026  
**Trạng Thái**: Hoạt Động ✅
