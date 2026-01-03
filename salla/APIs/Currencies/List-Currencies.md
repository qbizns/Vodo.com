# List Currencies

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /currencies:
    get:
      summary: List Currencies
      deprecated: false
      description: >-
        This endpoint allows you to fetch a list of all currencies alongside
        their details, such as `name`, `code`, `symbol` and `status`.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `metadata.read`- Metadata Read Only

        </Accordion>
      operationId: get-currencies
      tags:
        - Merchant API/APIs/Currencies
        - Currencies
      parameters: []
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/currencies_response_body'
              example:
                status: 200
                success: true
                data:
                  - name: ريال سعودي
                    code: SAR
                    symbol: ر.س
                    status: disabled
                  - name: درهم اماراتي
                    code: AED
                    symbol: ' د.إ'
                    status: enabled
                  - name: جنيه مصري
                    code: EGP
                    symbol: ج.م
                    status: enabled
                  - name: دولار أمريكي
                    code: USD
                    symbol: $
                    status: enabled
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
                    metadata.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: Currencies
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Currencies
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394257-run
components:
  schemas:
    currencies_response_body:
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
          type: array
          x-stoplight:
            id: tiveeiv2mfvp2
          items:
            $ref: '#/components/schemas/Currencies'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Currencies:
      type: object
      properties:
        name:
          type: string
          description: Name of the currency
          examples:
            - ريال سعودي
        code:
          type: string
          description: Code of the currency
          examples:
            - SAR
        symbol:
          type: string
          description: Symbol of the currency
          examples:
            - ر.س
        status:
          type: string
          description: Status of the currency. The value is either `Enabled` or `Disabled`
          enum:
            - enabled
            - disabled
          examples:
            - disabled
          x-apidog-enum:
            - value: enabled
              name: ''
              description: Currency is enabled.
            - value: disabled
              name: ''
              description: Currency is diabled.
      x-apidog-orders:
        - name
        - code
        - symbol
        - status
      required:
        - name
        - code
        - symbol
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
