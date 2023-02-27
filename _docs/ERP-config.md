# ERPNext Notes

## 1. Instalation

Llamar a Nacho

## 2. Patches

### 2.1 Allow zero in ROUND_OFF
```diff
diff --git a/erpnext/accounts/doctype/gl_entry/gl_entry.py b/erpnext/accounts/doctype/gl_entry/gl_entry.py
index 5c76799..9edd602 100644
--- a/erpnext/accounts/doctype/gl_entry/gl_entry.py
+++ b/erpnext/accounts/doctype/gl_entry/gl_entry.py
@@ -56,9 +56,9 @@ class GLEntry(Document):
                                        .format(self.voucher_type, self.voucher_no, self.account))
 
                # Zero value transaction is not allowed
-               if not (flt(self.debit) or flt(self.credit)):
-                       frappe.throw(_("{0} {1}: Either debit or credit amount is required for {2}")
-                               .format(self.voucher_type, self.voucher_no, self.account))
+               # if not (flt(self.debit) or flt(self.credit)):
+               #       frappe.throw(_("{0} {1}: Either debit or credit amount is required for {2}")
+               #               .format(self.voucher_type, self.voucher_no, self.account))
 
        def pl_must_have_cost_center(self):
                if frappe.db.get_value("Account", self.account, "report_type") == "Profit and Loss":
```

### 2.2 Get the currency of the subscription plan when generating the invoice AND get the right debit_to account
```diff
diff --git a/erpnext/accounts/doctype/subscription/subscription.py b/erpnext/accounts/doctype/subscription/subscription.py
index 2bb2c00..3dc2121 100644
--- a/erpnext/accounts/doctype/subscription/subscription.py
+++ b/erpnext/accounts/doctype/subscription/subscription.py
@@ -241,6 +241,11 @@ class Subscription(Document):
                invoice.posting_date = self.current_invoice_start
                invoice.customer = self.customer
 
+                subscription_plan = frappe.get_doc("Subscription Plan", self.plans[0].plan)
+                invoice.currency = subscription_plan.currency
+                invoice.party_account_currency = subscription_plan.currency
+                invoice.debit_to = "Debtors-%s - BL" % invoice.currency
+
                # Subscription is better suited for service items. I won't update `update_stock`
                # for that reason
                items_list = self.get_items_from_plans(self.plans, prorate)
```

### 2.3 Method 'get_pending_invoices_to_be_paid' for Subscription Invoice
`/api/method/erpnext.accounts.doctype.subscription_invoice.subscription_invoice.get_pending_invoices_to_be_paid`
```diff
diff --git a/erpnext/accounts/doctype/subscription_invoice/subscription_invoice.py b/erpnext/accounts/doctype/subscription_invoice/subscription_invoice.py
index 6f459b4..a40f312 100644
--- a/erpnext/accounts/doctype/subscription_invoice/subscription_invoice.py
+++ b/erpnext/accounts/doctype/subscription_invoice/subscription_invoice.py
@@ -3,7 +3,17 @@
 # For license information, please see license.txt
 
 from __future__ import unicode_literals
+import frappe
 from frappe.model.document import Document
 
 class SubscriptionInvoice(Document):
-       pass
+  pass
+
+@frappe.whitelist()
+def get_pending_invoices_to_be_paid():
+  pending_invoices = frappe.db.sql("""SELECT `tabSubscription Invoice`.* FROM `tabSubscription Invoice`
+  JOIN `tabSales Invoice` 
+      ON `tabSales Invoice`.name = `tabSubscription Invoice`.invoice
+          AND `tabSales Invoice`.status IN ('Unpaid', 'Overdue')
+              AND `tabSales Invoice`.docstatus=1""", as_dict=1)
+  return pending_invoices
```

### 2.4 Method 'get_delivery_note_of_sales_order' for Delivery Note
```diff
diff --git a/erpnext/stock/doctype/delivery_note/delivery_note.py b/erpnext/stock/doctype/delivery_note/delivery_note.py
index 1eb2b0985a..7264d8a748 100644
--- a/erpnext/stock/doctype/delivery_note/delivery_note.py
+++ b/erpnext/stock/doctype/delivery_note/delivery_note.py
@@ -585,3 +585,8 @@ def make_sales_return(source_name, target_doc=None):
 def update_delivery_note_status(docname, status):
        dn = frappe.get_doc("Delivery Note", docname)
        dn.update_status(status)
+
+
+@frappe.whitelist()
+def get_delivery_note_of_sales_order(sales_order):
+       return frappe.db.sql("SELECT parent FROM `tabDelivery Note Item` WHERE against_sales_order='%s'" % sales_order)
\ No newline at end of file
```

