<?php

return [
    'search' => [
        'per_page' => max(1, (int) env('PROPERTY_SEARCH_PER_PAGE', 10)),
    ],
];
