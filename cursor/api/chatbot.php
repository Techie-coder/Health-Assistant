<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/TriageEngine.php';

$input = getJsonInput();
$message = sanitizeText($input['message'] ?? '');
$lang = in_array($input['lang'] ?? 'en', ['en', 'sw'], true) ? $input['lang'] : 'en';

if ($message === '') jsonResponse(['error' => 'Message required'], 400);

$engine = new TriageEngine();
jsonResponse(['reply' => $engine->chat($message, $lang)]);