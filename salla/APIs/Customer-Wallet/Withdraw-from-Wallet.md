# Withdraw from Wallet

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /customers/wallets/withdraw:
    post:
      summary: Withdraw from Wallet
      deprecated: false
      description: >-
        This endpoint allows you to withdraw a specific amount from the customer
        wallet.


        :::warning[]

        This endpoint is accessible only for allowed applications.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `customer-wallets.read_write`- Customer Wallet Read & Write

        </Accordion>


        :::warning[]

        This endpoint will work only if the store has the [Customer
        Wallet](https://apps.salla.sa/en/app/13657422) application installed.

        :::
      operationId: customer-wallet
      tags:
        - Merchant API/APIs/Customer Wallet
        - Customer Wallet
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/customer_wallet_body_request'
            example:
              customer_id: 1994632444
              amount: 22
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Wallet_response_body'
              example:
                status: 200
                success: true
                data:
                  status: confirmed
                  amount_float: -12
                  date:
                    date: '2025-11-27 13:45:54.321280'
                    timezone_type: 3
                    timezone: Asia/Riyadh
                  wallet:
                    id: 1337429619
                    balance: 468
                    created_at: '2025-11-26 10:28:13'
          headers: {}
          x-apidog-name: Created Successfully
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
                    customer-wallets.read_write
          headers: {}
          x-apidog-name: Unauthorized
        '403':
          description: ''
          content:
            application/json:
              schema:
                title: ''
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
                  error:
                    type: object
                    properties:
                      code:
                        type: integer
                        description: >-
                          Not Found Response error code, a numeric or
                          alphanumeric unique identifier used to represent the
                          error.
                      message:
                        type: string
                        description: >-
                          A message or data structure that is generated or
                          returned when the response is not found or explain the
                          error.
                    required:
                      - code
                      - message
                    x-apidog-orders:
                      - code
                      - message
                    x-apidog-ignore-properties: []
                x-apidog-orders:
                  - 01KBYYHYYRW1A5Z3VYP5KYNV0Y
                required:
                  - status
                  - success
                  - error
                x-apidog-refs:
                  01KBYYHYYRW1A5Z3VYP5KYNV0Y:
                    $ref: '#/components/schemas/error_forbidden_403'
                x-apidog-ignore-properties:
                  - status
                  - success
                  - error
              example:
                status: 403
                success: false
                error:
                  code: error
                  message: Application does not have permission to customer wallet
          headers: {}
          x-apidog-name: Forbidden
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
                  code: validation_failed
                  message: Validation is not successfull
                  fields:
                    '{field-name}':
                      - The {field-label} field is required.
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-salla-php-method-name: create
      x-salla-php-return-type: Customer
      x-apidog-folder: Merchant API/APIs/Customer Wallet
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-24850516-run
components:
  schemas:
    customer_wallet_body_request:
      type: object
      properties:
        customer_id:
          type: number
          description: >-
            A unique identifier assigned to a customer. Get a list of customer
            IDs from [here](https://docs.salla.dev/5394121e0)
          examples:
            - 1994632444
        amount:
          type: number
          description: Amount that will be added to the customer wallet.
      x-apidog-refs: {}
      x-apidog-orders:
        - customer_id
        - amount
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Wallet_response_body:
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
          description: >
            Response flag, boolean indicator used to signal a particular
            condition or state in the response of a system or application, often
            representing the presence or absence of certain conditions or
            outcomes.
        data:
          $ref: '#/components/schemas/Wallet'
      x-apidog-orders:
        - status
        - success
        - data
      required:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Wallet:
      type: object
      properties:
        status:
          type: string
          description: Status of the deposite/withdraw operation.
          examples:
            - confirmed
        amount:
          type: number
          description: The amount added/deducted from the customer wallet.
        date:
          type: object
          properties:
            date:
              type: string
              description: >-
                A specific point in time, typically expressed in terms of a
                calendar system, including the day, month, year, hour, minutes,
                seconds and nano seconds.
              examples:
                - '2025-11-14 14:28:03.000000'
            timezone_type:
              type: integer
              description: Timezone type of the date, for Middel East = 3
            timezone:
              type: string
              description: |
                Timezone value "Asia/Riyadh"
          required:
            - date
            - timezone_type
            - timezone
          x-apidog-orders:
            - date
            - timezone_type
            - timezone
          x-apidog-ignore-properties: []
        wallet:
          type: object
          properties:
            id:
              type: integer
              description: A unique identifier for the customer wallet.
            balance:
              type: number
              description: The balance available in the customer wallet.
            created_at:
              type: string
              description: The date when the customer wallet was created.
          required:
            - id
            - balance
            - created_at
          x-apidog-orders:
            - id
            - balance
            - created_at
          x-apidog-ignore-properties: []
      required:
        - status
        - amount
        - date
        - wallet
      x-apidog-orders:
        - status
        - amount
        - date
        - wallet
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
    error_forbidden_403:
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
