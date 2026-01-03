# Create Order History

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /orders/{order_id}/histories:
    post:
      summary: Create Order History
      deprecated: false
      description: >-
        This endpoint allows you to append a `note` to the Order History, by
        passing the `order_id` as a path parameter. 


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `orders.read_write` - Orders Read & Write

        </Accordion>
      operationId: post-orders-order_id-histories
      tags:
        - Merchant API/APIs/Order Histories
        - Order Histories
      parameters:
        - name: order_id
          in: path
          description: >-
            Unique identification number assigend to an order. Get a list of
            Order IDs from [here](https://docs.salla.dev/api-5394146).
          required: true
          example: 0
          schema:
            type: integer
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                note:
                  type: string
                  description: Note value
              required:
                - note
              x-apidog-orders:
                - note
              x-apidog-ignore-properties: []
            example: ''
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/post_orderHistories_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 1333127585
                  status: مسترجع
                  customized:
                    id: 2062355698
                    name: مسترجع
                    type: custom
                    slug: restored
                    original:
                      id: 989286562
                      name: مسترجع
                    parent: {}
                  note: ملاحظة
                  created_at:
                    date: '2023-02-21 11:09:57.000000'
                    timezone_type: 3
                    timezone: Asia/Riyadh
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
              example:
                status: 422
                success: false
                error:
                  code: error
                  message: alert.invalid_fields
                  fields:
                    note:
                      - نص التعليق مطلوب
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-salla-php-method-name: createHistory
      x-salla-php-return-type: POSTOrderHistory
      x-apidog-folder: Merchant API/APIs/Order Histories
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394163-run
components:
  schemas:
    post_orderHistories_response_body:
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
          $ref: '#/components/schemas/POSTOrderHistory'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    POSTOrderHistory:
      title: POSTOrderHistory
      type: object
      properties:
        id:
          type: number
          description: >-
            A unique identifier associated with a specific entry or record in a
            database containing information about a customer's past order or
            transaction history.
        status:
          type: string
          description: The current state or condition of an order.
        customized:
          type: object
          properties:
            id:
              type: number
              description: >-
                A unique identifier associated with a specific, user-defined
                order status.
            name:
              type: string
              description: User-defined label or name given to a specific order status.
            type:
              type: string
              description: >-
                A user-defined classification or category associated with a
                custom order status.
            slug:
              type: string
              description: >-
                A unique and user-defined text string associated with a custom
                order status.
            original:
              type: object
              properties:
                id:
                  type: number
                  description: >-
                    A customised unique identifier typically associated with the
                    initial status of an order.
                name:
                  type: string
                  description: >-
                    A customised label given to the status of an order when it
                    is first placed in a system.
              x-apidog-orders:
                - id
                - name
              required:
                - id
                - name
              x-apidog-ignore-properties: []
            parent:
              type: string
              description: >-
                Customised status of a higher-level or overarching status to
                which specific order is associated to.
          x-apidog-orders:
            - id
            - name
            - type
            - slug
            - original
            - parent
          required:
            - id
            - name
            - type
            - slug
            - original
            - parent
          x-apidog-ignore-properties: []
        note:
          type: string
          description: A record or comment associated with a specific order.
        created_at:
          $ref: '#/components/schemas/Date'
          description: POST order history date an time of creation.
      x-apidog-orders:
        - id
        - status
        - customized
        - note
        - created_at
      required:
        - id
        - status
        - customized
        - note
        - created_at
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Date:
      type: object
      title: Date
      x-examples:
        Example:
          date: '2020-10-14 14:28:03.000000'
          timezone_type: 3
          timezone: Asia/Riyadh
      x-tags:
        - Models
      properties:
        date:
          type: string
          format: date-time
          description: >-
            A specific point in time, typically expressed in terms of a calendar
            system, including the day, month, year, hour, minutes, seconds and
            nano seconds. For example: "2020-10-14 14:28:03.000000"
        timezone_type:
          type: number
          description: 'Timezone type of the date, for Middel East = 3 '
        timezone:
          type: string
          description: Timezone value "Asia/Riyadh"
      x-apidog-orders:
        - date
        - timezone_type
        - timezone
      required:
        - date
        - timezone_type
        - timezone
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
