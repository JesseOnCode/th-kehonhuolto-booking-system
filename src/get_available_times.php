<?php
/**
 * GET_AVAILABLE_TIMES.PHP - KORJATTU VERSIO
 */
require_once 'db_config.php';

// 1. INPUT VALIDOINTI (KORJATTU: Poistettu deprecated FILTER_SANITIZE_STRING)
$date = $_GET['date'] ?? '';
$treatment_id = filter_input(INPUT_GET, 'treatment_id', FILTER_VALIDATE_INT) ?: 1;

// Perusvalidointi päivämäärälle
if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

try {
    // 2. HAETAAN VALITUN HOIDON KESTO
    $stmt_dur = $pdo->prepare("SELECT duration FROM treatments WHERE id = ?");
    $stmt_dur->execute([$treatment_id]);
    $selected_duration = $stmt_dur->fetchColumn() ?: 60;
    
    /**
     * SQL-LOGIIKKA (SUORAVIIVAISTETTU):
     * - Haetaan slotit, joissa ei ole varausta (huomioiden 30min tauko)
     * - Tarkistetaan, että hoito mahtuu päivän työaikaan.
     */
    
    // Haetaan päivän viimeinen mahdollinen hetki (työajan loppu + 30min joustovara)
    $last_time_stmt = $pdo->prepare("SELECT MAX(available_time) as last_time FROM available_times WHERE available_date = :date");
    $last_time_stmt->execute([':date' => $date]);
    $last_available_time = $last_time_stmt->fetchColumn();

    if (!$last_available_time) {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT v.available_time 
            FROM available_times v
            WHERE v.available_date = :date
            -- Estetään päällekkäisyys varattujen aikojen kanssa (+30min tauko)
            AND NOT EXISTS (
                SELECT 1 
                FROM appointments a
                JOIN treatments t ON a.treatment_id = t.id
                WHERE a.appointment_date = v.available_date
                AND a.status != 'cancelled'
                AND v.available_time >= a.appointment_time
                AND v.available_time < ADDTIME(a.appointment_time, SEC_TO_TIME((t.duration + 30) * 60))
            )
            -- Varmistetaan, että hoito mahtuu päivän loppuun
            -- Lasketaan: slotin aloitusaika + hoidon kesto <= viimeisen slotin alkamisaika + 30min
            AND ADDTIME(v.available_time, SEC_TO_TIME(:duration * 60)) <= ADDTIME(:last_time, '00:30:01')
            ORDER BY v.available_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':date' => $date,
        ':last_time' => $last_available_time,
        ':duration' => $selected_duration
    ]);
    
    $times = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Muotoillaan 10:00:00 -> 10:00
    $formatted_times = array_map(function($time) {
        return substr($time, 0, 5);
    }, $times);

    header('Content-Type: application/json');
    echo json_encode($formatted_times);

} catch (PDOException $e) {
    error_log("Get available times error: " . $e->getMessage());
    header('Content-Type: application/json', true, 500);
    echo json_encode(["error" => "Database error"]);
}