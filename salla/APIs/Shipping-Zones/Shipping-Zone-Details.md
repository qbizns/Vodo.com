# Shipping Zone Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /shipping/zones/{zone_id}:
    get:
      summary: Shipping Zone Details
      deprecated: false
      description: >-
        This endpoint allows you to fetch a specific __Custom__ Shipping Zone by
        passing the `zone_id` as a path parameter. 


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `shipping.read`- Shipping Read Only

        </Accordion>
      operationId: get-shipping-zones-zone_id
      tags:
        - Merchant API/APIs/Shipping Zones
        - Shipping Zones
      parameters:
        - name: zone_id
          in: path
          description: >-
            Unique identifier assigned to a shipping zone, list of shipping
            companies can be found [here](https://docs.salla.dev/api-5394239).
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
                $ref: '#/components/schemas/shippingZone_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 488260393
                  zone_code: .SA.riyadh
                  company:
                    id: 1473353380
                    slug: ABU HALIFA
                  country:
                    id: 1473353380
                    name: السعودية
                    name_en: Saudi Arabia
                    code: SA
                    mobile_code: '+966'
                  city:
                    id: 1473353380
                    name: الرياض
                    name_en: Riyadh
                  cities_excluded:
                    - id: 257742554
                      name: أبو حليفة
                      name_en: ABU HALIFA
                  fees:
                    amount: '15.00'
                    currency: SAR
                    type: fixed
                  cash_on_delivery:
                    status: true
                    fees: '12.00'
                  duration: 3-5
          headers: {}
          x-apidog-name: OK
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
      x-salla-php-method-name: retrieve
      x-salla-php-return-type: ShippingZone
      x-apidog-folder: Merchant API/APIs/Shipping Zones
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394248-run
components:
  schemas:
    shippingZone_response_body:
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
          $ref: '#/components/schemas/ShippingZone'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    ShippingZone:
      title: ShippingZone
      type: object
      properties:
        id:
          type: number
          description: >-
            A unique identifier associated with a certain shipping zone. List of
            shipping zons can be found
            [here](https://docs.salla.dev/api-5394247)
          examples:
            - 488260393
        zone_code:
          type: string
          description: A short alphanumeric identification code for a zone.
          examples:
            - .SA.riyadh
        company:
          type: object
          properties:
            id:
              type: number
              description: >-
                A unique identifier associated with a company. List of shipping
                companies can be found
                [here](https://docs.salla.dev/api-5394239)
              examples:
                - 1473353380
            slug:
              type: string
              description: Shipping company lable or short title.
              nullable: true
          x-apidog-orders:
            - id
            - slug
          required:
            - id
            - slug
          x-apidog-ignore-properties: []
        country:
          type: object
          properties:
            id:
              type: number
              description: >-
                The unique identifier of a country. List of countries can be
                found [here](https://docs.salla.dev/api-5394228)
              examples:
                - 1473353380
            name:
              type: string
              description: Country name
              examples:
                - السعودية
            name_en:
              type: string
              description: Country name in English
              examples:
                - Saudi Arabia
            code:
              type: string
              description: >-
                A short alphanumeric identification code for countries and
                dependent areas.
              examples:
                - SA
            mobile_code:
              type: string
              description: Country mobile code.
              examples:
                - '+966'
          x-apidog-orders:
            - id
            - name
            - name_en
            - code
            - mobile_code
          required:
            - id
            - name
            - name_en
            - code
            - mobile_code
          x-apidog-ignore-properties: []
        city:
          type: object
          properties:
            id:
              type: number
              description: >-
                The unique identifier of a city. List of cities can be found
                [here](https://docs.salla.dev/api-5394230)
              examples:
                - 1473353380
            name:
              type: string
              description: A label given to a significant urban area.
              examples:
                - الرياض
            name_en:
              type: string
              description: City name in English.
              examples:
                - Riyadh
          x-apidog-orders:
            - id
            - name
            - name_en
          required:
            - id
            - name
            - name_en
          x-apidog-ignore-properties: []
        cities_excluded:
          type: array
          description: The cities excluded from Shipping zone.
          items:
            type: object
            properties:
              id:
                type: number
                description: Unique identufiers for excluded cities.
                examples:
                  - 257742554
              name:
                type: string
                description: Excluded city name.
                examples:
                  - أبو حليفة
              name_en:
                type: string
                description: Excluded vity name in English
                examples:
                  - ABU HALIFA
            x-apidog-orders:
              - id
              - name
              - name_en
            x-apidog-ignore-properties: []
        fees:
          type: object
          properties:
            amount:
              type: string
              description: Amount due for shipping services.
              examples:
                - '15.00'
            currency:
              type: string
              description: Fee currency.
              examples:
                - SAR
            type:
              type: string
              description: Type of fee applies for shipping.
              examples:
                - fixed
            weight_unit:
              type: string
              description: 'The unit used to describe the weight '
              examples:
                - kg
            up_to_weight:
              type: number
              description: Maximum weight allowed for shipping.
              examples:
                - 5
            amount_per_unit:
              type: number
              description: Amount per unit
              examples:
                - 2
            per_unit:
              type: number
              description: Fees applies per unit.
              examples:
                - 3
          x-apidog-orders:
            - amount
            - currency
            - type
            - weight_unit
            - up_to_weight
            - amount_per_unit
            - per_unit
          required:
            - amount
            - currency
            - type
            - weight_unit
            - up_to_weight
            - amount_per_unit
            - per_unit
          x-apidog-ignore-properties: []
        cash_on_delivery:
          type: object
          properties:
            status:
              type: boolean
              default: true
              description: 'Whether or not the Shipment supports Cash On Delivery (COD) '
            fees:
              type: string
              description: Cash On Delivery (COD) Fee
              examples:
                - '12.00'
          x-apidog-orders:
            - status
            - fees
          required:
            - status
            - fees
          x-apidog-ignore-properties: []
        duration:
          type: string
          description: Shipping duration in days
          examples:
            - 3-5
      x-apidog-orders:
        - id
        - zone_code
        - company
        - country
        - city
        - cities_excluded
        - fees
        - cash_on_delivery
        - duration
      required:
        - id
        - zone_code
        - company
        - country
        - city
        - cities_excluded
        - fees
        - cash_on_delivery
        - duration
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
