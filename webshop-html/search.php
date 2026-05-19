<?php
session_start();
require __DIR__ . '/db.php';

$q = trim($_GET['q'] ?? '');
$products = [];

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

if ($pdo && $q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR category LIKE ?");
    $stmt->execute(["%$q%", "%$q%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($pdo) {
    $cart_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE session_id = ?");
    $cart_stmt->execute([session_id()]);
    $cart_count = $cart_stmt->fetchColumn() ?? 0;
} else {
    $cart_count = 0;
}

$popularProducts = [];

if ($pdo && $q === '') {
    $popularProducts = $pdo->query("SELECT * FROM products LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Tìm kiếm sản phẩm đã kiểm định - ShopVN</title>
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
      <a href="logout.php">Đăng xuất</a>
    <?php else: ?>
      <a href="login.php">Đăng nhập</a>
    <?php endif; ?>

    <a href="cart.php">
      <button class="cart-btn">🛒 Giỏ hàng (<?= $cart_count ?>)</button>
    </a>
  </div>
</nav>

<section class="section products-page">

  <div class="section-header">
    <div>
      <p>Tìm kiếm ShopVN</p>
      <h2>Tìm sản phẩm đã qua kiểm định</h2>
    </div>
  </div>

  <div class="search-panel">
    <form method="GET" action="search.php">
      <div class="search-bar">
        <input
          type="text"
          name="q"
          placeholder="Nhập tên sản phẩm, danh mục hoặc phụ kiện cần tìm..."
          value="<?= htmlspecialchars($q) ?>"
        />
        <button type="submit">Tìm kiếm</button>
      </div>
    </form>

    <div class="search-tags">
      <a href="search.php?q=giày"><span class="tag">Giày</span></a>
      <a href="search.php?q=túi"><span class="tag">Túi xách</span></a>
      <a href="search.php?q=đồng hồ"><span class="tag">Đồng hồ</span></a>
      <a href="search.php?q=tai nghe"><span class="tag">Tai nghe</span></a>
      <a href="search.php?q=điện tử"><span class="tag">Điện tử</span></a>
      <a href="search.php?q=phụ kiện"><span class="tag">Phụ kiện</span></a>
    </div>
  </div>

  <?php if ($q !== ''): ?>

    <div class="section-header search-result-header">
      <div>
        <p>Kết quả tìm kiếm</p>
        <h2><?= count($products) ?> sản phẩm phù hợp với “<?= htmlspecialchars($q) ?>”</h2>
      </div>
    </div>

    <?php if (empty($products)): ?>

      <div class="empty-state">
        <h3>Chưa tìm thấy sản phẩm phù hợp</h3>
        <p>Thử tìm bằng từ khóa ngắn hơn như “giày”, “tai nghe”, “balo” hoặc “phụ kiện”.</p>
        <a href="products.php">Xem tất cả sản phẩm →</a>
      </div>

    <?php else: ?>

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

    <?php endif; ?>

  <?php else: ?>

    <div class="section-header search-result-header">
      <div>
        <p>Gợi ý tìm kiếm</p>
        <h2>Sản phẩm phổ biến</h2>
      </div>
      <a href="products.php">Xem tất cả →</a>
    </div>

    <div class="products">
      <?php foreach ($popularProducts as $p): ?>

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

  <?php endif; ?>

</section>

<div class="banner">
  <div>
    <p>Tìm đúng sản phẩm, rõ tình trạng</p>
    <span>ShopVN ưu tiên sản phẩm có mô tả minh bạch, đã kiểm tra cơ bản và đóng gói trước khi giao.</span>
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
