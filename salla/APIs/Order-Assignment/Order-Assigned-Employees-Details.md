# Order Assigned Employees Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /orders/assignment/{order_id}:
    get:
      summary: Order Assigned Employees Details
      deprecated: false
      description: >-
        This endpoint allows you to fetch the assigned employees' details for a
        specific order by passing the `order_id` as a path parameter. 
      operationId: get-orders-assignment-order
      tags:
        - Merchant API/APIs/Order Assignment
        - Order Assignment
      parameters:
        - name: order_id
          in: path
          description: >-
            Unique identification number assigend to an order. Get a list of
            Order IDs from [here](https://docs.salla.dev/api-5394146).
          required: true
          example: 525144736
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/orderAssignedEmployees_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 525144736
                    name: Ahmed Mohammed
                    avatar: >-
                      http://www.gravatar.com/avatar/d41d8cd98f00b204e9800998ecf8427e?s=80&d=mm&r=g
          headers: {}
          x-apidog-name: Success
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Order Assignment
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-6930855-run
components:
  schemas:
    orderAssignedEmployees_response_body:
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
            $ref: '#/components/schemas/OrderAssignedEmployees'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    OrderAssignedEmployees:
      type: object
      properties:
        id:
          type: integer
          description: Unique identifier assigned to an employee.
        name:
          type: string
          description: Employee name.
        avatar:
          type: string
          description: Employee avatar image
      x-apidog-orders:
        - id
        - name
        - avatar
      required:
        - id
        - name
        - avatar
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
