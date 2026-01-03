# List Advertisements

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /advertisements:
    get:
      summary: List Advertisements
      deprecated: false
      description: >-
        This endpoint allows you to list all of the advertisement posts of the
        store.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `marketing.read`- Marketing Read Only

        </Accordion>
      operationId: get-advertisements
      tags:
        - Merchant API/APIs/Advertisements
        - Advertisements
      parameters: []
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/advertisements_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 2142305900
                    title: تخفيضات على جميع التيشيرتات
                    description: أعلان لتصنيف التيشيرت
                    type:
                      id: 1032561074
                      name: category
                      link: https://offerLink
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
                  - id: 235400060
                    title: جميع المنتجات ستتوفر مرة أخرى بعد العيد
                    description: توفر المنتجات
                    type:
                      id: 132432
                      name: without_url
                      link: https://offerLink
                    style:
                      icon: sicon-bell
                      font_color: '#ffffff'
                      background_color: '#9d8383'
                    expire_date:
                      date: '2022-12-12 00:00:00.000000'
                      timezone_type: 3
                      timezone: Asia/Riyadh
                    pages:
                      - cart
                      - product
                pagination:
                  count: 5
                  total: 5
                  perPage: 15
                  currentPage: 1
                  totalPages: 1
                  links: []
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
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: Advertisement
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Advertisements
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394265-run
components:
  schemas:
    advertisements_response_body:
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
            id: 0g0jjeqblwzdt
          items:
            $ref: '#/components/schemas/Advertisement'
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
