

# How to use

## On PostFinance backend:

Create a test account, and login to the admin backend.
https://e-payment.postfinance.ch/ncol/test/admin_postfinance.asp

Open "Configuration" > "Technical information" in the menu.

### Conf. > Techn. Inf. > Global security parameters

- "Compose the string ...": "Each parameter followed by a passphrase."
  Hash algorithm: SHA-512
  Character encoding: UTF-8

### Conf. > Techn. Inf. > Data and origin verification

- Define the url of the merchant page, without subfolders, trailing slashes and spaces. ex:
  http://civilab.localhost/civicrm/contribute/transact;http://42.crm/civicrm/contribute/transact
  (TODO: No idea what the second url is doing here.)

- "SHA-IN Pass phrase":
  Randomize a string, and remember it for later. Can be e.g. 30 characters.
  random.org might help you (slice and recombine, if you don't trust random.org).

### Conf. > Techn. Inf. > Transaction feedback

- HTTP redirection in the browser
  (leave this empty for now)

- Direct HTTP server-to-server request

  Request type: Always online

  URL of the merchant page. E.g.
  Accept: http://www.example.com/civicrm/payment/ipn/ok?processor_name=postfinance&mode=test
  Cancel: http://www.example.com/civicrm/payment/ipn/notok?processor_name=postfinance&mode=test
  (The CiviCRM payment processor will only use the POST data. It does not care
  about "ok" or "notok" after the "ipn".)

  Method: POST

- "SHA-OUT Pass phrase":
  Again, randomize a string, and remember it for later.


### Conf. > Account > Currencies

Add the currencies of your choice.
(Recommended: CHF, EUR, USD)

### Conf. > Payment methods

Add the payment methods of your choice.
(e.g. VISA)


## On CiviCRM:

### Install the extension

Administer > System settings > Manage extensions

### Create payment processor(s)

Administer > System settings > Payment processors

- Payment Processor Type = PostFinance

- Processor details (Live / Test)
  PSPID (Merchant ID): The name you use to login on the postfinance backend
  SHA-IN pass phrase: Same as above
  SHA-OUT pass phrase: Same as above

### Adjust localization / currency settings

Administer > Localization > Languages, Currency, Locations

Suggested (optional) settings:

- "Default Currency": EUR or CHF (instead of USD).
- "Available Currencies": Add EUR and CHF.
- "Default Country": Switzerland (make your choice)
- "Available Countries": Add Switzerland, and your personal choice of EU countries.


### Test: Create a contribution page

Contributions > New contribution page

Tab: Amounts
- Currency: EUR or CHF
- Payment Processor: PostFinance
- Contribution Amounts section enabled: Yes
- Allow other amounts: Yes

Note: In some cases the "Payment Processor" checkboxes do not show up.
Usually this is because one of the previous steps was not done correctly.

Contributions > Manage Contribution Pages

You see a list of contribution pages. For the one you just created, click the
"Links" on the right column, then in the pop-up menu click "Test-drive".

On the test-drive contribution page:
- Contribution Amount: E.g. 55 (EUR)
- Email: Your email



## Troubleshooting

On PostFinance backend:
https://e-payment.postfinance.ch/ncol/test/admin_postfinance.asp

Configuration > Error logs

"unknown order/1/s/":
Usually this means a mismatch of the SHA

"unknown order/1/r/":
Usually this means you have the wrong referer url.
