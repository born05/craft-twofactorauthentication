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
        return $response;
        // Craft::$app->end(0, $response);
    }

    public function getReturnUrl()
    {
        // Get the return URL
        $userService = Craft::$app->getUser();
        $request = Craft::$app->getRequest();
        $returnUrl = $userService->getReturnUrl();

        // Clear it out
        $userService->removeReturnUrl();

        // MODIFIED FROM COPY
        // Prevent looping back to the verify controller.
        if (
            $returnUrl === null ||
            $returnUrl === $request->getPathInfo() ||
            TwoFactorAuth::$plugin->request->is2FASpecialRequests()
        ) {
            // Is this a CP request and can they access the CP?
            if (Craft::$app->getRequest()->getIsCpRequest() && $this->checkPermission('accessCp')) {
                $returnUrl = UrlHelper::cpUrl(Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect());
            } else {
                $returnUrl = UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->getPostLoginRedirect());
            }
        }

        // Clear it out
        $userService->removeReturnUrl();

        return $returnUrl;
    }
}
