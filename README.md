# UnzerPayment

Unzer payment integration for Shopware 5 including the following payment methods:
* Alipay
* Credit Cards (Mastercard, Visa, Amex, China Union Pay, JCB, Diners/Discover)
* EPS
* Direct
* Giropay
* Instalment (secured)
* iDEAL
* Invoice
* Invoice (secured)
* PayPal
* Prepayment
* Przelewy 24
* SEPA direct debit
* SEPA direct debit (secured)
* SOFORT
* WeChat Pay

## Installation
### For production
1. Upload the plugin files into the `custom/plugins` folder in your shopware installation.
2. Inside the plugin directory `custom/plugins/UnzerPayment` run `composer install --no-dev`
3. Switch to backend and install the plugin using the Shopware plugin manager and configure it as you need.

### For development
1. Clone the plugin repository into the `custom/plugins` folder in your shopware installation.
2. Inside the plugin directory run `composer install`
3. Go to the plugin manager and install/activate the plugin.

## Configuration
After the actual plugin installation it is necessary to activate the new payment methods and add them to the desired shipping methods.

Further information and configuration you can find within the <a href="https://dev.unzer.de/handbuch-shopware-ab-5-6/" target="_blank">manual</a>

## Migration from HeidelPayment to UnzerPayment
1. Uninstall the Heidelpay plugin. Make sure that the stored data of the plugin is <strong>not</strong> deleted.
1. Install and configure the Unzer plugin.
1. Activate the Unzer payment methods and assign them to the corresponding shipping methods.
1. Orders placed with the Heidelpay plugin can now be processed as usual via the Unzer plugin in the Shopware backend.
