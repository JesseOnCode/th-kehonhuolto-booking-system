<?php
/**
 * DB_CONFIG.PHP
 * Tietokantayhteyden määritykset.
 */

// 1. Asetukset (XAMPP oletukset)
$host    = 'localhost';
$db      = 'hieronta_varaus';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

// 2. DSN-määritys
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// 3. PDO-asetukset
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Heittää poikkeukset (try-catch ottaa kiinni)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Palauttaa tiedot selkeinä taulukkoina
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Estää SQL-injektiot tehokkaammin
];

try {
    // Luodaan yhteys PDO-oliolla
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Jos yhteys epäonnistuu, pysäytetään suoritus ja näytetään siisti virhe
    error_log($e->getMessage()); // Kirjaa virheen palvelimen lokiin
    die("Pahoittelut, tietokantaan ei saatu yhteyttä juuri nyt.");
}