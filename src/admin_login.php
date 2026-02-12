<?php
/**
 * ADMIN_LOGIN.PHP
 * Yrittäjän sisäänkirjautuminen käyttäjätunnuksella.
 */
session_start();
require_once 'db_config.php';

// 1. Jos yrittäjä on jo kirjautunut, ohjataan dashboardille
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = "";

// 2. KÄSITELLÄÄN KIRJAUTUMINEN
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trimmaus poistaa vahingossa tulleet välilyönnit tunnuksen alusta/lopusta
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Täytä molemmat kentät.";
    } else {
        try {
            // Haetaan admin käyttäjätunnuksen perusteella
            // HUOM: Varmista että tietokannassa sarake on nimeltään 'username' (eikä 'email')
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :user LIMIT 1");
            $stmt->execute([':user' => $username]);
            $admin = $stmt->fetch();

            // Tarkistetaan salasana
            if ($admin && password_verify($password, $admin['password'])) {
                // KIRJAUTUMINEN ONNISTUI
                
                // Estetään session fixation -hyökkäykset luomalla uusi session ID
                session_regenerate_id(true);
                
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];

                header("Location: admin_dashboard.php");
                exit;
            } else {
                // Geneerinen virheviesti ei paljasta, oliko tunnus vai salasana väärin
                $error = "Väärä käyttäjätunnus tai salasana.";
            }
        } catch (PDOException $e) {
            $error = "Järjestelmävirhe. Yritä myöhemmin uudelleen.";
            // Kehitysvaiheessa voit käyttää: $error = "DB Virhe: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Admin Sisäänkirjautuminen</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #0f0f0f;">

<div class="calendar-card" style="width: 100%; max-width: 400px; padding: 40px; text-align: center;">
    <div class="profile-logo" style="margin-bottom: 20px;">
        <img src="logo.jpg" alt="Logo">
    </div>
    
    <h2 style="color: var(--gold); margin-bottom: 20px;">Hallintapaneeli</h2>

    <?php if ($error): ?>
        <p style="color: var(--error); background: rgba(231, 76, 60, 0.1); padding: 10px; border-radius: 4px; font-size: 14px;">
            <?php echo $error; ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group" style="text-align: left;">
            <label>Käyttäjätunnus</label>
            <input type="text" name="username" required 
                   placeholder="admin" autocomplete="username">
        </div>
        
        <div class="form-group" style="text-align: left; margin-top: 20px;">
            <label>Salasana</label>
            <input type="password" name="password" required 
                   placeholder="••••••••" autocomplete="current-password">
        </div>

        <button type="submit" class="confirm-btn" style="margin-top: 30px; width: 100%;">
            KIRJAUDU SISÄÄN
        </button>
    </form>
    
    <p style="margin-top: 20px;"><a href="booking.php" style="color: var(--muted); text-decoration: none; font-size: 13px;">← Takaisin varauskalenteriin</a></p>
</div>

</body>
</html>