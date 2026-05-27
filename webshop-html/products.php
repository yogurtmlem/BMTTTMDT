<?php
session_start();
require __DIR__ . '/db.php';

$session_id = session_id();
$products = [];
$cart_count = 0;
$error_message = '';

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

function product_image(array $product, array $productImages): string
{
    if (!empty($product['image'])) {
        return basename($product['image']);
    }

    return $productImages[$product['name']] ?? 'battery.jpg';
}

function product_stock_label(?int $stock): string
{
    if ($stock === null) {
        return 'Còn hàng';
    }

    if ($stock <= 0) {
        return 'Hết hàng';
    }

    if ($stock <= 3) {
        return 'Sắp hết · còn ' . $stock . ' sản phẩm';
    }

    return 'Còn ' . $stock . ' sản phẩm';
}

if ($pdo) {
    try {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $per_page = 6;
        $offset = ($page - 1) * $per_page;

        $total_stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $total_products = (int) $total_stmt->fetchColumn();
        $total_pages = (int) ceil($total_products / $per_page);

        $stmt = $pdo->prepare("SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cart_stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = ?");
        $cart_stmt->execute([$session_id]);
        $cart_count = (int) $cart_stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Products page error: ' . $e->getMessage());
        $error_message = 'Không thể tải danh sách sản phẩm. Vui lòng thử lại sau.';
    }
} else {
    $error_message = 'Không thể kết nối cơ sở dữ liệu.';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sản phẩm đã kiểm định - ShopVN Re-commerce</title>
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
      <a href="profile.php">Hồ sơ</a>
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
    <strong>📦 Quản lý tồn kho</strong>
    Sản phẩm hết hàng sẽ tự khóa nút thêm vào giỏ.
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

  <?php if ($error_message !== ''): ?>
    <div class="empty-state">
      <h3>Chưa tải được sản phẩm</h3>
      <p><?= htmlspecialchars($error_message) ?></p>
    </div>
  <?php elseif (empty($products)): ?>
    <div class="empty-state">
      <h3>Chưa có sản phẩm nào</h3>
      <p>Admin cần thêm sản phẩm vào hệ thống trước khi trang này hiển thị dữ liệu.</p>
    </div>
  <?php else: ?>
    <div class="products">
      <?php foreach ($products as $p): ?>
        <?php
          $img = product_image($p, $productImages);
          $condition = $productConditions[$p['name']] ?? 'Đã kiểm định · Tình trạng tốt';
          $stock = array_key_exists('stock', $p) ? (int) $p['stock'] : null;
          $is_out_of_stock = ($stock !== null && $stock <= 0);
          $rating = isset($p['rating']) ? max(0, min(5, (int) round($p['rating']))) : 0;
          $reviews = isset($p['reviews']) ? (int) $p['reviews'] : 0;
        ?>

        <div class="product-card">
          <a class="product-img" href="product.php?id=<?= urlencode((string) $p['id']) ?>" aria-label="Xem chi tiết <?= htmlspecialchars($p['name']) ?>">
            <img
              src="assets/images/<?= htmlspecialchars($img) ?>"
              alt="<?= htmlspecialchars($p['name']) ?>"
              loading="lazy"
            >
          </a>

          <div class="product-info">
            <a href="product.php?id=<?= urlencode((string) $p['id']) ?>" style="text-decoration:none;">
              <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
            </a>

            <div class="condition-badge"><?= htmlspecialchars($condition) ?></div>

            <div class="product-meta">
              <?= htmlspecialchars($p['category'] ?? 'Sản phẩm đã kiểm định') ?> ·
              <?= htmlspecialchars(product_stock_label($stock)) ?>
            </div>

            <div class="product-stars">
              <?= str_repeat('★', $rating) ?><?= str_repeat('☆', 5 - $rating) ?>
              <span>(<?= htmlspecialchars((string) $reviews) ?> đánh giá)</span>
            </div>

            <div class="product-price"><?= number_format((float) $p['price']) ?>₫</div>

            <?php if ($is_out_of_stock): ?>
              <button type="button" class="add-btn" disabled style="opacity:0.55; cursor:not-allowed;">
                Hết hàng
              </button>
            <?php else: ?>
              <form method="POST" action="cart.php">
                <input type="hidden" name="action" value="add"/>
                <input type="hidden" name="product_id" value="<?= htmlspecialchars((string) $p['id']) ?>"/>
                <button type="submit" class="add-btn">+ Thêm vào giỏ</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
        <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="page-btn">← Trước</a>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>" class="page-btn">Tiếp →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</section>

<div class="recommerce-note">
  <div>
    <p>Cam kết minh bạch tình trạng và tồn kho</p>
    <span>Mỗi sản phẩm được phân loại, kiểm tra, hiển thị tồn kho và mô tả rõ trước khi bán lại.</span>
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
