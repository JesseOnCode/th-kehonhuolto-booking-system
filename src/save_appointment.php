<?php
/**
 * SAVE_APPOINTMENT.PHP - KORJATTU
 */
session_start();
require_once 'db_config.php'; 

$success = false;
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['booking_csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['booking_csrf_token']) {
        $error_message = "Virheellinen pyyntö.";
    }
    elseif (isset($_SESSION['last_booking']) && (time() - $_SESSION['last_booking']) < 5) {
        $error_message = "Odota hetki ennen seuraavaa varausta.";
    }
    else {
        // KORJAUS: Käytetään strip_tags ja trim, koska FILTER_SANITIZE_STRING on vanhentunut
        $first_name   = trim(strip_tags($_POST['first_name'] ?? ''));
        $last_name    = trim(strip_tags($_POST['last_name'] ?? ''));
        $phone        = trim(strip_tags($_POST['phone'] ?? ''));
        $email        = trim(strip_tags($_POST['email'] ?? ''));
        $notes        = trim(strip_tags($_POST['notes'] ?? ''));
        $date         = $_POST['date'] ?? '';
        $time         = $_POST['time'] ?? '';
        $treatment_id = filter_input(INPUT_POST, 'treatment_id', FILTER_VALIDATE_INT);

        // KORJAUS: Sähköpostin validointi, joka sallii ääkköset (ä, ö)
        // Käytetään yksinkertaista tarkistusta: täytyy löytyä @ ja piste, tukee unicodea /u
        $email_valid = preg_match('/^.+@.+\..+$/u', $email);

        if (empty($first_name) || empty($last_name) || empty($date) || empty($time)) {
            $error_message = "Pakollisia tietoja puuttuu.";
        }
        elseif (!$email_valid) {
            $error_message = "Tarkista sähköpostiosoite (sallii ä ja ö).";
        }
        else {
            try {
                $pdo->beginTransaction();

                // Tarkistetaan onko aika vapaana tai jo varattu
                $dup_check = "SELECT id FROM appointments WHERE appointment_date = :date AND appointment_time = :time AND status != 'cancelled'";
                $dup_stmt = $pdo->prepare($dup_check);
                $dup_stmt->execute([':date' => $date, ':time' => $time]);
                
                if ($dup_stmt->fetch()) {
                    throw new Exception("Aika on jo ehditty varata.");
                }

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
                    ':tid'   => $treatment_id ?: 1,
                    ':notes' => $notes
                ]);

                // Poistetaan vastaava vapaa slotti jos sellainen on olemassa
                $stmt_del = $pdo->prepare("DELETE FROM available_times WHERE available_date = ? AND available_time = ?");
                $stmt_del->execute([$date, $time]);

                $pdo->commit();
                $success = true;
                $_SESSION['last_booking'] = time();
                unset($_SESSION['booking_csrf_token']);

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log("Save appointment error: " . $e->getMessage());
                $error_message = $e->getMessage() ?: "Varaus epäonnistui.";
            }
        }
    }
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
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com;">
</head>
<body>

<div class="booking-wrapper" style="justify-content: center; align-items: center; text-align: center; padding: 20px;">
    <div class="calendar-card" style="max-width: 500px; padding: 40px; border: 1px solid var(--border);">
        
        <?php if ($success): ?>
            <div style="font-size: 60px; color: var(--gold); margin-bottom: 20px;">✓</div>
            <h1 style="color: var(--gold); margin-bottom: 20px;">Kiitos varauksestasi!</h1>
            
            <div style="background: rgba(197, 160, 89, 0.05); padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
                <p><strong>Aika:</strong> <?php echo safe_output(date("d.m.Y", strtotime($date))); ?> klo <?php echo safe_output(substr($time, 0, 5)); ?></p>
                <p><strong>Asiakas:</strong> <?php echo safe_output($first_name . " " . $last_name); ?></p>
            </div>
            
            <?php if (!empty($email)): ?>
            <p style="color: var(--muted); font-size: 14px;">
                Vahvistus on lähetetty osoitteeseen:<br>
                <strong><?php echo safe_output($email); ?></strong>
            </p>
            <?php endif; ?>
            
            <a href="booking.php" class="confirm-btn" style="display: inline-block; text-decoration: none; margin-top: 30px;">TAKAISIN VARAUKSIIN</a>
        
        <?php else: ?>
            <h1 style="color: var(--error);">Varaus epäonnistui</h1>
            <p style="color: var(--muted);"><?php echo safe_output($error_message); ?></p>
            <button onclick="history.back()" class="confirm-btn" style="margin-top: 20px;">Yritä uudelleen</button>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
