<?php
/**
 * LOGOUT.PHP
 * Lopettaa yrittäjän istunnon ja tyhjentää kirjautumistiedot.
 */

// 1. Alustetaan istunto, jotta se voidaan lopettaa
session_start();

// 2. Tyhjennetään kaikki istuntomuuttujat
$_SESSION = array();

// 3. Jos käytössä on istuntoevästeitä, tuhotaan ne
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Tuhotaan itse istunto palvelimelta
session_destroy();

// 5. Ohjataan yrittäjä takaisin kirjautumissivulle
header("Location: admin_login.php");
exit;
?>