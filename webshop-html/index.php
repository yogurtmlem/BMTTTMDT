<?php
session_start();
require 'db.php';
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM products LIMIT 6");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $cart_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE session_id = ?");
    $cart_stmt->execute([session_id()]);
    $cart_count = $cart_stmt->fetchColumn() ?? 0;
} else {
    $products = [];
    $cart_count = 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>ShopVN - Mua sắm thông minh</title>
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
      <span class="user-badge">👤 <?= $_SESSION['user'] ?></span>
      <a href="logout.php">Đăng xuất</a>
    <?php else: ?>
      <a href="login.php">Đăng nhập</a>
    <?php endif; ?>
    <a href="cart.php"><button class="cart-btn">🛒 Giỏ hàng (<?= $cart_count ?>)</button></a>
  </div>
</nav>

<section class="hero">
  <div class="hero-content">
    <span class="hero-badge">Ưu đãi hôm nay</span>
    <h1>ShopVN - Mua sắm tiện lợi mỗi ngày</h1>
    <p>Sản phẩm chất lượng, giá tốt, giao hàng nhanh và thanh toán an toàn.</p>
    <a href="products.php"><button class="hero-btn">Khám phá sản phẩm</button></a>
  </div>
  <div class="hero-visual">🛍️</div>
</section>

<section class="features">
  <div>🚚 <span>Giao hàng nhanh</span></div>
  <div>💳 <span>Thanh toán an toàn</span></div>
  <div>⭐ <span>Sản phẩm uy tín</span></div>
  <div>🔄 <span>Đổi trả dễ dàng</span></div>
</section>

<section class="section">
  <div class="section-header">
    <div>
      <p>Gợi ý cho bạn</p>
      <h2>Sản phẩm nổi bật</h2>
    </div>
    <a href="products.php">Xem tất cả →</a>
  </div>

  <div class="products">
    <?php foreach ($products as $p): ?>
    <div class="product-card">
      <div class="product-img"><?= $p['emoji'] ?></div>
      <div class="product-info">
        <div class="product-name"><?= $p['name'] ?></div>
        <div class="product-stars">
          <?= str_repeat('★', round($p['rating'])) ?><?= str_repeat('☆', 5-round($p['rating'])) ?>
          <span>(<?= $p['reviews'] ?>)</span>
        </div>
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
</section>

<div class="banner">
  <div>
    <p>🚚 Miễn phí vận chuyển đơn từ 500.000₫</p>
    <span>Giao hàng toàn quốc trong 2–3 ngày</span>
  </div>
  <span>Ưu đãi hôm nay →</span>
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
