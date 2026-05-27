<?php
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$session_id = session_id();
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$username = $_SESSION['user'];
$orders = [];
$orderItems = [];
$cart_count = 0;
$error_message = '';

function profile_table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function profile_column_exists(PDO $pdo, string $table, string $column): bool
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return false;
    }
}

function order_status_text(string $status): string
{
    $map = [
        'pending' => 'Đang xử lý',
        'confirmed' => 'Đã xác nhận',
        'packing' => 'Đang đóng gói',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy'
    ];
    return $map[$status] ?? $status;
}

if ($pdo) {
    try {
        $cartStmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = ?');
        $cartStmt->execute([$session_id]);
        $cart_count = (int) $cartStmt->fetchColumn();

        if (profile_table_exists($pdo, 'orders')) {
            $hasUserId = profile_column_exists($pdo, 'orders', 'user_id');
            $hasSessionId = profile_column_exists($pdo, 'orders', 'session_id');
            $hasStatus = profile_column_exists($pdo, 'orders', 'status');
            $hasShipping = profile_column_exists($pdo, 'orders', 'shipping_status');
            $hasCreatedAt = profile_column_exists($pdo, 'orders', 'created_at');

            $select = 'id, total';
            $select .= $hasStatus ? ', status' : ", 'pending' AS status";
            $select .= $hasShipping ? ', shipping_status' : ", 'processing' AS shipping_status";
            $select .= $hasCreatedAt ? ', created_at' : ', id AS created_at';

            $where = [];
            $params = [];
            if ($hasUserId && $user_id > 0) {
                $where[] = 'user_id = ?';
                $params[] = $user_id;
            }
            if ($hasSessionId) {
                $where[] = 'session_id = ?';
                $params[] = $session_id;
            }

            if (!empty($where)) {
                $sql = 'SELECT ' . $select . ' FROM orders WHERE ' . implode(' OR ', $where) . ' ORDER BY id DESC';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if (!empty($orders) && profile_table_exists($pdo, 'order_items')) {
                $orderIds = array_map(fn($order) => (int) $order['id'], $orders);
                $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
                $itemSql = "SELECT oi.order_id, oi.product_id, oi.quantity, oi.price, p.name
                            FROM order_items oi
                            LEFT JOIN products p ON oi.product_id = p.id
                            WHERE oi.order_id IN ($placeholders)
                            ORDER BY oi.id ASC";
                $itemStmt = $pdo->prepare($itemSql);
                $itemStmt->execute($orderIds);
                foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
                    $orderItems[(int) $item['order_id']][] = $item;
                }
            }
        }
    } catch (PDOException $e) {
        error_log('Profile page error: ' . $e->getMessage());
        $error_message = 'Không thể tải lịch sử đơn hàng. Vui lòng thử lại sau.';
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
<title>Hồ sơ & lịch sử đơn hàng - ShopVN</title>
<link rel="stylesheet" href="style.css"/>
</head>
<body>
<nav>
  <div class="logo">Shop<span>VN</span></div>
  <div class="nav-links">
    <a href="index.php">Trang chủ</a>
    <a href="products.php">Sản phẩm</a>
    <a href="search.php">Tìm kiếm</a>
    <a href="profile.php">Hồ sơ</a>
    <span class="user-badge">👤 <?= htmlspecialchars($username) ?></span>
    <?php if ($username === 'admin'): ?><a href="admin/">Admin</a><?php endif; ?>
    <a href="logout.php">Đăng xuất</a>
    <a href="cart.php"><button class="cart-btn">🛒 Giỏ hàng (<?= $cart_count ?>)</button></a>
  </div>
</nav>

<main class="profile-page">
  <section class="profile-hero-card">
    <div>
      <p class="profile-label">Tài khoản ShopVN</p>
      <h1>Xin chào, <?= htmlspecialchars($username) ?></h1>
      <span>Theo dõi các đơn đã đặt, trạng thái xử lý và tổng tiền đơn hàng.</span>
    </div>
    <a href="products.php" class="primary-link-btn profile-shop-btn">Mua thêm sản phẩm</a>
  </section>

  <?php if ($error_message !== ''): ?>
    <section class="empty-state"><h3>Chưa tải được hồ sơ</h3><p><?= htmlspecialchars($error_message) ?></p></section>
  <?php else: ?>
    <section class="profile-stats-grid">
      <div class="profile-stat-card"><strong><?= count($orders) ?></strong><span>Tổng đơn hàng</span></div>
      <div class="profile-stat-card"><strong><?= number_format(array_sum(array_map(fn($o) => (float) $o['total'], $orders))) ?>₫</strong><span>Tổng chi tiêu</span></div>
      <div class="profile-stat-card"><strong><?= $cart_count ?></strong><span>Sản phẩm trong giỏ</span></div>
    </section>

    <section class="order-history-card">
      <div class="section-header profile-section-header">
        <div><p>Lịch sử mua hàng</p><h2>Đơn hàng của bạn</h2></div>
      </div>

      <?php if (empty($orders)): ?>
        <div class="empty-state profile-empty-order">
          <h3>Bạn chưa có đơn hàng nào</h3>
          <p>Khi bạn đặt hàng thành công, đơn sẽ xuất hiện ở đây.</p>
          <a href="products.php">Xem sản phẩm →</a>
        </div>
      <?php else: ?>
        <div class="order-list">
          <?php foreach ($orders as $order): ?>
            <?php
              $orderId = (int) $order['id'];
              $items = $orderItems[$orderId] ?? [];
              $status = (string) ($order['status'] ?? 'pending');
              $shipping = (string) ($order['shipping_status'] ?? 'processing');
              $createdAt = (string) ($order['created_at'] ?? '');
            ?>
            <article class="order-card">
              <div class="order-card-top">
                <div>
                  <p>Đơn hàng #<?= $orderId ?></p>
                  <h3><?= number_format((float) $order['total']) ?>₫</h3>
                  <span><?= htmlspecialchars($createdAt) ?></span>
                </div>
                <div class="order-badges">
                  <span class="order-status-badge"><?= htmlspecialchars(order_status_text($status)) ?></span>
                  <span class="shipping-badge">Giao hàng: <?= htmlspecialchars(order_status_text($shipping)) ?></span>
                </div>
              </div>

              <?php if (!empty($items)): ?>
                <div class="order-items">
                  <?php foreach ($items as $item): ?>
                    <div class="order-item-row">
                      <span><?= htmlspecialchars($item['name'] ?? ('Sản phẩm #' . $item['product_id'])) ?></span>
                      <strong>x<?= (int) $item['quantity'] ?> · <?= number_format((float) $item['price']) ?>₫</strong>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="order-no-items">Đơn hàng cũ chưa có dữ liệu chi tiết sản phẩm.</p>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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
