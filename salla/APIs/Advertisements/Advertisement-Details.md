# Advertisement Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /advertisements/{advertisements_id}:
    get:
      summary: Advertisement Details
      deprecated: false
      description: >
        This endpoint allows you to list an existing advertisement post by
        passing the `advertisement_id` as a path parameter. 



        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `marketing.read`- Marketing Read Only

        </Accordion>
      operationId: get-advertisements-advertisements_id
      tags:
        - Merchant API/APIs/Advertisements
        - Advertisements
      parameters:
        - name: advertisements_id
          in: path
          description: ' Unique identification number assigned to the Advertisement. List of Advertisement IDs can be found [here](https://docs.salla.dev/api-5394265).'
          required: true
          example: 0
          schema:
            type: integer
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
                  id: 2142305900
                  title: جميع المنتجات ستتوفر مرة أخرى بعد العيد
                  description: توفر المنتجات
                  type:
                    id: 1032561074
                    name: category
                    link: https://productLink
                  style:
                    icon: sicon-braille-hand
                    font_color: '#ffffff'
                    background_color: '#9d8383'
                  expire_date:
                    date: '2022-01-21 00:00:00.000000'
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
                    marketing.read
          headers: {}
          x-apidog-name: Unauthorized
        '404':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Object%20Not%20Found(404)'
              example:
                status: 404
                success: false
                error:
                  code: error
                  message: المحتوى الذي تحاول الوصول اليه غير متوفر
          headers: {}
          x-apidog-name: Not Found
      security:
        - bearer: []
      x-salla-php-method-name: retrieve
      x-salla-php-return-type: Advertisement
      x-apidog-folder: Merchant API/APIs/Advertisements
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394266-run
components:
  schemas:
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
    Object Not Found(404):
      type: object
      properties:
        status:
          type: integer
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
          type: object
          properties:
            code:
              type: integer
              description: >-
                Not Found Response error code, a numeric or alphanumeric unique
                identifier used to represent the error.
            message:
              type: string
              description: >-
                A message or data structure that is generated or returned when
                the response is not found or explain the error.
          required:
            - code
            - message
          x-apidog-orders:
            - code
            - message
          x-apidog-ignore-properties: []
      required:
        - status
        - success
        - error
      x-apidog-orders:
        - status
        - success
        - error
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
