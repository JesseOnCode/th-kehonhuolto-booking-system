<?php
/**
 * ADD_TIME.PHP - Päivitetty: Lisää suoraan varauksen
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { 
    header("Location: admin_login.php");
    exit; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: admin_dashboard.php?error=invalid_token");
        exit;
    }
    
    $customer_name = trim(strip_tags($_POST['customer_name'] ?? 'Admin-varaus'));
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $treatment_id = 1; // Oletushoito

    if (empty($date) || empty($time)) {
        header("Location: admin_dashboard.php?error=empty_fields");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Lisätään suoraan varauksiin
        $sql = "INSERT INTO appointments (customer_first_name, customer_last_name, appointment_date, appointment_time, treatment_id, status, notes) 
                VALUES (:fname, 'Ylläpitäjä', :date, :time, :t_id, 'booked', 'Lisätty manuaalisesti hallinnasta')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':fname' => $customer_name, 
            ':date' => $date, 
            ':time' => $time . ':00',
            ':t_id' => $treatment_id
        ]);

        // 2. Poistetaan mahdollinen vapaa slotti samalta ajalta, ettei tule tuplia
        $stmt_del = $pdo->prepare("DELETE FROM available_times WHERE available_date = ? AND available_time = ?");
        $stmt_del->execute([$date, $time . ':00']);

        $pdo->commit();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: admin_dashboard.php?success=time_added");
        exit;
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Manual booking error: " . $e->getMessage());
        header("Location: admin_dashboard.php?error=database_error");
        exit;
    }
}