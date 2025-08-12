<?php
// === SECURITY HEADERS ===
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Content-Security-Policy: default-src 'self'");
header("Referrer-Policy: no-referrer");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// === RATE LIMITING (simple IP-based) ===
$ip = $_SERVER['REMOTE_ADDR'];
$logfile = __DIR__ . '/ip_log.txt';
$logEntry = date('Y-m-d H:i:s') . " - IP: $ip\n";
file_put_contents($logfile, $logEntry, FILE_APPEND);
$safe_ip = str_replace(':', '_', $ip);   // sanitize IP for filename
$limit = 10;           // Max 10 requests
$window = 60;          // Per 60 seconds
$now = time();
$filename = sys_get_temp_dir() . "/rate_{$safe_ip}.json";

if (file_exists($filename)) {
    $requests = json_decode(file_get_contents($filename), true);
    $requests = array_filter($requests, fn($t) => $t > $now - $window);
} else {
    $requests = [];
}

if (count($requests) >= $limit) {
    http_response_code(429);
    echo json_encode(["error" => "Too many requests. Try again later."]);
    exit();
}

$requests[] = $now;
file_put_contents($filename, json_encode($requests));
