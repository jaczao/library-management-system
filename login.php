<!-- login.php -->
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Login Biblioteca</title>
</head>
<body>
  <h2>Login</h2>
  <form action="login_action.php" method="post">
    <label>Username (per bibliotecario) o Codice Fiscale (per lettore):</label>
    <input type="text" name="username" required><br/><br/>
    
    <label>Password:</label>
    <input type="password" name="password" required><br/><br/>
    
    <label>Ruolo:</label>
    <select name="ruolo">
      <option value="lettore">Lettore</option>
      <option value="bibliotecario">Bibliotecario</option>
    </select><br/><br/>
    
    <input type="submit" value="Login">
  </form>
</body>
</html>
