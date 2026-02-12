<?php
/**
 * ADD_TIME.PHP
 * Tallentaa yrittäjän asettaman yksittäisen vapaan aloitusaika-slotin tietokantaan.
 * 
 * TIETOTURVAPARANNUKSET:
 * - CSRF token validointi
 * - Input validointi ja sanitointi
 * - Prepared statements
 * - Error logging
 */

session_start();
require_once 'db_config.php';

// 1. TURVATARKISTUS: Vain kirjautunut yrittäjä saa lisätä aikoja
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { 
    header("Location: admin_login.php");
    exit; 
}

// 2. LOMAKKEEN KÄSITTELY
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3. CSRF-SUOJAUS
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: admin_dashboard.php?error=invalid_token");
        exit;
    }
    
    // 4. INPUT VALIDOINTI JA SANITOINTI
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $time = filter_input(INPUT_POST, 'time', FILTER_SANITIZE_STRING);
    $default_t_id = 1;

    // Tarkistetaan että kentät eivät ole tyhjiä
    if (empty($date) || empty($time)) {
        header("Location: admin_dashboard.php?error=empty_fields");
        exit;
    }
    
    // 5. PÄIVÄMÄÄRÄN VALIDOINTI
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        header("Location: admin_dashboard.php?error=invalid_date_format");
        exit;
    }
    
    // 6. AJAN VALIDOINTI
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
        header("Location: admin_dashboard.php?error=invalid_time_format");
        exit;
    }

    try {
        // 7. SQL-TALLENNUS
        // INSERT IGNORE estää duplikaatit
        $sql = "INSERT IGNORE INTO available_times (treatment_id, available_date, available_time) 
                VALUES (:t_id, :date, :time)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':t_id' => $default_t_id, 
            ':date' => $date, 
            ':time' => $time . ':00' // Lisätään sekunnit
        ]);
        
        // 8. REGENEROIDAAN CSRF TOKEN
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // 9. PALATAAN DASHBOARDILLE
        header("Location: admin_dashboard.php?success=time_added");
        exit;
        
    } catch (PDOException $e) {
        // Lokitetaan virhe turvallisesti
        error_log("Add time error: " . $e->getMessage());
        header("Location: admin_dashboard.php?error=database_error");
        exit;
    }
} else {
    // Jos joku yrittää avata tiedoston suoraan selaimessa ilman lomaketta
    header("Location: admin_dashboard.php");
    exit;
}
