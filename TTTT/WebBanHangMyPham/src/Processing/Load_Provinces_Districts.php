<?php
/**
 * Load Provinces, Districts, and Wards from Vietnamese-Administrative-Units-Dataset.json
 * Creates a hierarchical structure: Province -> District -> Ward
 * 
 * This script extracts actual province names and tries to infer district names from ward names
 */

// Suppress errors that might break JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header first
header('Content-Type: application/json; charset=utf-8');

// Prevent any output before JSON
ob_start();

// JSON file path - multiple possible locations
$possible_paths = [
    __DIR__ . '/../../../Vietnamese-Administrative-Units-Dataset.json',
    __DIR__ . '/../../../../Vietnamese-Administrative-Units-Dataset.json',
    dirname(dirname(dirname(__DIR__))) . '/Vietnamese-Administrative-Units-Dataset.json'
];

$json_file = null;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        $json_file = $path;
        break;
    }
}

$provinces = [];
$errors = [];

if (!$json_file || !file_exists($json_file)) {
    $error_msg = $json_file ? "JSON file not found: $json_file" : "JSON file not found in any expected location";
    $errors[] = $error_msg;
    ob_clean();
    echo json_encode([
        'provinces' => [],
        'success' => false,
        'error' => 'JSON file not found',
        'errors' => $errors,
        'searched_paths' => $possible_paths
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$json_content = @file_get_contents($json_file);

if ($json_content === false) {
    $errors[] = "Failed to read JSON file";
    ob_clean();
    echo json_encode([
        'provinces' => [],
        'success' => false,
        'error' => 'Failed to read JSON file',
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$data = @json_decode($json_content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $errors[] = "JSON decode error: " . json_last_error_msg();
    ob_clean();
    echo json_encode([
        'provinces' => [],
        'success' => false,
        'error' => 'Invalid JSON format',
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if (!$data || !isset($data['provinces']) || !isset($data['wards'])) {
    $errors[] = "Invalid JSON structure - missing provinces or wards";
    ob_clean();
    echo json_encode([
        'provinces' => [],
        'success' => false,
        'error' => 'Invalid JSON structure',
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Step 1: Create province lookup by ID - ensure ALL provinces are included with their actual names
$province_by_id = [];
foreach ($data['provinces'] as $province) {
    if (!isset($province['id']) || !isset($province['name'])) {
        continue;
    }
    $province_by_id[$province['id']] = $province;
    // Initialize province in result array with actual province name
    $provinces[$province['name']] = [];
}

// Step 2: Group wards by province and extract district information
$province_wards = [];

foreach ($data['wards'] as $ward) {
    if (!isset($ward['province_id']) || !isset($ward['code']) || !isset($ward['name'])) {
        continue;
    }
    
    $province_id = $ward['province_id'];
    $province = $province_by_id[$province_id] ?? null;
    
    if (!$province) {
        continue;
    }
    
    $province_name = $province['name']; // Use actual province name
    $ward_code = $ward['code'];
    $ward_name = $ward['name'];
    
    // Normalize ward code to 5 digits
    $normalized_code = str_pad($ward_code, 5, '0', STR_PAD_LEFT);
    
    // Extract district identifier from ward code (first 3 digits)
    $district_key = substr($normalized_code, 0, 3);
    
    // Initialize province structure
    if (!isset($province_wards[$province_name])) {
        $province_wards[$province_name] = [];
    }
    
    // Initialize district structure
    if (!isset($province_wards[$province_name][$district_key])) {
        $province_wards[$province_name][$district_key] = [
            'wards' => [],
            'potential_names' => []
        ];
    }
    
    // Add ward to district
    if (!in_array($ward_name, $province_wards[$province_name][$district_key]['wards'])) {
        $province_wards[$province_name][$district_key]['wards'][] = $ward_name;
        
        // Try to extract district name from ward name
        // Many Vietnamese wards have district names embedded
        // Pattern: "Phường [District Name]" or "Xã [District Name]"
        $clean_ward_name = preg_replace('/^(Phường|Xã|Thị trấn)\s+/', '', $ward_name);
        
        // Store potential district names (we'll use the most common one)
        $province_wards[$province_name][$district_key]['potential_names'][] = $clean_ward_name;
    }
}

// Step 3: Convert to final structure with actual district names
foreach ($province_wards as $province_name => $districts) {
    foreach ($districts as $district_key => $district_data) {
        $wards_list = $district_data['wards'];
        $potential_names = $district_data['potential_names'];
        
        // Sort wards alphabetically
        sort($wards_list);
        
        // Determine district name
        $district_name = 'Khu vực ' . $district_key; // Default fallback
        
        // Try to find a common district name from ward names
        if (count($potential_names) > 0) {
            // Count frequency of potential district names
            $name_counts = array_count_values($potential_names);
            arsort($name_counts);
            
            // Get the most common name
            $most_common_name = key($name_counts);
            $count = current($name_counts);
            
            // If a name appears in multiple wards, it's likely the district name
            if ($count > 1 && strlen($most_common_name) > 2) {
                // Determine if it's a Quận (urban) or Huyện (rural) based on ward type
                $first_ward = $wards_list[0];
                $ward_type = 'Quận'; // Default
                if (strpos($first_ward, 'Xã') !== false) {
                    $ward_type = 'Huyện';
                } elseif (strpos($first_ward, 'Thị trấn') !== false) {
                    $ward_type = 'Huyện';
                }
                
                $district_name = $ward_type . ' ' . $most_common_name;
            } else {
                // Use first ward's name as district indicator
                $first_ward = $wards_list[0];
                $clean_first = preg_replace('/^(Phường|Xã|Thị trấn)\s+/', '', $first_ward);
                
                // Determine ward type
                $ward_type = 'Quận';
                if (strpos($first_ward, 'Xã') !== false || strpos($first_ward, 'Thị trấn') !== false) {
                    $ward_type = 'Huyện';
                }
                
                // Use a more descriptive name
                $district_name = $ward_type . ' ' . $clean_first;
            }
        }
        
        // Store in final structure
        $provinces[$province_name][$district_name] = $wards_list;
    }
    
    // Sort districts within each province
    ksort($provinces[$province_name]);
}

// Step 4: Ensure all provinces are included (even if they have no wards)
// Sort provinces alphabetically
ksort($provinces);

// Count statistics
$total_provinces = count($provinces);
$total_districts = 0;
$total_wards = 0;

foreach ($provinces as $province_name => $districts) {
    $total_districts += count($districts);
    foreach ($districts as $district_name => $wards) {
        $total_wards += count($wards);
    }
}

// Ensure we have at least some data
if (empty($provinces)) {
    // Fallback data if JSON parsing failed
    $provinces = [
        'Thành phố Hà Nội' => [
            'Quận Ba Đình' => ['Phường Ba Đình', 'Phường Ngọc Hà', 'Phường Giảng Võ']
        ],
        'Thành phố Hồ Chí Minh' => [
            'Quận 1' => ['Phường Bến Nghé', 'Phường Bến Thành']
        ]
    ];
    $total_provinces = count($provinces);
    $total_districts = 2;
    $total_wards = 5;
    $errors[] = 'Using fallback data - JSON parsing may have failed';
}

// Return complete data structure
$response = [
    'provinces' => $provinces,
    'success' => true,
    'statistics' => [
        'total_provinces' => $total_provinces,
        'total_districts' => $total_districts,
        'total_wards' => $total_wards
    ]
];

if (!empty($errors)) {
    $response['errors'] = $errors;
}

// Clear any output buffer
ob_clean();

// Output JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();
?>
