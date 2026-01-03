# List Estimate Rates

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /shipping/companies/estimate-rate:
    get:
      summary: List Estimate Rates
      deprecated: false
      description: >-
        This endpoint allows you to fetch all of the shipping companies'
        estimate rates, based on the customer's order address 
      operationId: get-shipping-companies-estimate-rate
      tags:
        - Merchant API/APIs/Shipping Companies
        - Shipping Companies
      parameters:
        - name: city_id
          in: query
          description: >-
            Unique identification number assigned to the City. Get a list of
            City IDs from [here](https://docs.salla.dev/api-5394230)
          required: true
          schema:
            type: integer
        - name: country_id
          in: query
          description: >-
            Unique identification number assigned to the Country. Get a list of
            Country IDs from [here](https://docs.salla.dev/api-5394228)
          required: true
          schema:
            type: integer
        - name: order_id
          in: query
          description: >-
            Unique identification number assigned to the Order . Get a list of
            Order IDs from [here](https://docs.salla.dev/api-5394146)
          required: false
          schema:
            type: integer
        - name: geocode
          in: query
          description: Geographic Location Code
          required: false
          schema:
            type: string
        - name: postal_code
          in: query
          description: Address Postal Code value
          required: false
          schema:
            type: string
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/estimateRates_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 534427805
                    company_id: 1892382264
                    title: ريدبوكس
                    logo: >-
                      https://cdn.salla.sa/qGNRv/EHSZest9WHlDTmTJjJnEWqgUu75NLQ5gNJin0FhA.png
                    working_days: >-
                      [استلم طلبك من خزائن ريد بوكس الذكية RedBox، بالوقت
                      المناسب لك، الإيداع سيكون من 1-3 أيام]
                    total:
                      amount: 14
                      currency: SAR
                    services:
                      - name: cod
                        amount:
                          amount: 0
                          currency: SAR
                  - id: 1680687267
                    company_id: 941683204
                    title: ارامكس - هيمو
                    logo: >-
                      https://cdn.salla.sa/qGNRv/EHSZest9WHlDTmTJjJnEWqgUu75NLQ5gNJin0FhA.png
                    working_days: من 1 - 2  يوم عمل
                    total:
                      amount: 24
                      currency: SAR
                    services:
                      - name: cod
                        amount:
                          amount: 8
                          currency: SAR
                  - id: 1055252317
                    company_id: 1926022186
                    title: dhl - هيمو
                    logo: >-
                      https://cdn.salla.sa/qGNRv/EHSZest9WHlDTmTJjJnEWqgUu75NLQ5gNJin0FhA.png
                    working_days: 7 -20 يوم عمل
                    total:
                      amount: 28
                      currency: SAR
                    services:
                      - name: cod
                        amount:
                          amount: 0
                          currency: SAR
                  - id: 1130138841
                    company_id: 478065722
                    title: سمسا
                    logo: >-
                      https://cdn.salla.sa/qGNRv/xPQymWwCmTITkScYSnApzJO7eQLm5PrsRCltN6uo.png
                    working_days: ١ - ٣ أيام عمل
                    total:
                      amount: 28
                      currency: SAR
                    services:
                      - name: cod
                        amount:
                          amount: 0
                          currency: SAR
                  - id: 1172876926
                    company_id: 98754
                    title: HeMo Hassan
                    logo: >-
                      https://cdn.salla.sa/qGNRv/EHSZest9WHlDTmTJjJnEWqgUu75NLQ5gNJin0FhA.png
                    working_days: خلال يومين من إتمام عملية الدفع
                    total:
                      amount: 50
                      currency: SAR
                    services:
                      - name: cod
                        amount:
                          amount: 0
                          currency: SAR
          headers: {}
          x-apidog-name: Success
        '422':
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
                    $ref: '#/components/schemas/Validation'
                x-apidog-orders:
                  - status
                  - success
                  - error
                x-apidog-ignore-properties: []
              example:
                status: 422
                success: false
                error:
                  code: error
                  message: alert.invalid_fields
                  fields:
                    city_id:
                      - حقل city id مطلوب.
                    country_id:
                      - حقل country id مطلوب.
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Shipping Companies
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-6899590-run
components:
  schemas:
    estimateRates_response_body:
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
            $ref: '#/components/schemas/EstimateRates'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    EstimateRates:
      type: object
      properties:
        id:
          type: integer
          description: Estimated Rate ID
        company_id:
          type: integer
          description: >-
            Shipping company unique identifier. List of sipping companies can be
            found [here](https://docs.salla.dev/api-5394239)
          nullable: true
        title:
          type: string
          description: Shipping company title
        logo:
          type: string
          description: Shipping company logo
        working_days:
          type: string
          description: Shipping company working hours
        total:
          type: object
          properties:
            amount:
              type: integer
              description: Estimated Total Amount value
            currency:
              type: string
              description: Estimated Total Currency value
          x-apidog-orders:
            - amount
            - currency
          x-apidog-ignore-properties: []
        services:
          type: array
          items:
            type: object
            properties:
              name:
                type: string
                description: Service Name
              amount:
                type: object
                properties:
                  amount:
                    type: integer
                    description: Service Amount value
                  currency:
                    type: string
                    description: Service Currency value
                required:
                  - amount
                  - currency
                x-apidog-orders:
                  - amount
                  - currency
                x-apidog-ignore-properties: []
            x-apidog-orders:
              - name
              - amount
            required:
              - name
              - amount
            x-apidog-ignore-properties: []
      x-apidog-orders:
        - id
        - company_id
        - title
        - logo
        - working_days
        - total
        - services
      required:
        - id
        - company_id
        - title
        - logo
        - working_days
        - total
        - services
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
