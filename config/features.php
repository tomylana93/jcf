<?php

declare(strict_types=1);

return [
    'chat' => (bool) env('FEATURE_CHAT', false),
    'documentation' => (bool) env('FEATURE_DOCUMENTATION', false),
    'api' => (bool) env('FEATURE_API', false),
    'backup' => (bool) env('FEATURE_BACKUP', env('BACKUP_ENABLED', false)),
];
