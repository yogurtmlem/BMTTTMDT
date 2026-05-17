# Mini-SIEM - ShopVN Monitor

Hệ thống giám sát an ninh mạng (mini-SIEM) cho website TMĐT mô phỏng.

## Yêu cầu
- Docker Desktop
- Git

## Cách chạy

### 1. Clone repo
```bash
git clone <repo-url>
cd mini-siem
```

### 2. Khởi động hệ thống
```bash
docker compose up -d --build
```
Lần đầu mất 5-10 phút để tải images.

### 3. Truy cập
| Service | URL |
|---------|-----|
| ShopVN Website | http://localhost |
| Kibana Dashboard | http://localhost:5601 |
| Elasticsearch | http://localhost:9200 |

### 4. Đăng nhập shop
- Username: `admin`
- Password: `admin123`

### 5. Import Kibana Dashboard
1. Vào http://localhost:5601
2. Menu → Stack Management → Saved Objects
3. Click Import → chọn file `export.ndjson`
4. Vào Dashboards → mở **Mini-SIEM - ShopVN Monitor**

### 6. Tạo dữ liệu log
Truy cập các trang của shop, thêm sản phẩm vào giỏ, đăng nhập...

### 7. Mô phỏng tấn công (PowerShell)

**Brute force:**
```powershell
for ($i=1; $i -le 50; $i++) {
  Invoke-WebRequest -Uri "http://localhost/login.php" -Method POST -Body "username=admin&password=wrong$i" -UseBasicParsing | Out-Null
}
```

**SQL Injection:**
```powershell
$payloads = @("1' OR '1'='1", "UNION SELECT * FROM users", "1; DROP TABLE users--")
foreach ($p in $payloads) {
  Invoke-WebRequest -Uri "http://localhost/search.php?q=$p" -UseBasicParsing | Out-Null
}
```

**Directory Scan:**
```powershell
$paths = @("/admin", "/wp-admin", "/config.php", "/.env", "/phpmyadmin")
foreach ($p in $paths) {
  Invoke-WebRequest -Uri "http://localhost$p" -UseBasicParsing -ErrorAction SilentlyContinue | Out-Null
}
```

## Kiến trúc hệ thống
ShopVN (PHP+Apache) → Apache Logs
↓
Filebeat
↓
Logstash (parse + detect)
↓
Elasticsearch
↓
Kibana