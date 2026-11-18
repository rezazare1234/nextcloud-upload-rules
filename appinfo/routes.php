<?php
declare(strict_types=1);
return ['routes' => [
    ['name' => 'config#policy', 'url' => '/policy', 'verb' => 'GET'],
    ['name' => 'config#save', 'url' => '/settings', 'verb' => 'PUT'],
    ['name' => 'config#directory', 'url' => '/directory', 'verb' => 'GET'],
]];
