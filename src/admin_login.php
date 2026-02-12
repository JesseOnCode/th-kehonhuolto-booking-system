<?php
/**
 * ADMIN_LOGIN.PHP
 * Yrittäjän sisäänkirjautuminen.
 */
session_start();
require_once 'db_config.php';

// Jos yrittäjä on jo sisällä, ei turhaan näytetä kirjautumista
if (isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Haetaan yrittäjä sähköpostilla
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch();

        // password_verify() osaa purkaa Jessen SQL:ssä olevan $2y$10$ -hashin
        if ($admin && password_verify($password, $admin['password'])) {
            // KIRJAUTUMINEN ONNISTUI
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];

            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Väärä sähköposti tai salasana.";
        }
    } catch (PDOException $e) {
        $error = "Tietokantavirhe: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Kirjaudu sisään | Artisan Massage</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #0f0f0f;">

<div class="calendar-card" style="width: 100%; max-width: 400px; padding: 40px; text-align: center;">
    <div class="profile-logo" style="margin-bottom: 20px;">
        <img src="logo.jpg" alt="Logo">
    </div>
    
    <h2 style="color: var(--gold); margin-bottom: 20px;">Yrittäjän hallinta</h2>

    <?php if ($error): ?>
        <p style="color: var(--error); background: rgba(231, 76, 60, 0.1); padding: 10px; border-radius: 4px;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group" style="text-align: left;">
            <label>Sähköpostiosoite</label>
            <input type="email" name="email" required placeholder="admin@demo.fi">
        </div>
        
        <div class="form-group" style="text-align: left; margin-top: 20px;">
            <label>Salasana</label>
            <input type="password" name="password" required placeholder="••••••••">
        </div>

        <button type="submit" class="confirm-btn" style="margin-top: 30px; width: 100%;">KIRJAUDU SISÄÄN</button>
    </form>
    
    <p style="margin-top: 20px;"><a href="booking.php" style="color: var(--muted); text-decoration: none; font-size: 13px;">← Takaisin kalenteriin</a></p>
</div>

</body>
</html>