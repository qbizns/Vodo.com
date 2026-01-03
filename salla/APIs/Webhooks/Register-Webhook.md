# Register Webhook

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /webhooks/subscribe:
    post:
      summary: Register Webhook
      deprecated: false
      description: >-
        This endpoint allows you to register a new webhook. 


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

        • The added URL **must** accept `POST` requests.

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
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/webhook_request_body'
            example:
              name: Salla Update Customer Event
              event: customer.updated
              url: https://webhook.site/07254470-c763-4ee3-bef1-ab2480262814
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
                $ref: '#/components/schemas/webhook_response_body'
              examples:
                '1':
                  summary: Webhook V2
                  value:
                    status: 200
                    success: true
                    data:
                      id: 60587520
                      name: Salla Update Customer Event
                      event: customer.updated
                      type: manual
                      url: >-
                        https://webhook.site/07254470-c763-4ee3-bef1-ab2480262814
                      version: 2
                      rule: payment_method = mada OR price < 50
                      headers:
                        Authorization: abcd1234
                        Accept-Language: AR
                '3':
                  summary: Webhook V1
                  value:
                    status: 200
                    success: true
                    data:
                      id: 60587520
                      name: Salla Update Customer Event
                      event: customer.updated
                      url: >-
                        https://webhook.site/07254470-c763-4ee3-bef1-ab2480262814
                      headers:
                        Authorization: abcd1234
                        Accept-Language: AR
          headers: {}
          x-apidog-name: New webhook has been registered successfully
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
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394134-run
components:
  schemas:
    webhook_request_body:
      type: object
      properties:
        name:
          type: string
          description: >-
            Webhook name. List of Webhook names can be found
            [here](https://docs.salla.dev/api-5394135).
          examples:
            - Salla Update Customer Event
        event:
          type: string
          description: >-
            Webhook event. List of events can be found [here](doc-421119), you
            can use one from the list.
          examples:
            - customer.updated
        url:
          type: string
          description: Webhook URL.
          examples:
            - https://webhook.site/07254470-c763-4ee3-bef1-ab2480262814
        version:
          type: integer
          description: Version of the webhook; either valued as `1` or `2`.
          enum:
            - 1
            - 2
          examples:
            - 2
          x-apidog-enum:
            - value: 1
              name: ''
              description: Webhook verion 1.
            - value: 2
              name: ''
              description: Webhook version 2.
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
          description: Webhook headers.
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
      required:
        - event
        - url
      x-apidog-orders:
        - name
        - event
        - url
        - version
        - rule
        - headers
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    webhook_response_body:
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
          $ref: '#/components/schemas/Webhook'
      x-apidog-orders:
        - status
        - success
        - data
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
