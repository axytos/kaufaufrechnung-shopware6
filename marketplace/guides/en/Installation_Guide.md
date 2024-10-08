---
author: axytos GmbH
title: "Installation Guide"
subtitle: "axytos Buy Now Pay Later for Shopware6"
header-right: axytos Buy Now Pay Later for Shopware6
lang: "en"
titlepage: true
titlepage-rule-height: 2
toc-own-page: true
linkcolor: blue
---

## Installation Guide

The plugin provides the payment method purchase on account for shopping in your Shopware shop.

Purchases made with this payment method may be accepted by axytos up to receivables management.

All relevant changes to orders with this payment method are automatically sent to axytos.

Adjustments beyond the installation, e.g. of invoice and e-mail templates, are not necessary.

For more information, visit [https://www.axytos.com/](https://www.axytos.com/).


## Requirements

1. Contractual relationship with [https://www.axytos.com/](https://www.axytos.com/).

2. Connection data to connect the plugin to [https://portal.axytos.com/](https://portal.axytos.com/).

In order to be able to use this plugin, you first need a contractual relationship with [https://www.axytos.com/](https://www.axytos.com/).

During onboarding you will receive the necessary connection data to connect the plugin to [https://portal.axytos.com/](https://portal.axytos.com/).

Plugin installation via the Shopware Store

1. Buy and add the plugin in the Shopware Store within your Shopware distribution for free.

2. Switch to the administration of your Shopware distribution. The Axytos Purchase On Account plugin is listed under Extensions > My Extensions > Apps.

3. Run Install App.

You can buy and add the plugin for free via the Shopware Store within your Shopware distribution.

Once added, it will be listed under My Extensions.

Run Install App.

The plugin is now installed and can be configured and activated.

In order to be able to use the plugin, you need valid connection data for [https://portal.axytos.com/](https://portal.axytos.com/) (see requirements).


## Plugin and shop configuration in Shopware

1. Switch to the administration of your Shopware distribution. The Axytos Purchase On Account plugin is listed under Extensions > My Extensions > Apps.

2. Execute configuration in the three-point menu of Axytos Purchase on account.

3. Choose API Host, either 'Live' or 'Sandbox'.

4. Enter API key. You will be informed of the correct value during the onboarding of axytos (see requirements).

5. Execute save.

6. Run Test API Connection.

7. If the connection test fails, please get in touch with your contact person at axytos.

8. If the connection test ends successfully, you are done here. The payment method must now be activated in the storefront.

9. Switch to storefront.

10.  Under Storefront > Payment and shipping > Payment methods select the payment method Purchase on account | Activate Axytos purchase on account.

For configuration, you must save valid connection data to [https://portal.axytos.com/](https://portal.axytos.com/) (see requirements), i.e. API host and API key for the plugin.

Then run Test API Connection.

If the connection test fails, please get in touch with your contact person at axytos, if not you are done here.

Now activate the payment method purchase on account | Axytos purchase on account in the storefront.


## Can't select purchase on account for purchases?

Check the following points:

1. The Axytos purchase on account plugin is installed.

2. The Axytos purchase on account plugin is activated.

3. The Axytos purchase on account plugin is configured with correct connection data (API host & API key).

4. The Axytos purchase on account plugin is activated as a payment method in the sales channel concerned.

Check the correctness of the connection data with Test API connection.

Incorrect connection data means that the plugin cannot be selected for purchases.

