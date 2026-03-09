<?php
/**
 * Reports Class
 * Handles reporting and analytics
 */

require_once __DIR__ . '/Database.php';

class Reports {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get revenue report
     */
    public function getRevenueReport(string $startDate, string $endDate): array {
        $sql = "SELECT 
                    DATE(i.invoice_date) as date,
                    COUNT(*) as total_invoices,
                    SUM(i.total_amount) as total_revenue,
                    SUM(i.paid_amount) as total_paid,
                    SUM(i.balance_due) as outstanding
                FROM invoices i
                WHERE DATE(i.invoice_date) BETWEEN ? AND ?
                GROUP BY DATE(i.invoice_date)
                ORDER BY i.invoice_date ASC";
        
        return $this->db->fetchAll($sql, [$startDate, $endDate]);
    }
    
    /**
     * Get doctor performance report
     */
    public function getDoctorPerformanceReport(string $startDate = null, string $endDate = null): array {
        $startDate = $startDate ?? date('Y-01-01');
        $endDate = $endDate ?? date('Y-m-d');
        
        $sql = "SELECT 
                    u.user_id,
                    u.full_name as doctor_name,
                    COALESCE(d.specialization, 'N/A') as specialization,
                    COUNT(DISTINCT pv.visit_id) as total_visits,
                    COUNT(DISTINCT CASE WHEN pv.status = 'Completed' THEN pv.visit_id END) as completed_visits,
                    COUNT(DISTINCT i.invoice_id) as total_invoices,
                    SUM(i.total_amount) as total_revenue,
                    COUNT(DISTINCT pt.test_id) as tests_recommended,
                    COUNT(DISTINCT pr.prescription_id) as prescriptions_issued
                FROM users u
                LEFT JOIN doctor_details d ON u.user_id = d.user_id
                LEFT JOIN patient_visits pv ON u.user_id = pv.doctor_id 
                    AND DATE(pv.visit_date) BETWEEN ? AND ?
                LEFT JOIN invoices i ON pv.visit_id = i.visit_id
                LEFT JOIN patient_tests pt ON pv.visit_id = pt.visit_id
                LEFT JOIN patient_prescriptions pr ON pv.visit_id = pr.visit_id
                WHERE u.role_id = ? AND u.is_active = 1
                GROUP BY u.user_id, u.full_name
                ORDER BY total_visits DESC";
        
        return $this->db->fetchAll($sql, [$startDate, $endDate, 2]); // role_id 2 = doctor
    }
    
    /**
     * Get patient visit statistics
     */
    public function getPatientVisitStats(string $startDate = null, string $endDate = null): array {
        $startDate = $startDate ?? date('Y-01-01');
        $endDate = $endDate ?? date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(DISTINCT pv.patient_id) as total_patients,
                    COUNT(pv.visit_id) as total_visits,
                    AVG(TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE())) as avg_patient_age,
                    COUNT(CASE WHEN p.gender = 'Male' THEN 1 END) as male_patients,
                    COUNT(CASE WHEN p.gender = 'Female' THEN 1 END) as female_patients,
                    COUNT(CASE WHEN pv.status = 'Completed' THEN 1 END) as completed_visits,
                    COUNT(CASE WHEN pv.status = 'In Progress' THEN 1 END) as pending_visits
                FROM patient_visits pv
                JOIN patients p ON pv.patient_id = p.patient_id
                WHERE DATE(pv.visit_date) BETWEEN ? AND ?";
        
