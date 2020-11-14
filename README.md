# CITANZ SilverStripe eCommerce
No doc at this stage - use on your own risk.
## How does it work?
It doesn't...

## Demo?
https://demo-shop.cita.nz/

## Email Configuration
```
SilverStripe\Control\Email\Email:
  noreply_email:
    noreply@yourdomainname.com: 'Sitename'
```

## Cronjob
set up a cronjob to purge pending carts (on a daily basis)

## Product
`Cita\eCommerce\Model\Product`

## Order
`Cita\eCommerce\Model\Order`
### implement `createInvoiceRows` function in extension to create your own invoice rows

## Payment settings
Payment is using Omnipay and its plugins. We have implemented 5 payment gateways in this module.

```
---
Name: 'citanz-silverstripe-ecommerce-payment'
---
SilverStripe\Omnipay\Model\Payment:
  file_logging: true
  # allowed_gateways:
    # - PaymentExpress_PxPay
    # - PayPal_Express
    # - Poli
    # - Paystation_Hosted
    # - Stripe
```
To enable the payment gateway that you wish to use, create a `payment.yml` file in your '_config' directory, and uncomment the line(s) accordingly. Example:
```
---
Name: 'payment'
---
SilverStripe\Omnipay\Model\Payment:
  file_logging: true
  allowed_gateways:
    - PaymentExpress_PxPay
```

### Stripe
...

Make sure you require Stripe's v3 library in your template
```
Requirements::javascript('https://js.stripe.com/v3/');
```

### turn off order's default buttons:
Choose which one(s) you wish to turn off, and set the value(s) to false
```
Cita\eCommerce\Model\Order:
  default_buttons:
    send_invoice    :   true
    cheque_cleared  :   true
    refund          :   true
    send_tracking   :   true
    debit_cleared   :   true
```

## Email sending
If you want to customise emails, please implement below methods:
- SendCustomerEmail($from, $to, $str, $customer_sent_flag)
- SendAdminEmail($from, $to_admin, $str, $admin_sent_flag)

and make sure you update the 'sent' prop in $customer_sent_flag & $admin_sent_flag to `true`

## Checkout values
### GST
GST calculation is based on the subtotal amount AFTER the discount (is there is one) plus shipping cost.

### Shipping cost
- Shipping cost IS NOT included in GST calculation
- Shipping cost DOES NOT accept discount (if you want to give the freight provider money, you extend the classes and customise the calculation and take manage your own calculation from there.)

### Templating
If you would like to create your own cart templates, please override below files:

```
Cita\eCommerce\Controller\Layout\Cart.ss
Cita\eCommerce\Controller\Layout\Cart_checkout.ss
Cita\eCommerce\Controller\Layout\Cart_complete.ss
Cita\eCommerce\Model\Layout\Catalog.ss
Cita\eCommerce\Model\Layout\Product.ss
Cita\eCommerce\Model\Layout\ProductCollection.ss
```




## FAQ
### Test cards?
#### POLi
Username: DemoShopper

Password: DemoShopper

#### Payment Express
Card: 4111111111111111

Card Holder: YOUR_NAME

Expiry: [leave it as it is]

CVV: 100

#### Paystation
Card: 5555555555554444

Card Holder: YOUR_NAME

Expiry: 0521

CVV: 100

#### Stripe
Card: 4000005540000008

Expiry: 0555

CVV: 555

### Why Payment Express method rounds my payable total (or amount shows up on the payment gateway is different from what's on the checkout grand total)?
When on sandbox mode, Payment Express only allows integer value to be the amount to pay, therefore we have to round the amount before we pass it to Payment Express's payment gateway.
