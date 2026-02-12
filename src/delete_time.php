


<?php
// delete_time.php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['admin_logged_in'])) { exit; }

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM available_times WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin_dashboard.php?msg=time_deleted");
    } catch (PDOException $e) {
        die("Virhe poistettaessa: " . $e->getMessage());
    }
}