<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head><title>Выход</title></head>
<body style="text-align: center; padding: 50px;">
    <p>Вы уверены, что хотите выйти?</p>
    <p>
        <a href="logout.php?confirm=yes" style="margin: 0 10px;">Да</a>
        <a href="javascript:history.back()" style="margin: 0 10px;">Нет</a>
    </p>
</body>
</html>