# Create Advertisement

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /advertisements:
    post:
      summary: Create Advertisement
      deprecated: false
      description: >-
        This endpoint allows you to create an advertisement post of the store on
        its pages.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `marketing.read_write`- Marketing Read & Write

        </Accordion>
      operationId: post-advertisements
      tags:
        - Merchant API/APIs/Advertisements
        - Advertisements
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/advertisment_request_body'
            example:
              title: Adv Title
              description: Adv Description
              type:
                name: product
                id: 1261174103
                link: null
              expire_date: '2022-12-31'
              pages:
                - all
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/advertisement_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 1549230938
                  title: Adv Title
                  description: Adv Description
                  type:
                    id: 1261174103
                    name: product
                    link: https://productImageLink
                  style:
                    icon: sicon-bell
                    font_color: '#ffffff'
                    background_color: '#9d8383'
                  expire_date:
                    date: '2022-12-12 00:00:00.000000'
                    timezone_type: 3
                    timezone: Asia/Riyadh
                  pages:
                    - all
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
                    marketing.read_write
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
                    title:
                      - حقل عنوان الإعلان مطلوب.
                    description:
                      - حقل محتوى الإعلان مطلوب.
                    type:
                      - حقل النوع مطلوب.
                    type.id:
                      - id for product is invalid
                    type.name:
                      - حقل اسم الإعلان مطلوب.
                    expire_date:
                      - حقل تاريخ انتهاء الإعلان مطلوب.
                    pages:
                      - حقل صفحات الإعلان مطلوب.
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-salla-php-method-name: create
      x-salla-php-return-type: Advertisement
      x-apidog-folder: Merchant API/APIs/Advertisements
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394264-run
components:
  schemas:
    advertisment_request_body:
      type: object
      properties:
        title:
          type: string
          description: Advertisement Title.
          examples:
            - Adv Title
        description:
          type: string
          description: >-
            Advertisement Description. 🌐 [Support
            multi-language](https://docs.salla.dev/doc-421122)
          examples:
            - Adv Description
        type:
          type: object
          properties:
            name:
              type: string
              description: Advertisement Type
              enum:
                - category
                - page
                - product
                - offers
                - without_url
                - external_link
              examples:
                - product
              x-apidog-enum:
                - value: category
                  name: ''
                  description: ' Fetch type based on category of the product'
                - value: page
                  name: ''
                  description: Fetch type based on the page
                - value: product
                  name: ''
                  description: Fetch type based on the product
                - value: offers
                  name: ''
                  description: Fetch type based on offers
                - value: without_url
                  name: ''
                  description: Fetch type without url
                - value: external_link
                  name: ''
                  description: Fetch type with external link
            id:
              type: number
              description: >-
                Advertisement ID. The `type.id` variable is `requiredif`
                `type.name` is any of these values: `["category" , "page" ,
                "product"]`
              examples:
                - 1261174103
            link:
              type: string
              description: >-
                Advertisement Link. The `type.link` is `requiredif` `type.name`
                is `external_link`
              nullable: true
          x-apidog-orders:
            - name
            - id
            - link
          x-apidog-ignore-properties: []
        expire_date:
          type: string
          description: Advertisement expiry date.
          examples:
            - '2022-12-31'
        pages:
          type: array
          description: Which pages should the advertisement appear on.
          items:
            type: string
            enum:
              - all
              - cart
              - product
              - payment
              - category
              - home
            examples:
              - all
            x-apidog-enum:
              - value: all
                name: ''
                description: Fetch all pages
              - value: cart
                name: ''
                description: Fetch cart pages
              - value: product
                name: ''
                description: Fetch products pages
              - value: payment
                name: ''
                description: Fetch payment pages
              - value: category
                name: ''
                description: Fetch category pages
              - value: home
                name: ''
                description: Fetch home pages
      required:
        - title
        - description
        - type
        - expire_date
        - pages
      x-apidog-orders:
        - title
        - description
        - type
        - expire_date
        - pages
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    advertisement_response_body:
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
          $ref: '#/components/schemas/Advertisement'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Advertisement:
      type: object
      properties:
        id:
          type: number
          description: Advertisement ID
        title:
          type: string
          description: Advertisement Title
        description:
          type: string
          description: Advertisement Description. 🌐 [Support multi-language](doc-421122)
        type:
          type: object
          properties:
            id:
              type: number
              description: Advertisement Type ID
            name:
              type: string
              description: Advertisement Type Name
            link:
              type: string
              description: Advertisement Type Link
              nullable: true
          x-apidog-orders:
            - id
            - name
            - link
          required:
            - id
            - name
          x-apidog-ignore-properties: []
        style:
          type: object
          properties:
            icon:
              type: string
              description: Advertisement Style Icon
            font_color:
              type: string
              description: Advertisement Style Font Color
            background_color:
              type: string
              description: Advertisement Style Background Color
          x-apidog-orders:
            - icon
            - font_color
            - background_color
          required:
            - icon
            - font_color
            - background_color
          x-apidog-ignore-properties: []
        expire_date:
          type: object
          properties:
            date:
              type: string
              description: Advertisement Expiry Date
            timezone_type:
              type: number
              description: Advertisement Timezone Type
            timezone:
              type: string
              description: Advertisement Timezone
          x-apidog-orders:
            - date
            - timezone_type
            - timezone
          required:
            - date
            - timezone_type
            - timezone
          x-apidog-ignore-properties: []
        pages:
          type: array
          description: Which pages should the advertisement appear on
          items:
            type: string
      x-apidog-orders:
        - id
        - title
        - description
        - type
        - style
        - expire_date
        - pages
      required:
        - id
        - title
        - description
        - type
        - style
        - expire_date
        - pages
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
