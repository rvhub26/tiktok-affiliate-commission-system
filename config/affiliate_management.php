<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Affiliate Type Managers
    |--------------------------------------------------------------------------
    |
    | Only affiliate accounts listed here can change Inhouse/Online type for
    | affiliates inside their own downline branch. Use affiliate codes.
    |
    */
    'type_manager_codes' => array_values(array_filter(array_map(
        fn ($code) => strtoupper(trim($code)),
        explode(',', env('AFFILIATE_TYPE_MANAGER_CODES', ''))
    ))),
];
