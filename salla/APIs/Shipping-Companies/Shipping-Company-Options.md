# Shipping Company Options

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /shipping/companies/{company_id}/options:
    get:
      summary: Shipping Company Options
      deprecated: false
      description: >-
        This endpoint is used to show the shipping company's options when
        issuing an AWB for an order
      tags:
        - Merchant API/APIs/Shipping Companies
        - Shipping Companies
      parameters:
        - name: company_id
          in: path
          description: >-
            Unique identification number assigned to a Shipping Company. Get a
            list of Shpping companies IDs
            [here](https://docs.salla.dev/5578815e0)
          required: true
          example: 1723506348
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/shippingCompanyOptions_response_body'
              example:
                status: 200
                success: true
                data:
                  - name: boxes
                    label: عدد الكراتين
                    type: items
                    format: dropdown-list
                    required: true
                    description: ''
                    options:
                      - value: 1
                        label: 1
                      - value: 2
                        label: 2
                      - value: 3
                        label: 3
                      - value: 4
                        label: 4
                      - value: 5
                        label: 5
                      - value: 6
                        label: 6
                      - value: 7
                        label: 7
                      - value: 8
                        label: 8
                      - value: 9
                        label: 9
                      - value: 10
                        label: 10
                  - name: without_products
                    label: عدم ارسال تفاصيل المنتجات في البوليصة
                    type: boolean
                    format: checkbox
                    required: false
                    description: ''
          headers: {}
          x-apidog-name: Success
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
              example:
                status: 404
                success: false
                error:
                  code: 404
                  message: المحتوى الذي تحاول الوصول اليه غير متوفر
          headers: {}
          x-apidog-name: error_notFound_404
      security:
        - bearer: []
      x-apidog-folder: Merchant API/APIs/Shipping Companies
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-8817101-run
components:
  schemas:
    shippingCompanyOptions_response_body:
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
            $ref: '#/components/schemas/shippingCompanyOptions'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    shippingCompanyOptions:
      type: object
      properties:
        name:
          type: string
          description: The label used to describe a specific option
        label:
          type: string
          description: >-
            The title used to describe a specific option associated with a
            company.
        type:
          type: string
          description: >-
            Type of the company option, it can be items or a boolean  

            Allowed values: `items`, `boolean`,`number`, `string`,`collection`,
            `static`
        format:
          type: string
          description: >-
            The format of the company option can be a dropdown-list or checkbox.


            Allowed values: `dropdown-list`,`radio-list`, `checkbox`, `switch`,
            `text`, `number`, `slider`
        required:
          type: boolean
          description: This is to indicate if the company option is obligatory.
        description:
          type: string
          description: >-
            A detailed information about an option or attribute associated with
            a company.
        options:
          type: array
          items:
            type: object
            properties:
              value:
                type: string
                description: The value associated with a specific option.
              label:
                type: string
                description: The description associated with a specific option.
            x-apidog-orders:
              - value
              - label
            x-apidog-ignore-properties: []
      x-apidog-orders:
        - name
        - label
        - type
        - format
        - required
        - description
        - options
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
