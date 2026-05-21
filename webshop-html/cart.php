<?php
session_start();
require __DIR__ . '/db.php';

$session_id = session_id();

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
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.session_id = ?
        ");
        $stmt->execute([$session_id]);
        $checkout_items = $stmt->fetchAll();

        $checkout_total = array_sum(array_map(
            fn($i) => $i['price'] * $i['quantity'],
            $checkout_items
        ));

        $pdo->prepare("INSERT INTO orders (session_id, total) VALUES (?, ?)")
            ->execute([$session_id, $checkout_total]);

        $pdo->prepare("DELETE FROM cart WHERE session_id = ?")
            ->execute([$session_id]);

        header('Location: checkout.php?success=1');
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT c.id, c.quantity, p.id AS product_id, p.name, p.price, p.emoji
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.session_id = ?
");
$stmt->execute([$session_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = array_sum(array_map(
    fn($i) => $i['price'] * $i['quantity'],
    $items
));

$cart_count = array_sum(array_map(
    fn($i) => $i['quantity'],
    $items
));
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Giỏ hàng - ShopVN Re-commerce</title>
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

<main class="cart-page">

  <div class="section-header cart-header">
    <div>
      <p>Giỏ hàng ShopVN</p>
      <h2>Kiểm tra đơn hàng</h2>
    </div>

    <a href="products.php">Tiếp tục mua sắm →</a>
  </div>

  <?php if (empty($items)): ?>

    <section class="empty-state cart-empty">
      <h3>Giỏ hàng của bạn đang trống</h3>
      <p>Khám phá các sản phẩm đã kiểm định và thêm món phù hợp vào giỏ hàng.</p>
      <a href="products.php">Xem sản phẩm →</a>
    </section>

  <?php else: ?>

    <section class="cart-layout">

      <div class="cart-items">

        <?php foreach ($items as $item): ?>

          <?php
            $img = $productImages[$item['name']] ?? 'battery.jpg';
            $line_total = $item['price'] * $item['quantity'];
          ?>

          <div class="cart-item">

            <div class="cart-item-img">
              <img
                src="assets/images/<?= $img ?>"
                alt="<?= htmlspecialchars($item['name']) ?>"
                loading="lazy"
              >
            </div>

            <div class="cart-item-info">
              <h3><?= htmlspecialchars($item['name']) ?></h3>
              <p>Đã kiểm định · Mô tả tình trạng rõ ràng</p>
              <span><?= number_format($item['price']) ?>₫ / sản phẩm</span>
            </div>

            <div class="cart-item-qty">
              <span>Số lượng</span>
              <strong><?= htmlspecialchars($item['quantity']) ?></strong>
            </div>

            <div class="cart-item-total">
              <?= number_format($line_total) ?>₫
            </div>

            <form method="POST" class="cart-remove-form">
              <input type="hidden" name="action" value="remove"/>
              <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>"/>
              <button type="submit">Xóa</button>
            </form>

          </div>

        <?php endforeach; ?>

      </div>

      <aside class="cart-summary">

        <h3>Tóm tắt đơn hàng</h3>

        <div class="summary-row">
          <span>Tạm tính</span>
          <strong><?= number_format($total) ?>₫</strong>
        </div>

        <div class="summary-row">
          <span>Phí vận chuyển</span>
          <strong>Miễn phí</strong>
        </div>

        <div class="summary-row">
          <span>Kiểm định sản phẩm</span>
          <strong>Đã bao gồm</strong>
        </div>

        <div class="summary-total">
          <span>Tổng cộng</span>
          <strong><?= number_format($total) ?>₫</strong>
        </div>

        <form method="POST">
          <input type="hidden" name="action" value="checkout"/>
          <button type="submit" class="checkout-btn">
            Xác nhận đặt hàng
          </button>
        </form>

        <p class="summary-note">
          Đơn hàng sẽ được kiểm tra lần cuối trước khi đóng gói và giao đến bạn.
        </p>

      </aside>

    </section>

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
