<?php
session_start();
require __DIR__ . '/db.php';

$session_id = session_id();
$notice = $_GET['notice'] ?? '';
$error = $_GET['error'] ?? '';

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

function redirect_cart(string $type = '', string $message = ''): void
{
    $url = 'cart.php';
    if ($type !== '' && $message !== '') {
        $url .= '?' . http_build_query([$type => $message]);
    }
    header('Location: ' . $url);
    exit;
}

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

function table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Table check error: ' . $e->getMessage());
        return false;
    }
}

function safe_product_image(array $product, array $productImages): string
{
    if (!empty($product['image'])) {
        return basename($product['image']);
    }

    return $productImages[$product['name']] ?? 'battery.jpg';
}

function stock_text(?int $stock): string
{
    if ($stock === null) {
        return 'Còn hàng';
    }

    if ($stock <= 0) {
        return 'Hết hàng';
    }

    return 'Còn ' . $stock . ' sản phẩm';
}

function get_product_for_cart(PDO $pdo, int $product_id, bool $hasStock): ?array
{
    $sql = $hasStock
        ? 'SELECT id, name, stock FROM products WHERE id = ?'
        : 'SELECT id, name FROM products WHERE id = ?';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    return $product ?: null;
}

$hasStock = false;
$hasImage = false;

