<?php

return [
    '*' => [
        /**
         * Allow a TOTP delay (in seconds). This gives the user some extra
         * time after the code has expired.
         */
        'totpDelay' => null,

        /**
         * Whether 2FA should be enabled for the front-end.
         */
        'verifyFrontEnd' => false,

        /**
         * Whether 2FA should be enabled for the back-end/control panel.
         */
        'verifyBackEnd' => true,

        /**
         * Whether users should be forced to enable 2FA when logging into the
         * front-end. When this is true, users that do not have 2FA enabled will
         * not be able to access the front-end without first enabling 2FA.
         */
        'forceFrontEnd' => false,

        /**
         * Whether users should be forced to enable 2FA when logging into the
         * backend/control panel. When this is true, users that do not have
         * 2FA enabled will not be able to access the control panel without
         * first enabling 2FA.
         */
        'forceBackEnd' => false,

        /**
         * The URI we should use for 2FA login verification on the front-end.
         */
        'verifyPath' => '',

        /**
         * The URI we should use for 2FA settings (enabling/disabling) on the front-end.
         */
        'settingsPath' => '',

        /**
         * Allow paths that do not need 2FA. Exact path or regex, no leading slashes.
         */
        'backEndPathAllow' => [],

        /**
         *
         * NOTE: Use EITHER `frontEndPathAllow` OR `frontEndPathExclude`.
         *       Using both will block all requests.
         *
         */

        /**
         * Allow paths that do not need 2FA. Exact path or regex, no leading slashes.
         */
        'frontEndPathAllow' => [],

        /**
         * Exclude paths that _do_ need 2FA. Exact path or regex, no leading slashes.
         */
        'frontEndPathExclude' => [],
    ],
];
