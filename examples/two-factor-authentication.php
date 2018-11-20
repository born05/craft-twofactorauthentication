<?php

return [
    'verifyFrontEnd' => false,
    'forceFrontEnd' => false,
    'forceBackEnd' => false,
    
    // The URI we should use for 2FA ligin verification on the front-end.
    'verifyPath' => '',
    
    // The URI we should use for 2FA settings (turning it on and off) on the front-end.
    'settingsPath' => '',

    // Choose between using the whitelist or blacklist! Using both will block everything!
    // Whitelist paths that do not need 2FA.
    'frontEndPathWhitelist' => [
        '*' => [],
    ],
    // Blacklist paths that do need 2FA.
    'frontEndPathBlacklist' => [
        '*' => [],
    ],
];