### 2.5 Method 'get_note_item_by_serial_no' for Delivery Note Item
```diff
diff --git a/erpnext/stock/doctype/delivery_note_item/delivery_note_item.py b/erpnext/stock/doctype/delivery_note_item/delivery_note_item.py
index aaca802..4027c07 100644
--- a/erpnext/stock/doctype/delivery_note_item/delivery_note_item.py
+++ b/erpnext/stock/doctype/delivery_note_item/delivery_note_item.py
@@ -10,3 +10,10 @@ from erpnext.controllers.print_settings import print_settings_for_item_table
 class DeliveryNoteItem(Document):
        def __setup__(self):
                print_settings_for_item_table(self)
+
+@frappe.whitelist()
+def get_note_item_by_serial_no(serial_no):
+  return frappe.db.sql("""SELECT note_item.item_code
+                  FROM `tabDelivery Note Item` AS note_item , `tabDelivery Note Item` AS note_item_1
+                  WHERE note_item.against_sales_order = note_item_1.against_sales_order AND note_item_1.serial_no='%s'""" % serial_no)
+
```


## 3. Operation


### 3.1 Restart
#### Hidrogeno

`sudo kill -9 $(pidof -x supervisord) && yes | sudo bench setup production operador`

#### S30

Tiene que ser root.
`cd /opt/apps/frappe-bench && kill -9 $(pidof -x supervisord) && yes | bench setup production operador`
  
## 4. Config setup

### 4.1 Custom Fields - ERP
| Resource Name   | Field Type    | Field Name             | Description                 | Required | Extra Info
---               | ---           | ---                    | ---                         | :---:    |:---:
Contact           | Data          | hhub_userid            | user id del hub             | si       |
Payment Entry     | Data          | hhub_payment_gateway   | nombre de pasarela          | si       |
Payment Entry     | Data          | hhub_payment_trxid     | id de transaccion           | si       |
Payment Entry     | Data          | hhub_card_issuerName   | issuer de la tarjeta        | si       |
Payment Entry     | Data          | hhub_card_last4        | ultimos 4 de tarjeta        | si       |
Payment Entry     | Data          | hhub_card_holder       | titular de tarjeta          | si       |
Payment Entry     | Data          | hhub_payment_user      | id de usuario en la plataforma de pagos | si |
Payment Entry     | Data          | statement_descriptor   | descripcion de pago en resumen | si |
Delivery Note     | Data          | shipping_airway_number | Airway Bill Number (DHL)    | no       | allow on submit
Sales Order Item  | Data          | subscription_code      | codigo de suscripcion       | no       |
Sales Order Item  | Data          | interpretation_code    | interpretacion ecg          | no       |
Sales Invoice     | Data          | payment_attempts       | nros de intentos de pagos   | si       | default 0 & allow on submit
Address           | Data          | address_recipient      | persona asociada al address | si       |
Subscription      | Section Break | sb_5                   | Included items              | no       |
Subscription      | Table         | items                  | Items                       | no       | Options: Subscription Item

### 4.1 Custom DocType - ERP

#### Subscription Item 
```
Name: Subscription Item
Module: Selling
Fields: 
     - Serial Number
       fieldname : serial_number
       fieldtype : Link
       label     : Serial Number
       options   : Serial No
     - Item
       fieldname : item
       fieldtype : Link
       label     : Item
       options   : Item
```

## 5 Data Setup

### 5.0 Companias

1. Bambus Life (Global)
   Ventas web (en todos los paises)
2. Tingelmar (UY)

Proveedores
1. CardrioSecur

Clientes
1. SEMM

### 5.1 Productos

| | | | 
|-|-|-|
CS-ACT-USBC  | CardioSecur Active (usb-c) 
CS-ACT-MUSB  | CardioSecur Active (m-usb)
CS-ACT-LIGHT | CardioSecur Active (lightning)
CS-PRO-LIGHT | CardioSecur Pro (lightning)
CS-ACT-SUBS    | CardioSecur Active Subscription
CS-PRO-SUBS-12 | CardioSecur Pro Subscription 12L
CS-PRO-SUBS-22 | CardioSecur Pro Subscription 22L
CS-ECG-INTER   | Interpretación de Electrocardiograma

### 5.2 Warehouses
Default Warehouse - Stores BL
  
### 5.3 Price Lists

*CardioSecur Prices (buying)*  
  
