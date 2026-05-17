SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

USE shopdb;

ALTER DATABASE shopdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  category VARCHAR(50),
  emoji VARCHAR(10),
  stock INT DEFAULT 100,
  rating DECIMAL(2,1) DEFAULT 4.5,
  reviews INT DEFAULT 0
);

CREATE TABLE cart (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(100),
  product_id INT,
  quantity INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(100),
  total DECIMAL(10,2),
  status VARCHAR(20) DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (username, password, email) VALUES
('admin', 'admin123', 'admin@shopvn.com'),
('nguyenvana', 'password123', 'vana@gmail.com'),
('tranthib', 'password123', 'thib@gmail.com');

INSERT INTO products (name, price, category, emoji, stock, rating, reviews) VALUES
('Giày thể thao Urban', 850000, 'Thời trang', '👟', 50, 5.0, 128),
('Túi xách da thật', 1200000, 'Thời trang', '👜', 30, 4.0, 84),
('Đồng hồ thông minh', 2500000, 'Điện tử', '⌚', 20, 5.0, 210),
('Tai nghe không dây', 680000, 'Điện tử', '🎧', 45, 4.0, 97),
('Ốp lưng điện thoại', 120000, 'Phụ kiện', '📱', 200, 5.0, 305),
('Bàn phím cơ gaming', 1890000, 'Điện tử', '💻', 15, 4.0, 56),
('Kính mát thời trang', 450000, 'Thời trang', '🕶️', 60, 4.0, 43),
('Balo laptop cao cấp', 750000, 'Phụ kiện', '🎒', 35, 5.0, 172),
('Chuột không dây', 320000, 'Điện tử', '🖱️', 80, 4.0, 89),
('Mũ lưỡi trai', 180000, 'Thời trang', '🧢', 100, 5.0, 61),
('Đèn LED thông minh', 290000, 'Điện tử', '💡', 55, 4.0, 34),
('Pin dự phòng 20000mAh', 550000, 'Phụ kiện', '🔋', 40, 5.0, 198);