# Update Transaction

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /transactions/{transaction_id}:
    put:
      summary: Update Transaction
      deprecated: false
      description: >-
        This endpoint allows you to `refund`, `void`, or `reverse` a transaction
        by passing the `transaction_id` as a path parameter. 

        The endpoint also supports partial refunds.


        :::caution

        The store must have enough balance to process the refund amounts

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `transactions.read_write`- Transactions Read & Write

        </Accordion>
      tags:
        - Merchant API/APIs/Transactions
        - Transactions
      parameters:
        - name: transaction_id
          in: path
          description: >-
            Unique identification number assigned to the Transaction. List of
            Transaction IDs can be found
            [here](https://docs.salla.dev/api-8382471)
          required: true
          example: 293485739
          schema:
            type: integer
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                action:
                  type: string
                  enum:
                    - refund
                    - void
                    - reverse
                  examples:
                    - refund
                  description: >-
                    Action to be performed on the transaction; actions from the
                    enum values only are allowed.
                  x-apidog-mock: refund
                  x-apidog-enum:
                    - name: ''
                      value: refund
                      description: ''
                    - name: ''
                      value: void
                      description: ''
                    - name: ''
                      value: reverse
                      description: ''
                amount:
                  type: number
                  description: Transaction Amount value.
                  x-apidog-mock: '90'
                currency:
                  type: string
                  description: Transaction Amount Currency value.
                  x-apidog-mock: SAR
              x-apidog-orders:
                - 01J1YDGVTPGARY7XQF9A3HS2XD
              x-apidog-refs:
                01J1YDGVTPGARY7XQF9A3HS2XD:
                  $ref: '#/components/schemas/updateTransaction_request_body'
              x-apidog-ignore-properties:
                - action
                - amount
                - currency
      responses:
        '201':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/progress_ActionSuccess'
              example:
                status: 201
                success: true
                data:
                  message: Invoice Partially Refunded successfully
                  code: 201
          headers: {}
          x-apidog-name: Progress In-Action
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
                    transactions.read_write
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
                  code: 404
                  message: Invoice not found
          headers: {}
          x-apidog-name: Not Found
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
              examples:
                '3':
                  summary: No Funds Available
                  value:
                    status: 422
                    success: false
                    error:
                      code: 422
                      message: >-
                        لايمكن إرجاع العملية بمبلغ 900ر.س لعدم وجود رصيد كافي في
                        رصيد المدفوعات 
                '5':
                  summary: Validation Error
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        action:
                          - >-
                            The selected action is invalid. It must be one of:
                            refund, void, reverse.
                        currency:
                          - The selected currency is invalid.
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Transactions
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-8385232-run
components:
  schemas:
    updateTransaction_request_body:
      type: object
      properties:
        action:
          type: string
          enum:
            - refund
            - void
            - reverse
          examples:
            - refund
          description: >-
            Action to be performed on the transaction; actions from the enum
            values only are allowed.
          x-apidog-mock: refund
          x-apidog-enum:
            - name: ''
              value: refund
              description: ''
            - name: ''
              value: void
              description: ''
            - name: ''
              value: reverse
              description: ''
        amount:
          type: number
          description: Transaction Amount value.
          x-apidog-mock: '90'
        currency:
          type: string
          description: Transaction Amount Currency value.
          x-apidog-mock: SAR
      x-apidog-orders:
        - action
        - amount
        - currency
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    progress_ActionSuccess:
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
          type: object
          properties:
            message:
              type: string
              description: >-
                A text or data communication generated by a system or
                application in response to a request.
            code:
              type: number
              description: >-
                A numerical or alphanumeric identifier used in various systems
                and protocols to indicate the status or outcome of a specific
                request.
          x-apidog-orders:
            - message
            - code
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - status
        - success
        - data
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
