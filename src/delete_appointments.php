<?php
/**
 * DELETE_APPOINTMENT.PHP
 * Poistaa varauksen tietokannasta.
 */
session_start();
require_once 'db_config.php';

// Vain kirjautunut yrittäjä voi poistaa
if (!isset($_SESSION['admin_logged_in'])) { exit; }

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        
        // Ohjataan takaisin ilmoituksen kera
        header("Location: admin_dashboard.php?msg=appointment_deleted");
    } catch (PDOException $e) {
        die("Virhe poistettaessa varausta: " . $e->getMessage());
    }
}