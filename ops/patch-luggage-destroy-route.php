<?php
$file = '/var/www/html/routes/web-backend.php';
$content = file_get_contents($file);
$needle = "Route::post('{id}/action', [LuggageDepositController::class, 'action']);";
$insert = "Route::delete('{id}', [LuggageDepositController::class, 'destroy']);";
if (strpos($content, $insert) === false && strpos($content, $needle) !== false) {
    $content = str_replace(
        $needle,
        $needle . "\n        " . $insert,
        $content
    );
    file_put_contents($file, $content);
    echo "route_added\n";
} else {
    echo "route_ok\n";
}
