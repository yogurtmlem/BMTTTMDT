<?php
session_start();
require 'db.php';

if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM products");
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
    'Giày thể thao Urban' => 'Đã kiểm định · Tình trạng 90%',
    'Túi xách da thật' => 'Đã kiểm định · Ít dấu sử dụng',
    'Đồng hồ thông minh' => 'Đã kiểm định · Hoạt động tốt',
    'Tai nghe không dây' => 'Đã kiểm định · Pin ổn định',
    'Ốp lưng điện thoại' => 'Hàng chọn lọc · Như mới',
    'Bàn phím cơ gaming' => 'Đã test phím · Hoạt động tốt',
    'Kính mắt thời trang' => 'Đã kiểm tra · Tình trạng tốt',
    'Balo laptop cao cấp' => 'Đã vệ sinh · Còn mới',
    'Chuột không dây' => 'Đã test kết nối · Dùng tốt',
    'Mũ lưỡi trai' => 'Đã làm sạch · Tình trạng tốt',
    'Đèn LED thông minh' => 'Đã test sáng · Hoạt động tốt',
    'Pin dự phòng 20000mAh' => 'Đã kiểm tra sạc · Dung lượng ổn'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sản phẩm đã kiểm định - ShopVN Re-commerce</title>

<link rel="stylesheet" href="style.css"/>

<body>

<nav>
  <div class="logo">Shop<span>VN</span></div>

  <div class="nav-links">
    <a href="index.php">Trang chủ</a>
    <a href="products.php">Sản phẩm</a>
    <a href="search.php">Tìm kiếm</a>

    <?php if (isset($_SESSION['user'])): ?>
      <span class="user-badge">👤 <?= htmlspecialchars($_SESSION['user']) ?></span>
      <a href="logout.php">Đăng xuất</a>
    <?php else: ?>
      <a href="login.php">Đăng nhập</a>
    <?php endif; ?>

    <a href="cart.php">
      <button class="cart-btn">🛒 Giỏ hàng (<?= $cart_count ?>)</button>
    </a>
  </div>
</nav>

<section class="trust-strip">
  <div class="trust-item">
    <strong>✅ Đã kiểm định</strong>
    Sản phẩm được kiểm tra trước khi đăng bán.
  </div>

  <div class="trust-item">
    <strong>♻️ Re-commerce</strong>
    Mua sắm tiết kiệm và giảm lãng phí.
  </div>

  <div class="trust-item">
    <strong>🛡️ Đổi trả 7 ngày</strong>
    Hỗ trợ đổi trả nếu sản phẩm không đúng mô tả.
  </div>

  <div class="trust-item">
    <strong>📦 Giao hàng an toàn</strong>
    Đóng gói kỹ, theo dõi đơn hàng rõ ràng.
  </div>
</section>

<section class="section products-page">

  <div class="section-header">
    <div>
      <p>ShopVN Re-commerce</p>
      <h2>Sản phẩm đã qua kiểm định</h2>
    </div>
    <a href="search.php">Tìm sản phẩm →</a>
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
            Minh bạch tình trạng · Kiểm tra chức năng · Đóng gói trước khi giao
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

<div class="recommerce-note">
  <div>
    <p>Cam kết minh bạch tình trạng sản phẩm</p>
    <span>Mỗi sản phẩm được phân loại, kiểm tra và mô tả rõ trước khi bán lại.</span>
  </div>
  <span>ShopVN Re-commerce →</span>
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
