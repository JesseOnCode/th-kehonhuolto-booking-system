<?php
session_start();
// Jos on jo kirjautunut, ohjataan dashboardille
if (isset($_SESSION['admin_logged_in'])) { header("Location: admin_dashboard.php"); exit; }

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Yksinkertaistettu kirjautuminen (Jesse: Tähän voi myöhemmin liittää admin_users -taulun)
    if ($_POST['username'] === 'admin' && $_POST['password'] === 'hieronta2026') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $error = "Väärä käyttäjätunnus tai salasana.";
    }
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Admin Kirjautuminen</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="booking-wrapper" style="justify-content: center; align-items: center;">
    <div class="calendar-card" style="width: 100%; max-width: 400px; text-align: center;">
        <h2 style="color: var(--gold);">Yrittäjän sisäänkirjautuminen</h2>
        <form method="POST">
            <div class="form-group" style="text-align: left;">
                <label>Käyttäjätunnus</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group" style="text-align: left;">
                <label>Salasana</label>
                <input type="password" name="password" required>
            </div>
            <?php if($error): ?><p style="color: var(--error);"><?php echo $error; ?></p><?php endif; ?>
            <button type="submit" class="confirm-btn">KIRJAUDU SISÄÄN</button>
        </form>
    </div>
</div>
</body>
</html>