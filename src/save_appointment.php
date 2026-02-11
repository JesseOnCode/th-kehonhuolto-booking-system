<?php
/**
 * SAVE_APPOINTMENTS.PHP
 * Tämä tiedosto tallentaa varauksen tietokantaan.
 */
require_once 'db_config.php'; 

$success = false;
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Lomaketiedot
    $name    = $_POST['name'] ?? '';
    $phone   = $_POST['phone'] ?? '';
    $email   = $_POST['email'] ?? '';
    $notes   = $_POST['notes'] ?? '';
    $date    = $_POST['date'] ?? ''; // YYYY-MM-DD
    $time    = $_POST['time'] ?? ''; // HH:MM
    $treatment_id = $_POST['treatment_id'] ?? 1;

    try {
        // SQL-lause, joka vastaa uutta korjattua tietokantarakennetta
        $sql = "INSERT INTO appointments (customer_name, customer_email, customer_phone, appointment_date, appointment_time, treatment_id, notes) 
                VALUES (:name, :email, :phone, :date, :time, :treatment_id, :notes)";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':name'           => $name,
            ':email'          => $email,
            ':phone'          => $phone,
            ':date'           => $date,
            ':time'           => $time,
            ':treatment_id'   => $treatment_id,
            ':notes'          => $notes
        ]);

        $success = true;
    } catch (PDOException $e) {
        $success = false;
        $error_message = "Tietokantavirhe: " . $e->getMessage();
    }
} else {
    header("Location: booking.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Varaus vahvistettu | Artisan Massage</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="booking-wrapper" style="justify-content: center; align-items: center; text-align: center;">
    <div class="calendar-card" style="max-width: 500px; padding: 40px;">
        <?php if ($success): ?>
            <div style="font-size: 60px; color: #c5a059; margin-bottom: 20px;">✓</div>
            <h1 style="color: var(--gold); margin-bottom: 20px;">Kiitos varauksestasi!</h1>
            <p>Varauksesi ajalle <strong><?php echo date("d.m.Y", strtotime($date)); ?> klo <?php echo $time; ?></strong> on nyt tallennettu järjestelmäämme.</p>
            <p style="color: #888; margin-top: 10px;">Vahvistus on lähetetty osoitteeseen: <?php echo htmlspecialchars($email); ?></p>
            <a href="index.php" class="confirm-btn" style="display: inline-block; text-decoration: none; margin-top: 30px;">PALAA ETUSIVULLE</a>
        <?php else: ?>
            <h1 style="color: #e74c3c;">Hups! Tallennus epäonnistui.</h1>
            <p><?php echo $error_message; ?></p>
            <button onclick="history.back()" class="confirm-btn" style="margin-top: 20px;">Yritä uudelleen</button>
        <?php endif; ?>
    </div>
</div>
</body>
</html>