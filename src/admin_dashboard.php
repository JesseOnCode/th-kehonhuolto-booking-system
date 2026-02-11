<?php
/**
 * ADMIN_DASHBOARD.PHP
 * Yrittäjän hallintapaneeli: Työaikojen generointi ja varausten hallinta.
 */
session_start();
require_once 'db_config.php';

// 1. TURVATARKISTUS: Vain kirjautuneille
if (!isset($_SESSION['admin_logged_in'])) { 
    header("Location: admin_login.php"); 
    exit; 
}

// 2. DATAN HAKU
try {
    // Haetaan varsinaiset varaukset (Asiakkaat)
    $stmt = $pdo->query("SELECT a.*, t.name as treatment_name 
                         FROM appointments a 
                         LEFT JOIN treatments t ON a.treatment_id = t.id 
                         ORDER BY a.appointment_date ASC, a.appointment_time ASC");
    $appointments = $stmt->fetchAll();

    // Haetaan asetetut vapaat ajat, joissa EI OLE varausta (hallintaa varten)
    $stmt_free = $pdo->query("SELECT v.* FROM available_times v
                              WHERE NOT EXISTS (
                                  SELECT 1 FROM appointments a 
                                  WHERE a.appointment_date = v.available_date 
                                  AND a.appointment_time = v.available_time
                                  AND a.status != 'cancelled'
                              )
                              ORDER BY v.available_date ASC, v.available_time ASC");
    $free_times = $stmt_free->fetchAll();

} catch (PDOException $e) {
    die("Tietokantavirhe: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hallintapaneeli | Artisan Massage</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="booking-wrapper">
    <div class="booking-sidebar">
        <div class="profile-logo"><img src="logo.jpg" alt="Logo"></div>
        <h3 style="text-align: center;">Hallinta</h3>
        <nav style="margin-top: 20px;">
            <a href="#tyoajat" style="color: var(--gold); text-decoration: none; display: block; padding: 10px 0;">⏰ Lisää työaikoja</a>
            <a href="#vapaat_lista" style="color: var(--text-light); text-decoration: none; display: block; padding: 10px 0;">🗓️ Vapaat slotit</a>
            <a href="#varaukset" style="color: var(--text-light); text-decoration: none; display: block; padding: 10px 0;">📅 Varauskirja</a>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">
            <a href="logout.php" class="back-btn" style="text-decoration: none; font-size: 14px; display: block;">KIRJAUDU ULOS</a>
        </nav>
    </div>

    <div class="booking-main">
        
        <section id="tyoajat" style="margin-bottom: 50px;">
            <header class="main-header">
                <h1>Työaikojen hallinta</h1>
                <p>Merkitse ajat, jolloin otat vastaan varauksia. Asiakas valitsee hoidon keston varatessaan.</p>
            </header>

            <div class="selection-grid">
                <div class="calendar-card" style="padding: 25px; border: 1px solid var(--border);">
                    <h3 style="color: var(--gold); margin-top: 0;">Generoi työvuoro</h3>
                    <form action="add_time_range.php" method="POST">
                        <div class="form-group">
                            <label>Päivämäärä</label>
                            <input type="date" name="date" required>
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
                        <input type="hidden" name="treatment_id" value="1">
                        <button type="submit" class="confirm-btn">LUO AJAT (30min välein)</button>
                    </form>
                </div>

                <div class="calendar-card" style="padding: 25px; border: 1px solid var(--border);">
                    <h3 style="color: var(--gold); margin-top: 0;">Lisää yksittäinen aika</h3>
                    <form action="add_time.php" method="POST">
                        <div class="form-group">
                            <label>Päivämäärä</label>
                            <input type="date" name="date" required>
                        </div>
                        <div class="form-group">
                            <label>Kellonaika</label>
                            <input type="time" name="time" required>
                        </div>
                        <input type="hidden" name="treatment_id" value="1">
                        <button type="submit" class="confirm-btn" style="background: transparent; border: 1px solid var(--gold); color: var(--gold);">LISÄÄ SLOTTI</button>
                    </form>
                </div>
            </div>
        </section>

        <section id="vapaat_lista" style="margin-bottom: 50px;">
            <h3>Kalenterissa olevat vapaat slotit</h3>
            <div class="calendar-card" style="padding: 20px; border: 1px solid var(--border);">
                <?php if (empty($free_times)): ?>
                    <p style="color: var(--muted);">Ei asetettuja vapaita aikoja.</p>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;">
                        <?php foreach ($free_times as $ft): ?>
                            <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 4px; border: 1px solid var(--border); position: relative;">
                                <small style="color: var(--gold);"><?php echo date("d.m.Y", strtotime($ft['available_date'])); ?></small><br>
                                <strong>klo <?php echo date("H:i", strtotime($ft['available_time'])); ?></strong>
                                <a href="delete_time.php?id=<?php echo $ft['id']; ?>" 
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
                <h1>Tulevat varaukset</h1>
            </header>

            <div class="calendar-card" style="padding: 0; overflow-x: auto; border: 1px solid var(--border);">
                <table class="calendar-table" style="text-align: left; width: 100%; border-collapse: collapse;">
                    <thead style="background: rgba(197, 160, 89, 0.1);">
                        <tr>
                            <th style="padding: 20px; font-size: 12px; color: var(--gold);">AIKA</th>
                            <th style="font-size: 12px; color: var(--gold);">ASIAKAS</th>
                            <th style="font-size: 12px; color: var(--gold);">PALVELU</th>
                            <th style="font-size: 12px; color: var(--gold);">YHTEYSTIEDOT</th>
                            <th style="font-size: 12px; color: var(--gold);">HUOMIOT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr><td colspan="5" style="padding: 40px; text-align: center; color: var(--muted);">Ei tehtyjä varauksia.</td></tr>
                        <?php else: ?>
                            <?php foreach ($appointments as $app): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 20px;">
                                    <span style="color: var(--gold); display: block;"><?php echo date("d.m.Y", strtotime($app['appointment_date'])); ?></span>
                                    <span style="font-size: 18px;"><?php echo date("H:i", strtotime($app['appointment_time'])); ?></span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($app['customer_first_name'] . ' ' . $app['customer_last_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($app['treatment_name']); ?></td>
                                <td style="font-size: 13px; color: var(--muted);">
                                    📧 <?php echo htmlspecialchars($app['customer_email']); ?><br>
                                    📞 <?php echo htmlspecialchars($app['customer_phone']); ?>
                                </td>
                                <td style="font-size: 12px; font-style: italic; color: var(--muted);">
                                    <?php echo !empty($app['notes']) ? htmlspecialchars($app['notes']) : '-'; ?>
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