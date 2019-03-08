# osCommerce CoinGate Plugin

## Install

Sign up for CoinGate account at <https://coingate.com> for production and <https://sandbox.coingate.com> for testing (sandbox) environment.

Please note, that for "Test" mode you **must** generate separate API credentials on <https://sandbox.coingate.com>. API credentials generated on <https://coingate.com> will **not** work for "Test" mode.

### via FTP

1. Download [oscommerce-coingate.zip](https://github.com/coingate/oscommerce-plugin/releases/download/v1.0.1/coingate-oscommerce-1.0.1.zip).
2. Extract downloaded zip. Upload `includes` directory and `coingate_callback.php` file to the root directory of your osCommerce installation.
3. Login to your osCommerce admin panel and go to Modules » Payment. Click Install module, then click Bitcoin via CoinGate and click Install module again.
4. Go to Modules » Payment, click Bitcoin via CoinGate and click Edit. Set Enable CoinGate module to True and Enable test mode to False. Enter [API Credentials](https://support.coingate.com/en/42/how-can-i-create-coingate-api-credentials) (App ID, Api Key, Api Secret) and configure other extension settings. Click Save.

To create new order statuses login to your osCommerce admin panel, go to Localization » Orders Status, click Insert, enter new order status name and click Save.
