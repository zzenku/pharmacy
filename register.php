<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) redirect('index.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'Заполните все поля.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email.';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов.';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают.';
    } else {
        $db = getDB();
        $st = $db->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            $error = 'Этот email уже зарегистрирован.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare('INSERT INTO users (name, email, password) VALUES (?,?,?)')
               ->execute([$name, $email, $hash]);
            $_SESSION['user_id'] = $db->lastInsertId();
            flash('success', 'Аккаунт создан! Оформите подписку, чтобы начать покупать.');
            redirect('subscription.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Регистрация — <?= SITE_NAME ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-card__top">
      <div class="auth-card__logo">Pharma<span>Sub</span></div>
      <div class="auth-card__subtitle">Оптовые цены — только для подписчиков</div>
    </div>
    <div class="auth-card__body">
      <h2 class="mb-3" style="font-size:1.3rem;font-weight:700;">Создать аккаунт</h2>

      <?php if ($error): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="form-group">
          <label class="form-label">Имя</label>
          <input type="text" name="name" class="form-control"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                 placeholder="Иван Петров" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="example@mail.ru" required>
        </div>
        <div class="form-group">
          <label class="form-label">Пароль</label>
          <input type="password" name="password" class="form-control"
                 placeholder="Минимум 6 символов" required>
        </div>
        <div class="form-group">
          <label class="form-label">Подтвердите пароль</label>
          <input type="password" name="confirm" class="form-control"
                 placeholder="Повторите пароль" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
          Зарегистрироваться
        </button>
      </form>

      <div class="auth-switch mt-3">
        Уже есть аккаунт? <a href="login.php">Войти</a>
      </div>

      <div class="alert alert-warning mt-3" style="font-size:.83rem;">
        🔒 После регистрации вам потребуется <strong>оформить подписку</strong>
        (<?= formatPrice(SUBSCRIPTION_PRICE) ?>/мес), чтобы получить доступ к покупкам по оптовым ценам.
      </div>
    </div>
  </div>
</div>

</body>
</html>
