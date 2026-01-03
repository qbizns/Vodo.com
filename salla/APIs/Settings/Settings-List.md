# Settings List

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /settings:
    get:
      summary: Settings List
      deprecated: false
      description: >-
        This endpoint allows you to fetch the list of the main settings
        associated with the store to enable / disable / show / hide store
        features based on a specific entity


        :::info[Read More]

        For more on Store Settings, check the Merchant's Help Desk article
        [here](https://help.salla.sa/article/1887201789).

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `store-settings.read`- Settings Read Only

        </Accordion>
      operationId: get-settings-entity
      tags:
        - Merchant API/APIs/Settings
        - Settings
      parameters:
        - name: entity
          in: query
          description: 'Choose which entity to fetch the related settings '
          required: true
          example: orders
          schema:
            type: string
            enum:
              - products
              - orders
              - customers
              - reports
              - blogs
              - mahally
              - feedbacks
              - shipping
              - store
            x-apidog-enum:
              - name: ''
                value: products
                description: ''
              - name: ''
                value: orders
                description: ''
              - name: ''
                value: customers
                description: ''
              - name: ''
                value: reports
                description: ''
              - name: ''
                value: blogs
                description: ''
              - name: ''
                value: mahally
                description: ''
              - name: ''
                value: feedbacks
                description: ''
              - value: shipping
                name: ''
                description: ''
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/settings_response_body'
              examples:
                '1':
                  summary: Success | Orders Entity
                  value:
                    status: 200
                    success: true
                    data:
                      - slug: orders.receive_orders
                        type: form
                        value: null
                      - slug: orders.receiving_times
                        type: form
                        value: null
                      - slug: orders.order_notes
                        type: form
                        value: null
                      - slug: orders.cancel_order
                        type: form
                        value: null
                      - slug: orders.auto_complete
                        type: form
                        value: null
                      - slug: orders.auto_return_stock
                        type: form
                        value: null
                      - slug: orders.disable_bank_transfer_payment_period
                        type: boolean
                        value: false
                      - slug: orders.shipping_indicator
                        type: boolean
                        value: true
                      - slug: orders.price_quote
                        type: boolean
                        value: false
                      - slug: orders.reorder
                        type: boolean
                        value: true
                      - slug: orders.agreement
                        type: form
                        value: null
                      - slug: orders.complete_customization
                        type: form
                        value: null
                      - slug: orders.invoices_customization
                        type: form
                        value: null
                      - slug: orders.shipping_policy_deduction
                        type: form
                        value: null
                      - slug: orders.minimum_amount
                        type: string
                        value: '123.23'
                      - slug: orders.attache_note_notify_status
                        type: boolean
                        value: true
                      - slug: orders.status_notifications
                        type: dropdown
                        value:
                          - email
                      - slug: orders.digital_products_notify
                        type: dropdown
                        value:
                          - email
                          - sms
                '4':
                  summary: Success | Products Entity
                  value:
                    status: 200
                    success: true
                    data:
                      - slug: products.duplicate_product_in_cart
                        type: boolean
                        value: true
                      - slug: products.purchase_count
                        type: form
                        value: null
                      - slug: products.quantity_sort
                        type: boolean
                        value: true
                      - slug: products.more_details_button
                        type: boolean
                        value: true
                      - slug: products.zero_price_indicator
                        type: boolean
                        value: true
                      - slug: products.recommendations
                        type: form
                        value: null
                      - slug: products.show_special_offers
                        type: boolean
                        value: true
                      - slug: products.auto_add_product_offer_to_cart
                        type: boolean
                        value: true
                      - slug: products.show_start_from_price
                        type: boolean
                        value: false
                      - slug: products.digital_protection
                        type: boolean
                        value: false
                      - slug: products.show_weight
                        type: boolean
                        value: true
                      - slug: products.show_sku
                        type: boolean
                        value: false
                      - slug: products.show_hs_code
                        type: boolean
                        value: true
                      - slug: products.product_price_included_tax
                        type: boolean
                        value: true
                      - slug: products.default_weight
                        type: object
                        value:
                          unit: g
                          weight: 125
                      - slug: products.product_notify_availability
                        type: form
                        value: null
                      - slug: products.product_inventory
                        type: form
                        value: null
                      - slug: products.brand_options
                        type: form
                        value: null
                '5':
                  summary: Success| Reports Entity
                  value:
                    status: 200
                    success: true
                    data:
                      - slug: reports.order_statuses
                        type: form
                        value: null
                '6':
                  summary: Success | Empty response
                  value:
                    status: 200
                    success: true
                    data: []
                '7':
                  summary: Success | Marketing Entity
                  value:
                    status: 200
                    success: true
                    data:
                      - slug: marketing.settings.coupon_in_cart
                        type: boolean
                        value: false
                      - slug: marketing.settings.enable_offers_coupons
                        type: boolean
                        value: true
                      - slug: marketing.settings.show_offers
                        type: boolean
                        value: true
                '8':
                  summary: Success | Feedbacks Success
                  value:
                    status: 200
                    success: true
                    data:
                      - slug: feedbacks.publish_testimonials
                        type: boolean
                        value: true
                      - slug: feedbacks.publish_ratings
                        type: boolean
                        value: true
                      - slug: feedbacks.allow_attach_images
                        type: boolean
                        value: false
                      - slug: feedbacks.allow_likes
                        type: boolean
                        value: false
                      - slug: feedbacks.show_rating_summary
                        type: boolean
                        value: false
                      - slug: feedbacks.allow_contact_support
                        type: boolean
                        value: false
                      - slug: feedbacks.testimonials_enabled
                        type: boolean
                        value: false
                      - slug: feedbacks.shipping_enabled
                        type: boolean
                        value: false
                      - slug: feedbacks.products_enabled
                        type: boolean
                        value: false
                      - slug: feedbacks.allow_hidden_names
                        type: boolean
                        value: false
                      - slug: feedbacks.display_testimonials
                        type: boolean
                        value: false
                      - slug: feedbacks.display_customer_reviews
                        type: boolean
                        value: false
                      - slug: feedbacks.display_product_reviews_on_app
                        type: boolean
                        value: false
                      - slug: feedbacks.publish_comments
                        type: boolean
                        value: true
                      - slug: feedbacks.pages_feedback_enabled
                        type: boolean
                        value: true
                      - slug: feedbacks.products_feedback_enabled
                        type: boolean
                        value: true
                      - slug: feedbacks.disable_guest_feedback
                        type: boolean
                        value: true
                      - slug: feedbacks.rating_message
                        type: form
                        value: null
                      - slug: feedbacks.update_rating
                        type: form
                        value: null
                      - slug: feedbacks.thanks_message
                        type: form
                        value: null
                '9':
                  summary: Success | Store
                  value:
                    status: 200
                    success: true
                    data:
                      - slug: store.maintenance
                        type: form
                        value: null
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
                    store-settings.read,store-settings.read_write
          headers: {}
          x-apidog-name: error_unauthorized_401
        '422':
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
                    $ref: '#/components/schemas/Validation'
                x-apidog-orders:
                  - status
                  - success
                  - error
                x-apidog-ignore-properties: []
              example:
                status: 422
                success: false
                error:
                  code: error
                  message: alert.invalid_fields
                  fields:
                    entity:
                      - حقل entity مطلوب.
          headers: {}
          x-apidog-name: error_validation_422
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Settings
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-6965777-run
components:
  schemas:
    settings_response_body:
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
          items:
            $ref: '#/components/schemas/Settings'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Settings:
      type: object
      properties:
        slug:
          type: string
          description: >-
            A unique identifier used to reference a specific setting or
            configuration within a system or application.
        type:
          type: string
          enum:
            - form
            - object
            - string
            - boolean
            - integer
            - dropdown
          description: Settings type enum values
          x-apidog-enum:
            - name: ''
              value: form
              description: >-
                Settings variable is of type form. Find more about Settings from
                [here](https://salla.dev/blog/stand-out-with-theme-settings/)
            - name: ''
              value: object
              description: >-
                Settings variable is of type object. Find more about Settings
                from
                [here](https://salla.dev/blog/stand-out-with-theme-settings/)
            - name: ''
              value: string
              description: >-
                Settings variable is of type string. Find more about Settings
                from
                [here](https://salla.dev/blog/stand-out-with-theme-settings/)
            - name: ''
              value: boolean
              description: >-
                Settings variable is of type boolean. Find more about Settings
                from
                [here](https://salla.dev/blog/stand-out-with-theme-settings/)
            - name: ''
              value: integer
              description: >-
                Settings variable is of type integer. Find more about Settings
                from
                [here](https://salla.dev/blog/stand-out-with-theme-settings/)
            - name: ''
              value: dropdown
              description: >-
                Settings variable is of type dropdown. Find more about Settings
                from
                [here](https://salla.dev/blog/stand-out-with-theme-settings/)
        value:
          type: string
      x-apidog-orders:
        - slug
        - type
        - value
      required:
        - slug
        - type
        - value
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
