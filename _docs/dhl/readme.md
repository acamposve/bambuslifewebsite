# Integracion DHL

## Dashboard de servicios XML

https://xmlportal.dhl.com/

user: tingelmar.apps@gmail.com


### Credenciales

Bajo la seccion de ["Estado de Servicios XML"](https://xmlportal.dhl.com/userCustomerListing), tenemos el id de sitio.
Para usar el api precisamos el `id de sitio` y `clave de sitio`.
La `clave de sitio` nos llega por email la primera vez.

Para aplicar las credenciales hay que editar `dhl.js` en el `hhubserver`.

```js
var settings = {
  account_id: 717035665,
  site_id: '<id de sitio>',
  site_password: '<clave>',
  region_code: 'AM',
  requested_pickup_time: 'Y',
  new_shipper: 'Y',
  language_code: 'es',
  pieces_enabled: 'Y',
  shipping: {
    shipper_account_number: 717035665,
    shipping_payment_type: 'S',
    billing_account_number: 717035665,
    duty_payment_type: 'R'
  }
}
```
