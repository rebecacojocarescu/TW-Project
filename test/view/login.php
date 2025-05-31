<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link rel="stylesheet" href="../stiluri/styles.css" />
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
    </style>
  </head>
  <body>
    <div class="login-container">
      <div class="login-box">
        <h1>LOGIN</h1>
        <div id="popup" class="popup"></div>
        <form id="loginForm" onsubmit="return handleSubmit(event)">
          <input type="hidden" name="action" value="login">
          <input type="text" name="name" placeholder="Name" class="input-field" required />
          <input type="text" name="surname" placeholder="Surname" class="input-field" required />
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