| | | | 
|-|-:|-|
CS-ACT-USBC    | 58,23 | USD
CS-ACT-MUSB    | 58,23 | USD
CS-ACT-LIGHT   | 58,23 | USD
CS-PRO-LIGHT   | 91,98 | EUR


*Precios Web*  
  
| | | | 
|-|-:|-|
CS-ACT-USBC    | 120,00 | USD
CS-ACT-MUSB    | 120,00 | USD
CS-ACT-LIGHT   | 120,00 | USD
CS-PRO-LIGHT   | 200,00 | USD
CS-ACT-SUBS    |  19,90 | USD
CS-PRO-SUBS-12 |  29,90 | USD
CS-PRO-SUBS-22 |  39,90 | USD
CS-ECG-INTER   |  29,00 | USD


*Subscriptions Prices UY*
  
| | | | 
|-|-:|-|
CS-ACT-SUBS    |  680,58 | UYU
CS-PRO-SUBS-12 | 1022,58 | UYU
CS-PRO-SUBS-22 | 1364,58 | UYU

*Subscriptions Prices CL*
  
| | | | 
|-|-:|-|
CS-ACT-SUBS    | 1 | CLP
CS-PRO-SUBS-12 | 1 | CLP
CS-PRO-SUBS-22 | 1 | CLP

> Los precios de las subscripciones son actualizados de forma automatica al detectar un cambio en el Exchange Rate. Convierte de `Precios Web` en `USD` a esta lista en `UYU`. 

### 5.4 Taxes
  
Ya viene cargado `Uruguay Tax - BL`
  
- `Chile Tax - BL`
On Net Total - VAT BL - 19

- `Sin IVA - BL`
On Net Total - VAT BL - Rate: 0 Amount: 0

### 5.5 Shipping Rule

| | | | 
|-|-:|-|
DHL Express      | 20 | USD
DHL Express UY   | 10 | USD
  

### 5.6 Cuentas contables

#### General 

| | | | 
|-|-|-|
Account Name   | Exchange Invoice Loss
Currency       | USD
Account Type   | Round Off
Tree           | Expenses -> Indirect Expenses

| | | | 
|-|-|-|
Account Name   | Payment Gateway Commission - BL
Currency       | USD
Account Type   | <none>
Tree           | Expenses -> Indirect Expenses

#### Uruguay 

| | | | 
|-|-|-|
Account Name | Debtors-UYU
Currency     | UYU
Account Type | Receivable
Tree         | Application of Funds -> Current Assets -> Accounts Receivable
|-|-|-|
Account Name | Cash-UYU
Currency     | UYU
Account Type | Cash
Tree         | Application of Funds -> Current Assets -> Cash In Hand


#### Chile 

| | | | 
|-|-|-|
Account Name | Debtors-CLP
Currency     | CLP
Account Type | Receivable
Tree         | Application of Funds -> Current Assets -> Accounts Receivable
|-|-|-|
Account Name | Cash-CLP
Currency     | CLP
Account Type | Cash
Tree         | Application of Funds -> Current Assets -> Cash In Hand

### 5.7 Subscription Plans

#### Uruguay
| | | | 
|-|-|-|
Plan Name           | CardioSecur Active Subscription UYU
Item                | CS-ACT-SUBS
Currency            | UYU
Price Determination | Based on price list
Price List          | Subscription Prices UY
|-|-|-|
Plan Name           | CardioSecur Pro Subscription 12L UYU
Item                | CS-PRO-SUBS-12
Currency            | UYU
Price Determination | Based on price list
Price List          | Subscription Prices UY
|-|-|-|
Plan Name           | CardioSecur Pro Subscription 22L UYU
Item                | CS-PRO-SUBS-22
Currency            | UYU
Price Determination | Based on price list
Price List          | Subscription Prices UY

#### Chile
| | | | 
|-|-|-|
Plan Name           | CardioSecur Active Subscription CLP
Item                | CS-ACT-SUBS
Currency            | CLP
Price Determination | Based on price list
Price List          | Subscription Prices CL
|-|-|-|
Plan Name           | CardioSecur Pro Subscription 12L CLP
Item                | CS-PRO-SUBS-12
Currency            | CLP
Price Determination | Based on price list
Price List          | Subscription Prices CL
|-|-|-|
Plan Name           | CardioSecur Pro Subscription 22L CLP
Item                | CS-PRO-SUBS-22
Currency            | CLP
Price Determination | Based on price list
Price List          | Subscription Prices CL


### 5.8 Purchase Receipt

```
Date               : 
Supplier           : CardioSecur
Price List         : CardioSecur Prices
Accepted Warehouse : Stores - BL
```
