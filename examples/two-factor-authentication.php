<?php

return [
    '*' => [
        // Allow a totp delay in seconds (gives the user some extra time after code expired)
        'totpDelay' => null,

        'verifyFrontEnd' => false,
        'verifyBackEnd' => true,

        'forceFrontEnd' => false,
        'forceBackEnd' => false,
        
        // The URI we should use for 2FA ligin verification on the front-end.
        'verifyPath' => '',
        
        // The URI we should use for 2FA settings (turning it on and off) on the front-end.
        'settingsPath' => '',

        // Choose between using the whitelist or blacklist! Using both will block everything!
        // Whitelist paths that do not need 2FA. Exact path or regex. No leading slashes.
        'frontEndPathWhitelist' => [
        ],
        // Blacklist paths that do need 2FA. Exact path or regex. No leading slashes.
        'frontEndPathBlacklist' => [
        ],
    ],
];
