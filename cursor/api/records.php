<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db = getDb();

if ($method === 'POST') {
    $input = getJsonInput();
    $phone = sanitizeText($input['phone'] ?? '');
    $ageGroup = in_array($input['age_group'] ?? 'adult', ['child', 'adult', 'elderly'], true) ? $input['age_group'] : 'adult';
    $lang = in_array($input['lang'] ?? 'en', ['en', 'sw'], true) ? $input['lang'] : 'en';

    if ($phone === '') jsonResponse(['error' => 'Phone required'], 400);

    $hash = hash('sha256', $phone);
    $db->prepare(
        'INSERT INTO patients (phone_hash, age_group, language) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE age_group = VALUES(age_group), language = VALUES(language)'
    )->execute([$hash, $ageGroup, $lang]);

    $stmt = $db->prepare('SELECT id FROM patients WHERE phone_hash = ?');
    $stmt->execute([$hash]);
    $row = $stmt->fetch();

    jsonResponse(['patient_id' => (int)$row['id'], 'message' => 'ok']);
}

if ($method === 'GET') {
    $phone = sanitizeText($_GET['phone'] ?? '');
    if ($phone === '') jsonResponse(['error' => 'Phone required'], 400);

    $hash = hash('sha256', $phone);
    $stmt = $db->prepare('SELECT id FROM patients WHERE phone_hash = ?');
    $stmt->execute([$hash]);
    $patient = $stmt->fetch();

    if (!$patient) jsonResponse(['sessions' => [], 'patient_id' => null]);

    $stmt = $db->prepare(
        'SELECT id, symptoms, urgency, language, created_at FROM symptom_sessions
         WHERE patient_id = ? ORDER BY created_at DESC LIMIT 25'
    );
    $stmt->execute([(int)$patient['id']]);
    jsonResponse(['patient_id' => (int)$patient['id'], 'sessions' => $stmt->fetchAll()]);
}

jsonResponse(['error' => 'Method not allowed'], 405);