if (!$pdo) {
    $error = 'Không thể kết nối cơ sở dữ liệu.';
} else {
    $hasStock = table_has_column($pdo, 'products', 'stock');
    $hasImage = table_has_column($pdo, 'products', 'image');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $product_id = (int) ($_POST['product_id'] ?? 0);

        if ($action === 'add') {
            if ($product_id <= 0) {
                redirect_cart('error', 'Sản phẩm không hợp lệ.');
            }

            try {
                $pdo->beginTransaction();

                $lockSql = $hasStock
                    ? 'SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE'
                    : 'SELECT id, name FROM products WHERE id = ?';
                $stmt = $pdo->prepare($lockSql);
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    $pdo->rollBack();
                    redirect_cart('error', 'Không tìm thấy sản phẩm.');
                }

                $stmt = $pdo->prepare('SELECT quantity FROM cart WHERE session_id = ? AND product_id = ?');
                $stmt->execute([$session_id, $product_id]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                $currentQty = $existing ? (int) $existing['quantity'] : 0;
                $newQty = $currentQty + 1;

                if ($hasStock) {
                    $stock = (int) $product['stock'];
                    if ($stock <= 0) {
                        $pdo->rollBack();
                        redirect_cart('error', 'Sản phẩm này đã hết hàng.');
                    }
                    if ($newQty > $stock) {
                        $pdo->rollBack();
                        redirect_cart('error', 'Không thể thêm quá số lượng tồn kho. Hiện chỉ còn ' . $stock . ' sản phẩm.');
                    }
                }

                if ($existing) {
                    $stmt = $pdo->prepare('UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ?');
                    $stmt->execute([$newQty, $session_id, $product_id]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, 1)');
                    $stmt->execute([$session_id, $product_id]);
                }

                $pdo->commit();
                redirect_cart('notice', 'Đã thêm sản phẩm vào giỏ hàng.');
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Add cart error: ' . $e->getMessage());
                redirect_cart('error', 'Không thể thêm sản phẩm vào giỏ hàng.');
            }
        }

        if ($action === 'update') {
            if ($product_id <= 0) {
                redirect_cart('error', 'Sản phẩm không hợp lệ.');
            }

            $requestedQty = (int) ($_POST['quantity'] ?? 1);

            try {
                $pdo->beginTransaction();

                $lockSql = $hasStock
                    ? 'SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE'
                    : 'SELECT id, name FROM products WHERE id = ?';
                $stmt = $pdo->prepare($lockSql);
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    $pdo->rollBack();
                    redirect_cart('error', 'Không tìm thấy sản phẩm.');
                }

                if ($requestedQty <= 0) {
                    $stmt = $pdo->prepare('DELETE FROM cart WHERE session_id = ? AND product_id = ?');
                    $stmt->execute([$session_id, $product_id]);
                    $pdo->commit();
                    redirect_cart('notice', 'Đã xóa sản phẩm khỏi giỏ hàng.');
                }

                if ($hasStock) {
                    $stock = (int) $product['stock'];
                    if ($stock <= 0) {
                        $stmt = $pdo->prepare('DELETE FROM cart WHERE session_id = ? AND product_id = ?');
                        $stmt->execute([$session_id, $product_id]);
                        $pdo->commit();
                        redirect_cart('error', 'Sản phẩm này đã hết hàng nên đã được xóa khỏi giỏ.');
                    }
                    if ($requestedQty > $stock) {
                        $requestedQty = $stock;
                        $message = 'Số lượng đã được chỉnh về mức tồn kho tối đa: ' . $stock . ' sản phẩm.';
                    } else {
                        $message = 'Đã cập nhật số lượng.';
                    }
                } else {
                    $message = 'Đã cập nhật số lượng.';
                }

                $stmt = $pdo->prepare('UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ?');
                $stmt->execute([$requestedQty, $session_id, $product_id]);

                $pdo->commit();
                redirect_cart('notice', $message);
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Update cart error: ' . $e->getMessage());
                redirect_cart('error', 'Không thể cập nhật số lượng.');
            }
        }

        if ($action === 'remove') {
            if ($product_id <= 0) {
                redirect_cart('error', 'Sản phẩm không hợp lệ.');
            }

            try {
                $pdo->prepare('DELETE FROM cart WHERE session_id = ? AND product_id = ?')
                    ->execute([$session_id, $product_id]);

                redirect_cart('notice', 'Đã xóa sản phẩm khỏi giỏ hàng.');
            } catch (PDOException $e) {
                error_log('Remove cart error: ' . $e->getMessage());
                redirect_cart('error', 'Không thể xóa sản phẩm.');
            }
        }

        if ($action === 'increase' || $action === 'decrease' || $action === 'set_quantity') {
            if ($product_id <= 0) {
                redirect_cart('error', 'Sản phẩm không hợp lệ.');
            }

            try {
                $stmt = $pdo->prepare('SELECT quantity FROM cart WHERE session_id = ? AND product_id = ?');
                $stmt->execute([$session_id, $product_id]);
                $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$cartItem) {
                    redirect_cart('error', 'Sản phẩm không có trong giỏ hàng.');
                }

                $currentQty = (int) $cartItem['quantity'];
                $newQty = $currentQty;

                if ($action === 'increase') {
                    $newQty = $currentQty + 1;
                } elseif ($action === 'decrease') {
                    $newQty = $currentQty - 1;
                } else {
                    $newQty = (int) ($_POST['quantity'] ?? $currentQty);
                }

                if ($newQty <= 0) {
                    $pdo->prepare('DELETE FROM cart WHERE session_id = ? AND product_id = ?')
                        ->execute([$session_id, $product_id]);

                    redirect_cart('notice', 'Đã cập nhật giỏ hàng.');
                }

                if ($hasStock) {
                    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
                    $stmt->execute([$product_id]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$product) {
                        redirect_cart('error', 'Không tìm thấy sản phẩm.');
                    }

                    $stock = (int) $product['stock'];

                    if ($stock <= 0) {
                        $pdo->prepare('DELETE FROM cart WHERE session_id = ? AND product_id = ?')
                            ->execute([$session_id, $product_id]);

                        redirect_cart('error', 'Sản phẩm này đã hết hàng và đã được xóa khỏi giỏ.');
                    }

                    if ($newQty > $stock) {
                        $newQty = $stock;
                        $message = 'Số lượng đã được điều chỉnh theo tồn kho hiện có.';
                    } else {
                        $message = 'Đã cập nhật số lượng.';
                    }
                } else {
                    $message = 'Đã cập nhật số lượng.';
                }

                $pdo->prepare('UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ?')
                    ->execute([$newQty, $session_id, $product_id]);

                redirect_cart('notice', $message);
            } catch (PDOException $e) {
                error_log('Update cart quantity error: ' . $e->getMessage());
                redirect_cart('error', 'Không thể cập nhật số lượng.');
            }
        }

        if ($action === 'checkout') {
            try {
                $pdo->beginTransaction();

                $selectItems = $hasStock
                    ? "SELECT c.product_id, c.quantity, p.name, p.price, p.stock
                       FROM cart c
                       JOIN products p ON c.product_id = p.id
                       WHERE c.session_id = ?
                       FOR UPDATE"
                    : "SELECT c.product_id, c.quantity, p.name, p.price
                       FROM cart c
                       JOIN products p ON c.product_id = p.id
                       WHERE c.session_id = ?";

                $stmt = $pdo->prepare($selectItems);
                $stmt->execute([$session_id]);
                $checkout_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($checkout_items)) {
                    $pdo->rollBack();
                    redirect_cart('error', 'Giỏ hàng đang trống.');
                }

                foreach ($checkout_items as $item) {
                    $qty = (int) $item['quantity'];

                    if ($qty <= 0) {
                        $pdo->rollBack();
                        redirect_cart('error', 'Số lượng sản phẩm trong giỏ không hợp lệ.');
                    }

                    if ($hasStock) {
                        $stock = (int) $item['stock'];
                        if ($stock <= 0) {
                            $pdo->rollBack();
                            redirect_cart('error', 'Sản phẩm "' . $item['name'] . '" đã hết hàng.');
                        }
                        if ($qty > $stock) {
                            $pdo->rollBack();
                            redirect_cart('error', 'Sản phẩm "' . $item['name'] . '" chỉ còn ' . $stock . ' sản phẩm.');
                        }
                    }
                }

                $checkout_total = 0;
                foreach ($checkout_items as $item) {
                    $checkout_total += (float) $item['price'] * (int) $item['quantity'];
                }

                if ($hasStock) {
                    $updateStock = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

                    foreach ($checkout_items as $item) {
                        $qty = (int) $item['quantity'];
                        $updateStock->execute([$qty, (int) $item['product_id'], $qty]);

                        if ($updateStock->rowCount() !== 1) {
                            $pdo->rollBack();
                            redirect_cart('error', 'Không thể cập nhật tồn kho cho sản phẩm "' . $item['name'] . '".');
                        }
                    }
                }

                if (!table_exists($pdo, 'orders')) {
                    $pdo->rollBack();
                    redirect_cart('error', 'Chưa có bảng orders. Hãy chạy migration order_history_migration.sql trước.');
                }

                $orderColumns = ['session_id', 'total'];
                $orderValues = [$session_id, $checkout_total];
                $orderPlaceholders = ['?', '?'];

                if (table_has_column($pdo, 'orders', 'user_id')) {
                    $orderColumns[] = 'user_id';
                    $orderValues[] = $_SESSION['user_id'] ?? null;
                    $orderPlaceholders[] = '?';
                }

                if (table_has_column($pdo, 'orders', 'status')) {
                    $orderColumns[] = 'status';
                    $orderValues[] = 'pending';
                    $orderPlaceholders[] = '?';
                }

                if (table_has_column($pdo, 'orders', 'shipping_status')) {
                    $orderColumns[] = 'shipping_status';
                    $orderValues[] = 'processing';
                    $orderPlaceholders[] = '?';
                }

                $insertOrderSql = 'INSERT INTO orders (' . implode(', ', $orderColumns) . ') VALUES (' . implode(', ', $orderPlaceholders) . ')';
                $pdo->prepare($insertOrderSql)->execute($orderValues);
                $order_id = (int) $pdo->lastInsertId();

                if (table_exists($pdo, 'order_items')) {
                    $insertItem = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
                    foreach ($checkout_items as $item) {
                        $insertItem->execute([
                            $order_id,
                            (int) $item['product_id'],
                            (int) $item['quantity'],
                            (float) $item['price']
                        ]);
                    }
                }

                $pdo->prepare('DELETE FROM cart WHERE session_id = ?')
                    ->execute([$session_id]);

                $pdo->commit();

                header('Location: checkout.php?success=1');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log('Checkout error: ' . $e->getMessage());
                redirect_cart('error', 'Không thể đặt hàng. Vui lòng thử lại.');
            }
        }
    }
}

