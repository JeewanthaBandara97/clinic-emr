<?php
/**
 * Appointment Class
 * Handles appointment booking and management
 */

require_once __DIR__ . '/Database.php';

class Appointment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate appointment code
     */
    public function generateAppointmentCode(): string {
        $sql = "SELECT MAX(appointment_id) as max_id FROM appointments";
        $result = $this->db->fetchOne($sql);
        $nextId = ($result['max_id'] ?? 0) + 1;
        return 'APT' . date('Ymd') . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create appointment
     */
    public function create(array $data): int {
        $sql = "INSERT INTO appointments (
                    appointment_code, patient_id, doctor_id, appointment_date,
                    appointment_time, duration_minutes, reason_for_visit,
                    appointment_type, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->insert($sql, [
            $this->generateAppointmentCode(),
            $data['patient_id'],
            $data['doctor_id'],
            $data['appointment_date'],
            $data['appointment_time'],
            $data['duration_minutes'] ?? 30,
            $data['reason_for_visit'] ?? null,
            $data['appointment_type'] ?? 'Routine Checkup',
            $data['status'] ?? 'Scheduled',
            $data['notes'] ?? null
        ]);
    }
    
    /**
     * Get appointment by ID
     */
    public function getById(int $appointmentId): ?array {
        $sql = "SELECT a.*, 
                    p.full_name as patient_name, p.phone as patient_phone,
                    u.full_name as doctor_name, u.email as doctor_email
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                JOIN users u ON a.doctor_id = u.user_id
                WHERE a.appointment_id = ?";
        
        return $this->db->fetchOne($sql, [$appointmentId]);
    }
    
    /**
     * Get patient's appointments
     */
    public function getPatientAppointments(int $patientId, string $status = null): array {
        $sql = "SELECT a.*, u.full_name as doctor_name
                FROM appointments a
                JOIN users u ON a.doctor_id = u.user_id
                WHERE a.patient_id = ?";
        
        $params = [$patientId];
        
        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get doctor's appointments
     */
    public function getDoctorAppointments(int $doctorId, string $fromDate = null, string $toDate = null): array {
        $fromDate = $fromDate ?? date('Y-m-d');
        $toDate = $toDate ?? date('Y-m-d', strtotime('+30 days'));
        
        $sql = "SELECT a.*, p.full_name as patient_name, p.phone as patient_phone, p.patient_code
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                WHERE a.doctor_id = ?
                AND DATE(a.appointment_date) BETWEEN ? AND ?
                AND a.status IN ('Scheduled', 'Confirmed', 'In Progress')
                ORDER BY a.appointment_date ASC, a.appointment_time ASC";
        
        return $this->db->fetchAll($sql, [$doctorId, $fromDate, $toDate]);
    }
    
    /**
     * Check doctor's available slots
     */
    public function getAvailableSlots(int $doctorId, string $date, int $durationMinutes = 30): array {
        $sql = "SELECT s.*, 
                    IF(s.is_available IS NULL OR s.is_available = 1, 'Available', 'Booked') as slot_status
                FROM appointment_slots s
                WHERE s.doctor_id = ?
                AND s.slot_date = ?
                AND s.duration_minutes = ?
                AND s.is_available = 1
                ORDER BY s.slot_time ASC";
        
        return $this->db->fetchAll($sql, [$doctorId, $date, $durationMinutes]);
    }
    
    /**
     * Generate slots for doctor (for a date range)
     */
    public function generateSlots(int $doctorId, string $fromDate, string $toDate,
                                   string $startTime = '09:00', string $endTime = '17:00',
                                   int $slotDuration = 30): int {
        try {
            $this->db->beginTransaction();
            
            $currentDate = strtotime($fromDate);
            $endDateTs = strtotime($toDate);
            $slotCount = 0;
            
            // Get doctor details
            $doctorDetails = $this->db->fetchOne(
                "SELECT available_days, available_time_start, available_time_end FROM doctor_details WHERE user_id = ?",
                [$doctorId]
            );
            
            if (!$doctorDetails) {
                throw new Exception('Doctor not found');
            }
            
            $availableDays = explode(',', $doctorDetails['available_days']);
            $availableStart = $doctorDetails['available_time_start'] ?? $startTime;
            $availableEnd = $doctorDetails['available_time_end'] ?? $endTime;
            
            while ($currentDate <= $endDateTs) {
                $date = date('Y-m-d', $currentDate);
                $dayName = date('D', $currentDate);
                
                // Check if doctor is available on this day
                if (in_array(substr($dayName, 0, 3), $availableDays)) {
                    
                    // Clear existing slots for this date
                    $this->db->delete(
                        "DELETE FROM appointment_slots WHERE doctor_id = ? AND slot_date = ? AND is_available = 1",
                        [$doctorId, $date]
                    );
                    
                    // Generate slots
                    $slotTime = strtotime($availableStart);
                    $endSlotTime = strtotime($availableEnd);
                    
                    while ($slotTime < $endSlotTime) {
                        $time = date('H:i', $slotTime);
                        
                        $slotSql = "INSERT INTO appointment_slots (doctor_id, slot_date, slot_time, duration_minutes, is_available)
                                   VALUES (?, ?, ?, ?, 1)";
                        
                        $this->db->insert($slotSql, [
                            $doctorId, $date, $time, $slotDuration
                        ]);
                        
                        $slotCount++;
                        $slotTime += ($slotDuration * 60);
                    }
                }
                
                $currentDate += 86400; // Add 1 day
            }
            
            $this->db->commit();
            return $slotCount;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Book appointment
     */
    public function bookAppointment(int $patientId, int $doctorId, string $date, 
                                     string $time, int $slotId = null): int {
        try {
            $this->db->beginTransaction();
            
            // Check if slot exists and is available
            if ($slotId) {
                $slot = $this->db->fetchOne(
                    "SELECT * FROM appointment_slots WHERE slot_id = ? AND is_available = 1",
                    [$slotId]
                );
                
                if (!$slot) {
                    throw new Exception('Slot not available');
                }
            }
            
            // Create appointment
            $appointmentId = $this->create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'appointment_date' => $date,
                'appointment_time' => $time,
                'status' => 'Confirmed'
            ]);
            
            // Mark slot as booked
            if ($slotId) {
                $this->db->update(
                    "UPDATE appointment_slots SET is_available = 0, appointment_id = ? WHERE slot_id = ?",
                    [$appointmentId, $slotId]
                );
            }
            
            $this->db->commit();
            return $appointmentId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Update appointment status
     */
    public function updateStatus(int $appointmentId, string $status): bool {
        $sql = "UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ?";
        return $this->db->update($sql, [$status, $appointmentId]) > 0;
    }
    
    /**
     * Reschedule appointment
     */
    public function reschedule(int $appointmentId, string $newDate, string $newTime): bool {
        try {
            $this->db->beginTransaction();
            
            // Get old appointment slot
            $oldAppointment = $this->getById($appointmentId);
            
            // Free up old slot
            if ($oldAppointment) {
                $this->db->update(
                    "UPDATE appointment_slots SET is_available = 1, appointment_id = NULL 
                     WHERE doctor_id = ? AND appointment_id = ?",
                    [$oldAppointment['doctor_id'], $appointmentId]
                );
            }
            
            // Update appointment
            $updateSql = "UPDATE appointments 
                         SET appointment_date = ?, appointment_time = ?, status = 'Rescheduled', updated_at = NOW()
                         WHERE appointment_id = ?";
            
            $this->db->update($updateSql, [$newDate, $newTime, $appointmentId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Cancel appointment
     */
    public function cancel(int $appointmentId, string $reason = ''): bool {
        try {
            $this->db->beginTransaction();
            
            // Free up slot
            $this->db->update(
                "UPDATE appointment_slots SET is_available = 1, appointment_id = NULL 
                 WHERE appointment_id = ?",
                [$appointmentId]
            );
            
            // Update appointment
            $updateSql = "UPDATE appointments 
                         SET status = 'Cancelled', notes = CONCAT(notes, '\n\nCancelled: ', ?), updated_at = NOW()
                         WHERE appointment_id = ?";
            
            $this->db->update($updateSql, [$reason, $appointmentId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get today's appointments
     */
    public function getTodayAppointments(int $doctorId = null): array {
        $sql = "SELECT a.*, p.full_name as patient_name, p.phone as patient_phone,
                    u.full_name as doctor_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                JOIN users u ON a.doctor_id = u.user_id
                WHERE DATE(a.appointment_date) = CURDATE()
                AND a.status IN ('Scheduled', 'Confirmed', 'In Progress')";
        
        $params = [];
        
        if ($doctorId) {
            $sql .= " AND a.doctor_id = ?";
            $params[] = $doctorId;
        }
        
        $sql .= " ORDER BY a.appointment_time ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
}
