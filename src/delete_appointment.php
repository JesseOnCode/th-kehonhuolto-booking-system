<?php
/**
 * DELETE_APPOINTMENT.PHP
 * Poistaa varauksen tietokannasta.
 * 
 * TIETOTURVAPARANNUKSET:
 * - CSRF token validointi
 * - Session tarkistus
 * - Input validointi
 * - Prepared statements
 */

session_start();
require_once 'db_config.php';

// 1. TURVATARKISTUS: Vain kirjautuneille yrittäjille
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// 2. CSRF-SUOJAUS
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    header("Location: admin_dashboard.php?error=invalid_token");
    exit;
}

// 3. INPUT VALIDOINTI
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: admin_dashboard.php?error=invalid_id");
    exit;
}

try {
    // 4. TARKISTETAAN ETTÄ VARAUS KUULUU OIKEAAN TIETOKANTAAN
    // (Estää deletion injection -hyökkäykset)
    $check_stmt = $pdo->prepare("SELECT id FROM appointments WHERE id = ?");
    $check_stmt->execute([$id]);
    
    if (!$check_stmt->fetch()) {
        header("Location: admin_dashboard.php?error=appointment_not_found");
        exit;
    }
    
    // 5. POISTETAAN VARAUS
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->execute([$id]);
    
    // 6. REGENEROIDAAN CSRF TOKEN
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // 7. PALATAAN DASHBOARDILLE
    header("Location: admin_dashboard.php?success=appointment_deleted");
    exit;
    
} catch (PDOException $e) {
    // Lokitetaan virhe turvallisesti
    error_log("Delete appointment error: " . $e->getMessage());
    header("Location: admin_dashboard.php?error=database_error");
    exit;
}
