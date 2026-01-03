# Order Status Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /orders/statuses/{status_id}:
    get:
      summary: Order Status Details
      deprecated: false
      description: >-
        This endpoint allows you to fetch details about specific order status by
        passing the `status_id` as a path parameter. 


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `orders.read` - Orders Read Only

        </Accordion>
      operationId: get-orders-statuses-status_id
      tags:
        - Merchant API/APIs/Order Status
        - Order Status
      parameters:
        - name: status_id
          in: path
          description: >-
            Unique identification number assigned to a status. List of Status
            IDs can be found [here](https://docs.salla.dev/api-5394150)
          required: true
          example: 0
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/orderStatuses_response_body'
              examples:
                '1':
                  summary: Example | Custom Status Type
                  value:
                    status: 200
                    success: true
                    data:
                      id: 1638621685
                      name: تم التنفيذ
                      type: custom
                      slug: completed
                      message: '[ {store.name} ] \n أصبحت حالة طلبك {order.id} {status}'
                      color: '#58C9B9'
                      icon: sicon-gold-badge
                      sort: 0
                      is_active: true
                      original:
                        id: 1298199463
                        name: تم التنفيذ
                      parent: {}
                      children:
                        - id: 1422535667
                          name: جاري التوصيل
                          slug: delivering
                          type: custom
                          message: >-
                            [ {store.name} ] \n أصبحت حالة طلبك {order.id}
                            {status}
                          color: '#58C9B9'
                          icon: sicon-shipping
                          sort: 0
                          is_active: true
                        - id: 647449340
                          name: تم التوصيل
                          slug: delivered
                          type: custom
                          message: >-
                            [ {store.name} ] \n أصبحت حالة طلبك {order.id}
                            {status}
                          color: '#58C9B9'
                          icon: sicon-party-horn
                          sort: 1
                          is_active: true
                        - id: 1887201789
                          name: تم الشحن
                          slug: shipped
                          type: custom
                          message: >-
                            [ {store.name} ] \n أصبحت حالة طلبك {order.id}
                            {status}
                          color: '#58C9B9'
                          icon: sicon-shipping-fast
                          sort: 2
                          is_active: true
                '3':
                  summary: Example | Original Status Type
                  value:
                    status: 200
                    success: true
                    data:
                      id: 1473353380
                      name: بإنتظار الدفع
                      type: original
                      slug: payment_pending
                      message: '[ {store.name} ] \n أصبحت حالة طلبك {order.id} {status}'
                      color: '#f55157'
                      icon: uea77
                      sort: 1
                      is_active: true
                      original: {}
                      parent: {}
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
                    orders.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: retrieveStatuses
      x-salla-php-return-type: OrderStatuses
      x-apidog-folder: Merchant API/APIs/Order Status
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394151-run
components:
  schemas:
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
