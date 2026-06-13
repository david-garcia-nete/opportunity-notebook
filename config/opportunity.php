<?php

return [
    'allowed_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', env('OPPORTUNITY_ALLOWED_EMAILS', '')),
    ))),
];
