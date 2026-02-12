<?php
/**
 * SAVE_APPOINTMENT.PHP
 * Tallentaa varauksen ja poistaa vastaavat vapaat slotit (kesto + 30min tauko).
 * 
 * TIETOTURVAPARANNUKSET:
 * - CSRF token validointi
 * - Input validointi ja sanitointi
 * - XSS-suojaus
 * - SQL injection prevention
 * - Rate limiting
 */

// Secure headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

session_start();
require_once 'db_config.php'; 

$success = false;
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. CSRF-SUOJAUS
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['booking_csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['booking_csrf_token']) {
        $error_message = "Virheellinen pyyntö. Palaa takaisin ja yritä uudelleen.";
    }
    // 2. RATE LIMITING - Estetään roskapostitus
    elseif (isset($_SESSION['last_booking']) && (time() - $_SESSION['last_booking']) < 10) {
        $error_message = "Odota hetki ennen seuraavaa varausta.";
    }
    else {
        // 3. INPUT VALIDOINTI JA SANITOINTI
        $first_name   = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
        $last_name    = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
        $phone        = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $email        = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $notes        = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
        $date         = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
        $time         = filter_input(INPUT_POST, 'time', FILTER_SANITIZE_STRING);
        $treatment_id = filter_input(INPUT_POST, 'treatment_id', FILTER_VALIDATE_INT);
        
        // Trim white spaces
        $first_name = trim($first_name);
        $last_name = trim($last_name);
        $phone = trim($phone);
        $email = trim($email);
        $notes = trim($notes);

        // 4. VALIDOINTI
        if (empty($first_name) || empty($last_name) || empty($date) || empty($time)) {
            $error_message = "Pakollisia tietoja puuttuu.";
        }
        // Nimen pituus validointi
        elseif (strlen($first_name) < 2 || strlen($first_name) > 50 || 
                strlen($last_name) < 2 || strlen($last_name) > 50) {
            $error_message = "Nimi on liian lyhyt tai pitkä.";
        }
        // Email validointi
        elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Virheellinen sähköpostiosoite.";
        }
        // Päivämäärän validointi
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $error_message = "Virheellinen päivämäärä.";
        }
        // Ajan validointi
        elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
            $error_message = "Virheellinen kellonaika.";
        }
        // Treatment ID validointi
        elseif (!$treatment_id || $treatment_id < 1) {
            $error_message = "Virheellinen palvelu.";
        }
        // Tarkistetaan että päivämäärä on tulevaisuudessa
        elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
            $error_message = "Varaus täytyy tehdä tulevaisuuteen.";
        }
        else {
            try {
                // 5. TARKISTETAAN ETTÄ VALITTU AIKA ON TODELLA VAPAA
                $check_sql = "SELECT id FROM available_times 
                              WHERE available_date = :date 
                              AND available_time = :time 
                              LIMIT 1";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([':date' => $date, ':time' => $time]);
                
                if (!$check_stmt->fetch()) {
                    $error_message = "Valittu aika ei ole enää saatavilla. Valitse toinen aika.";
                }
                else {
                    // 6. TARKISTETAAN ETTÄ EI OLE DUPLIKAATTIVARAUSTA
                    $dup_check = "SELECT id FROM appointments 
                                  WHERE appointment_date = :date 
                                  AND appointment_time = :time 
                                  AND status != 'cancelled'";
                    $dup_stmt = $pdo->prepare($dup_check);
                    $dup_stmt->execute([':date' => $date, ':time' => $time]);
                    
                    if ($dup_stmt->fetch()) {
                        $error_message = "Tämä aika on jo varattu. Valitse toinen aika.";
                    }
                    else {
                        // 7. ALOITETAAN TRANSAKTIO
                        $pdo->beginTransaction();

                        // 8. TALLENNETAAN VARAUS
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

                        // 9. HAETAAN HOIDON KESTO
                        $stmt_dur = $pdo->prepare("SELECT duration FROM treatments WHERE id = ?");
                        $stmt_dur->execute([$treatment_id]);
                        $duration = $stmt_dur->fetchColumn() ?: 60;

                        // 10. POISTETAAN VARATUT SLOTIT
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

                        // 11. VAHVISTETAAN MUUTOKSET
                        $pdo->commit();
                        $success = true;
                        
                        // 12. ASETETAAN RATE LIMITING TIMESTAMP
                        $_SESSION['last_booking'] = time();
                        
                        // 13. POISTETAAN KÄYTETTY CSRF TOKEN
                        unset($_SESSION['booking_csrf_token']);
                    }
                }

            } catch (PDOException $e) {
                // Jos jokin meni vikaan, perutaan kaikki muutokset
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                // Lokitetaan virhe turvallisesti
                error_log("Save appointment error: " . $e->getMessage());
                $success = false;
                $error_message = "Varaus epäonnistui. Yritä myöhemmin uudelleen.";
            }
        }
    }
} else {
    header("Location: booking.php");
    exit;
}

// XSS-suojaus funktio
function safe_output($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
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
