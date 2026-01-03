# Create Auto Assignment Rules

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /orders/assignment/rules:
    post:
      summary: Create Auto Assignment Rules
      deprecated: false
      description: |-
        This endpoint allows you to create multiple order auto assignment rules.

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">
        `orders.read_write` - Orders Read & Write
        </Accordion>
      operationId: post-orders-assignment-rules
      tags:
        - Merchant API/APIs/Order Assignment
        - Order Assignment
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/orderAutoAssignment_request_body'
            example:
              user_id: 2897329
              rules:
                - entity: city
                  value: '2097610897'
                - entity: country
                  value: SA
                - entity: status
                  value: '7646469'
                - entity: branch
                  value: '566146469'
      responses:
        '201':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/orderAutoAssignment_response_body'
              example:
                status: 201
                success: true
                data:
                  id: 123123123
                  user_id: 2897329
                  rules:
                    - entity: city
                      value: '2097610897'
                    - entity: country
                      value: SA
                    - entity: status
                      value: '7646469'
                    - entity: branch
                      value: '566146469'
          headers: {}
          x-apidog-name: Created Successfully
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
                    orders.read_write
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Order Assignment
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5677301-run
components:
  schemas:
    orderAutoAssignment_request_body:
      type: object
      properties:
        user_id:
          type: integer
          description: >-
            Employee User ID. List of Empoloyee can be found
            [here](https://docs.salla.dev/api-5394259)
        rules:
          type: array
          items:
            type: object
            properties:
              entity:
                type: string
                description: Order Rule Entity Name
              value:
                type: string
                description: Order Rule Entity Value
            required:
              - entity
              - value
            x-apidog-orders:
              - entity
              - value
            x-apidog-ignore-properties: []
      x-apidog-orders:
        - user_id
        - rules
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    orderAutoAssignment_response_body:
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
          $ref: '#/components/schemas/OrderAutoAssignment'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    OrderAutoAssignment:
      title: OrderAutoAssignment
      type: object
      properties:
        id:
          type: integer
          description: A unique identifier assigned to a specific order.
        user_id:
          type: integer
          description: >-
            A unique identifier assigned to a user of a system, application, or
            website. 
        rules:
          type: array
          items:
            type: object
            properties:
              entity:
                type: string
                description: Name of the intity.
              value:
                type: string
                description: Value of the entity.
            x-apidog-orders:
              - entity
              - value
            x-apidog-ignore-properties: []
      x-apidog-orders:
        - id
        - user_id
        - rules
      required:
        - id
        - user_id
        - rules
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
