
### FALTA
 + PageSpeed
   + Add PurgedCSS
   + Agregar descriptions!!
   + Agregar structured data: 
     + Corporate Contact: https://developers.google.com/search/docs/data-types/corporate-contact (FALTA TELEFONO!!)
     + FAQ: https://developers.google.com/search/docs/data-types/faqpage
     + Product: https://developers.google.com/search/docs/data-types/product
     + Bradcrumb: https://developers.google.com/search/docs/data-types/breadcrumb

### TEST
#### -------- COMPRAS EN UY
1. Compra Active UY (global)
   User   : arondella+pacuy04111903@tingelmar.com
   Card   : 5161 4413 1585 2061
   A. Delivery (creacion de device y subscripcion)
   B. Cobro de mensualidad (cuota 1)

2. Compra Active UY (local)
   User    : arondella+pacuy04111904@tingelmar.com
   A. Delivery (creacion de device y subscripcion)
   B. Cobro de mensualidad (cuota 1)

3. Registro de medico
   User   : arondella+doc@tingelmar.com

4. Compra Pro UY
   User   : arondella+docpro@tingelmar.com
   Device : MM5092
   Organizacion : 61
   Paciente 1   : 871

5. Compra Pro UY (2)
   User   : arondella+docpro2@tingelmar.com
   Device : MM5091
   Organizacion : X
   Paciente 1   : X

#### -------- COMPRAS EN CL 
1. Compra Active CL (global)
   User   : arondella+paccl04111903@tingelmar.com
   Card   : 5031 7557 3453 0604 (123 - 11/25)
   A. Delivery (creacion de device y subscripcion)
   B. Cobro de mensualidad (cuota 1)
     19,97 + 3,79 = 23,76 ( muestra: 23,77 )

1. Compra Active CL (local)
   User    : arondella+paccl04111904@tingelmar.com
   A. Delivery (creacion de device y subscripcion)
   B. Cobro de mensualidad (cuota 1)


https://api.mercadopago.com/v1/payments/22489289?access_token=TEST-4450788232191490-042612-40ccc8aacefa128d70ac5e97783b8b78-429640315

#### -------- PLAN FAMILIAR
 OwnerId = 540 (PiD: 1089) arondella+paccl05111901@tingelmar.com
 Invited = 535 (PiD: 1084) arondella+paccl04111901@tingelmar.com
 appointmentDevicesId : 390

### POST VENTA
  user arondella+enzo@tingelmar.com
  http://localhost:8000/uy/compra/J2YxVMRDaNTmLLQklxnQ/-



## 0.9.11 - BETA - 2021-09-24
### Fix
 - Se ajusta banner y landing de casmu

## 0.9.10 - BETA - 2021-09-24
### New
 - Se cambia el banner del home

## 0.9.9 - BETA - 2021-09-23
### New
 - Se agrega página landing de sorteo de casmu

## 0.9.8 - BETA - 2021-09-23
### New
 - Se cambia titulo de home
 
## 0.9.7 - BETA - 2021-05-03
### New
 - Se publica seccion de prensa

## 0.9.6 - BETA - 2021-03-11
### New
 - Se ajusta seccion de prensa

## 0.9.5 - BETA - 2021-03-01
### New
 - Se agrega seccion de prensa

## 0.9.4 - BETA - 2020-09-28
### New
 - Se vuelve al banner de Covid.

## 0.9.3 - BETA - 2020-09-25
### New
 - Se modifica para mostrar los precios cuando no hay stock en CSActive y CSPro.

## 0.9.2 - BETA - 2020-09-21
### New
 - Se ajusta logo en metas de favicon y se agrega manifest.
 - Se agregan tags de OpenGraph

## 0.9.1 - BETA - 2020-09-16
### New
 - Se agrega descarga de brochures
 - Se agrega registro de paciente
 - Se agrega opcion de registro de pacientes y medicos en el menu principal

### Fixes
 - Se corrige zona horaria de citas en perfil

## 0.9.0 - BETA - 2020-09-07
### New
 - Se agrega nueva pagina para completar el registro de nuevo usuario paciente.

## 0.8.4 - BETA - 2020-08-31
### Fixes
 - Se corrige envio de mail en reserva

## 0.8.3 - BETA - 2020-08-26
### New
 - Se agrega reCaptcha a formulario de registro de medicos. 

## 0.8.2 - BETA - 2020-08-24
### Fixes
 - Se ajusta mostrado de error en registro de medicos

