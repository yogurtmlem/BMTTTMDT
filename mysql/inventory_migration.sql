-- Inventory management migration
-- Chạy file này nếu bảng products chưa có cột stock.
ALTER TABLE products
  ADD COLUMN stock INT NOT NULL DEFAULT 10;

-- Có thể chỉnh tồn kho mẫu theo sản phẩm hiện có:
-- UPDATE products SET stock = 5 WHERE id = 1;
-- UPDATE products SET stock = 0 WHERE id = 2;
