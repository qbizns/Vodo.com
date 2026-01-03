# Create Order Item

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /orders/items:
    post:
      summary: Create Order Item
      deprecated: false
      description: >-
        This endpoint allows you to create an order item for a specific order by
        passing its ID in the body request.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `orders.read_write` - Orders Read & Write

        </Accordion>
      operationId: post-orders-items
      tags:
        - Merchant API/APIs/Order Items
        - Order Items
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/orderItem_request_body'
            example:
              order_id: 837293123
              identifier_type: id
              identifier: 123456
              quantity: 1
              branch_id: 12342
              options:
                - id: 789012
                  value:
                    - value1
                    - value2
              name: Custom Product Name
              price: 100
              cost_price: 80
              weight: 150
              weight_type: g
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/orderItem_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 365435777
                    name: SSD
                    sku: sam-ssd-256g
                    quantity: 1
                    currency: SAR
                    weight: 0.51
                    weight_label: ٥١٠ جم
                    amounts:
                      price_without_tax:
                        amount: 150
                        currency: SAR
                      total_discount:
                        amount: 0
                        currency: SAR
                      tax:
                        percent: '15.00'
                        amount:
                          amount: 22.5
                          currency: SAR
                      total:
                        amount: 150
                        currency: SAR
                    notes: ''
                    options:
                      - id: 675638105
                        product_option_id: 1902643925
                        name: size
                        type: radio
                        value:
                          id: 1090448197
                          name: 256G
                          price:
                            amount: 0
                            currency: SAR
                    images: []
                    codes: []
                    files: []
                    reservations: []
                    branches_quantity: []
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
                    orders.read_write
          headers: {}
          x-apidog-name: Unauthorized
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
              example:
                status: 404
                success: false
                error:
                  code: error
                  message: المحتوى الذي تحاول الوصول اليه غير متوفر
          headers: {}
          x-apidog-name: Not Found
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Order Items
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5751402-run
components:
  schemas:
    orderItem_request_body:
      type: object
      properties:
        order_id:
          type: integer
          description: >-
            Order ID. List of Order ID can be found
            [here](https://docs.salla.dev/api-5394146)
        identifier_type:
          type: string
          description: Product item identifier type
          enum:
            - id
            - sku
          x-apidog-enum:
            - value: id
              name: ''
              description: ''
            - value: sku
              name: ''
              description: ''
        identifier:
          type: integer
          description: >-
            Order Item Identifier ID. List of Order Items can be found
            [here](https://docs.salla.dev/api-5565737)
        quantity:
          type: integer
          description: Order Item Quantity
        branch_id:
          type: integer
          description: >-
            Branch ID. List of Branch ID can be found
            [here](https://docs.salla.dev/api-5394224).
        options:
          type: array
          items:
            type: object
            properties:
              id:
                type: integer
                description: Product Option ID
              value:
                type: array
                items:
                  type: string
            x-apidog-orders:
              - id
              - value
            x-apidog-ignore-properties: []
        name:
          type: string
          description: >-
            Product Name, list of products can be found
            [here](https://docs.salla.dev/api-5394168).
        price:
          type: integer
          description: Product Price.
        cost_price:
          type: integer
          description: Product Cost Price.
        weight:
          type: integer
          description: Product Weight Value.
        weight_type:
          type: string
          description: Product Weight Type.
      x-apidog-orders:
        - order_id
        - identifier_type
        - identifier
        - quantity
        - branch_id
        - options
        - name
        - price
        - cost_price
        - weight
        - weight_type
      required:
        - order_id
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    orderItem_response_body:
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
        data:
          type: array
          x-stoplight:
            id: orurptuq9pvoy
          items:
            $ref: '#/components/schemas/OrderItem'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    OrderItem:
      description: >-
        Detailed structure of the Order Item object showing its fields and data
        types.
      type: object
      title: OrderItem
      x-tags:
        - Models
      x-examples: {}
      properties:
        id:
          type: number
          description: >-
            A unique identifier, typically numerical or alphanumeric, assigned
            to an individual item or product within an order.
        name:
          type: string
          description: >-
            The name or description of an individual item or product within an
            order.
        sku:
          type: string
          description: >-
            Stock Keeping Unit, and it is a unique code or identifier used to
            track and manage individual products or items in inventory,
            facilitating inventory management, sales tracking, and product
            identification.
        quantity:
          type: integer
          description: >-
            The numerical count of a specific item or product included in an
            order, indicating how many of that particular item have been
            purchased or are part of the order.
        currency:
          type: string
          description: >-
            The specific currency in which the price or value of an individual
            item within an order is expressed, indicating the monetary unit used
            for pricing that particular item.
        weight:
          type: number
          description: >-
            The numerical measurement representing the weight of an individual
            item or product within an order. 
        weight_label:
          type: string
          description: >-
            A textual label or description associated with the weight of an
            individual item within an order, typically used to indicate the unit
            of measurement (e.g., kg, lb) and provide clarity regarding how the
            item's weight is expressed.
        amounts:
          type: object
          properties:
            price_without_tax:
              type: object
              properties:
                amount:
                  type: integer
                  description: 'Order item amounts price without tax '
                currency:
                  type: string
                  description: Order item amounts price without tax currency
              x-apidog-orders:
                - amount
                - currency
              required:
                - amount
                - currency
              x-apidog-ignore-properties: []
            total_discount:
              type: object
              properties:
                amount:
                  type: integer
                  description: Total discount amount of the order item amounts.
                currency:
                  type: string
                  description: Order item amounts total discount currency
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
                  type: string
                  description: Order item amounts tax percent
                amount:
                  type: object
                  properties:
                    amount:
                      type: integer
                      description: Order item amounts tax amount
                    currency:
                      type: string
                      description: Order item amounts tax caurrency
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
                  type: integer
                  description: Order item amounts total amount
                currency:
                  type: string
                  description: Total discount currency of the order item amounts.
              x-apidog-orders:
                - amount
                - currency
              required:
                - amount
                - currency
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - price_without_tax
            - total_discount
            - tax
            - total
          required:
            - price_without_tax
            - total_discount
            - tax
            - total
          x-apidog-ignore-properties: []
        notes:
          type: string
          description: Order items notes
        options:
          type: array
          items:
            type: object
            properties:
              id:
                type: number
                description: >-
                  A unique identifier, often numerical or alphanumeric, assigned
                  to a specific item.
              product_option_id:
                type: number
                description: >-
                  A unique identifier, often numerical or alphanumeric, assigned
                  to a specific product option , enabling easy tracking and
                  management of various product configurations, such as size,
                  color, or other customizable features.
              name:
                type: string
                description: A label for a product variation or choice, like size or color.
              type:
                type: string
                description: Type of the product option.
                enum:
                  - radio
                  - date
                  - datetime
                  - image
                  - text
                  - text area
                  - number
                  - checkbox
                  - splitter
                x-apidog-enum:
                  - value: radio
                    name: ''
                    description: Option type of radio
                  - value: date
                    name: ''
                    description: Option type of date
                  - value: datetime
                    name: ''
                    description: Option type of date and time
                  - value: image
                    name: ''
                    description: Option type of image
                  - value: text
                    name: ''
                    description: Option type of text
                  - value: text area
                    name: ''
                    description: Option type of text area
                  - value: number
                    name: ''
                    description: Option type of number
                  - value: checkbox
                    name: ''
                    description: Option type of checkbox
                  - value: splitter
                    name: ''
                    description: Option type of splitter
              value:
                type: array
                description: >-
                  If `type` value is set to `radio` or `checkbox`, the returned
                  response is an object. Otherwise, a string is returned in all
                  other available `type` values.
                items:
                  type: object
                  properties:
                    id:
                      type: number
                      description: >-
                        A unique identifier, typically numerical or
                        alphanumeric, associated with a specific value or choice
                        within a product option.
                    name:
                      type: string
                      description: >-
                        The descriptive label or text representing a specific
                        choice or value within a product option.
                    price:
                      type: object
                      properties:
                        amount:
                          type: integer
                          description: Option value amount.
                        currency:
                          type: string
                          description: Option value currency.
                      x-apidog-orders:
                        - amount
                        - currency
                      x-apidog-ignore-properties: []
                  x-apidog-orders:
                    - id
                    - name
                    - price
                  x-apidog-ignore-properties: []
            x-apidog-orders:
              - id
              - product_option_id
              - name
              - type
              - value
            x-apidog-ignore-properties: []
        images:
          type: array
          items:
            type: object
            properties:
              id:
                type: number
                description: >-
                  A unique identifier, typically numerical or alphanumeric,
                  associated with a specific product image in a database or
                  system, enabling easy tracking and referencing of images used
                  for a product.
              image:
                type: string
                description: >-
                  Textual reference, such as a file path or URL link, that
                  points to the location of an image file representing a
                  product. 
              type:
                type: string
                description: Type of the product image.
            x-apidog-orders:
              - id
              - image
              - type
            x-apidog-ignore-properties: []
        codes:
          type: array
          items:
            type: object
            properties:
              code:
                type: string
                description: Product codes value
              status:
                type: string
                description: Product codes status
            x-apidog-orders:
              - code
              - status
            x-apidog-ignore-properties: []
        files:
          type: array
          items:
            type: object
            properties:
              url:
                type: string
                description: >-
                  A web address (URL) that provides access to a file associated
                  with a product.
              name:
                type: string
                description: the name or title of a file associated with a product.
              size:
                type: number
                description: >-
                  The numerical measurement that represents the size of a file
                  associated with a product.
            x-apidog-orders:
              - url
              - name
              - size
            x-apidog-ignore-properties: []
        reservations:
          type: array
          items:
            type: object
            properties:
              id:
                type: number
                description: Product reservations unique identification.
              from:
                type: string
                description: >-
                  Product reservation starting time expressed in 24 hours
                  format.
                examples:
                  - '14:30'
              to:
                type: string
                description: Product reservation e time expressed in 24 hours format.
                examples:
                  - '17:30'
              date:
                type: string
                description: Prodcut reservation date.
                examples:
                  - '2022-01-10'
            x-apidog-orders:
              - id
              - from
              - to
              - date
            x-apidog-ignore-properties: []
        branches_quantity:
          type: array
          items:
            type: integer
            description: Quantity existing in branches
      x-apidog-orders:
        - id
        - name
        - sku
        - quantity
        - currency
        - weight
        - weight_label
        - amounts
        - notes
        - options
        - images
        - codes
        - files
        - reservations
        - branches_quantity
      required:
        - id
        - name
        - sku
        - quantity
        - currency
        - weight
        - weight_label
        - amounts
        - notes
        - options
        - images
        - codes
        - files
        - reservations
        - branches_quantity
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
