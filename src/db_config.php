<?php
/**
 * DB_CONFIG.PHP
 * Tietokantayhteyden määritykset XAMPP-ympäristöön.
 */

// 1. Tietokannan parametrit
$host = 'localhost';
$db   = 'hieronta_varaus'; // SQL-dumpin mukainen nimi
$user = 'root';           // XAMPP oletuskäyttäjä
$pass = '';               // XAMPP oletussalasana on tyhjä
$charset = 'utf8mb4';     // SQL-dumpin mukainen merkistö

// 2. DSN (Data Source Name) määritys
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// 3. PDO-asetukset (virheen käsittely ja hakumuoto)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Heittää poikkeuksen virhetilanteessa
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Palauttaa tulokset assosiatiivisena taulukkona
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Käyttää aitoja valmisteltuja kyselyitä tietoturvan vuoksi
];

try {
    // 4. Luodaan yhteys
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Jos yhteys epäonnistuu, tulostetaan virhe (kehitysvaiheessa)
    // Tuotannossa tämä kannattaa muuttaa lokitukseksi
    die("Tietokantayhteys epäonnistui: " . $e->getMessage());
}

?>