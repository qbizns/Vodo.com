# Add Language

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /languages:
    post:
      summary: Add Language
      deprecated: false
      description: |-
        This endpoint allows you to add one or more languages to the store. 

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">
        `metadata.read_write`- Metadata Read & Write
        </Accordion>
      operationId: post-languages
      tags:
        - Merchant API/APIs/Languages
        - Languages
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                locales:
                  type: array
                  items:
                    type: object
                    properties:
                      iso_code:
                        type: string
                        description: ISO Code of the Language
                        examples:
                          - fr
                      sort_order:
                        type: string
                        examples:
                          - '1'
                        description: The order of which a language will appear
                        nullable: true
                    x-apidog-orders:
                      - iso_code
                      - sort_order
                    x-apidog-ignore-properties: []
              x-apidog-orders:
                - locales
              x-apidog-ignore-properties: []
            example:
              locales:
                - iso_code: fr
                  sort_order: '1'
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Languages_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 12
                    name: suomen kieli
                    status: enabled
                    rtl: false
                    flag: https://assets.salla.sa/images/flags/fi.svg
                    iso_code: fi
                    country_code: fi
                    sort_order: 9
                  - id: 15
                    name: Ελληνικά
                    status: enabled
                    rtl: false
                    flag: https://assets.salla.sa/images/flags/el.svg
                    iso_code: el
                    country_code: gr
                    sort_order: 10
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
                    metadata.read_write
          headers: {}
          x-apidog-name: Unauthorized
        '422':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/error_validation_422'
              examples:
                '3':
                  summary: Example
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        locales.0.iso_code:
                          - حقل locales.0.iso_code مطلوب.
                '4':
                  summary: Example 2
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        locales.0.iso_code:
                          - >-
                            يجب أن لا يتجاوز طول النّص locales.0.iso_code 3
                            حروفٍ/حرفًا
                          - حقل locales.0.iso_code غير صالحٍ
                '5':
                  summary: Example 3
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        locales.0.iso_code:
                          - لغة أضيفت بالفعل
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Languages
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394254-run
components:
  schemas:
    Languages_response_body:
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
            id: tqyb9o84m19gw
          items:
            $ref: '#/components/schemas/Languages'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Languages:
      type: object
      properties:
        id:
          type: number
          description: A unique identifier assigned to a specific Language.
          examples:
            - 1
        name:
          type: string
          description: Language label or name.
          examples:
            - العربية
        status:
          type: string
          description: Language status. Either `enabled` or `disabled`
          enum:
            - enabled
            - disabled
          examples:
            - enabled
          x-apidog-enum:
            - value: enabled
              name: ''
              description: Language is enabled
            - value: disabled
              name: ''
              description: Language is disabled
        rtl:
          type: boolean
          default: true
          description: Right-To-Left Supportability
        flag:
          type: string
          description: Icon/Flag of the Language
          examples:
            - https://i.ibb.co/jyqRQfQ/avatar-male.webp
        iso_code:
          type: string
          description: ISO Code of the Language
          examples:
            - ar
        country_code:
          type: string
          description: Country code of the language
          examples:
            - sa
        sort_order:
          type: string
          description: Display order of the language
          examples:
            - '2'
      x-apidog-orders:
        - id
        - name
        - status
        - rtl
        - flag
        - iso_code
        - country_code
        - sort_order
      required:
        - id
        - name
        - status
        - rtl
        - flag
        - iso_code
        - country_code
        - sort_order
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
