<?php
/**
 * DB_CONFIG.PHP
 * Tietokantayhteyden määritykset.
 * 
 * TIETOTURVAPARANNUKSET:
 * - PDO prepared statements
 * - Error mode exception
 * - Emulate prepares disabled
 * - Secure error handling
 * - Character set UTF-8
 */

// 1. TIETOKANTA-ASETUKSET
// TUOTANTOON: Siirrä nämä .env-tiedostoon tai konfiguraatiotiedostoon palvelimen ulkopuolelle
$host    = 'localhost';
$db      = 'hieronta_varaus';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

// 2. DSN-MÄÄRITYS
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// 3. PDO-ASETUKSET (OWASP BEST PRACTICES)
$options = [
    // Heittää poikkeukset virhetilanteissa (turvallinen error handling)
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Palauttaa tiedot assosiaatioina (ei objekteina)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // KRIITTINEN: Estää SQL-injektiot tehokkaammin
    // Käyttää "true" prepared statements serverin puolella
    PDO::ATTR_EMULATE_PREPARES   => false,
    
    // Aseta yhteys persistent-tilaan (nopeuttaa kyselyitä)
    // HUOM: Poista kommentti vain jos tarvitset korkean suorituskyvyn
    // PDO::ATTR_PERSISTENT         => true,
    
    // Aseta timeout yhteyksille (estää DoS-hyökkäyksiä)
    PDO::ATTR_TIMEOUT            => 5,
];

try {
    // 4. LUODAAN TIETOKANTAYHTEYS
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // 5. ASETETAAN SQL_MODE STRICT (parempi data-validointi)
    $pdo->exec("SET sql_mode='STRICT_ALL_TABLES'");
    
} catch (\PDOException $e) {
    // 6. TURVALLINEN VIRHEIDEN KÄSITTELY
    
    // Lokitetaan virhe palvelimen lokitiedostoon (ei näytetä käyttäjälle)
    error_log("Database connection error: " . $e->getMessage());
    
    // TUOTANTOTILA: Näytetään yleinen virheviesti (ei paljasta tietokanta-asetuksia)
    // KEHITYSTILA: Voit näyttää tarkan virheen kommentoimalla alla olevan rivin pois
    die("Pahoittelut, tietokantaan ei saatu yhteyttä juuri nyt. Yritä myöhemmin uudelleen.");
    
    // KEHITYSTILASSA voit käyttää tätä:
    // die("Database Error: " . $e->getMessage());
}

// 7. HELPER-FUNKTIOT

/**
 * Turvallinen tietokantatiedon tulostus (XSS-suojaus)
 */
function db_escape_output($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Varmista että ID on validi integer
 */
function validate_id($id) {
    return filter_var($id, FILTER_VALIDATE_INT) !== false && $id > 0;
}
