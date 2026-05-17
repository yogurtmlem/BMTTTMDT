<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $password === $user['password']) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['user_id'] = $user['id'];
        header('Location: index.php');
        exit;
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<title>Đăng nhập - ShopVN</title>
<link rel="stylesheet" href="style.css"/>
</head>
<body>

<nav>
  <div class="logo">Shop<span>VN</span></div>
  <div class="nav-links">
    <a href="index.php">Trang chủ</a>
    <a href="products.php">Sản phẩm</a>
    <a href="search.php">Tìm kiếm</a>
    <a href="login.php">Đăng nhập</a>
    <a href="cart.php"><button class="cart-btn">🛒 Giỏ hàng</button></a>
  </div>
</nav>

<div class="login-page">
  <div class="login-card">
    <h2>Đăng nhập</h2>
    <p>Chào mừng trở lại! Vui lòng đăng nhập để tiếp tục.</p>

    <?php if (!empty($error)): ?>
      <div style="background:#fee;border:1px solid #fcc;border-radius:8px;padding:0.75rem;margin-bottom:1rem;color:#c00;font-size:14px">
        ❌ Sai tên đăng nhập hoặc mật khẩu
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Tên đăng nhập</label>
        <input type="text" name="username" placeholder="Nhập tên đăng nhập" required/>
      </div>
      <div class="form-group">
        <label>Mật khẩu</label>
        <input type="password" name="password" placeholder="Nhập mật khẩu" required/>
      </div>
      <button type="submit" class="login-btn">Đăng nhập</button>
    </form>

    <div style="background:#f0faf6;border-radius:8px;padding:0.75rem;margin-top:1rem;font-size:13px;color:#085041">
      💡 Demo: dùng <strong>admin</strong> / <strong>admin123</strong>
    </div>

    <div class="login-footer">
      Chưa có tài khoản? <a href="#">Đăng ký ngay</a>
    </div>
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