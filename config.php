<?php
// =============================================
// Конфигурация подключения к базе данных
// Измените параметры под ваш localhost
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // ваш пользователь MySQL
define('DB_PASS', '');           // ваш пароль MySQL (обычно пустой на localhost)
define('DB_NAME', 'pharmasub');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'PharmaSub');
define('SUBSCRIPTION_PRICE', 990);  // цена подписки в рублях/мес
define('CURRENCY', '₽');

// Подключение к БД
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;">
                <h2>Ошибка подключения к базе данных</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Проверьте параметры в <strong>config.php</strong> и убедитесь, что MySQL запущен в XAMPP.</p>
            </div>');
        }
    }
    return $pdo;
}

// Старт сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Вспомогательные функции
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $db = getDB();
        $st = $db->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$_SESSION['user_id']]);
        $user = $st->fetch() ?: null;
    }
    return $user;
}

function hasSubscription(): bool {
    $user = currentUser();
    if (!$user) return false;
    if ($user['role'] === 'admin') return true;
    if (!$user['subscription_active']) return false;
    if ($user['subscription_expires'] && $user['subscription_expires'] < date('Y-m-d')) return false;
    return true;
}

function isAdmin(): bool {
    $user = currentUser();
    return $user && $user['role'] === 'admin';
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function formatPrice(float $price): string {
    return number_format($price, 0, '.', ' ') . ' ' . CURRENCY;
}

function cartCount(): int {
    if (!isLoggedIn()) return 0;
    $db = getDB();
    $st = $db->prepare('SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?');
    $st->execute([$_SESSION['user_id']]);
    return (int)$st->fetchColumn();
}

function flash(string $key, string $msg = ''): string {
    if ($msg) {
        $_SESSION['flash'][$key] = $msg;
        return '';
    }
    $val = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $val;
}
