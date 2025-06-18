<?php
// Ștergem cookie-ul JWT
setcookie('jwt_token', '', time() - 3600, '/', '', true, true);

// Redirecționăm către pagina de login
header('Location: login.php');
exit();
?> 