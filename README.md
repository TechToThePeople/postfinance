

## How to use

### On PostFinance backend:

Create a test account, and login to the admin backend.
https://e-payment.postfinance.ch/ncol/test/admin_postfinance.asp

Open "Configuration" > "Technical information" in the menu.

- Global security parameters
  "Compose the string ...": "Each parameter followed by a passphrase."
  Hash algorithm: SHA-512 for instance
  Character encoding: UTF-8

- Data and origin verification
  Define the url of the merchant page, without subfolders, trailing slashes and spaces. ex:
  http://civilab.localhost/civicrm/contribute/transact;http://42.crm/civicrm/contribute/transact
  (TODO: No idea what the second url is doing here.)

  "SHA-IN Pass phrase": Randomize a string. random.org might help you.

- Transaction feedback
  Direct HTTP server-to-server request: Here define two url. The string after IPN can be different since we only use the post data. ex:
  http://www.example.com/civicrm/payment/ipn/ok?processor_name=postfinance&mode=test
  http://www.example.com/civicrm/payment/ipn/notok?processor_name=postfinance&mode=test

  "SHA-OUT Pass phrase": Use same as SHA-IN.
  (TODO: Yes, we will change this)


### On CiviCRM:

Install the extension
Administer > System settings > Manage extensions

Create one or more payment processors with type = PostFinance
Administer > System settings > Payment processors
Password: Set the SHA-IN / SHA-OUT passphrase.
(TODO: Yes, we will change this)

Create a contribution page that uses the payment processor
Contributions > Manage contribution pages


