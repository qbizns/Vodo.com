# List Events

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /webhooks/events:
    get:
      summary: List Events
      deprecated: false
      description: >-
        This endpoint allows you to list all the available events that can be
        used in registering webhooks from this endpoint.


        :::info[Information]

        Read more about Webhook Events
        [here](https://docs.salla.dev/doc-421119).

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `webhooks.read`- Webhooks Read Only

        </Accordion>
      operationId: List-Events
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
                $ref: '#/components/schemas/webhookEvents_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 1473353380
                    label: تم إنشاء طلب
                    event: order.created
                  - id: 566146469
                    label: تم تحديث بيانات طلب
                    event: order.updated
                  - id: 1939592358
                    label: تم إنشاء منتج
                    event: product.created
                  - id: 1298199463
                    label: تم تحديث بيانات منتج
                    event: product.updated
                  - id: 525144736
                    label: تم حذف منتج
                    event: product.deleted
                  - id: 1764372897
                    label: تمت إضافة عميل
                    event: customer.created
                  - id: 989286562
                    label: تم تحديث بيانات عميل
                    event: customer.updated
                  - id: 349994915
                    label: تمت إضافة تصنيف
                    event: category.created
                  - id: 1723506348
                    label: تم تحديث بيانات تصنيف
                    event: category.updated
          headers: {}
          x-apidog-name: >+
            A successful call returns a payload that contains a current list of
            the available webhooks events.

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
      x-salla-php-method-name: listEvents
      x-salla-php-return-type: Events
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Webhooks
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394136-run
components:
  schemas:
    webhookEvents_response_body:
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
          items:
            $ref: '#/components/schemas/Events'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Events:
      type: object
      properties:
        id:
          type: number
          description: >-
            A unique identifier associated with the data or information received
            as a response to a specific event triggered by a webhook or API
            request.
        label:
          type: string
          description: >-
            A specific identifier or name used to reference and extract data
            from the response received when a webhook event is triggered.
        event:
          type: string
          description: Event text to be used to register new webhook.
      x-apidog-orders:
        - id
        - label
        - event
      required:
        - id
        - label
        - event
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
