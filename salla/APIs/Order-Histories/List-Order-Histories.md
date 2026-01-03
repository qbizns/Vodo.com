# List Order Histories

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /orders/{order_id}/histories:
    get:
      summary: List Order Histories
      deprecated: false
      description: >
        This endpoint allows you to return the order history of previous and
        current order statuses for a specific order by passing the `order_id` as
        a path parameter. 
      operationId: Order-Histories
      tags:
        - Merchant API/APIs/Order Histories
        - Order Histories
      parameters:
        - name: order_id
          in: path
          description: >-
            Unique identification number assigend to an order. Get a list of
            Order IDs from [here](https://docs.salla.dev/api-5394146).
          required: true
          example: 3155923424
          schema:
            type: integer
        - name: page
          in: query
          description: The Pagination page number
          required: false
          example: 5
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/get_orderHistories_response_body'
              examples:
                '1':
                  summary: Example
                  value:
                    status: 200
                    success: true
                    data:
                      - id: 213320125
                        status: تم التنفيذ
                        note: تم ارسال الطلب عبر شركة الشحن ارامكس
                        created_at:
                          date: '2020-04-02 18:59:47.000000'
                          timezone_type: 3
                          timezone: Asia/Riyadh
                      - id: 213320122
                        status: قيد التنفيذ
                        note: الطلب قيد التنفيذ
                        created_at:
                          date: '2020-04-02 16:59:47.000000'
                          timezone_type: 3
                          timezone: Asia/Riyadh
                      - id: 213320121
                        status: تحت المراجعة
                        note: الطلب تحت المراجعة
                        created_at:
                          date: '2020-04-02 16:50:47.000000'
                          timezone_type: 3
                          timezone: Asia/Riyadh
                    pagination:
                      count: 3
                      total: 3
                      perPage: 15
                      currentPage: 1
                      totalPages: 1
                      links: []
                '3':
                  summary: Success | With Created_by
                  value:
                    status: 200
                    success: true
                    data:
                      - id: 774142439
                        status: بإنتظار المراجعة
                        customized:
                          id: 1592566197
                          name: استلمنا طلبك وجاري تجهيزه
                          type: custom
                          slug: under_review
                          original:
                            id: 566146469
                            name: بإنتظار المراجعة
                          parent: {}
                        note: ''
                        created_at:
                          date: '2024-02-07 14:06:18.000000'
                          timezone_type: 3
                          timezone: Asia/Riyadh
                        created_by:
                          id: 1473353380
                          name: أحمد Conn
                        type: activity
                    pagination:
                      count: 10
                      total: 10
                      perPage: 15
                      currentPage: 1
                      totalPages: 1
                      links: []
          headers: {}
          x-apidog-name: 'Success '
        '401':
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
                    description: >-
                      Response flag, boolean indicator used to signal a
                      particular condition or state in the response of a system
                      or application, often representing the presence or absence
                      of certain conditions or outcomes.
                  error:
                    $ref: '#/components/schemas/Unauthorized'
                x-apidog-orders:
                  - status
                  - success
                  - error
                x-apidog-ignore-properties: []
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
          x-apidog-name: Record Not Found
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Order Histories
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394162-run
components:
  schemas:
    get_orderHistories_response_body:
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
            id: vu4p3jl2f17ae
          items:
            $ref: '#/components/schemas/ListOrderHistories'
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
    ListOrderHistories:
      title: ListOrderHistories
      type: object
      properties:
        id:
          type: number
          description: >-
            A unique identifier associated with a specific entry or record in a
            database that contains information about a customer's past order or
            transaction history.
        status:
          type: string
          description: >-
            The current state of a specific order in a customer's transaction
            history.
        note:
          type: string
          description: >-
            A written remark, or message associated with an order entry in a
            customer's transaction history.
        created_at:
          $ref: '#/components/schemas/Date'
        created_by:
          type: object
          properties:
            id:
              type: integer
              description: A nique identification of the user who created the offer.
            name:
              type: string
              description: The name of the user who created the offer.
            avatar:
              type: string
              examples:
                - https://www.gravatar.com/avatar/1247a0caa31131ed44f5387
              description: A URL of the order history creator
          x-apidog-orders:
            - id
            - name
            - avatar
          required:
            - id
            - name
            - avatar
          x-apidog-ignore-properties: []
          nullable: true
        type:
          type: string
          description: Type value
          enum:
            - comment
            - activity
          x-apidog-enum:
            - name: ''
              value: comment
              description: 'Order history of type comment '
            - name: ''
              value: activity
              description: Order history of type activity.
      x-apidog-orders:
        - id
        - status
        - note
        - created_at
        - created_by
        - type
      required:
        - id
        - status
        - note
        - created_at
        - created_by
        - type
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Date:
      type: object
      title: Date
      x-examples:
        Example:
          date: '2020-10-14 14:28:03.000000'
          timezone_type: 3
          timezone: Asia/Riyadh
      x-tags:
        - Models
      properties:
        date:
          type: string
          format: date-time
          description: >-
            A specific point in time, typically expressed in terms of a calendar
            system, including the day, month, year, hour, minutes, seconds and
            nano seconds. For example: "2020-10-14 14:28:03.000000"
        timezone_type:
          type: number
          description: 'Timezone type of the date, for Middel East = 3 '
        timezone:
          type: string
          description: Timezone value "Asia/Riyadh"
      x-apidog-orders:
        - date
        - timezone_type
        - timezone
      required:
        - date
        - timezone_type
        - timezone
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
