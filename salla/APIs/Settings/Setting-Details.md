# Setting Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /settings/fields/{slug}:
    get:
      summary: Setting Details
      deprecated: false
      description: >-
        This endpoint allows you to fetch settings details for a specific slug
        by passing the `slug` as a path parameter.


        :::info[Read More]

        For more on Store Settings, check the Merchant's Help Desk article
        [here](https://help.salla.sa/article/1887201789)

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `store-settings.read`- Settings Read Only

        </Accordion>
      operationId: get-settings-slug
      tags:
        - Merchant API/APIs/Settings
        - Settings
      parameters:
        - name: slug
          in: path
          description: >-
            Unique identifier or URL-friendly name assigned to the Settings .
            Get a list of Settings Slugs from
            [here](https://docs.salla.dev/api-6965777)
          required: true
          schema:
            type: string
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/slugSettings_response_body'
              examples:
                '1':
                  summary: Success | Receive Orders
                  value:
                    status: 200
                    success: true
                    data:
                      receive_orders: true
                      limit:
                        enable: false
                        count: 50
                        message:
                          ar: "ياهلا {name}\r\n نعتذر عميلنا العزيز، لايمكن استقبال طلبك اليوم لوصولنا للحد الاعلى لطلبات اليوم، يمكنك الطلب بعد {time}."
                          en: "ياهلا {name}\r\n نعتذر عميلنا العزيز، لايمكن استقبال طلبك اليوم لوصولنا للحد الاعلى لطلبات اليوم، يمكنك الطلب بعد {time}."
                          zh: "ياهلا {name}\r\n نعتذر عميلنا العزيز، لايمكن استقبال طلبك اليوم لوصولنا للحد الاعلى لطلبات اليوم، يمكنك الطلب بعد {time}."
                '3':
                  summary: Success | Recieving Times
                  value:
                    saturday:
                      enabled: true
                      from: '00:00'
                      to: '23:55'
                    sunday:
                      enabled: false
                      from: '00:00'
                      to: '23:55'
                    monday:
                      enabled: false
                      from: '00:00'
                      to: '23:55'
                    tuesday:
                      enabled: false
                      from: '00:00'
                      to: '23:55'
                    wednesday:
                      enabled: false
                      from: '00:00'
                      to: '23:55'
                    thursday:
                      enabled: false
                      from: '00:00'
                      to: '23:55'
                    friday:
                      enabled: false
                      from: '00:00'
                      to: '23:55'
                '4':
                  summary: Success | Reports Order Statuses
                  value:
                    status: 200
                    success: true
                    data:
                      order_statuses:
                        - id: 1
                          name: بإنتظار الدفع
                          selected: true
                        - id: 2
                          name: بإنتظار المراجعة
                          selected: true
                        - id: 3
                          name: قيد التنفيذ
                          selected: true
                        - id: 4
                          name: تم التنفيذ
                          selected: true
                        - id: 8
                          name: جاري التوصيل
                          selected: true
                        - id: 9
                          name: تم التوصيل
                          selected: false
                        - id: 10
                          name: تم الشحن
                          selected: false
                        - id: 11
                          name: بإنتظار تأكيد الدفع
                          selected: true
                        - id: 13
                          name: طلب عرض سعر
                          selected: false
                '5':
                  summary: Success | Products Purchase Count
                  value:
                    enabled: true
                    condition:
                      enabled: false
                      categories: []
                '6':
                  summary: Success | Products Recommendations
                  value: |-
                    {
                        "enabled": true,
                        "types": "category" // random, category, brand, tag
                    }
                '7':
                  summary: Success | Products - Product Notify Availability
                  value:
                    enabled:
                      products: true
                      skus: true
                    channels:
                      - email
                      - sms
                    channels_status:
                      email: true
                      sms: true
                      mobile: false
                      whatsapp: false
                    content_title: '{product} صار متوفر!'
                    content_message: |-
                      ياهلا {name}،
                              قبل فترة حاولت تطلب ({product}) وكان مخلص عندنا
                              و عشانك وفرناه في المتجر لكن بكمية محدودة
                              ألحق اطلب من الرابط التالي:
                              {product_link}
                '8':
                  summary: Success | Products - Product Inventory
                  value: |-
                    {
                        "show_out_products": 0,
                        "manual_quantity": 1,
                        "display_product_quantity": "show" // hide, show, less_than_5
                    }
                '9':
                  summary: Success | Products - Brand Options
                  value:
                    status: 200
                    success: true
                    data:
                      show_banner: 0
                      show_in_menu: 1
                      menu_title:
                        en: ''
                        ar: الماركات التجارية
                      menu_order: '100'
                '10':
                  summary: Success | Products - Size Guides
                  value:
                    status: 200
                    success: true
                    data:
                      - id: 1298199463
                        name: Size 4
                        description: test size 4
                        type: 1
                        enablec: false
                        brands:
                          - 2079537577
                        translations:
                          ar:
                            name: Size 4
                            description: test size 4
                      - id: 1939592358
                        name: Size 3
                        description: test size 3
                        type: 2
                        enablec: false
                        categories:
                          - 1908230909
                        translations:
                          ar:
                            name: Size 3
                            description: test size 3
                      - id: 566146469
                        name: Size 2
                        description: test Size 2
                        type: 1
                        enablec: false
                        brands:
                          - 814202285
                        translations:
                          ar:
                            name: Size 2
                            description: test Size 2
                      - id: 1473353380
                        name: Size 1
                        description: test size 1
                        type: 2
                        enablec: false
                        categories:
                          - 1134193150
                        translations:
                          ar:
                            name: Size 1
                            description: test size 1
          headers: {}
          x-apidog-name: Success
        '401':
          description: ''
          content:
            application/json:
              schema:
                type: object
                properties:
                  status:
                    type: number
                    description: >-
                      Response status code, a numeric or alphanumeric identifier
                      used to convey the outcome or status of a request,
                      operation, or transaction in various systems and
                      applications, typically indicating whether the action was
                      successful, encountered an error, or resulted in a
                      specific condition.
                  success:
                    type: boolean
                    description: >-
                      Response flag, boolean indicator used to signal a
                      particular condition or state in the response of a system
                      or application, often representing the presence or absence
                      of certain conditions or outcomes.
                  error:
                    $ref: '#/components/schemas/Unauthorized'
                x-apidog-orders:
                  - status
                  - success
                  - error
                x-apidog-ignore-properties: []
              example:
                status: 401
                success: false
                error:
                  code: Unauthorized
                  message: >-
                    The access token should have access to one of those scopes:
                    exports.read_write
          headers: {}
          x-apidog-name: error_unauthorized_401
        '404':
          description: ''
          content:
            application/json:
              schema:
                type: object
                properties:
                  status:
                    type: number
                    description: >-
                      Response status code, a numeric or alphanumeric identifier
                      used to convey the outcome or status of a request,
                      operation, or transaction in various systems and
                      applications, typically indicating whether the action was
                      successful, encountered an error, or resulted in a
                      specific condition.
                  success:
                    type: boolean
                    x-stoplight:
                      id: f4ajks6ba59j4
                    description: >-
                      Response flag, boolean indicator used to signal a
                      particular condition or state in the response of a system
                      or application, often representing the presence or absence
                      of certain conditions or outcomes.
                  error:
                    $ref: '#/components/schemas/NotFound'
                x-apidog-orders:
                  - status
                  - success
                  - error
                x-apidog-ignore-properties: []
          headers: {}
          x-apidog-name: error_notFound_404
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Settings
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-6965781-run
components:
  schemas:
    slugSettings_response_body:
      anyOf:
        - type: object
          properties:
            status:
              type: integer
              description: >-
                Response status code, a numeric or alphanumeric identifier used
                to convey the outcome or status of a request, operation, or
                transaction in various systems and applications, typically
                indicating whether the action was successful, encountered an
                error, or resulted in a specific condition.
            success:
              type: string
              description: >-
                Response flag, boolean indicator used to signal a particular
                condition or state in the response of a system or application,
                often representing the presence or absence of certain conditions
                or outcomes.
            data:
              type: object
              properties:
                receive_orders:
                  type: boolean
                  description: >-
                    Whether or not the store receives orders from store
                    customers
                limit:
                  type: object
                  properties:
                    enable:
                      type: boolean
                      description: >-
                        Whether or not to enable the daily limit orders for the
                        store
                    count:
                      type: number
                      description: Number of orders to accept from the store customers
                    message:
                      type: object
                      properties:
                        ar:
                          type: string
                          description: Text message in the Arabic Language
                        en:
                          type: string
                          description: Text message in the English Language
                      x-apidog-orders:
                        - ar
                        - en
                      required:
                        - ar
                        - en
                      description: >-
                        Text message to appear when the store doesn't accept
                        orders
                      x-apidog-ignore-properties: []
                  x-apidog-orders:
                    - enable
                    - count
                    - message
                  required:
                    - enable
                    - count
                    - message
                  description: Daily limit orders for the store
                  x-apidog-ignore-properties: []
              x-apidog-orders:
                - receive_orders
                - limit
              required:
                - receive_orders
                - limit
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - status
            - success
            - data
          required:
            - status
            - success
            - data
          title: ''
          description: Receive Orders Data Schema
          x-apidog-ignore-properties: []
        - type: object
          properties:
            dayOfTheWeek:
              type: object
              properties:
                enabled:
                  type: boolean
                  description: >-
                    Whether or not to enable selecting specific days of the week
                    to receive orders
                from:
                  type: string
                  description: Accepting orders From date value
                  format: date-time
                to:
                  type: string
                  description: Accepting orders To date value
                  format: date-time
              x-apidog-orders:
                - enabled
                - from
                - to
              required:
                - enabled
                - from
                - to
              description: >-
                Allowed values are:

                `saturday`, `sunday`, `monday`, `tuesday`, `wednesday`,
                `thursday`, `friday`
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - dayOfTheWeek
          required:
            - dayOfTheWeek
          title: ''
          description: Receive Order Times Data Schema
          x-apidog-ignore-properties: []
        - type: object
          properties:
            status:
              type: string
              description: >-
                Response status code, a numeric or alphanumeric identifier used
                to convey the outcome or status of a request, operation, or
                transaction in various systems and applications, typically
                indicating whether the action was successful, encountered an
                error, or resulted in a specific condition.
            success:
              type: string
              description: >-
                Response flag, boolean indicator used to signal a particular
                condition or state in the response of a system or application,
                often representing the presence or absence of certain conditions
                or outcomes.
            data:
              type: object
              properties:
                id:
                  type: number
                  description: Order Status ID
                name:
                  type: string
                  description: Order Status Name
                selected:
                  type: boolean
                  description: Whether or not the order status is selected
              x-apidog-orders:
                - id
                - name
                - selected
              required:
                - id
                - name
                - selected
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - status
            - success
            - data
          required:
            - status
            - success
            - data
          title: ''
          description: Reports Order Statuses Data Schema
          x-apidog-ignore-properties: []
        - type: object
          properties:
            enabled:
              type: boolean
              description: Whether or not to show the products' inventory value
            condition:
              type: object
              properties:
                enabled:
                  type: boolean
                  description: >-
                    Whether or not to show how many times the product was
                    purchased by other customers
                categories:
                  type: array
                  items:
                    type: string
                  description: >-
                    If `enabled` is set to `true`, select categories on which to
                    show how many times the product was purchased by other
                    customers
              x-apidog-orders:
                - enabled
                - categories
              required:
                - enabled
                - categories
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - enabled
            - condition
          required:
            - enabled
            - condition
          title: ''
          description: Products Purchase Count Data Schema
          x-apidog-ignore-properties: []
        - type: object
          properties:
            enabled:
              type: boolean
              description: >-
                Whether or not to show products that the customer may like which
                appears as recommendation
            types:
              type: string
              enum:
                - random
                - category
                - brand
                - tag
              x-apidog-enum:
                - value: random
                  name: ''
                  description: ''
                - value: category
                  name: ''
                  description: ''
                - value: brand
                  name: ''
                  description: ''
                - value: tag
                  name: ''
                  description: ''
              description: Show product types based on one of the enum values
          x-apidog-orders:
            - enabled
            - types
          required:
            - enabled
            - types
          title: ''
          description: Products Recommendation Data Schema
          x-apidog-ignore-properties: []
        - type: object
          properties:
            enabled:
              type: object
              properties:
                products:
                  type: boolean
                skus:
                  type: boolean
              x-apidog-orders:
                - products
                - skus
              required:
                - products
                - skus
              description: >-
                Whether or not to enable product availability notifications to
                alert customers when an item is back in stock.
              x-apidog-ignore-properties: []
            channels:
              type: object
              properties:
                email:
                  type: boolean
                  description: Whether or not to alert customers via email
                sms:
                  type: boolean
                  description: Whether or not to alert customers via SMS
              x-apidog-orders:
                - email
                - sms
              required:
                - email
                - sms
              x-apidog-ignore-properties: []
            channels_status:
              type: object
              properties:
                email:
                  type: string
                  description: Whether or not the email channel is enabled for alert
                sms:
                  type: string
                  description: Whether or not the sms channel is enabled for alert
                mobile:
                  type: string
                  description: >-
                    Whether or not the mobile phone number channel is enabled
                    for alert
                whatsapp:
                  type: string
                  description: Whether or not the whatsapp channel is enabled for alert
              x-apidog-orders:
                - email
                - sms
                - mobile
                - whatsapp
              required:
                - email
                - sms
                - mobile
                - whatsapp
              x-apidog-ignore-properties: []
            content_title:
              type: string
              description: Alert Message content title
            content_message:
              type: string
              description: Alert Message content value
          x-apidog-orders:
            - enabled
            - channels
            - channels_status
            - content_title
            - content_message
          required:
            - enabled
            - channels
            - channels_status
            - content_title
            - content_message
          title: ''
          description: Products Notification Availability Data Schema
          x-apidog-ignore-properties: []
        - type: object
          properties:
            show_out_products:
              type: boolean
              description: 'Whether or not to show products being out of stock '
            manual_quantity:
              type: boolean
            display_product_quantity:
              type: string
              enum:
                - show
                - hide
                - less_than_five
              x-apidog-enum:
                - value: show
                  name: ''
                  description: ''
                - value: hide
                  name: ''
                  description: ''
                - value: less_than_five
                  name: ''
                  description: ''
              description: Show the product current quantity value
          x-apidog-orders:
            - show_out_products
            - manual_quantity
            - display_product_quantity
          required:
            - show_out_products
            - manual_quantity
            - display_product_quantity
          title: ''
          description: Products Inventory Data Schema
          x-apidog-ignore-properties: []
        - type: object
          properties:
            status:
              type: string
              description: >-
                Response status code, a numeric or alphanumeric identifier used
                to convey the outcome or status of a request, operation, or
                transaction in various systems and applications, typically
                indicating whether the action was successful, encountered an
                error, or resulted in a specific condition.
            sucess:
              type: string
              description: >-
                Response flag, boolean indicator used to signal a particular
                condition or state in the response of a system or application,
                often representing the presence or absence of certain conditions
                or outcomes.
            data:
              type: object
              properties:
                show_banner:
                  type: boolean
                  description: Whether or not to show banner
                show_in_menu:
                  type: string
                  description: Whether or not to show product in the menu
                menu_title:
                  type: object
                  properties:
                    en:
                      type: string
                      description: Menu title expressed in English language
                    ar:
                      type: string
                      description: Menu title expressed in Arabic language
                  x-apidog-orders:
                    - en
                    - ar
                  required:
                    - en
                    - ar
                  x-apidog-ignore-properties: []
                menu_order:
                  type: integer
                  description: Number of the menu order
              x-apidog-orders:
                - show_banner
                - show_in_menu
                - menu_title
                - menu_order
              required:
                - show_banner
                - show_in_menu
                - menu_title
                - menu_order
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - status
            - sucess
            - data
          description: Products Brand Options Data Schema
          required:
            - status
            - sucess
            - data
          title: ''
          x-apidog-ignore-properties: []
        - type: object
          properties:
            status:
              type: string
              description: >-
                Response status code, a numeric or alphanumeric identifier used
                to convey the outcome or status of a request, operation, or
                transaction in various systems and applications, typically
                indicating whether the action was successful, encountered an
                error, or resulted in a specific condition.
            sucess:
              type: string
              description: >-
                Response flag, boolean indicator used to signal a particular
                condition or state in the response of a system or application,
                often representing the presence or absence of certain conditions
                or outcomes.
            data:
              type: array
              items:
                type: object
                properties:
                  id:
                    type: integer
                    description: Uniques identifier for a specific product.
                  name:
                    type: string
                    description: Lable or title of the product
                  description:
                    type: string
                    description: A brief explanation of the product.
                  type:
                    type: integer
                    description: Type of the product.
                  enabled:
                    type: boolean
                    description: whether or not the option is enabled.
                  brands:
                    type: integer
                    description: The product brand.
                  categories:
                    type: integer
                    description: The product category.
                  translations:
                    type: object
                    properties:
                      ar:
                        type: string
                        description: Product details in Arabic.
                      name:
                        type: string
                        description: Product name in Arabic.
                      description:
                        type: string
                        description: Product description in Arabic.
                    x-apidog-orders:
                      - ar
                      - name
                      - description
                    required:
                      - ar
                      - name
                      - description
                    description: >-
                      Brand translations are based on the store's enabled
                      language locale. For instance, if the store supports both
                      Arabic and English, the translations object will return
                      two entries: ar for Arabic and en for English.
                    x-apidog-ignore-properties: []
                x-apidog-orders:
                  - id
                  - name
                  - description
                  - type
                  - enabled
                  - brands
                  - categories
                  - translations
                required:
                  - id
                  - name
                  - description
                  - type
                  - enabled
                  - brands
                  - categories
                  - translations
                x-apidog-ignore-properties: []
          x-apidog-orders:
            - status
            - sucess
            - data
          description: Products Size Guide Data Schema
          required:
            - status
            - sucess
            - data
          title: ''
          x-apidog-ignore-properties: []
      x-apidog-folder: ''
    NotFound:
      type: object
      properties:
        code:
          anyOf:
            - type: string
            - type: number
          description: >-
            Not Found Response error code, a numeric or alphanumeric unique
            identifier used to represent the error.
        message:
          type: string
          description: >-
            A message or data structure that is generated or returned when the
            response is not found or explain the error.
      x-apidog-orders:
        - code
        - message
      required:
        - code
        - message
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
