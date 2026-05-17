<?php
session_start();
require 'db.php';

$q = $_GET['q'] ?? '';
$products = [];

if ($q) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR category LIKE ?");
    $stmt->execute(["%$q%", "%$q%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$cart_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE session_id = ?");
$cart_stmt->execute([session_id()]);
$cart_count = $cart_stmt->fetchColumn() ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<title>Tìm kiếm - ShopVN</title>
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

<div class="search-section">
  <div class="section-title">Tìm kiếm sản phẩm</div>

  <form method="GET">
    <div class="search-bar">
      <input type="text" name="q" placeholder="Nhập tên sản phẩm..." value="<?= htmlspecialchars($q) ?>"/>
      <button type="submit">🔍 Tìm</button>
    </div>
  </form>

  <div class="search-tags">
    <a href="search.php?q=giày"><span class="tag">Giày thể thao</span></a>
    <a href="search.php?q=túi"><span class="tag">Túi xách</span></a>
    <a href="search.php?q=đồng hồ"><span class="tag">Đồng hồ</span></a>
    <a href="search.php?q=tai nghe"><span class="tag">Tai nghe</span></a>
    <a href="search.php?q=điện tử"><span class="tag">Điện tử</span></a>
    <a href="search.php?q=phụ kiện"><span class="tag">Phụ kiện</span></a>
  </div>

  <?php if ($q): ?>
    <div class="section-title">
      <?= count($products) ?> kết quả cho "<?= htmlspecialchars($q) ?>"
    </div>
    <?php if (empty($products)): ?>
      <p style="color:#888;padding:2rem 0">Không tìm thấy sản phẩm nào.</p>
    <?php else: ?>
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
    <?php endif; ?>
  <?php else: ?>
    <div class="section-title">Sản phẩm phổ biến</div>
    <div class="products">
      <?php
      $all = $pdo->query("SELECT * FROM products LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
      foreach ($all as $p): ?>
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