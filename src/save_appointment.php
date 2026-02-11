<?php
/**
 * SAVE_APPOINTMENT.PHP
 * Tämä tiedosto suorittaa varsinaisen tallennuksen tietokantaan.
 * Vastaa Jessen SQL-rakennetta (customer_first_name, customer_last_name).
 */

// 1. TIETOKANTAYHTEYS
// Haetaan yhteysasetukset. require_once varmistaa, ettei yhteyttä avata useasti.
require_once 'db_config.php'; 

$success = false;
$error_message = "";

// 2. TARKISTETAAN POST-METODI
// Varmistetaan, että tiedot tulivat lomakkeen kautta, ei suoralla linkillä.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Kerätään tiedot muuttujiin. 
    // Käytetään ??-operaattoria estämään "undefined index" -virheet.
    $first_name   = $_POST['first_name'] ?? '';
    $last_name    = $_POST['last_name'] ?? '';
    $phone        = $_POST['phone'] ?? '';
    $email        = $_POST['email'] ?? '';
    $notes        = $_POST['notes'] ?? '';
    $date         = $_POST['date'] ?? '';       // Muoto: YYYY-MM-DD
    $time         = $_POST['time'] ?? '';       // Muoto: HH:MM
    $treatment_id = $_POST['treatment_id'] ?? 1;

    // Tarkistetaan vielä kerran, ettei kriittisiä tietoja puutu
    if (empty($first_name) || empty($last_name) || empty($date) || empty($time)) {
        $error_message = "Pakollisia tietoja puuttuu. Palaa takaisin ja täytä kaikki kentät.";
    } else {
        try {
            // 3. SQL-KYSELY
            // Käytetään valmisteltua kyselyä (Prepared Statement) tietoturvan vuoksi.
            $sql = "INSERT INTO appointments (
                        customer_first_name, 
                        customer_last_name, 
                        customer_email, 
                        customer_phone, 
                        appointment_date, 
                        appointment_time, 
                        treatment_id, 
                        notes
                    ) VALUES (
                        :fname, 
                        :lname, 
                        :email, 
                        :phone, 
                        :adate, 
                        :atime, 
                        :tid, 
                        :notes
                    )";
            
            $stmt = $pdo->prepare($sql);
            
            // Suoritetaan tallennus sidotuilla arvoilla
            $stmt->execute([
                ':fname' => $first_name,
                ':lname' => $last_name,
                ':email' => $email,
                ':phone' => $phone,
                ':adate' => $date,
                ':atime' => $time,
                ':tid'   => $treatment_id,
                ':notes' => $notes
            ]);

            $success = true;
        } catch (PDOException $e) {
            // Jos tallennus epäonnistuu (esim. joku ehti varata ajan sekuntia aiemmin)
            $success = false;
            // Kehitysvaiheessa näytetään tarkka virhe, tuotannossa yleisviesti.
            $error_message = "Varaus epäonnistui: Aika on saattanut juuri tulla varatuksi. (" . $e->getMessage() . ")";
        }
    }
} else {
    // Jos sivulle tullaan ilman POST-dataa, ohjataan takaisin kalenteriin
    header("Location: booking.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Varaus vahvistettu | Artisan Massage</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="booking-wrapper" style="justify-content: center; align-items: center; text-align: center; padding: 20px;">
    <div class="calendar-card" style="max-width: 500px; padding: 40px; border: 1px solid var(--border);">
        
        <?php if ($success): ?>
            <div style="font-size: 60px; color: var(--gold); margin-bottom: 20px;">✓</div>
            <h1 style="color: var(--gold); margin-bottom: 20px;">Varauksesi on vahvistettu!</h1>
            
            <div style="background: rgba(197, 160, 89, 0.05); padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
                <p style="margin: 5px 0;"><strong>Päivämäärä:</strong> <?php echo date("d.m.Y", strtotime($date)); ?></p>
                <p style="margin: 5px 0;"><strong>Kellonaika:</strong> klo <?php echo substr($time, 0, 5); ?></p>
                <p style="margin: 5px 0;"><strong>Asiakas:</strong> <?php echo htmlspecialchars($first_name . " " . $last_name); ?></p>
            </div>
            
            <p style="color: var(--muted); font-size: 14px;">
                Lähetimme vahvistusviestin osoitteeseen:<br>
                <strong><?php echo htmlspecialchars($email); ?></strong>
            </p>
            
            <a href="index.php" class="confirm-btn" style="display: inline-block; text-decoration: none; margin-top: 30px;">PALAA ETUSIVULLE</a>
        
        <?php else: ?>
            <h1 style="color: var(--error);">Hups! Jotain meni vikaan.</h1>
            <p style="color: var(--muted);"><?php echo $error_message; ?></p>
            <button onclick="history.back()" class="confirm-btn" style="margin-top: 20px;">Yritä uudelleen</button>
        <?php endif; ?>

    </div>
</div>

</body>
</html>