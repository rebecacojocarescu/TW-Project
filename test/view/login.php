<?php
session_start();
require_once '../model/User.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($surname) || empty($password)) {
        $error = "All fields are required";
    } else {
        $user = new User();
        $result = $user->authenticate($name, $surname, $password);

        if ($result) {
            $_SESSION['user_id'] = $result['id'];
            $_SESSION['user_name'] = $name;
            $_SESSION['user_surname'] = $surname;
            header("Location: homepage.php");
            exit;
        } else {
            $error = "Invalid credentials";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Pow</title>
    <link rel="stylesheet" href="../stiluri/styles.css" />
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
  </head>
  <body>
    <div class="login-container">
      <div class="login-box">
        <h1>LOGIN</h1>
        <div id="popup" class="popup"></div>
        <?php if ($error): ?>
          <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>
        <form id="loginForm" onsubmit="return handleSubmit(event)">
          <input type="hidden" name="action" value="login">
          <input type="text" name="name" placeholder="Name" class="input-field" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" />
          <input type="text" name="surname" placeholder="Surname" class="input-field" required value="<?php echo isset($_POST['surname']) ? htmlspecialchars($_POST['surname']) : ''; ?>" />
          <input type="password" name="password" placeholder="Password" class="input-field" required />
          <button type="submit" class="login-button" id="submitBtn">LOGIN</button>
        </form>
        <p class="signup-text">
          Don't have an account? <a href="register.php">Sign up</a>
        </p>
      </div>
      <div class="circle"></div>
      <div class="cat-image">
        <img src="../stiluri/imagini/cat-image.png" alt="Cat" />
      </div>
    </div>

    <script>
      function showPopup(message, type) {
        var popup = document.getElementById('popup');
        popup.textContent = message;
        popup.className = 'popup ' + type + ' show';
        
        setTimeout(function() {
          popup.classList.remove('show');
        }, 3000);
      }

      function handleSubmit(event) {
        event.preventDefault();
        
        var form = event.target;
        var submitBtn = document.getElementById('submitBtn');
        var formData = new FormData(form);
        
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../controller/AuthController.php', true);

        xhr.onreadystatechange = function() {
          if (xhr.readyState === 4) {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;

            if (xhr.status === 200) {
              try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                  window.location.href = response.redirect;
                } else {
                  showPopup(response.message, 'error');
                }
              } catch (e) {
                showPopup('Eroare la procesarea răspunsului', 'error');
              }
            } else {
              showPopup('Eroare de conexiune', 'error');
            }
          }
        };

        xhr.send(formData);
        return false;
      }

      window.onload = function() {
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success')) {
          showPopup('Cont creat cu succes! Vă puteți autentifica.', 'success');
          history.replaceState({}, document.title, window.location.pathname);
        }
      };
    </script>
  </body>
</html>