        return [$this->db->fetchOne($sql, [$startDate, $endDate])];
    }
    
    /**
     * Get medicine usage report
     */
    public function getMedicineUsageReport(string $startDate = null, string $endDate = null, int $limit = 20): array {
        $startDate = $startDate ?? date('Y-01-01');
        $endDate = $endDate ?? date('Y-m-d');
        
        $sql = "SELECT 
                    m.medicine_id,
                    m.medicine_name,
                    COUNT(ppm.medicine_id) as times_prescribed,
                    COUNT(DISTINCT pr.patient_id) as unique_patients,
                    SUM(CAST(ppm.dose AS CHAR)) as total_units
                FROM patient_prescription_medicines ppm
                JOIN medicines m ON ppm.medicine_name = m.medicine_name
                JOIN patient_prescriptions pr ON ppm.prescription_id = pr.prescription_id
                WHERE DATE(pr.prescription_date) BETWEEN ? AND ?
                GROUP BY m.medicine_id, m.medicine_name
                ORDER BY times_prescribed DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$startDate, $endDate, $limit]);
    }
    
    /**
     * Get lab test report
     */
    public function getLabTestReport(string $startDate = null, string $endDate = null): array {
        $startDate = $startDate ?? date('Y-01-01');
        $endDate = $endDate ?? date('Y-m-d');
        
        $sql = "SELECT 
                    tt.type_name,
                    COUNT(pt.test_id) as total_tests,
                    COUNT(CASE WHEN pt.status = 'Completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN pt.status = 'Processing' THEN 1 END) as processing,
                    COUNT(CASE WHEN pt.status = 'Cancelled' THEN 1 END) as cancelled
                FROM patient_tests pt
                LEFT JOIN test_types tt ON pt.test_type = tt.type_name
                WHERE DATE(pt.requested_at) BETWEEN ? AND ?
                GROUP BY tt.type_name
                ORDER BY total_tests DESC";
        
        return $this->db->fetchAll($sql, [$startDate, $endDate]);
    }
    
    /**
     * Get appointment statistics
     */
    public function getAppointmentStats(string $startDate = null, string $endDate = null): array {
        $startDate = $startDate ?? date('Y-01-01');
        $endDate = $endDate ?? date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(*) as total_appointments,
                    COUNT(CASE WHEN status = 'Completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN status = 'Scheduled' THEN 1 END) as scheduled,
                    COUNT(CASE WHEN status = 'No Show' THEN 1 END) as no_show,
                    COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) as cancelled,
                    ROUND(100 * COUNT(CASE WHEN status = 'No Show' THEN 1 END) / COUNT(*), 2) as no_show_rate
                FROM appointments
                WHERE DATE(appointment_date) BETWEEN ? AND ?";
        
        return [$this->db->fetchOne($sql, [$startDate, $endDate])];
    }
    
    /**
     * Get patient satisfaction report (if ratings table exists)
     */
    public function getPatientDemographicsReport(): array {
        $sql = "SELECT 
                    blood_group,
                    COUNT(*) as patient_count,
                    AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())) as avg_age
                FROM patients
                WHERE is_active = 1
                GROUP BY blood_group
                ORDER BY patient_count DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get financial summary
     */
    public function getFinancialSummary(string $startDate = null, string $endDate = null): ?array {
        $startDate = $startDate ?? date('Y-01-01');
        $endDate = $endDate ?? date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(*) as total_invoices,
                    SUM(subtotal) as subtotal,
                    SUM(tax_amount) as total_tax,
                    SUM(total_amount) as total_invoiced,
                    SUM(paid_amount) as total_paid,
                    SUM(balance_due) as total_outstanding,
                    ROUND(100 * SUM(paid_amount) / SUM(total_amount), 2) as payment_percentage
                FROM invoices
                WHERE DATE(invoice_date) BETWEEN ? AND ?";
        
        return $this->db->fetchOne($sql, [$startDate, $endDate]);
    }
    
    /**
     * Get top diagnoses
     */
    public function getTopDiagnoses(int $limit = 10, string $startDate = null, string $endDate = null): array {
        $startDate = $startDate ?? date('Y-01-01');
        $endDate = $endDate ?? date('Y-m-d');
        
        $sql = "SELECT 
                    SUBSTRING_INDEX(diagnosis, ',', 1) as diagnosis,
                    COUNT(*) as count
                FROM patient_visits
                WHERE diagnosis IS NOT NULL AND diagnosis != ''
                AND DATE(visit_date) BETWEEN ? AND ?
                GROUP BY SUBSTRING_INDEX(diagnosis, ',', 1)
                ORDER BY count DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$startDate, $endDate, $limit]);
    }
    
    /**
     * Generate comprehensive report
     */
    public function generateComprehensiveReport(string $startDate, string $endDate): array {
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'generated_at' => date('Y-m-d H:i:s')
            ],
            'financial_summary' => $this->getFinancialSummary($startDate, $endDate),
            'patient_statistics' => $this->getPatientVisitStats($startDate, $endDate),
            'doctor_performance' => $this->getDoctorPerformanceReport($startDate, $endDate),
            'appointment_stats' => $this->getAppointmentStats($startDate, $endDate),
            'medicine_usage' => $this->getMedicineUsageReport($startDate, $endDate, 10),
            'lab_tests' => $this->getLabTestReport($startDate, $endDate),
            'top_diagnoses' => $this->getTopDiagnoses(10, $startDate, $endDate)
        ];
    }
}
