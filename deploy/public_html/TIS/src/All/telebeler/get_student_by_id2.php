<?php
require_once '../db.php';

// Enable error reporting for debugging (disable display_errors in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/error.log'); // Adjust path as needed

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'Tələbə məlumatlarını əldə edərkən xəta baş verdi.',
    'data' => null,
    'debug' => []
];

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Verilənlər bazasına qoşulma xətası: ' . mysqli_connect_error());
    }

    // Check if ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Yanlış və ya çatışmayan tələbə ID.');
    }

    $studentId = intval($_GET['id']);
    
    // Prepare SQL query to fetch from telebeler only
    $query = "SELECT id, username, poct, number, photo, active_status, dogum_tarixi, years, cins, unvan, vetandasliq, sinif, qebul_tarixi, orta_bal, davamiyyet, status, ata, elaqe_nomre_ata, ana, elaqe_nomre_ana, riyaziyyat, fizika, kimya, biologiya, tarix, edebiyyat, qeyd, muellim_adi, ixtisas_adi 
              FROM telebeler 
              WHERE id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Sorğu hazırlanarkən xəta baş verdi: ' . $conn->error);
    }

    $stmt->bind_param('i', $studentId);
    if (!$stmt->execute()) {
        throw new Exception('Sorğu icra edilərkən xəta baş verdi: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();

        // Map gender (cins)
        $cinsLabel = ($student['cins'] == '0') ? 'Kişi' : (($student['cins'] == '1') ? 'Qadın' : 'Təyin edilməyib');

        // Map active_status
        $statusClass = 'secondary';
        $statusLabel = 'Təyin edilməyib';
        switch (strtolower($student['active_status'] ?? '')) {
            case 'active':
                $statusClass = 'success';
                $statusLabel = 'Aktiv';
                break;
            case 'inactive':
                $statusClass = 'danger';
                $statusLabel = 'Qeyri-aktiv';
                break;
            case 'graduate':
                $statusClass = 'info';
                $statusLabel = 'Məzun';
                break;
        }

        // Map davamiyyet (attendance status)
        $davamiyyetLabel = 'Təyin edilməyib';
        switch (strtolower($student['davamiyyet'] ?? '')) {
            case 'istirak_edir':
                $davamiyyetLabel = 'İştirak edib';
                break;
            case 'istirak_etmir':
                $davamiyyetLabel = 'İştirak etməyib';
                break;
            default:
                $davamiyyetLabel = $student['davamiyyet'] ?? 'Təyin edilməyib';
        }

        // Map status (general student status)
        $generalStatusLabel = 'Təyin edilməyib';
        switch (strtolower($student['status'] ?? '')) {
            case 'uzrli':
                $generalStatusLabel = 'Üzrlü';
                break;
            case 'istirak_edir':
                $generalStatusLabel = 'İştirak edir';
                break;
            case 'istirak_etmir':
                $generalStatusLabel = 'İştirak etmir';
                break;
            default:
                $generalStatusLabel = $student['status'] ?? 'Təyin edilməyib';
        }

        // Process muellim_adi as a JSON array
        $muellimDisplay = 'N/A'; // Default message
        if (!empty($student['muellim_adi'])) {
            $muellimArray = json_decode($student['muellim_adi'], true);
            if (is_array($muellimArray)) {
                // Filter out empty strings and keep all valid teacher names
                $validMuellim = array_filter($muellimArray, function($value) {
                    return !empty($value) && $value !== '""';
                });
                // If there are valid teacher names, join them with commas
                if (!empty($validMuellim)) {
                    $validMuellim = array_map(function($value) {
                        return htmlspecialchars($value, ENT_QUOTES);
                    }, $validMuellim);
                    $muellimDisplay = implode(', ', $validMuellim);
                }
            } elseif (!empty($student['muellim_adi']) && $student['muellim_adi'] !== '""') {
                // Fallback for non-JSON string
                $muellimDisplay = htmlspecialchars($student['muellim_adi'], ENT_QUOTES);
            }
        }

        // Process photo path
        $photoPath = $student['photo'] ?? '';

        $response['status'] = 'success';
        $response['message'] = 'Tələbə məlumatları uğurla əldə edildi.';
        $response['data'] = [
            'profile_info' => [
                'id' => $student['id'],
                'name' => $student['username'],
                'email' => $student['poct'] ?? '',
                'phone' => $student['number'] ?? '',
                'photo' => $photoPath,
                'status' => $statusLabel,
                'status_class' => $statusClass,
            ],
            'personal_info' => [
                'dogum_tarixi' => $student['dogum_tarixi'] ?? '',
                'years' => $student['years'] ?? '',
                'cins' => $cinsLabel,
                'unvan' => $student['unvan'] ?? '',
                'vetandasliq' => $student['vetandasliq'] ?? '',
            ],
            'academic_info' => [
                'sinif' => $student['sinif'] ?? '',
                'qebul_tarixi' => $student['qebul_tarixi'] ?? '',
                'orta_bal' => $student['orta_bal'] ?? '',
                'davamiyyet' => $davamiyyetLabel,
                'status' => $generalStatusLabel,
                'muellim_adi' => $muellimDisplay,
                'ixtisas_adi' => $student['ixtisas_adi'] ?? 'N/A',
            ],
            'parent_info' => [
                'ata' => $student['ata'] ?? '',
                'elaqe_nomre_ata' => $student['elaqe_nomre_ata'] ?? '',
                'ana' => $student['ana'] ?? '',
                'elaqe_nomre_ana' => $student['elaqe_nomre_ana'] ?? '',
            ],
            'grades' => [
                'riyaziyyat' => $student['riyaziyyat'] ?? '',
                'fizika' => $student['fizika'] ?? '',
                'kimya' => $student['kimya'] ?? '',
                'biologiya' => $student['biologiya'] ?? '',
                'tarix' => $student['tarix'] ?? '',
                'edebiyyat' => $student['edebiyyat'] ?? '',
            ],
            'notes' => $student['qeyd'] ?? '',
        ];
    } else {
        $response['message'] = 'Bu ID ilə tələbə tapılmadı.';
    }
    $stmt->close();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['debug'] = [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    error_log("Error in get_student_by_id.php: " . $e->getMessage());
}

$conn->close();

// Return JSON response
echo json_encode($response);
?>