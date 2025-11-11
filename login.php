<?php
require 'config.php';

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
        
        if ($user && verifyPassword($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['Account_ID'];
            $_SESSION['user_name'] = $user['Full_name'];
            $_SESSION['user_role'] = $user['Account_role'];
            $_SESSION['user_login'] = $user['Login'];
            
            header('Location: index.php');
            exit();
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
        .container { max-width: 400px; margin: 50px auto; padding: 20px; }
        .error { color: red; margin-bottom: 10px; }
        input, button { width: 100%; padding: 10px; margin: 5px 0; }
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
        <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
    </div>
</body>
</html>