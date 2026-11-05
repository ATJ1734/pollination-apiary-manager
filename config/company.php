<?php

declare(strict_types=1);

// Every company-identifying string in the app comes from here, which reads
// the environment. Editing .env rebrands the whole app with no code changes.
return [
    'name' => env('COMPANY_NAME', '[Company Name]'),
    'tagline' => env('COMPANY_TAGLINE', '[Company tagline]'),
    'address' => env('COMPANY_ADDRESS', '[Street Address, City, State ZIP]'),
    'phone' => env('COMPANY_PHONE', '[(555) 010-0000]'),
    'email' => env('COMPANY_EMAIL', '[info@example.com]'),
    'website' => env('COMPANY_WEBSITE', '[www.example.com]'),
    'logo' => env('COMPANY_LOGO', '/assets/img/logo-placeholder.svg'),
];
