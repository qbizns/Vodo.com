# Branch Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /branches/{branch}:
    get:
      summary: Branch Details
      deprecated: false
      description: >-
        This endpoint allows you to return the complete details for a specific
        branch by passing the `branch` as a path parameter. 



        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `branches.read`- Branches Read Only

        </Accordion>
      operationId: Branch-Details
      tags:
        - Merchant API/APIs/Branches
        - Branches
      parameters:
        - name: branch
          in: path
          description: >-
            The Branch ID. List of Branch IDs can be found
            [here](https://docs.salla.dev/api-5394224)
          required: true
          example: 0
          schema:
            type: integer
        - name: with
          in: query
          description: Used to fetch the branch details with translations
          required: false
          schema:
            type: string
            enum:
              - translations
            x-apidog-enum:
              - value: translations
                name: ''
                description: ''
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/branch_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 1846327032
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
                  closest_time: null
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
                  city:
                    id: 1355786303
                    name: CAIRO
                    name_en: CAIRO
                    country_id: 1723506348
          headers: {}
          x-apidog-name: Success
        '401':
          description: ''
          content:
            application/json:
              schema:
                title: ''
                type: object
                properties:
                  status:
                    type: number
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
                  error: &ref_0
                    $ref: '#/components/schemas/Unauthorized'
                x-apidog-orders:
                  - 01K1FR3PQF1V0GY2NP0NWEEJ1C
                x-apidog-refs:
                  01K1FR3PQF1V0GY2NP0NWEEJ1C:
                    $ref: '#/components/schemas/error_unauthorized_401'
                x-apidog-ignore-properties:
                  - status
                  - success
                  - error
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
        '404':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Object%20Not%20Found(404)'
              example:
                success: false
                status: 404
                error:
                  code: 404
                  message: The content you are trying to access is no longer available
          headers: {}
          x-apidog-name: Not Found
        x-200:With translations:
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
                    description: >-
                      Detailed structure of the branch model object showing its
                      fields and data types.
                    properties:
                      id:
                        type: integer
                        description: The unique identifier of a branch.
                      name:
                        type: string
                        description: >-
                          The label given to a specific branch. 🌐 [Support
                          multi-language](doc-421122)
                      status:
                        type: string
                        description: >-
                          The status of the branch, indicating whether it is
                          "Active" as open for business or "Inactive" as closed.
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
                        description: >-
                          Branch's location on map in both longitude and
                          latitude
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
                        description: >-
                          Branch's street name. 🌐 [Support
                          multi-language](doc-421122)
                      address_description:
                        type: string
                        description: >-
                          Branch's address description. 🌐 [Support
                          multi-language](doc-421122)
                      additional_number:
                        type: 'null'
                        description: Branch's additional (alternative) phone number.
                      building_number:
                        type: 'null'
                        description: Branch's building number.
                      local:
                        type: string
                        description: >-
                          Branch's local district. 🌐 [Support
                          multi-language](doc-421122)
                      postal_code:
                        type: string
                        description: >-
                          Branch's postal code. Value length is 5 characters
                          long.
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
                          The time required for the branch to get an order ready
                          for shipping or pickup.
                      is_open:
                        type: boolean
                        description: >-
                          Whether or not the branch is currently `open` or
                          `closed`
                      closest_time:
                        type: object
                        description: >-
                          The time when the branch will be closed based on the
                          request time. Each request may have a different value.
                        properties:
                          from:
                            type: string
                          to:
                            type: string
                        x-apidog-orders:
                          - from
                          - to
                        x-apidog-ignore-properties: []
                      working_hours:
                        type: array
                        description: >-
                          Branch working hours. Required if `branch.type is
                          "branch"`
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
                        description: >-
                          Whether or not this branch is the default branch for
                          all operations.
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
                              A physical location where customers can shop,
                              interact, and access services.
                          - value: warehouse
                            name: ''
                            description: >-
                              A facility for storing inventory and managing
                              order fulfillment logistics.
                      cod_cost:
                        type: string
                        description: Cash on delivery cost value
                      country:
                        type: object
                        properties:
                          id:
                            type: integer
                          name:
                            type: string
                          name_en:
                            type: string
                          code:
                            type: string
                          mobile_code:
                            type: string
                          capital:
                            type: 'null'
                        x-apidog-orders:
                          - id
                          - name
                          - name_en
                          - code
                          - mobile_code
                          - capital
                        x-apidog-ignore-properties: []
                      city:
                        type: object
                        properties:
                          id:
                            type: integer
                            description: >-
                              A unique identifier or code assigned to a specific
                              city.
                          name:
                            type: string
                            description: >-
                              The lable used for a specific urban area or
                              municipality within a country or region.
                          name_en:
                            type: string
                            description: City name expressed in English characters.
                          country_id:
                            type: integer
                            description: Unique identifier of the country
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
                      translations:
                        type: object
                        properties:
                          ar:
                            type: object
                            properties:
                              name:
                                type: string
                              address_description:
                                type: string
                              street:
                                type: string
                              local:
                                type: string
                              preparation_time:
                                type: string
                            x-apidog-orders:
                              - name
                              - address_description
                              - street
                              - local
                              - preparation_time
                            x-apidog-ignore-properties: []
                        x-apidog-orders:
                          - ar
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
                      - translations
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
                x-apidog-orders:
                  - status
                  - success
                  - data
                x-apidog-ignore-properties: []
              example:
                status: 200
                success: true
                data:
                  id: 1473353380
                  name: فرع حوش بكر
                  status: active
                  location:
                    lat: '21.3843141'
                    lng: '39.8512604'
                  street: Unnamed Road, المشاعر
                  address_description: Unnamed Road, المشاعر، مكة 24251، السعودية
                  additional_number: null
                  building_number: null
                  local: المشاعر
                  postal_code: '24251'
                  contacts:
                    phone: '0598084006'
                    whatsapp: '0598084006'
                    telephone: '7185538740'
                  preparation_time: ساعة
                  is_open: false
                  closest_time:
                    from: '05:00'
                    to: '07:25'
                  working_hours:
                    - name: السبت
                      times:
                        - from: '05:00'
                          to: '07:25'
                  is_cod_available: true
                  is_default: false
                  type: branch
                  cod_cost: '0.00'
                  country:
                    id: 1473353380
                    name: السعودية
                    name_en: Saudi Arabia
                    code: SA
                    mobile_code: '+966'
                    capital: null
                  city:
                    id: 1939592358
                    name: مكة
                    name_en: Mecca
                    country_id: 1473353380
                  translations:
                    ar:
                      name: فرع حوش بكر
                      address_description: Unnamed Road, المشاعر، مكة 24251، السعودية
                      street: Unnamed Road, المشاعر
                      local: المشاعر
                      preparation_time: '01:00:00'
          headers: {}
          x-apidog-name: With translations
      security:
        - bearer: []
      x-salla-php-method-name: retrieve
      x-salla-php-return-type: Branch
      x-apidog-folder: Merchant API/APIs/Branches
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394225-run
components:
  schemas:
    branch_response_body:
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
          $ref: '#/components/schemas/Branch'
      x-apidog-orders:
        - status
        - success
        - data
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
    Object Not Found(404):
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
        error:
          type: object
          properties:
            code:
              type: integer
              description: >-
                Not Found Response error code, a numeric or alphanumeric unique
                identifier used to represent the error.
            message:
              type: string
              description: >-
                A message or data structure that is generated or returned when
                the response is not found or explain the error.
          required:
            - code
            - message
          x-apidog-orders:
            - code
            - message
          x-apidog-ignore-properties: []
      required:
        - status
        - success
        - error
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
        error: *ref_0
      x-apidog-orders:
        - status
        - success
        - error
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
