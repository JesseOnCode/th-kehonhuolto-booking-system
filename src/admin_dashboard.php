<?php
session_start();
require_once 'db_config.php';

// Turvatarkistus: Jos ei ole kirjautunut, heitetään pois
if (!isset($_SESSION['admin_logged_in'])) { header("Location: admin_login.php"); exit; }

// Haetaan varaukset ja liitetään mukaan hoidon nimi (Tomi & Jesse yhteistyö)
try {
    $stmt = $pdo->query("SELECT a.*, t.name as treatment_name 
                         FROM appointments a 
                         LEFT JOIN treatments t ON a.treatment_id = t.id 
                         ORDER BY a.appointment_date ASC");
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Virhe haettaessa varauksia: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Hallintapaneeli | Artisan Massage</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="booking-wrapper">
    <div class="booking-sidebar">
        <div class="profile-logo"><img src="logo.jpg" alt="Logo"></div>
        <h3 style="text-align: center;">Hallinta</h3>
        <nav style="margin-top: 20px;">
            <p style="color: var(--gold); border-bottom: 1px solid var(--border); padding-bottom: 10px;">📅 Varaukset</p>
            <a href="logout.php" style="color: var(--muted); text-decoration: none; font-size: 14px;">Kirjaudu ulos</a>
        </nav>
    </div>

    <div class="booking-main">
        <header class="main-header">
            <h1>Tulevat varaukset</h1>
            <p>Tässä ovat kaikki asiakkaiden tekemät ajanvaraukset aikajärjestyksessä.</p>
        </header>

        <div class="calendar-card" style="padding: 0; overflow: hidden;">
            <table class="calendar-table" style="text-align: left;">
                <thead style="background: rgba(197, 160, 89, 0.1);">
                    <tr>
                        <th style="padding: 20px;">AIKA</th>
                        <th>ASIAKAS</th>
                        <th>PALVELU</th>
                        <th>YHTEYSTIEDOT</th>
                        <th>HUOMIOT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $app): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 20px; color: var(--gold); font-weight: bold;">
                            <?php echo date("d.m.Y H:i", strtotime($app['appointment_date'])); ?>
                        </td>
                        <td><?php echo htmlspecialchars($app['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($app['treatment_name'] ?? 'Hieronta'); ?></td>
                        <td style="font-size: 13px; color: var(--muted);">
                            <?php echo htmlspecialchars($app['customer_email']); ?><br>
                            <?php echo htmlspecialchars($app['customer_phone']); ?>
                        </td>
                        <td style="font-size: 12px; font-style: italic;"><?php echo htmlspecialchars($app['notes']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>