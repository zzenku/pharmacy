<?php
$pageTitle = 'Подписка';
require_once __DIR__ . '/header.php';

if (!isLoggedIn()) redirect('login.php');

$user   = currentUser();
$hasSub = hasSubscription();
$db     = getDB();

// Активация (эмуляция оплаты)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe'])) {
    $expires = date('Y-m-d', strtotime('+30 days'));
    $db->prepare('UPDATE users SET subscription_active=1, subscription_expires=? WHERE id=?')
       ->execute([$expires, $user['id']]);
    flash('success', 'Подписка активирована! Теперь вам доступны оптовые цены.');
    redirect('products.php');
}

// Отмена
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel'])) {
    $db->prepare('UPDATE users SET subscription_active=0, subscription_expires=NULL WHERE id=?')
       ->execute([$user['id']]);
    flash('success', 'Подписка отменена.');
    redirect('subscription.php');
}

$flashSuccess = flash('success');
?>

<div class="container" style="max-width:820px;">
  <div class="page-header">
    <h1>💳 Подписка PharmaSub</h1>
    <p>Оформите подписку и получите доступ к оптовым ценам на все препараты</p>
  </div>

  <?php if ($flashSuccess): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($flashSuccess) ?></div>
  <?php endif; ?>

  <?php if ($hasSub): ?>
    <!-- Активная подписка -->
    <div class="card mb-3">
      <div class="card-body text-center" style="padding:40px;">
        <div style="font-size:3.5rem;margin-bottom:16px;">✅</div>
        <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:8px;">Подписка активна</h2>
        <p class="text-muted mb-3">
          Действует до: <strong><?= $user['subscription_expires'] ? date('d.m.Y', strtotime($user['subscription_expires'])) : 'Бессрочно' ?></strong>
        </p>
        <div class="alert alert-success" style="max-width:400px;margin:0 auto 24px;">
          🎉 Вам доступны оптовые цены на все препараты в каталоге!
        </div>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
          <a href="products.php" class="btn btn-primary btn-lg">🛒 Перейти в каталог</a>
          <form method="post" onsubmit="return confirm('Вы уверены, что хотите отменить подписку? Доступ к оптовым ценам будет закрыт.')">
            <button type="submit" name="cancel" class="btn btn-outline" style="color:var(--danger);border-color:var(--danger);">
              Отменить подписку
            </button>
          </form>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- Оформление подписки -->
    <div class="two-col">
      <div>
        <!-- Карточка подписки -->
        <div class="sub-box">
          <div style="font-size:.9rem;opacity:.75;text-transform:uppercase;letter-spacing:.6px;font-weight:600;">
            Подписка PharmaSub
          </div>
          <div class="sub-box__price"><?= formatPrice(SUBSCRIPTION_PRICE) ?></div>
          <div class="sub-box__period">в месяц · автопродление</div>

          <ul class="sub-features">
            <li>Оптовые цены на все препараты в каталоге (экономия 30–45%)</li>
            <li>Доступ к более чем <?= $db->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn() ?> наименованиям</li>
            <li>Приоритетная обработка заказов</li>
            <li>История всех покупок</li>
            <li>Отмена в любой момент</li>
          </ul>

          <form method="post">
            <button type="submit" name="subscribe" class="btn btn-accent btn-lg btn-block"
                    style="background:#fff;color:var(--primary-dark);">
              💳 Оформить подписку
            </button>
          </form>
          <div style="font-size:.8rem;opacity:.65;margin-top:12px;">
            Демо-режим: оплата эмулируется без реального списания
          </div>
        </div>
      </div>

      <div>
        <!-- Преимущества -->
        <div class="card">
          <div class="card-header">💡 Что даёт подписка</div>
          <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:14px;">
              <div style="display:flex;gap:12px;align-items:flex-start;">
                <span style="font-size:1.5rem;">💰</span>
                <div>
                  <div style="font-weight:600;margin-bottom:2px;">Экономия до 45%</div>
                  <div style="font-size:.84rem;color:var(--text-muted);">Оптовые цены недоступны без подписки — никаких исключений</div>
                </div>
              </div>
              <div style="display:flex;gap:12px;align-items:flex-start;">
                <span style="font-size:1.5rem;">🔓</span>
                <div>
                  <div style="font-weight:600;margin-bottom:2px;">Полный доступ к каталогу</div>
                  <div style="font-size:.84rem;color:var(--text-muted);">Все категории: кардио, антибиотики, витамины, офтальмология и др.</div>
                </div>
              </div>
              <div style="display:flex;gap:12px;align-items:flex-start;">
                <span style="font-size:1.5rem;">🚫</span>
                <div>
                  <div style="font-weight:600;margin-bottom:2px;">Без подписки — без покупки</div>
                  <div style="font-size:.84rem;color:var(--text-muted);">Просматривать каталог можно, но добавить в корзину — нельзя</div>
                </div>
              </div>
              <div style="display:flex;gap:12px;align-items:flex-start;">
                <span style="font-size:1.5rem;">↩️</span>
                <div>
                  <div style="font-weight:600;margin-bottom:2px;">Отмена в любой момент</div>
                  <div style="font-size:.84rem;color:var(--text-muted);">Никаких штрафов и скрытых условий</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Пример экономии -->
        <div class="card mt-2">
          <div class="card-header">📊 Пример экономии</div>
          <div class="card-body" style="padding:0;">
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
              <thead>
                <tr>
                  <th style="padding:10px 16px;background:var(--primary);color:#fff;text-align:left;">Препарат</th>
                  <th style="padding:10px 16px;background:var(--primary);color:#fff;text-align:right;">Аптека</th>
                  <th style="padding:10px 16px;background:var(--primary);color:#fff;text-align:right;">Подписка</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $examples = $db->query('SELECT name, price_retail, price_wholesale FROM products WHERE active=1 LIMIT 5')->fetchAll();
                foreach ($examples as $ex):
                  $s = round(($ex['price_retail'] - $ex['price_wholesale']) / $ex['price_retail'] * 100);
                ?>
                <tr style="border-bottom:1px solid var(--border);">
                  <td style="padding:10px 16px;"><?= htmlspecialchars($ex['name']) ?></td>
                  <td style="padding:10px 16px;text-align:right;color:var(--text-muted);text-decoration:line-through;"><?= formatPrice($ex['price_retail']) ?></td>
                  <td style="padding:10px 16px;text-align:right;font-weight:700;color:var(--primary);">
                    <?= formatPrice($ex['price_wholesale']) ?> <span style="font-size:.75rem;color:var(--success);">−<?= $s ?>%</span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
