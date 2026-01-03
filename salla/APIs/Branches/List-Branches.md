# List Branches

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /branches:
    get:
      summary: List Branches
      deprecated: false
      description: >+
        This endpoint allows you to list all branches related to your store
        directly from this endpoint.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `branches.read`- Branches Read Only

        </Accordion>

      operationId: List-Branches
      tags:
        - Merchant API/APIs/Branches
        - Branches
      parameters:
        - name: page
          in: query
          description: The Pagination page number
          required: false
          example: 4
          schema:
            type: integer
        - name: is_default
          in: query
          description: Whether or not this branch is the default one for all operations
          required: false
          example: 'true'
          schema:
            type: boolean
        - name: keyword
          in: query
          description: Search using a keyword
          required: false
          example: Main Branch
          schema:
            type: string
        - name: branch_code
          in: query
          description: Search using a branch code
          required: false
          example: A12312
          schema:
            type: string
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/branches_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 1846327032
                    name: مركز الجمال
                    status: active
                    location:
                      lat: '30.0778'
                      lng: '31.2852'
                    street: الرحمة
                    address_description: 123 شارع الرحمة
                    additional_number: '6666'
                    building_number: '6666'
                    local: omm
                    postal_code: '66666'
                    contacts:
                      phone: '+966508265874'
                      whatsapp: '+966508265874'
                      telephone: '012526886'
                    preparation_time: '6'
                    is_open: true
                    closest_time:
                      from: '09:00'
                      to: '23:00'
                    working_hours:
                      - name: السبت
                        times:
                          - from: '09:00'
                            to: '23:55'
                      - name: الأحد
                        times:
                          - from: '09:00'
                            to: '23:55'
                      - name: الإثنين
                        times:
                          - from: '09:00'
                            to: '23:55'
                      - name: الثلاثاء
                        times:
                          - from: '09:00'
                            to: '23:55'
                      - name: الأربعاء
                        times:
                          - from: '09:00'
                            to: '23:55'
                      - name: الخميس
                        times:
                          - from: '09:00'
                            to: '23:55'
                      - name: الجمعة
                        times:
                          - from: '19:00'
                            to: '23:55'
                    is_cod_available: true
                    is_default: true
                    type: branch
                    cod_cost: '5.00'
                    country:
                      id: 1723506348
                      name: مصر
                      name_en: Egypt
                      code: EG
                      mobile_code: '+20'
                      capital: Cairo
                    city:
                      id: 1355786303
                      name: CAIRO
                      name_en: CAIRO
                      country_id: 1723506348
                  - id: 1005180409
                    name: فرع مول البستان
                    status: active
                    location:
                      lat: '30.0778'
                      lng: '31.2852'
                    street: اكتوبر
                    address_description: 65  حي الزعفران
                    additional_number: '8888'
                    building_number: '14'
                    local: العاشر من رمضان
                    postal_code: '564325'
                    contacts:
                      phone: ''
                      whatsapp: ''
                      telephone: ''
                    preparation_time: '6'
                    is_open: true
                    closest_time:
                      from: '09:00'
                      to: '18:00'
                    working_hours:
                      - name: السبت
                        times:
                          - from: '09:00'
                            to: '18:00'
                      - name: الأحد
                        times:
                          - from: '09:00'
                            to: '18:00'
                      - name: الأربعاء
                        times:
                          - from: '06:00'
                            to: '22:00'
                    is_cod_available: true
                    is_default: false
                    type: branch
                    cod_cost: '5.00'
                    country:
                      id: 1723506348
                      name: مصر
                      name_en: Egypt
                      code: EG
                      mobile_code: '+20'
                    city:
                      id: 1355786303
                      name: CAIRO
                      name_en: CAIRO
                      country_id: 1723506348
                pagination:
                  count: 2
                  total: 2
                  perPage: 15
                  currentPage: 1
                  totalPages: 1
                  links: []
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
                    branches.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: Branch
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Branches
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394224-run
components:
  schemas:
    branches_response_body:
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
            id: hdg40rkd0c10o
          items:
            $ref: '#/components/schemas/Branch'
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
    Branch:
      description: >-
        Detailed structure of the branch model object showing its fields and
        data types.
      type: object
      title: Branch
      x-tags:
        - Models
      properties:
        id:
          description: The unique identifier of a branch.
          type: number
        name:
          type: string
          description: >-
            The label given to a specific branch. 🌐 [Support
            multi-language](doc-421122)
        status:
          type: string
          description: >-
            The status of the branch, indicating whether it is "Active" as open
            for business or "Inactive" as closed.
          enum:
            - active
            - inactive
          x-apidog-enum:
            - value: active
              name: ''
              description: Open for business
            - value: inactive
              name: ''
              description: Closed for business
        location:
          type: object
          description: Branch's location on map in both longitude and latitude
          properties:
            lat:
              type: string
              description: Latitude of the location.
            lng:
              type: string
              description: Longitude of the location.
          x-apidog-orders:
            - lat
            - lng
          required:
            - lat
            - lng
          x-apidog-ignore-properties: []
        street:
          type: string
          description: Branch's street name. 🌐 [Support multi-language](doc-421122)
        address_description:
          type: string
          description: >-
            Branch's address description. 🌐 [Support
            multi-language](doc-421122)
        additional_number:
          type: string
          description: 'Branch''s additional (alternative) phone number. '
        building_number:
          type: string
          description: 'Branch''s building number. '
        local:
          type: string
          description: Branch's local district. 🌐 [Support multi-language](doc-421122)
        postal_code:
          type: string
          description: Branch's postal code. Value length is 5 characters long.
        contacts:
          type: object
          description: Branch's contacts details.
          properties:
            phone:
              type: string
              description: Branch phone number.
            whatsapp:
              type: string
              description: Branch whatsapp number.
            telephone:
              type: string
              description: Branch telephone number.
          x-apidog-orders:
            - phone
            - whatsapp
            - telephone
          required:
            - phone
            - whatsapp
            - telephone
          x-apidog-ignore-properties: []
        preparation_time:
          type: string
          description: >-
            The time required for the branch to get an order ready for shipping
            or pickup.
        is_open:
          type: boolean
          description: Whether or not the branch is currently `open` or `closed`
        closest_time:
          type: object
          properties:
            from:
              type: string
            to:
              type: string
          x-apidog-orders:
            - from
            - to
          description: >-
            The time when the branch will be closed based on the request time.
            Each request may have a different value. 
          x-apidog-ignore-properties: []
          nullable: true
        working_hours:
          description: Branch working hours. Required if `branch.type is "branch"`
          type: array
          items:
            type: object
            properties:
              name:
                type: string
              times:
                type: array
                items:
                  type: object
                  properties:
                    from:
                      type: string
                    to:
                      type: string
                  x-apidog-orders:
                    - from
                    - to
                  x-apidog-ignore-properties: []
            x-apidog-orders:
              - name
              - times
            x-apidog-ignore-properties: []
        is_cod_available:
          type: boolean
          description: Whether or not Cash on delivery available.
        is_default:
          type: boolean
          description: Whether or not this branch is the default branch for all operations.
        type:
          type: string
          description: Branch type, either a standard `branch` or `warehouse`
          enum:
            - branch
            - warehouse
          x-apidog-enum:
            - value: branch
              name: ''
              description: >-
                A physical location where customers can shop, interact, and
                access services.
            - value: warehouse
              name: ''
              description: >-
                A facility for storing inventory and managing order fulfillment
                logistics.
        cod_cost:
          type: string
          description: |+
            Cash on delivery cost value

        country:
          $ref: '#/components/schemas/Country'
        city:
          type: object
          properties:
            id:
              type: number
              description: A unique identifier or code assigned to a specific city.
            name:
              type: string
              description: >-
                The lable used for a specific urban area or municipality within
                a country or region.
            name_en:
              type: string
              description: City name expressed in English characters.
            country_id:
              type: number
              description: Unique identifier of the country
          x-apidog-refs: {}
          x-apidog-orders:
            - id
            - name
            - name_en
            - country_id
          required:
            - id
            - name
            - name_en
            - country_id
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - id
        - name
        - status
        - location
        - street
        - address_description
        - additional_number
        - building_number
        - local
        - postal_code
        - contacts
        - preparation_time
        - is_open
        - closest_time
        - working_hours
        - is_cod_available
        - is_default
        - type
        - cod_cost
        - country
        - city
      required:
        - id
        - name
        - status
        - location
        - street
        - address_description
        - additional_number
        - building_number
        - local
        - postal_code
        - contacts
        - preparation_time
        - is_open
        - closest_time
        - working_hours
        - is_cod_available
        - is_default
        - type
        - cod_cost
        - country
        - city
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Country:
      description: >-
        Detailed structure of the country model object showing its fields and
        data types.
      type: object
      x-examples: {}
      x-tags:
        - Models
      title: Country
      properties:
        id:
          description: A unique identifier assigned to a specific country.
          type: number
        name:
          type: string
          description: >-
            The official or commonly used name of a specific nation or
            geographic region.
        name_en:
          type: string
          description: Country name expressed in English characters.
        code:
          type: string
          description: >-
            Country iso code , a standardized, three-letter code assigned to
            each country by the International Organization for Standardization.
        mobile_code:
          type: string
          description: >-
            The international dialing code used to make phone calls to a
            specific country from abroad, also known as the country's "calling
            code."
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
