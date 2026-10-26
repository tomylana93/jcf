<?php

declare(strict_types=1);

return [
    'website' => (bool) env('FEATURE_WEBSITE', true),
    'chat' => (bool) env('FEATURE_CHAT', true),
    'documentation' => (bool) env('FEATURE_DOCUMENTATION', true),
    'backup' => (bool) env('FEATURE_BACKUP', env('BACKUP_ENABLED', false)),
];
