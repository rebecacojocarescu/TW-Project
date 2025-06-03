<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - Pow</title>
    <link rel="stylesheet" href="../stiluri/styles.css" />
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
    <style>
      .popup {
        display: none;
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 5px;
        color: white;
        font-size: 14px;
        z-index: 1000;
        animation: slideIn 0.5s ease-out;
      }

      .popup.error {
        background-color: #e74c3c;
      }

      .popup.success {
        background-color: #2ecc71;
      }

      @keyframes slideIn {
        from {
          transform: translateX(100%);
          opacity: 0;
        }
        to {
          transform: translateX(0);
          opacity: 1;
        }
      }

      .popup.show {
        display: block;
      }

      /* Loading spinner */
      .login-button.loading {
        position: relative;
        color: transparent;
      }

      .login-button.loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin: -10px 0 0 -10px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
      }

      @keyframes spin {
        to { transform: rotate(360deg); }
      }

      .error-list {
        margin: 0;
        padding-left: 20px;
      }

      .error-list li {
        margin: 5px 0;
      }
    </style>
  </head>
  <body>
    <div class="login-container">
      <div class="login-box">
        <h1>REGISTER</h1>
        <div id="popup" class="popup"></div>
        <form id="registerForm" onsubmit="return handleSubmit(event)">
          <input type="hidden" name="action" value="register">
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

      function handleSubmit(event) {
        event.preventDefault();
        
        var form = event.target;
        var submitBtn = document.getElementById('submitBtn');
        var formData = new FormData(form);
        
        var password = form.querySelector('[name="password"]').value;
        var confirmPassword = form.querySelector('[name="confirm_password"]').value;
        
        if (password !== confirmPassword) {
          showPopup('Parolele nu coincid', 'error');
          return false;
        }
        
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
                  window.location.href = 'login.php?success=1';
                } else {
                  showPopup(response.errors, 'error', true);
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