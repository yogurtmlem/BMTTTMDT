-- Run this once before testing profile.php order history.

ALTER TABLE orders
  ADD COLUMN user_id INT NULL AFTER id,
  ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER total,
  ADD COLUMN shipping_status VARCHAR(30) NOT NULL DEFAULT 'processing' AFTER status,
  ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER shipping_status;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_order_items_order_id (order_id),
  INDEX idx_order_items_product_id (product_id)
);