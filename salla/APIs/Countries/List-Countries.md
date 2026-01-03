# List Countries

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /countries:
    get:
      summary: List Countries
      deprecated: false
      description: |-
        This endpoint allows you to list all available countries. 

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">
        `metadata.read`- Metadata Read Only
        </Accordion>
      operationId: List-Countries
      tags:
        - Merchant API/APIs/Countries
        - Countries
      parameters:
        - name: page
          in: query
          description: The Pagination page number
          required: false
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/countries_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 1473353380
                    name: السعودية
                    name_en: Saudi Arabia
                    code: SA
                    mobile_code: '+966'
                    capital: Riyadh
                  - id: 566146469
                    name: الامارات
                    name_en: United Arab Emirates
                    code: AE
                    mobile_code: '+971'
                    capital: Abu Dhabi
                  - id: 1939592358
                    name: الكويت
                    name_en: Kuwait
                    code: KW
                    mobile_code: '+965'
                    capital: Kuwait City
                  - id: 1298199463
                    name: قطر
                    name_en: Qatar
                    code: QA
                    mobile_code: '+974'
                    capital: Doha
                  - id: 525144736
                    name: البحرين
                    name_en: Bahrain
                    code: BH
                    mobile_code: '+973'
                    capital: Manama
                  - id: 1764372897
                    name: العراق
                    name_en: Iraq
                    code: IQ
                    mobile_code: '+964'
                    capital: Baghdad
                  - id: 989286562
                    name: عمان
                    name_en: Oman
                    code: OM
                    mobile_code: '+968'
                    capital: Muscat
                  - id: 349994915
                    name: اليمن
                    name_en: Yemen
                    code: YE
                    mobile_code: '+967'
                    capital: Sanaa
                  - id: 1723506348
                    name: مصر
                    name_en: Egypt
                    code: EG
                    mobile_code: '+20'
                    capital: Cairo
                  - id: 814202285
                    name: السودان
                    name_en: Sudan
                    code: SD
                    mobile_code: '+249'
                    capital: Khartoum
                  - id: 40688814
                    name: ليبيا
                    name_en: Libya
                    code: LY
                    mobile_code: '+218'
                    capital: Tripoli
                  - id: 1548352431
                    name: الجزائر
                    name_en: Algeria
                    code: DZ
                    mobile_code: '+213'
                    capital: Algiers
                  - id: 773200552
                    name: تونس
                    name_en: Tunisia
                    code: TN
                    mobile_code: '+216'
                    capital: Tunis
                  - id: 2079537577
                    name: المغرب
                    name_en: Morocco
                    code: MA
                    mobile_code: '+212'
                    capital: Rabat
                  - id: 1440241834
                    name: سوريا
                    name_en: Syria
                    code: SY
                    mobile_code: '+963'
                    capital: Damascus
                pagination:
                  count: 15
                  total: 240
                  perPage: 15
                  currentPage: 1
                  totalPages: 16
                  links:
                    next: https://api.salla.dev/admin/v2/countries?page=2
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
                    metadata.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: Country
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Countries
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394228-run
components:
  schemas:
    countries_response_body:
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
            id: 9gvz22sl2eadi
          items:
            $ref: '#/components/schemas/Country'
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
