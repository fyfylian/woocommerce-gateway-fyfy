![Fyfy Pay Checkout](https://raw.githubusercontent.com/fyfy/woocommerce-gateway-fyfy/master/.wordpress-org/banner-1544x500.png)

# Fyfy Pay Checkout for WooCommerce

A plugin for Wordpress to handle WooCommerce payments in the Fyfy (FYFY), Bitcoin (BTC), and Ethereum (ETH) cryptocurrency.

**Features Include:**

* Bitcoin, Ethereum and Fyfy support
* Automatic conversion from supported store currencies like USD or EUR to crypto at latest market prices
* Full order status feedback in your WooCommerce panel
* Decentralized and non-proprietary
* Configurable conversion and validation service providers
* Configurable confirmation times with sensible defaults

## Installation

1. Be sure you're running WooCommerce 3.5 or higher in your shop.
2. Search for "fyfy" in your Wordpress plugin section
3. Activate the plugin through the **Plugins** menu in WordPress.
4. Go to **WooCommerce &gt; Settings &gt; Payments** and select the "Fyfy" method to configure this plugin.

[Check this in-depth tutorial for support.](https://fyfy.github.io/tutorials/wordpress-payment-plugin-installation)

## Changelog

Please see the Changelog section in [readme.txt](readme.txt).

## Development

### Adding A New Validation Service

Validation services are defined under [`./validation_services/`](./validation_services/). Each service class must implement the `WC_Gateway_Fyfy_Validation_Service_Interface`, defined in [`./validation_services/interface.php`](./validation_services/interface.php). The easiest way to start is to take an existing service (e.g. [`fyfy_watch.php`](./validation_services/fyfy_watch.php)) and rename and adapt it to the new service. The new service then also needs to be registered in the respective `validation_service_<currency>` setting. The value of the setting must match the file name (without the `.php` extension) of the service definition. If the new service requires additional setting fields, [`settings.js`](./js/settings.js) also needs to be adapted to show/hide those fields conditionally.

## Acknowledgement

This Fyfy gateway is based on skyverge's [WooCommerce Offline Gateway](https://github.com/bekarice/woocommerce-gateway-offline), which in turn forks the WooCommerce core "Cheque" payment gateway.
