<?php
session_start();
require __DIR__ . '/../db.php';

// Simple admin auth
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Get recent orders
$orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Get all users
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
body { font-family: Arial, sans-serif; background: #f5f5f5; }
.admin-layout { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }

/* Sidebar */
.admin-sidebar { background: #0f172a; padding: 0; display: flex; flex-direction: column; }
.sidebar-logo { padding: 1.5rem; border-bottom: 1px solid #1e293b; font-size: 18px; font-weight: bold; color: #fff; }
.sidebar-logo span { color: #1D9E75; }
.sidebar-subtitle { font-size: 11px; color: #64748b; font-weight: normal; display: block; margin-top: 2px; }
.admin-nav { padding: 1rem 0; }
.admin-nav a { display: flex; align-items: center; gap: 10px; color: #94a3b8; text-decoration: none; padding: 0.75rem 1.5rem; font-size: 14px; transition: all 0.2s; }
.admin-nav a:hover { background: #1e293b; color: #fff; }
.admin-nav a.active { background: #1e293b; color: #1D9E75; border-right: 3px solid #1D9E75; }

/* Main */
.admin-main { padding: 2rem; overflow-y: auto; }
.page-header { margin-bottom: 2rem; }
.page-header h1 { font-size: 22px; font-weight: bold; color: #1e293b; }
.page-header p { color: #64748b; font-size: 14px; margin-top: 4px; }

/* Stats */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
.stat-card { background: #fff; border-radius: 12px; padding: 1.25rem 1.5rem; border: 1px solid #e2e8f0; }
.stat-label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; }
.stat-value { font-size: 28px; font-weight: bold; color: #0f172a; }
.stat-value.green { color: #1D9E75; }
.stat-icon { font-size: 20px; margin-bottom: 0.5rem; }

/* Tables */
.table-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 2rem; }
.table-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.table-header h2 { font-size: 15px; font-weight: bold; color: #1e293b; }
.table-header span { font-size: 12px; color: #64748b; background: #f1f5f9; padding: 3px 10px; border-radius: 20px; }
table { width: 100%; border-collapse: collapse; }
th { background: #f8fafc; padding: 0.75rem 1.5rem; text-align: left; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; }
td { padding: 0.875rem 1.5rem; font-size: 13px; color: #374151; border-bottom: 1px solid #f1f5f9; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: #f8fafc; }
.badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; background: #dcfce7; color: #166534; }
.empty-row { text-align: center; color: #94a3b8; padding: 2rem; }

@media (max-width: 900px) {
  .admin-layout {
    grid-template-columns: 1fr;
  }
  .admin-sidebar {
    display: none;
  }
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .admin-main {
    padding: 1rem;
  }
  table {
    display: block;
    overflow-x: auto;
  }
}
</style>
</head>
<body>

<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="admin-sidebar">

    <div class="sidebar-logo">
      Shop<span>VN</span>
      <span class="sidebar-subtitle">Admin Panel</span>
    </div>

    <nav class="admin-nav">
      <a href="index.php" class="active">📊 Dashboard</a>
      <a href="../index.php">🏠 Về trang chủ</a>
      <a href="../logout.php">🚪 Đăng xuất</a>
    </nav>

  </aside>

  <!-- Main -->
  <main class="admin-main">

    <div class="page-header">
      <h1>Admin Dashboard</h1>
      <p>Quản lý hệ thống ShopVN</p>
    </div>

    <!-- Stats -->
    <div class="stats-grid">

      <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-label">Tổng người dùng</div>
        <div class="stat-value"><?= $total_users ?></div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">🛒</div>
        <div class="stat-label">Tổng đơn hàng</div>
        <div class="stat-value"><?= $total_orders ?></div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-label">Doanh thu</div>
        <div class="stat-value green">
          <?= number_format($total_revenue) ?>₫
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-label">Sản phẩm</div>
        <div class="stat-value"><?= $total_products ?></div>
      </div>

    </div>

    <!-- Orders -->
    <div class="table-card">

      <div class="table-header">
        <h2>Đơn hàng gần đây</h2>
        <span><?= count($orders) ?> đơn</span>
      </div>

      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Session</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Thời gian</th>
          </tr>
        </thead>

        <tbody>

        <?php if (!empty($orders)): ?>

          <?php foreach ($orders as $order): ?>
          <tr>
            <td>#<?= $order['id'] ?></td>

            <td>
              <?= htmlspecialchars(substr($order['session_id'], 0, 12)) ?>...
            </td>

            <td>
              <?= number_format($order['total']) ?>₫
            </td>

            <td>
              <span class="badge">
                <?= htmlspecialchars($order['status']) ?>
              </span>
            </td>

            <td><?= $order['created_at'] ?></td>
          </tr>
          <?php endforeach; ?>

        <?php else: ?>

          <tr>
            <td colspan="5" class="empty-row">
              Chưa có đơn hàng
            </td>
          </tr>

        <?php endif; ?>

        </tbody>
      </table>

    </div>

    <!-- Users -->
    <div class="table-card">

      <div class="table-header">
        <h2>Danh sách người dùng</h2>
        <span><?= count($users) ?> users</span>
      </div>

      <table>

        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Ngày tạo</th>
          </tr>
        </thead>

        <tbody>

        <?php foreach ($users as $user): ?>

          <tr>
            <td>#<?= $user['id'] ?></td>

            <td>
              <?= htmlspecialchars($user['username']) ?>
            </td>

            <td>
              <?= htmlspecialchars($user['email'] ?? '—') ?>
            </td>

            <td><?= $user['created_at'] ?></td>
          </tr>

        <?php endforeach; ?>

        </tbody>

      </table>

    </div>

  </main>

</div>

</body>
</html>