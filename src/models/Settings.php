<?php

namespace born05\twofactorauthentication\models;

use craft\base\Model;
use craft\helpers\ConfigHelper;

class Settings extends Model
{
    public $verifyFrontEnd = false;
    public $forceFrontEnd = false;
    public $forceBackEnd = false;

    /**
     * @var mixed The URI we should use for 2FA on the front-end.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getVerifyPath()
     */
    public $verifyPath = '';
    
    /**
     * @var mixed The URI we should use for 2FA settings on the front-end.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getSettingsPath()
     */
    public $settingsPath = '';

    // Choose between using the whitelist or blacklist! Using both will block everything!
    /**
     * @var mixed URIs. Exact path or regex.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getFrontEndPathWhitelist()
     */
    public $frontEndPathWhitelist = [];
    
    /**
     * @var mixed URIs. Exact path or regex.
     *
     * See [[ConfigHelper::localizedValue()]] for a list of supported value types.
     * @see getFrontEndPathBlacklist()
     */
    public $frontEndPathBlacklist = [];

    public function rules()
    {
        return [
            [['verifyFrontEnd', 'forceFrontEnd', 'forceBackEnd'], 'boolean'],
        ];
    }

    /**
     * Returns the localized Verify Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see verifyPath
     */
    public function getVerifyPath(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->verifyPath, $siteHandle);
    }

    /**
     * Returns the localized Settings Path value.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see settingsPath
     */
    public function getSettingsPath(string $siteHandle = null): string
    {
        return ConfigHelper::localizedValue($this->settingsPath, $siteHandle);
    }

    /**
     * Returns the localized frontEndPathWhitelist.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see frontEndPathWhitelist
     */
    public function getFrontEndPathWhitelist(string $siteHandle = null): array
    {
        return ConfigHelper::localizedValue($this->frontEndPathWhitelist, $siteHandle);
    }

    /**
     * Returns the localized frontEndPathBlacklist.
     *
     * @param string|null $siteHandle The site handle the value should be defined for. Defaults to the current site.
     * @return string
     * @see frontEndPathBlacklist
     */
    public function getFrontEndPathBlacklist(string $siteHandle = null): array
    {
        return ConfigHelper::localizedValue($this->frontEndPathBlacklist, $siteHandle);
    }
}