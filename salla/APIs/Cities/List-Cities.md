# List Cities

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /countries/{country}/cities:
    get:
      summary: List Cities
      deprecated: false
      description: >-
        This endpoint allows you to list all available cities for a specific
        country by passing the `country` as a path parameter. 



        :::note

        [Country details](https://docs.salla.dev/api-5394229) will also be
        returned in the payload.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `metadata.read`- Metadata Read Only

        </Accordion>
      operationId: List-Cities
      tags:
        - Merchant API/APIs/Cities
        - Cities
      parameters:
        - name: country
          in: path
          description: >-
            Unique identification number assigned to the Country. Get a list of
            country IDs [here](https://docs.salla.dev/5394228e0).
          required: true
          example: 0
          schema:
            type: integer
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
                $ref: '#/components/schemas/cities_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 1473353380
                    name: Riyadh
                    name_en: Riyadh
                    country_id: 1473353380
                  - id: 566146469
                    name: Jeddah
                    name_en: Jeddah
                    country_id: 1473353380
                  - id: 1939592358
                    name: Mecca
                    name_en: Mecca
                    country_id: 1473353380
                  - id: 1298199463
                    name: Medina
                    name_en: Medina
                    country_id: 1473353380
                  - id: 525144736
                    name: Dammam
                    name_en: Dammam
                    country_id: 1473353380
                  - id: 1764372897
                    name: Al Ahsa
                    name_en: Al Ahsa
                    country_id: 1473353380
                  - id: 989286562
                    name: Al Qatif
                    name_en: Al Qatif
                    country_id: 1473353380
                  - id: 349994915
                    name: Khamis Mushait
                    name_en: Khamis Mushait
                    country_id: 1473353380
                  - id: 1723506348
                    name: Almuzaylif
                    name_en: Almuzaylif
                    country_id: 1473353380
                  - id: 814202285
                    name: Tabuk
                    name_en: Tabuk
                    country_id: 1473353380
                  - id: 40688814
                    name: Al Hofuf
                    name_en: Al Hofuf
                    country_id: 1473353380
                  - id: 1548352431
                    name: Al Mubarraz
                    name_en: Al Mubarraz
                    country_id: 1473353380
                  - id: 773200552
                    name: Najran
                    name_en: Najran
                    country_id: 1473353380
                  - id: 2079537577
                    name: Hafar Al Batin
                    name_en: Hafar Al Batin
                    country_id: 1473353380
                  - id: 1440241834
                    name: Al Jubail
                    name_en: Al Jubail
                    country_id: 1473353380
                country:
                  id: 1473353380
                  name: Saudi Arabia
                  code: SA
                pagination:
                  count: 15
                  total: 905
                  perPage: 15
                  currentPage: 1
                  totalPages: 61
                  links:
                    next: >-
                      http://api.salla.dev/admin/v2/countries/1473353380/cities?page=2
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
        '404':
          description: ''
          content:
            application/json:
              schema:
                type: object
                properties: {}
              example:
                success: false
                status: 404
                error:
                  code: 404
                  message: The content you are trying to access is no longer available
          headers: {}
          x-apidog-name: Not Found
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: City
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Cities
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394230-run
components:
  schemas:
    cities_response_body:
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
            id: 5ci3iu1w4j585
          items:
            $ref: '#/components/schemas/City'
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
    City:
      description: >-
        Detailed structure of the city model object showing its fields and data
        types.
      type: object
      x-examples: {}
      x-tags:
        - Models
      title: City
      properties:
        id:
          type: number
          description: A unique identifier or code assigned to a specific city.
        name:
          type: string
          description: >-
            The lable used for a specific urban area or municipality within a
            country or region.
        name_en:
          type: string
          description: City name expressed in English characters.
      x-apidog-orders:
        - id
        - name
        - name_en
      required:
        - id
        - name
        - name_en
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
