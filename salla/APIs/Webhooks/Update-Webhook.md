# Update Webhook

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /webhooks/{id}:
    put:
      summary: Update Webhook
      deprecated: false
      description: >-
        This endpoint allows you to update an existing webhook by passing the
        `id` as path parameter



        :::tip[Note] 

        - The webhook used is to notify/update/delete an external service when
        an event has occurred. 

        - To trigger your webhook to send data, you can choose one event from
        the [List Events](https://docs.salla.dev/doc-421119) endpoint. 

        :::


        :::info[Information]

        Read more on Webhooks [here](https://docs.salla.dev/doc-421119).

        :::


        :::caution[Alert]

        • New subscriptions with the same URL will update events / restore old
        webhooks *(if they exist)*.

        • The added URL **must** accept `PUT` requests.

        • By default, all new webhooks are registered as version `2`. To use
        version `1`, specify it in your request parameters.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `webhooks.read_write`- Webhooks Read & Write

        </Accordion>
      operationId: Create-webhook
      tags:
        - Merchant API/APIs/Webhooks
        - Webhooks
      parameters:
        - name: id
          in: path
          description: >-
            Webhook ID. Get a list of Webhooks IDs from
            [here]https://docs.salla.dev/api-5394135)
          required: true
          example: 773200552
          schema:
            type: integer
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/updateWebhook_request_body'
            example:
              name: Salla Update Customer Evensst
              version: 2
              rule: payment_method = mada OR price < 50
              headers:
                - key: Your Secret token key name
                  value: Your Secret token value
      responses:
        '200':
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
                      id:
                        type: integer
                        description: Webhook ID
                      name:
                        type: string
                        description: 'Webhook name '
                      event:
                        type: string
                        description: Webhook event
                      version:
                        type: integer
                        description: Webhook version
                      rule:
                        type: string
                        description: Webhook rule
                      url:
                        type: string
                        description: Webhook url
                      headers:
                        type: object
                        properties:
                          Your Secret token key name:
                            type: string
                            description: 'The secret token '
                        required:
                          - Your Secret token key name
                        x-apidog-orders:
                          - Your Secret token key name
                        x-apidog-ignore-properties: []
                      type:
                        type: string
                        description: Webhook type
                      security:
                        type: object
                        properties:
                          strategy:
                            type: string
                            description: The security strategy
                          secret:
                            type: 'null'
                            description: The security secrete number
                        required:
                          - strategy
                          - secret
                        x-apidog-orders:
                          - strategy
                          - secret
                        x-apidog-ignore-properties: []
                    required:
                      - id
                      - name
                      - event
                      - version
                      - rule
                      - url
                      - headers
                      - type
                      - security
                    x-apidog-orders:
                      - id
                      - name
                      - event
                      - version
                      - rule
                      - url
                      - headers
                      - type
                      - security
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
                status: 200
                success: true
                data:
                  id: 773200552
                  name: Salla Update Customer Evensst
                  event: test
                  version: 2
                  rule: payment_method = mada OR price < 50
                  url: https://webhook.site/fake_url
                  headers:
                    Your Secret token key name: Your Secret token value
                  type: manual
                  security:
                    strategy: ''
                    secret: null
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
                    webhooks.read_write
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
                    url:
                      - >-
                        لابد أن يكون الرابط صالح وفعّال (لا يحتوي على localhost
                        أو test.)
                    event:
                      - حقل event غير صالحٍ
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-salla-php-method-name: register
      x-salla-php-return-type: Webhook
      x-apidog-folder: Merchant API/APIs/Webhooks
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-10312606-run
components:
  schemas:
    updateWebhook_request_body:
      type: object
      properties:
        name:
          type: string
          description: >-
            Webhook name. List of Webhook names can be found
            [here](https://docs.salla.dev/api-5394135).
          examples:
            - Salla Update Customer Event
        url:
          type: string
          description: Webhook registered URL. `requiredif` no ID is passed.
          examples:
            - https://webhook.site/07254470-c763-4ee3-bef1-ab2480262814
        version:
          type: integer
          description: Version of the webhook; either valued as `1` or `2`
          enum:
            - 1
            - 2
          examples:
            - 2
        rule:
          type: string
          description: >-
            Operations, expressions and conditions to your webhook. For example,
            you may use `=`,`!=`,`AND`,`OR` etc in such a menner:
            `payment_method = YOUR_PAYMENT_METHOD` or in combination `company_id
            = 871291 OR price < 50`. That adds more capbility to filter the
            response based on conditions
          examples:
            - payment_method = mada OR price < 50
        headers:
          type: array
          description: Webhook headers
          items:
            type: object
            properties:
              key:
                type: string
                description: >-
                  Any header key, which its value is sent in the post request to
                  the webhook URL
                examples:
                  - Your Secret token key name
              value:
                type: string
                description: >-
                  The value sent to the webhook; for example: `cf-ray:
                  669af54ecf55dfcb-FRA`
                examples:
                  - Your Secret token value
            x-apidog-orders:
              - key
              - value
            x-apidog-ignore-properties: []
        security_strategy:
          type: string
          description: in:signature,token
          nullable: true
        secret:
          type: string
          description: required_if:security_strategy,signature
      x-apidog-orders:
        - name
        - url
        - version
        - rule
        - headers
        - security_strategy
        - secret
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
