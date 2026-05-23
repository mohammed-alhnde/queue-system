<?php
header('Content-Type: application/json');
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'الطريقة غير مسموحة']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('تنسيق JSON غير صالح');
    }
    
    $customerId = $data['customer_id'] ?? 0;

    if ($customerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرّف العميل غير صالح']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get the customer
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'العميل غير موجود']);
        exit;
    }

    if ($customer['status'] === 'serving') {
        echo json_encode(['success' => false, 'message' => 'العميل قيد الخدمة بالفعل']);
        exit;
    }

    // Find a free online counter that can serve this service type
    $stmt = $conn->query("SELECT * FROM counters WHERE is_online = 1 AND current_customer_id IS NULL ORDER BY id ASC");
    $availableCounters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $matchedCounter = null;
    $preferredCounters = [];

    foreach ($availableCounters as $counter) {
        $serviceTypes = json_decode($counter['service_types'], true);
        if (is_array($serviceTypes) && in_array($customer['service_type'], $serviceTypes, true)) {
            $preferredCounters[] = $counter;
        }
    }

    if (!empty($preferredCounters)) {
        $matchedCounter = $preferredCounters[0];
    } elseif (!empty($availableCounters)) {
        $matchedCounter = $availableCounters[0];
    }

    if (!$matchedCounter) {
        echo json_encode(['success' => false, 'message' => 'لا يوجد كاونتر متاح حالياً']);
        exit;
    }

    $conn->beginTransaction();
    $stmt = $conn->prepare("UPDATE customers SET status = 'serving', called_at = NOW() WHERE id = ?");
    $stmt->execute([$customerId]);

    $stmt = $conn->prepare("UPDATE counters SET current_customer_id = ? WHERE id = ?");
    $stmt->execute([$customerId, $matchedCounter['id']]);
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'تم استدعاء العميل بنجاح',
        'counter' => $matchedCounter['name']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>