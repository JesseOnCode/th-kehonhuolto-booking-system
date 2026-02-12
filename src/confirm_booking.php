<?php
/**
 * CONFIRM_BOOKING.PHP
 * Kerää asiakkaan tiedot ja valmistelee datan tallennusta varten.
 * 
 * TIETOTURVAPARANNUKSET:
 * - CSRF token generointi
 * - Input validointi
 * - XSS-suojaus
 */

// Secure headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. INPUT VALIDOINTI
    $selected_date = filter_input(INPUT_POST, 'selected_date', FILTER_SANITIZE_STRING);
    $selected_time = filter_input(INPUT_POST, 'selected_time', FILTER_SANITIZE_STRING);
    $treatment_id  = filter_input(INPUT_POST, 'treatment_id', FILTER_VALIDATE_INT);
    
    if (!$treatment_id) {
        $treatment_id = 1;
    }

    // Jos tiedot puuttuvat, ohjataan takaisin
    if (empty($selected_date) || empty($selected_time)) {
        header("Location: booking.php?error=missing_selection");
        exit;
    }
    
    // 2. PÄIVÄMÄÄRÄN JA AJAN VALIDOINTI
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date) || 
        !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $selected_time)) {
        header("Location: booking.php?error=invalid_format");
        exit;
    }
    
    // 3. GENEEROIDAAN CSRF TOKEN LOMAKKEELLE
    $_SESSION['booking_csrf_token'] = bin2hex(random_bytes(32));

    // Muotoillaan näyttöä varten
    $formatted_display_date = date("d.m.Y", strtotime($selected_date));
    
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
    <title>Vahvista varaus | Artisan Massage</title>
    
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com;">
</head>
<body>

<div class="booking-wrapper">
    
    <div class="booking-sidebar">
        <button type="button" class="back-btn" onclick="history.back()">← MUOKKAA AIKAA</button>
        
        <div class="profile-logo">
            <img src="logo.jpg" alt="Artisan Massage Logo">
        </div>

        <div class="service-details">
            <div class="summary-card">
                <h3>Varauksesi tiedot</h3>
                
                <div class="summary-item">
                    <span>Palvelu:</span>
                    <strong>Klassinen Hieronta</strong>
                </div>
                
                <div class="summary-item">
                    <span>Päivämäärä:</span>
                    <strong><?php echo safe_output($formatted_display_date); ?></strong>
                </div>
                
                <div class="summary-item">
                    <span>Kellonaika:</span>
                    <strong>klo <?php echo safe_output(date("H:i", strtotime($selected_time))); ?></strong>
                </div>
            </div>
            
            <div class="total-price">
                <small>MAKSETAAN PAIKAN PÄÄLLÄ</small>
                <h2>55,00 €</h2>
            </div>
        </div>
    </div>

    <div class="booking-main">
        <header class="main-header">
            <h1>Viimeistele varaus</h1>
            <p>Täytä vielä yhteystietosi. Varauksen jälkeen järjestelmä huomioi 30 minuutin tauon ennen seuraavaa vapaata aikaa.</p>
        </header>

        <form action="save_appointment.php" method="POST" class="confirmation-form">
            
            <!-- CSRF TOKEN -->
            <input type="hidden" name="csrf_token" value="<?php echo safe_output($_SESSION['booking_csrf_token']); ?>">
            
            <input type="hidden" name="date" value="<?php echo safe_output($selected_date); ?>">
            <input type="hidden" name="time" value="<?php echo safe_output($selected_time); ?>">
            <input type="hidden" name="treatment_id" value="<?php echo safe_output($treatment_id); ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label for="first_name">Etunimi *</label>
                    <input type="text" 
                           id="first_name" 
                           name="first_name" 
                           placeholder="Matti" 
                           required 
                           minlength="2" 
                           maxlength="50"
                           pattern="[A-Za-zÀ-ÿ\s\-']+"
                           title="Vain kirjaimet, välilyönnit ja väliviivat">
                </div>

                <div class="form-group">
                    <label for="last_name">Sukunimi *</label>
                    <input type="text" 
                           id="last_name" 
                           name="last_name" 
                           placeholder="Meikäläinen" 
                           required 
                           minlength="2" 
                           maxlength="50"
                           pattern="[A-Za-zÀ-ÿ\s\-']+"
                           title="Vain kirjaimet, välilyönnit ja väliviivat">
                </div>

                <div class="form-group">
                    <label for="phone">Puhelinnumero *</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           placeholder="040 123 4567" 
                           required
                           maxlength="20"
                           pattern="[0-9\s\-\+\(\)]+"
                           title="Vain numerot, välilyönnit ja erikoismerkit">
                </div>

                <div class="form-group">
                    <label for="email">Sähköposti *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="matti@esimerkki.fi" 
                           required
                           maxlength="100">
                </div>

                <div class="form-group full-width">
                    <label for="notes">Terveiset hierojalle (valinnainen)</label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="3" 
                              placeholder="Esim. allergiat tai erityistoiveet..."
                              maxlength="500"></textarea>
                </div>
            </div>

            <div class="terms-check">
                <input type="checkbox" id="terms1" name="terms" required>
                <label for="terms1">
                    Hyväksyn varausehdot. Peruutus on tehtävä viimeistään 24h ennen hoidon alkua. 
                    Tämän jälkeen veloitamme 50% hoidon hinnasta. *
                </label>
            </div>
            
            <div class="terms-check">
                <input type="checkbox" id="terms2" name="privacy" required>
                <label for="terms2">
                    Hyväksyn, että TH-kehonhuolto kerää ja käsittelee henkilötietojani ajanvarauksen tekemistä varten, 
                    sekä mahdollisia yhteydenottoja varten. Olen tietoinen siitä, että tietojani käsitellään 
                    tietosuojalain (GDPR) mukaisesti ja että minulla on oikeus tarkastaa, oikaista ja poistaa tietoni. *
                </label>
            </div>

            <button type="submit" class="confirm-btn">VAHVISTA VARAUS</button>
        </form>
    </div>
</div>

</body>
</html>
