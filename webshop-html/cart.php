<?php
session_start();
require 'db.php';

$session_id = session_id();

// Add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $product_id = $_POST['product_id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE session_id = ? AND product_id = ?");
        $stmt->execute([$session_id, $product_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE session_id = ? AND product_id = ?")
                ->execute([$session_id, $product_id]);
        } else {
            $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, 1)")
                ->execute([$session_id, $product_id]);
        }
        header('Location: cart.php');
        exit;
    }
    
    if ($action === 'remove') {
        $product_id = $_POST['product_id'] ?? 0;
        $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?")
            ->execute([$session_id, $product_id]);
        header('Location: cart.php');
        exit;
    }

    if ($action === 'checkout') {
        $stmt = $pdo->prepare("
            SELECT c.quantity, p.price 
            FROM cart c JOIN products p ON c.product_id = p.id 
            WHERE c.session_id = ?
        ");
        $stmt->execute([$session_id]);
        $items = $stmt->fetchAll();
        $total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
        
        $pdo->prepare("INSERT INTO orders (session_id, total) VALUES (?, ?)")
            ->execute([$session_id, $total]);
        $pdo->prepare("DELETE FROM cart WHERE session_id = ?")->execute([$session_id]);
        
        header('Location: checkout.php?success=1');
        exit;
    }
}

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.emoji
    FROM cart c JOIN products p ON c.product_id = p.id
    WHERE c.session_id = ?
");
$stmt->execute([$session_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<title>Giỏ hàng - ShopVN</title>
<link rel="stylesheet" href="style.css"/>
</head>
<body>
<nav>
  <div class="logo">Shop<span>VN</span></div>
  <div class="nav-links">
    <a href="index.php">Trang chủ</a>
    <a href="products.php">Sản phẩm</a>
    <a href="search.php">Tìm kiếm</a>
    <?php if (isset($_SESSION['user'])): ?>
      <span style="color:#1D9E75;font-size:14px">👤 <?= $_SESSION['user'] ?></span>
      <a href="logout.php">Đăng xuất</a>
    <?php else: ?>
      <a href="login.php">Đăng nhập</a>
    <?php endif; ?>
    <button class="cart-btn">🛒 Giỏ hàng (<?= count($items) ?>)</button>
  </div>
</nav>

<div class="section">
  <div class="section-title">Giỏ hàng của bạn</div>
  
  <?php if (empty($items)): ?>
    <div style="text-align:center;padding:3rem;color:#888">
      <p style="font-size:48px">🛒</p>
      <p>Giỏ hàng trống</p>
      <a href="products.php" style="color:#1D9E75">Tiếp tục mua sắm →</a>
    </div>
  <?php else: ?>
    <div style="max-width:700px;margin:0 auto">
      <?php foreach ($items as $item): ?>
      <div style="background:#fff;border:1px solid #e0e0e0;border-radius:12px;padding:1rem;margin-bottom:1rem;display:flex;align-items:center;gap:1rem">
        <div style="font-size:36px"><?= $item['emoji'] ?></div>
        <div style="flex:1">
          <div style="font-weight:bold"><?= $item['name'] ?></div>
          <div style="color:#1D9E75"><?= number_format($item['price']) ?>₫</div>
        </div>
        <div style="display:flex;align-items:center;gap:0.5rem">
          <span>Số lượng: <?= $item['quantity'] ?></span>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="remove"/>
            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>"/>
            <button type="submit" style="background:#fee;border:1px solid #fcc;border-radius:6px;padding:4px 8px;cursor:pointer;color:#c00">Xóa</button>
          </form>
        </div>
        <div style="font-weight:bold;min-width:100px;text-align:right"><?= number_format($item['price'] * $item['quantity']) ?>₫</div>
      </div>
      <?php endforeach; ?>
      
      <div style="background:#f0faf6;border:1px solid #9FE1CB;border-radius:12px;padding:1.5rem;margin-top:1rem">
        <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:bold">
          <span>Tổng cộng:</span>
          <span style="color:#1D9E75"><?= number_format($total) ?>₫</span>
        </div>
        <form method="POST" style="margin-top:1rem">
          <input type="hidden" name="action" value="checkout"/>
          <button type="submit" class="hero-btn" style="width:100%">Đặt hàng ngay</button>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

<footer>
  <p>© 2026 ShopVN. All rights reserved.</p>
  <div class="footer-links">
    <a href="#">Chính sách</a>
    <a href="#">Liên hệ</a>
    <a href="#">Hỗ trợ</a>
  </div>
</footer>
</body>
</html>