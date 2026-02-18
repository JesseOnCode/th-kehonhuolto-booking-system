<?php
/**
 * ADMIN_LOGIN.PHP
 * Yrittäjän sisäänkirjautuminen käyttäjätunnuksella.
 */

// Secure headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

session_start();
require_once 'db_config.php';

// Jos yrittäjä on jo kirjautunut, ohjataan dashboardille
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}

// 2. CSRF TOKEN GENEROINTI LOMAKKEELLE (varmistaa, että lomakkeen lähettäjä on todella käyttäjä itse eikä ulkopuolinen sivusto.)
if (!isset($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

// 3. BRUTE FORCE PROTECTION - kirjautumisen suojaus
function check_rate_limit() {
    //ensimmäinen yritys
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_login_attempt'] = time();
    }
    
    // tarkistetaan, onko edellisen salasanan syöttöyrityksestä kulunut 15min
    if (time() - $_SESSION['last_login_attempt'] > 900) {
        $_SESSION['login_attempts'] = 0;
    }
    
    // ajan päivitys
    $_SESSION['last_login_attempt'] = time();
    
    // max 5 yritystä 15 minuutin sisään
    if ($_SESSION['login_attempts'] >= 5) {
        return false;
    }
    
    return true;
}

// käsitellään kirjautuminen
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // CSRF-suojaus
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['login_csrf_token']) {
        $error = "Virheellinen pyyntö. Yritä uudelleen.";
    }
    // Rate limiting check
    elseif (!check_rate_limit()) {
        $error = "Liian monta kirjautumisyritystä. Odota 15 minuuttia.";
    }
    else {
        // tarkistetaan käyttäjänimen ja salasanan syöte
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';
        
        $username = trim($username);

        if (empty($username) || empty($password)) {
            $error = "Täytä molemmat kentät.";
            $_SESSION['login_attempts']++;
        } 
        // tarkistetaan vielä käyttäjänimi
        elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $error = "Virheellinen käyttäjätunnus.";
            $_SESSION['login_attempts']++;
        }
        else {
            try {
                // Haetaan admin käyttäjätunnuksen perusteella
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :user LIMIT 1");
                $stmt->execute([':user' => $username]);
                $admin = $stmt->fetch();

                // Käytetään password_verify joka on turvallinen timing attackeja vastaan
                if ($admin && password_verify($password, $admin['password'])) {
                    // KIRJAUTUMINEN ONNISTUI
                    
                    // Estetään session fixation -hyökkäykset
                    session_regenerate_id(true);
                    
                    // Asetetaan session muuttujat
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['last_activity'] = time();
                    
                    // Generoidaan CSRF token dashboardia varten
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    // Nollataan login attempts
                    $_SESSION['login_attempts'] = 0;
                    
                    // Poistetaan login CSRF token
                    unset($_SESSION['login_csrf_token']);

                    header("Location: admin_dashboard.php");
                    exit;
                } else {
                    // Geneerinen virheviesti (ei paljasta oliko käyttäjä vai salasana väärin)
                    $error = "Väärä käyttäjätunnus tai salasana.";
                    $_SESSION['login_attempts']++;
                    
                    // Lisätään pieni viive brute force -hyökkäysten hidastamiseksi
                    usleep(500000); // 0.5 sekuntia
                }
            } catch (PDOException $e) {
                // Lokitetaan virhe turvallisesti
                error_log("Login error: " . $e->getMessage());
                $error = "Järjestelmävirhe. Yritä myöhemmin uudelleen.";
            }
        }
    }
    
    // Regeneroidaan login CSRF token uutta yritystä varten
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sisäänkirjautuminen</title>
    <link rel="stylesheet" href="css/style.css">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #0f0f0f;">

<div class="calendar-card" style="width: 100%; max-width: 400px; padding: 40px; text-align: center;">
    <div class="profile-logo" style="margin-bottom: 20px;">
        <img src="logo.jpg" alt="Logo">
    </div>
    
    <h2 style="color: var(--gold); margin-bottom: 20px;">Hallintapaneeli</h2>

    <?php if ($error): ?>
        <p style="color: var(--error); background: rgba(231, 76, 60, 0.1); padding: 10px; border-radius: 4px; font-size: 14px;">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>
    
    <?php if (isset($_GET['error']) && $_GET['error'] === 'session_timeout'): ?>
        <p style="color: #f39c12; background: rgba(243, 156, 18, 0.1); padding: 10px; border-radius: 4px; font-size: 14px;">
            Istunto vanhentunut. Kirjaudu uudelleen.
        </p>
    <?php endif; ?>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out'): ?>
        <p style="color: #2ecc71; background: rgba(46, 204, 113, 0.1); padding: 10px; border-radius: 4px; font-size: 14px;">
            Olet kirjautunut ulos.
        </p>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['login_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        
        <div class="form-group" style="text-align: left;">
            <label>Käyttäjätunnus</label>
            <input type="text" name="username" required 
                   placeholder="admin" 
                   autocomplete="username"
                   maxlength="50"
                   pattern="[a-zA-Z0-9_]{3,50}"
                   title="3-50 merkkiä, vain kirjaimet, numerot ja alaviiva">
        </div>
        
        <div class="form-group" style="text-align: left; margin-top: 20px;">
            <label>Salasana</label>
            <input type="password" name="password" required 
                   placeholder="••••••••" 
                   autocomplete="current-password"
                   minlength="6">
        </div>

        <button type="submit" class="confirm-btn" style="margin-top: 30px; width: 100%;">
            KIRJAUDU SISÄÄN
        </button>
    </form>
    
    <p style="margin-top: 20px;">
        <a href="booking.php" style="color: var(--muted); text-decoration: none; font-size: 13px;">
            ← Takaisin varauskalenteriin
        </a>
    </p>
</div>

</body>
</html>
