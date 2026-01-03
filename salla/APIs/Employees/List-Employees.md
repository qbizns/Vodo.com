# List Employees

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /users:
    get:
      summary: List Employees
      deprecated: false
      description: |
        This endpoint allows you to fetch a list of your store employees.
      operationId: get-users
      tags:
        - Merchant API/APIs/Employees
        - Employees
      parameters: []
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/employees_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 282459793
                    name: User Name1
                    mobile: '777654321'
                    mobile_code: '+967'
                    email: user1@test.sa
                    role: team
                    status: suspended
                  - id: 1148378730
                    name: User Name2
                    mobile: '777654321'
                    mobile_code: '+967'
                    email: user2@test.sa
                    role: team
                    status: suspended
                  - id: 1697216187
                    name: User Name3
                    mobile: '777654321'
                    mobile_code: '+967'
                    email: user3@test.sa
                    role: user
                    status: suspended
                  - id: 1296225140
                    name: User Name4
                    mobile: '777654321'
                    mobile_code: '+967'
                    email: user4@test.sa
                    role: team
                    status: suspended
                  - id: 467410228
                    name: User Name5
                    mobile: '777654321'
                    mobile_code: '+967'
                    email: user5@test.sa
                    role: user
                    status: suspended
                  - id: 355137381
                    name: User Name6
                    mobile: '777654321'
                    mobile_code: '+967'
                    email: user6@test.sa
                    role: team
                    status: suspended
                  - id: 115245908
                    name: User Name7
                    mobile: '777654321'
                    mobile_code: '+967'
                    email: user7@test.sa
                    role: team
                    status: suspended
                  - id: 1072903216
                    name: User Name8
                    mobile: '555555555'
                    mobile_code: '+966'
                    email: user8@test.sa
                    role: user
                    status: suspended
                  - id: 1689171978
                    name: User
                    mobile: '555555555'
                    mobile_code: '+966'
                    email: user@test.sa
                    role: user
                    status: active
                  - id: 2133460396
                    name: User Name9
                    mobile: '555555555'
                    mobile_code: '+966'
                    email: user9@test.sa
                    role: team
                    status: suspended
                  - id: 1279539873
                    name: User Name Test
                    mobile: '777654321'
                    mobile_code: '+967'
                    email: test@user.sa
                    role: user
                    status: active
                  - id: 27854744
                    name: User Name10
                    mobile: '555555555'
                    mobile_code: '+966'
                    email: user10@test.sa
                    role: user
                    status: active
                  - id: 446255397
                    name: User Name11
                    mobile: '555555555'
                    mobile_code: '+966'
                    email: user11@test.sa
                    role: team
                    status: active
                  - id: 1192353076
                    name: User Name12
                    mobile: '777654321'
                    mobile_code: '+967'
                    email: user12@test.sa
                    role: user
                    status: active
                pagination:
                  count: 16
                  total: 16
                  perPage: 30
                  currentPage: 1
                  totalPages: 1
                  links: []
          headers: {}
          x-apidog-name: Success
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: Employees
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Employees
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394259-run
components:
  schemas:
    employees_response_body:
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
            id: 2bb48nte96n99
          items:
            $ref: '#/components/schemas/Employees'
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
    Employees:
      type: object
      properties:
        id:
          type: number
          description: >-
            A unique identifier assigned to an individual employee working at a
            specific store. List of employees can be found
            [here](https://docs.salla.dev/api-5394259).
          examples:
            - 282459793
        name:
          type: string
          description: Given name of store employee.
          examples:
            - Taleb
        mobile:
          type: string
          description: Store employee mobile number.
          examples:
            - '580885751'
        mobile_code:
          type: string
          description: Store employee mobile Code
          examples:
            - '+966'
        email:
          type: string
          description: Email address of the store employee.
          examples:
            - taleb@marketing.agency
        role:
          type: string
          description: Role of the store employee. Value can either be `user` or `team`
          enum:
            - user
            - team
          examples:
            - team
          x-apidog-enum:
            - value: user
              name: ''
              description: User is of type user.
            - value: team
              name: ''
              description: User is of type team.
        status:
          type: string
          description: >-
            Status of the store employee. Value can either be `active` or
            `suspended`
          enum:
            - active
            - suspended
          examples:
            - suspended
          x-apidog-enum:
            - value: active
              name: ''
              description: User is active.
            - value: suspended
              name: ''
              description: User is suspended.
      x-apidog-orders:
        - id
        - name
        - mobile
        - mobile_code
        - email
        - role
        - status
      required:
        - id
        - name
        - mobile
        - mobile_code
        - email
        - role
        - status
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
