<?php
require 'config.php';
checkAuth();

$error = '';
$success = '';

$stmt = $pdo->prepare("SELECT * FROM Account WHERE Account_ID = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Валидация
    if (empty($full_name)) {
        $error = 'ФИО не может быть пустым';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = 'Новый пароль должен быть не менее 6 символов';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = 'Новые пароли не совпадают';
    } elseif (!empty($new_password) && $user['Password'] !== $current_password) {
        $error = 'Текущий пароль неверен';
    } else {
        if (!empty($new_password)) {
            $stmt = $pdo->prepare("UPDATE Account SET Full_name = ?, Password = ? WHERE Account_ID = ?");
            $stmt->execute([$full_name, $new_password, $_SESSION['user_id']]);
            $success = 'Профиль и пароль успешно обновлены!';
        } else {
            $stmt = $pdo->prepare("UPDATE Account SET Full_name = ? WHERE Account_ID = ?");
            $stmt->execute([$full_name, $_SESSION['user_id']]);
            $success = 'Профиль успешно обновлен!';
        }
        
        $_SESSION['user_name'] = $full_name;
        
        $user['Full_name'] = $full_name;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Редактирование профиля</title>
    <style>
        .container { max-width: 500px; margin: 20px auto; padding: 20px; }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
        input, button { width: 100%; padding: 10px; margin: 5px 0; }
        .menu { margin-bottom: 20px; }
        .menu a { margin-right: 15px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="menu">
            <a href="index.php">Главная</a>
            <a href="edit_profile.php">Профиль</a>
            <?php if (isAdmin()): ?>
                <a href="admin.php">Админ-панель</a>
            <?php endif; ?>
            <a href="logout.php">Выйти</a>
        </div>

        <h2>Редактирование профиля</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="post">
            <div>
                <label>Логин:</label>
                <input type="text" value="<?= htmlspecialchars($user['Login']) ?>" disabled>
                <small>Логин нельзя изменить</small>
            </div>
            
            <div>
                <label>ФИО:</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['Full_name']) ?>" required>
            </div>
            
            <div>
                <label>Текущий пароль (требуется для смены пароля):</label>
                <input type="password" name="current_password" placeholder="Введите текущий пароль">
            </div>
            
            <div>
                <label>Новый пароль (оставьте пустым, если не хотите менять):</label>
                <input type="password" name="new_password" placeholder="Новый пароль">
            </div>
            
            <div>
                <label>Подтвердите новый пароль:</label>
                <input type="password" name="confirm_password" placeholder="Повторите новый пароль">
            </div>
            
            <button type="submit">Сохранить изменения</button>
        </form>
    </div>
</body>
</html>