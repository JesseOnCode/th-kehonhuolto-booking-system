<?php
/**
 * ADD_TIME_RANGE.PHP
 * Generoi vapaat ajat huomioiden 30 minuutin tauon jokaisen hoidon välissä.
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['admin_logged_in'])) { exit; }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $t_id = $_POST['treatment_id'];
    
    // Määritetään tauon pituus (30 minuuttia)
    $buffer_minutes = 30;

    try {
        // Haetaan valitun hoidon kesto tietokannasta
        $stmt_dur = $pdo->prepare("SELECT duration FROM treatments WHERE id = ?");
        $stmt_dur->execute([$t_id]);
        $duration = $stmt_dur->fetchColumn();

        if (!$duration) { $duration = 60; } // Oletus jos ei löydy

        $current = strtotime("$date $start");
        $finish  = strtotime("$date $end");

        $pdo->beginTransaction();

        // INSERT IGNORE estää duplikaatit, jos aika on jo olemassa
        $sql = "INSERT IGNORE INTO available_times (treatment_id, available_date, available_time) 
                VALUES (:t_id, :date, :time)";
        $stmt = $pdo->prepare($sql);

        // Silmukka generoi aikoja niin kauan kuin HOITO + TAUKO mahtuu aikaväliin
        while ($current + ($duration * 60) <= $finish) {
            
            $stmt->execute([
                ':t_id' => $t_id,
                ':date' => $date,
                ':time' => date("H:i:s", $current)
            ]);
            
            // LASKENTALOGIIKKA:
            // Seuraava aloitusaika = Nykyinen aloitusaika + hoidon kesto + 30 min tauko
            $step = ($duration + $buffer_minutes) * 60;
            $current += $step;
        }

        $pdo->commit();
        header("Location: admin_dashboard.php?success=range_added_with_buffer");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Virhe generoitaessa aikoja: " . $e->getMessage());
    }
}