# Allocation Branch Settings

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /branches/allocation/settings:
    post:
      summary: Allocation Branch Settings
      deprecated: false
      description: >-
        This endpoint provides the configuration for how orders are assigned to
        branches and defines the strategy for deducting stock from branch
        inventories



        :::warning[]

        This endpoint is accessable only for allowed applications.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `branches.read_write`- Branchs Read & Write

        </Accordion>
      tags:
        - Merchant API/APIs/Branches Allocations
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/UpdateAllocationSettings_request_body'
            example:
              scope_id: 1473353380
              strategy: priority
              priorities:
                - branch_id: 1937885067
                  priority: 6
                - branch_id: 989676439
                  priority: 5
                - branch_id: 525010325
                  priority: 3
                - branch_id: 1299113620
                  priority: 4
                - branch_id: 1762665622
                  priority: 2
                - branch_id: 349856400
                  priority: 1
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/AllocationBranchSettings_response_body'
              example:
                status: 200
                success: true
                data:
                  strategy: priority
                  scope_id: 1473353380
          headers: {}
          x-apidog-name: Success
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Branches Allocations
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-22349439-run
components:
  schemas:
    UpdateAllocationSettings_request_body:
      type: object
      properties:
        scope_id:
          type: integer
          description: >-
            The ID of the store scope (marketplace or region) for which
            allocation settings are being applied. Required only if the store
            supports multi-markets
          nullable: true
        strategy:
          type: string
          description: >-
            The allocation strategy to determine how orders are assigned to
            branches. Possible values:

            •`priority`:  Orders are assigned based on branch priority.

            •`closest_to_customer`: Orders go to the nearest branch.

            •`most_stock`: Orders go to the branch with the highest stock.
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
        priorities:
          type: array
          items:
            type: object
            properties:
              branch_id:
                type: integer
                description: >-
                  The unique identifier of the branch whose priority is being
                  defined
              priority:
                type: integer
                description: >-
                  The position of the branch in the allocation order. Must be
                  sequential, starting at 1
            required:
              - branch_id
              - priority
            x-apidog-orders:
              - branch_id
              - priority
            x-apidog-ignore-properties: []
          description: >-
            A list of branches with their priority values. Determines the order
            in which branches are considered for order allocation, Required if
            `strategy` = `priority`
          nullable: true
      required:
        - scope_id
        - strategy
        - priorities
      x-apidog-orders:
        - scope_id
        - strategy
        - priorities
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    AllocationBranchSettings_response_body:
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
          type: object
          properties:
            strategy:
              type: string
              description: Specifies the allocation method
              enum:
                - closest_to_customer
                - most_stock
                - priority
              x-apidog-enum:
                - value: closest_to_customer
                  name: closest_to_customer
                  description: Assigns orders to the branch nearest to the customer.
                - value: most_stock
                  name: most_stock
                  description: >-
                    Assigns orders to the branch with the highest available
                    stock
                - value: priority
                  name: priority
                  description: >-
                    Assigns orders based on branch priority or predefined
                    ranking
            scope_id:
              type: string
              description: >-
                The ID of the store scope (marketplace or region) for which
                allocation settings are being applied. Required only if the
                store supports multi-markets
              nullable: true
          required:
            - strategy
            - scope_id
          x-apidog-orders:
            - strategy
            - scope_id
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
