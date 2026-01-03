# Update Option Template

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /products/options/templates/{id}:
    put:
      summary: Update Option Template
      deprecated: false
      description: >-
        This endpoint allows you to update a specific product option template by
        passing the `id` as a path parameter.



        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `products.read_write` - Products Read and Write 

        </Accordion>
      operationId: update-product-option-templates
      tags:
        - Merchant API/APIs/Product Option Templates
        - Product Option Templates
      parameters:
        - name: id
          in: path
          description: >-
            A unique identifier of the option template. List of option template
            IDs can be found [here](https://docs.salla.dev/9633869e0)
          required: true
          example: 956236464
          schema:
            type: integer
      requestBody:
        content:
          application/json:
            schema:
              type: object
              x-apidog-refs:
                01JCG2M3C8JF4MS5KPJV6G3TQN:
                  $ref: '#/components/schemas/product_option_template_request_body'
                  x-apidog-overrides:
                    type: null
              x-apidog-orders:
                - 01JCG2M3C8JF4MS5KPJV6G3TQN
              properties:
                name:
                  type: string
                  description: Option name.
                display_type:
                  type: string
                  description: >-
                    The manner in which product choices or attributes are
                    presented.
                  enum:
                    - text
                    - image
                    - color
                  x-apidog-enum:
                    - name: ''
                      value: text
                      description: ''
                    - name: ''
                      value: image
                      description: ''
                    - name: ''
                      value: color
                      description: ''
                translations:
                  type: object
                  properties:
                    en:
                      type: object
                      properties:
                        name:
                          type: string
                          description: option name in English
                      x-apidog-orders:
                        - name
                      x-apidog-ignore-properties: []
                    fr:
                      type: object
                      properties:
                        name:
                          type: string
                          description: Option Name in French
                      x-apidog-orders:
                        - name
                      x-apidog-ignore-properties: []
                  x-apidog-orders:
                    - en
                    - fr
                  description: Options presented in different languages.
                  x-apidog-ignore-properties: []
                details:
                  type: object
                  properties:
                    id:
                      type: number
                      description: option ID
                    name:
                      type: string
                      description: option value name
                    is_default:
                      type: boolean
                      description: indicates if the value is default or not
                    display_value:
                      type: string
                      description: Option Display value
                    translation:
                      type: object
                      properties:
                        ar:
                          type: object
                          properties:
                            name:
                              type: string
                          x-apidog-orders:
                            - name
                          x-apidog-ignore-properties: []
                        en:
                          type: object
                          properties:
                            name:
                              type: string
                          x-apidog-orders:
                            - name
                          x-apidog-ignore-properties: []
                        fr:
                          type: object
                          properties:
                            name:
                              type: string
                          x-apidog-orders:
                            - name
                          x-apidog-ignore-properties: []
                      x-apidog-orders:
                        - ar
                        - en
                        - fr
                      description: Option display value translations
                      required:
                        - ar
                        - en
                        - fr
                      x-apidog-ignore-properties: []
                  x-apidog-orders:
                    - id
                    - name
                    - is_default
                    - display_value
                    - translation
                  required:
                    - id
                    - name
                    - is_default
                    - display_value
                    - translation
                  description: >-
                    Option Details. Visible if `with=details` query paramter is
                    passed
                  x-apidog-ignore-properties: []
              required:
                - details
              x-apidog-ignore-properties:
                - name
                - display_type
                - translations
                - details
            example:
              name: اللون
              display_type: color
              translations:
                en:
                  name: color
                fr:
                  name: color
              details:
                - name: احمر غامق
                  is_default: false
                  display_value: '#3245'
                  translations:
                    en:
                      name: dark red
                    fr:
                      name: dagc ged
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/product_option_template_response'
              example:
                status: 200
                success: true
                data:
                  id: 956236464
                  name: color 1
                  feature_type: text
                  translations:
                    - id: 1473353380
                      name: color
                      locale: en
                    - id: 566146469
                      name: color
                      locale: fr
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
                    products.read_write
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Product Option Templates
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-9634567-run
components:
  schemas:
    product_option_template_request_body:
      type: object
      properties:
        name:
          type: string
          description: Option name.
        type:
          type: string
          description: Type of product option.
          enum:
            - checkbox
            - radio
          x-apidog-enum:
            - name: ''
              value: checkbox
              description: ''
            - name: ''
              value: radio
              description: ''
        display_type:
          type: string
          description: The manner in which product choices or attributes are presented.
          enum:
            - text
            - image
            - color
          x-apidog-enum:
            - name: ''
              value: text
              description: ''
            - name: ''
              value: image
              description: ''
            - name: ''
              value: color
              description: ''
        translations:
          type: object
          properties:
            en:
              type: object
              properties:
                name:
                  type: string
                  description: option name in English
              x-apidog-orders:
                - name
              x-apidog-ignore-properties: []
            fr:
              type: object
              properties:
                name:
                  type: string
                  description: Option Name in French
              x-apidog-orders:
                - name
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - en
            - fr
          description: Options presented in different languages.
          x-apidog-ignore-properties: []
        details:
          type: object
          properties:
            id:
              type: number
              description: option ID
            name:
              type: string
              description: option value name
            is_default:
              type: boolean
              description: indicates if the value is default or not
            display_value:
              type: string
              description: Option Display value
            translation:
              type: object
              properties:
                ar:
                  type: object
                  properties:
                    name:
                      type: string
                  x-apidog-orders:
                    - name
                  x-apidog-ignore-properties: []
                en:
                  type: object
                  properties:
                    name:
                      type: string
                  x-apidog-orders:
                    - name
                  x-apidog-ignore-properties: []
                fr:
                  type: object
                  properties:
                    name:
                      type: string
                  x-apidog-orders:
                    - name
                  x-apidog-ignore-properties: []
              x-apidog-orders:
                - ar
                - en
                - fr
              description: Option display value translations
              required:
                - ar
                - en
                - fr
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - id
            - name
            - is_default
            - display_value
            - translation
          required:
            - id
            - name
            - is_default
            - display_value
            - translation
          description: Option Details. Visible if `with=details` query paramter is passed
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - name
        - type
        - display_type
        - translations
        - details
      required:
        - details
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    product_option_template_response:
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
          description: >+
            Response flag, boolean indicator used to signal a particular
            condition or state in the response of a system or application, often
            representing the presence or absence of certain conditions or
            outcomes.







        data:
          $ref: '#/components/schemas/OptionsTemplate'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    OptionsTemplate:
      type: object
      properties:
        id:
          type: string
          description: A unique identifier of the options template.
        name:
          type: string
          description: Option name.
        type:
          type: string
          description: Type of product option.
          enum:
            - checkbox
            - radio
          x-apidog-enum:
            - name: ''
              value: checkbox
              description: Option type of checkbox.
            - name: ''
              value: radio
              description: Option type of radio.
        display_type:
          type: string
          description: The manner in which product choices or attributes are presented.
          enum:
            - text
            - image
            - color
          x-apidog-enum:
            - name: ''
              value: text
              description: Display the option as text.
            - name: ''
              value: image
              description: Display the option as an image.
            - name: ''
              value: color
              description: Display the option as a color.
        translations:
          type: object
          properties:
            en:
              type: object
              properties:
                name:
                  type: string
                  description: Option name in English
              x-apidog-orders:
                - name
              description: Options in English langaugae.
              required:
                - name
              x-apidog-ignore-properties: []
            ar:
              type: object
              properties:
                name:
                  type: string
                  description: Option Name in French
              x-apidog-orders:
                - name
              description: Options in Arabic langauge.
              required:
                - name
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - en
            - ar
          description: >-
            Options presented in different languages based on the store's
            enabled locales.
          required:
            - en
            - ar
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - id
        - name
        - type
        - display_type
        - translations
      required:
        - id
        - name
        - type
        - display_type
        - translations
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
