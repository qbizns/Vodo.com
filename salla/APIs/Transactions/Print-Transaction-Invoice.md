# Print Transaction Invoice

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /transactions/{transaction_id}/print:
    post:
      summary: Print Transaction Invoice
      deprecated: false
      description: >-
        This endpoint allows you to print the transaction invoice by passing the
        `transaction_id` as a path parameter.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `transactions.read_write`- Transactions Read & Write

        </Accordion>
      tags:
        - Merchant API/APIs/Transactions
        - Transactions
      parameters:
        - name: transaction_id
          in: path
          description: >-
            Transaction ID. Get a list of Transaction IDs from
            [here](https://docs.salla.dev/8382471e0)
          required: true
          example: 1924095294
          schema:
            type: integer
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
                      Response flag, boolean indicator used to signal a
                      particular condition or state in the response of a system
                      or application, often representing the presence or absence
                      of certain conditions or outcomes.
                  data:
                    type: object
                    properties:
                      url:
                        type: string
                        description: Payment Transaction from the store owner
                    required:
                      - url
                    x-apidog-orders:
                      - url
                required:
                  - status
                  - success
                  - data
                x-apidog-orders:
                  - status
                  - success
                  - data
              example:
                status: 200
                success: true
                data:
                  url: >-
                    https://cdn.salla.sa/QDqV/downloads/zymawy_ma_17-11-2024-12-51_0_print_transaction_invoice.pdf
          headers: {}
          x-apidog-name: Success
        '404':
          description: ''
          content:
            application/json:
              schema:
                type: object
                properties:
                  status:
                    type: integer
                  success:
                    type: boolean
                  error:
                    type: object
                    properties:
                      code:
                        type: integer
                      message:
                        type: string
                    required:
                      - code
                      - message
                    x-apidog-orders:
                      - code
                      - message
                required:
                  - status
                  - success
                  - error
                x-apidog-orders:
                  - status
                  - success
                  - error
              example:
                status: 404
                success: false
                error:
                  code: 404
                  message: cant print the invoice
          headers: {}
          x-apidog-name: Record Not Found
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Transactions
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-11716492-run
components:
  schemas: {}
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
