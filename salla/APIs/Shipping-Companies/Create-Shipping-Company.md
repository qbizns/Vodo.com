# Create Shipping Company

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /shipping/companies/:
    post:
      summary: Create Shipping Company
      deprecated: false
      description: |2-
         This endpoint allows you to create a **custom** shipping company.

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">
        `shipping.read_write`- Shipping Read & Write
        </Accordion>
      operationId: post-shipping-companies
      tags:
        - Merchant API/APIs/Shipping Companies
        - Shipping Companies
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              type: object
              properties:
                name:
                  type: string
                  description: Shipping Company Name
                  examples:
                    - Shipping Company
              required:
                - name
              x-apidog-orders:
                - name
              x-apidog-ignore-properties: []
            example:
              name: Shipping Company
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/shippingCompany_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 346226214
                  name: شركة
                  app_id: 765436
                  activation_type: api
                  slug: ''
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
                    shipping.read_write
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: create
      x-salla-php-return-type: ShippingCompany
      x-apidog-folder: Merchant API/APIs/Shipping Companies
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394238-run
components:
  schemas:
    shippingCompany_response_body:
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
          $ref: '#/components/schemas/ShippingCompany'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    ShippingCompany:
      type: object
      title: ShippingCompany
      description: >-
        Detailed structure of the Shipping company model object showing its
        fields and data types.
      properties:
        id:
          type: number
          description: >-
            A unique identifier associated with a specific shipping company or
            carrier. Shipping companies list can be found
            [here](https://docs.salla.dev/api-5394239)
          examples:
            - 441225901
        name:
          type: string
          description: >-
            The formal name or title of a carrier responsible for the
            transportation and delivery of goods.
          examples:
            - DHL
        app_id:
          type: string
          description: >-
            A unique identifier associated with a shipping or logistics
            application.
          examples:
            - '112233445'
        activation_type:
          type: string
          description: >-
            the method or process by which a shipping company or carrier
            activates its services, such as whether it's manual or API.
          enum:
            - manual
            - api
          x-apidog-enum:
            - value: manual
              name: ''
              description: Manual activation type
            - value: api
              name: ''
              description: Via API activation type
        slug:
          type: string
          description: >-
            A short form identifier for a shipping company's name. If the
            `activation_type` is set to `manual`, a `null` is returned;
            otherwise, you will receive a value.
          examples:
            - dhl
          nullable: true
      x-apidog-orders:
        - id
        - name
        - app_id
        - activation_type
        - slug
      required:
        - id
        - name
        - app_id
        - activation_type
        - slug
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
