<?php
/**
 * ADD_TIME.PHP
 * Tallentaa yrittäjän asettaman vapaan ajan tietokantaan.
 */
session_start();
require_once 'db_config.php';

// 1. TURVATARKISTUS: Vain kirjautunut yrittäjä voi lisätä aikoja
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: admin_login.php");
    exit; 
}

// 2. LOMAKKEEN KÄSITTELY
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Huom: Nimet 'date', 'time' ja 'treatment_id' tulevat dashboardin lomakkeesta
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $t_id = $_POST['treatment_id'] ?? '';

    // Tarkistetaan, että kentät eivät ole tyhjiä
    if (empty($date) || empty($time) || empty($t_id)) {
        header("Location: admin_dashboard.php?error=empty_fields");
        exit;
    }

    try {
        // SQL: Lisätään uusi rivi vapaiden aikojen tauluun
        $sql = "INSERT INTO available_times (treatment_id, available_date, available_time) 
                VALUES (:t_id, :date, :time)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':t_id' => $t_id, 
            ':date' => $date, 
            ':time' => $time
        ]);
        
        // Onnistumisen jälkeen ohjaus takaisin
        header("Location: admin_dashboard.php?success=time_added");
        exit;
        
    } catch (PDOException $e) {
        // Jos aika on jo olemassa tai tulee muu virhe
        die("Tietokantavirhe: " . $e->getMessage());
    }
} else {
    // Jos sivulle yritetään tulla ilman lomaketta
    header("Location: admin_dashboard.php");
    exit;
}
?>