<?php
namespace born05\twofactorauthentication\services;

use Craft;
use craft\helpers\UrlHelper;
use yii\base\Component;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class Response extends Component
{
    /**
     * Return the request with JSON.
     *
     * @param array $data
     */
    public function asJson($data)
    {
        $response = Craft::$app->getResponse();
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = $data;
        Craft::$app->end(0, $response);
    }

    /**
     * Determine if the url points to the verification part of this plugin.
     *
     * @param  strin $url
     * @return boolean
     */
    public function isTwoFactorAuthenticationUrl($url)
    {
        $verifyUrl = UrlHelper::actionUrl('two-factor-authentication/verify');

        return strpos($url, $verifyUrl) === 0;
    }
}
