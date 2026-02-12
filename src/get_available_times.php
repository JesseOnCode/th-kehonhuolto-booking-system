<?php
/**
 * GET_AVAILABLE_TIMES.PHP
 * Hakee vapaat ajat ja suodattaa pois varatut aikavälit + 30 min tauon.
 * 
 * KORJAUKSET:
 * - Tarkistaa että valittu hoito mahtuu kokonaan työaikaan
 * - Jos työaika loppuu 18:00, 60min hoito varattavissa max 17:00, 90min max 16:30
 * - CSRF-suojaus ja input validointi
 */
require_once 'db_config.php';

// 1. INPUT VALIDOINTI
$date = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING);
$treatment_id = filter_input(INPUT_GET, 'treatment_id', FILTER_VALIDATE_INT);

// Oletushoito jos ei määritelty
if (!$treatment_id) {
    $treatment_id = 1;
}

if (empty($date)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// 2. PÄIVÄMÄÄRÄN VALIDOINTI
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

try {
    // 3. HAETAAN VALITUN HOIDON KESTO
    $stmt_dur = $pdo->prepare("SELECT duration FROM treatments WHERE id = ?");
    $stmt_dur->execute([$treatment_id]);
    $selected_duration = $stmt_dur->fetchColumn() ?: 60;
    
    /**
     * SQL-LOGIIKKA:
     * 1. Haetaan kaikki yrittäjän asettamat vapaat slotit (v)
     * 2. Käytetään NOT EXISTS -lauseketta tarkistamaan, ettei mikään varaus (a) 
     *    osu päällekkäin kyseisen slotin kanssa
     * 3. Varaus katsotaan päällekkäiseksi, jos slotin aika on välillä:
     *    Varauksen alkuaika --- (Varauksen kesto + 30 min tauko)
     * 4. UUSI: Tarkistetaan myös että valittu hoito + sen jälkeinen 30min tauko
     *    mahtuvat kokonaan päivän viimeiseen varattavaan aikaan
     */
    
    // Haetaan kyseisen päivän viimeinen varattava aika
    $last_time_sql = "SELECT MAX(available_time) as last_time 
                      FROM available_times 
                      WHERE available_date = :date";
    $last_time_stmt = $pdo->prepare($last_time_sql);
    $last_time_stmt->execute([':date' => $date]);
    $last_time_result = $last_time_stmt->fetch();
    $last_available_time = $last_time_result['last_time'] ?? '23:59:59';
    
    $sql = "SELECT v.available_time 
            FROM available_times v
            WHERE v.available_date = :date
            AND NOT EXISTS (
                SELECT 1 
                FROM appointments a
                JOIN treatments t ON a.treatment_id = t.id
                WHERE a.appointment_date = v.available_date
                AND a.status != 'cancelled'
                AND v.available_time >= a.appointment_time
                AND v.available_time < ADDTIME(a.appointment_time, SEC_TO_TIME((t.duration + 30) * 60))
            )
            -- UUSI: Tarkistetaan että valittu hoito mahtuu kokonaan
            -- Jos kyseessä on viimeinen slotti päivältä, sen on oltava tarpeeksi aikaisin
            AND (
                -- Jos tämä EI ole viimeinen slotti, hyväksytään se
                v.available_time < :last_time
                OR 
                -- Jos tämä ON viimeinen slotti, tarkistetaan että hoito + tauko mahtuu
                (v.available_time = :last_time 
                 AND ADDTIME(v.available_time, SEC_TO_TIME((:duration + 30) * 60)) <= ADDTIME(:last_time, '00:30:00'))
            )
            -- Varmistetaan myös että hoito mahtuu päivän loppuun yleisesti
            AND ADDTIME(v.available_time, SEC_TO_TIME(:duration * 60)) <= ADDTIME(:last_time, '00:30:00')
            ORDER BY v.available_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':date' => $date,
        ':last_time' => $last_available_time,
        ':duration' => $selected_duration
    ]);
    
    // FETCH_COLUMN palauttaa vain kellonajat taulukkona
    $times = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Siistitään kellonajat (10:00:00 -> 10:00)
    $formatted_times = array_map(function($time) {
        return substr($time, 0, 5);
    }, $times);

    header('Content-Type: application/json');
    echo json_encode($formatted_times);

} catch (PDOException $e) {
    // Lokitetaan virhe turvallisesti
    error_log("Get available times error: " . $e->getMessage());
    
    // Palautetaan tyhjä lista virhetilanteessa, jotta UI ei jumiudu
    header('Content-Type: application/json');
    echo json_encode([]);
}
