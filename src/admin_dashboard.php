
<?php
/**
 * ADMIN_DASHBOARD.PHP
 * Hallintapaneeli yrittäjälle.
 * 
 * TIETOTURVAPARANNUKSET:
 * - CSRF token generointija validointi
 * - Session timeout tarkistus
 * - XSS-suojaus output escapingilla
 * - Korjattu delete_appointment linkki
 */

session_start();
require_once 'db_config.php';

// 1. TURVATARKISTUS: Vain kirjautuneille yrittäjille
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { 
    header("Location: admin_login.php"); 
    exit; 
}

// 2. SESSION TIMEOUT TARKISTUS (30 minuuttia)
$timeout_duration = 1800; // 30 minuuttia
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php?error=session_timeout");
    exit;
}
$_SESSION['last_activity'] = time();

// 3. CSRF TOKEN GENEROINTI
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 4. DATAN HAKU
try {
    // Haetaan varsinaiset asiakasvaraukset
    $stmt = $pdo->query("SELECT a.*, t.name as treatment_name, t.duration 
                         FROM appointments a 
                         LEFT JOIN treatments t ON a.treatment_id = t.id 
                         ORDER BY a.appointment_date ASC, a.appointment_time ASC");
    $appointments = $stmt->fetchAll();

    // Haetaan vapaat slotit
    $stmt_free = $pdo->query("SELECT v.* FROM available_times v
                              ORDER BY v.available_date ASC, v.available_time ASC");
    $free_times = $stmt_free->fetchAll();

} catch (PDOException $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    die("Tietokantavirhe. Ota yhteyttä ylläpitoon.");
}

// 5. XSS-SUOJAUS FUNKTIO
function safe_output($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hallintapaneeli | Artisan Massage</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Content Security Policy -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline';">
</head>
<body>

<div class="booking-wrapper">
    <div class="booking-sidebar">
        <div class="profile-logo"><img src="logo.jpg" alt="Logo"></div>
        <h3 style="text-align: center;">Hallinta</h3>
        <nav style="margin-top: 20px;">
            <a href="#tyoajat" style="color: var(--gold); text-decoration: none; display: block; padding: 10px 0;">⏰ Työaikojen hallinta</a>
            <a href="#vapaat_lista" style="color: var(--text-light); text-decoration: none; display: block; padding: 10px 0;">🗓️ Vapaat slotit</a>
            <a href="#varaukset" style="color: var(--text-light); text-decoration: none; display: block; padding: 10px 0;">📅 Varauskirja</a>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">
            <a href="logout.php" class="back-btn" style="text-decoration: none; font-size: 14px; display: block;">KIRJAUDU ULOS</a>
        </nav>
    </div>

    <div class="booking-main">
        
        <!-- SUCCESS/ERROR VIESTIT -->
        <?php if (isset($_GET['success'])): ?>
            <div style="background: rgba(46, 204, 113, 0.1); color: #2ecc71; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <?php 
                if ($_GET['success'] === 'range_added') echo "✓ Työajat lisätty onnistuneesti";
                if ($_GET['success'] === 'time_added') echo "✓ Aika lisätty onnistuneesti";
                if ($_GET['success'] === 'appointment_deleted') echo "✓ Varaus poistettu";
                if ($_GET['success'] === 'time_deleted') echo "✓ Vapaa aika poistettu";
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div style="background: rgba(231, 76, 60, 0.1); color: #e74c3c; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <?php 
                if ($_GET['error'] === 'invalid_token') echo "⚠ Virheellinen turvakoodi. Yritä uudelleen.";
                if ($_GET['error'] === 'missing_values') echo "⚠ Täytä kaikki kentät";
                if ($_GET['error'] === 'database_error') echo "⚠ Tietokantavirhe. Yritä myöhemmin uudelleen.";
                ?>
            </div>
        <?php endif; ?>
        
        <section id="tyoajat" style="margin-bottom: 50px;">
            <header class="main-header">
                <h1>Työaikojen hallinta</h1>
                <p>Määritä vapaat aikasi. Kun asiakas tekee varauksen, kyseiset slotit poistuvat automaattisesti listalta.</p>
            </header>

            <div class="selection-grid">
                <div class="calendar-card" style="padding: 25px; border: 1px solid var(--border);">
                    <h3 style="color: var(--gold); margin-top: 0;">Generoi työvuoro</h3>
                    <form action="add_time_range.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo safe_output($_SESSION['csrf_token']); ?>">
                        <div class="form-group">
                            <label>Päivämäärä</label>
                            <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <div class="form-group" style="flex: 1;">
                                <label>Alkaa</label>
                                <input type="time" name="start_time" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Loppuu</label>
                                <input type="time" name="end_time" required>
                            </div>
                        </div>
                        <button type="submit" class="confirm-btn">LUO AJAT (30min välein)</button>
                        <p style="font-size: 12px; color: var(--muted); margin-top: 10px;">
                            💡 Huom: Viimeinen varattava aika riippuu hoidon kestosta. 
                            Jos työaika päättyy 18:00, 60min hoito varattavissa max 17:00, 90min max 16:30.
                        </p>
                    </form>
                </div>

                <div class="calendar-card" style="padding: 25px; border: 1px solid var(--border);">
                    <h3 style="color: var(--gold); margin-top: 0;">Lisää varaus suoraan</h3>
                    <form action="add_time.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo safe_output($_SESSION['csrf_token']); ?>">
                        <div class="form-group">
                            <label>Asiakkaan nimi (esim. Yrityksen oma varaus)</label>
                            <input type="text" name="customer_name" placeholder="Matti Meikäläinen" required>
                        </div>
                        <div class="form-group">
                            <label>Päivämäärä</label>
                            <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Kellonaika</label>
                            <input type="time" name="time" required>
                        </div>
                        <button type="submit" class="confirm-btn" style="background: var(--gold); color: black;">LISÄÄ VARAUSKIRJAAN</button>
                    </form>
                </div>
        </section>

        <section id="vapaat_lista" style="margin-bottom: 50px;">
            <h3>Kalenterissa olevat vapaat slotit</h3>
            <div class="calendar-card" style="padding: 20px; border: 1px solid var(--border);">
                <?php if (empty($free_times)): ?>
                    <p style="color: var(--muted);">Ei asetettuja vapaita aikoja.</p>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">
                        <?php foreach ($free_times as $ft): ?>
                            <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 4px; border: 1px solid var(--border); position: relative;">
                                <small style="color: var(--gold);"><?php echo safe_output(date("d.m.Y", strtotime($ft['available_date']))); ?></small><br>
                                <strong>klo <?php echo safe_output(date("H:i", strtotime($ft['available_time']))); ?></strong>
                                <a href="delete_time.php?id=<?php echo safe_output($ft['id']); ?>&csrf_token=<?php echo safe_output($_SESSION['csrf_token']); ?>" 
                                   onclick="return confirm('Poistetaanko tämä aika varattavista?')"
                                   style="position: absolute; right: 10px; top: 10px; color: var(--error); text-decoration: none; font-weight: bold;">×</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="varaukset">
            <header class="main-header">
                <h1>Varauskirja</h1>
            </header>

            <div class="calendar-card" style="padding: 0; overflow-x: auto; border: 1px solid var(--border);">
                <table class="calendar-table" style="text-align: left; width: 100%; border-collapse: collapse;">
                    <thead style="background: rgba(197, 160, 89, 0.1);">
                        <tr>
                            <th style="padding: 20px; font-size: 12px; color: var(--gold);">AIKA</th>
                            <th style="font-size: 12px; color: var(--gold);">ASIAKAS</th>
                            <th style="font-size: 12px; color: var(--gold);">PALVELU</th>
                            <th style="font-size: 12px; color: var(--gold);">YHTEYSTIEDOT</th>
                            <th style="font-size: 12px; color: var(--gold);">HALLINTA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr><td colspan="5" style="padding: 40px; text-align: center; color: var(--muted);">Ei tehtyjä varauksia.</td></tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $app): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 20px;">
                                    <span style="color: var(--gold); display: block;"><?php echo safe_output(date("d.m.Y", strtotime($app['appointment_date']))); ?></span>
                                    <span style="font-size: 18px;"><?php echo safe_output(date("H:i", strtotime($app['appointment_time']))); ?></span>
                                </td>
                                <td><strong><?php echo safe_output($app['customer_first_name'] . ' ' . $app['customer_last_name']); ?></strong></td>
                                <td><?php echo safe_output($app['treatment_name']); ?> (<?php echo safe_output($app['duration']); ?>min)</td>
                                <td style="font-size: 13px; color: var(--muted);">
                                    <?php echo safe_output($app['customer_email']); ?><br>
                                    <?php echo safe_output($app['customer_phone']); ?>
                                </td>
                                <td>
                                    <a href="delete_appointment.php?id=<?php echo safe_output($app['id']); ?>&csrf_token=<?php echo safe_output($_SESSION['csrf_token']); ?>" 
                                       onclick="return confirm('Haluatko varmasti peruuttaa tämän varauksen?')" 
                                       style="color: var(--error); text-decoration: none; font-size: 11px; border: 1px solid var(--error); padding: 5px 10px; border-radius: 4px; font-weight: 600;">
                                       PERUUTA
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

</body>
</html>
