<?php
require 'config.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    
    if (empty($login) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Account WHERE Login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if ($user) {
            $login_success = false;
            
            // Пытаемся проверить пароль как хешированный
            if (password_verify($password, $user['Password'])) {
                $login_success = true;
            } 
            // Если не сработало, проверяем как незахешированный пароль
            else if ($user['Password'] === $password) {
                $login_success = true;
                
                // Обновляем пароль на хешированный версию
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE Account SET Password = ? WHERE Account_ID = ?");
                $stmt->execute([$hashed_password, $user['Account_ID']]);
            }
            
            if ($login_success) {
                $_SESSION['user_id'] = $user['Account_ID'];
                $_SESSION['user_name'] = $user['Full_name'];
                $_SESSION['user_role'] = $user['Account_role'];
                $_SESSION['user_login'] = $user['Login'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Неверный логин или пароль';
            }
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Авторизация</title>
    <style>
        .container { 
            max-width: 400px; 
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
        input, button { 
            width: 100%; 
            padding: 12px; 
            margin: 8px 0; 
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
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Авторизация</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="text" name="login" placeholder="Логин" 
                   value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
        <p style="text-align: center; margin-top: 15px;">
            Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>
        </p>
    </div>
</body>
</html>