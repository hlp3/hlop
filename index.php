<?php
require 'config.php';
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_bucket'])) {
    $product_id = intval($_POST['product_id']);
    
    $stmt = $pdo->prepare("SELECT * FROM Products WHERE Product_ID = ? AND Amount > 0");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        $stmt = $pdo->prepare("
            SELECT * FROM Purchase_products 
            WHERE Account_ID = ? AND Product_ID = ? AND Bucket_ID = 0
        ");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $existing_item = $stmt->fetch();
        
        if (!$existing_item) {
            $new_id = getNewPurchaseId($pdo);
            $stmt = $pdo->prepare("
                INSERT INTO Purchase_products (ID, Account_ID, Build_a_PC, Product_ID, Bucket_ID) 
                VALUES (?, ?, 0, ?, 0)
            ");
            $stmt->execute([$new_id, $_SESSION['user_id'], $product_id]);
            $success_message = "Товар добавлен в корзину!";
        } else {
            $info_message = "Товар уже в корзине";
        }
    } else {
        $error_message = "Товар не найден или отсутствует на складе";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_user'])) {
    if (!isAdmin()) {
        $reg_error = 'Только администраторы могут регистрировать новых пользователей';
    } else {
        $full_name = trim($_POST['full_name']);
        $login = trim($_POST['login']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = intval($_POST['role'] ?? 1);
        
        // Валидация
        if (empty($full_name) || empty($login) || empty($password)) {
            $reg_error = 'Все поля обязательны для заполнения';
        } elseif ($password !== $confirm_password) {
            $reg_error = 'Пароли не совпадают';
        } elseif (strlen($password) < 3) {
            $reg_error = 'Пароль должен быть не менее 3 символов';
        } else {
            $stmt = $pdo->prepare("SELECT Account_ID FROM Account WHERE Login = ?");
            $stmt->execute([$login]);
            
            if ($stmt->fetch()) {
                $reg_error = 'Пользователь с таким логином уже существует';
            } else {
                $hashed_password = hashPassword($password);
                
                $stmt = $pdo->prepare("INSERT INTO Account (Full_name, Login, Password, Account_role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$full_name, $login, $hashed_password, $role]);
                
                $reg_success = 'Новый пользователь успешно зарегистрирован!';
                
                $_POST['full_name'] = $_POST['login'] = '';
            }
        }
    }
}

$bucket_count = getBucketItemCount($pdo, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Главная страница</title>
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .user-info { background: #f0f0f0; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .products { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px; }
        .product { border: 1px solid #ddd; padding: 15px; border-radius: 5px; text-align: center; }
        .menu { margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .menu a { margin-right: 15px; text-decoration: none; color: #007bff; }
        .menu a:hover { text-decoration: underline; }
        .admin-badge { background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .bucket-count { background: #007bff; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .success { color: green; padding: 10px; background: #e6ffe6; border: 1px solid #ccffcc; border-radius: 4px; margin: 10px 0; }
        .info { color: #007bff; padding: 10px; background: #e6f3ff; border: 1px solid #b3d9ff; border-radius: 4px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffe6e6; border: 1px solid #ffcccc; border-radius: 4px; margin: 10px 0; }
        .add-to-bucket { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .add-to-bucket:hover { background: #218838; }
        .add-to-bucket:disabled { background: #6c757d; cursor: not-allowed; }
        .product-price { font-size: 1.2em; font-weight: bold; color: #e44d26; margin: 10px 0; }
        .in-bucket { color: #28a745; font-weight: bold; margin-top: 10px; }
        
        .register-form {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .register-form h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }
        .form-row {
            margin-bottom: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .form-row label {
            min-width: 150px;
            font-weight: bold;
        }
        .form-row input, .form-row select {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-row input:focus, .form-row select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        .register-btn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .register-btn:hover {
            background: #138496;
        }
        .toggle-form {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .toggle-form:hover {
            background: #5a6268;
        }
        .admin-section {
            border: 2px solid #dc3545;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            background: #fff;
        }
        .admin-section h3 {
            color: #dc3545;
            margin-top: 0;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggleRegisterForm');
            const registerForm = document.getElementById('registerForm');
            
            if (toggleBtn && registerForm) {
                registerForm.style.display = 'none';
                
                toggleBtn.addEventListener('click', function() {
                    if (registerForm.style.display === 'none') {
                        registerForm.style.display = 'block';
                        toggleBtn.textContent = 'Скрыть форму регистрации';
                    } else {
                        registerForm.style.display = 'none';
                        toggleBtn.textContent = 'Зарегистрировать нового пользователя';
                    }
                });
            }
            
            const regSuccess = document.querySelector('.success[style*="green"]');
            if (regSuccess) {
                setTimeout(() => {
                    const form = document.querySelector('form[action*="register"]');
                    if (form) {
                        form.reset();
                    }
                }, 1000);
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="menu">
            <a href="index.php">Главная</a>
            <a href="edit_profile.php">Профиль и корзина <span class="bucket-count"><?= $bucket_count ?></span></a>
            <?php if (isAdmin()): ?>
                <a href="admin.php">Админ-панель</a>
            <?php endif; ?>
            <a href="logout.php">Выйти</a>
        </div>

        <div class="user-info">
            <h2>Добро пожаловать, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
            <p>
                <strong>Логин:</strong> <?= htmlspecialchars($_SESSION['user_login']) ?>
                <?php if (isAdmin()): ?>
                    <span class="admin-badge">Администратор</span>
                <?php endif; ?>
            </p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if (isset($info_message)): ?>
            <div class="info"><?= $info_message ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
            <div class="admin-section">
                <h3>Панель администратора</h3>
                
                <button class="toggle-form" id="toggleRegisterForm">
                    Зарегистрировать нового пользователя
                </button>
                
                <div class="register-form" id="registerForm">
                    <h3>Регистрация нового пользователя</h3>
                    
                    <?php if (isset($reg_error)): ?>
                        <div class="error"><?= $reg_error ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($reg_success)): ?>
                        <div class="success"><?= $reg_success ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-row">
                            <label for="full_name">Полное имя:</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" 
                                   placeholder="Введите полное имя" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="login">Логин:</label>
                            <input type="text" id="login" name="login" 
                                   value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" 
                                   placeholder="Введите логин" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="password">Пароль:</label>
                            <input type="password" id="password" name="password" 
                                   placeholder="Пароль (не менее 3 символов)" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="confirm_password">Подтвердите пароль:</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="Повторите пароль" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="role">Роль пользователя:</label>
                            <select id="role" name="role">
                                <option value="1">Обычный пользователь</option>
                                <option value="2">Администратор</option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label></label>
                            <button type="submit" name="register_user" value="1" class="register-btn">
                                Зарегистрировать пользователя
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <h3>Каталог товаров:</h3>
        <div class="products">
            <?php
            $products = $pdo->query("SELECT * FROM Products WHERE Amount > 0 ORDER BY Product_ID")->fetchAll();
            
            if (count($products) > 0) {
                $bucket_items_stmt = $pdo->prepare("
                    SELECT Product_ID 
                    FROM Purchase_products 
                    WHERE Account_ID = ? AND Bucket_ID = 0
                ");
                $bucket_items_stmt->execute([$_SESSION['user_id']]);
                $bucket_items = $bucket_items_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($products as $product) {
                    $in_bucket = in_array($product['Product_ID'], $bucket_items);
                    
                    echo "
                    <div class='product'>
                        <h4>" . htmlspecialchars($product['Name']) . "</h4>
                        <div class='product-price'>{$product['Price']} руб.</div>
                        <p><strong>В наличии:</strong> {$product['Amount']} шт.</p>
                        
                        <form method='post'>
                            <input type='hidden' name='product_id' value='{$product['Product_ID']}'>";
                    
                    if ($in_bucket) {
                        echo "
                            <div class='in-bucket'>✓ В корзине</div>
                            <button type='submit' name='add_to_bucket' class='add-to-bucket' disabled>Добавлено</button>";
                    } else {
                        echo "
                            <button type='submit' name='add_to_bucket' class='add-to-bucket'>Добавить в корзину</button>";
                    }
                    
                    echo "
                        </form>
                    </div>";
                }
            } else {
                echo "<p>Товары отсутствуют. " . (isAdmin() ? 'Добавьте товары через <a href="admin.php">админ-панель</a>.' : '') . "</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>