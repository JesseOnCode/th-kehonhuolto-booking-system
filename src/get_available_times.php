<?php
/**
 * GET_AVAILABLE_TIMES.PHP
 * Hakee tietyn päivän vapaat ajat ja palauttaa ne JSON-muodossa JavaScriptille.
 */
require_once 'db_config.php';

// Otetaan päivämäärä vastaan URL-parametrista (esim. ?date=2026-02-12)
$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode([]);
    exit;
}

try {
    /**
     * SQL-LOGIIKKA:
     * 1. Haetaan kaikki ajat taulusta 'available_times' valitulle päivälle.
     * 2. Poistetaan tuloksista ne ajat (NOT IN), jotka löytyvät jo 'appointments'-taulusta
     * ja joiden status ei ole peruttu ('cancelled').
     */
    $sql = "SELECT available_time FROM available_times 
            WHERE available_date = :date 
            AND available_time NOT IN (
                SELECT appointment_time FROM appointments 
                WHERE appointment_date = :date AND status != 'cancelled'
            )
            ORDER BY available_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':date' => $date]);
    
    // FETCH_COLUMN palauttaa pelkät kellonajat (esim. ["10:00:00", "12:00:00"])
    $times = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Muotoillaan kellonajat siistimmäksi (esim. 10:00:00 -> 10:00)
    $formatted_times = array_map(function($time) {
        return substr($time, 0, 5);
    }, $times);

    // Asetetaan vastaus JSON-muotoon
    header('Content-Type: application/json');
    echo json_encode($formatted_times);

} catch (PDOException $e) {
    // Virhetilanteessa palautetaan tyhjä lista, jotta JS ei kaadu
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>