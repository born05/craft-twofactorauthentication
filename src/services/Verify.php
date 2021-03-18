<?php
namespace born05\twofactorauthentication\services;

use Craft;
use DateInterval;
use OTPHP\TOTP;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\SvgWriter;
use yii\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\Db;
use craft\helpers\DateTimeHelper;

use born05\twofactorauthentication\records\User as UserRecord;
use born05\twofactorauthentication\models\AuthenticationCode as AuthenticationCodeModel;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class Verify extends Component
{
    private $totp;

    const SESSION_AUTH_HANDLE = 'twofactorauth_verified';

    /**
     * Determines if the user has two-factor authentication.
     * @param  User $user
     * @return boolean
     */
    public function isEnabled(User $user)
    {
        $userRecord = UserRecord::findOne([
            'userId' => $user->id,
        ]);

        return isset($userRecord) && $userRecord->dateVerified !== null;
    }

    /**
     * Determines if the user is verified.
     * @param  User $user
     * @return boolean
     */
    public function isVerified(User $user)
    {
        return Craft::$app->getSession()->get(self::SESSION_AUTH_HANDLE) === true;
    }

    /**
     * Verify the authenticationCode with the user's credentials.
     * @param  User $user
     * @param  string $authenticationCode
     * @return boolean
     */
    public function verify(User $user, $authenticationCode)
    {
        $settings = TwoFactorAuth::$plugin->getSettings();

        $authenticationCodeModel = new AuthenticationCodeModel();
        $authenticationCodeModel->authenticationCode = str_replace(' ', '', $authenticationCode);

        if ($authenticationCodeModel->validate()) {
            // Magic checking of the authentication code.
            $totp = $this->getTotp($user);
            $isValid = $this->getTotp($user)->verify($authenticationCodeModel->authenticationCode);

            if (!$isValid && is_int($settings->totpDelay)) {
                $isValid = $this->getTotp($user)->verify(
                    $authenticationCodeModel->authenticationCode,
                    time() - $settings->totpDelay
                );
            }

            if (!$isValid) {
                return false;
            }

            $now = DateTimeHelper::currentUTCDateTime();
            $userRecord = $this->getUserRecord($user);
            if ($userRecord->dateVerified === null) {
                $userRecord->dateVerified = Db::prepareValueForDb($now);
                $userRecord->update();
            }

            Craft::$app->getSession()->set(self::SESSION_AUTH_HANDLE, true);

            return true;
        }

        return false;
    }

    /**
     * Disable the current user's two-factor authentication.
     * @param  User $user
     * @return string
     */
    public function disableUser(User $user)
    {
        // Update the user record
        $totp = new TOTP($user->email);
        $userRecord = $this->getUserRecord($user);
        // Remove verified state
        $userRecord->dateVerified = null;
        // Reset the secret
        $userRecord->secret = $totp->getSecret();
        $userRecord->update();

        Craft::$app->getSession()->remove(self::SESSION_AUTH_HANDLE);
    }

    /**
     * Get the user's secret.
     * @param  User $user
     * @return string
     */
    public function getUserSecret(User $user)
    {
        return $this->getTotp($user)->getSecret();
    }

    /**
     * Get the user's secret QR code.
     * @param  User $user
     * @return string
     */
    public function getUserQRCode(User $user)
    {
        $provisioningUri = $this->getTotp($user)->getProvisioningUri();
        $qrCode = QrCode::create($provisioningUri);

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        
        return $result->getDataUri();
    }

    /**
     * Get a valid TOTP instance.
     * @param  User $user
     * @return TOTP
     */
    private function getTotp(User $user)
    {
        if (!isset($this->totp)) {
            $userRecord = $this->getUserRecord($user);
            $this->totp = TOTP::create($userRecord->secret);
            $this->totp->setLabel($user->email);
            $this->totp->setIssuer(Craft::$app->getInfo()->name);
        }

        return $this->totp;
    }

    /**
     * Get the user record for two-factor.
     * @param  User $user
     * @return UserRecord
     */
    private function getUserRecord(User $user)
    {
        $userRecord = UserRecord::findOne([
            'userId' => $user->id,
        ]);

        if (!isset($userRecord)) {
            $totp = new TOTP($user->email);
            $userRecord = new UserRecord();
            $userRecord->userId = $user->id;
            $userRecord->secret = $totp->getSecret();
            $userRecord->save();
        }

        return $userRecord;
    }
}
