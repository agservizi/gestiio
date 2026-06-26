<?php

return [
    'disk' => env('SENSITIVE_FILES_DISK', 'sensitive'),
    'max_kb' => (int) env('SENSITIVE_FILES_MAX_KB', 51200),
    'store_base64_copy' => env('SENSITIVE_FILES_STORE_BASE64_COPY', false),
    'allowed_extensions' => [
        'pdf', 'jpg', 'jpeg', 'png', 'webp', 'tif', 'tiff', 'heic',
        'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'p7m', 'zip',
    ],
    'blocked_extensions' => [
        'php', 'phtml', 'phar', 'js', 'html', 'htm', 'svg', 'exe', 'bat',
        'cmd', 'com', 'scr', 'msi', 'sh', 'bash', 'ps1', 'jar', 'iso',
    ],
    'blocked_signatures' => [
        '<?php',
        '<script',
        '<svg',
        'MZ',
    ],
];
