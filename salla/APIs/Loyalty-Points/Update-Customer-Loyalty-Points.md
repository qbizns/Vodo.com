# Update Customer Loyalty Points

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /customers/loyalty/points:
    post:
      summary: Update Customer Loyalty Points
      deprecated: false
      description: >-
        This endpoint enables you to add loyalty points to customers, helping to
        enhance engagement and reward customer loyalty.


        :::info

        This endpoint will work only if the store has [Customer
        Loyalty](https://apps.salla.sa/en/app/1178176509) application installed.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `customers.read_write` - Customers Read & write

        </Accordion>
      tags:
        - Merchant API/APIs/Loyalty Points
        - Loyality Points
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                points:
                  type: number
                  description: Loyalty points, required unless rest_point is set to true
                  title: ''
                  minimum: 1
                  maximum: 999999
                  examples:
                    - 100
                reset_points:
                  type: boolean
                  description: Boolean value to reset loyalty points
                type:
                  type: string
                  enum:
                    - plus
                    - minus
                  x-apidog-enum:
                    - name: ''
                      value: plus
                      description: ''
                    - name: ''
                      value: minus
                      description: ''
                  description: >-
                    The type of increasing points or decreasing points "plus" or
                    "minus"
                reason:
                  type: string
                  description: Text for showing the reason for the update
                channel_send:
                  type: array
                  items:
                    type: string
                    enum:
                      - email
                      - sms
                      - mobile
                    x-apidog-enum:
                      - value: email
                        name: ''
                        description: ''
                      - value: sms
                        name: ''
                        description: ''
                      - value: mobile
                        name: ''
                        description: ''
                  description: 'Select which channel to send '
                customers:
                  type: array
                  items:
                    type: number
                    description: >-
                      List of customers to increase or decrease the loyalty
                      points
                  description: Customers list that will receive loyalty points
                select_all:
                  type: boolean
                  description: Selecting all customers
              x-apidog-orders:
                - 01JFAHSWDZYX06SGCA8QMCX6BF
              required:
                - points
                - type
                - reason
                - channel_send
                - customers
              x-apidog-refs:
                01JFAHSWDZYX06SGCA8QMCX6BF:
                  $ref: '#/components/schemas/add_loyalty_points_request'
              x-apidog-ignore-properties:
                - points
                - reset_points
                - type
                - reason
                - channel_send
                - customers
                - select_all
            example:
              points: 900
              reset_point: false
              type: plus
              reason: Valid reason text
              channel_send:
                - email
                - sms
              customers:
                - 748394059
                - 873874834
      responses:
        '201':
          description: ''
          content:
            application/json:
              schema:
                type: object
                properties:
                  status:
                    type: integer
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
                  data:
                    type: object
                    properties:
                      message:
                        type: string
                        description: Message indicator of response status
                    x-apidog-orders:
                      - message
                    required:
                      - message
                    x-apidog-ignore-properties: []
                required:
                  - status
                  - success
                  - data
                x-apidog-orders:
                  - status
                  - success
                  - data
                x-apidog-ignore-properties: []
              example:
                status: 201
                success: true
                data:
                  code: 201
                  message: تم تحديث نقاط الولاء للعملاء بنجاح
          headers: {}
          x-apidog-name: Created
        '400':
          description: ''
          content:
            application/json:
              schema:
                type: object
                properties:
                  status:
                    type: integer
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
                  data:
                    type: object
                    properties:
                      message:
                        type: string
                        description: Message indicator of response status
                    x-apidog-orders:
                      - message
                    required:
                      - message
                    x-apidog-ignore-properties: []
                required:
                  - status
                  - success
                  - data
                x-apidog-orders:
                  - status
                  - success
                  - data
                x-apidog-ignore-properties: []
              example:
                status: 400
                success: false
                error:
                  message: حدث خطأ أثناء تحديث نقاط الولاء للعملاء
          headers: {}
          x-apidog-name: Bad Request
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
                    customers.read_write
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
                    customers:
                      - حقل customers غير صالح
          headers: {}
          x-apidog-name: Validation
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Loyalty Points
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-12250579-run
components:
  schemas:
    add_loyalty_points_request:
      type: object
      properties:
        points:
          type: number
          description: Loyalty points, required unless rest_point is set to true
          title: ''
          minimum: 1
          maximum: 999999
          examples:
            - 100
        reset_points:
          type: boolean
          description: Boolean value to reset loyalty points
        type:
          type: string
          enum:
            - plus
            - minus
          x-apidog-enum:
            - name: ''
              value: plus
              description: ''
            - name: ''
              value: minus
              description: ''
          description: The type of increasing points or decreasing points "plus" or "minus"
        reason:
          type: string
          description: Text for showing the reason for the update
        channel_send:
          type: array
          items:
            type: string
            enum:
              - email
              - sms
              - mobile
            x-apidog-enum:
              - value: email
                name: ''
                description: ''
              - value: sms
                name: ''
                description: ''
              - value: mobile
                name: ''
                description: ''
          description: 'Select which channel to send '
        customers:
          type: array
          items:
            type: number
            description: List of customers to increase or decrease the loyalty points
          description: Customers list that will receive loyalty points
        select_all:
          type: boolean
          description: Selecting all customers
      x-apidog-orders:
        - points
        - reset_points
        - type
        - reason
        - channel_send
        - customers
        - select_all
      required:
        - points
        - type
        - reason
        - channel_send
        - customers
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
