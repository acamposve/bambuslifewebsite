# Bambus.Life Website

# Command line

Run server with assets watch:  
`npm run serve`
  
Build for production:
`npm run build`
`dep deploy production`
  
Build  Sitemap:
`bin/console presta:sitemaps:dump`

# DHL

Contacto: LEVI => 2604 1332 42702


# MercadoPago

## Transaction check

https://api.mercadopago.com/v1/payments/19722806?access_token=TEST-3945512895144203-022509-d3458a75fa3768bbe00cbda3b9790ffb-407141024

## Sanbox Test data
master:
5161 4413 1585 2061

visa:
4157 2362 1173 6486


En card holder name poner como prefijo:
```
    APRO: Pago aprobado.
    CONT: Pago pendiente.
    CALL: Rechazo llamar para autorizar.
    FUND: Rechazo por monto insuficiente.
    SECU: Rechazo por código de seguridad.
    EXPI: Rechazo por fecha de expiración.
    FORM: Rechazo por error en formulario.
    OTHE: Rechazo general.
```

## Payment Object <MercadoPago\Payment>
  - id                 = 18061828
  - status             = 'approved'
  - status_detail      = 'accredited'
  - date_created       = 2019-03-05T20:54:33.000-04:00
  - date_last_updated  = 2019-03-05T20:54:33.000-04:00
  - date_approved      = 2019-03-05T20:54:33.000-04:00
  - money_release_date = 2019-03-26T20:54:33.000-04:00
  - currency_id        = UYU
  - transaction_amount = 140
  - transaction_amount_refunded = 0
  - payer <MercadoPago\Payer>
    - id            = 413492379-qYU2eKCGZL3o2v
    - type          = 'registered'
    - first_name    = 'Test'
    - last_name     = 'Test'
    - email         = 'test_user_80507629@testuser.com'
    - phone <stdClass>
      - area_code = '01'
      - number    = '1111-1111'
      - extension = ''
    - identification <stdClass>
      - number = '32659430'
      - type   = 'DNI'
    - address  = NULL
  - payment_method_id = 'visa'
  - payment_type_id   = 'credit_card'
  - transaction_details <stdClass>
    - net_received_amount = 128.91
    - total_paid_amount   = 140
    - overpaid_amount     = 0
    - installment_amount  = 140
  - fee_details <stdClass>
    - type      = 'mercadopago_fee'
    - amount    = 11.09
    - fee_payer = 'collector'
  - card <MercadoPago\Card>
    - id               = NULL
    - customer_id      = NULL
    - expiration_month = 12
    - expiration_year  = 2022
    - first_six_digits = 415723
    - last_four_digits = 6486
    - payment_method   = NULL
    - security_code    = NULL
    - cardholder <stdClass>
      - name            = 'GABRIEL MORAES'
      - identification <stdClass>
        - number        = 32722703
        - type          = 'CI'
    - date_created      = '2019-03-05T20:54:33.000-04:00'
    - date_last_updated = '2019-03-05T20:54:33.000-04:00'
  - statement_descriptor = 'TINGELMARSA'
  - refunds              = 0
  - shipping_amount      = 0
  - coupon_amount        = 0
  - token                = '36244e8987a1420b9489b37bcfb313be'
  - issuer_id            = 1084
  - processing_mode      = 'aggregator'

## Card Object <MercadoPago\Card>
  - id               = '1551833673895'
  - customer_id      = '413492379-qYU2eKCGZL3o2v'
  - expiration_month = 12
  - expiration_year  = 2022
  - first_six_digits = '415723'
  - last_four_digits = '6486'
  - payment_method <MercadoPago\PaymentMethod>
    - id               = 'visa'
    - name             = 'visa'
    - payment_type_id  = 'credit_card'
    - thumbnail        = 'http://img.mlstatic.com/org-img/MP3/API/logos/visa.gif'
    - secure_thumbnail = 'https://www.mercadopago.com/org-img/MP3/API/logos/visa.gif'
  - security_code <stdClass>
    - length        = 3
    - card_location = 'back'
  - issuer <stdClass>
    - id     = 1084
    - name   = 'Visa'
  - cardholder <stdClass>
    - name   = 'GABRIEL MORAES'
    - identification <stdClass>
      - number = '32722703'
      - type   = 'CI'
  - date_created      = '2019-03-05T20:53:42.000-04:00'
  - date_last_updated = '2019-03-05T20:54:33.000-04:00'
  - token             = '36244e8987a1420b9489b37bcfb313be'
  - user_id           = '413492379'
  - live_mode         = FALSE




