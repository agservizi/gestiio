<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = app(App\Http\Services\StirlingMobileScannerService::class);
$created = $svc->createSession();
$sid = $created['sessionId'];
echo "created $sid\n";

function validate($sid) {
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
    $body = @file_get_contents("http://stirling-pdf:8080/pdf-tools/api/v1/mobile-scanner/validate-session/$sid", false, $ctx);
    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    echo "validate => $status $body\n";
}

validate($sid);

// upload
$jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k=');
$boundary = '----b'.bin2hex(random_bytes(4));
$body = "--$boundary\r\nContent-Disposition: form-data; name=\"files\"; filename=\"a.jpg\"\r\nContent-Type: image/jpeg\r\n\r\n$jpeg\r\n--$boundary--\r\n";
file_get_contents("http://stirling-pdf:8080/pdf-tools/api/v1/mobile-scanner/upload/$sid", false, stream_context_create([
    'http' => ['method' => 'POST', 'header' => "Content-Type: multipart/form-data; boundary=$boundary\r\n", 'content' => $body, 'timeout' => 20, 'ignore_errors' => true],
]));
echo "uploaded\n";
validate($sid);

$listed = $svc->listFiles($sid);
echo "files=".$listed['count']."\n";
$svc->downloadToTemp($sid, $listed['files'][0]['filename']);
echo "downloaded once\n";
validate($sid);
$listed = $svc->listFiles($sid);
echo "files_after_download=".$listed['count']."\n";

// download again (empty?)
validate($sid);

$svc->deleteSession($sid);
echo "after delete\n";
validate($sid);
