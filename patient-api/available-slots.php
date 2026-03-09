<?php
/**
 * API - Available Appointment Slots
 * Provides available slots for doctor on specific date
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Appointment.php';

header('Content-Type: application/json');

try {
    $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : '';
    
    if (!$doctorId || !$date) {
        throw new Exception('Doctor ID and date are required');
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format');
    }
    
    // Don't allow past dates
    if (strtotime($date) < strtotime('today')) {
        throw new Exception('Date must be in the future');
    }
    
    $appointment = new Appointment();
    $slots = $appointment->getAvailableSlots($doctorId, $date, 30);
    
    // Format slots response
    $response = array_map(function($slot) {
        return [
            'slot_id' => $slot['slot_id'],
            'slot_time' => substr($slot['slot_time'], 0, 5)
        ];
    }, $slots);
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
