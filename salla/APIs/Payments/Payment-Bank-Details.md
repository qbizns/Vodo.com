# Payment Bank Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /payment/banks/{bank_id}:
    get:
      summary: Payment Bank Details
      deprecated: false
      description: >-
        This endpoint allows you to fetch the details of the bank associated
        with the store to recieve payments by passing the `bank_id` as a path
        parameter. 


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `payments.read`- Payments Read Only

        </Accordion>
      operationId: get-payment-banks-bank_id
      tags:
        - Merchant API/APIs/Payments
        - Payments
      parameters:
        - name: bank_id
          in: path
          description: >-
            Unique identification number assigned to the Bank. List of Bank IDs
            can be found [here](https://docs.salla.dev/api-5394165)
          required: true
          example: ''
          schema:
            type: string
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/bank_response_body'
          headers: {}
          x-apidog-name: Success
        '401':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/error_unauthorized_401'
          headers: {}
          x-apidog-name: 'Unauthorized '
        '404':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Object%20Not%20Found(404)'
          headers: {}
          x-apidog-name: Not Found
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Payments
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394166-run
components:
  schemas:
    bank_response_body:
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
          $ref: '#/components/schemas/Banks'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Banks:
      title: Banks
      x-stoplight:
        id: 8ju8zo0yzfy79
      type: object
      properties:
        id:
          type: integer
          x-stoplight:
            id: rn8skmtc8j9k3
          description: A unique alphanumeric code or identifier assigned to each bank.
        bank_name:
          type: string
          x-stoplight:
            id: eiyg5pet5eomg
          description: A pre-defined name associated with the bank.
        account_name:
          type: string
          x-stoplight:
            id: fpqf7keq1v5tf
          description: A user defined name associated with the account.
        status:
          type: string
          description: Whether or not the bank is active.
          x-stoplight:
            id: 48p5dzjsg4psp
          enum:
            - active
            - inactive
          x-apidog-enum:
            - value: active
              name: ''
              description: Bank is active
            - value: inactive
              name: ''
              description: Bank is inactive
      x-apidog-orders:
        - id
        - bank_name
        - account_name
        - status
      required:
        - id
        - bank_name
        - account_name
        - status
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
    Object Not Found(404):
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
