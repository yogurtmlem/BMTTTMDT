<?php
session_start();
require __DIR__ . '/db.php';

$session_id = session_id();
$product_id = (int) ($_GET['id'] ?? 0);
$product = null;
$relatedProducts = [];
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

function table_has_column(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        $cache[$key] = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Column check error: ' . $e->getMessage());
        $cache[$key] = false;
    }

    return $cache[$key];
}

function product_detail_image(array $product, array $productImages): string
{
    if (!empty($product['image'])) {
        return basename($product['image']);
    }

    return $productImages[$product['name']] ?? 'battery.jpg';
}

function product_detail_stock_label(?int $stock): string
{
    if ($stock === null) {
        return 'Còn hàng';
    }

    if ($stock <= 0) {
        return 'Hết hàng';
    }

    if ($stock <= 3) {
        return 'Sắp hết · chỉ còn ' . $stock . ' sản phẩm';
    }

    return 'Còn ' . $stock . ' sản phẩm';
}

function fallback_description(array $product): string
{
    $category = $product['category'] ?? 'sản phẩm';
    return 'Sản phẩm ' . $category . ' đã được ShopVN kiểm tra tình trạng cơ bản, làm sạch và mô tả minh bạch trước khi bán lại.';
}

if ($pdo) {
    try {
        $cart_stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = ?');
        $cart_stmt->execute([$session_id]);
        $cart_count = (int) $cart_stmt->fetchColumn();

        if ($product_id <= 0) {
            $error_message = 'Sản phẩm không hợp lệ.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if (!$product) {
                $error_message = 'Không tìm thấy sản phẩm.';
            } else {
                $related_stmt = $pdo->prepare('SELECT * FROM products WHERE category = ? AND id <> ? ORDER BY id DESC LIMIT 4');
                $related_stmt->execute([$product['category'] ?? '', $product_id]);
                $relatedProducts = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (PDOException $e) {
        error_log('Product detail page error: ' . $e->getMessage());
        $error_message = 'Không thể tải chi tiết sản phẩm. Vui lòng thử lại sau.';
    }
} else {
    $error_message = 'Không thể kết nối cơ sở dữ liệu.';
}

$stock = ($product && array_key_exists('stock', $product)) ? (int) $product['stock'] : null;
$is_out_of_stock = ($stock !== null && $stock <= 0);
$img = $product ? product_detail_image($product, $productImages) : 'battery.jpg';
$condition = $product ? ($productConditions[$product['name']] ?? 'Đã kiểm định · Tình trạng tốt') : '';
$rating = $product && isset($product['rating']) ? max(0, min(5, (int) round($product['rating']))) : 0;
$reviews = $product && isset($product['reviews']) ? (int) $product['reviews'] : 0;
$description = $product ? trim((string) ($product['description'] ?? '')) : '';
if ($product && $description === '') {
    $description = fallback_description($product);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= $product ? htmlspecialchars($product['name']) . ' - ShopVN' : 'Chi tiết sản phẩm - ShopVN' ?></title>
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

<main class="product-detail-page">
  <a class="breadcrumb-link" href="products.php">← Quay lại danh sách sản phẩm</a>

  <?php if ($error_message !== ''): ?>
    <div class="empty-state product-detail-empty">
      <h3>Không tải được sản phẩm</h3>
      <p><?= htmlspecialchars($error_message) ?></p>
      <a href="products.php">Xem tất cả sản phẩm →</a>
    </div>
  <?php else: ?>
    <section class="product-detail-layout">
      <div class="product-detail-gallery">
        <div class="product-detail-image-card">
          <img src="assets/images/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>
        <div class="product-detail-assurance">
          <span>✅ Đã kiểm định</span>
          <span>🛡️ Đổi trả 7 ngày</span>
          <span>📦 Đóng gói cẩn thận</span>
        </div>
      </div>

      <div class="product-detail-info">
        <p class="product-detail-kicker">ShopVN Re-commerce</p>
        <h1><?= htmlspecialchars($product['name']) ?></h1>

        <div class="product-detail-rating">
          <span><?= str_repeat('★', $rating) ?><?= str_repeat('☆', 5 - $rating) ?></span>
          <small><?= htmlspecialchars((string) $reviews) ?> đánh giá</small>
        </div>

        <div class="product-detail-price"><?= number_format((float) $product['price']) ?>₫</div>

        <div class="product-detail-badges">
          <span><?= htmlspecialchars($condition) ?></span>
          <span class="<?= $is_out_of_stock ? 'stock-badge-out' : 'stock-badge-in' ?>">
            <?= htmlspecialchars(product_detail_stock_label($stock)) ?>
          </span>
        </div>

        <p class="product-detail-desc"><?= htmlspecialchars($description) ?></p>

        <div class="product-detail-specs">
          <div>
            <strong>Danh mục</strong>
            <span><?= htmlspecialchars($product['category'] ?? 'Sản phẩm đã kiểm định') ?></span>
          </div>
          <div>
            <strong>Tình trạng</strong>
            <span><?= htmlspecialchars($condition) ?></span>
          </div>
          <div>
            <strong>Tồn kho</strong>
            <span><?= htmlspecialchars(product_detail_stock_label($stock)) ?></span>
          </div>
        </div>

        <?php if ($is_out_of_stock): ?>
          <button type="button" class="product-detail-add" disabled>Hết hàng</button>
        <?php else: ?>
          <form class="product-detail-cart-form" method="POST" action="cart.php">
            <input type="hidden" name="action" value="add"/>
            <input type="hidden" name="product_id" value="<?= htmlspecialchars((string) $product['id']) ?>"/>

            <label for="quantity">Số lượng</label>
            <div class="product-detail-buy-row">
              <input
                id="quantity"
                class="product-detail-qty"
                type="number"
                name="quantity"
                value="1"
                min="1"
                <?= $stock !== null ? 'max="' . htmlspecialchars((string) $stock) . '"' : '' ?>
              />
              <button type="submit" class="product-detail-add">+ Thêm vào giỏ</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </section>

    <?php if (!empty($relatedProducts)): ?>
      <section class="section product-detail-related">
        <div class="section-header">
          <div>
            <p>Cùng danh mục</p>
            <h2>Sản phẩm liên quan</h2>
          </div>
        </div>

        <div class="products">
          <?php foreach ($relatedProducts as $related): ?>
            <?php
              $relatedImg = product_detail_image($related, $productImages);
              $relatedCondition = $productConditions[$related['name']] ?? 'Đã kiểm định · Tình trạng tốt';
            ?>
            <div class="product-card">
              <a class="product-img" href="product.php?id=<?= urlencode((string) $related['id']) ?>" aria-label="Xem chi tiết <?= htmlspecialchars($related['name']) ?>">
                <img src="assets/images/<?= htmlspecialchars($relatedImg) ?>" alt="<?= htmlspecialchars($related['name']) ?>" loading="lazy">
              </a>
              <div class="product-info">
                <a href="product.php?id=<?= urlencode((string) $related['id']) ?>" style="text-decoration:none;">
                  <div class="product-name"><?= htmlspecialchars($related['name']) ?></div>
                </a>
                <div class="condition-badge"><?= htmlspecialchars($relatedCondition) ?></div>
                <div class="product-price"><?= number_format((float) $related['price']) ?>₫</div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</main>

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
