<?php
/**
 * Render / Cloud Health Check Endpoint
 */
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['status' => 'ok', 'service' => 'PRIME DENTAL CLINIC', 'time' => date('c')]);
exit;
