<?php
session_start();

// Show the correct form (from session or URL)
$activeForm = $_SESSION['active_form'] ?? ($_GET['form'] ?? 'login');

// Save and clear any error messages
$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];
unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

function showError($error) {
    return !empty($error) ? "<p class='error-message'>$error</p>" : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}
?>



<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> Login and Register Forms | WeCommerce</title>
        <link rel="stylesheet" href="CSS/loginstyle.css">
    </head>

<body>
    <div class="login__container">
        <div class="form-box active <?= isActiveForm('login' , $activeForm); ?>" id="login-form">
            <form action="login_register.php" method="post">
                <h2>Login</h2>
                <?= showError($errors['login']); ?>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
                <p>Don't have an account? <a href="#" onclick="showForm('Register-form')">Register</a></p>

            </form>
        </div>

        <div class="form-box <?= isActiveForm('register' , $activeForm); ?>" id="Register-form">
            <form action="login_register.php" method="post">
                <h2>Register</h2>
                 <?= showError($errors['register']); ?>
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role" required>
                    <option value="">--Select Role--</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>    
                </select>
                <button type="submit" name="register">Register</button>
                <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>

            </form>
        </div>
     </div>

    
        
     

     <script src="script.js"></script>

</body>


</html>