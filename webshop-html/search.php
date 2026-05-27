<?php
session_start();
require __DIR__ . '/db.php';

$session_id = session_id();
$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$min_price = trim($_GET['min_price'] ?? '');
$max_price = trim($_GET['max_price'] ?? '');
$products = [];
$popularProducts = [];
$categories = [];
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

function table_has_column_search(PDO $pdo, string $table, string $column): bool
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

function product_image_search(array $product, array $productImages): string
{
    if (!empty($product['image'])) {
        return basename($product['image']);
    }
    return $productImages[$product['name']] ?? 'battery.jpg';
}

function product_stock_label_search(?int $stock): string
{
    if ($stock === null) return 'Còn hàng';
    if ($stock <= 0) return 'Hết hàng';
    if ($stock <= 3) return 'Sắp hết · còn ' . $stock . ' sản phẩm';
    return 'Còn ' . $stock . ' sản phẩm';
}

$hasFilters = ($q !== '' || $category !== '' || $min_price !== '' || $max_price !== '');
$hasStock = false;
$hasImage = false;

if ($pdo) {
    try {
        $hasStock = table_has_column_search($pdo, 'products', 'stock');
        $hasImage = table_has_column_search($pdo, 'products', 'image');

        $cart_stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = ?');
        $cart_stmt->execute([$session_id]);
        $cart_count = (int) $cart_stmt->fetchColumn();

        $categories = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC")
            ->fetchAll(PDO::FETCH_COLUMN);

        if ($hasFilters) {
            $where = [];
            $params = [];

            if ($q !== '') {
                $where[] = '(name LIKE ? OR category LIKE ?)';
                $params[] = '%' . $q . '%';
                $params[] = '%' . $q . '%';
            }

            if ($category !== '') {
                $where[] = 'category = ?';
                $params[] = $category;
            }

            if ($min_price !== '' && is_numeric($min_price)) {
                $where[] = 'price >= ?';
                $params[] = (float) $min_price;
            }

            if ($max_price !== '' && is_numeric($max_price)) {
                $where[] = 'price <= ?';
                $params[] = (float) $max_price;
            }

            $sql = 'SELECT * FROM products';
            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY id DESC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $popularProducts = $pdo->query('SELECT * FROM products ORDER BY id DESC LIMIT 4')->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('Search page error: ' . $e->getMessage());
        $error_message = 'Không thể tải dữ liệu tìm kiếm. Vui lòng thử lại sau.';
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
      <a href="profile.php">Hồ sơ</a>
      <span class="user-badge">👤 <?= htmlspecialchars($_SESSION['user']) ?></span>
      <?php if ($_SESSION['user'] === 'admin'): ?><a href="admin/">Admin</a><?php endif; ?>
      <a href="logout.php">Đăng xuất</a>
    <?php else: ?>
      <a href="login.php">Đăng nhập</a>
    <?php endif; ?>
    <a href="cart.php"><button class="cart-btn">🛒 Giỏ hàng (<?= $cart_count ?>)</button></a>
  </div>
</nav>

<section class="section products-page">
  <div class="section-header">
    <div>
      <p>Tìm kiếm ShopVN</p>
      <h2>Lọc sản phẩm theo tên, danh mục và khoảng giá</h2>
    </div>
  </div>

  <div class="search-panel search-filter-panel">
    <form method="GET" action="search.php" class="search-filter-form">
      <div class="search-bar">
        <input type="text" name="q" placeholder="Nhập tên sản phẩm hoặc từ khóa..." value="<?= htmlspecialchars($q) ?>" />
        <button type="submit">Lọc sản phẩm</button>
      </div>

      <div class="filter-grid">
        <div class="filter-field">
          <label for="category">Danh mục</label>
          <select id="category" name="category">
            <option value="">Tất cả danh mục</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-field">
          <label for="min_price">Giá từ</label>
          <input id="min_price" type="number" name="min_price" min="0" step="1000" placeholder="0" value="<?= htmlspecialchars($min_price) ?>" />
        </div>

        <div class="filter-field">
          <label for="max_price">Giá đến</label>
          <input id="max_price" type="number" name="max_price" min="0" step="1000" placeholder="VD: 1000000" value="<?= htmlspecialchars($max_price) ?>" />
        </div>

        <div class="filter-actions">
          <button type="submit" class="filter-submit">Áp dụng</button>
          <a href="search.php" class="filter-reset">Xóa lọc</a>
        </div>
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

  <?php if ($error_message !== ''): ?>
    <div class="empty-state"><h3>Chưa tải được dữ liệu</h3><p><?= htmlspecialchars($error_message) ?></p></div>
  <?php elseif ($hasFilters): ?>
    <div class="section-header search-result-header">
      <div>
        <p>Kết quả tìm kiếm</p>
        <h2><?= count($products) ?> sản phẩm phù hợp</h2>
      </div>
    </div>

    <?php if (empty($products)): ?>
      <div class="empty-state">
        <h3>Chưa tìm thấy sản phẩm phù hợp</h3>
        <p>Thử nới khoảng giá, chọn “Tất cả danh mục”, hoặc dùng từ khóa ngắn hơn.</p>
        <a href="products.php">Xem tất cả sản phẩm →</a>
      </div>
    <?php else: ?>
      <div class="products">
        <?php foreach ($products as $p): ?>
          <?php
            $img = product_image_search($p, $productImages);
            $condition = $productConditions[$p['name']] ?? 'Đã kiểm định · Tình trạng tốt';
            $stock = array_key_exists('stock', $p) ? (int) $p['stock'] : null;
            $is_out_of_stock = ($stock !== null && $stock <= 0);
            $rating = isset($p['rating']) ? max(0, min(5, (int) round($p['rating']))) : 0;
            $reviews = isset($p['reviews']) ? (int) $p['reviews'] : 0;
          ?>
          <div class="product-card">
            <a class="product-img" href="product.php?id=<?= urlencode((string) $p['id']) ?>" aria-label="Xem chi tiết <?= htmlspecialchars($p['name']) ?>">
              <img src="assets/images/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
            </a>
            <div class="product-info">
              <a href="product.php?id=<?= urlencode((string) $p['id']) ?>" style="text-decoration:none;"><div class="product-name"><?= htmlspecialchars($p['name']) ?></div></a>
              <div class="condition-badge"><?= htmlspecialchars($condition) ?></div>
              <div class="product-meta"><?= htmlspecialchars($p['category'] ?? 'Sản phẩm') ?> · <?= htmlspecialchars(product_stock_label_search($stock)) ?></div>
              <div class="product-stars"><?= str_repeat('★', $rating) ?><?= str_repeat('☆', 5 - $rating) ?> <span>(<?= htmlspecialchars((string) $reviews) ?> đánh giá)</span></div>
              <div class="product-price"><?= number_format((float) $p['price']) ?>₫</div>
              <?php if ($is_out_of_stock): ?>
                <button type="button" class="add-btn" disabled style="opacity:0.55;cursor:not-allowed;">Hết hàng</button>
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
    <?php endif; ?>
  <?php else: ?>
    <div class="section-header search-result-header">
      <div><p>Gợi ý tìm kiếm</p><h2>Sản phẩm phổ biến</h2></div>
      <a href="products.php">Xem tất cả →</a>
    </div>

    <div class="products">
      <?php foreach ($popularProducts as $p): ?>
        <?php
          $img = product_image_search($p, $productImages);
          $condition = $productConditions[$p['name']] ?? 'Đã kiểm định · Tình trạng tốt';
          $rating = isset($p['rating']) ? max(0, min(5, (int) round($p['rating']))) : 0;
          $reviews = isset($p['reviews']) ? (int) $p['reviews'] : 0;
        ?>
        <div class="product-card">
          <a class="product-img" href="product.php?id=<?= urlencode((string) $p['id']) ?>">
            <img src="assets/images/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
          </a>
          <div class="product-info">
            <a href="product.php?id=<?= urlencode((string) $p['id']) ?>" style="text-decoration:none;"><div class="product-name"><?= htmlspecialchars($p['name']) ?></div></a>
            <div class="condition-badge"><?= htmlspecialchars($condition) ?></div>
            <div class="product-meta"><?= htmlspecialchars($p['category'] ?? 'Sản phẩm đã kiểm định') ?></div>
            <div class="product-stars"><?= str_repeat('★', $rating) ?><?= str_repeat('☆', 5 - $rating) ?> <span>(<?= htmlspecialchars((string) $reviews) ?> đánh giá)</span></div>
            <div class="product-price"><?= number_format((float) $p['price']) ?>₫</div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<div class="banner">
  <div>
    <p>Tìm đúng sản phẩm, đúng ngân sách</p>
    <span>Dùng bộ lọc giá và danh mục để rút ngắn thời gian chọn sản phẩm đã kiểm định.</span>
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
