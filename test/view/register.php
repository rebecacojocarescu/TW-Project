<?php
require_once '../controllers/UserController.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: homepage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new UserController();
    $result = $controller->register($_POST);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Pow</title>
    <link rel="stylesheet" href="../stiluri/styles.css" />
    <link rel="stylesheet" href="../stiluri/register.css" />
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
  </head>
  <body>
    <div class="login-container">
      <div class="login-box">
        <h1>REGISTER</h1>
        <div id="popup" class="popup"></div>
        <form id="registerForm" method="POST">
          <input type="text" name="name" placeholder="Name" class="input-field" required />
          <input type="text" name="surname" placeholder="Surname" class="input-field" required />
          <input type="email" name="email" placeholder="Email" class="input-field" required />
          <input type="password" name="password" placeholder="Password" class="input-field" required />
          <input type="password" name="confirm_password" placeholder="Confirm Password" class="input-field" required />
          <button type="submit" class="login-button" id="submitBtn">REGISTER</button>
        </form>
        <p class="signup-text">
          Already have an account? <a href="login.php">Login</a>
        </p>
      </div>
      <div class="circle"></div>
      <div class="cat-image">
        <img src="../stiluri/imagini/cat-image.png" alt="Cat" />
      </div>
    </div>

    <script>
      function showPopup(message, type, isErrorList) {
        var popup = document.getElementById('popup');
        
        if (isErrorList && Array.isArray(message)) {
          var ul = document.createElement('ul');
          ul.className = 'error-list';
          message.forEach(function(error) {
            var li = document.createElement('li');
            li.textContent = error;
            ul.appendChild(li);
          });
          popup.innerHTML = '';
          popup.appendChild(ul);
        } else {
          popup.textContent = message;
        }
        
        popup.className = 'popup ' + type + ' show';
        
        setTimeout(function() {
          popup.classList.remove('show');
        }, 3000);
      }

      document.getElementById('registerForm').addEventListener('submit', function(event) {
        event.preventDefault();
        
        var form = event.target;
        var submitBtn = document.getElementById('submitBtn');
        var formData = new FormData(form);
        
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;

        fetch(form.action, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          submitBtn.classList.remove('loading');
          submitBtn.disabled = false;

          if (data.success) {
            window.location.href = 'login.php?success=1';
          } else {
            showPopup(data.errors, 'error', true);
          }
        })
        .catch(error => {
          submitBtn.classList.remove('loading');
          submitBtn.disabled = false;
          showPopup('Eroare de conexiune', 'error');
        });
      });

      document.querySelector('input[name="email"]').addEventListener('input', function(e) {
        var email = e.target.value;
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
          e.target.setCustomValidity('Introduceți o adresă de email validă');
        } else {
          e.target.setCustomValidity('');
        }
      });

      document.querySelector('input[name="confirm_password"]').addEventListener('input', function(e) {
        var password = document.querySelector('input[name="password"]').value;
        var confirmPassword = e.target.value;
        
        if (password !== confirmPassword) {
          e.target.setCustomValidity('Parolele nu coincid');
        } else {
          e.target.setCustomValidity('');
        }
      });
    </script>
  </body>
</html>