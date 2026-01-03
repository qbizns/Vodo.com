# Create Branches Allocations

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /branches/allocation:
    post:
      summary: Create Branches Allocations
      deprecated: false
      description: >-
        This endpoint allows you to define allocation rules for a branch,
        including assigning couriers and setting coverage areas.


        :::warning[]

        This endpoint is accessable only for allowed applications.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `branches.read_write`- Branchs Read & Write

        </Accordion>
      tags:
        - Merchant API/APIs/Branches Allocations
        - Branch Allocations
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/%20%20CreateAllocatedBranches_request_body'
            example:
              name: Allocation Rout Name
              company_id: '665151403'
              priority: 1
              rules:
                - type: total_quantity
                  operator: '>='
                  value: '3'
              action:
                type: branches
                strategy: most_stock
                single_branch_only: true
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AllocatedBranch_response_body'
              examples:
                '4':
                  summary: Success
                  value:
                    status: 201
                    success: true
                    data:
                      id: 349994915
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
                        single_branch_only: true
                '5':
                  summary: Exception
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        rules.coverage.radius_meters:
                          - >-
                            لا يمكن استخدام radius إلا إذا كانت طريقة التغطية
                            polygon
                        rules.coverage.coordinates:
                          - حقل rules.coverage.coordinates مطلوب.
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
                  error: &ref_2
                    $ref: '#/components/schemas/Unauthorized'
                x-apidog-orders:
                  - 01K0KYKG7Q1F8B5YDK1H1K08KD
                x-apidog-refs:
                  01K0KYKG7Q1F8B5YDK1H1K08KD:
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
                  - 01K0KT4QW2FPV0FZ2AF83HDPSE
                x-apidog-refs:
                  01K0KT4QW2FPV0FZ2AF83HDPSE:
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
        '422':
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
                  error: &ref_3
                    $ref: '#/components/schemas/Validation'
                x-apidog-orders:
                  - 01K0KTJDN70KBCRJR496SWV3AZ
                x-apidog-refs:
                  01K0KTJDN70KBCRJR496SWV3AZ:
                    $ref: '#/components/schemas/error_validation_422'
                x-apidog-ignore-properties:
                  - status
                  - success
                  - error
              example:
                status: 422
                success: false
                error:
                  code: error
                  message: alert.invalid_fields
                  fields:
                    branch_id:
                      - الحقل يحتوي على قيم غير صحيحة
                    rules.coverage.radius_meters:
                      - >-
                        لا يمكن استخدام radius إلا إذا كانت طريقة التغطية
                        polygon
          headers: {}
          x-apidog-name: Validation Error
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Branches Allocations
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-18495510-run
components:
  schemas:
    '  CreateAllocatedBranches_request_body':
      type: object
      properties:
        name:
          type: string
          description: Descriptive name of the allocation route for easy identification.
          nullable: true
        company_id:
          type: integer
          description: >-
            The ID of the shipping company to which this allocation route
            applies.
        priority:
          type: integer
          description: The allocation sort
        rules:
          type: array
          items: &ref_0
            $ref: '#/components/schemas/AllocationRouteRules'
          description: A set of conditions that must be met for this route to be applied.
        action: &ref_1
          $ref: '#/components/schemas/AllocationRouteAction'
          description: The action to be taken when all the defined rules are satisfied.
      required:
        - priority
        - rules
        - action
      x-apidog-orders:
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
          items: *ref_0
          description: A set of conditions that must be met for this route to be applied.
        action: *ref_1
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
        error: *ref_2
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
        error: *ref_3
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
