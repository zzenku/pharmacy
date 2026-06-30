<?php
$pageTitle = 'Профиль';
require_once __DIR__ . '/header.php';

if (!isLoggedIn()) redirect('login.php');

$db   = getDB();
$user = currentUser();
$uid  = $_SESSION['user_id'];

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $newpass = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name) {
        $error = 'Имя не может быть пустым.';
    } elseif ($newpass && strlen($newpass) < 6) {
        $error = 'Новый пароль должен быть не менее 6 символов.';
    } elseif ($newpass && $newpass !== $confirm) {
        $error = 'Пароли не совпадают.';
    } else {
        if ($newpass) {
            $hash = password_hash($newpass, PASSWORD_DEFAULT);
            $db->prepare('UPDATE users SET name=?, password=? WHERE id=?')->execute([$name, $hash, $uid]);
        } else {
            $db->prepare('UPDATE users SET name=? WHERE id=?')->execute([$name, $uid]);
        }
        $success = 'Данные успешно обновлены.';
        // Обновляем кэш пользователя
        $user['name'] = $name;
    }
}

$ordersCount = $db->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?');
$ordersCount->execute([$uid]);
$ordersCount = $ordersCount->fetchColumn();

$totalSpent = $db->prepare('SELECT COALESCE(SUM(total),0) FROM orders WHERE user_id=? AND status != "cancelled"');
$totalSpent->execute([$uid]);
$totalSpent = $totalSpent->fetchColumn();
?>

<div class="container" style="max-width:860px;">
  <div class="page-header">
    <h1>👤 Мой профиль</h1>
    <p>Управление аккаунтом и подпиской</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="two-col">
    <div>
      <!-- Редактирование данных -->
      <div class="card">
        <div class="card-header">✏️ Данные аккаунта</div>
        <div class="card-body">
          <form method="post">
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="text" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled
                     style="background:#f5f7f5;color:var(--text-muted);">
              <div class="form-text">Email изменить нельзя</div>
            </div>
            <div class="form-group">
              <label class="form-label">Имя</label>
              <input type="text" name="name" class="form-control"
                     value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <hr style="margin:20px 0;border:none;border-top:1px solid var(--border);">
            <div style="font-weight:600;font-size:.9rem;margin-bottom:12px;">Смена пароля</div>
            <div class="form-group">
              <label class="form-label">Новый пароль</label>
              <input type="password" name="new_password" class="form-control"
                     placeholder="Оставьте пустым, чтобы не менять">
            </div>
            <div class="form-group">
              <label class="form-label">Подтвердите пароль</label>
              <input type="password" name="confirm_password" class="form-control"
                     placeholder="Повторите новый пароль">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Сохранить изменения</button>
          </form>
        </div>
      </div>
    </div>

    <div>
      <!-- Статистика -->
      <div class="card mb-3">
        <div class="card-header">📊 Статистика</div>
        <div class="card-body">
          <div style="display:flex;flex-direction:column;gap:14px;">
            <div class="flex justify-between items-center">
              <span style="font-size:.9rem;color:var(--text-muted);">Заказов оформлено:</span>
              <strong><?= $ordersCount ?></strong>
            </div>
            <div class="flex justify-between items-center">
              <span style="font-size:.9rem;color:var(--text-muted);">Потрачено всего:</span>
              <strong><?= formatPrice($totalSpent) ?></strong>
            </div>
            <div class="flex justify-between items-center">
              <span style="font-size:.9rem;color:var(--text-muted);">Дата регистрации:</span>
              <strong><?= date('d.m.Y', strtotime($user['created_at'])) ?></strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Подписка -->
      <div class="card">
        <div class="card-header">💳 Подписка</div>
        <div class="card-body">
          <?php if (hasSubscription()): ?>
            <div class="alert alert-success" style="margin-bottom:14px;">
              ✅ Подписка активна
            </div>
            <div style="font-size:.88rem;color:var(--text-muted);margin-bottom:16px;">
              Действует до: <strong>
                <?= $user['subscription_expires'] ? date('d.m.Y', strtotime($user['subscription_expires'])) : 'Бессрочно' ?>
              </strong>
            </div>
            <a href="subscription.php" class="btn btn-outline btn-block btn-sm">Управление подпиской</a>
          <?php else: ?>
            <div class="alert alert-warning" style="margin-bottom:14px;">
              🔒 Подписка не оформлена
            </div>
            <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:16px;">
              Без подписки покупка препаратов недоступна
            </p>
            <a href="subscription.php" class="btn btn-accent btn-block">
              Оформить подписку — <?= formatPrice(SUBSCRIPTION_PRICE) ?>/мес
            </a>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-2">
        <a href="logout.php" class="btn btn-danger btn-block btn-sm"
           onclick="return confirm('Выйти из аккаунта?')">Выйти из аккаунта</a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
