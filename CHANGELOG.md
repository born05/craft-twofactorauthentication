Changelog
==================

## 3.3.1 - 2023-08-29
### Fixed
- Use `isTwoFactorEnabled` on settings screen.
- Use `two-factor-authentication/settings/turn-on` action on settings screen.

## 3.3.0 - 2023-08-29
### Fixed
- Verify screen updated to Craft's new frontend style.
- Verify step wasn't functioning correctly.

## 3.2.1 - 2023-07-06
### Fixed
- Prevent error when lastLoginDate is not set. Thanks to @joepagan

## 3.2.0 - 2023-05-04
### Changed
- Make sure to render CP templates in `TEMPLATE_MODE_CP`
- AssetBundle improvement to prevent assets not loading during login
- Code cleanup

## 3.1.0 - 2023-03-31
### Fixed
- Fix 500 error in CP for non-admins. Thanks to @mike-moreau

## 3.0.2 - 2022-09-19
### Changes
- Updated froms and controllers to use Craft 4 way of returning data.

## 3.0.1 - 2022-07-13
### Changes
- Code cleanup
- Simplify request check

### Fixed
- Init load event now really uses `WebApplication`

## 3.0.0 - 2022-06-28
### Fixed
- Issuer name is now the system name instead of application name
- Init load event now uses `WebApplication::EVENT_INIT`

## 3.0.0-beta.1 - 2022-05-09
### Changed
- Added craft 4 support

## 2.10.0 - 2021-11-08
### Added
- `backEndPathAllow` config option to allow specific backend paths to bypass 2fa checks.

## 2.9.0 - 2021-04-01
### Fixed
- Verification is now also needed after a second login.

## 2.8.1 - 2021-03-23
### Fixed
- Fixed otphp update. Thanks to @bencroker

## 2.8.0 - 2021-03-18
### Updated
- Updated spomky-labs/otphp requiring php 7.3 and up.

### Added
- Use endroid/qr-code for qr code generating instead of google's deprecated chart api.

## 2.7.4 - 2021-02-11
### Fixed
- Fixed getting logged out during 2FA screen on Craft 3.4 and up. Thanks to @goraxan

## 2.7.3.1 - 2020-11-03
### Fixed
- Fixed composer.json for composer 2

## 2.7.3 - 2020-10-16
### Changed
- Don't do anything until EVENT_AFTER_LOAD_PLUGINS

## 2.7.2 - 2020-10-16
### Fixed
- Make sure not to install the old session table.

## 2.7.1 - 2020-10-16
### Fixed
- Fixed installation errors

## 2.7.0 - 2020-10-06
Released earlier changes.

### Changed
- Move from a custom session table to storing data in the session.

## 2.7.0-beta.1 - 2020-09-25
### Changed
- Move from a custom session table to storing data in the session.

## 2.6.3 - 2020-09-22
### Changed
- Allow `userSessionDuration` to be `0`

## 2.6.2 - 2020-09-15
### Fixed
- DateTime comparison is now between similar timezones. 

## 2.6.1 - 2020-06-15
Fixed warning of 2.6.0

## 2.6.0 - 2020-06-15
> {warning} BREAKING! Use frontEndPathAllow/frontEndPathExclude instead of frontEndPathWhitelist/frontEndPathBlacklist!

### Improved
- Use allow/exclude in favor of whitelist/blacklist
- Removed `ConfigHelper::localizedValue` from config making configs more dependable.

## 2.5.0 - 2020-05-19
### Added
- French translation thanks to @romainpoirier

### Fixed
- Removed some old & unused code

## 2.4.0 - 2020-01-07
### Fixed
- `userLoginEventHandler` now uses verifyBackEnd and verifyFrontEnd options as it is supposed to.

## 2.3.0 - 2019-11-18
### Added
- Add in the ability to add the secure redirect field to the front-end forms. Thanks to @coxeh

### Fixed
- Fixed a bug where turning off 2 step on the front end always redirects to the admin cp. Thanks to @coxeh

