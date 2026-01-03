# List Order Statuses

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /orders/statuses:
    get:
      summary: List Order Statuses
      deprecated: false
      description: >-
        This endpoint allows you to fetch a list of all order statuses and
        sub-statuses.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `orders.read` - Orders Read Only

        </Accordion>
      operationId: List-Order-Statuses
      tags:
        - Merchant API/APIs/Order Status
        - Order Status
      parameters: []
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/get_orderStatuses_response_body'
              examples:
                '1':
                  summary: Example | Custom Status Type
                  value:
                    status: 200
                    success: true
                    data:
                      - id: 863076598
                        name: في انتظار الدفع
                        type: custom
                        slug: payment_pending
                        sort: 0
                        message: >-
                          [ {store.name} ] \n أصبحت حالة طلبك {order.id}
                          {status}
                        icon: sicon-trash
                        is_active: true
                        parent: {}
                        original:
                          id: 1473353380
                          name: بإنتظار الدفع
                      - id: 224309239
                        name: جاري مراجعة طلبك
                        type: custom
                        slug: under_review
                        sort: 1
                        message: ''
                        icon: sicon-trash
                        is_active: true
                        parent: {}
                        original:
                          id: 566146469
                          name: بإنتظار المراجعة
                      - id: 1597755120
                        name: بنفذ طلبك
                        type: custom
                        slug: in_progress
                        sort: 2
                        message: ''
                        icon: sicon-trash
                        is_active: true
                        parent: {}
                        original:
                          id: 1939592358
                          name: قيد التنفيذ
                      - id: 1638621685
                        name: تم التنفيذ
                        type: custom
                        slug: completed
                        sort: 3
                        message: ''
                        icon: sicon-trash
                        is_active: true
                        parent: {}
                        original:
                          id: 1298199463
                          name: تم التنفيذ
                      - id: 1422535667
                        name: جاري التوصيل
                        type: custom
                        slug: delivering
                        sort: 4
                        message: ''
                        icon: sicon-trash
                        is_active: true
                        parent:
                          id: 1638621685
                          name: تم التنفيذ
                        original:
                          id: 349994915
                          name: جاري التوصيل
                      - id: 647449340
                        name: تم التوصيل
                        type: custom
                        slug: delivered
                        sort: 5
                        message: ''
                        icon: sicon-trash
                        is_active: true
                        parent:
                          id: 1638621685
                          name: تم التنفيذ
                        original:
                          id: 1723506348
                          name: تم التوصيل
                      - id: 1887201789
                        name: تم الشحن
                        type: custom
                        slug: shipped
                        sort: 6
                        message: ''
                        icon: sicon-trash
                        is_active: false
                        parent:
                          id: 1638621685
                          name: تم التنفيذ
                        original:
                          id: 814202285
                          name: تم الشحن
                      - id: 687926769
                        name: ملغي
                        type: custom
                        slug: canceled
                        sort: 7
                        message: ''
                        icon: sicon-trash
                        is_active: true
                        parent: {}
                        original:
                          id: 525144736
                          name: ملغي
                      - id: 2062355698
                        name: مسترجع
                        type: custom
                        slug: restored
                        sort: 8
                        message: ''
                        icon: sicon-trash
                        is_active: true
                        parent: {}
                        original:
                          id: 989286562
                          name: مسترجع
                      - id: 1113229566
                        name: قيد الإسترجاع
                        type: custom
                        slug: restoring
                        sort: 9
                        message: ''
                        icon: sicon-trash
                        is_active: true
                        parent: {}
                        original:
                          id: 1548352431
                          name: قيد الإسترجاع
                '3':
                  summary: Example | Original Status Type
                  value:
                    status: 200
                    success: true
                    data:
                      - id: 1473353380
                        name: بإنتظار الدفع
                        type: original
                        slug: payment_pending
                        original: {}
                        parent: {}
                      - id: 566146469
                        name: بإنتظار المراجعة
                        type: original
                        slug: under_review
                        original: {}
                        parent: {}
                      - id: 1939592358
                        name: قيد التنفيذ
                        type: original
                        slug: in_progress
                        original: {}
                        parent: {}
                      - id: 1298199463
                        name: تم التنفيذ
                        type: original
                        slug: completed
                        original: {}
                        parent: {}
                      - id: 349994915
                        name: جاري التوصيل
                        type: original
                        slug: delivering
                        original: {}
                        parent: {}
                      - id: 1723506348
                        name: تم التوصيل
                        type: original
                        slug: delivered
                        original: {}
                        parent: {}
                      - id: 814202285
                        name: تم الشحن
                        type: original
                        slug: shipped
                        original: {}
                        parent: {}
                      - id: 525144736
                        name: ملغي
                        type: original
                        slug: canceled
                        original: {}
                        parent: {}
                      - id: 989286562
                        name: مسترجع
                        type: original
                        slug: restored
                        original: {}
                        parent: {}
                      - id: 1548352431
                        name: قيد الإسترجاع
                        type: original
                        slug: restoring
                        original: {}
                        parent: {}
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
      x-salla-php-method-name: listStatuses
      x-salla-php-return-type: ListOrderStatuses
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Order Status
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394150-run
components:
  schemas:
    get_orderStatuses_response_body:
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
            id: imhtproefjl6n
          items:
            $ref: '#/components/schemas/ListOrderStatuses'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    ListOrderStatuses:
      type: object
      title: ListOrderStatuses
      properties:
        id:
          type: number
          description: >-
            A unique identifier assigned to a specific order. Order status list
            can be found [here](https://docs.salla.dev/api-5394150).
          examples:
            - 863076598
        name:
          type: string
          description: >-
            Descriptive name or label associated with the current status of an
            order. [🌐Support multi-language](doc-421122)
          examples:
            - في انتظار الدفع
        type:
          type: string
          description: The category of a specific order, indicating its purpose or nature.
          enum:
            - original
            - custom
          examples:
            - custom
          x-apidog-enum:
            - value: original
              name: ''
              description: Original order statuses by Salla.
            - value: custom
              name: ''
              description: Custom order statuses, created by the Merchant
        slug:
          type: string
          description: >-
            A unique string or identifier used to represent and access a
            specific order .
          examples:
            - payment_pending
          enum:
            - payment_pending
            - waiting_for_payment_confirmation
            - payment_failed
            - waiting to receive it
            - in_progress
            - under_review
            - completed
            - delivering
            - delivered
            - shipped
            - canceled
            - restored
            - restoring
          x-apidog-enum:
            - value: payment_pending
              name: ''
              description: When the order is payment pending from the Merchant
            - value: waiting_for_payment_confirmation
              name: ''
              description: ' When the order''s payment is done by the customer and the Merchant is '
            - value: payment_failed
              name: ''
              description: '  When the order''s payment is failed to reach the Merchant'
            - value: waiting to receive it
              name: ''
              description: ''
            - value: in_progress
              name: ''
              description: 'When the order is in progress '
            - value: under_review
              name: ''
              description: When the order is  under review by the store owner
            - value: completed
              name: ''
              description: >-
                When the order is completed; aka paid and delivered to the
                customer
            - value: delivering
              name: ''
              description: When the order is being delivered at the moment to the customer
            - value: delivered
              name: ''
              description: When the order has been delievred to the customer
            - value: shipped
              name: ''
              description: When the order is shipped, on its way to the customer
            - value: canceled
              name: ''
              description: 'When the order is  cancelled '
            - value: restored
              name: ''
              description: When the order is restored to the store
            - value: restoring
              name: ''
              description: When the order is being restored to the store at the moment
        sort:
          type: integer
          description: >-
            The specific integer sequence assigned to each order status in a
            list or database, determining the order in which they are displayed
            or sorted. 
        message:
          type: string
          description: Status customized message
        icon:
          type: string
          description: Status Icon.
        is_active:
          type: boolean
          description: Whether or not the status is active
        parent:
          type: object
          properties:
            id:
              type: number
              description: The identifier or reference to the parent status of an order.
              examples:
                - 1638621685
            name:
              type: string
              description: the name or label associated with the parent status.
              examples:
                - تم التنفيذ
          x-apidog-orders:
            - id
            - name
          description: Parent of the order.
          required:
            - id
            - name
          x-apidog-ignore-properties: []
          nullable: true
        original:
          type: object
          properties:
            id:
              type: number
              description: >-
                The initial or original identifier assigned to the status of an
                order.
              examples:
                - 349994915
            name:
              type: string
              description: >-
                The unique identifier assigned to the initial or original status
                of an order.
              examples:
                - جاري التوصيل
          x-apidog-orders:
            - id
            - name
          description: Original order.
          required:
            - id
            - name
          x-apidog-ignore-properties: []
          nullable: true
      x-apidog-orders:
        - id
        - name
        - type
        - slug
        - sort
        - message
        - icon
        - is_active
        - parent
        - original
      required:
        - id
        - name
        - type
        - slug
        - sort
        - message
        - icon
        - is_active
        - parent
        - original
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
