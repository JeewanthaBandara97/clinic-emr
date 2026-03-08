<?php
/**
 * Helper Functions
 * Clinic EMR System
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}


 

/**
 * Validate email
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 */
function validatePhone(string $phone): bool {
    return preg_match('/^[0-9+\-\s()]{7,20}$/', $phone);
}

/**
 * Validate date
 */
function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Format date for display
 */
function formatDate(?string $date, string $format = 'd M Y'): string {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime(?string $datetime, string $format = 'd M Y h:i A'): string {
    if (empty($datetime)) return '-';
    return date($format, strtotime($datetime));
}

/**
 * Format time for display
 */
function formatTime(?string $time, string $format = 'h:i A'): string {
    if (empty($time)) return '-';
    return date($format, strtotime($time));
}

/**
 * Calculate age from date of birth
 */
function calculateAge(string $dob): int {
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    return $birthDate->diff($today)->y;
}

/**
 * Calculate BMI
 */
function calculateBMI(float $weight, float $height): float {
    if ($height <= 0) return 0;
    $heightInMeters = $height / 100;
    return round($weight / ($heightInMeters * $heightInMeters), 1);
}

/**
 * Get BMI category
 */
function getBMICategory(float $bmi): string {
    if ($bmi < 18.5) return 'Underweight';
    if ($bmi < 25) return 'Normal';
    if ($bmi < 30) return 'Overweight';
    return 'Obese';
}

/**
 * Generate random code
 */
function generateCode(string $prefix, int $length = 6): string {
    return $prefix . str_pad(mt_rand(1, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message as Bootstrap alert
 */
function displayFlash(): void {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'];
        $message = htmlspecialchars($flash['message']);
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

/**
 * Redirect with message
 */
function redirect(string $url, string $type = null, string $message = null): void {
    if ($type && $message) {
        setFlash($type, $message);
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get pagination data
 */
function getPagination(int $totalRecords, int $currentPage, int $perPage = RECORDS_PER_PAGE): array {
    $totalPages = ceil($totalRecords / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Render pagination HTML
 */
function renderPagination(array $pagination, string $baseUrl): string {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    $prevDisabled = $pagination['has_previous'] ? '' : 'disabled';
    $prevPage = $pagination['current_page'] - 1;
    $html .= "<li class='page-item {$prevDisabled}'><a class='page-link' href='{$baseUrl}?page={$prevPage}'>Previous</a></li>";
    
    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    if ($start > 1) {
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page=1'>1</a></li>";
        if ($start > 2) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $pagination['current_page'] ? 'active' : '';
        $html .= "<li class='page-item {$active}'><a class='page-link' href='{$baseUrl}?page={$i}'>{$i}</a></li>";
    }
    
    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) {
            $html .= "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }
        $html .= "<li class='page-item'><a class='page-link' href='{$baseUrl}?page={$pagination['total_pages']}'>{$pagination['total_pages']}</a></li>";
    }
    
    // Next button
    $nextDisabled = $pagination['has_next'] ? '' : 'disabled';
    $nextPage = $pagination['current_page'] + 1;
    $html .= "<li class='page-item {$nextDisabled}'><a class='page-link' href='{$baseUrl}?page={$nextPage}'>Next</a></li>";
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Get blood group options
 */
function getBloodGroups(): array {
    return ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Unknown'];
}

/**
 * Get gender options
 */
function getGenders(): array {
    return ['Male', 'Female', 'Other'];
}

/**
 * Get test types
 */
function getTestTypes(): array {
    return ['Blood Test', 'Urine Test', 'X-Ray', 'ECG', 'Ultrasound', 'MRI', 'CT Scan', 'Other'];
}

/**
 * Get medicine routes
 */
function getMedicineRoutes(): array {
    return ['Oral', 'Topical', 'Injection', 'Inhalation', 'Other'];
}

/**
 * Get frequency options
 */
function getFrequencies(): array {
    return [
        'Once daily',
        'Twice daily',
        'Three times daily',
        'Four times daily',
        'Every 4 hours',
        'Every 6 hours',
        'Every 8 hours',
        'Every 12 hours',
        'As needed',
        'At bedtime',
        'Before meals',
        'After meals',
        'With meals'
    ];
}

/**
 * Get medicines from database
 */
function getMedicines(): array {
    try {
        require_once __DIR__ . '/../classes/Database.php';
        $db = Database::getInstance();
        $medicines = $db->fetchAll("SELECT * FROM `medicines` ORDER BY `medicines`.`medicine_name` ASC");
        return is_array($medicines) ? $medicines : [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Debug function
 */
function dd($data): void {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}