# Add Customers To Group Customer

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /customers/groups/add_customers:
    post:
      summary: Add Customers To Group Customer
      deprecated: false
      description: |-
        This endpoint allows you to add customers to a specific customer groups.

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">
        `customers.read_write`- Customers Read & Write
        </Accordion>
      operationId: post-customers-groups-add_customers
      tags:
        - Merchant API/APIs/Customer Groups
        - Customer Groups
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/addCustomerGroup_request_body'
            example:
              group_id: 667738032
              customers:
                - 447121768
                - 1761729493
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/addCustomerToGroupCustomers_response_body'
              example:
                status: 200
                success: true
                data:
                  - The customers has been added to group successfully
          headers: {}
          x-apidog-name: Progress In-Action
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
                    customers.read_write
          headers: {}
          x-apidog-name: Unauthorized
        '422':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/error_validation_422'
              examples:
                '3':
                  summary: Example 1
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        group_id:
                          - حقل group id مطلوب.
                        customers:
                          - حقل customers مطلوب.
                '4':
                  summary: Example 2
                  value:
                    status: 422
                    success: false
                    error:
                      code: error
                      message: alert.invalid_fields
                      fields:
                        group_id:
                          - يجب أن يكون حقل group id عددًا صحيحًا
                        customers.0:
                          - حقل customers.0 غير صالح
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-salla-php-method-name: addToGroup
      x-apidog-folder: Merchant API/APIs/Customer Groups
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394130-run
components:
  schemas:
    addCustomerGroup_request_body:
      type: object
      properties:
        group_id:
          type: integer
          description: >-
            Customer Group ID. List of Customer Group ID can be found
            [here](https://docs.salla.dev/api-5394129).
          examples:
            - 667738032
        customers:
          type: array
          description: >-
            Customer IDs. List of Customer ID can be found
            [here](https://docs.salla.dev/api-5394121)
          items:
            type: integer
            examples:
              - ''
      x-apidog-orders:
        - group_id
        - customers
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    addCustomerToGroupCustomers_response_body:
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
          x-stoplight:
            id: f4ajks6ba59j4
          description: >-
            Response flag, boolean indicator used to signal a particular
            condition or state in the response of a system or application, often
            representing the presence or absence of certain conditions or
            outcomes.
        data:
          type: array
          description: Data Response
          items:
            type: string
            examples:
              - The customers has been added to group successfully
      x-apidog-orders:
        - status
        - success
        - data
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
