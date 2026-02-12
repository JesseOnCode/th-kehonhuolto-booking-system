<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}
?>


<?php
/**
 * ADD_TIME.PHP
 * Tallentaa yrittäjän asettaman yksittäisen vapaan aloitusaika-slotin tietokantaan.
 */
session_start();
require_once 'db_config.php';

// 1. TURVATARKISTUS: Vain kirjautunut yrittäjä saa lisätä aikoja.
// Pidetään kutsumattomat vieraat poissa hallintapaneelista.
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: admin_login.php");
    exit; 
}

// 2. LOMAKKEEN KÄSITTELY
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Otetaan vastaan päivä ja kellonaika.
    // Treatment_id on poistettu lomakkeelta, joten käytetään oletusta 1.
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $default_t_id = 1; 

    // Tarkistetaan, etteivät kentät ole tyhjiä (perusvalidointi).
    if (empty($date) || empty($time)) {
        header("Location: admin_dashboard.php?error=empty_fields");
        exit;
    }

    try {
        // 3. SQL-TALLENNUS
        // Käytetään INSERT IGNORE -komentoa. 
        // Jos olet jo lisännyt tämän tarkan ajan aiemmin, koodi ei "pahoita mieltään" ja kaadu, 
        // vaan jättää duplikaatin huomiotta.
        $sql = "INSERT IGNORE INTO available_times (treatment_id, available_date, available_time) 
                VALUES (:t_id, :date, :time)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':t_id' => $default_t_id, 
            ':date' => $date, 
            ':time' => $time
        ]);
        
        // Palataan takaisin dashboardille onnistumisviestin kera.
        header("Location: admin_dashboard.php?success=time_added");
        exit;
        
    } catch (PDOException $e) {
        // Jos jokin menee todella pahasti pieleen (esim. tietokantayhteys katkeaa).
        die("Hups! Tietokantavirhe: " . $e->getMessage());
    }
} else {
    // Jos joku yrittää avata tiedoston suoraan selaimessa ilman lomaketta.
    header("Location: admin_dashboard.php");
    exit;
}
?>