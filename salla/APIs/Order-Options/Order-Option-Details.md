# Order Option Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /orders/options/{id}:
    get:
      summary: Order Option Details
      deprecated: false
      description: |-
        This endpoint allows you to list a specific order option details

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">
        `orders.read` - Orders Read Only
        </Accordion>
      operationId: show-order-option
      tags:
        - Merchant API/APIs/Order Options
        - Order Option
      parameters:
        - name: id
          in: path
          description: >-
            Unique identification number associated with a specific order
            options
          required: true
          example: 123456787
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/orderOption_response_body'
              examples:
                '1':
                  summary: Text Option
                  value:
                    status: 200
                    success: true
                    data:
                      id: 123456787
                      name: text order option
                      reference_id: 123456786
                      description: some description
                      status: true
                      is_required: false
                      type: text
                      length: '80'
                      is_multi_choice: false
                      selected_products: true
                      categories:
                        - id: 123456788
                          name: category
                        - id: 123456789
                          name: sub category
                      price: 0
                      currency: SAR
                '2':
                  summary: Number Option
                  value:
                    status: 200
                    success: true
                    data:
                      id: 123456787
                      name: number order option
                      reference_id: 123456786
                      description: some description
                      status: true
                      is_required: false
                      type: number
                      length: '80'
                      is_multi_choice: false
                      selected_products: true
                      categories:
                        - id: 123456788
                          name: category
                        - id: 123456789
                          name: sub category
                      price: 0
                      currency: SAR
                '3':
                  summary: Checkbox Option
                  value:
                    status: 200
                    success: true
                    data:
                      id: 123456787
                      name: checkbox order option
                      reference_id: 123456786
                      description: some description
                      status: true
                      is_required: false
                      type: checkbox
                      length: '80'
                      is_multi_choice: false
                      selected_products: false
                      categories: []
                      price: 0
                      currency: SAR
                      options:
                        - id: 123456788
                          name: option field 1
                          price: '25'
                          cost: null
                          weight: null
                        - id: 123456789
                          name: option field 2
                          price: '30'
                          cost: null
                          weight: null
                '4':
                  summary: Days Option
                  value:
                    status: 200
                    success: true
                    data:
                      id: 123456787
                      name: date order option
                      reference_id: 123456786
                      description: some description
                      status: true
                      is_required: false
                      type: date
                      length: '80'
                      is_multi_choice: false
                      selected_products: false
                      categories: []
                      price: 100
                      currency: SAR
                      booking_details:
                        id: 123456788
                        type: date
                        location: here
                        sessions_count: 1
                        time_strict_value: 1
                        time_strict_type: days
                        availabilities:
                          - id: 123456789
                            day: monday
                            is_available: true
                            times:
                              - from: null
                                to: null
                          - id: 123456799
                            day: tuesday
                            is_available: true
                            times:
                              - from: null
                                to: null
                        overrides:
                          - id: 123456778
                            date: '2025-01-30'
                          - id: 123456779
                            date: '2025-01-31'
                '5':
                  summary: Days & Times Option
                  value:
                    status: 200
                    success: true
                    data:
                      id: 123456787
                      name: date order option
                      reference_id: 123456786
                      description: some description
                      status: true
                      is_required: false
                      type: date
                      length: '80'
                      is_multi_choice: false
                      selected_products: false
                      categories: []
                      price: 100
                      currency: SAR
                      booking_details:
                        id: 123456788
                        type: date_time
                        location: here
                        sessions_count: 1
                        time_strict_value: 1
                        time_strict_type: days
                        availabilities:
                          - id: 123456789
                            day: monday
                            is_available: true
                            times:
                              - from: '10:00'
                                to: '16:00'
                          - id: 123456799
                            day: tuesday
                            is_available: true
                            times:
                              - from: '10:00'
                                to: '12:00'
                              - from: '14:00'
                                to: '16:00'
                        overrides:
                          - id: 123456778
                            date: '2025-01-30'
                          - id: 123456779
                            date: '2025-01-31'
                        session_duration: 90
                        session_gap: 10
          headers: {}
          x-apidog-name: Success
        '401':
          description: ''
          content:
            application/json:
              schema:
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
                  error:
                    $ref: '#/components/schemas/Unauthorized'
                x-apidog-orders:
                  - status
                  - success
                  - error
                x-apidog-ignore-properties: []
          headers: {}
          x-apidog-name: error_unauthorized_401
        '404':
          description: ''
          content:
            application/json:
              schema:
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
                    x-stoplight:
                      id: f4ajks6ba59j4
                    description: >-
                      Response flag, boolean indicator used to signal a
                      particular condition or state in the response of a system
                      or application, often representing the presence or absence
                      of certain conditions or outcomes.
                  error:
                    $ref: '#/components/schemas/NotFound'
                x-apidog-orders:
                  - status
                  - success
                  - error
                x-apidog-ignore-properties: []
          headers: {}
          x-apidog-name: error_notFound_404
      security:
        - bearer: []
      x-salla-php-method-name: showOrderOption
      x-salla-php-return-type: OrderOption
      x-apidog-folder: Merchant API/APIs/Order Options
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-13121125-run
components:
  schemas:
    orderOption_response_body:
      type: object
      properties:
        status:
          type: integer
        success:
          type: boolean
        data:
          $ref: '#/components/schemas/OrderOption'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    OrderOption:
      type: object
      description: >-
        Dynamic cart-level options that allow users to make selections during
        checkout
      properties:
        id:
          type: integer
          description: Unique Identifier
        name:
          type: string
          description: The label or textual identifier for the checkout field option
        reference_id:
          type: integer
          description: >-
            Unique identification number associated with the refrence of the
            order option
        description:
          type:
            - string
            - 'null'
          description: Additional details about this checkout field option
        status:
          type: boolean
          description: status of the order option either available or not
        is_required:
          type: string
          description: Whether this field is mandatory during checkout
        type:
          type: string
          enum:
            - text
            - number
            - checkbox
            - date
          description: The type of input field to render
          x-apidog-enum:
            - name: ''
              value: text
              description: Text input field
            - name: ''
              value: number
              description: Numeric input field
            - name: ''
              value: checkbox
              description: Checkbox selection
            - name: ''
              value: date
              description: Date picker field
        length:
          type: integer
          description: Maximum length for text/number input fields
        is_multi_choice:
          type: boolean
          description: Whether multiple selections are allowed
        selected_products:
          type: boolean
          description: Whether this option applies to specific products
        categories:
          type: array
          items:
            type: object
            properties:
              id:
                type: integer
                description: 'Unique identification number associated with the category '
              name:
                type: string
                description: Label or title of the category
            x-apidog-orders:
              - id
              - name
            description: ID of a product category
            required:
              - id
              - name
            x-apidog-ignore-properties: []
          description: Product Categories this option applies to
        price:
          type: number
          description: Price adjustment for this option
        currency:
          type: string
          description: Currencu of the order option price
        options:
          type: object
          description: Configuration for option choices
          properties:
            id:
              type: integer
              description: Uniqiue identification number associated with the option
            name:
              type: string
              description: Name of the option choice
            price:
              type:
                - integer
                - 'null'
              description: Price adjustment for this option choiceThe ad
            cost:
              type:
                - integer
                - 'null'
              description: Cost associated with this option choice
            weight:
              type:
                - integer
                - 'null'
              description: Weight adjustment for this option choice
            is_default:
              type: boolean
              description: Whether or not this option is set as default
          x-apidog-orders:
            - id
            - name
            - price
            - cost
            - weight
            - is_default
          required:
            - id
            - name
          x-apidog-ignore-properties: []
        booking_details:
          type: object
          description: Configuration for bookable options
          properties:
            id:
              type: integer
              description: Unique identification number associated with the booking
            type:
              type: string
              enum:
                - date
                - date_time
              description: Type of booking date selection
              x-apidog-enum:
                - value: date
                  name: ''
                  description: Date only selection
                - value: date_time
                  name: ''
                  description: Date and time selection
            location:
              type: string
              description: Location where the booking takes place
            sessions_count:
              type: integer
              description: Number of sessions included in the booking
            time_strict_value:
              type: integer
              description: Time restriction value for bookings
            time_strict_type:
              type: string
              description: Type of time restriction for the booking
            maximum_quantity_per_order:
              type: integer
              description: Maximum number of bookings allowed per order
            availabilities:
              type: array
              description: Available booking slots
              items:
                type: object
                properties:
                  id:
                    type: integer
                    description: >-
                      Unique identification number associated with the booking
                      availability 
                  day:
                    type: string
                    description: Day of the week or specific date
                  is_available:
                    type: boolean
                    description: Whether bookings are available on this day
                  times:
                    type: array
                    description: Available time slots
                    items:
                      type: object
                      properties:
                        from:
                          type: string
                          description: Start time of the slot
                        to:
                          type: string
                          description: End time of the slot
                      required:
                        - from
                        - to
                      x-apidog-orders:
                        - from
                        - to
                      x-apidog-ignore-properties: []
                required:
                  - id
                  - day
                  - is_available
                x-apidog-orders:
                  - id
                  - day
                  - is_available
                  - times
                x-apidog-ignore-properties: []
            overrides:
              type: array
              items:
                type: object
                properties:
                  id:
                    type: integer
                  date:
                    type: string
                x-apidog-orders:
                  - id
                  - date
                description: Special scheduling override rules
                required:
                  - id
                  - date
                x-apidog-ignore-properties: []
              description: List of scheduling overrides
          required:
            - id
            - type
            - location
            - sessions_count
            - time_strict_value
            - time_strict_type
            - availabilities
          x-apidog-orders:
            - id
            - type
            - location
            - sessions_count
            - time_strict_value
            - time_strict_type
            - maximum_quantity_per_order
            - availabilities
            - overrides
          x-apidog-ignore-properties: []
        session_duration:
          type: integer
          description: The timing of the session
        session_gap:
          type: integer
          description: The time between each session
      x-apidog-orders:
        - id
        - name
        - reference_id
        - description
        - status
        - is_required
        - type
        - length
        - is_multi_choice
        - selected_products
        - categories
        - price
        - currency
        - options
        - booking_details
        - session_duration
        - session_gap
      required:
        - name
        - type
      allOf:
        - type: string
        - type: string
        - type: string
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    NotFound:
      type: object
      properties:
        code:
          anyOf:
            - type: string
            - type: number
          description: >-
            Not Found Response error code, a numeric or alphanumeric unique
            identifier used to represent the error.
        message:
          type: string
          description: >-
            A message or data structure that is generated or returned when the
            response is not found or explain the error.
      x-apidog-orders:
        - code
        - message
      required:
        - code
        - message
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
