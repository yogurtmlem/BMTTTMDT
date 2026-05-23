# Mini-SIEM - ShopVN Monitor

Hệ thống giám sát an ninh mạng (mini-SIEM) cho website TMĐT mô phỏng, xây dựng trên ELK Stack + Fail2Ban.

**Môn học:** Bảo mật thông tin trong thương mại điện tử  
**Nhóm:** [Tên nhóm]  
**Trường:** [Tên trường]

---

## Yêu cầu
- Docker Desktop (đã bật WSL2 nếu dùng Windows)
- Git
- PowerShell (để chạy attack simulation)

---

## Cách chạy

### 1. Clone repo
```bash
git clone https://github.com/yogurtmlem/BMTTTMDT.git
cd BMTTTMDT
```

### 2. Khởi động hệ thống
```bash
docker compose up -d --build
```
Lần đầu mất 5–10 phút để tải images. Chờ đến khi tất cả container hiện **Started**.

### 3. Cấu hình SSL/TLS (HTTPS)

Chạy lệnh sau để tạo SSL certificate bên trong container:

```bash
docker exec -i mini-siem-webshop-1 bash -c "openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/shopvn.key -out /etc/ssl/certs/shopvn.crt -subj '/CN=localhost/O=ShopVN/C=VN'"
```

Cấu hình và reload Apache:

```bash
docker exec -i mini-siem-webshop-1 bash -c "a2enmod ssl && printf '\n  DocumentRoot /var/www/html\n  SSLEngine on\n  SSLCertificateFile /etc/ssl/certs/shopvn.crt\n  SSLCertificateKeyFile /etc/ssl/private/shopvn.key\n  <Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n  \n\n' > /etc/apache2/sites-available/ssl.conf && a2ensite ssl && service apache2 reload"
```

> Certificate không được push lên GitHub. Mỗi collaborator cần tự generate trên máy local.

### 4. Truy cập hệ thống

| Service | URL | Mô tả |
|---------|-----|-------|
| ShopVN (HTTP) | http://localhost | Website TMĐT mô phỏng |
| ShopVN (HTTPS) | https://localhost | Website với SSL/TLS |
| Đăng ký tài khoản | http://localhost/register.php | Tạo tài khoản mới |
| Admin Panel | http://localhost/admin | Chỉ dành cho tài khoản `admin` |
| Kibana Dashboard | http://localhost:5601 | Dashboard giám sát bảo mật |
| Elasticsearch | http://localhost:9200 | Kiểm tra dữ liệu log |

> Khi truy cập HTTPS, trình duyệt hiển thị cảnh báo bảo mật do dùng self-signed certificate. Chọn **Advanced → Proceed to localhost** để tiếp tục.

### 5. Tài khoản mặc định

| Tài khoản | Username | Password | Ghi chú |
|-----------|----------|----------|---------|
| Admin | `admin` | `admin123` | Truy cập được Admin Panel |
| Người dùng | `nguyenvana` | `password123` | Tài khoản thường |
| Người dùng | `tranthib` | `password123` | Tài khoản thường |

> Mật khẩu được lưu dạng **bcrypt hash** trong database, không lưu plaintext.

#### Reset mật khẩu admin (nếu cần)
Tạo file `webshop-html/fixpw.php`:
```php
<?php require "db.php"; $h = password_hash("admin123", PASSWORD_DEFAULT); $pdo->prepare("UPDATE users SET password = ? WHERE username = ?")->execute([$h, "admin"]); echo "Done: " . $h; ?>
```
Truy cập `http://localhost/fixpw.php` rồi **xóa file đi** sau khi dùng.

### 6. Cấu hình Kibana

#### 6.1 Tạo Data View
1. Vào http://localhost:5601
2. Menu → Stack Management → Data Views → Create data view
3. Điền:
   - **Name**: `nginx-logs`
   - **Index pattern**: `nginx-logs-*`
   - **Timestamp field**: `@timestamp`
4. Click **Save data view to Kibana**

#### 6.2 Import Dashboard
1. Menu → Stack Management → Saved Objects
2. Click **Import** → chọn file `export.ndjson`
3. Vào Dashboards → mở **Mini-SIEM - ShopVN Monitor**
4. Đổi time range sang **Last 1 hour**

#### 6.3 Khắc phục lỗi Kibana Alerting
Nếu vào Rules thấy **"Additional setup required"**, chạy:
```powershell
docker compose up -d --force-recreate kibana
```
Sau đó tạo lại data view và import dashboard.

