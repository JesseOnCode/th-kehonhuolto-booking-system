<?php
/**
 * CONFIRM_BOOKING.PHP
 * Tämä sivu kerää asiakkaan yhteystiedot ja valmistelee varauksen tallennuksen.
 */

// 1. BACKEND (Tomi): Alusta istunto ja sisällytä tietokantayhteys
session_start();
// include('db_config.php'); 

// 2. BACKEND (Tomi): Tarkistetaan, että tiedot on lähetetty booking.php-sivulta
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Otetaan tiedot talteen piilokentistä
    $selected_date = $_POST['selected_date'] ?? null;
    $selected_time = $_POST['selected_time'] ?? null;
    
    // Jos tiedot puuttuvat, ohjataan käyttäjä takaisin alkuun
    if (!$selected_date || !$selected_time) {
        header("Location: booking.php?error=missing_selection");
        exit;
    }

    // 3. BACKEND: Muotoillaan päivämäärä siistiksi
    // strtotime muuttaa merkkijonon aikaleimaksi, date muotoilee sen
    $formatted_display_date = date("d.m.Y", strtotime($selected_date));
} else {
    // Jos sivulle yritetään tulla suoralla URL-osoitteella, palautetaan varauksen alkuun
    header("Location: booking.php");
    exit;
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
</head>
<body>

<div class="booking-wrapper">
    
    <div class="booking-sidebar">
        <button type="button" class="back-btn" onclick="history.back()">← MUOKKAA AIKAA</button>
        
        <div class="profile-logo">
            <img src="logo.png" alt="Artisan Massage Logo">
        </div>

        <div class="service-details">
            <div class="summary-card">
                <h3>Varauksesi tiedot</h3>
                
                <div class="summary-item">
                    <span>Palvelu:</span>
                    <strong>45min Klassinen Hieronta</strong>
                </div>
                
                <div class="summary-item">
                    <span>Päivämäärä:</span>
                    <strong><?php echo $formatted_display_date; ?></strong>
                </div>
                
                <div class="summary-item">
                    <span>Kellonaika:</span>
                    <strong>klo <?php echo $selected_time; ?></strong>
                </div>
            </div>
            
            <div class="total-price">
                <small>MAKSETAAN PAIKAN PÄÄLLÄ</small>
                <h2>45,00 €</h2>
            </div>
        </div>
    </div>

    <div class="booking-main">
        <header class="main-header">
            <h1>Viimeistele varaus</h1>
            <p>Täytä vielä yhteystietosi. Lähetämme varausvahvistuksen ilmoittamaasi sähköpostiin.</p>
        </header>

        <form action="save_appointment.php" method="POST" class="confirmation-form">
            
            <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
            <input type="hidden" name="time" value="<?php echo $selected_time; ?>">
            <input type="hidden" name="treatment_id" value="1"> <div class="form-grid">
                <div class="form-group">
                    <label for="name">Koko nimi *</label>
                    <input type="text" id="name" name="name" placeholder="Matti Meikäläinen" required>
                </div>

                <div class="form-group">
                    <label for="phone">Puhelinnumero *</label>
                    <input type="tel" id="phone" name="phone" placeholder="040 123 4567" required>
                </div>

                <div class="form-group full-width">
                    <label for="email">Sähköposti *</label>
                    <input type="email" id="email" name="email" placeholder="matti.meikalainen@esimerkki.fi" required>
                </div>

                <div class="form-group full-width">
                    <label for="notes">Terveiset hierojalle (valinnainen)</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Kirjoita tähän, jos sinulla on toiveita tai esimerkiksi allergioita..."></textarea>
                </div>
            </div>

            <div class="terms-check">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">
                    Hyväksyn varausehdot. Ymmärrän, että peruutus on tehtävä viimeistään 24 tuntia ennen hoidon alkua. Tämän jälkeen veloitus 50% hinnasta.
                </label>
            </div>

            <button type="submit" class="confirm-btn">VAHVISTA VARAUS</button>
        </form>
    </div>
</div>

</body>
</html>