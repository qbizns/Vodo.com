# Create Custom Order Status

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /orders/statuses:
    post:
      summary: Create Custom Order Status
      deprecated: false
      description: >-
        This endpoint allows you to create a custom order status using the
        parameters available to be sent as body request.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `orders.read_write` - Orders Read & Write

        </Accordion>
      operationId: post-orders-statuses-status
      tags:
        - Merchant API/APIs/Order Status
        - Order Status
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/post_customSubStatus_request_body'
            example:
              parent_id: 863076598
              name: تبقى 40 دقيقة على الدفع
              message: أكمل الدفع الان
              icon: sicon-cup-hot
              sort: 3
              is_active: true
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/orderStatuses_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 120960473
                  name: تبقى 40 دقيقة على الدفع
                  type: custom
                  slug: payment_pending
                  message: أكمل الدفع الان
                  color: '#ffff'
                  icon: sicon-cup-hot
                  sort: 3
                  is_active: true
                  original:
                    id: 1473353380
                    name: بإنتظار الدفع
                  parent:
                    id: 863076598
                    name: في انتظارك تدفع
                  children: []
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
        '422':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/error_validation_422'
              examples:
                '3':
                  summary: Example 1
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        parent_id:
                          - حقل رقم الحالة الرئيسية - الأب  مطلوب.
                        name:
                          - حقل عنوان الحالة - اسم الحالة مطلوب.
                        message:
                          - حقل نص الرسالة الفرعية مطلوب.
                '4':
                  summary: Example 2
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        parent_id:
                          - >-
                            orders::custom_status.messages.error.you_cant_add_sub_status_for_nun_main_status
                '5':
                  summary: 'Example 3 '
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        name:
                          - يجب أن يكون حقل عنوان الحالة - اسم الحالة نصآ.
                        is_active:
                          - 'يجب أن تكون قيمة حقل الفعالية إما true أو false '
                '6':
                  summary: Example 4
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        parent_id:
                          - 'رقم الحالة الرئيسية - الأب - غير صالح '
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-salla-php-method-name: createCustomStatus
      x-salla-php-return-type: OrderStatuses
      x-apidog-folder: Merchant API/APIs/Order Status
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394149-run
components:
  schemas:
    post_customSubStatus_request_body:
      type: object
      properties:
        parent_id:
          type: integer
          description: >-
            Order Status Parent ID. List of Order Statuses can be found
            [here](https://docs.salla.dev/api-5394150)
          examples:
            - 863076598
        name:
          type: string
          description: Order Status Name.
          examples:
            - تبقى 40 دقيقة على الدفع
        message:
          type: string
          description: Order Status Message.
          examples:
            - أكمل الدفع الان
        icon:
          type: string
          description: Order Status Icon.
          examples:
            - sicon-cup-hot
        sort:
          type: integer
          description: Order Status Sort.
          examples:
            - 3
        is_active:
          type: boolean
          description: Whether or not the Order Status is active.
          default: true
      required:
        - parent_id
        - name
        - message
      x-apidog-orders:
        - parent_id
        - name
        - message
        - icon
        - sort
        - is_active
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    orderStatuses_response_body:
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
          $ref: '#/components/schemas/OrderStatuses%20'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    'OrderStatuses ':
      title: OrderStatuses
      type: object
      properties:
        id:
          type: number
          description: >-
            A unique identifier associated with a specific status assigned to an
            order.

            List of order statuses can be found
            [here](https://docs.salla.dev/api-5394150)
          examples:
            - 863076598
        name:
          type: string
          description: >-
            A label or designation given to a specific status assigned to an
            order. [🌐Support multi-language](doc-421122)
          examples:
            - في انتظار الدفع
        type:
          type: string
          description: The categorization or classification of an order.
          enum:
            - original
            - custom
          examples:
            - custom
          x-apidog-enum:
            - value: original
              name: ''
              description: Original order status by Salla.
            - value: custom
              name: ''
              description: Custom order status, made by the Merchant.
        slug:
          type: string
          description: >-
            A user-friendly and URL-friendly text string associated with a
            specific order. __Note__ the parent slug is inherited.
          examples:
            - payment_pending
        message:
          type: string
          description: >-
            A remark that provides information about the current status or
            condition of an order. [🌐Support multi-language](doc-421122)
          examples:
            - '[ {store.name} ] \\n أصبحت حالة طلبك {order.id} {status}'
        color:
          type: string
          description: >-
            A specific color code or indicator assigned to different order
            statuses.
          examples:
            - '#58C9B9'
        icon:
          type: string
          description: >-
            Graphical symbol or image used to represent different order
            statuses.
          examples:
            - sicon-gold-badge
        sort:
          type: number
          description: >-
            The specific numerical or alphanumeric sequence assigned to each
            order status in a list or database.
          examples:
            - 0
        is_active:
          type: boolean
          description: The option to indicate that the order status is active.
          default: true
        original:
          type: object
          properties:
            id:
              type: number
              description: >-
                A unique identifier associated with the initial or default
                status of an order.
              examples:
                - 349994915
            name:
              type: string
              description: >-
                The label assigned to the status of an order when it was first
                placed.
              examples:
                - جاري التوصيل
          x-apidog-orders:
            - id
            - name
          required:
            - id
            - name
          x-apidog-ignore-properties: []
          nullable: true
        parent:
          type: object
          properties:
            id:
              type: number
              description: A unique identifier associated to the parent order.
              examples:
                - 1638621685
            name:
              type: string
              description: The name or label assigned to the parent order.
              examples:
                - تم التنفيذ
          x-apidog-orders:
            - id
            - name
          required:
            - id
            - name
          x-apidog-ignore-properties: []
          nullable: true
        children:
          type: array
          items:
            type: object
            properties:
              id:
                type: number
                description: >-
                  A unique identifier associated with a specific status assigned
                  to an order.
                examples:
                  - 863076598
              name:
                type: string
                description: The name or label assigned to the order.
                examples:
                  - في انتظار الدفع
              slug:
                type: string
                description: >-
                  A user-friendly and URL-friendly text string associated with a
                  specific order.
                examples:
                  - payment_pending
              type:
                type: string
                description: The classification or categorization of an order.
                enum:
                  - original
                  - custom
                examples:
                  - custom
                x-apidog-enum:
                  - value: original
                    name: ''
                    description: Original order status.
                  - value: custom
                    name: ''
                    description: Custom order status.
              message:
                type: string
                description: >-
                  A notification that provides information about the current
                  status of the order.
                examples:
                  - '[ {store.name} ] \\n أصبحت حالة طلبك {order.id} {status}'
              color:
                type: string
                description: >-
                  A specific color code or indicator assigned to different order
                  statuses.
                examples:
                  - '#58C9B9'
              icon:
                type: string
                description: >-
                  A graphical symbol or image used to represent different order
                  statuses.
                examples:
                  - sicon-shipping
              sort:
                type: number
                description: Order status sort order in a list of order statuses.
                examples:
                  - 0
              is_active:
                type: boolean
                description: "The option to indicate order status is 'Active'.\r\n"
                default: true
            x-apidog-orders:
              - id
              - name
              - slug
              - type
              - message
              - color
              - icon
              - sort
              - is_active
            x-apidog-ignore-properties: []
          nullable: true
      x-apidog-orders:
        - id
        - name
        - type
        - slug
        - message
        - color
        - icon
        - sort
        - is_active
        - original
        - parent
        - children
      required:
        - id
        - name
        - type
        - slug
        - message
        - color
        - icon
        - sort
        - is_active
        - original
        - parent
        - children
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
