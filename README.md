# PaystackPay
This is the payment plugin that integrates PayPerDownload component of Joomla! with Paystack payment gateway.
### What is PayPerDownload?
With Pay per Download component you can control access to resources on your site based on users’ membership. You can have different types of memberships, with different levels, prices and expiration dates.
- Read about the component [here](http://www.ratmilwebsolutions.com/category/11-pay-per-download.html).
- Review their JED listing [here](http://extensions.joomla.org/extensions/e-commerce/paid-downloads/18146).
### What is Paystack?
Paystack provides modern online and offline payments for Africa. Paystack helps businesses in Africa get paid by anyone, anywhere in the world. And its free to sign up too! [Join](https://dashboard.paystack.com/#/signup) now.
## Requirements
 - An account with Paystack.com. Sign up [here](https://dashboard.paystack.com/#/signup).
- A Joomla! 3 website (of course).
- PayPerDownload component installed on the site. You can download the component [here](http://www.ratmilwebsolutions.com/category/11-pay-per-download.html).

## Installation
- Download the plugin.
- Log into the back end of your Joomla! site.
- On the Control Panel page, click "Install Extensions" > "Upload Package File" OR from the top menu, click Extensions > Manage > Install > Upload Package File.

## Setup
After successful installation, open the plugin by going to Extensions > Plugin, then searching for it.
- Fill all the fields in the plugin, publish it, and save.
- Open the PPD component configuration by going to Components > Pay Per Download > Configuration.
- On the "Gateway Settings" tab, set "Use PayPal" to NO, "Use Payment Gateway Plugin" to YES.

### Constraints
- (In version 1.0): Nigerian NGN currency code is not in the PPD component by default. I had to add Currency Code manually to PPD (see how to [here](http://www.ratmilwebsolutions.com/forum/4-payperdownload-support/2545-how-to-add-new-currency.html) ). I had to also add a field for capturing currency code in the payment plugin because of this. I hope I can remove the field in later versions.

## Update
- (In version 1.3.0): added support for taxes, removed duplicate values, fixed wrong return fee, and removed firstname/lastname which could be incorrect (handled by the payment gateway) (By Olivier).

- (In version 1.1): The PayPerDownload plugin version 6.1.0 now factors in a dropdown for you to select your currency. This plugin has been updated to reflect that upgrade (Thanks Olivier and Ratmil!).
