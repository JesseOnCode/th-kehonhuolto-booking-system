<?php
/**
 * LOGOUT.PHP
 * Turvallinen uloskirjautuminen.
 * 
 * TIETOTURVAPARANNUKSET:
 * - Session destruction
 * - Cookie clearing
 * - Regenerate session ID
 */

// Aloitetaan session
session_start();

// 1. POISTETAAN KAIKKI SESSION-MUUTTUJAT
$_SESSION = array();

// 2. POISTETAAN SESSION-COOKIE
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. TUHOTAAN SESSION KOKONAAN
session_destroy();

// 4. REGENEROIDAAN SESSION ID (estetään session fixation)
session_start();
session_regenerate_id(true);

// 5. OHJATAAN KIRJAUTUMISSIVULLE
header("Location: admin_login.php?msg=logged_out");
exit;
