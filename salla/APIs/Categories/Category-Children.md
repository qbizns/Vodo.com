# Category Children

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /categories/{category}/children:
    get:
      summary: Category Children
      deprecated: false
      description: >-
        This endpoint allows you to return specific category children by passing
        the `category` as a path parameter. 



        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `categories.read`- Categories Read Only

        </Accordion>
      operationId: Category-Children
      tags:
        - Merchant API/APIs/Categories
        - Categories
      parameters:
        - name: category
          in: path
          description: >-
            Unique identifiers assigned to a Category. List of Category IDs can
            be found [here](https://docs.salla.dev/api-5394207).
          required: true
          example: 0
          schema:
            type: integer
        - name: page
          in: query
          description: The Pagination page number
          required: false
          schema:
            type: integer
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
                    description: Response Status Code
                  success:
                    type: boolean
                    description: 'Whether or not the response is successful '
                  data:
                    type: array
                    items:
                      type: object
                      properties:
                        id:
                          type: integer
                          description: Category ID
                        name:
                          type: string
                          description: Category Name
                        urls:
                          type: object
                          properties:
                            customer:
                              type: string
                              description: Category Customer URL
                            admin:
                              type: string
                              description: Category Admin URL
                          required:
                            - customer
                            - admin
                          x-apidog-orders:
                            - customer
                            - admin
                          x-apidog-ignore-properties: []
                        sort_order:
                          type: integer
                          description: Category Sort Order
                        items:
                          type: array
                          items:
                            type: string
                      x-apidog-orders:
                        - id
                        - name
                        - urls
                        - sort_order
                        - items
                      x-apidog-refs: {}
                      x-apidog-ignore-properties: []
                  pagination:
                    $ref: '#/components/schemas/Pagination'
                x-apidog-orders:
                  - status
                  - success
                  - data
                  - pagination
                x-apidog-ignore-properties: []
              example:
                status: 200
                success: true
                data:
                  - id: 1255216786
                    name: فواكه صيفية
                    urls:
                      customer: https://shtara.com/profile
                      admin: https://shtara.com/profile
                    sort_order: 1
                    items: []
                pagination:
                  count: 1
                  total: 1
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
                    categories.read
          headers: {}
          x-apidog-name: Unauthorized
        '404':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Object%20Not%20Found(404)'
              example:
                success: false
                status: 404
                error:
                  code: 404
                  message: The content you are trying to access is no longer available
          headers: {}
          x-apidog-name: Not Found
      security:
        - bearer: []
      x-salla-php-method-name: listChildren
      x-salla-php-return-type: Category
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Categories
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394211-run
components:
  schemas:
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
          description: number of returned results
        total:
          type: number
          description: number of all results
        perPage:
          type: number
          description: number of results per page
          maximum: 65
        currentPage:
          type: number
          description: number of current page
        totalPages:
          type: number
          description: 'number of total pages '
        links:
          type: array
          description: array of linkes to next and previous pages
          items:
            type: string
      x-examples: {}
      x-tags:
        - Responses
      x-apidog-orders:
        - count
        - total
        - perPage
        - currentPage
        - totalPages
        - links
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
