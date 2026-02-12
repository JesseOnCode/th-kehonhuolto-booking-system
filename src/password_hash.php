<?php
/**
 * HASH_TOOL.PHP
 * Työkalu turvallisten BCRYPT-salasanojen luomiseen.
 */

$hashed_password = "";
$plain_password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $plain_password = $_POST['password'] ?? '';
    if (!empty($plain_password)) {
        // Luodaan turvallinen BCRYPT-hash
        $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);
    }
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <title>Salasana-generaattori | Artisan Massage</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #0f0f0f; color: white; font-family: sans-serif; display: flex; justify-content: center; padding-top: 50px; }
        .tool-card { background: #1a1a1a; padding: 30px; border-radius: 8px; border: 1px solid #333; width: 100%; max-width: 600px; }
        .result-box { background: #222; border: 1px dashed var(--gold, #c5a059); padding: 15px; margin-top: 20px; word-break: break-all; color: #c5a059; font-family: monospace; }
        .warning { color: #ff4d4d; font-size: 12px; margin-top: 20px; border-top: 1px solid #333; padding-top: 10px; }
    </style>
</head>
<body>

<div class="tool-card">
    <h2 style="color: #c5a059;">Salasana-generaattori</h2>
    <p>Kirjoita uusi salasana, niin koodi muuttaa sen turvalliseksi hash-merkkijonoksi.</p>

    <form method="POST">
        <div class="form-group" style="margin-bottom: 15px;">
            <label>Uusi salasana selväkielisenä:</label>
            <input type="text" name="password" value="<?php echo htmlspecialchars($plain_password); ?>" required style="width: 100%; padding: 10px; background: #222; border: 1px solid #444; color: white;">
        </div>
        <button type="submit" class="confirm-btn" style="width: 100%;">GENEROI HASH</button>
    </form>

    <?php if ($hashed_password): ?>
        <div class="result">
            <p style="margin-top: 20px; font-weight: bold;">Kopioi tämä tietokantaan (password-sarake):</p>
            <div class="result-box">
                <?php echo $hashed_password; ?>
            </div>
            <p style="font-size: 12px; color: #888; margin-top: 10px;">
                Tämä hash vastaa salasanaa: <strong><?php echo htmlspecialchars($plain_password); ?></strong>
            </div>
        </div>
    <?php endif; ?>


</div>

</body>
</html>