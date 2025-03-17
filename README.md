# Unzer Payment plugin for Shopware 5

Use Unzer Payment plugin for Shopware 5 to provide an easy-to-install payment gateway integration for all your online payments.

## Description

Accept payments with cards, bank transfers, wallets, and other global and local payment methods. Unzer Payment plugin helps you with quick and easy integration, full support, and flexible solutions that grow with your business. We are your payment partner for every situation.

## Features

* Seamless integration into the Shop-system
* Merchant-friendly order management with up-to-date payment details, real-time billing and refunds made easy.
* Payment processing via the Unzer Payment API
* 3D-Secure authentication
* PCI-DSS Level 1 certified

## Content security policy (CSP)

If you are using a Content Security Policy (CSP) you must include different Unzer URL's to your policy, which are required by the UI components to work. For more information, please go to [Unzer Documentation CSP Information](https://docs.unzer.com/online-payments/ui-component-v2/#content-security-policy-csp).

## Supported payment methods

Unzer payment plugin includes the following payment methods:
* Alipay
* Apple Pay
* Bancontact
* Credit Card
* Unzer Direct Bank Transfer
* EPS
* Google Pay
* iDEAL
* PayPal
* Prepayment
* SEPA Direct Debit
* SOFORT
* TWINT
* Unzer Direct Debit
* Unzer direct Debit (secured)
* Unzer Invoice B2C / B2B (secured)
* Unzer Installment (secured)
* WeChat Pay

Regarding plugin compatibility, please take a look at the release notes for more information.

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

Further information and configuration you can find within the [manual](https://docs.unzer.com/plugins/shopware-5/).

## Migration from HeidelPayment to UnzerPayment
1. Uninstall the Heidelpay plugin. Make sure that the stored data of the plugin is **not** deleted.
1. Install and configure the Unzer plugin.
1. Activate the Unzer payment methods and assign them to the corresponding shipping methods.
1. Orders placed with the Heidelpay plugin can now be processed as usual via the Unzer plugin in the Shopware backend.

## User Guide

Please find information on installation, configuration, usage etc on our [documentation pages](https://docs.unzer.com/plugins/shopware-5).

## Support

For any issues or questions please get in touch with our support.

**Email**: support@unzer.com

**Phone**: +49 (0)6221/6471-100

**Twitter**: [@UnzerTech](https://twitter.com/UnzerTech)

**Webpage**: https://unzer.com/

