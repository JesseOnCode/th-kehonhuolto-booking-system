<?php
session_start(); 

// Tuodaan tietokantayhteys ja asetukset db_config.php-tiedostosta
require_once 'db_config.php'; 

// tarkistetaan, onko ylläpitäjä kirjautunut sisään; jos ei, ohjataan kirjautumissivulle
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { 
// lähetetään uudelleenohjausotsikko selaimelle    
header("Location: admin_login.php"); 
    exit; 
}

// Tarkistetaan onko sivu avattu lomakkeen lähetyksen (POST) kautta
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF-suojaus: Tarkistetaan, että lomakkeen token täsmää istunnon tokeniin
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        //ohjataan takaisin virheilmoituksen kanssa
        header("Location: admin_dashboard.php?error=invalid_token"); 
        exit;
    }
    
    // puhdistetaan asiakkaan nimi poistamalla HTML-tägit ja turhat välilyönnit
    $customer_name = trim(strip_tags($_POST['customer_name'] ?? 'Admin-varaus'));
    // Haetaan päivämäärä lomakkeesta
    $date = $_POST['date'] ?? '';
    // Haetaan kellonaika lomakkeesta
    $time = $_POST['time'] ?? '';
    // Määritetään oletuspalvelun ID (esim. Klassinen hieronta)
    $treatment_id = 1; 

    // Tarkistetaan, etteivät päivämäärä tai aika ole tyhjiä
    if (empty($date) || empty($time)) {
        
        // Ohjataan takaisin, jos kenttiä puuttuu
        header("Location: admin_dashboard.php?error=empty_fields"); 
        exit;
    }

    try {
        // Aloitetaan tietokantatransaktio (tarkistetaan, että koko ketju toimii.)
        $pdo->beginTransaction();

        // Luodaan SQL-kysely varauksen lisäämiseksi suoraan appointments-tauluun
        $sql = "INSERT INTO appointments (customer_first_name, customer_last_name, appointment_date, appointment_time, treatment_id, status, notes) 
                VALUES (:fname, 'Ylläpitäjä', :date, :time, :t_id, 'booked', 'Lisätty manuaalisesti hallinnasta')";
        
        // Valmistellaan SQL-kysely tietoturvallisesti (Prepared Statement)
        $stmt = $pdo->prepare($sql);
        // Suoritetaan kysely ja sidotaan muuttujat parametreihin
        $stmt->execute([
            ':fname' => $customer_name,
            ':date' => $date,
            ':time' => $time . ':00', 
            ':t_id' => $treatment_id
        ]);

        // Luodaan kysely, joka poistaa mahdollisen vapaan slotin samalta ajalta
        $stmt_del = $pdo->prepare("DELETE FROM available_times WHERE available_date = ? AND available_time = ?");
        // suoritetaan poisto, jolla varmistetaan, että sama aika ei jää vapaaksi kalenteriin.
        $stmt_del->execute([$date, $time . ':00']);

        // Vahvistetaan kaikki transaktion aikana tehdyt muutokset tietokantaan
        $pdo->commit();
        // Luodaan uusi CSRF-token seuraavaa pyyntöä varten turvallisuuden parantamiseksi
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        // Ohjataan ylläpitäjä takaisin dashboardille onnistumisviestin kera
        header("Location: admin_dashboard.php?success=time_added");
        exit;
        
        // Napataan mahdolliset tietokantavirheet
    } catch (PDOException $e) { 
        // Jos virhe tapahtui transaktion aikana, perutaan kaikki siihen kuuluvat muutokset
        if ($pdo->inTransaction()) $pdo->rollBack();
        // Kirjataan virhe palvelimen lokiin tutkintaa varten
        error_log("Manual booking error: " . $e->getMessage());
        // Ohjataan takaisin dashboardille tietokantavirheen ilmoituksella
        header("Location: admin_dashboard.php?error=database_error");
        exit;
    }
}