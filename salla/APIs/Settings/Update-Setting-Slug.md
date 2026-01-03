# Update Setting Slug

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /settings/fields/{slug}:
    put:
      summary: Update Setting Slug
      deprecated: false
      description: >-
        This endpoint allows you to update a specific Setting slug based on a
        specific entity, where the payload will be changed based on the passed
        body parameter values 


        :::info[Read More]

        For more on Store Settings, check the Merchant's Help Desk article
        [here](https://help.salla.sa/article/1887201789)

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `store-settings.read_write`- Settings Read & Write

        </Accordion>
      operationId: put-settings-slug
      tags:
        - Merchant API/APIs/Settings
        - Settings
      parameters:
        - name: slug
          in: path
          description: >-
            Unique identifier or URL-friendly name assigned to the Settings .
            Get a list of Settings Slugs from
            [here](https://docs.salla.dev/api-6965777)
          required: true
          schema:
            type: string
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/settings_request_body'
            example:
              value: true
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
                  message: The record has been updated successfully
                  code: 201
          headers: {}
          x-apidog-name: Created
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
                    store-settings.read_write
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
          headers: {}
          x-apidog-name: error_notFound_404
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Settings
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-6965780-run
components:
  schemas:
    settings_request_body:
      type: object
      properties:
        value:
          anyOf:
            - type: boolean
              description: >-
                Send the `boolean` variable if the entity value `type` is set to
                `boolean`
            - type: string
              description: >-
                Send the `string` variable if the entity value `type` is set to
                `string`
            - type: array
              description: >-
                Send the `array` variable if the entity value `type` is set to
                `dropdown`
              items:
                type: string
          description: >-
            Required if entity type value is either: boolean, string, or
            dropdown
      x-apidog-orders:
        - value
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
