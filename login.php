<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) redirect('index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Заполните все поля.';
    } else {
        $db = getDB();
        $st = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $user = $st->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            flash('success', 'Добро пожаловать, ' . $user['name'] . '!');
            redirect('index.php');
        } else {
            $error = 'Неверный email или пароль.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Вход — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-card__top">
      <div class="auth-card__logo">Pharma<span>Sub</span></div>
      <div class="auth-card__subtitle">Аптека по подписке</div>
    </div>
    <div class="auth-card__body">
      <h2 class="mb-3" style="font-size:1.3rem;font-weight:700;">Вход в аккаунт</h2>

      <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="example@mail.ru" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Пароль</label>
          <input type="password" name="password" class="form-control"
                 placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">Войти</button>
      </form>

      <div class="auth-switch mt-3">
        Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
      </div>

      <div class="alert alert-info mt-3" style="font-size:.82rem;">
        <div>
          <strong>Тестовые аккаунты:</strong><br>
          👑 Админ: <code>admin@pharmasub.ru</code> / <code>password</code><br>
          ✅ С подпиской: <code>user@pharmasub.ru</code> / <code>user123</code><br>
          🔒 Без подписки: <code>test@pharmasub.ru</code> / <code>password</code>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
