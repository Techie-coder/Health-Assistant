<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$lat = (float)($_GET['lat'] ?? 0);
$lng = (float)($_GET['lng'] ?? 0);
$radius = min(100, max(1, (float)($_GET['radius'] ?? 30)));
$type = sanitizeText($_GET['type'] ?? '');

if ($lat === 0.0 && $lng === 0.0) {
    jsonResponse(['error' => 'lat and lng required'], 400);
}

$db = getDb();
$stmt = $db->query('SELECT id, name, type, lat, lng, phone, address, services FROM facilities WHERE is_active = 1');
$all = $stmt->fetchAll();
$results = [];

foreach ($all as $f) {
    if ($type !== '' && $f['type'] !== $type) continue;
    $dist = haversine($lat, $lng, (float)$f['lat'], (float)$f['lng']);
    if ($dist <= $radius) {
        $f['distance_km'] = round($dist, 1);
        $f['services'] = json_decode($f['services'] ?? '[]', true);
        $results[] = $f;
    }
}

usort($results, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
jsonResponse(['facilities' => array_slice($results, 0, 15)]);

function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}