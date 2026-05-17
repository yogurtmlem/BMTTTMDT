<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<title>Đặt hàng thành công - ShopVN</title>
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
      <span style="color:#1D9E75;font-size:14px">👤 <?= $_SESSION['user'] ?></span>
      <a href="logout.php">Đăng xuất</a>
    <?php else: ?>
      <a href="login.php">Đăng nhập</a>
    <?php endif; ?>
    <a href="cart.php"><button class="cart-btn">🛒 Giỏ hàng (0)</button></a>
  </div>
</nav>

<div class="login-page">
  <div class="login-card" style="text-align:center">
    <div style="font-size:64px;margin-bottom:1rem">✅</div>
    <h2>Đặt hàng thành công!</h2>
    <p style="margin:1rem 0;color:#666">Cảm ơn bạn đã mua hàng tại ShopVN.<br/>Đơn hàng của bạn đang được xử lý.</p>
    <div style="background:#f0faf6;border:1px solid #9FE1CB;border-radius:8px;padding:1rem;margin:1rem 0">
      <p style="color:#085041;font-size:14px">🚚 Dự kiến giao hàng trong 2-3 ngày làm việc</p>
    </div>
    <a href="index.php"><button class="hero-btn" style="width:100%">Tiếp tục mua sắm</button></a>
  </div>
</div>

<footer>
  <p>© 2026 ShopVN. All rights reserved.</p>
  <div class="footer-links">
    <a href="#">Chính sách</a>
    <a href="#">Liên hệ</a>
    <a href="#">Hỗ trợ</a>
  </div>
</footer>
</body>
</html>