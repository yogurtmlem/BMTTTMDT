<?php
session_start();
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
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
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Đăng nhập - ShopVN Re-commerce</title>
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
    <a href="cart.php">
      <button class="cart-btn">🛒 Giỏ hàng</button>
    </a>
  </div>
</nav>

<main class="login-page">

  <section class="login-card">

    <div class="login-header">
      <p>ShopVN Re-commerce</p>
      <h1>Đăng nhập tài khoản</h1>
      <span>Theo dõi giỏ hàng, lịch sử mua và các sản phẩm đã kiểm định.</span>
    </div>

    <?php if (!empty($error)): ?>
      <div class="form-alert">
        Sai tên đăng nhập hoặc mật khẩu. Vui lòng thử lại.
      </div>
    <?php endif; ?>

    <form method="POST" class="login-form">

      <div class="form-group">
        <label for="username">Tên đăng nhập</label>
        <input
          id="username"
          type="text"
          name="username"
          placeholder="Ví dụ: admin"
          required
        />
      </div>

      <div class="form-group">
        <label for="password">Mật khẩu</label>
        <input
          id="password"
          type="password"
          name="password"
          placeholder="Nhập mật khẩu"
          required
        />
      </div>

      <button type="submit" class="login-btn">
        Đăng nhập
      </button>

    </form>

    <div class="demo-note">
      Tài khoản demo: <strong>admin</strong> / <strong>admin123</strong>
    </div>

    <div class="login-footer">
      Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
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
