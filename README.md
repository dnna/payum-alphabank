# Payum Alpha Bank Gateway

This gateway integrates Alpha Bank's redirect payment method. Factory parameters:
 - *mid*
 - *sharedSecretKey*


Optional factory parameters:
 - *lang* - Language. Default el.
 - *cssUrl* - URL to a CSS file that be used to customize Alpha Bank's checkout page. Default none.
 - *sandbox* - Whether this is Alpha Bank's sandbox environment. Default true.
 - *useMasterPass* - If true the payMethod passed to Alpha Bank is set to auto:MasterPass . Default false.

Notes:
 - mid and sharedSecretKey can also be passed in the "details" attribute of the payment model to override the factory values. This enables distributing payments to different Alpha Bank accounts on a per-payment basis depending on the business logic. If used in this way, it is recommended to set sharedSecretKey as `Payum\Core\Security\SensitiveValue` instead of string, to avoid saving the keys in the database.
 - The orderid passed to Alpha Bank is a randomized string and does not correspond to the actual order number. This enables the user to make multiple payment retries for the same order. The real orderid is passed in Alpha Bank's var2 field.
 - You can set var3 to var5 by setting custom3 to custom5 on "details" attribute.
 - To use the installments functionality, the fields "extInstallmentoffset" and "extInstallmentperiod" must be passed in the payment model's "details" attribute.

## License

This code is released under the [MIT License](LICENSE).
