# List Abandoned Carts

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /carts/abandoned:
    get:
      summary: List Abandoned Carts
      deprecated: false
      description: >-
        This endpoint allows you to list abandoned carts that contain all the
        needed data that you can use to help customers continue the purchasing
        process.


        :::info[]

        An abandoned cart occurs when a customer adds an item to their cart and
        does not complete the purchase.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `carts.read`- Carts Read Only

        </Accordion>
      operationId: List-Abandoned-Carts
      tags:
        - Merchant API/APIs/Abandoned Carts
        - Abandoned Carts
      parameters:
        - name: page
          in: query
          description: The Pagination page number
          required: false
          schema:
            type: integer
        - name: offset
          in: query
          description: >-
            Returns all the carts created within or before an offset time. EX:
            `15` minutes
          required: false
          schema:
            type: integer
        - name: per_page
          in: query
          description: The number of abandoned carts to show in one query
          required: false
          schema:
            type: number
            maximum: 60
        - name: keyword
          in: query
          description: Search carts by `customer_id` or the customer's mobile number
          required: false
          example: ''
          schema:
            type: string
            examples:
              - '928374983'
              - '+966560000000'
              - '966560000000'
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/abandondCarts_response_body'
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
                    carts.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: AbandonedCart
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Abandoned Carts
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394138-run
components:
  schemas:
    abandondCarts_response_body:
      type: object
      properties:
        status:
          type: string
          x-stoplight:
            id: 4tnx58nzhl7qs
        success:
          type: string
          x-stoplight:
            id: h9pg8j6nr73ko
        data:
          type: array
          x-stoplight:
            id: hr833l1r6bwk4
          items:
            $ref: '#/components/schemas/AbandonedCart'
        pagination:
          $ref: '#/components/schemas/Pagination'
      x-apidog-orders:
        - status
        - success
        - data
        - pagination
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Pagination:
      type: object
      title: Pagination
      description: >-
        For a better response behavior as well as maintain the best security
        level, All retrieving API endpoints use a mechanism to retrieve data in
        chunks called pagination.  Pagination working by return only a specific
        number of records in each response, and through passing the page number
        you can navigate the different pages.
      properties:
        count:
          type: number
          description: Number of returned results.
        total:
          type: number
          description: Number of all results.
        perPage:
          type: number
          description: Number of results per page.
          maximum: 65
        currentPage:
          type: number
          description: Number of current page.
        totalPages:
          type: number
          description: Number of total pages.
        links:
          type: object
          properties:
            next:
              type: string
              description: Next Page
            previous:
              type: string
              description: Previous Page
          x-apidog-orders:
            - next
            - previous
          description: Array of linkes to next and previous pages.
          required:
            - next
            - previous
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - count
        - total
        - perPage
        - currentPage
        - totalPages
        - links
      required:
        - count
        - total
        - perPage
        - currentPage
        - totalPages
        - links
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    AbandonedCart:
      title: AbandonedCart
      type: object
      properties:
        id:
          type: number
          description: >-
            A unique identifier associated with a shopping cart. List of
            abandond carts can be found
            [here](https://docs.salla.dev/api-5394138).
        total:
          type: object
          properties:
            amount:
              type: number
              description: The sum of the prices of all the items in a cart.
            currency:
              type: string
              description: The currency of the prices of all the items in a cart.
          x-apidog-orders:
            - amount
            - currency
          required:
            - amount
            - currency
          x-apidog-ignore-properties: []
        subtotal:
          type: object
          properties:
            amount:
              type: number
              description: The sub-total cost of items in a shopping cart.
            currency:
              type: string
              description: The currency of the sub-total amount of the items in a cart.
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
              type: number
              description: >-
                Total amount of reduction in price applied to the items in a
                cart.
            currency:
              type: string
              description: 'The currency of the total discount amount for a cart. '
          x-apidog-orders:
            - amount
            - currency
          required:
            - amount
            - currency
          x-apidog-ignore-properties: []
        checkout_url:
          type: string
          description: >-
            A web link that directs a user to a specific page where they can
            proceed with the final steps of a purchase.
        age_in_minutes:
          type: number
          description: >-
            The age of cart, aka the time difference between cart's `created_at`
            and time now in minutes.
        created_at:
          type: object
          properties:
            date:
              type: string
              description: Timestamp to indicate the created date of the cart.
            timezone_type:
              type: number
              description: 'Timezone type of the cart created date '
            timezone:
              type: string
              description: Timezone value for the cart created date.
          x-apidog-orders:
            - date
            - timezone_type
            - timezone
          required:
            - date
            - timezone_type
          x-apidog-ignore-properties: []
        updated_at:
          type: object
          properties:
            date:
              type: string
              description: >-
                The specific date and time when the information of a cart was
                last updated. 
            timezone_type:
              type: number
              description: >-
                The timezone type of the specific date and time when the
                information of a cart was last updated. 
            timezone:
              type: string
              description: >-
                Timezone value of the specific date and time when the
                information of a cart was last updated. 
          x-apidog-orders:
            - date
            - timezone_type
            - timezone
          required:
            - date
            - timezone_type
            - timezone
          x-apidog-ignore-properties: []
        customer:
          type: object
          properties:
            id:
              type: number
              description: >-
                A unique identifier associated with an individual or entity in a
                database or system. List of customers can be found
                [here](https://docs.salla.dev/api-5394121).
            name:
              type: string
              description: >-
                The personal or business name of an individual or entity that is
                a customer of a company or organization.
            mobile:
              type: string
              description: >-
                The numerical contact information belonging to a customer that
                allows communication via telephone.
            email:
              type: string
              description: Email address of the customer used for electronic communication.
            avatar:
              type: string
              description: >-
                Image representing the customer, often a personal photograph or
                avatar.
            country:
              type: string
              description: The nation of origin or residence of the customer.
            city:
              type: string
              description: The city where the customer resides.
          x-apidog-orders:
            - id
            - name
            - mobile
            - email
            - avatar
            - country
            - city
          required:
            - id
            - name
            - mobile
            - email
            - avatar
            - country
            - city
          x-apidog-ignore-properties: []
        coupon:
          type: object
          properties:
            id:
              type: number
              description: >-
                A unique identifier assigned to a specific coupon or discount
                code in an e-commerce or promotional system. List of coupons can
                be found [here](https://docs.salla.dev/api-5394275).
            code:
              type: string
              description: >-
                A specific alphanumeric sequence or combination of charactersof
                the coupon.
            status:
              type: string
              description: >-
                The current condition or state of a coupon in [Salla
                Store](https://.salla.sa).
              enum:
                - expired
                - pending
                - invalid
                - forbidden
                - purchased
                - active
              x-apidog-enum:
                - value: expired
                  name: ''
                  description: The coupon validity has expired.
                - value: pending
                  name: ''
                  description: The coupon is pending.
                - value: invalid
                  name: ''
                  description: The coupon is not longer valid.
                - value: forbidden
                  name: ''
                  description: The coupon is forbidden.
                - value: purchased
                  name: ''
                  description: The coupon have been purchased.
                - value: active
                  name: ''
                  description: The coupon is active.
            type:
              type: string
              description: The category or classification of a coupon.
            amount:
              type: object
              properties:
                amount:
                  type: number
                  description: Amount value of the coupon.
                currency:
                  type: string
                  description: Amount currency of the coupon
              x-apidog-orders:
                - amount
                - currency
              required:
                - amount
                - currency
              x-apidog-ignore-properties: []
            minimum_amount:
              type: object
              properties:
                amount:
                  type: number
                  description: >-
                    Minimum amount value the cart total amount should reach to
                    get the coupon.
                currency:
                  type: string
                  description: >-
                    The currency of the minimum amount the cart needs to reach
                    to benifit from the coupon.
              x-apidog-orders:
                - amount
                - currency
              required:
                - amount
                - currency
              x-apidog-ignore-properties: []
            expiry_date:
              type: string
              description: >-
                The specific date and time when a coupon becomes invalid or no
                longer usable
            created_at:
              type: object
              properties:
                date:
                  type: string
                  description: >-
                    Timestamp indicating the date and time of creating the
                    coupon.
                timezone_type:
                  type: number
                  description: >-
                    Timezone type of the timestamp indicating the date and time
                    of creating the coupon.
                timezone:
                  type: string
                  description: >-
                    Timezone value of the timestamp indicating the date and time
                    of creating the coupon.
              x-apidog-orders:
                - date
                - timezone_type
                - timezone
              required:
                - date
                - timezone_type
                - timezone
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - id
            - code
            - status
            - type
            - amount
            - minimum_amount
            - expiry_date
            - created_at
          required:
            - id
            - code
            - status
            - type
            - amount
            - minimum_amount
            - expiry_date
            - created_at
          x-apidog-ignore-properties: []
        items:
          type: array
          items:
            type: object
            properties:
              id:
                type: number
                description: >-
                  A unique identifier associated with each individual item added
                  to a cart.
              product_id:
                type: number
                description: A unique identifier associated with a specific product.
              quantity:
                type: number
                description: >-
                  The numerical value representing the number of units or items
                  of a specific product that a customer has added to their cart.
              weight:
                type: number
                description: >-
                  The numerical measurement representing the weight of an
                  individual item or product within an order. 
                x-stoplight:
                  id: 789s82dmlwo2a
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
                        x-apidog-ignore-properties: []
                    x-apidog-orders:
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
                    x-apidog-ignore-properties: []
                x-apidog-orders:
                  - price_without_tax
                  - total_discount
                  - tax
                  - total
                x-apidog-ignore-properties: []
              notes:
                type: string
                description: Order items notes
            x-apidog-orders:
              - id
              - product_id
              - quantity
              - weight
              - amounts
              - notes
            x-apidog-ignore-properties: []
      x-apidog-orders:
        - id
        - total
        - subtotal
        - total_discount
        - checkout_url
        - age_in_minutes
        - created_at
        - updated_at
        - customer
        - coupon
        - items
      required:
        - id
        - total
        - subtotal
        - total_discount
        - checkout_url
        - age_in_minutes
        - created_at
        - updated_at
        - customer
        - coupon
        - items
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
