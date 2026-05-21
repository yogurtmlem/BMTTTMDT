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

$productImages = [
    'Giày thể thao Urban' => 'shoes.jpg',
    'Túi xách da thật' => 'bag.jpg',
    'Đồng hồ thông minh' => 'watch.jpg',
    'Tai nghe không dây' => 'headphones.jpg',
    'Ốp lưng điện thoại' => 'case.jpg',
    'Bàn phím cơ gaming' => 'gaming.jpg',
    'Kính mắt thời trang' => 'sunglasses.jpg',
    'Balo laptop cao cấp' => 'balo.jpg',
    'Chuột không dây' => 'mouse.jpg',
    'Mũ lưỡi trai' => 'hat.jpg',
    'Đèn LED thông minh' => 'light.jpg',
    'Pin dự phòng 20000mAh' => 'battery.jpg'
];

$productConditions = [
    'Giày thể thao Urban' => 'Đã kiểm định · Tình trạng tốt',
    'Túi xách da thật' => 'Đã vệ sinh · Ít dấu sử dụng',
    'Đồng hồ thông minh' => 'Đã test chức năng · Hoạt động tốt',
    'Tai nghe không dây' => 'Đã kiểm tra pin · Âm thanh ổn định',
    'Ốp lưng điện thoại' => 'Hàng chọn lọc · Gần như mới',
    'Bàn phím cơ gaming' => 'Đã test phím · Dùng tốt',
    'Kính mắt thời trang' => 'Đã kiểm tra ngoại quan',
    'Balo laptop cao cấp' => 'Đã làm sạch · Còn mới',
    'Chuột không dây' => 'Đã test kết nối',
    'Mũ lưỡi trai' => 'Đã làm sạch',
    'Đèn LED thông minh' => 'Đã test sáng',
    'Pin dự phòng 20000mAh' => 'Đã kiểm tra sạc'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>ShopVN Re-commerce - Sản phẩm đã kiểm định</title>
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
      <span class="user-badge">👤 <?= htmlspecialchars($_SESSION['user']) ?></span>
      <?php if ($_SESSION['user'] === 'admin'): ?>
      <a href="admin/">Admin</a>
    <?php endif; ?>
    <a href="logout.php">Đăng xuất</a>
    <?php else: ?>
      <a href="login.php">Đăng nhập</a>
    <?php endif; ?>

    <a href="cart.php">
      <button class="cart-btn">🛒 Giỏ hàng (<?= $cart_count ?>)</button>
    </a>
  </div>
</nav>

<section class="hero">
  <div class="hero-content">
    <span class="hero-badge">Re-commerce đã kiểm định</span>

    <h1>Mua sắm đồ đã qua chọn lọc, minh bạch và tiết kiệm hơn</h1>

    <p>
      ShopVN tập trung vào các sản phẩm đã được kiểm tra tình trạng,
      làm sạch cơ bản và mô tả rõ trước khi bán lại.
    </p>

    <a href="products.php">
      <button class="hero-btn">Xem sản phẩm đã kiểm định</button>
    </a>
  </div>

  <div class="hero-visual">♻️</div>
</section>

<section class="features">
  <div>✅ <span>Kiểm tra trước khi bán</span></div>
  <div>♻️ <span>Mua sắm bền vững</span></div>
  <div>🛡️ <span>Đổi trả trong 7 ngày</span></div>
  <div>📦 <span>Đóng gói cẩn thận</span></div>
</section>

<section class="section">

  <div class="section-header">
    <div>
      <p>Sản phẩm nổi bật</p>
      <h2>Hàng đã kiểm định hôm nay</h2>
    </div>

    <a href="products.php">Xem tất cả →</a>
  </div>

  <div class="products">

    <?php foreach ($products as $p): ?>

      <?php
        $img = $productImages[$p['name']] ?? 'battery.jpg';
        $condition = $productConditions[$p['name']] ?? 'Đã kiểm định · Tình trạng tốt';
      ?>

      <div class="product-card">

        <div class="product-img">
          <img
            src="assets/images/<?= $img ?>"
            alt="<?= htmlspecialchars($p['name']) ?>"
            loading="lazy"
          >
        </div>

        <div class="product-info">

          <div class="product-name">
            <?= htmlspecialchars($p['name']) ?>
          </div>

          <div class="condition-badge">
            <?= htmlspecialchars($condition) ?>
          </div>

          <div class="product-meta">
            Mô tả tình trạng rõ ràng · Kiểm tra cơ bản trước khi giao
          </div>

          <div class="product-stars">
            <?= str_repeat('★', round($p['rating'])) ?>
            <?= str_repeat('☆', 5 - round($p['rating'])) ?>
            <span>(<?= htmlspecialchars($p['reviews']) ?> đánh giá)</span>
          </div>

          <div class="product-price">
            <?= number_format($p['price']) ?>₫
          </div>

          <form method="POST" action="cart.php">
            <input type="hidden" name="action" value="add"/>
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($p['id']) ?>"/>

            <button type="submit" class="add-btn">
              + Thêm vào giỏ
            </button>
          </form>

        </div>
      </div>

    <?php endforeach; ?>

  </div>

</section>

<div class="banner">
  <div>
    <p>Cam kết minh bạch tình trạng sản phẩm</p>
    <span>Sản phẩm được phân loại, kiểm tra và hiển thị thông tin rõ ràng trước khi bán lại.</span>
  </div>

  <span>Quy trình kiểm định →</span>
</div>

<footer>
  <p>© 2026 ShopVN Re-commerce. Nền tảng mua sắm bền vững.</p>

  <div class="footer-links">
    <a href="#">Quy trình kiểm định</a>
    <a href="#">Chính sách đổi trả</a>
    <a href="#">Hỗ trợ khách hàng</a>
  </div>
</footer>

</body>
</html>
