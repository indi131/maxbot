<?php

declare(strict_types=1);

use App\Http\Request;
use App\Http\Response;

/** @var \App\Application $app */
$app = require dirname(__DIR__) . '/bootstrap.php';

if ((new Request())->method() !== 'POST') {
    Response::json(['error' => 'Method not allowed'], 405);
}

$request = new Request();

try {
    $data = $request->getJson();
} catch (\InvalidArgumentException $e) {
    Response::json(['error' => $e->getMessage()], 400);
}

$password = isset($data['password']) && is_string($data['password']) ? $data['password'] : '';
$dataField = isset($data['data']) && is_string($data['data']) ? $data['data'] : '';

try {
    $result = $app->notificationService->receive($password, $dataField);
    Response::json($result, 200);
} catch (\InvalidArgumentException $e) {
    Response::json(['error' => $e->getMessage()], 400);
} catch (\RuntimeException $e) {
    Response::json(['error' => $e->getMessage()], 404);
} catch (\Throwable $e) {
    Response::json(['error' => 'Внутренняя ошибка'], 500);
}
