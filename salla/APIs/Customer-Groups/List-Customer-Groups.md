# List Customer Groups

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /customers/groups:
    get:
      summary: List Customer Groups
      deprecated: false
      description: >-
        This endpoint allows you to list all the customer groups in your store.


        :::info[Information]

        Customer groups segment your customers into smaller, targeted groups
        rather than a default group. This enables tailored service, better
        understanding of their needs, and personalized treatment for each group.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `customers.read`- Customers Read Only

        </Accordion>
      operationId: List-Groups
      tags:
        - Merchant API/APIs/Customer Groups
        - Customer Groups
      parameters:
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
                $ref: '#/components/schemas/customerGroups_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 2075683582
                    name: VIP Customers
                  - id: 2075683583
                    name: Golden Customers
                pagination:
                  count: 2
                  total: 2
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
                    cutomers.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: listGroups
      x-salla-php-return-type: CustomerGroup
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Customer Groups
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394129-run
components:
  schemas:
    customerGroups_response_body:
      type: object
      properties:
        status:
          type: string
          x-stoplight:
            id: kb8wuu2lgux80
        success:
          type: string
          x-stoplight:
            id: 0llz2yv3myz7r
        data:
          type: array
          x-stoplight:
            id: 6h79afi1xqcxn
          items:
            $ref: '#/components/schemas/CustomerGroup'
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
    CustomerGroup:
      description: >-
        Detailed structure of the customer group model object showing its fields
        and data types.
      type: object
      x-examples: {}
      x-tags:
        - Models
      title: CustomerGroup
      properties:
        id:
          type: number
          description: The unique identifier assigned to a specific group of customers.
        name:
          type: string
          description: The name or label for a the Customer Group.
        conditions:
          type: object
          description: >-
            Conditions for group membership, such as `total_sales > 100`,
            determine auto-assignment. For example, customers with sales
            exceeding 100 are added to the group automatically.
          properties:
            type:
              type: string
              description: "The type of the condition.\r\n"
            symbol:
              type: string
              description: >-
                A symbol or function defining relationships between values, used
                in conditional logic.
            value:
              type: number
              description: The condition after the operator.
          x-apidog-orders:
            - type
            - symbol
            - value
          required:
            - type
            - symbol
            - value
          x-apidog-ignore-properties: []
        features:
          type: object
          x-apidog-refs:
            01JJ90T6D94VC68GZZQWCTFJCZ:
              $ref: '#/components/schemas/CustomerGroupFeatures'
              x-apidog-overrides: {}
              required:
                - payment_method
          x-apidog-orders:
            - 01JJ90T6D94VC68GZZQWCTFJCZ
          properties:
            payment_method: &ref_0
              type: array
              description: >-
                The various methods of payment that are offered to a specific
                group of customers. List of payment methods can be found
                [here](https://docs.salla.dev/api-5394164).
              items:
                type: string
            shipping: &ref_1
              type: array
              description: >-
                The various delivery methods that are accessible or offered to a
                specific group of customers.
              items:
                type: string
          required:
            - payment_method
            - shipping
          x-apidog-ignore-properties:
            - payment_method
            - shipping
      x-apidog-orders:
        - id
        - name
        - conditions
        - features
      required:
        - id
        - name
        - conditions
        - features
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    CustomerGroupFeatures:
      title: CustomerGroupFeatures
      type: object
      properties:
        payment_method: *ref_0
        shipping: *ref_1
      x-apidog-orders:
        - payment_method
        - shipping
      deprecated: true
      required:
        - payment_method
        - shipping
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
