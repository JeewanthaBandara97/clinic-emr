<?php
/**
 * Visit AJAX Handler
 * Handles dynamic data loading for visit creation
 */

// turn on error reporting for troubleshooting AJAX 500s
ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../../includes/auth.php';
requireDoctor();

require_once __DIR__ . '/../../classes/Medicine.php';
require_once __DIR__ . '/../../classes/LabTest.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$medicine = new Medicine();
$labTest = new LabTest();

try {
    switch ($action) {
        
        // =====================================================
        // MEDICINE OPERATIONS
        // =====================================================
        
        case 'search_medicines':
            $term = trim($_GET['term'] ?? '');
            $limit = (int)($_GET['limit'] ?? 15);
            
            // allow empty or short term so the dropdown can fire even with no input
            //if (strlen($term) < 2) {
            //    echo json_encode([]);
            //    exit;
            //}
            
            $results = $medicine->searchForPrescription($term, $limit);
            
            // Format for autocomplete
            $formatted = array_map(function($med) {
                return [
                    'id' => $med['medicine_id'],
                    'value' => $med['medicine_name'],
                    'label' => $med['medicine_name'] . 
                              ($med['generic_name'] ? ' (' . $med['generic_name'] . ')' : '') .
                              ($med['strength'] ? ' - ' . $med['strength'] : ''),
                    'medicine_name' => $med['medicine_name'],
                    'generic_name' => $med['generic_name'] ?? '',
                    'strength' => $med['strength'] ?? '',
                    'route' => $med['route'] ?? 'Oral',
                    'unit' => $med['unit_symbol'] ?? '',
                    'issuing_unit' => $med['issuing_unit'] ?? ''
                ];
            }, $results);
            
            echo json_encode($formatted);
            break;
            
        case 'get_medicine':
            $medicineId = (int)($_GET['id'] ?? 0);
            
            if ($medicineId <= 0) {
                throw new Exception('Invalid medicine ID');
            }
            
            $result = $medicine->getById($medicineId);
            
            if (!$result) {
                throw new Exception('Medicine not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            break;
        
        // =====================================================
        // LAB TEST OPERATIONS
        // =====================================================
        
        case 'get_test_types':
            $types = $labTest->getTypeList(true);
            echo json_encode($types);
            break;
            
        case 'get_tests_by_type':
            $typeId = (int)($_GET['type_id'] ?? 0);
            
            if ($typeId <= 0) {
                echo json_encode([]);
                exit;
            }
            
            $tests = $labTest->getLabTests(true, $typeId);
            
            $formatted = array_map(function($test) {
                return [
                    'id' => $test['test_id'],
                    'name' => $test['test_name'],
                    'price' => $test['test_price']
                ];
            }, $tests);
            
            echo json_encode($formatted);
            break;
            
        case 'search_tests':
            $term = trim($_GET['term'] ?? '');
            $limit = (int)($_GET['limit'] ?? 10);
            
            if (strlen($term) < 2) {
                echo json_encode([]);
                exit;
            }
            
            $results = $labTest->searchForAutocomplete($term, $limit);
            
            $formatted = array_map(function($test) {
                return [
                    'id' => $test['test_id'],
                    'value' => $test['test_name'],
                    'label' => $test['test_name'] . ' (' . $test['type_name'] . ')',
                    'test_name' => $test['test_name'],
                    'type_name' => $test['type_name'],
                    'price' => $test['test_price']
                ];
            }, $results);
            
            echo json_encode($formatted);
            break;
            
        case 'get_all_tests_grouped':
            $grouped = $labTest->getTestsGroupedByType(true);
            echo json_encode($grouped);
            break;
        
        // =====================================================
        // COMMON LOOKUPS
        // =====================================================
        
        case 'get_frequencies':
            $frequencies = [
                'OD' => 'Once Daily (OD)',
                'BD' => 'Twice Daily (BD)',
                'TDS' => 'Three Times Daily (TDS)',
                'QID' => 'Four Times Daily (QID)',
                'HS' => 'At Bedtime (HS)',
                'SOS' => 'As Needed (SOS)',
                'STAT' => 'Immediately (STAT)',
                'AC' => 'Before Meals (AC)',
                'PC' => 'After Meals (PC)',
                'Q4H' => 'Every 4 Hours',
                'Q6H' => 'Every 6 Hours',
                'Q8H' => 'Every 8 Hours',
                'Q12H' => 'Every 12 Hours',
                'Weekly' => 'Once Weekly',
                'Alternate' => 'Alternate Days'
            ];
            echo json_encode($frequencies);
            break;
            
        case 'get_routes':
            $routes = [
                'Oral',
                'Sublingual',
                'Topical',
                'Inhalation',
                'IV',
                'IM',
                'SC',
                'Rectal',
                'Vaginal',
                'Nasal',
                'Ophthalmic',
                'Otic',
                'Transdermal'
            ];
            echo json_encode($routes);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}