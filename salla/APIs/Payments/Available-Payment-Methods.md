# Available Payment Methods

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /payment/methods:
    get:
      summary: Available Payment Methods
      deprecated: false
      description: |-
        This endpoint allows you to list all available payment methods.

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">
        `payments.read`- Payments Read Only
        </Accordion>
      operationId: Available-Methods
      tags:
        - Merchant API/APIs/Payments
        - Payments
      parameters:
        - name: page
          in: query
          description: The Pagination page number
          required: false
          schema:
            type: integer
        - name: status
          in: query
          description: Fetches the payment methods enabled in the store
          required: false
          schema:
            type: string
            enum:
              - enabled
            x-apidog-enum:
              - value: enabled
                name: ''
                description: ''
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/availableMethods_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 1473353380
                    slug: credit_card
                    name: creditCard
                  - id: 1939592358
                    slug: paypal
                    name: paypal
                  - id: 1298199463
                    slug: mada
                    name: mada
                  - id: 525144736
                    slug: free
                    name: free
                  - id: 1764372897
                    slug: bank
                    name: BankTransfer
                  - id: 989286562
                    slug: cod
                    name: COD
                  - id: 349994915
                    slug: apple_pay
                    name: ApplePay
                  - id: 1723506348
                    slug: stc_pay
                    name: StcPay
                  - id: 814202285
                    slug: tamara_installment
                    name: TamaraInstallment
                  - id: 40688814
                    slug: tabby_installment
                    name: TabbyInstallment
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
                    payments.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: Payment[]
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Payments
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394164-run
components:
  schemas:
    availableMethods_response_body:
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
            id: e0k759pdqsnio
          items:
            $ref: '#/components/schemas/Payment'
        pagination:
          $ref: '#/components/schemas/Pagination'
      x-apidog-orders:
        - status
        - success
        - data
        - pagination
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Pagination:
      type: object
      title: Pagination
      description: >-
        For a better response behavior as well as maintain the best security
        level, All retrieving API endpoints use a mechanism to retrieve data in
        chunks called pagination.  Pagination working by return only a specific
        number of records in each response, and through passing the page number
        you can navigate the different pages.
      properties:
        count:
          type: number
          description: Number of returned results.
        total:
          type: number
          description: Number of all results.
        perPage:
          type: number
          description: Number of results per page.
          maximum: 65
        currentPage:
          type: number
          description: Number of current page.
        totalPages:
          type: number
          description: Number of total pages.
        links:
          type: object
          properties:
            next:
              type: string
              description: Next Page
            previous:
              type: string
              description: Previous Page
          x-apidog-orders:
            - next
            - previous
          description: Array of linkes to next and previous pages.
          required:
            - next
            - previous
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - count
        - total
        - perPage
        - currentPage
        - totalPages
        - links
      required:
        - count
        - total
        - perPage
        - currentPage
        - totalPages
        - links
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Payment:
      description: >-
        Detailed structure of the payment model object showing its fields and
        data types.
      type: object
      x-examples: {}
      x-tags:
        - Models
      title: Payment
      properties:
        id:
          type: number
          description: A unique code assigned to a payment option.
        slug:
          type: string
          description: A short identifier for a payment option.
        name:
          type: string
          description: A brief label for a payment option.
      x-apidog-orders:
        - id
        - slug
        - name
      required:
        - id
        - slug
        - name
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