## 0.8.1 - BETA - 2020-08-13
### Fixes
 - Se ajustan fechas en listados en perfil
 - Se agrega `showSetNickname` en control de video en pagina de cita.
 - Se ajustan botones de volver en wizard de reservas

## 0.8.0 - BETA - 2020-08-05
### New
 - Se agrega reserva de cita con soporte de video

## 0.7.2 - BETA - 2020-06-25
### New
 - Se quita el precio de lsa derivaciones en pagina de order de pro. 

## 0.7.1 - BETA - 2020-05-25
### Fixes
 - Se corrige link a pagina de accesorios

## 0.7.0 - BETA - 2020-05-22
### New
 - Nuevo diseño de paginas home, entidades medicas.
 - Se agrega pagina de Covid y portal médico

## 0.6.1 - BETA - 2020-05-07
### New
 - Se agrega pagina de accesorios
 - Compra de informe de diagnostico
 - Ajuste de calculo de subtotal:
   - OrderView - calculo de impuestos y subtotal
   - CartView  - calculo de impuestos y subtotal
 - Se agrega pedido de datos de identificacion de usuario y de empresa en el registro
 - Se modifica el calculo de stock en paginas de orden para solo mostrar local

## 0.7.0 - BETA - 2020-07-21 (Booking)
### New
 - Poder reservar una cita con un medico
 - Pagina de cita
 - Listado de reservas
 - Soporte de video consulta

## 0.6.0 - BETA - 2020-03-16
### New
 - [WIP] Se agrega pagina de accesorios (oculto)
 - Nuevas opciones de compra para CS Pro.
 - Se quita textos de cancelable en cualquier momento de Pro
 - [WIP] Compra de informe de diagnostico (oculto aun la solicitud)

## 0.5.2 - BETA - 2020-02-14
### New
 - Se mejoran paginas de reseteo de contraseña
 
### Fixes
 - Se corrige login para traer datos de usuario

## 0.5.1 - BETA - 2020-01-16
### Fixes
 - Se corrigen bugs en pagina de perfil

## 0.5.0 - BETA - 2020-01-16
### New
 - Se agrega boton de solicitud de informe en observaciones (listado y view)
 - Se agrega estado de solicitud en observaciones (listado y view)
 - Nueva pagina de solicitud de informe
 - Soporte para mostrar estado de suscripcion Trialing
 - Se deja oculto: Medico tratante y Solicitud de informe

## 0.4.3 - BETA - 2019-12-30
### New
 - Se cambia direccion de correo de envio de formulario de contacto.

## 0.4.2 - BETA - 2019-12-26
### New
 - Se re-habilita la venta de USB-C
 - Se agrega logo de ANII

### Fixes
 - Se corrigen ortografias en home
 - Se ajusta menu principal en mobile.

## 0.4.1 - BETA - 2019-12-16
### New
 - Se ajusta texto de que incluye el registro inicial active y el beneficio temporal de CEP hasta 15 de enero por 30%.

## 0.4.0 - BETA - 2019-12-11
### New
 - Se agrega soporte de plan familiar
 - Ajustes de textos en registro de medico y plan familiar
 - Se mejora popup de ingreso de cupones.

### Fixes
 - Se corrige recuperacion de password para soportar caracteres especiales (como el +)

## 0.3.5 - BETA - 2019-11-13
### New
 - Se quita promo de devices pro
 - Se saca la opcion de USBC de UY
 - Se cambia Personas por Paciente y Familia en Home y pag de producto CS Active
 
### Fixes
 - Se corrige mostrado de fecha de informe en listado de observaciones
 - Se ajusta mostrado de tarjetas para Chile
 - Se ajusta duracion de garantia en pagina de garantia de HW


## 0.3.4 - BETA
### New
 - Se agrega nuevo documento de permutacion de electrodos
 - Se actualiza documento de antecedentes cientificos a v5.0
 - Se actualiza logo de CardioSecur
 - Se agrega ITC como distribuidor
 - Se actualizan credenciales de test de cuenta de MPCL.

### Fixes
 - Se corrige mostrado de dialog de cupones en checkout.

## 0.3.3 - BETA
### Fixes
 - Se corrige verificacion de claves en cambio de clave.
 - Se ajusta mostrado de error en login

## 0.3.2 - BETA
### New
  - Se agrega aclaracion de producto local o importado
  - Se agrega soporte para cuentas de MP locales.

### Fixes
  - Se ajusta calculo de precios en orders

## 0.3.1 (i18n) - BETA
### New
  - Se agrega opcion de cupones genericos

