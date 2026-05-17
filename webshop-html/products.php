<?php
session_start();
require 'db.php';

$category = $_GET['category'] ?? '';
if ($category) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ?");
    $stmt->execute([$category]);
} else {
    $stmt = $pdo->query("SELECT * FROM products");
}
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cart_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE session_id = ?");
$cart_stmt->execute([session_id()]);
$cart_count = $cart_stmt->fetchColumn() ?? 0;

$categories = ['Thời trang', 'Điện tử', 'Phụ kiện'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<title>Sản phẩm - ShopVN</title>
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
    <a href="cart.php"><button class="cart-btn">🛒 Giỏ hàng (<?= $cart_count ?>)</button></a>
  </div>
</nav>

<div class="section">
  <div class="section-title">Tất cả sản phẩm</div>

  <div class="search-tags" style="margin-bottom:1.5rem">
    <a href="products.php"><span class="tag <?= !$category ? 'active' : '' ?>">Tất cả</span></a>
    <?php foreach ($categories as $cat): ?>
    <a href="products.php?category=<?= $cat ?>"><span class="tag <?= $category === $cat ? 'active' : '' ?>"><?= $cat ?></span></a>
    <?php endforeach; ?>
  </div>

  <div class="products">
    <?php foreach ($products as $p): ?>
    <div class="product-card">
      <div class="product-img"><?= $p['emoji'] ?></div>
      <div class="product-info">
        <div class="product-name"><?= $p['name'] ?></div>
        <div class="product-stars"><?= str_repeat('★', round($p['rating'])) ?><?= str_repeat('☆', 5-round($p['rating'])) ?> (<?= $p['reviews'] ?>)</div>
        <div class="product-price"><?= number_format($p['price']) ?>₫</div>
        <form method="POST" action="cart.php">
          <input type="hidden" name="action" value="add"/>
          <input type="hidden" name="product_id" value="<?= $p['id'] ?>"/>
          <button type="submit" class="add-btn">+ Thêm vào giỏ</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
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