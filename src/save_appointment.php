<?php
/**
 * SAVE_APPOINTMENT.PHP
 * Tämä tiedosto suorittaa varsinaisen tallennuksen tietokantaan.
 */

// 1. Tietokantayhteyden haku (Tomi & Jesse)
require_once 'db_config.php'; 

$success = false;
$error_message = "";

// 2. Varmistetaan, että tiedot tulivat POST-metodilla
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Kerätään tiedot muuttujiin
    $name    = $_POST['name'] ?? '';
    $phone   = $_POST['phone'] ?? '';
    $email   = $_POST['email'] ?? '';
    $notes   = $_POST['notes'] ?? '';
    $date    = $_POST['date'] ?? '';
    $time    = $_POST['time'] ?? '';
    $treatment_id = $_POST['treatment_id'] ?? 1; // Oletuksena 1 (45min hieronta)

    // Yhdistetään päivä ja aika MySQL:n DATETIME-muotoon (YYYY-MM-DD HH:MM:SS)
    $appointment_datetime = $date . ' ' . $time . ':00';

    try {
        // 3. SQL-kysely (Jesse: Tämän on vastattava appointments-taulun sarakkeita)
        $sql = "INSERT INTO appointments (customer_name, customer_email, customer_phone, appointment_date, treatment_id, notes) 
                VALUES (:name, :email, :phone, :appointment_date, :treatment_id, :notes)";
        
        $stmt = $pdo->prepare($sql);
        
        // Sidotaan arvot (estää SQL-injektiot)
        $stmt->execute([
            ':name'             => $name,
            ':email'            => $email,
            ':phone'            => $phone,
            ':appointment_date' => $appointment_datetime,
            ':treatment_id'     => $treatment_id,
            ':notes'            => $notes
        ]);

        $success = true;
    } catch (PDOException $e) {
        // Jos tallennus epäonnistuu (esim. tietokantavirhe)
        $success = false;
        $error_message = "Tietokantavirhe: " . $e->getMessage();
    }
} else {
    // Jos sivulle yritetään tulla ilman lomaketta
    header("Location: booking.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Varaus vahvistettu | Artisan Massage</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="booking-wrapper" style="justify-content: center; align-items: center; text-align: center;">
    <div class="booking-main" style="max-width: 600px; background: var(--sidebar-bg); border-radius: 12px; border: 1px solid var(--border);">
        
        <?php if ($success): ?>
            <div class="profile-logo" style="border-color: #2ecc71;">
                <span style="color: #2ecc71; font-size: 50px;">✓</span>
            </div>
            <h1 style="color: var(--gold);">Kiitos varauksestasi!</h1>
            <p>Varauksesi ajalle <strong><?php echo date("d.m.Y klo H:i", strtotime($appointment_datetime)); ?></strong> on tallennettu.</p>
            <p style="color: var(--muted);">Lähetimme vahvistuksen osoitteeseen <?php echo htmlspecialchars($email); ?>.</p>
            
            <a href="index.php" class="confirm-btn" style="display: inline-block; text-decoration: none; margin-top: 20px;">PALAA ETUSIVULLE</a>
        
        <?php else: ?>
            <h1 style="color: var(--error);">Hups! Jotain meni vikaan.</h1>
            <p><?php echo $error_message; ?></p>
            <button onclick="history.back()" class="confirm-btn">Yritä uudelleen</button>
        <?php endif; ?>

    </div>
</div>

</body>
</html>