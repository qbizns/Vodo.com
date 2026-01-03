#   Routes List

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /shipping/routes:
    get:
      summary: '  Routes List'
      deprecated: false
      description: >-
        This endpoint allows you to fetch all shipping routes configured for the
        store.

        Each route includes its type (e.g., normal, auto, branded, default),
        status, priority, and combination strategy.

        These routes control how shipping options appear to customers at
        checkout.


        :::warning[]

        This endpoint is accessable only for allowed applications.

        :::

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `shipping.read`- Shipping Read

        </Accordion>
      tags:
        - Merchant API/APIs/Shipping Routes
        - Shipping Routes
      parameters:
        - name: name
          in: query
          description: The name assigned to the shipping route
          required: false
          example: Branded Route
          schema:
            type: string
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
                      Response status code, a numeric or alphanumeric identifier
                      used to convey the outcome or status of a request,
                      operation, or transaction in various systems and
                      applications, typically indicating whether the action was
                      successful, encountered an error, or resulted in a
                      specific condition.
                  data:
                    type: array
                    items:
                      type: object
                      properties:
                        id:
                          type: integer
                          description: Unique identifier of the route
                        name:
                          type: string
                          description: The name assigned to the shipping route
                        type:
                          type: string
                          description: The route type
                          enum:
                            - normal
                            - auto
                            - branded
                            - default
                          x-apidog-enum:
                            - value: normal
                              name: Selected companies
                              description: >-
                                Used to explicitly list selected shipping
                                companies on checkout
                            - value: auto
                              name: Auto assign
                              description: >-
                                Automatically assigns one shipping company at
                                checkout without customer input.
                            - value: branded
                              name: Special name
                              description: >-
                                Shows multiple companies under one brand name
                                with a unified price
                            - value: default
                              name: Default
                              description: >-
                                Fallback route that displays all available
                                shipping companies by default
                        status:
                          type: boolean
                          description: Indicates if the route is currently active
                        priority:
                          type: integer
                          description: >-
                            Controls route selection order. Lower numbers are
                            given higher priority
                        strategy:
                          type: string
                          description: >-
                            The internal method used to calculate route
                            applicability or pricing
                        combinable:
                          type: boolean
                          description: >-
                            Whether this route can be combined with other routes
                            during checkout
                      required:
                        - id
                        - name
                        - type
                        - status
                        - priority
                        - strategy
                        - combinable
                      x-apidog-orders:
                        - id
                        - name
                        - type
                        - status
                        - priority
                        - strategy
                        - combinable
                      x-apidog-ignore-properties: []
                  pagination: &ref_0
                    $ref: '#/components/schemas/Pagination'
                x-apidog-orders:
                  - 01K0P4A2MA7Y2VW7T4XYV6TNV4
                required:
                  - status
                  - success
                  - data
                  - pagination
                x-apidog-refs:
                  01K0P4A2MA7Y2VW7T4XYV6TNV4:
                    $ref: '#/components/schemas/ShippingRouteList_response_body'
                x-apidog-ignore-properties:
                  - status
                  - success
                  - data
                  - pagination
              example:
                status: 200
                success: true
                data:
                  - id: 2087831307
                    name: Branded Route
                    type: branded
                    status: true
                    priority: 1
                    strategy: default
                    combinable: true
                  - id: 580171786
                    name: Selected Route
                    type: default
                    status: true
                    priority: 2
                    strategy: ratio
                    combinable: false
                  - id: 298476048
                    name: Auto assign route
                    type: auto
                    status: true
                    priority: 3
                    strategy: quota
                    combinable: false
                pagination:
                  count: 3
                  total: 3
                  perPage: 15
                  currentPage: 1
                  totalPages: 1
                  links: {}
          headers: {}
          x-apidog-name: Success
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
                  message: The access token is invalid
          headers: {}
          x-apidog-name: Unauthorized
        '404':
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
                    $ref: '#/components/schemas/Validation'
                x-apidog-orders:
                  - status
                  - success
                  - error
                x-apidog-ignore-properties: []
              example:
                status: 404
                success: false
                error:
                  code: error
                  message: المحتوى الذي تحاول الوصول اليه غير متوفر
          headers: {}
          x-apidog-name: Record Not Found
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Shipping Routes
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-19357016-run
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
    ShippingRouteList_response_body:
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
            Response status code, a numeric or alphanumeric identifier used to
            convey the outcome or status of a request, operation, or transaction
            in various systems and applications, typically indicating whether
            the action was successful, encountered an error, or resulted in a
            specific condition.
        data:
          type: array
          items:
            type: object
            properties:
              id:
                type: integer
                description: Unique identifier of the route
              name:
                type: string
                description: The name assigned to the shipping route
              type:
                type: string
                description: The route type
                enum:
                  - normal
                  - auto
                  - branded
                  - default
                x-apidog-enum:
                  - value: normal
                    name: Selected companies
                    description: >-
                      Used to explicitly list selected shipping companies on
                      checkout
                  - value: auto
                    name: Auto assign
                    description: >-
                      Automatically assigns one shipping company at checkout
                      without customer input.
                  - value: branded
                    name: Special name
                    description: >-
                      Shows multiple companies under one brand name with a
                      unified price
                  - value: default
                    name: Default
                    description: >-
                      Fallback route that displays all available shipping
                      companies by default
              status:
                type: boolean
                description: Indicates if the route is currently active
              priority:
                type: integer
                description: >-
                  Controls route selection order. Lower numbers are given higher
                  priority
              strategy:
                type: string
                description: >-
                  The internal method used to calculate route applicability or
                  pricing
              combinable:
                type: boolean
                description: >-
                  Whether this route can be combined with other routes during
                  checkout
            required:
              - id
              - name
              - type
              - status
              - priority
              - strategy
              - combinable
            x-apidog-orders:
              - id
              - name
              - type
              - status
              - priority
              - strategy
              - combinable
            x-apidog-ignore-properties: []
        pagination: *ref_0
      required:
        - status
        - success
        - data
        - pagination
      x-apidog-orders:
        - status
        - success
        - data
        - pagination
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
