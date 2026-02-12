<?php
/**
 * SAVE_APPOINTMENT.PHP
 * Tallentaa varauksen ja poistaa vastaavat vapaat slotit (kesto + 30min tauko).
 */
require_once 'db_config.php'; 

$success = false;
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. KERÄTÄÄN TIEDOT
    $first_name   = $_POST['first_name'] ?? '';
    $last_name    = $_POST['last_name'] ?? '';
    $phone        = $_POST['phone'] ?? '';
    $email        = $_POST['email'] ?? '';
    $notes        = $_POST['notes'] ?? '';
    $date         = $_POST['date'] ?? ''; 
    $time         = $_POST['time'] ?? ''; 
    $treatment_id = $_POST['treatment_id'] ?? 1;

    if (empty($first_name) || empty($last_name) || empty($date) || empty($time)) {
        $error_message = "Pakollisia tietoja puuttuu.";
    } else {
        try {
            // ALOITETAAN TRANSAKTIO (Kaikki tai ei mitään)
            $pdo->beginTransaction();

            // 2. TALLENNETAAN VARAUS APPOINTMENTS-TAULUUN
            $sql_app = "INSERT INTO appointments (
                            customer_first_name, customer_last_name, customer_email, 
                            customer_phone, appointment_date, appointment_time, 
                            treatment_id, notes
                        ) VALUES (:fname, :lname, :email, :phone, :adate, :atime, :tid, :notes)";
            
            $stmt_app = $pdo->prepare($sql_app);
            $stmt_app->execute([
                ':fname' => $first_name,
                ':lname' => $last_name,
                ':email' => $email,
                ':phone' => $phone,
                ':adate' => $date,
                ':atime' => $time,
                ':tid'   => $treatment_id,
                ':notes' => $notes
            ]);

            // 3. HAETAAN HOIDON KESTO
            $stmt_dur = $pdo->prepare("SELECT duration FROM treatments WHERE id = ?");
            $stmt_dur->execute([$treatment_id]);
            $duration = $stmt_dur->fetchColumn() ?: 60; // Oletus 60min jos ei löydy

            // 4. AUTOMAATTINEN SIIVOUS (Poistetaan slotit vapaista ajoista)
            // Lasketaan varattu aika: hoidon kesto + 30 min tauko
            $total_busy_minutes = $duration + 30;

            $sql_del = "DELETE FROM available_times 
                        WHERE available_date = :date 
                        AND available_time >= :start 
                        AND available_time < ADDTIME(:start, SEC_TO_TIME(:busy_sec))";
            
            $stmt_del = $pdo->prepare($sql_del);
            $stmt_del->execute([
                ':date'     => $date,
                ':start'    => $time,
                ':busy_sec' => $total_busy_minutes * 60
            ]);

            // VAHVISTETAAN MUUTOKSET
            $pdo->commit();
            $success = true;

        } catch (PDOException $e) {
            // Jos jokin meni vikaan, perutaan kaikki muutokset
            $pdo->rollBack();
            $success = false;
            $error_message = "Varaus epäonnistui: " . $e->getMessage();
        }
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
            <h1 style="color: var(--gold); margin-bottom: 20px;">Kiitos varauksestasi!</h1>
            
            <div style="background: rgba(197, 160, 89, 0.05); padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
                <p><strong>Aika:</strong> <?php echo date("d.m.Y", strtotime($date)); ?> klo <?php echo substr($time, 0, 5); ?></p>
                <p><strong>Asiakas:</strong> <?php echo htmlspecialchars($first_name . " " . $last_name); ?></p>
            </div>
            
            <p style="color: var(--muted); font-size: 14px;">
                Vahvistus on lähetetty osoitteeseen:<br>
                <strong><?php echo htmlspecialchars($email); ?></strong>
            </p>
            
            <a href="index.php" class="confirm-btn" style="display: inline-block; text-decoration: none; margin-top: 30px;">PALAA ETUSIVULLE</a>
        
        <?php else: ?>
            <h1 style="color: var(--error);">Varaus epäonnistui</h1>
            <p style="color: var(--muted);"><?php echo $error_message; ?></p>
            <button onclick="history.back()" class="confirm-btn" style="margin-top: 20px;">Yritä uudelleen</button>
        <?php endif; ?>

    </div>
</div>

</body>
</html>