<?php
namespace born05\twofactorauthentication\services;

use Craft;
use DateInterval;
use OTPHP\TOTP;
use yii\base\Component;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\Db;
use craft\helpers\DateTimeHelper;
use born05\twofactorauthentication\records\User as UserRecord;
use born05\twofactorauthentication\records\Session as SessionRecord;
use born05\twofactorauthentication\models\AuthenticationCode as AuthenticationCodeModel;
use born05\twofactorauthentication\Plugin as TwoFactorAuth;

class Verify extends Component
{
    private $totp;

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
        $sessionId = $this->getSessionId($user);

        if (isset($sessionId)) {
            $sessionRecord = SessionRecord::findOne([
                'userId' => $user->id,
                'sessionId' => $sessionId,
            ]);

            if (isset($sessionRecord)) {
                $sessionDuration = Craft::$app->getUser()->getRemainingSessionTime();
                $minimalSessionDate = DateTimeHelper::currentUTCDateTime();
                $minimalSessionDate->sub(new DateInterval('PT' . $sessionDuration . 'S'));
                $dateVerified = DateTimeHelper::toDateTime($sessionRecord->dateVerified);

                return $dateVerified > $minimalSessionDate;
            }
        }

        return false;
    }

    /**
     * Verify the authenticationCode with the user's credentials.
     * @param  User $user
     * @param  string $authenticationCode
     * @return boolean
     */
    public function verify(User $user, $authenticationCode)
    {
        $authenticationCodeModel = new AuthenticationCodeModel();
        $authenticationCodeModel->authenticationCode = str_replace(' ', '', $authenticationCode);

        if ($authenticationCodeModel->validate()) {
            // Magic checking of the authentication code.
            $totp = $this->getTotp($user);
            $isValid = $this->getTotp($user)->verify($authenticationCodeModel->authenticationCode);

            if (!$isValid) {
                return false;
            }

            $now = DateTimeHelper::currentUTCDateTime();
            $userRecord = $this->getUserRecord($user);
            if ($userRecord->dateVerified === null) {
                $userRecord->dateVerified = Db::prepareValueForDb($now);
                $userRecord->update();
            }

            $twoFactorSessionRecord = $this->getTwoFactorSessionRecord($user);
            
            if (isset($twoFactorSessionRecord)) {
                $twoFactorSessionRecord->dateVerified = Db::prepareValueForDb($now);
                $twoFactorSessionRecord->update();
            }

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

        // Delete the session records
        $twoFactorSessionRecords = SessionRecord::findAll([
            'userId' => $user->id,
        ]);
        
        foreach ($twoFactorSessionRecords as $twoFactorSessionRecord) {
            $twoFactorSessionRecord->delete();
        }
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
        return $this->getTotp($user)->getQrCodeUri();
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
            $this->totp = new TOTP($user->email, $userRecord->secret);
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

    /**
     * Get the session record for two-factor.
     * @param  User $user
     * @return SessionRecord
     */
    private function getTwoFactorSessionRecord(User $user)
    {
        $sessionId = $this->getSessionId($user);

        if (!isset($sessionId)) {
            return null;
        }

        $twoFactorSessionRecord = SessionRecord::findOne([
            'userId' => $user->id,
            'sessionId' => $sessionId,
        ]);

        if (!isset($twoFactorSessionRecord)) {
            $twoFactorSessionRecord = new SessionRecord();
            $twoFactorSessionRecord->userId = $user->id;
            $twoFactorSessionRecord->sessionId = $sessionId;
            $twoFactorSessionRecord->dateVerified = DateTimeHelper::currentUTCDateTime();
            $twoFactorSessionRecord->save();
        }

        return $twoFactorSessionRecord;
    }

    /**
     * Get the session id.
     * @param  User $user
     * @return int
     */
    private function getSessionId(User $user)
    {
        $session = Craft::$app->getSession();
        $token = $session->get(Craft::$app->user->tokenParam);

        // Extract the current session token's UID from the identity cookie
        $tokenId = (new Query())
            ->select(['id'])
            ->from([Table::SESSIONS])
            ->where([
                'token' => $token,
                'userId' => $user->id,
            ])
            ->scalar();

        return $tokenId;
    }

    /**
     * Determine if the UserAgent matches the current one.
     *
     * @param string $userAgent
     * @return bool
     */
    private function checkUserAgentString($userAgent)
    {
        if (Craft::$app->getConfig()->get('requireMatchingUserAgentForSession')) {
            $currentUserAgent = Craft::$app->request->getUserAgent();

            return $userAgent === $currentUserAgent;
        }

        return true;
    }
}
