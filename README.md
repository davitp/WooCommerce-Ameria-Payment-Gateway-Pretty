# WooCommerce Ameriabank Payment Gateway Pretty

### The plugin works with WooCommerce, the functionality includes adding an additional payment option for checkout, using Ameriabank third-party payment system.

* This plugin allows you to add an additional payment system to your WooCommerce website, using Ameriabank payment gateway.
* You can edit settings in dashboard by going to WooCommerce->Settings->Checkout. Scroll down to the bottom of the page where you should  activate it and fill in the settings.
* With this plugin you can put your credentials from the admin side without a single touch of a code.
* I also provide a testing mode in the plugin settings.
* If you want to test, check the testing mode checkbox on the settings page.
* When you install the plugin, you should fill the 'Starting Order ID' field in settings. It is required and is done only once.
* If you use testing mode, put something random in the 'Starting Order ID' field(e.g. 896542)
* If you’re using the plugin on  production mode then just put 1 (if you’re using your Ameriabank gateway first time) or the ID of your last order in Ameriabank VPOS system.
* If after pressing the payment button you're not allowed to pay, most of the times the problem is in the 'Starting Order ID'. Try to put  another number there.
* Client ID, Username and Password must be provided by Ameriabank.
* Updates and changes to the plugin are welcome. If you have a proper edited code, feel free to fork the repository and make your changes.

### Known problems.
* Please try to put unique Starting Order ID, when you are in test mode(remember that the other users use ARCA system too, try to find the unique ID) and also for production (here the Starting Order ID is only yours). 
* While testing, try to do transactions with up to 5 AMD amount (read the file Ameriabank sent to you, there will be the max price you can set for the testing)

### You can see all settings here in the screenshot

![N](https://raw.githubusercontent.com/uptimex/WooCommerce-Ameria-Payment-Gateway-Pretty/master/screenshot.jpg)
