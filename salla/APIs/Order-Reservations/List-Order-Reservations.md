# List Order Reservations

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /reservations:
    get:
      summary: List Order Reservations
      deprecated: false
      description: |-
        This endpint allows you to retrieve all the current order reservations.

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">
        `orders.read` - Orders Read Only
        </Accordion>
      operationId: get-orders-reservations
      tags:
        - Merchant API/APIs/Order Reservations
        - Order Reservations
      parameters:
        - name: start
          in: query
          description: Reservation start date
          required: true
          example: '2024-01-01'
          schema:
            type: string
            format: date
        - name: end
          in: query
          description: Reservation end date
          required: true
          example: '2024-01-31'
          schema:
            type: string
            format: date
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/reservation_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 1473353380
                    title: تعليم القيادة (2)
                    type: day
                    start: '2024-02-16T00:00:00'
                    end: 2024-02-16T:23:59:59
                    seats_count: 2
                    url: >-
                      https://dashboard.test/orders/order/yRdxQ9m28rEbamllyGd1Xz5KoDJZgkAP
                    created_at: '2024-02-13 13:48:33'
                  - id: 566146469
                    title: تعليم القيادة (1)
                    type: time
                    start: '2024-02-19T10:00:00'
                    end: '2024-02-19T10:30:00'
                    seats_count: 1
                    url: >-
                      https://dashboard.test/orders/order/LR98Gn45mxgPMnQQ930WwkpdzKNbQjJv
                    created_at: '2024-02-15 11:58:36'
          headers: {}
          x-apidog-name: Success
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
                    orders.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Order Reservations
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5579097-run
components:
  schemas:
    reservation_response_body:
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
          type: string
          x-stoplight:
            id: npw61890d9c6x
          description: >-
            Response flag, boolean indicator used to signal a particular
            condition or state in the response of a system or application, often
            representing the presence or absence of certain conditions or
            outcomes.
        data:
          type: array
          x-stoplight:
            id: orurptuq9pvoy
          items:
            $ref: '#/components/schemas/Reservation'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Reservation:
      type: object
      title: Reservation
      x-tags:
        - Models
      x-examples: {}
      properties:
        id:
          type: number
          description: >-
            A unique alphanumeric code assigned to a specific reservation or
            booking. List of order reservations can be found
            [here](https://docs.salla.dev/api-5579097).
        title:
          type: string
          description: A descriptive label or name given to a specific reservation.
        type:
          type: string
          description: The type of reservation.
        start:
          type: string
          description: Reservation start date
        end:
          type: string
          description: Reservation end date
        weight:
          type: integer
          description: Reservation item weight.
        url:
          type: string
          description: Reservation URL
        created_at:
          type: string
          description: Reservation create at timestamp.
      x-apidog-orders:
        - id
        - title
        - type
        - start
        - end
        - weight
        - url
        - created_at
      required:
        - id
        - title
        - type
        - start
        - end
        - weight
        - url
        - created_at
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
