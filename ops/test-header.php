<?php
header('Content-Type: application/json');
echo json_encode([
    'has_x_api_key' => request()->hasHeader('x-api-key'),
    'header_length' => strlen((string) request()->header('x-api-key')),
]);