### 7. Tạo dữ liệu log
Truy cập các trang shop, đăng nhập, tìm kiếm, thêm sản phẩm vào giỏ, đặt hàng để sinh log tự nhiên.

### 8. Mô phỏng tấn công (PowerShell)

> ⚠️ Chỉ chạy trên môi trường lab của nhóm. Không tấn công hệ thống thật.

> Nếu đã test trước đó, unban IP trước khi chạy lại:
> ```powershell
> docker exec mini-siem-fail2ban-1 fail2ban-client unban --all
> ```

**Chạy tất cả attacks cùng lúc:**
```powershell
# Brute force
for ($i=1; $i -le 50; $i++) {
  Invoke-WebRequest -Uri "http://localhost/login.php" -Method POST -Body "username=admin&password=wrong$i" -UseBasicParsing | Out-Null
  Write-Host "Brute force $i sent"
}

# SQL Injection
$payloads = @("1' OR '1'='1", "UNION SELECT * FROM users", "1; DROP TABLE users--", "admin'--")
foreach ($p in $payloads) {
  Invoke-WebRequest -Uri "http://localhost/search.php?q=$p" -UseBasicParsing | Out-Null
  Write-Host "SQL injection: $p"
}

# Directory Scan
$paths = @("/admin", "/wp-admin", "/config.php", "/.env", "/phpmyadmin", "/shell.php", "/backup.sql", "/etc/passwd")
foreach ($p in $paths) {
  Invoke-WebRequest -Uri "http://localhost$p" -UseBasicParsing -ErrorAction SilentlyContinue | Out-Null
  Write-Host "Scan: $p"
}

# DDoS simulation
for ($i=1; $i -le 500; $i++) {
  Invoke-WebRequest -Uri "http://localhost/" -UseBasicParsing | Out-Null
  if ($i % 100 -eq 0) { Write-Host "DDoS: $i/500" }
}
```

### 9. Kiểm tra Fail2Ban

```powershell
# Xem trạng thái brute force jail
docker exec mini-siem-fail2ban-1 fail2ban-client status apache-auth

# Xem trạng thái scan jail
docker exec mini-siem-fail2ban-1 fail2ban-client status apache-scan

# Unban tất cả IP
docker exec mini-siem-fail2ban-1 fail2ban-client unban --all
```

### 10. Kibana Detection Queries

Dùng các query sau trong **Kibana → Discover** để kiểm tra:

| Tấn công | KQL Query |
|----------|-----------|
| Directory Scan | `http.response.status_code: 404` |
| SQL Injection | `url.original: *union* or url.original: *drop*` |
| Brute Force | `url.original: *login*` |

---

## Kiến trúc hệ thống
ShopVN (PHP + Apache)
↓ Apache access.log / error.log
Filebeat
↓ port 5044
Logstash (parse + detect + enrich)
↓
Elasticsearch (lưu trữ, index theo ngày)
↓
Kibana (dashboard, alerting rules)
Fail2Ban (tự động block IP tấn công)
SSL/TLS (mã hóa traffic qua HTTPS)

---

## Tính năng bảo mật đã triển khai

| Tính năng | Công cụ | Mô tả |
|-----------|---------|-------|
| ✅ Thu thập log tự động | Filebeat | Đọc Apache log liên tục |
| ✅ Parse & enrich log | Logstash + Grok | Tách trường, thêm GeoIP |
| ✅ Phát hiện Brute-force | Logstash rule | Tag & Kibana alert |
| ✅ Phát hiện SQL Injection | Logstash regex | Pattern matching URL |
| ✅ Phát hiện Directory Scan | Logstash rule | HTTP 404 threshold |
| ✅ Phát hiện DDoS | Kibana dashboard | Request rate spike |
| ✅ Dashboard giám sát | Kibana | Real-time, auto-refresh 10s |
| ✅ Cảnh báo tự động | Kibana Alerting | 3 rules, check mỗi 1 phút |
| ✅ Tự động block IP | Fail2Ban | Ban 1 giờ sau khi vi phạm |
| ✅ Mã hóa traffic | SSL/TLS (OpenSSL) | Self-signed cert, HTTPS |
| ✅ Hash mật khẩu | bcrypt | PASSWORD_DEFAULT PHP |
| ✅ Admin panel | PHP | Quản lý đơn hàng, users |
| ✅ Đăng ký tài khoản | PHP + MySQL | Lưu hash vào DB |