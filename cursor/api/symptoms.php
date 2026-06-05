<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/TriageEngine.php';

$input = getJsonInput();
$symptoms = sanitizeText($input['symptoms'] ?? '');
$lang = in_array($input['lang'] ?? 'en', ['en', 'sw'], true) ? $input['lang'] : 'en';
$ageGroup = in_array($input['age_group'] ?? 'adult', ['child', 'adult', 'elderly'], true) ? $input['age_group'] : 'adult';
$duration = max(0, min(365, (int)($input['duration_days'] ?? 0)));
$lat = isset($input['lat']) ? (float)$input['lat'] : null;
$lng = isset($input['lng']) ? (float)$input['lng'] : null;
$patientId = isset($input['patient_id']) ? (int)$input['patient_id'] : null;

if ($symptoms === '') {
    jsonResponse(['error' => $lang === 'sw' ? 'Dalili zinahitajika' : 'Symptoms are required'], 400);
}

$engine = new TriageEngine();
$result = $engine->analyze($symptoms, $lang, $ageGroup, $duration);

try {
    $db = getDb();
    $stmt = $db->prepare(
        'INSERT INTO symptom_sessions (patient_id, symptoms, urgency, ai_response, language, lat, lng)
         VALUES (:pid, :symptoms, :urgency, :response, :lang, :lat, :lng)'
    );
    $stmt->execute([
        ':pid' => $patientId ?: null,
        ':symptoms' => $symptoms,
        ':urgency' => $result['urgency'],
        ':response' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ':lang' => $lang,
        ':lat' => $lat,
        ':lng' => $lng,
    ]);
    $result['session_id'] = (int)$db->lastInsertId();
} catch (Throwable) {
    $result['session_id'] = null;
}

jsonResponse($result);