# Customer Loyalty Points

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /customers/loyalty/points:
    get:
      summary: Customer Loyalty Points
      deprecated: false
      description: >-
        This endpoint allows you to fetch the history of a customer's loylty
        points that is assocaited with the store.



        :::info

        This endopint will work only if the store has [Customer
        Loyalty](https://apps.salla.sa/en/app/1178176509) application installed.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `customers.read` - Customers Read Only

        </Accordion>
      tags:
        - Merchant API/APIs/Loyalty Points
        - Loyality Points
      parameters:
        - name: customer_id
          in: query
          description: >-
            Unique identification number assigned to the Customer. List of
            Customers IDs can be found
            [here](https://docs.salla.dev/api-5394121).
          required: true
          example: '1257881496'
          schema:
            type: string
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/loyality_response_body'
              example:
                status: 200
                success: true
                data:
                  - name: مخصصة
                    points: 900
                    used_points: 0
                    status: مؤكدة
                    created_at:
                      date: '2024-12-17 17:42:14.000000'
                      timezone_type: 3
                      timezone: Asia/Riyadh
                    expiry_date: '2029-02-17 17:42:14'
                    order_id: 98787845454
                    used_at: {}
                    notes: reason text
                    reference_id: 3215487
                  - name: مخصصة
                    points: 900
                    used_points: 0
                    status: مؤكدة
                    created_at:
                      date: '2024-12-17 17:15:47.000000'
                      timezone_type: 3
                      timezone: Asia/Riyadh
                    expiry_date: '2029-02-17 17:15:47'
                    order_id: 123432
                    used_at: '2025-12-31 23:59:59'
                    notes: sample note
                    reference_id: 1234321
                pagination:
                  count: 30
                  total: 39
                  perPage: 30
                  currentPage: 1
                  totalPages: 2
                  links:
                    next: >-
                      http://api.salla.dev/admin/v2/customers/loyalty/points?customer_id=1227534533&page=2
          headers: {}
          x-apidog-name: OK
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
                  code: Invalid-token
                  message: please provide a valid API Key
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
                    customer_id:
                      - حقل customer id مطلوب.
          headers: {}
          x-apidog-name: Validation
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Loyalty Points
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-12250577-run
components:
  schemas:
    loyality_response_body:
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
        data:
          type: array
          items:
            $ref: '#/components/schemas/LoyaltyPoints'
        pagination:
          $ref: '#/components/schemas/Pagination'
      x-apidog-orders:
        - status
        - success
        - data
        - pagination
      required:
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
    LoyaltyPoints:
      type: object
      properties:
        name:
          type: string
          description: Description or a reason of the loyalty points increase or decrese
        points:
          type: integer
          description: Amount of loyalty points for the customer
        used_points:
          type: integer
          description: Amount of used points for the customer
        status:
          type: string
          description: Status of the loyalty points
        created_at:
          $ref: '#/components/schemas/Date'
        expiry_date:
          type: string
          description: Date and time of the expirey date
          title: '2029-02-16 14:59:03'
        order_id:
          type: string
          description: >-
            Order unique identifier. Used in case the loyalty points have been
            earned from an order.
          nullable: true
        reference_id:
          type: string
          description: >-
            Order reference unique identifier. Used in case the loyalty points
            have been earned from an order.
          nullable: true
      x-apidog-orders:
        - name
        - points
        - used_points
        - status
        - created_at
        - expiry_date
        - order_id
        - reference_id
      required:
        - name
        - points
        - used_points
        - status
        - created_at
        - expiry_date
        - order_id
        - reference_id
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
