# List Branch Delivery Zones

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /branches/delivery-zones:
    get:
      summary: List Branch Delivery Zones
      deprecated: false
      description: >-
        This endpoint is used to retrieve delivery zone configurations for
        specific branches. It helps determine which geographical areas (zones)
        each branch can serve for deliveries


        :::warning[]

        This endpoint is accessable only for allowed applications.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `branches.read`- Branchs Read

        </Accordion>
      tags:
        - Merchant API/APIs/Branch Delivery Zones
        - Branch Delivery Zones
      parameters:
        - name: branch_id
          in: query
          description: ID of the specific branch for which to retrieve delivery zones.
          required: false
          example: 566146469
          schema:
            type: integer
        - name: per_page
          in: query
          description: Delivery zonses limit per page
          required: false
          example: 20
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/BranchDeliveryZones_response_body'
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
                  - 01K6SQGX0T3T8315S7H95QRNW9
                x-apidog-refs:
                  01K6SQGX0T3T8315S7H95QRNW9:
                    $ref: '#/components/schemas/error_unauthorized_401'
                x-apidog-ignore-properties:
                  - status
                  - success
                  - error
          headers: {}
          x-apidog-name: Unauthorized
        '403':
          description: ''
          content:
            application/json:
              schema:
                title: ''
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
                  error:
                    type: object
                    properties:
                      code:
                        type: integer
                        description: >-
                          Not Found Response error code, a numeric or
                          alphanumeric unique identifier used to represent the
                          error.
                      message:
                        type: string
                        description: >-
                          A message or data structure that is generated or
                          returned when the response is not found or explain the
                          error.
                    required:
                      - code
                      - message
                    x-apidog-orders:
                      - code
                      - message
                    x-apidog-ignore-properties: []
                x-apidog-orders:
                  - 01K6SQ8ZQPYFTYQ0Y0G18A9F20
                required:
                  - status
                  - success
                  - error
                x-apidog-refs:
                  01K6SQ8ZQPYFTYQ0Y0G18A9F20:
                    $ref: '#/components/schemas/error_forbidden_403'
                x-apidog-ignore-properties:
                  - status
                  - success
                  - error
          headers: {}
          x-apidog-name: Forbidden
        '422':
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
                  error: &ref_1
                    $ref: '#/components/schemas/Validation'
                x-apidog-orders:
                  - 01K6SQDNH9FSK0QRP31QRKDC6H
                x-apidog-refs:
                  01K6SQDNH9FSK0QRP31QRKDC6H:
                    $ref: '#/components/schemas/error_validation_422'
                x-apidog-ignore-properties:
                  - status
                  - success
                  - error
          headers: {}
          x-apidog-name: Parameter Error
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Branch Delivery Zones
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-22300545-run
components:
  schemas:
    BranchDeliveryZones_response_body:
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
        data:
          type: array
          items:
            $ref: '#/components/schemas/BranchDeliveryZone'
        pagination:
          $ref: '#/components/schemas/Pagination'
      required:
        - status
        - success
        - data
        - pagination
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
    BranchDeliveryZone:
      type: object
      properties:
        id:
          type: integer
          description: Unique identifier of the delivery zone configuration
        branch_id:
          type: integer
          description: >-
            The branch to which this delivery zone belongs, list of branches can
            be found [here](https://docs.salla.dev/api-5394224)
        status:
          type: boolean
          description: >-
            Indicates whether the delivery zone is active `(true)` or inactive
            `(false)`
        rules:
          type: object
          properties:
            coverage:
              type: object
              properties:
                enabled:
                  type: boolean
                  description: >-
                    Indicates if the delivery coverage is currently active for
                    this zone
                method:
                  type: string
                  description: |-
                    Specifies the method used to define the delivery area:
                    •`radius`  circular zone based on a center point.
                    •`polygon`  custom geofenced area defined by coordinates.
                radius_meters:
                  type: 'null'
                  description: >-
                    Required when `method` is `radius`; specifies the radius (in
                    meters) of the delivery area.
                coordinates:
                  type: array
                  items:
                    type: object
                    properties:
                      lat:
                        type: number
                        description: Latitude of the polygon point.
                      lng:
                        type: number
                        description: Longitude of the polygon point.
                    required:
                      - lat
                      - lng
                    x-apidog-orders:
                      - lat
                      - lng
                    x-apidog-ignore-properties: []
                  description: >-
                    List of geographic points (`lat`/`lng`) defining the
                    polygonal delivery zone. Required when`method` is `polygon`
                  nullable: true
              required:
                - enabled
                - method
                - radius_meters
                - coordinates
              x-apidog-orders:
                - enabled
                - method
                - radius_meters
                - coordinates
              x-apidog-ignore-properties: []
            working_hours:
              type: array
              items:
                type: object
                properties:
                  name:
                    type: string
                    description: Represents the day of the week
                  times:
                    type: array
                    items:
                      type: object
                      properties:
                        from:
                          type: string
                          description: The start time of the working interval
                        to:
                          type: string
                          description: The end time of the working interval
                      x-apidog-orders:
                        - from
                        - to
                      required:
                        - from
                        - to
                      x-apidog-ignore-properties: []
                    description: >-
                      List of start and end times for working intervals on that
                      day.
                x-apidog-orders:
                  - name
                  - times
                required:
                  - name
                  - times
                x-apidog-ignore-properties: []
              description: >-
                Defines the operating schedule (opening and closing times) for
                each day of the week for a specific delivery zone or route
          required:
            - coverage
          x-apidog-orders:
            - coverage
            - working_hours
          description: Contains configuration rules for delivery coverage
          x-apidog-ignore-properties: []
      required:
        - id
        - branch_id
        - status
        - rules
      x-apidog-orders:
        - id
        - branch_id
        - status
        - rules
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
    error_forbidden_403:
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
    Validation:
      type: object
      properties:
        code:
          type: string
          description: >-
            Response error code,a numeric or alphanumeric unique identifier used
            to represent the error.
        message:
          type: string
          description: >-
            A message or data structure that is generated or returned when the
            response is not found or explain the error.
        fields:
          type: object
          description: Validation rules with problems
          properties:
            '{field-name}':
              type: array
              items:
                type: string
          x-apidog-orders:
            - '{field-name}'
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - code
        - message
        - fields
      required:
        - code
        - message
        - fields
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    error_validation_422:
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
        error: *ref_1
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
