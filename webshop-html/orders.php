<?php
session_start();
require __DIR__ . '/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int) ($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $new_shipping = $_POST['shipping_status'] ?? '';

    $allowed_status = ['pending', 'confirmed', 'packing', 'shipping', 'completed', 'cancelled'];
    $allowed_shipping = ['processing', 'packing', 'shipping', 'delivered'];

    if ($order_id > 0 && in_array($new_status, $allowed_status) && in_array($new_shipping, $allowed_shipping)) {
        $pdo->prepare("UPDATE orders SET status = ?, shipping_status = ? WHERE id = ?")
            ->execute([$new_status, $new_shipping, $order_id]);
        $message = "Đã cập nhật đơn hàng #$order_id";
    }
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

function status_label(string $s): string {
    return ['pending'=>'Đang xử lý','confirmed'=>'Đã xác nhận','packing'=>'Đang đóng gói','shipping'=>'Đang giao','completed'=>'Hoàn tất','cancelled'=>'Đã hủy'][$s] ?? $s;
}
function shipping_label(string $s): string {
    return ['processing'=>'Đang xử lý','packing'=>'Đang đóng gói','shipping'=>'Đang vận chuyển','delivered'=>'Đã giao'][$s] ?? $s;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Quản lý đơn hàng - ShopVN Admin</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; min-height: 100vh; }
.sidebar { width: 240px; min-height: 100vh; background: #0f172a; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; }
.sidebar-logo { padding: 1.5rem 1.5rem 1rem; border-bottom: 1px solid #1e293b; }
.sidebar-logo .brand { font-size: 20px; font-weight: 700; color: #fff; }
.sidebar-logo .brand span { color: #1D9E75; }
.sidebar-logo .sub { font-size: 11px; color: #475569; margin-top: 2px; }
.sidebar-nav { padding: 1rem 0; flex: 1; }
.nav-item { display: flex; align-items: center; gap: 10px; padding: 0.7rem 1.5rem; color: #94a3b8; text-decoration: none; font-size: 14px; transition: all 0.15s; }
.nav-item:hover { background: #1e293b; color: #fff; }
.nav-item.active { background: #1e293b; color: #1D9E75; border-left: 3px solid #1D9E75; }
.nav-section { padding: 0.5rem 1.5rem; font-size: 10px; color: #334155; text-transform: uppercase; letter-spacing: 1px; margin-top: 0.5rem; }
.main { margin-left: 240px; flex: 1; padding: 2rem; min-height: 100vh; }
.page-title { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; }
.page-sub { font-size: 13px; color: #64748b; margin-bottom: 1.5rem; }
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.stat { background: #fff; border-radius: 12px; padding: 1.25rem 1.5rem; border: 1px solid #e2e8f0; }
.stat .icon { font-size: 22px; margin-bottom: 0.5rem; }
.stat .label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem; }
.stat .val { font-size: 26px; font-weight: 700; color: #1e293b; }
.stat .val.green { color: #1D9E75; }
.table-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
.card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.card-header h2 { font-size: 14px; font-weight: 600; color: #1e293b; }
.badge-count { font-size: 11px; color: #64748b; background: #f1f5f9; padding: 2px 10px; border-radius: 20px; }
table { width: 100%; border-collapse: collapse; }
thead th { background: #f8fafc; padding: 0.65rem 1.5rem; text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; }
tbody td { padding: 0.8rem 1.5rem; font-size: 13px; color: #374151; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: #f8fafc; }
select.status-select { padding: 4px 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 12px; margin-right: 4px; }
.update-btn { background: #1D9E75; color: #fff; border: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; }
.update-btn:hover { background: #0F6E56; }
.alert-success { background: #f0faf6; border: 1px solid #9FE1CB; color: #085041; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 14px; }
.empty { text-align: center; color: #94a3b8; padding: 2rem; font-size: 13px; }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="brand">Shop<span>VN</span></div>
    <div class="sub">Admin Panel</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">Menu</div>
    <a href="admin/" class="nav-item">📊 Dashboard</a>
    <a href="orders.php" class="nav-item active">📋 Quản lý đơn hàng</a>
    <a href="index.php" class="nav-item">🏠 Về trang chủ</a>
    <div class="nav-section">Tài khoản</div>
    <a href="logout.php" class="nav-item">🚪 Đăng xuất</a>
  </nav>
</aside>

<main class="main">
  <div class="page-title">Quản lý đơn hàng</div>
  <div class="page-sub">Cập nhật trạng thái xử lý và vận chuyển cho từng đơn hàng</div>

  <div class="stats">
    <div class="stat"><div class="icon">👥</div><div class="label">Người dùng</div><div class="val"><?= $total_users ?></div></div>
    <div class="stat"><div class="icon">🛒</div><div class="label">Đơn hàng</div><div class="val"><?= $total_orders ?></div></div>
    <div class="stat"><div class="icon">💰</div><div class="label">Doanh thu</div><div class="val green"><?= number_format($total_revenue) ?>₫</div></div>
    <div class="stat"><div class="icon">📦</div><div class="label">Sản phẩm</div><div class="val"><?= $total_products ?></div></div>
  </div>

  <?php if ($message): ?>
    <div class="alert-success">✅ <?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="table-card">
    <div class="card-header">
      <h2>Danh sách đơn hàng</h2>
      <span class="badge-count"><?= count($orders) ?> đơn</span>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th><th>Session</th><th>Tổng tiền</th>
          <th>Trạng thái đơn</th><th>Vận chuyển</th>
          <th>Thời gian</th><th>Cập nhật</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="7" class="empty">Chưa có đơn hàng nào</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $order): ?>
        <tr>
          <form method="POST">
        <tr>
          <td>#<?= $order['id'] ?></td>
          <td><?= substr($order['session_id'] ?? '', 0, 10) ?>...</td>
          <td><?= number_format($order['total']) ?>₫</td>

          <td>
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>"/>

            <select name="status" class="status-select">
              <?php foreach (['pending','confirmed','packing','shipping','completed','cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= ($order['status'] ?? 'pending') === $s ? 'selected' : '' ?>>
                  <?= status_label($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>

          <td>
            <select name="shipping_status" class="status-select">
              <?php foreach (['processing','packing','shipping','delivered'] as $s): ?>
                <option value="<?= $s ?>" <?= ($order['shipping_status'] ?? 'processing') === $s ? 'selected' : '' ?>>
                  <?= shipping_label($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>

          <td><?= $order['created_at'] ?? '—' ?></td>

          <td>
            <button type="submit" class="update-btn">Lưu</button>
          </td>
        </tr>
        </form>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

</body>
</html>