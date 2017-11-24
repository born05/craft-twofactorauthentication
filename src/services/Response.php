<?php
namespace born05\twofactorauthentication\services;

use Craft;
use yii\base\Component;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class Response extends Component
{
    /**
     * Return the request with JSON.
     *
     * @param array $data
     */
    public function returnJson($data = array())
    {
        JsonHelper::setJsonContentTypeHeader();
        HeaderHelper::setNoCache();
        ob_start();
        echo JsonHelper::encode($data);
        Craft::$app->end();
    }

    /**
     * Determine if the url points to the verification part of this plugin.
     *
     * @param  strin $url
     * @return boolean
     */
    public function isTwoFactorAuthenticationUrl($url)
    {
        $verifyUrl = UrlHelper::getActionUrl('two-factor-authentication/verify');

        return strpos($url, $verifyUrl) === 0;
    }
}