## 0.3.0 (i18n) - BETA 2
### New
  - Se cambia mostrado de precios y mensajes de disclaimer

## 0.3.0 (i18n) - BETA 1
### New
  - Se agrega email a contactos de pacientes.
  - Se corrige estilo de combos.
  - Se deduce el support_party utilizando el country del usuario

### Fix
  - Se agrega manejo de urls legales sin locale (caso terminos y condiciones del footer del mail)

## 0.3.0 (i18n) - ALPHA 6
### New
  - Se agrega nuevo login/registro en checkout

## 0.3.0 (i18n) - ALPHA 5
### New
  - Ajustes de contenidos para lanzamiento
  - Resolucion geo-ip.

## 0.3.0 (i18n) - ALPHA 4
### New
  - Ajustes de contenidos para lanzamiento

## 0.3.0 (i18n) - ALPHA 3
### New
  - Se agrega localizacion por locale (solo pais, no idioma)
    - Al iniciar sesion se mueve al pais que se registro el usuario
    - El usuario puede cambiar de pais y comprar en otro pais.
  - Soporte de IntelliPurchase para soportar mutliples ordenes (internacionales y locales)
    - Nueva pagina de confirmacion de orden
    - Nuevos calculos de carrito y descuentos
  - Soporte para pickup y multiples direcciones de entrega
  - Se quitan los adicionales (Electrodos e interpretacion) de pagina de Active
    (con esto se quita la advertencia de incluir la interpretacion)
  - Se quita el aviso de la compra de electrodos en mercado local
  - Pagina de perfil
    - Nuevo Look & Feel
    - Seccion de medico de referencia
    - Listado de ECGs y sus informes para descargar
  - Nueva pagina de seleccion de medico de referencia
  - Nueva pagina de registro de medicos prestadores de servicios (por ahora solo informes de ECG)

## 0.2.6 (Public BETA) | 2019-07-17
### Fixes
  - Ajustes de contenido para paginas home, prod_active y prod_pro.
  
## 0.2.5 (Public BETA) | 2019-07-10
### New
  - Se agrega Metas de OpenGraph en active, pro, para entidades, compra active, compra pro, home
  
### Fixes
  - Ajustes de contenido y L&F en home, pagina de entidades y footer.

## 0.2.4 (Public BETA) | 2019-07-06
### New
 - Se agrega pagina para organizaciones (FALTA TAG DESCRIPTION ! )
 - Se ajusta L&F y se agregam video en home.
 - Se ajusta Timeout de NgageClient.
 - Se agrega informacion de proveedores de electrodos en paginas de orden de productos.

## 0.2.3 (Public BETA) | 2019-07-04
### New
 - Se agrega SD de organizacion en todas las paginas.
 - Se agrega SD de breadcrumbs para paginas de productos y compra de productos
 - Se cambian imagenes de aleman por espaniol
 - Se agrega logo de productos en home.
 - Se ajusta texto de price tag disclaimer.

## 0.2.2 (Public BETA) | 2019-07-02
### Fixes
 - Se ajusta generacion de URLs con HTTPS en sitemap

## 0.2.1 (Public BETA) | 2019-07-02
### New
 - Se agregan certificados en Home.
 - Se agrega sitemap

### Fixes
 - Se ajusta direccion de oficina en footer
 - se corrige calculo de precio en order/csactive cuando el primero esta deshabilitado

## 0.2.0 (Public BETA) | 2019-07-01
### New
 - Se agrega price tag disclaimer.

### Fixes
 - Se corrigen links legales de footer
 - Se quitan referencias a entrega de electrodos
 - Se cambia $_ENV a $_GLOBAL
 - Se ajusta script de deploy

## 0.1.8 (BETA) | 2019-07-01
### New
 - Se actualizan paginas de acerca, politicas para lanzamiento.
 - Se actualiza pagina de home.

### Fixes
 - Ajustes de pagina de producto de CS Active

## 0.1.7 (BETA) | 2019-06-14
### New
 - Se pide el cambio de moneda sin fecha para usar la fecha del server HHub.
 - Se agrega contenido de acerca y politica.
 - Se agrega clave publica de MP en env y se carga desde JS desde Twig
 - Se agrega clave privada de MP en env y se carga desde Controller
 - Se agrega mostrado de impuestos en pagina de detalle de subscripcion.

