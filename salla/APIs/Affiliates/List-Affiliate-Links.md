# List Affiliate Links

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /affiliates/{affiliates_id}/links:
    get:
      summary: List Affiliate Links
      deprecated: false
      description: >-
        This endpoint allows you to fetch links for  a specific affiliate by
        passing the `affiliate_id` as a path parameter.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `marketing.read`- Marketing Read Only

        </Accordion>
      operationId: get-affiliates-affiliates.links
      tags:
        - Merchant API/APIs/Affiliates
        - Affiliates
      parameters:
        - name: affiliates_id
          in: path
          description: >-
            Unique identification number assigned to the Affiliate. List of
            Affiliate IDs can be found
            [here](https://docs.salla.dev/api-5394270).
          required: true
          example: ''
          schema:
            type: string
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/affiliate_links_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 611208326
                    status: active
                    marketer:
                      id: 611208326
                      name: Affiliater name
                    link: https://mtjr.at/CODE
                    statistics:
                      sales:
                        amount: 930.25
                        currency: SAR
                      visits: 11
                      profit:
                        amount: 263.16
                        currency: SAR
                      orders_count: 1
                pagination:
                  count: 10
                  total: 10
                  perPage: 65
                  currentPage: 10
                  totalPages: 10
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
      x-salla-php-method-name: retrieveretrieve
      x-salla-php-return-type: Affiliate
      x-apidog-folder: Merchant API/APIs/Affiliates
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-13902666-run
components:
  schemas:
    affiliate_links_response_body:
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
            $ref: '#/components/schemas/AffiliateLink'
        pagination:
          $ref: '#/components/schemas/Pagination'
      x-apidog-orders:
        - status
        - success
        - data
        - pagination
      required:
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
    AffiliateLink:
      type: object
      x-apidog-refs: {}
      properties:
        id:
          type: number
          description: |-
            Affiliate unique identifier.
            Example: `611208326`
          x-apidog-mock: '611208326'
        status:
          type: string
          description: Status of the affiliate link
        marketer:
          type: object
          properties:
            id:
              type: number
              description: Marketer ID
            name:
              type: string
              description: Marketer Name
          x-apidog-orders:
            - id
            - name
          required:
            - id
            - name
          x-apidog-ignore-properties: []
        link:
          type: string
          description: Affiliate auto-generated link
        statistics:
          $ref: '#/components/schemas/AffiliateStatistics'
      x-apidog-orders:
        - id
        - status
        - marketer
        - link
        - statistics
      required:
        - id
        - status
        - marketer
        - link
        - statistics
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    AffiliateStatistics:
      type: object
      x-apidog-refs: {}
      properties:
        sales:
          type: object
          properties:
            amount:
              type: number
              description: Marketer's sales amount
            currency:
              type: string
              description: Sales currency value
          x-apidog-orders:
            - amount
            - currency
          required:
            - amount
            - currency
          x-apidog-ignore-properties: []
        visits:
          type: number
          description: Affiliate's link total visits
        profit:
          type: object
          properties:
            amount:
              type: number
              description: Marketer's profit amount
            currency:
              type: string
              description: Profit currency value
          x-apidog-orders:
            - amount
            - currency
          required:
            - amount
            - currency
          x-apidog-ignore-properties: []
        orders_count:
          type: number
          description: Total orders count made via the affiliate link
      x-apidog-orders:
        - sales
        - visits
        - profit
        - orders_count
      required:
        - sales
        - profit
        - orders_count
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
