<?php
/**
 * GET_AVAILABLE_TIMES.PHP
 * Hakee vapaat ajat ja suodattaa pois varatut aikavälit + 30 min tauon.
 */
require_once 'db_config.php';

$date = $_GET['date'] ?? '';

if (empty($date)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

try {
    /**
     * SQL-LOGIIKKA (UUSI):
     * 1. Haetaan kaikki yrittäjän asettamat vapaat slotit (v).
     * 2. Käytetään NOT EXISTS -lauseketta tarkistamaan, ettei mikään varaus (a) 
     * osu päällekkäin kyseisen slotin kanssa.
     * 3. Varaus katsotaan päällekkäiseksi, jos slotin aika on välillä:
     * Varauksen alkuaika --- (Varauksen kesto + 30 min tauko)
     */
    $sql = "SELECT v.available_time 
            FROM available_times v
            WHERE v.available_date = :date
            AND NOT EXISTS (
                SELECT 1 
                FROM appointments a
                JOIN treatments t ON a.treatment_id = t.id
                WHERE a.appointment_date = v.available_date
                AND a.status != 'cancelled'
                -- Slotti on varattu, jos se on suurempi/yhtäsuuri kuin varauksen alkuaika...
                AND v.available_time >= a.appointment_time
                -- ...mutta pienempi kuin (alkuaika + kesto + 30 min tauko)
                AND v.available_time < ADDTIME(a.appointment_time, SEC_TO_TIME((t.duration + 30) * 60))
            )
            ORDER BY v.available_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':date' => $date]);
    
    // FETCH_COLUMN palauttaa vain kellonajat taulukkona
    $times = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Siistitään kellonajat (10:00:00 -> 10:00)
    $formatted_times = array_map(function($time) {
        return substr($time, 0, 5);
    }, $times);

    header('Content-Type: application/json');
    echo json_encode($formatted_times);

} catch (PDOException $e) {
    // Palautetaan tyhjä lista virhetilanteessa, jotta UI ei jumiudu
    header('Content-Type: application/json');
    echo json_encode([]);
}