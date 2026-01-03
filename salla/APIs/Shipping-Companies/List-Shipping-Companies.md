# List Shipping Companies

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /shipping/companies/:
    get:
      summary: List Shipping Companies
      deprecated: false
      description: >
        This endpoint allows you to list all active shipping companies
        associated with the store. 
         
        :::note

        If the `"activation_type"` is set to:

          - ***manual*** : which means that the shipping company is from the merchant side *(not available to be linked from salla dashboard)*

          - ***api*** : which means it has been linked through salla.
          :::
        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `shipping.read`- Shipping Read Only

        </Accordion>
      operationId: list-shipping-companies
      tags:
        - Merchant API/APIs/Shipping Companies
        - Shipping Companies
      parameters: []
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/shippingCompanies_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 1723506348
                    name: سمسا
                    app_id: '1683195908'
                    activation_type: manual
                    slug: smsa
                  - id: 989286562
                    name: ارامكس
                    app_id: '1311345502'
                    activation_type: manual
                    slug: armx
                  - id: 2079537577
                    name: البريد السعودي | سُبل
                    app_id: '88903443'
                    activation_type: manual
                    slug: sbl
                  - id: 814202285
                    name: DHL Express
                    app_id: '827885927'
                    activation_type: api
                    slug: dhl-express
                  - id: 1130931637
                    name: Ajeek
                    app_id: '1499493023'
                    activation_type: api
                    slug: ajeek
                  - id: 665151403
                    name: أي مكان
                    app_id: '944213936'
                    activation_type: manual
                    slug: Imkan
                  - id: 915304371
                    name: UPS
                    app_id: '1218344689'
                    activation_type: api
                    slug: ups
                  - id: 1764372897
                    name: فتشر
                    app_id: '2099547131'
                    activation_type: api
                    slug: fetcher
                  - id: 1378987453
                    name: mlcGO
                    app_id: '1720219575'
                    activation_type: manual
                    slug: mlcgo
                  - id: 349994915
                    name: سلاسة
                    app_id: '456034465'
                    activation_type: manual
                    slug: slsh
                  - id: 1096243131
                    name: Storage Station
                    app_id: '1353087977'
                    activation_type: api
                    slug: storage-station
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
      x-salla-php-method-name: list
      x-salla-php-return-type: ShippingCompany
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Shipping Companies
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394239-run
components:
  schemas:
    shippingCompanies_response_body:
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
          type: array
          x-stoplight:
            id: njmrw42s89jgj
          items:
            $ref: '#/components/schemas/ShippingCompany'
        pagination:
          $ref: '#/components/schemas/Pagination'
      x-apidog-orders:
        - status
        - success
        - data
        - pagination
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Pagination:
      type: object
      title: Pagination
      description: >-
        For a better response behavior as well as maintain the best security
        level, All retrieving API endpoints use a mechanism to retrieve data in
        chunks called pagination.  Pagination working by return only a specific
        number of records in each response, and through passing the page number
        you can navigate the different pages.
      properties:
        count:
          type: number
          description: Number of returned results.
        total:
          type: number
          description: Number of all results.
        perPage:
          type: number
          description: Number of results per page.
          maximum: 65
        currentPage:
          type: number
          description: Number of current page.
        totalPages:
          type: number
          description: Number of total pages.
        links:
          type: object
          properties:
            next:
              type: string
              description: Next Page
            previous:
              type: string
              description: Previous Page
          x-apidog-orders:
            - next
            - previous
          description: Array of linkes to next and previous pages.
          required:
            - next
            - previous
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - count
        - total
        - perPage
        - currentPage
        - totalPages
        - links
      required:
        - count
        - total
        - perPage
        - currentPage
        - totalPages
        - links
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
