<?php
/**
 * ADD_TIME_RANGE.PHP
 * Generoi vapaat aloitusaika-vaihtoehdot 30 minuutin välein yrittäjän valitsemalle välille.
 * 
 * TIETOTURVAPARANNUKSET:
 * - Session regeneration
 * - CSRF token validointi
 * - Input validointi ja sanitointi
 * - Rate limiting valmius
 */

session_start();
require_once 'db_config.php';

// 1. TURVATARKISTUS - Vain kirjautuneet ylläpitäjät
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { 
    header("Location: admin_login.php");
    exit; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. CSRF-SUOJAUS
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: admin_dashboard.php?error=invalid_token");
        exit;
    }
    
    // 3. INPUT VALIDOINTI JA SANITOINTI
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $start = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
    $end = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);
    
    // Käytetään 30 minuuttia "atomisena" yksikkönä
    $step_minutes = 30;

    if (empty($date) || empty($start) || empty($end)) {
        header("Location: admin_dashboard.php?error=missing_values");
        exit;
    }
    
    // 4. PÄIVÄMÄÄRÄN VALIDOINTI
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        header("Location: admin_dashboard.php?error=invalid_date_format");
        exit;
    }
    
    // 5. AJAN VALIDOINTI
    if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
        header("Location: admin_dashboard.php?error=invalid_time_format");
        exit;
    }
    
    // 6. LOOGINEN VALIDOINTI: Loppuaika oltava alkuaikaa myöhemmin
    $start_timestamp = strtotime("$date $start");
    $end_timestamp = strtotime("$date $end");
    
    if ($start_timestamp >= $end_timestamp) {
        header("Location: admin_dashboard.php?error=invalid_time_range");
        exit;
    }

    try {
        // 7. TRANSAKTIO
        $pdo->beginTransaction();

        // SQL: INSERT IGNORE estää duplikaatit
        $sql = "INSERT IGNORE INTO available_times (treatment_id, available_date, available_time) 
                VALUES (1, :date, :time)";
        $stmt = $pdo->prepare($sql);

        // 8. GENEROIDAAN AJAT SILMUKASSA
        // TÄRKEÄ: Generoidaan slotit vain siihen asti, että viimeinenkin mahdollinen varaus 
        // (pisin hoito eli 90min) mahtuisi ennen loppuaikaa
        $current = $start_timestamp;
        
        // Haetaan pisin hoitoaika tietokannasta
        $max_treatment_stmt = $pdo->query("SELECT MAX(duration) as max_duration FROM treatments");
        $max_duration = $max_treatment_stmt->fetchColumn() ?: 90;
        
        // Lasketaan viimeinen sallittu aloitusaika: loppuaika - pisin hoito
        $last_allowed_start = $end_timestamp - ($max_duration * 60);
        
        while ($current <= $last_allowed_start) {
            $stmt->execute([
                ':date' => $date,
                ':time' => date("H:i:s", $current)
            ]);
            
            // Siirrytään 30 minuuttia eteenpäin
            $current += ($step_minutes * 60);
        }

        $pdo->commit();
        
        // 9. REGENEROIDAAN CSRF TOKEN
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        header("Location: admin_dashboard.php?success=range_added");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        
        // Lokitetaan virhe turvallisesti
        error_log("Add time range error: " . $e->getMessage());
        header("Location: admin_dashboard.php?error=database_error");
        exit;
    }
} else {
    header("Location: admin_dashboard.php");
    exit;
}
