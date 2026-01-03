# List Active Webhooks

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /webhooks:
    get:
      summary: List Active Webhooks
      deprecated: false
      description: >-
        This endpoint allows you to list all available, registered, and active
        webhooks related to the store.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `webhooks.read`- Webhooks Read Only

        </Accordion>
      operationId: get-webhooks
      tags:
        - Merchant API/APIs/Webhooks
        - Webhooks
      parameters: []
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/webhooks_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 831980095
                    name: Customer Login
                    event: customer.login
                    version: 2
                    rule: first_name = `User`
                    type: manual
                    url: https://webhook.site/ae7ff328-b54d-42d0-bc7c-94673cd2e982
                    headers:
                      Authorization: Your Secret Token
                      Accept-Language: AR
                pagination:
                  count: 0
                  total: 0
                  perPage: 65
                  currentPage: 0
                  totalPages: 0
                  links:
                    - string
          headers: {}
          x-apidog-name: OK
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
                    webhooks.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: Webhook
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Webhooks
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394135-run
components:
  schemas:
    webhooks_response_body:
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
            id: gha86ioae9hl8
          items:
            $ref: '#/components/schemas/Webhook'
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
    Webhook:
      type: object
      x-examples:
        example:
          id: 60587520
          name: Salla Update Customer Event
          event: customer.updated
          url: https://webhook.site/07254470-c763-4ee3-bef1-ab2480262814
          version: 2
          rule: payment_method = mada
          headers:
            Authorization: Your Secret token
            Accept-Language: AR
      title: Webhook
      x-tags:
        - Models
      properties:
        id:
          type: number
          description: A unique identifier assigned to a webhook.
          examples:
            - 60587520
        name:
          type: string
          description: The designated label assigned to a webhook.
          examples:
            - Salla Update Customer Event
        event:
          type: string
          description: >-
            An event that triggers a webhook to send real-time data between
            applications (from the events list).
          examples:
            - customer.updated
        type:
          type: string
          description: Webhook type.
        url:
          type: string
          description: >-
            The address where a webhook sends data when a predefined event
            occurs.
          examples:
            - https://webhook.site/07254470-c763-4ee3-bef1-ab2480262814
        version:
          type: number
          description: >-
            The webhook version, with values of `1` or `2`, reflecting changes
            or updates to its functionality or structure.
          enum:
            - 1
            - 2
          examples:
            - 2
          x-apidog-enum:
            - value: 1
              name: ''
              description: Webhook Version 1 (not used currently)
            - value: 2
              name: ''
              description: Webhook Version 2 (current one)
        rule:
          type: string
          description: >-
            operations, expressions, and conditions to your webhook, like =, !=,
            AND, or OR. For example: payment_method = YOUR_PAYMENT_METHOD ,
            payment_method = mada OR price < 50

            This enables precise response filtering based on your criteria.
          examples:
            - payment_method = mada
        headers:
          type: object
          description: >-
            Details included in webhook requests, such as authentication and
            content metadata, ensure secure and accurate communication between
            web services. These are represented by `headers.key` and
            `headers.value`.
          properties:
            Authorization:
              type: string
              description: >-
                Any header key, with its corresponding value, is sent within the
                POST request to the webhook URL.
              examples:
                - Your Secret token
            Accept-Language:
              type: string
              description: >-
                The value transmitted to the webhook, like this example:
                `cf-ray: 669af54ecf55dfcb-FRA`.
              examples:
                - AR
          x-apidog-orders:
            - Authorization
            - Accept-Language
          required:
            - Authorization
            - Accept-Language
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - id
        - name
        - event
        - type
        - url
        - version
        - rule
        - headers
      required:
        - id
        - name
        - event
        - type
        - url
        - version
        - rule
        - headers
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
