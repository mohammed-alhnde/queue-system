<?php
header('Content-Type: application/json');
include '../config.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $data = [];
    
    // Get currently serving customer
    $stmt = $conn->query("
        SELECT c.*, cnt.name as counter_name 
        FROM customers c 
        LEFT JOIN counters cnt ON cnt.current_customer_id = c.id 
        WHERE c.status = 'serving' AND DATE(c.created_at) = CURDATE() 
        ORDER BY c.called_at DESC 
        LIMIT 1
    ");
    $data['now_serving'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get next in line (oldest waiting customer)
    $stmt = $conn->query("
        SELECT * FROM customers 
        WHERE status = 'waiting' AND DATE(created_at) = CURDATE() 
        ORDER BY created_at ASC 
        LIMIT 1
    ");
    $data['next_in_line'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get waiting count
    $stmt = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status = 'waiting' AND DATE(created_at) = CURDATE()");
    $data['waiting_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get recently called (last 6 completed or serving)
    $stmt = $conn->query("
        SELECT queue_number, called_at 
        FROM customers 
        WHERE (status = 'completed' OR status = 'serving') 
        AND DATE(created_at) = CURDATE() 
        AND called_at IS NOT NULL 
        ORDER BY called_at DESC 
        LIMIT 6
    ");
    $data['recent_called'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get waiting queue for ticker
    $stmt = $conn->query("
        SELECT queue_number 
        FROM customers 
        WHERE status = 'waiting' AND DATE(created_at) = CURDATE() 
        ORDER BY created_at ASC 
        LIMIT 10
    ");
    $data['waiting_queue'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all counters with their current serving number
    $stmt = $conn->query("
        SELECT c.id, c.name, c.service_types, c.is_online,
               cust.queue_number AS current_queue_number,
               cust.name AS current_customer_name
        FROM counters c
        LEFT JOIN customers cust ON cust.id = c.current_customer_id
        ORDER BY c.id ASC
    ");
    $data['counters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counter information for currently serving
    if ($data['now_serving']) {
        $stmt = $conn->prepare("
            SELECT name FROM counters WHERE current_customer_id = ?
        ");
        $stmt->execute([$data['now_serving']['id']]);
        $counter = $stmt->fetch(PDO::FETCH_ASSOC);
        $data['now_serving']['counter_name'] = $counter ? $counter['name'] : 'كاونتر متاح';
    }
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>