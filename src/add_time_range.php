<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}
?>


<?php
/**
 * ADD_TIME_RANGE.PHP
 * Generoi vapaat aloitusaika-vaihtoehdot 30 minuutin välein yrittäjän valitsemalle välille.
 */
session_start();
require_once 'db_config.php';

// 1. TURVATARKISTUS
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: admin_login.php");
    exit; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Otetaan vastaan päivä, alku- ja loppuaika
    $date = $_POST['date'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    
    // Käytetään 30 minuuttia "atomisena" yksikkönä. 
    // Yrittäjä vain ilmoittaa olevansa töissä, ei sitä mitä hoitoa tekee.
    $step_minutes = 30;

    if (empty($date) || empty($start) || empty($end)) {
        header("Location: admin_dashboard.php?error=missing_values");
        exit;
    }

    try {
        $current = strtotime("$date $start");
        $finish  = strtotime("$date $end");

        // Aloitetaan transaktio (varmistaa, että joko kaikki ajat tallentuvat tai ei mitään)
        $pdo->beginTransaction();

        // SQL: Käytetään INSERT IGNOREa, jotta ei tule virhettä, jos aika on jo lisätty
        // Käytetään treatment_id = 1 oletuksena, koska kanta vaatii sen (ei vaikuta asiakkaan valintaan)
        $sql = "INSERT IGNORE INTO available_times (treatment_id, available_date, available_time) 
                VALUES (1, :date, :time)";
        $stmt = $pdo->prepare($sql);

        // Generoidaan ajat silmukassa
        while ($current < $finish) {
            $stmt->execute([
                ':date' => $date,
                ':time' => date("H:i:s", $current)
            ]);
            
            // Siirrytään 30 minuuttia eteenpäin
            $current += ($step_minutes * 60);
        }

        $pdo->commit();
        header("Location: admin_dashboard.php?success=range_added");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Virhe aikoja luodessa: " . $e->getMessage());
    }
} else {
    header("Location: admin_dashboard.php");
    exit;
}
?>