$items = [];
$total = 0;
$cart_count = 0;

if ($pdo) {
    try {
        $imageSelect = $hasImage ? ', p.image' : '';
        $stockSelect = $hasStock ? ', p.stock' : '';

        $stmt = $pdo->prepare("
            SELECT c.id, c.quantity, p.id AS product_id, p.name, p.price, p.emoji
            $imageSelect
            $stockSelect
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.session_id = ?
            ORDER BY c.id DESC
        ");
        $stmt->execute([$session_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $total += (float) $item['price'] * (int) $item['quantity'];
            $cart_count += (int) $item['quantity'];
        }
    } catch (PDOException $e) {
        error_log('Load cart error: ' . $e->getMessage());
        $error = 'Không thể tải giỏ hàng.';
    }
}
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

<main class="cart-page">

  <div class="section-header cart-header">
    <div>
      <p>Giỏ hàng ShopVN</p>
      <h2>Kiểm tra đơn hàng</h2>
    </div>

    <a href="products.php">Tiếp tục mua sắm →</a>
  </div>

  <?php if ($notice !== ''): ?>
    <div class="cart-alert cart-alert-success">
      <?= htmlspecialchars($notice) ?>
    </div>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <div class="cart-alert cart-alert-error">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

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
            $img = safe_product_image($item, $productImages);
            $quantity = (int) $item['quantity'];
            $line_total = (float) $item['price'] * $quantity;
            $stock = array_key_exists('stock', $item) ? (int) $item['stock'] : null;
            $maxQty = $stock === null ? 99 : max(0, $stock);
            $canIncrease = $stock === null || $quantity < $stock;
          ?>

          <div class="cart-item">

            <div class="cart-item-img">
              <img
                src="assets/images/<?= htmlspecialchars($img) ?>"
                alt="<?= htmlspecialchars($item['name']) ?>"
                loading="lazy"
              >
            </div>

            <div class="cart-item-info">
              <h3><?= htmlspecialchars($item['name']) ?></h3>
              <p>Đã kiểm định · <?= htmlspecialchars(stock_text($stock)) ?></p>
              <span><?= number_format((float) $item['price']) ?>₫ / sản phẩm</span>
            </div>

            <div class="cart-item-qty">
              <span class="qty-label">Số lượng</span>

              <form method="POST" class="qty-stepper" aria-label="Cập nhật số lượng">
                <input type="hidden" name="action" value="update"/>
                <input type="hidden" name="product_id" value="<?= htmlspecialchars((string) $item['product_id']) ?>"/>

                <button
                  type="submit"
                  class="qty-stepper-btn"
                  name="quantity"
                  value="<?= max(0, $quantity - 1) ?>"
                  aria-label="Giảm số lượng"
                >−</button>

                <input
                  class="qty-stepper-input"
                  type="number"
                  name="quantity"
                  value="<?= htmlspecialchars((string) $quantity) ?>"
                  min="0"
                  max="<?= htmlspecialchars((string) $maxQty) ?>"
                  onchange="this.form.submit()"
                  aria-label="Số lượng sản phẩm"
                />

                <button
                  type="submit"
                  class="qty-stepper-btn"
                  name="quantity"
                  value="<?= $quantity + 1 ?>"
                  aria-label="Tăng số lượng"
                  <?= $canIncrease ? '' : 'disabled' ?>
                >+</button>
              </form>

              <?php if ($stock !== null): ?>
                <small class="qty-stock-note">Còn <?= $stock ?> sản phẩm</small>
              <?php endif; ?>
            </div>

            <div class="cart-item-total">
              <?= number_format($line_total) ?>₫
            </div>

            <form method="POST" class="cart-remove-form">
              <input type="hidden" name="action" value="remove"/>
              <input type="hidden" name="product_id" value="<?= htmlspecialchars((string) $item['product_id']) ?>"/>
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
          Khi đặt hàng thành công, hệ thống sẽ tự động trừ tồn kho. Nếu số lượng vượt tồn kho, đơn hàng sẽ bị chặn.
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
