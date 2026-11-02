<?php

declare(strict_types=1);

return [
    'chat' => (bool) env('FEATURE_CHAT', false),
    'documentation' => (bool) env('FEATURE_DOCUMENTATION', false),
    'backup' => (bool) env('FEATURE_BACKUP', env('BACKUP_ENABLED', false)),
];
