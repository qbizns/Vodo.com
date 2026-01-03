# Invoice Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /orders/invoices/{invoice_id}:
    get:
      summary: Invoice Details
      deprecated: false
      description: >-
        This endpoint allows you to fetch a specific order invoice details by
        passing the `invoice_id` as a path parameter. 


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `orders.read` - Orders Read Only

        </Accordion>
      operationId: get-invoice-details
      tags:
        - Merchant API/APIs/Order Invoice
        - Order Invoice
      parameters:
        - name: invoice_id
          in: path
          description: >-
            Unique identification number assigned to an invoice. List of Invoice
            IDs can be found [here](https://docs.salla.dev/api-5394157)
          required: true
          example: 673490471
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/invoice_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 1128904162
                  invoice_number: '1'
                  uuid: acde070d-8c4c-4f0d-9d8a-162843c10333
                  order_id: 1152394206
                  invoice_reference_id: '1233'
                  type: Tax Invoice
                  date: '2022-06-30'
                  qr_code: SAVE20
                  payment_method: bank
                  sub_total:
                    amount: 130
                    currency: SAR
                  shipping_cost:
                    amount: 23
                    taxable: true
                    currency: SAR
                  cod_cost:
                    amount: 0
                    taxable: true
                    currency: SAR
                  discount:
                    amount: 0
                    currency: SAR
                  tax:
                    percent: 0
                    amount:
                      amount: 0
                      currency: SAR
                  total:
                    amount: 153
                    currency: SAR
                  items:
                    - id: 1670203268
                      product_id: '977135276'
                      item_id: 588287185
                      name: شاحن آنكر 1000
                      sku: PRO-001
                      quantity: 1
                      type: product
                      price:
                        amount: 130
                        currency: SAR
                      discount:
                        amount: 0
                        currency: SAR
                      tax:
                        percent: 0
                        amount:
                          amount: 0
                          currency: SAR
                      total:
                        amount: 130
                        currency: SAR
          headers: {}
          x-apidog-name: Success
        '401':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/error_unauthorized_401'
              example:
                status: 401
                success: false
                error:
                  code: Unauthorized
                  message: >-
                    The access token should have access to one of those scopes:
                    orders.read
          headers: {}
          x-apidog-name: Unauthorized
        '404':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Object%20Not%20Found(404)'
              example:
                status: 404
                success: false
                error:
                  code: error
                  message: المحتوى الذي تحاول الوصول اليه غير متوفر
          headers: {}
          x-apidog-name: Not Found
        '422':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/error_validation_422'
              examples:
                '4':
                  summary: 'Example | Already Generated '
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        type:
                          - تم اصدار فاتورة للطلب من قبل
                '5':
                  summary: Example | Credit Note Error
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        type:
                          - >-
                            لايمكن إصدار فاتورة مسترجع لطلب ليس لديه فاتورة
                            ضرريبية
                '6':
                  summary: Example | Type Body Variable Required
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        type:
                          - حقل نوع الفاتورة مطلوب.
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-salla-php-method-name: retrieveInvoice
      x-salla-php-return-type: Invoice
      x-apidog-folder: Merchant API/APIs/Order Invoice
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394158-run
components:
  schemas:
    invoice_response_body:
      type: object
      properties:
        status:
          type: number
          description: >-
            Response status code, a numeric or alphanumeric identifier used to
            convey the outcome or status of a request, operation, or transaction
            in various systems and applications, typically indicating whether
            the action was successful, encountered an error, or resulted in a
            specific condition.
        success:
          type: boolean
          description: >-
            Response flag, boolean indicator used to signal a particular
            condition or state in the response of a system or application, often
            representing the presence or absence of certain conditions or
            outcomes.
        data:
          $ref: '#/components/schemas/InvoiceDetails'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    InvoiceDetails:
      title: ' InvoiceDetails'
      type: object
      properties:
        id:
          type: number
          description: A unique identifier of the invoice
          examples:
            - 454902145
        invoice_number:
          type: string
          description: The invoice number as in the order
          examples:
            - '1'
        uuid:
          type: string
          description: Another unique identifier of the invoice
          examples:
            - e7f9e1e3-90d1-487d-afe1-f97e10b80b1d
          format: uuid
        order_id:
          type: number
          description: >-
            A unique identifier assigned to the order. List of orders can be
            found [here](https://docs.salla.dev/api-5394146).
        invoice_reference_id:
          type: string
          description: >-
            Invoice reference. This is especially used if the invoice is issued
            outside Salla system
          nullable: true
        type:
          type: string
          description: Invoice Type
          examples:
            - Tax Invoice
        date:
          type: string
          examples:
            - '2023-10-01'
          description: Invoice issuance date
        qr_code:
          type: string
          description: Invoice QR code
          nullable: true
        payment_method:
          type: string
          examples:
            - credit_card
          description: Invoice payment method
        subtotal:
          type: object
          properties:
            amount:
              type: number
              description: Subtotal amount
              examples:
                - 50
            currency:
              type: string
              description: Subtotal currency
              examples:
                - SAR
          x-apidog-orders:
            - amount
            - currency
          required:
            - amount
            - currency
          x-apidog-ignore-properties: []
        shipping_cost:
          type: object
          properties:
            amount:
              type: string
              description: Shipping amount
            taxable:
              type: boolean
              description: If the amount is taxable or not
            currency:
              type: string
              description: shipping amount currency
          x-apidog-orders:
            - amount
            - taxable
            - currency
          required:
            - amount
            - taxable
            - currency
          x-apidog-ignore-properties: []
        cod_cost:
          type: object
          properties:
            amount:
              type: number
              description: Cash on delivery fees amount
            taxable:
              type: boolean
              description: If the amount is taxable or not
            currency:
              type: string
              description: Cash on delivery fees currency
          x-apidog-orders:
            - amount
            - taxable
            - currency
          required:
            - amount
            - taxable
          x-apidog-ignore-properties: []
        discount:
          type: object
          properties:
            amount:
              type: string
              description: Discounted amount
            currency:
              type: string
              description: Discount Currency
              examples:
                - SAR
          x-apidog-orders:
            - amount
            - currency
          required:
            - amount
            - currency
          x-apidog-ignore-properties: []
        tax:
          type: object
          properties:
            percent:
              type: number
              description: Tax Percentage
            amount:
              type: object
              properties:
                amount:
                  type: number
                  description: Tax amount
                currency:
                  type: string
                  description: Tax currency
                  examples:
                    - SAR
              x-apidog-orders:
                - amount
                - currency
              required:
                - amount
                - currency
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - percent
            - amount
          required:
            - percent
            - amount
          x-apidog-ignore-properties: []
        total:
          type: object
          properties:
            amount:
              type: number
              description: Invoice total amount
            currency:
              type: string
              description: Total currency
              examples:
                - SAR
          x-apidog-orders:
            - amount
            - currency
          required:
            - amount
            - currency
          x-apidog-ignore-properties: []
        items:
          type: array
          items:
            type: object
            properties:
              id:
                type: string
                description: Unique identification number associated.
              item_id:
                type: string
                description: Unique identification number associated with the item.
              product_id:
                type: string
                description: Uniqiue identification number for the product.
              name:
                type: string
                description: Item name
              sku:
                type: string
                description: Item sku
              quantity:
                type: string
                description: Item quantity
              type:
                type: string
                description: Item type
                enum:
                  - product
                  - service
                  - group_products
                  - codes
                  - digital
                  - food
                  - donating
                examples:
                  - product
                x-apidog-enum:
                  - name: ''
                    value: product
                    description: Item type is product.
                  - name: ''
                    value: service
                    description: Item type is service.
                  - name: ''
                    value: group_products
                    description: Item type is group product.
                  - name: ''
                    value: codes
                    description: Item type is codes.
                  - name: ''
                    value: digital
                    description: Item type is digital.
                  - name: ''
                    value: food
                    description: Item type is food.
                  - name: ''
                    value: donating
                    description: Item type is donating.
              price:
                type: object
                properties:
                  amount:
                    type: number
                    description: Price amount
                  currency:
                    type: string
                    description: Price currency
                    examples:
                      - SAR
                description: Item price
                x-apidog-orders:
                  - amount
                  - currency
                x-apidog-ignore-properties: []
              discount:
                type: object
                properties:
                  amount:
                    type: number
                    description: Discount amount
                  currency:
                    type: string
                    description: Discount currency
                    examples:
                      - SAR
                description: Item discount
                x-apidog-orders:
                  - amount
                  - currency
                x-apidog-ignore-properties: []
              tax:
                type: object
                properties:
                  percent:
                    type: number
                    description: Tax percentage
                  amount:
                    type: object
                    properties:
                      amount:
                        type: number
                        description: Tax amount
                        examples:
                          - 15
                      currency:
                        type: string
                        description: Tax currency
                        examples:
                          - SAR
                    x-apidog-orders:
                      - amount
                      - currency
                    x-apidog-ignore-properties: []
                x-apidog-orders:
                  - percent
                  - amount
                description: Item tax
                x-apidog-ignore-properties: []
              total:
                type: object
                properties:
                  amount:
                    type: number
                    description: Total amount
                  currency:
                    type: string
                    description: Total currency
                    examples:
                      - SAR
                x-apidog-orders:
                  - amount
                  - currency
                description: Item total
                x-apidog-ignore-properties: []
            x-apidog-orders:
              - id
              - item_id
              - product_id
              - name
              - sku
              - quantity
              - type
              - price
              - discount
              - tax
              - total
            x-apidog-ignore-properties: []
          description: Invoice items details.
      x-apidog-orders:
        - id
        - invoice_number
        - uuid
        - order_id
        - invoice_reference_id
        - type
        - date
        - qr_code
        - payment_method
        - subtotal
        - shipping_cost
        - cod_cost
        - discount
        - tax
        - total
        - items
      required:
        - id
        - invoice_number
        - uuid
        - order_id
        - invoice_reference_id
        - type
        - date
        - qr_code
        - payment_method
        - subtotal
        - shipping_cost
        - cod_cost
        - discount
        - tax
        - total
        - items
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Object Not Found(404):
      type: object
      properties:
        status:
          type: integer
          description: >-
            Response status code, a numeric or alphanumeric identifier used to
            convey the outcome or status of a request, operation, or transaction
            in various systems and applications, typically indicating whether
            the action was successful, encountered an error, or resulted in a
            specific condition.
        success:
          type: boolean
          description: >-
            Response flag, boolean indicator used to signal a particular
            condition or state in the response of a system or application, often
            representing the presence or absence of certain conditions or
            outcomes.
        error:
          type: object
          properties:
            code:
              type: integer
              description: >-
                Not Found Response error code, a numeric or alphanumeric unique
                identifier used to represent the error.
            message:
              type: string
              description: >-
                A message or data structure that is generated or returned when
                the response is not found or explain the error.
          required:
            - code
            - message
          x-apidog-orders:
            - code
            - message
          x-apidog-ignore-properties: []
      required:
        - status
        - success
        - error
      x-apidog-orders:
        - status
        - success
        - error
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    error_validation_422:
      type: object
      properties:
        status:
          type: number
          description: >-
            Response status code, a numeric or alphanumeric identifier used to
            convey the outcome or status of a request, operation, or transaction
            in various systems and applications, typically indicating whether
            the action was successful, encountered an error, or resulted in a
            specific condition.
        success:
          type: boolean
          description: >-
            Response flag, boolean indicator used to signal a particular
            condition or state in the response of a system or application, often
            representing the presence or absence of certain conditions or
            outcomes.
        error:
          $ref: '#/components/schemas/Validation'
      x-apidog-orders:
        - status
        - success
        - error
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Validation:
      type: object
      properties:
        code:
          type: string
          description: >-
            Response error code,a numeric or alphanumeric unique identifier used
            to represent the error.
        message:
          type: string
          description: >-
            A message or data structure that is generated or returned when the
            response is not found or explain the error.
        fields:
          type: object
          description: Validation rules with problems
          properties:
            '{field-name}':
              type: array
              items:
                type: string
          x-apidog-orders:
            - '{field-name}'
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - code
        - message
        - fields
      required:
        - code
        - message
        - fields
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    error_unauthorized_401:
      type: object
      properties:
        status:
          type: number
          description: >-
            Response status code, a numeric or alphanumeric identifier used to
            convey the outcome or status of a request, operation, or transaction
            in various systems and applications, typically indicating whether
            the action was successful, encountered an error, or resulted in a
            specific condition.
        success:
          type: boolean
          description: >-
            Response flag, boolean indicator used to signal a particular
            condition or state in the response of a system or application, often
            representing the presence or absence of certain conditions or
            outcomes.
        error:
          $ref: '#/components/schemas/Unauthorized'
      x-apidog-orders:
        - status
        - success
        - error
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Unauthorized:
      type: object
      x-examples: {}
      title: Unauthorized
      properties:
        code:
          type: string
          description: Code Error
        message:
          type: string
          description: Message Error
      x-apidog-orders:
        - code
        - message
      required:
        - code
        - message
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
  securitySchemes:
    bearer:
      type: http
      scheme: bearer
servers:
  - url: ''
    description: Cloud Mock
  - url: https://api.salla.dev/admin/v2
    description: Production
security:
  - bearer: []

```
