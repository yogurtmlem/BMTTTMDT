# Mini-SIEM - ShopVN Monitor

Hệ thống giám sát an ninh mạng (mini-SIEM) cho website TMĐT mô phỏng, xây dựng trên ELK Stack + Fail2Ban.

## Yêu cầu
- Docker Desktop
- Git

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
Lần đầu mất 5-10 phút để tải images.

### 3. Cấu hình SSL/TLS HTTPS 

Chạy lệnh sau để tạo SSL certificate bên trong container webshop:

```bash
docker exec -i mini-siem-webshop-1 bash -c "
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
-keyout /etc/ssl/private/shopvn.key \
-out /etc/ssl/certs/shopvn.crt \
-subj '/CN=localhost/O=ShopVN/C=VN'
"
```

Tiếp theo, cấu hình và reload Apache:

```bash
docker exec -i mini-siem-webshop-1 bash -c "
a2enmod ssl && \
printf '<VirtualHost _default_:443>\n\
  DocumentRoot /var/www/html\n\
  SSLEngine on\n\
  SSLCertificateFile /etc/ssl/certs/shopvn.crt\n\
  SSLCertificateKeyFile /etc/ssl/private/shopvn.key\n\
  <Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
  </Directory>\n\
</VirtualHost>\n' > /etc/apache2/sites-available/ssl.conf && \
a2ensite ssl && \
service apache2 reload
"
```
> Certificate và private key không được push lên GitHub vì lý do bảo mật. Mỗi collaborator cần tự generate certificate trên máy local.

### 4. Truy cập
| Service | URL | Mô tả |
|---------|-----|-------|
| ShopVN Website (HTTP) | http://localhost | Website TMĐT mô phỏng |
| ShopVN Website (HTTPS) | https://localhost | Website với SSL/TLS mã hóa |
| Admin Panel | http://localhost/admin | Quản trị đơn hàng, người dùng |
| Kibana Dashboard | http://localhost:5601 | Dashboard giám sát |
| Elasticsearch | http://localhost:9200 | Kiểm tra dữ liệu log |

> Khi truy cập HTTPS, trình duyệt sẽ hiển thị cảnh báo bảo mật do hệ thống sử dụng self-signed certificate trong môi trường lab. Chọn **Advanced → Proceed to localhost** để tiếp tục.

### 5. Tài khoản mặc định
| Tài khoản | Username | Password |
|-----------|----------|----------|
| Admin shop | `admin` | `admin123` |
| Người dùng | `nguyenvana` | `password123` |

### 6. Import Kibana Dashboard
1. Vào http://localhost:5601
2. Menu → Stack Management → Saved Objects
3. Click **Import** → chọn file `export.ndjson`
4. Vào Dashboards → mở **Mini-SIEM - ShopVN Monitor**

### 7. Tạo dữ liệu log
Truy cập các trang của shop, đăng nhập, thêm sản phẩm vào giỏ, đặt hàng...

### 8. Mô phỏng tấn công (PowerShell)

**Brute force login:**
```powershell
for ($i=1; $i -le 50; $i++) {
  Invoke-WebRequest -Uri "http://localhost/login.php" -Method POST -Body "username=admin&password=wrong$i" -UseBasicParsing | Out-Null
  Write-Host "Request $i sent"
}
```

**SQL Injection:**
```powershell
$payloads = @("1' OR '1'='1", "UNION SELECT * FROM users", "1; DROP TABLE users--", "admin'--")
foreach ($p in $payloads) {
  Invoke-WebRequest -Uri "http://localhost/search.php?q=$p" -UseBasicParsing | Out-Null
  Write-Host "SQL injection sent: $p"
}
```

**Directory Scan:**
```powershell
$paths = @("/admin", "/wp-admin", "/config.php", "/.env", "/phpmyadmin", "/shell.php", "/backup.sql")
foreach ($p in $paths) {
  Invoke-WebRequest -Uri "http://localhost$p" -UseBasicParsing -ErrorAction SilentlyContinue | Out-Null
  Write-Host "Scan sent: $p"
}
```

**DDoS simulation:**
```powershell
for ($i=1; $i -le 500; $i++) {
  Invoke-WebRequest -Uri "http://localhost/" -UseBasicParsing | Out-Null
  if ($i % 100 -eq 0) { Write-Host "$i/500 sent" }
}
```

### 9. Kiểm tra Fail2Ban
```powershell
# Xem trạng thái jail
docker exec mini-siem-fail2ban-1 fail2ban-client status apache-auth

# Unban tất cả IP
docker exec mini-siem-fail2ban-1 fail2ban-client unban --all
```

### 10. Kibana Alerting Rules
Ba rule cảnh báo tự động được cấu hình sẵn:
- **Brute Force Detection** — phát hiện khi >10 request đến `/login.php` trong 1 phút
- **Directory Scan Detection** — phát hiện khi >15 lần 404 trong 1 phút  
- **SQL Injection Detection** — phát hiện từ khóa SQL trong URL

## Kiến trúc hệ thống
ShopVN (PHP + Apache)
↓ Apache access.log / error.log
Filebeat
↓ port 5044
Logstash (parse + detect + enrich)
↓
Elasticsearch (lưu trữ, index)
↓
Kibana (dashboard, alerting)
Fail2Ban (tự động block IP tấn công)

## Tính năng bảo mật đã triển khai
- ✅ Thu thập log tự động qua Filebeat
- ✅ Parse log bằng Grok pattern
- ✅ Phát hiện Brute-force, SQL Injection, Directory Scan, DDoS
- ✅ Dashboard giám sát thời gian thực (Kibana)
- ✅ Cảnh báo tự động (Kibana Alerting Rules)
- ✅ Tự động block IP tấn công (Fail2Ban)
- ✅ Mật khẩu người dùng được hash (bcrypt)
- ✅ Admin panel quản trị hệ thống
- ✅ SSL/TLS HTTPS encryption với OpenSSL