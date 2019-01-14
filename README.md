# krokedil-assign-customer-to-order-for-woocommerce
Creates customer account during new WooCommerce order if email doesn't exist in an existing account. If account does exist, the customer ID will be tagged to the order even if customer isn't logged in.

## Configuration
Under --> WooCommerce --> Settings and the Accounts & Privacy tab make sure to have the following settings:
* _Allow customers to place orders without an account_ should be checked.
* _Allow customers to log into an existing account during checkout_ should **NOT** be checked.
* _Allow customers to create an account during checkout_ should **NOT** be checked.

## Changelog
1.1.0 			- 2019-01-14
* Enhancement	- Saves order address data in customer when creating a new user.

1.0.0 
* Initial release.