## 0.1.6 (BETA) | 2019-06-03
### Fixes
 - Se agrega binary_mode en MercadoPago.
 - Ajustes para soporte de registro de comisiones a traves de JounralEntries
 - Se quita el telefono de BambusLife en la pagina de factura.
 - Se usa el monto sin redondear para pasar a MP.
 - Se agrega soporte de descuento
   - Se muestra en carrito y checkout
   - Se permite ingresar codigo en checkout
   - Se muestra linea de descuento acorde a Grand o Net Total en:
     - OrderView
     - NewOrder Mail
     - FacturaView
     - FacturaMil
     - SubscriptionView

## 0.1.5 (BETA) | 2019-05-21
### New
 - Se agrega Commission en SalesInvoice.
 - Se deja fijo el header.
 - Se agrega metodo de cobro customizable de MercadoPago.

### Fixes
 - Se corrige mensajes y manejo de error de cobro en pagina de factura y orden.
 - No se muestran linea de impuesto cuando es 0.
 - Se quita linea de costo de envio, impuesto, redondeos de factura cuando es 0.
 - Se corrige cambio de precio en pagina de orden de Pro.

## 0.1.4 (BETA)
### Fixes
 - Se cambia configuracion de Chile para que use la Key de la cuenta de MP Uruguay.
 - Se cambian los medios de pago para Chile para que sean solo 3 sellos.

## 0.1.3 (BETA)
### Fixes
 - Fix en pagina de perfil cuando no tiene suscripcion activa y tampoco invoices.
 - Fix de mostrado de mensaje de suscripcion suspendida cuando la suscripcion esta cancelada en paginas de perfil y detalle de subscripcion.

## 0.1.2 (BETA)
### Fixes
 - Se quita nro de telefono de orderView
 - Se agrega el external_userid de mercadopago al payment entry
 - Se agrega `LONG_TIMEOUT` para `preparePaymentEntry` y `confirmPaymentEntry`

## 0.1.1 (BETA)
### Fixes
 - Se quita nro de telefono del footer y de mails
 - Se quita pedido de prescripcion (hardcoded).
 - Se quita datos de suscripcion cuando no hay suscripcion.
 - Se quita cancelar suscripcion cuando la suscripcion esta cancelada.
 - Se corrige fecha de factura actual
 - Se corrige campos de a nombre de en factura.
 - Se quita costo de envio en factura mensual. 
 - Se corrige el mostrado del precio del Pro en la pagina de orden del Pro
 - Se corrige mostrado de confirmacion de pago en factura.

## 0.1.0 (BETA)

 + Se corrige ortografia
   ```
   - OK - Productos -> Active
   - OK - Productos -> Active -> Entiende sus sintomas
   - OK - Productos -> Active -> Evita el danio
   - OK - Productos -> Active -> Reconoce arritmia
   - OK - Productos -> Active -> Controla riesgo
   - OK - Productos -> Pro
   - OK - Comprar   -> Active
   - OK - Comprar   -> Pro
   - OK - Checkout  -> Carrito
   - OK - Checkout  -> Carrito vacio
   - OK - Checkout  -> Warning Active
   - OK - Checkout  -> Warning Pro
   - OK - Checkout  -> Info usuario
   - OK - Checkout  -> Datos de envio
   - OK - Checkout  -> Pago
   - OK - Checkout  -> Order view
   - OK - Usuario   -> Login
   - (FALTA MANEJAR ERROR DE CAPTCHA Y DE SERVIDOR DE MAIL SIN RESPUESTA) - Usuario   -> Contacto
   - OK - Usuario   -> Recuperar contrasenia
   - OK - Usuario   -> Cuenta
   - OK - Usuario   -> Cuenta -> Suscripcion
   ```
 + Soporte de responsive
   ```
   - Productos -> Active
   - Productos -> Active -> Entiende sus sintomas
   - Productos -> Active -> Evita el danio
   - Productos -> Active -> Reconoce arritmia
   - Productos -> Active -> Controla riesgo
   - Productos -> Pro
   - Comprar   -> Active
   - Comprar   -> Pro
   - Checkout  -> Carrito
   - Checkout  -> Carrito vacio
   - Checkout  -> Warning Active
   - Checkout  -> Warning Pro
   - Checkout  -> Info usuario
   - Checkout  -> Datos de envio
   - Checkout  -> Pago
   - Checkout  -> Order view
   - Usuario   -> Login
   - Usuario   -> Contacto
   - Usuario   -> Recuperar contrasenia
   - Usuario   -> Cuenta
   - Usuario   -> Cuenta -> Suscripcion
   ```

