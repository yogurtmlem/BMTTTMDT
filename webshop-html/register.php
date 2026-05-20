<?php
session_start();
require __DIR__ . '/db.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        $error = 'Tên đăng nhập đã tồn tại.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)")
            ->execute([$username, $hashed, $email]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Đăng ký - ShopVN</title>
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

<main class="login-page">
  <section class="login-card">
    <div class="login-header">
      <p>ShopVN Re-commerce</p>
      <h1>Tạo tài khoản mới</h1>
      <span>Đăng ký để theo dõi đơn hàng và mua sắm nhanh hơn.</span>
    </div>

    <?php if ($success): ?>
      <div style="background:#f0faf6;border:1px solid #9FE1CB;border-radius:8px;padding:1rem;margin-bottom:1rem;color:#085041;">
        ✅ Đăng ký thành công! <a href="login.php" style="color:#1D9E75">Đăng nhập ngay →</a>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="form-alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="login-form">
      <div class="form-group">
        <label>Tên đăng nhập</label>
        <input type="text" name="username" placeholder="Ví dụ: nguyenvana" required/>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="example@email.com"/>
      </div>
      <div class="form-group">
        <label>Mật khẩu</label>
        <input type="password" name="password" placeholder="Ít nhất 6 ký tự" required/>
      </div>
      <button type="submit" class="login-btn">Đăng ký</button>
    </form>

    <div class="login-footer">
      Đã có tài khoản? <a href="login.php">Đăng nhập</a>
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