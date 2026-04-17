<?php

declare(strict_types=1);

use App\Http\Request;
use App\Http\Response;

/** @var \App\Application $app */
$app = require dirname(__DIR__) . '/bootstrap.php';

$request = new Request();
$secret = $app->config->get('MAX_WEBHOOK_SECRET');
$header = $request->header('X-Max-Bot-Api-Secret');

if ($secret !== '') {
    if ($header === null || ! hash_equals($secret, $header)) {
        Response::json(['error' => 'Forbidden'], 403);
    }
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    Response::json(['ok' => true]);
}

try {
    /** @var array<string, mixed> $update */
    $update = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (is_array($update)) {
        $app->botService->handleUpdate($update);
    }
} catch (\Throwable) {
    // всё равно отвечаем 200, чтобы MAX не отключал webhook
}

Response::json(['ok' => true]);
