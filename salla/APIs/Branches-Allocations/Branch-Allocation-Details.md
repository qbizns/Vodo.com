# Branch Allocation Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /branches/allocation/{Id}:
    get:
      summary: Branch Allocation Details
      deprecated: false
      description: >-
        This endpoint allows you to show the allocated branch details by passing
        the `id`of the allocated branch as a path parameter; the response
        includes the assigned shipping companies based on branch coordinates.


        :::warning[]

        This endpoint is accessable only for allowed applications.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `branches.read`- Branchs Read

        </Accordion>
      tags:
        - Merchant API/APIs/Branches Allocations
        - Branch Allocations
      parameters:
        - name: Id
          in: path
          description: >-
            Branch Allocated ID. Get a list of IDs from
            [here](https://docs.salla.dev/api-18495252)
          required: true
          example: 1939592358
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AllocatedBranch_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 1939592358
                  name: Allocation Rout Name
                  company_id: 665151403
                  priority: 1
                  rules:
                    - type: total_quantity
                      operator: '>='
                      value: '3'
                  action:
                    type: branches
                    strategy: most_stock
                    branch_ids: []
                    single_branch_only: false
          headers: {}
          x-apidog-name: Success
        '401':
          description: ''
          content:
            application/json:
              schema:
                title: ''
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
                  error: &ref_0
                    $ref: '#/components/schemas/Unauthorized'
                x-apidog-orders:
                  - 01K0KYW1VNPP1HB32ND6Y18EV8
                x-apidog-refs:
                  01K0KYW1VNPP1HB32ND6Y18EV8:
                    $ref: '#/components/schemas/error_unauthorized_401'
                x-apidog-ignore-properties:
                  - status
                  - success
                  - error
              example:
                status: 401
                success: false
                error:
                  code: Unauthorized
                  message: The access token is invalid
          headers: {}
          x-apidog-name: Unauthorized
        '403':
          description: ''
          content:
            application/json:
              schema:
                title: ''
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
                      Response flag, boolean indicator used to signal a
                      particular condition or state in the response of a system
                      or application, often representing the presence or absence
                      of certain conditions or outcomes.
                  error:
                    type: object
                    properties:
                      code:
                        type: integer
                        description: >-
                          Not Found Response error code, a numeric or
                          alphanumeric unique identifier used to represent the
                          error.
                      message:
                        type: string
                        description: >-
                          A message or data structure that is generated or
                          returned when the response is not found or explain the
                          error.
                    required:
                      - code
                      - message
                    x-apidog-orders:
                      - code
                      - message
                    x-apidog-ignore-properties: []
                x-apidog-orders:
                  - 01K0KSH3SEZ836R438H4V229EM
                x-apidog-refs:
                  01K0KSH3SEZ836R438H4V229EM:
                    $ref: '#/components/schemas/error_forbidden_403'
                required:
                  - status
                  - success
                  - error
                x-apidog-ignore-properties:
                  - status
                  - success
                  - error
              example:
                status: 403
                success: false
                error:
                  code: error
                  message: التطبيق لايدعم هذه الميزة
          headers: {}
          x-apidog-name: Forbidden
        '404':
          description: ''
          content:
            application/json:
              schema:
                title: ''
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
                      Response flag, boolean indicator used to signal a
                      particular condition or state in the response of a system
                      or application, often representing the presence or absence
                      of certain conditions or outcomes.
                  error:
                    type: object
                    properties:
                      code:
                        type: integer
                        description: >-
                          Not Found Response error code, a numeric or
                          alphanumeric unique identifier used to represent the
                          error.
                      message:
                        type: string
                        description: >-
                          A message or data structure that is generated or
                          returned when the response is not found or explain the
                          error.
                    required:
                      - code
                      - message
                    x-apidog-orders:
                      - code
                      - message
                    x-apidog-ignore-properties: []
                x-apidog-orders:
                  - 01K0KTDERMRBB8BRTTYW9BMQWC
                required:
                  - status
                  - success
                  - error
                x-apidog-refs:
                  01K0KTDERMRBB8BRTTYW9BMQWC:
                    $ref: '#/components/schemas/Object%20Not%20Found(404)'
                x-apidog-ignore-properties:
                  - status
                  - success
                  - error
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
      x-apidog-folder: Merchant API/APIs/Branches Allocations
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-18877324-run
components:
  schemas:
    AllocatedBranch_response_body:
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
          $ref: '#/components/schemas/AllocatedBranch'
      required:
        - status
        - success
        - data
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-refs: {}
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    AllocatedBranch:
      type: object
      properties:
        id:
          type: integer
          description: Unique identifier of the allocation route.
        name:
          type: string
          description: Descriptive name of the allocation route for easy identification.
        company_id:
          type: integer
          description: >-
            The ID of the shipping company to which this allocation route
            applies.
          nullable: true
        priority:
          type: integer
          description: The sort
        rules:
          type: array
          items:
            $ref: '#/components/schemas/AllocationRouteRules'
          description: A set of conditions that must be met for this route to be applied.
        action:
          $ref: '#/components/schemas/AllocationRouteAction'
          description: The action to be taken when all the defined rules are satisfied.
      required:
        - id
        - priority
        - rules
        - action
      x-apidog-orders:
        - id
        - name
        - company_id
        - priority
        - rules
        - action
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    AllocationRouteAction:
      type: object
      properties:
        type:
          type: string
          description: |-
            Defines the`type` of allocation action:
            -`branch`: allocate to a specific branch.
            -`branches`: allocate using a strategy across multiple branches.
          enum:
            - branch
            - branches
          x-apidog-enum:
            - value: branch
              name: branch
              description: Allocate to branches
            - value: branches
              name: branches
              description: Allocate using a strategy across multiple branches
        branch_ids:
          type: array
          items:
            type: number
          description: The branches ids if `type` is`branch`
          nullable: true
        strategy:
          type: string
          description: The`strategy` slug such as`most_stock` (if `type` is`branches`).
          enum:
            - closest_to_customer
            - most_stock
            - priority
          x-apidog-enum:
            - value: closest_to_customer
              name: ''
              description: Allocation based on being the closest to the customer
            - value: most_stock
              name: ''
              description: Allocation based on the most available inventory stock items
            - value: priority
              name: ''
              description: Allocation based on priority
          nullable: true
        single_branch_only:
          type: boolean
          description: >-
            Determines whether the deduction will be applied to a single branch
            or across multiple branches.
          nullable: true
      required:
        - type
        - branch_ids
        - strategy
        - single_branch_only
      x-apidog-orders:
        - type
        - branch_ids
        - strategy
        - single_branch_only
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    AllocationRouteRules:
      type: object
      properties:
        type:
          type: string
          description: |-
            The type of rule to evaluate. Examples:
            `total_items`
          enum:
            - destination_country
            - destination_city
            - total_items
            - total_quantity
            - product_tags
            - skus
            - scope_id
          x-apidog-enum:
            - value: destination_country
              name: destination_country
              description: Matches order based on the shipping destination’s country code.
            - value: destination_city
              name: destination_city
              description: >-
                Matches order based on the shipping destination’s city name or
                code.
            - value: total_items
              name: total_items
              description: Matches order based on the total number of items in the order.
            - value: total_quantity
              name: total_quantity
              description: Matches orders based on the total quantity of products ordered
            - value: product_tags
              name: product_tags
              description: Matches products based on the tags assigned to them
            - value: skus
              name: skus
              description: Matches orders containing products that have specific SKU.
            - value: scope_id
              name: scope_id
              description: >-
                Matches orders based on the origin market/store (e.g., a
                particular marketplace or region).
        operator:
          type: string
          description: >-
            Comparison operator used for the rule condition. Supported
            operators:`==`, ` !=`, `>`, `<`, `>=`, `<=`
          enum:
            - '>'
            - '>='
            - <
            - <=
            - '=='
            - '!='
            - in
            - not_in
            - any_in
          x-apidog-enum:
            - value: '>'
              name: ''
              description: Matches values that are greater than the specified value
            - value: '>='
              name: ''
              description: >-
                Matches values that are greater than or equal to the specified
                value
            - value: <
              name: ''
              description: Matches values that are less than the specified value.
            - value: <=
              name: ''
              description: >-
                Matches values that are less than or equal to the specified
                value.
            - value: '=='
              name: ''
              description: Matches values that are exactly equal to the specified value
            - value: '!='
              name: ''
              description: Matches values that are not equal to the specified value.
            - value: in
              name: ''
              description: ' Matches values that exist in a specified list of values'
            - value: not_in
              name: ''
              description: Matches values that do not exist in a specified list of values.
            - value: any_in
              name: ''
              description: >-
                Matches values that exist in any of the specified list of
                values.
        value:
          type: array
          items:
            type: string
          description: >-
            The value against which the order’s attribute is compared (based on
            the`type`).
      x-apidog-orders:
        - type
        - operator
        - value
      required:
        - type
        - operator
        - value
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
        error: *ref_0
      x-apidog-orders:
        - status
        - success
        - error
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    error_forbidden_403:
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
