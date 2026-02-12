<?php
/**
 * CONFIRM_BOOKING.PHP
 * Kerää asiakkaan tiedot ja valmistelee datan tallennusta varten.
 */

session_start();
// Tomi: Aktivoi tämä, kun haluat hakea esim. palvelun hinnan tietokannasta
// require_once 'db_config.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Otetaan tiedot talteen booking.php-sivun piilokentistä
    $selected_date = $_POST['selected_date'] ?? null;
    $selected_time = $_POST['selected_time'] ?? null;
    
    // Jesse: Jos haluatte tukea useita eri hoitoja, ID voidaan välittää tässä
    $treatment_id  = $_POST['treatment_id'] ?? 1; 

    // Jos tiedot puuttuvat, ohjataan takaisin kalenteriin
    if (!$selected_date || !$selected_time) {
        header("Location: booking.php?error=missing_selection");
        exit;
    }

    // Muotoillaan näyttöä varten (esim. 2026-02-12 -> 12.02.2026)
    $formatted_display_date = date("d.m.Y", strtotime($selected_date));
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
    <title>Vahvista varaus | Artisan Massage</title>
    
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
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
                    <strong>45min Klassinen Hieronta</strong>
                </div>
                
                <div class="summary-item">
                    <span>Päivämäärä:</span>
                    <strong><?php echo $formatted_display_date; ?></strong>
                </div>
                
                <div class="summary-item">
                    <span>Kellonaika:</span>
                    <strong>klo <?php echo date("H:i", strtotime($selected_time)); ?></strong>
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
            <p>Täytä vielä yhteystietosi. Varauksen jälkeen järjestelmä huomioi 30 minuutin tauon ennen seuraavaa vapaata aikaa.</p>
        </header>

        <form action="save_appointment.php" method="POST" class="confirmation-form">
            
            <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
            <input type="hidden" name="time" value="<?php echo $selected_time; ?>">
            <input type="hidden" name="treatment_id" value="<?php echo $treatment_id; ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label for="first_name">Etunimi *</label>
                    <input type="text" id="first_name" name="first_name" placeholder="Matti" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Sukunimi *</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Meikäläinen" required>
                </div>

                <div class="form-group">
                    <label for="phone">Puhelinnumero *</label>
                    <input type="tel" id="phone" name="phone" placeholder="040 123 4567" required>
                </div>

                <div class="form-group">
                    <label for="email">Sähköposti *</label>
                    <input type="email" id="email" name="email" placeholder="matti@esimerkki.fi" required>
                </div>

                <div class="form-group full-width">
                    <label for="notes">Terveiset hierojalle (valinnainen)</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Esim. allergiat tai erityistoiveet..."></textarea>
                </div>
            </div>

            <div class="terms-check">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">
                    Hyväksyn varausehdot. Peruutus on tehtävä viimeistään 24h ennen hoidon alkua. 
                    Tämän jälkeen veloitamme 50% hoidon hinnasta.
                </label>
                                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">
                    Hyväksyn, että TH-kehonhuolto kerää ja käsittelee henkilötietojani ajanvarauksen tekemistä varten, sekä mahdollisia yhteydenottoja varten.
                    Olen tietoinen siitä, että tietojani käsitellään tietosuojalain (GDPR) mukaisesti ja että minulla on oikeus tarkastaa, oikaista ja poistaa tietoni.
                </label>
            </div>

            <button type="submit" class="confirm-btn">VAHVISTA VARAUS</button>
        </form>
    </div>
</div>

</body>
</html>