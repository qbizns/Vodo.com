# Default Route

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /shipping/routes/default:
    patch:
      summary: Default Route
      deprecated: false
      description: >-
        This endpoint allows you to view or update the default shipping route.

        You can enable or disable the route, assign shipping companies, and set
        the selection strategy used at checkout.


        :::warning[]

        This endpoint is accessable only for allowed applications.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `shipping.read_write` - Shipping Read & Write

        </Accordion>
      tags:
        - Merchant API/APIs/Shipping Routes
        - Shipping Routes
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                status:
                  type: boolean
                  description: >-
                    Indicates whether the default route is currently active
                    (true) or inactive (false)
                companies: &ref_0
                  $ref: '#/components/schemas/ShippingRouteCompany'
                  description: List of shipping companies associated with the default route
                strategy: &ref_1
                  $ref: '#/components/schemas/ShippingRouteStrategy'
                  description: >-
                    Configuration for how shipping companies are selected and
                    prioritized in this route
              x-apidog-orders:
                - 01K0Q88B8MKF8WVQT59JRJQ4YE
              x-apidog-refs:
                01K0Q88B8MKF8WVQT59JRJQ4YE:
                  $ref: '#/components/schemas/DefaultRoute_request_body'
              x-apidog-ignore-properties:
                - status
                - companies
                - strategy
            example:
              status: true
              companies:
                - id: 12981994637
                  priority: 1
                - id: 566146469
                  priority: 2
              strategy:
                type: manual
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
                    type: object
                    properties:
                      id:
                        type: integer
                        description: Unique identifier of the route
                      name:
                        type: string
                        description: The name assigned to the shipping route
                      priority:
                        type: integer
                        description: >-
                          Controls route selection order. Lower numbers are
                          given higher priority
                      status:
                        type: boolean
                        description: Indicates whether the route is currently active
                      type:
                        type: string
                        description: Defines the behavior of the route at checkout
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
                            name: Auto Assign
                            description: >-
                              Automatically assigns one shipping company at
                              checkout without customer input
                          - value: branded
                            name: Special Name
                            description: >-
                              Shows multiple companies under one brand name with
                              a unified price
                          - value: default
                            name: Default
                            description: >-
                              Fallback route that displays all available
                              shipping companies by default
                      branded: &ref_2
                        $ref: '#/components/schemas/BrandedRoute'
                      companies: *ref_0
                      condition_match:
                        type: string
                        enum:
                          - all
                          - any
                        x-apidog-enum:
                          - value: all
                            name: Match all
                            description: >-
                              All specified conditions must be met for the route
                              to be applied
                          - value: any
                            name: Match any
                            description: >-
                              At least one of the specified conditions must be
                              met
                        description: >-
                          Determines whether all or any defined conditions must
                          be satisfied to activate this route
                      conditions: &ref_3
                        $ref: '#/components/schemas/ShippingRouteCondition'
                      strategy: *ref_1
                    required:
                      - id
                      - name
                      - status
                      - type
                      - strategy
                    x-apidog-orders:
                      - id
                      - name
                      - priority
                      - status
                      - type
                      - branded
                      - companies
                      - condition_match
                      - conditions
                      - strategy
                    x-apidog-ignore-properties: []
                x-apidog-orders:
                  - 01K0Q89BECQ5RTY5QYN4SFXC99
                required:
                  - status
                  - success
                  - data
                x-apidog-refs:
                  01K0Q89BECQ5RTY5QYN4SFXC99:
                    $ref: '#/components/schemas/ShippingRoute_response_body'
                x-apidog-ignore-properties:
                  - status
                  - success
                  - data
              example:
                status: 200
                success: true
                data:
                  id: 1637823335
                  name: المسار الافتراضي
                  status: true
                  type: default
                  companies:
                    - id: 1298199463
                      priority: 1
                      capacity: 0
                    - id: 566146469
                      priority: 2
                      capacity: 0
                  strategy:
                    type: manual
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
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Shipping Routes
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-19370978-run
components:
  schemas:
    ShippingRouteStrategy:
      type: object
      properties:
        type:
          type: string
          description: Strategy type for selecting companies
          enum:
            - default
            - manual
            - quota
            - ratio
            - lowest_price
          x-apidog-enum:
            - value: default
              name: Salla recommendations
              description: ''
            - value: manual
              name: manual
              description: ''
            - value: quota
              name: Capacity orders count
              description: Available for special plan only
            - value: ratio
              name: Capacity orders ratio
              description: Available for special plan only
            - value: lowest_price
              name: Lowest price
              description: ''
        capacity_level:
          type: string
          description: >-
            the level on which this strategy will be applied, and used with
            `quota` , `ratio`  only
        alternative_companies:
          type: array
          items:
            type: string
          description: >-
            Fallback companies that can be used if primary companies are
            unavailable, and used with `quota`  only
      required:
        - type
      x-apidog-orders:
        - type
        - capacity_level
        - alternative_companies
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    ShippingRouteCompany:
      type: array
      items:
        type: object
        properties:
          id:
            type: integer
            description: >-
              Shipping company ID. Find a complete list of Shipment companies
              [here](api-5578809/?nav=01HNA8MH78MVX1S0DRXDHE3A1K)
          priority:
            type: integer
            description: Manual priority for sorting companies within this route
          capacity:
            type: integer
            description: Manual priority for sorting companies within this route
        required:
          - id
        x-apidog-orders:
          - id
          - priority
          - capacity
        x-apidog-ignore-properties: []
      x-apidog-folder: ''
    DefaultRoute_request_body:
      type: object
      properties:
        status:
          type: boolean
          description: >-
            Indicates whether the default route is currently active (true) or
            inactive (false)
        companies: *ref_0
        strategy: *ref_1
      x-apidog-orders:
        - status
        - companies
        - strategy
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    ShippingRouteCondition:
      type: array
      items:
        type: object
        properties:
          type:
            type: string
            description: The condition type
            enum:
              - cart_total
              - cart_weight
              - cart_quantity
              - categories
              - areas
              - customer_groups
              - schedule
              - branches
              - branch_count
              - specific_products_quantity
              - excluded
              - allowed
            x-apidog-enum:
              - value: cart_total
                name: ''
                description: ' Based on total cart value'
              - value: cart_weight
                name: ''
                description: Based on total cart weight
              - value: cart_quantity
                name: ''
                description: Number of items in the cart
              - value: categories
                name: ''
                description: Filters by product categories
              - value: areas
                name: ''
                description: Filter by allowed or excluded areas (countries and cities)
              - value: customer_groups
                name: ''
                description: Filter by customer groups
              - value: schedule
                name: ''
                description: Restrict delivery to specific days/times
              - value: branches
                name: ''
                description: >-
                  Route is available only through selected branches (available
                  on special plan only)
              - value: branch_count
                name: ''
                description: >-
                  Based on number of branches in the cart (available on special
                  plan only)
              - value: specific_products_quantity
                name: ''
                description: Quantity of specific products required
              - value: excluded
                name: ''
                description: ''
              - value: allowed
                name: ''
                description: ''
          operator:
            type: string
            description: the condition operator
            enum:
              - '=='
              - '!='
              - '>'
              - '>='
              - <
              - <=
              - between
              - in
              - not_in
              - within_range
            x-apidog-enum:
              - value: '=='
                name: ''
                description: Matches values that are exactly equal to the specified value
              - value: '!='
                name: ''
                description: Matches values that are not equal to the specified value.
              - value: '>'
                name: ''
                description: Matches values that are greater than the specified value.
              - value: '>='
                name: ''
                description: >-
                  Matches values that are greater than or equal to the specified
                  value
              - value: <
                name: ''
                description: Matches values that are less than the specified value
              - value: <=
                name: ''
                description: >-
                  Matches values that are less than or equal to the specified
                  value.
              - value: between
                name: ''
                description: Matches values that fall within a specified range (inclusive)
              - value: in
                name: ''
                description: Matches values that exist in a specified list of values
              - value: not_in
                name: ''
                description: >-
                  Matches values that do not exist in a specified list of
                  values.
              - value: within_range
                name: ''
                description: Matches values that fall within a given numeric or date range.
          value:
            anyOf:
              - type: object
                properties:
                  days:
                    type: array
                    items:
                      type: string
                      description: Days strings
                      examples:
                        - '"Friday"'
                      enum:
                        - Sunday
                        - Monday
                        - Tuesday
                        - Wednesday
                        - Thursday
                        - Friday
                        - Saturday
                      x-apidog-enum:
                        - value: Sunday
                          name: ''
                          description: ''
                        - value: Monday
                          name: ''
                          description: ''
                        - value: Tuesday
                          name: ''
                          description: ''
                        - value: Wednesday
                          name: ''
                          description: ''
                        - value: Thursday
                          name: ''
                          description: ''
                        - value: Friday
                          name: ''
                          description: ''
                        - value: Saturday
                          name: ''
                          description: ''
                    description: >-
                      List of days. Must be written with a first captical letter
                      (ex: "Friday"). required if the type is "scheduale"
                  time_from:
                    type: string
                    examples:
                      - '11:00'
                    description: Time in 24-hour format
                  time_to:
                    type: string
                    examples:
                      - '16:30'
                    description: Time in 24-hour format
                x-apidog-orders:
                  - days
                  - time_from
                  - time_to
                description: required if the type is `scheduale`
                required:
                  - days
                  - time_from
                  - time_to
                x-apidog-ignore-properties: []
              - type: object
                properties:
                  min:
                    type: integer
                    description: >-
                      The minimum amount of the the chosen type. For example,
                      the minimum value of the cart_total
                  max:
                    type: integer
                    description: >-
                      The maximum amount of the the chosen type. For example,
                      the maximum value of the cart_total
                x-apidog-orders:
                  - min
                  - max
                required:
                  - min
                  - max
                description: Required if the operator is set to `between` or `within_range`
                x-apidog-ignore-properties: []
              - type: integer
                description: >-
                  Value can be a single integer if the operator is set to `==`
                  or `>=` or `<=` or `>` or `<` or `!=`
              - type: array
                items:
                  type: integer
                description: >-
                  Required if the type is set to `categories`. Example: [213984,
                  7482390]
              - type: array
                items:
                  type: object
                  properties: {}
                  x-apidog-orders: []
                  x-apidog-ignore-properties: []
                description: >-
                  Required array of objects in case the `type` is set to
                  `allowed` or `excluded`. used to include the allowed/excluded
                  country and cities.
            description: >-
              the value based on the type. This key depends on the `type` and
              the `operator`
        required:
          - type
          - operator
          - value
        x-apidog-orders:
          - type
          - operator
          - value
        x-apidog-ignore-properties: []
      x-apidog-folder: ''
    BrandedRoute:
      type: object
      properties:
        name:
          type: string
          description: Display name for the branded delivery service
        description:
          type: string
          description: Optional description of the branded service
        logo_url:
          type: string
          description: URL to the branded logo shown in the checkout
        combinable:
          type: boolean
          description: Indicates if this route can be combined with others
        pricing:
          type: object
          properties:
            type:
              type: string
              description: Pricing type
              enum:
                - rate
                - fixed
              x-apidog-enum:
                - value: rate
                  name: Rate
                  description: Based on weight
                - value: fixed
                  name: Fixed
                  description: Fixed price
            cost:
              type: integer
              description: Base cost for shipping
            amount_per_unit:
              type: integer
              description: Additional cost, it is required idf type = rate
            up_to_weight:
              type: integer
              description: Max weight included in base cost, it is required idf type = rate
            per_unit:
              type: integer
              description: Cost per extra weight unit, it is required idf type = rate
          required:
            - type
            - cost
          x-apidog-orders:
            - type
            - cost
            - amount_per_unit
            - up_to_weight
            - per_unit
          description: Pricing details
          x-apidog-ignore-properties: []
      required:
        - name
        - description
        - logo_url
      x-apidog-orders:
        - name
        - description
        - logo_url
        - combinable
        - pricing
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    ShippingRoute_response_body:
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
          type: object
          properties:
            id:
              type: integer
              description: Unique identifier of the route
            name:
              type: string
              description: The name assigned to the shipping route
            priority:
              type: integer
              description: >-
                Controls route selection order. Lower numbers are given higher
                priority
            status:
              type: boolean
              description: Indicates whether the route is currently active
            type:
              type: string
              description: Defines the behavior of the route at checkout
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
                  name: Auto Assign
                  description: >-
                    Automatically assigns one shipping company at checkout
                    without customer input
                - value: branded
                  name: Special Name
                  description: >-
                    Shows multiple companies under one brand name with a unified
                    price
                - value: default
                  name: Default
                  description: >-
                    Fallback route that displays all available shipping
                    companies by default
            branded: *ref_2
            companies: *ref_0
            condition_match:
              type: string
              enum:
                - all
                - any
              x-apidog-enum:
                - value: all
                  name: Match all
                  description: >-
                    All specified conditions must be met for the route to be
                    applied
                - value: any
                  name: Match any
                  description: At least one of the specified conditions must be met
              description: >-
                Determines whether all or any defined conditions must be
                satisfied to activate this route
            conditions: *ref_3
            strategy: *ref_1
          required:
            - id
            - name
            - status
            - type
            - strategy
          x-apidog-orders:
            - id
            - name
            - priority
            - status
            - type
            - branded
            - companies
            - condition_match
            - conditions
            - strategy
          x-apidog-ignore-properties: []
      required:
        - status
        - success
        - data
      x-apidog-orders:
        - status
        - success
        - data
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
