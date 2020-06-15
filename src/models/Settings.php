<?php

namespace born05\twofactorauthentication\models;

use craft\base\Model;

class Settings extends Model
{
    /**
     * Allow a totp delay in seconds (gives the user some extra time after code expired)
     *
     * @var int
     */
    public $totpDelay = null;

    public $verifyFrontEnd = false;
    public $verifyBackEnd = true;

    public $forceFrontEnd = false;
    public $forceBackEnd = false;

    /**
     * @var string The URI we should use for 2FA on the front-end.
     */
    public $verifyPath = '';
    
    /**
     * @var string The URI we should use for 2FA settings on the front-end.
     */
    public $settingsPath = '';

    // Choose between using the allow or exclude! Using both will block everything!
    /**
     * @var array URIs. Exact path or regex.
     */
    public $frontEndPathAllow = [];
    
    /**
     * @var array URIs. Exact path or regex.
     */
    public $frontEndPathExclude = [];

    public function rules()
    {
        return [
            [['verifyFrontEnd', 'verifyBackEnd', 'forceFrontEnd', 'forceBackEnd'], 'boolean'],
        ];
    }
}
