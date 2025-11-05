<?php
require 'config.php';

if (isset($_SESSION['user_id']) && !isAdmin()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (isAdmin() && isset($_POST['role'])) {
        $role = intval($_POST['role']);
    } else {
        $role = 1;
    }
    
    if (empty($full_name) || empty($login) || empty($password)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        $stmt = $pdo->prepare("SELECT Account_ID FROM Account WHERE Login = ?");
        $stmt->execute([$login]);
        
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким логином уже существует';
        } else {
            $stmt = $pdo->prepare("INSERT INTO Account (Full_name, Login, Password, Account_role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $login, $password, $role]);
            
            $success = 'Регистрация успешна!';
            
            if (isAdmin()) {
                $success .= ' <a href="admin.php">Вернуться в админ-панель</a>';
            } else {
                $success .= ' Теперь вы можете <a href="login.php">войти</a>.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Регистрация</title>
    <style>
        .container { 
            max-width: 500px; 
            margin: 50px auto; 
            padding: 30px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .error { 
            color: red; 
            margin-bottom: 15px; 
            padding: 10px;
            background-color: #ffe6e6;
            border: 1px solid #ffcccc;
            border-radius: 4px;
        }
        .success { 
            color: green; 
            margin-bottom: 15px; 
            padding: 10px;
            background-color: #e6ffe6;
            border: 1px solid #ccffcc;
            border-radius: 4px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select, button { 
            width: 100%; 
            padding: 12px; 
            margin: 5px 0; 
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button { 
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 12px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .menu {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .menu a {
            margin-right: 15px;
            text-decoration: none;
            color: #007bff;
        }
        .menu a:hover {
            text-decoration: underline;
        }
        .admin-note {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="menu">
                <a href="index.php">Главная</a>
                <?php if (isAdmin()): ?>
                    <a href="admin.php">Админ-панель</a>
                <?php else: ?>
                    <a href="edit_profile.php">Профиль</a>
                <?php endif; ?>
                <a href="logout.php">Выйти</a>
            </div>
        <?php endif; ?>

        <h2><?= isAdmin() ? 'Добавить нового пользователя' : 'Регистрация' ?></h2>
        
        <?php if (isAdmin()): ?>
            <div class="admin-note">
                <strong>Режим администратора:</strong> Вы можете создавать пользователей с разными ролями.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="post">
                <div class="form-group">
                    <label for="full_name">Полное имя:</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" 
                           placeholder="Введите полное имя" required>
                </div>
                
                <div class="form-group">
                    <label for="login">Логин:</label>
                    <input type="text" id="login" name="login" 
                           value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" 
                           placeholder="Введите логин" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Пароль (не менее 6 символов)" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Подтвердите пароль:</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Повторите пароль" required>
                </div>
                
                <?php if (isAdmin()): ?>
                    <div class="form-group">
                        <label for="role">Роль пользователя:</label>
                        <select id="role" name="role">
                            <option value="1" <?= ($_POST['role'] ?? 1) == 1 ? 'selected' : '' ?>>Обычный пользователь</option>
                            <option value="2" <?= ($_POST['role'] ?? 1) == 2 ? 'selected' : '' ?>>Администратор</option>
                        </select>
                    </div>
                <?php endif; ?>
                
                <button type="submit">
                    <?= isAdmin() ? 'Создать пользователя' : 'Зарегистрироваться' ?>
                </button>
            </form>
        <?php endif; ?>

        <div style="margin-top: 20px; text-align: center;">
            <?php if (isAdmin()): ?>
                <p><a href="admin.php">← Вернуться в админ-панель</a></p>
            <?php else: ?>
                <p>Уже есть аккаунт? <a href="login.php">Войдите</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>