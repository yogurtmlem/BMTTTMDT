<?php
session_start();
require __DIR__ . '/../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Panel - ShopVN</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; min-height: 100vh; }

/* Sidebar */
.sidebar {
  width: 240px;
  min-height: 100vh;
  background: #0f172a;
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  z-index: 100;
}
.sidebar-logo {
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid #1e293b;
}
.sidebar-logo .brand { font-size: 20px; font-weight: 700; color: #fff; }
.sidebar-logo .brand span { color: #1D9E75; }
.sidebar-logo .sub { font-size: 11px; color: #475569; margin-top: 2px; }
.sidebar-nav { padding: 1rem 0; flex: 1; }
.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 0.7rem 1.5rem;
  color: #94a3b8; text-decoration: none;
  font-size: 14px; transition: all 0.15s;
}
.nav-item:hover { background: #1e293b; color: #fff; }
.nav-item.active { background: #1e293b; color: #1D9E75; border-left: 3px solid #1D9E75; }
.nav-section { padding: 0.5rem 1.5rem; font-size: 10px; color: #334155; text-transform: uppercase; letter-spacing: 1px; margin-top: 0.5rem; }

/* Main */
.main {
  margin-left: 240px;
  flex: 1;
  padding: 2rem;
  min-height: 100vh;
}
.page-title { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; }
.page-sub { font-size: 13px; color: #64748b; margin-bottom: 1.5rem; }

/* Stats */
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.stat {
  background: #fff; border-radius: 12px;
  padding: 1.25rem 1.5rem;
  border: 1px solid #e2e8f0;
}
.stat .icon { font-size: 22px; margin-bottom: 0.5rem; }
.stat .label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.25rem; }
.stat .val { font-size: 26px; font-weight: 700; color: #1e293b; }
.stat .val.green { color: #1D9E75; }

/* Kibana embed */
.kibana-card {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  margin-bottom: 1.5rem;
}
.kibana-card .card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex; align-items: center; gap: 8px;
  font-size: 14px; font-weight: 600; color: #1e293b;
}
.live-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #1D9E75;
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}
.kibana-card iframe { display: block; width: 100%; border: none; }

/* Tables */
.table-card {
  background: #fff; border-radius: 12px;
  border: 1px solid #e2e8f0;
  overflow: hidden; margin-bottom: 1.5rem;
}
.card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex; justify-content: space-between; align-items: center;
}
.card-header h2 { font-size: 14px; font-weight: 600; color: #1e293b; }
.badge-count {
  font-size: 11px; color: #64748b;
  background: #f1f5f9; padding: 2px 10px;
  border-radius: 20px;
}
table { width: 100%; border-collapse: collapse; }
thead th {
  background: #f8fafc; padding: 0.65rem 1.5rem;
  text-align: left; font-size: 11px; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.5px;
  border-bottom: 1px solid #e2e8f0;
}
tbody td {
  padding: 0.8rem 1.5rem; font-size: 13px;
  color: #374151; border-bottom: 1px solid #f1f5f9;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: #f8fafc; }
.status-badge {
  padding: 2px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 500;
  background: #dcfce7; color: #166534;
}
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
    <a href="index.php" class="nav-item active">📊 Dashboard</a>
    <a href="../orders.php" class="nav-item">📋 Quản lý đơn hàng</a>
    <a href="../index.php" class="nav-item">🏠 Về trang chủ</a>
    <div class="nav-section">Tài khoản</div>
    <a href="../logout.php" class="nav-item">🚪 Đăng xuất</a>
  </nav>
</aside>

<main class="main">
  <div class="page-title">Admin Dashboard</div>
  <div class="page-sub">Quản lý hệ thống ShopVN Re-commerce</div>

  <!-- Stats -->
  <div class="stats">
    <div class="stat">
      <div class="icon">👥</div>
      <div class="label">Người dùng</div>
      <div class="val"><?= $total_users ?></div>
    </div>
    <div class="stat">
      <div class="icon">🛒</div>
      <div class="label">Đơn hàng</div>
      <div class="val"><?= $total_orders ?></div>
    </div>
    <div class="stat">
      <div class="icon">💰</div>
      <div class="label">Doanh thu</div>
      <div class="val green"><?= number_format($total_revenue) ?>₫</div>
    </div>
    <div class="stat">
      <div class="icon">📦</div>
      <div class="label">Sản phẩm</div>
      <div class="val"><?= $total_products ?></div>
    </div>
  </div>

  <!-- Kibana -->
  <div class="kibana-card">
<div class="card-header" style="justify-content:flex-start;gap:8px;">
      <div class="live-dot"></div>
      <h2>Live Security Monitor — cập nhật mỗi 10 giây</h2>
    </div>
    <iframe
      src="http://localhost:5601/app/dashboards#/view/31271515-e4f9-429c-8c88-c6e7d76db30a?embed=true&_g=(refreshInterval%3A(pause%3A!f%2Cvalue%3A10000)%2Ctime%3A(from%3Anow-1h%2Cto%3Anow))"
      height="600px">
    </iframe>
  </div>

  <!-- Orders -->
  <div class="table-card">
    <div class="card-header">
      <h2>Đơn hàng gần đây</h2>
      <span class="badge-count"><?= count($orders) ?> đơn</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Session</th><th>Tổng tiền</th><th>Trạng thái</th><th>Thời gian</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($orders)): ?>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= substr($o['session_id'], 0, 12) ?>...</td>
            <td><?= number_format($o['total']) ?>₫</td>
            <td><span class="status-badge"><?= $o['status'] ?></span></td>
            <td><?= $o['created_at'] ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="empty">Chưa có đơn hàng</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Users -->
  <div class="table-card">
    <div class="card-header">
      <h2>Danh sách người dùng</h2>
      <span class="badge-count"><?= count($users) ?> users</span>
    </div>
    <table>
      <thead>
        <tr><th>ID</th><th>Username</th><th>Email</th><th>Ngày tạo</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>#<?= $u['id'] ?></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
          <td><?= $u['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

</body>
</html>