## 2.2.0 - 2019-07-31
### Added
- Added verifyBackEnd option

### Fixed
- Fixed admin redirect issue when using forceBackEnd.

## 2.1.2 - 2019-04-08
### Added
- Added `totpDelay` config option (gives the user some extra time after code expired)

## 2.1.1 - 2019-02-28
### Fixed
- Disabling 2FA for other users failed

## 2.1.0 - 2019-02-22
### Fixed
- Make sure the User's EVENT_AFTER_LOGIN is triggered thanks to @engram-design

## 2.1.0-beta.2 - 2019-01-23
### Fixed
- Fix otphp to pull their fix for random_compat on php 7.0 thanks to @mikestecker

## 2.0.1 - 2019-01-23
### Fixed
- Fix otphp to pull their fix for random_compat on php 7.0 thanks to @mikestecker

## 2.1.0-beta.1 - 2019-01-18
### Fixed
- Support for Craft 3.1

## 2.0.0 - 2019-01-15
Stable release for Craft 3.

## 2.0.0-beta.14 - 2018-11-22
### Added
- Blacklist and whitelist can be exact paths or regex.
- `isTwoFactorEnabled` can be used from twig.

## 2.0.0-beta.13 - 2018-11-06
### Added
- Added option to disable 2FA when a user is locked out of the account.

## 2.0.0-beta.12 - 2018-10-12
### Fixed
- Allow 2FA on the front end home.

## 2.0.0-beta.11 - 2018-10-10
### Fixed
- Fixed link to Google Authenticator

## 2.0.0-beta.10 - 2018-10-05
### Fixed
- Fixed 2FA CP path detection

## 2.0.0-beta.9 - 2018-10-05
### Added
- Front end 2FA support
- Allow forcing 2FA

### Fixed
- Migrated old code to Craft 3

## 2.0.0-beta.8 - 2018-09-09
### Improved
- @jlamb1 Fix Deprecation Error "Round"

## 2.0.0-beta.7 - 2018-08-10
### Improved
- @brandonkelly Fixed the install migration

## 2.0.0-beta.6 - 2018-07-20
### Changed
- Disabled the plugin for console commands.
- Added support for upgrading from Craft 2.

## 2.0.0-beta.5 - 2018-07-11
### Improved
- Added Dutch (NL) translation file. Thanks to @RichardFrontwise

## 2.0.0-beta.4 - 2018-07-09
### Changed
- Changed the translation scope to app.

## 2.0.0-beta.3 - 2018-07-05
### Changed
- Fixed debug bar support. Debug should be allowed, not trigger logout.

## 2.0.0-beta.2 - 2018-07-04
### Improved
- Prevent redirect loop for cookieBased login.

## 2.0.0-beta.1 - 2018-06-29
### Improved
- Fixed user overview with 2FA column enabled.

## 2.0.0-beta - 2018-06-29
- Beta release of the Craft 3 plugin.

## 1.2.0 - 2018-06-29
### Changed
- Moved releases to the craft-2 branch.

## 1.1.0 - 2017-12-07
### Improved
- Fixed console support.
- Downgraded otphp to 8.3.2 for better PHP support.

## 1.0.1 - 2017-11-06
### Improved
- rememberedUserSessionDuration is now optional.
- Fixed a DateTime checking issue causing unpredictable behavior.

## 1.0.0 - 2017-10-16
### Improved
- Validation feedback fix.
- Updated otphp to 9.0.2.

## 0.0.6 - 2017-07-18
### Improved
- Make csrf required to turn 2FA off.

## 0.0.5 - 2017-05-09
### Improved
- Fix for login popup on verify screen.

## 0.0.4 - 2017-05-09
### Improved
- Fix for looping back to the verify controller.

## 0.0.3 - 2017-05-05
### Changed
- Added our own vendor code.

## 0.0.2 - 2017-05-05
### Improved
- Fixes for installing through composer

## 0.0.1 - 2017-05-05
Initial Beta Release
