# Shipment Tracking

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /shipments/{shipment_id}/tracking:
    get:
      summary: Shipment Tracking
      deprecated: false
      description: >
        This endpoint allows you to fetch tracking details for a specific
        shipment by passing the `shipment_id` as a path parameter. 



        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `shipping.read`- Shipping Read Only

        </Accordion>
      operationId: get-shipments-shipment_id-tracking
      tags:
        - Merchant API/APIs/Shipments
        - Shipments
      parameters:
        - name: shipment_id
          in: path
          description: >-
            Unique identification number assigned to a shipment. List of
            Shipment IDs from [here](https://docs.salla.dev/api-5394232)
          required: true
          example: 0
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/shipmentTracking_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 362985662
                  order_id: 560695738
                  order_reference_id: 48927
                  type: shipment
                  courier_id: 1927161457
                  courier_name: Shipping App
                  courier_logo: >-
                    https://salla-dev-portal.s3.eu-central-1.amazonaws.com/uploads/Zo3PtoY2KNo3MkpWARHXhK91DUrsEtQJJqpz5PbY.png
                  shipping_number: '846984645'
                  tracking_number: '43534254'
                  pickup_id: '076543'
                  trackable: true
                  tracking_link: https://api.shipengine.com/v1/labels/498498496/track
                  label:
                    format: pdf
                    url: >-
                      https://salla-dev.s3.eu-central-1.amazonaws.com/mKZa/shipping-policy/48102-IPqC9O6ysElWi4ze5148I2ilFABxAKvHggmEG4pB.pdf
                  status: delivering
                  history:
                    - status: delivering
                      note: third note the shipment is on the way
                      create_at:
                        date: '2023-04-06 11:57:51.000000'
                        timezone_type: 3
                        timezone: Asia/Riyadh
                    - status: created
                      note: second note without sending the status in the request
                      create_at:
                        date: '2023-04-06 11:49:51.000000'
                        timezone_type: 3
                        timezone: Asia/Riyadh
                    - status: created
                      note: first note without sending the status in the request
                      create_at:
                        date: '2023-04-06 11:49:35.000000'
                        timezone_type: 3
                        timezone: Asia/Riyadh
                    - status: created
                      note: note about the product
                      create_at:
                        date: '2023-04-06 11:44:23.000000'
                        timezone_type: 3
                        timezone: Asia/Riyadh
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
                    shipping.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: track
      x-salla-php-return-type: ShipmentTracking
      x-apidog-folder: Merchant API/APIs/Shipments
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394237-run
components:
  schemas:
    shipmentTracking_response_body:
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
        data:
          $ref: '#/components/schemas/ShipmentTracking%20'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    'ShipmentTracking ':
      title: ShipmentTracking
      type: object
      properties:
        id:
          type: number
          description: >-
            A unique identifier for the shipment. List of shipments can be found
            [here](https://docs.salla.dev/api-5394232).
        order_id:
          type: number
          description: >-
            A unique identifier for the order associated with the shipment. List
            of orders can be found [here](https://docs.salla.dev/api-5394146)
        order_reference_id:
          type: number
          description: 'A unique identifier associated with an order refrence. '
          nullable: true
        type:
          type: string
          description: Shipment type
        courier_id:
          type: integer
          description: >-
            Shipment courier unique identifiation. Find a complete list of
            Shipment companies [here](api-5394239/?nav=1)
        courier_name:
          type: string
          description: Shipment courier name.
        courier_logo:
          type: string
          description: Shipment courier logo.
        shipping_number:
          type: string
          description: Shipping number.
        tracking_number:
          type: string
          description: Tracking number.
        pickup_id:
          type: number
          description: A unique identifier associated with a pickup.
          nullable: true
        trackable:
          type: boolean
          description: Whether or not the shipment is trackable
        tracking_link:
          type: string
          description: >-
            A hyperlink that provides a direct link to the tracking page for the
            shipment
        label:
          type: object
          properties:
            format:
              type: string
              description: Shipment label format.
            url:
              type: string
              description: Shipment label URL
          x-apidog-orders:
            - format
            - url
          x-apidog-ignore-properties: []
        status:
          type: string
          description: The current status of the shipment
        history:
          type: array
          items:
            type: object
            properties:
              status:
                type: string
                description: Shipment history status
              note:
                type: string
                description: Shipment history note
                nullable: true
              create_at:
                type: object
                properties:
                  date:
                    type: string
                    description: Create at date timestamp
                  timezone_type:
                    type: integer
                    description: Created at timezone type
                  timezone:
                    type: string
                    description: 'Created at timezone '
                x-apidog-orders:
                  - date
                  - timezone_type
                  - timezone
                x-apidog-ignore-properties: []
            x-apidog-orders:
              - status
              - note
              - create_at
            x-apidog-ignore-properties: []
      x-apidog-orders:
        - id
        - order_id
        - order_reference_id
        - type
        - courier_id
        - courier_name
        - courier_logo
        - shipping_number
        - tracking_number
        - pickup_id
        - trackable
        - tracking_link
        - label
        - status
        - history
      required:
        - id
        - order_id
        - order_reference_id
        - type
        - courier_id
        - courier_name
        - courier_logo
        - shipping_number
        - tracking_number
        - pickup_id
        - trackable
        - tracking_link
        - label
        - status
        - history
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
