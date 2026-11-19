<?php

return [
    'workspace' => ['enabled' => env('OFFLINE_WORKSPACE_ENABLED', false)],
    'production' => [
        'enabled' => env('OFFLINE_PRODUCTION_SYNC_ENABLED', false),
        'history_days' => 90,
        'snapshot_hours' => 24,
        'cursor_days' => 7,
        'max_records' => 10000,
    ],
];
