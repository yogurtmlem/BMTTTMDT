<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Đặt hàng thành công - ShopVN Re-commerce</title>
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
      <button class="cart-btn">🛒 Giỏ hàng (0)</button>
    </a>
  </div>
</nav>

<main class="checkout-success-page">

  <section class="checkout-success-card">

    <div class="success-icon">✓</div>

    <p class="success-label">Đơn hàng đã được ghi nhận</p>

    <h1>Đặt hàng thành công</h1>

    <p class="success-desc">
      Cảm ơn bạn đã mua sắm tại ShopVN Re-commerce.
      Đơn hàng sẽ được kiểm tra lần cuối, đóng gói và chuẩn bị giao.
    </p>

    <div class="order-status-box">
      <div>
        <strong>Kiểm tra tình trạng</strong>
        <span>Sản phẩm được rà soát lại trước khi đóng gói.</span>
      </div>

      <div>
        <strong>Thời gian giao hàng</strong>
        <span>Dự kiến 2–3 ngày làm việc tùy khu vực.</span>
      </div>

      <div>
        <strong>Hỗ trợ đổi trả</strong>
        <span>Đổi trả trong 7 ngày nếu sản phẩm không đúng mô tả.</span>
      </div>
    </div>

    <div class="checkout-actions">
      <a href="products.php" class="primary-link-btn">
        Tiếp tục mua sắm
      </a>

      <a href="index.php" class="secondary-link-btn">
        Về trang chủ
      </a>
    </div>

  </section>

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
