# List Customers

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /customers:
    get:
      summary: List Customers
      deprecated: false
      description: >-
        This endpoint lets you list all customers associated with your store and
        filter them using a keyword. It retrieves customers whose `"mobile
        number"`, `"email"`, or `"name"` match the keyword you provide.


        :::info[]

        The rate limit for the customers endpoints is **`500`** request per
        **`10`** minutes. For more details, please visit the [Rate
        Limiting](https://docs.salla.dev/421125m0) & [change
        log](https://docs.salla.dev/421127m0#291---2024-04-16) documentation
        page.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `customers.read`- Customers Read Only

        </Accordion>
      operationId: List-Customers
      tags:
        - Merchant API/APIs/Customers
        - Customers
      parameters:
        - name: page
          in: query
          description: The Pagination page number
          required: false
          example: 2
          schema:
            type: integer
        - name: keyword
          in: query
          description: >-
            <br>- `customer.mobile` | 966511804534 <br>- `customer.name` | Del
            John <br>- `shipping_number` | Find a specific shipment by its ID
            from [here](api-5394160) <br>- `reference_id` | 613398835 <br>-
            `tag_name` | Find a specific tag by its name from
            [here](api-5394154)
          required: false
          example: '966511804534'
          schema:
            type: string
        - name: date_from
          in: query
          description: Fetch a list of customers created before a specific date
          required: false
          example: '2024-12-30'
          schema:
            type: string
        - name: date_to
          in: query
          description: Fetch a list of customers created after a specific date
          required: false
          example: '2024-12-31'
          schema:
            type: string
        - name: fields
          in: query
          description: 'Extra fields that can be included in the response. '
          required: false
          example:
            - '?fields[]=is_blocked&fields[]=block_reason'
          schema:
            type: array
            items:
              type: string
              enum:
                - is_blocked
                - is_whitelisted
                - block_reason
                - is_inactive
                - total_points
                - country_id
                - custom_fields
                - current_store_customer
                - is_orders_rated
                - is_notifications_enabled
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/customers_response_body'
              examples:
                '1':
                  summary: Example
                  value:
                    status: 200
                    success: true
                    data:
                      - id: 2075683582
                        first_name: Ahmed
                        last_name: Ali
                        mobile: 777777777
                        mobile_code: '967'
                        email: ahmedali@test.test
                        urls:
                          customer: https://shtara.com/profile
                          admin: https://shtara.com/profiley
                        avatar: https://i.ibb.co/jyqRQfQ/avatar-male.webp
                        gender: male
                        city: Riyadh
                        country: السعودية
                        location: Alaziziah Street, building number 5
                        updated_at:
                          date: '2020-04-02 22:43:26.000000'
                          timezone_type: 3
                          timezone: Asia/Riyadh
                        groups:
                          - 11323141
                          - 11323142
                      - id: 2075683581
                        first_name: Ali
                        last_name: Ahmed
                        mobile: 777777777
                        mobile_code: '967'
                        email: aliahmed@test.test
                        avatar: https://i.ibb.co/jyqRQfQ/avatar-male.webp
                        gender: male
                        city: Riyadh
                        country: السعودية
                        location: 'null'
                        updated_at:
                          date: '2020-04-02 22:43:26.000000'
                          timezone_type: 3
                          timezone: Asia/Riyadh
                        groups:
                          - 11323141
                          - 11323142
                    pagination:
                      count: 2
                      total: 2
                      perPage: 15
                      currentPage: 1
                      totalPages: 1
                      links: []
                '3':
                  summary: With Extra Keys
                  value:
                    status: 200
                    success: true
                    data:
                      - id: 234324533
                        full_name: Ali Ahmed
                        first_name: Ali
                        last_name: Ahmed
                        mobile: 567000000
                        mobile_code: '+966'
                        email: email9@outlook.com
                        urls:
                          customer: https://demostore.salla.sa/demo/profile
                          admin: https://s.salla.sa/customers/293847
                        avatar: >-
                          https://cdn.assets.salla.network/prod/admin/cp/assets/images/avatar_male.png
                        gender: male
                        birthday: null
                        city: جدة
                        country: السعودية | Saudi Arabia
                        country_code: SA
                        currency: SAR
                        location: حي المحمدية - شارع عبدالعزيز اسماعيل
                        lang: ar
                        created_at:
                          date: '2025-07-24 12:23:42.000000'
                          timezone_type: 3
                          timezone: Asia/Riyadh
                        updated_at:
                          date: '2025-07-24 12:23:42.000000'
                          timezone_type: 3
                          timezone: Asia/Riyadh
                        groups:
                          - 1113849985
                        block_reason: null
                        is_blocked: false
                        is_inactive: false
                        is_notifications_enabled: true
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
                    customers.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: Customer
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Customers
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394121-run
components:
  schemas:
    customers_response_body:
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
            id: ers7i2jitiqqp
          items:
            $ref: '#/components/schemas/Customer'
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
    Customer:
      description: >-
        Detailed structure of the customer model object showing its fields and
        data types.
      type: object
      x-tags:
        - Models
      title: Customer
      x-examples:
        Example:
          id: 2107468057
          first_name: Mohammed
          last_name: Ali
          mobile: 665323256199
          mobile_code: '+999'
          email: customer@demo.com
          avatar: https://i.ibb.co/jyqRQfQ/avatar-male.webp
          gender: male
          birthday:
            date: '1997-06-03 00:00:00.000000'
            timezone_type: 3
            timezone: Asia/Riyadh
          city: الرياض
          country: السعودية
          country_code: sa
          currency: SAR
          location: '35.35418'
          updated_at: '2020-01-01 01:01:00.000000'
          groups:
            - 11323141
            - 11323142
      properties:
        id:
          type: number
          description: A unique identifier for the customer.
        first_name:
          type: string
          description: The customer's first name.
          maxLength: 25
        last_name:
          type: string
          description: The customer's last name.
          maxLength: 25
        mobile:
          type: number
          description: The customer's mobile phone number without the country code.
        mobile_code:
          type: string
          description: The country code for the customer's mobile phone number.
        email:
          type: string
          format: email
          description: The customer's email address.
        urls:
          $ref: '#/components/schemas/URLs'
          description: >-
            A list of URLs associated with the customer, such as their website
            or social media profiles.
        avatar:
          type: string
          description: A URL to the customer's avatar image.
        gender:
          type: string
          description: The customer's gender
          enum:
            - male
            - female
          x-apidog-enum:
            - value: male
              name: ''
              description: Male Person
            - value: female
              name: ''
              description: Female Person
        birthday:
          type: object
          properties:
            date:
              type: string
              format: date-time
              description: The customer's date of birth.
            timezone_type:
              type: integer
              format: int32
              description: The time zone type for the customer's date of birth.
            timezone:
              type: string
              description: The time zone for the customer's date of birth.
          x-apidog-orders:
            - date
            - timezone_type
            - timezone
          required:
            - date
            - timezone_type
          description: The customer date of birth
          x-apidog-ignore-properties: []
        city:
          type: string
          description: The city where the customer lives.
        country:
          type: string
          description: The country where the customer lives.
        country_code:
          type: string
          description: The country code for the customer's country.
        currency:
          type: string
          description: The currency that the customer uses.
        location:
          type: string
          description: The customer's location, represented as a string.
        updated_at:
          $ref: '#/components/schemas/Date'
          description: The date and time when the customer's information was last updated.
        groups:
          type: array
          items:
            type: integer
          description: A list of group IDs that the customer belongs to.
      required:
        - id
        - first_name
        - last_name
        - mobile
        - mobile_code
        - email
        - urls
        - avatar
        - gender
        - birthday
        - city
        - country
        - country_code
        - currency
        - location
        - updated_at
        - groups
      x-apidog-orders:
        - id
        - first_name
        - last_name
        - mobile
        - mobile_code
        - email
        - urls
        - avatar
        - gender
        - birthday
        - city
        - country
        - country_code
        - currency
        - location
        - updated_at
        - groups
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Date:
      type: object
      title: Date
      x-examples:
        Example:
          date: '2020-10-14 14:28:03.000000'
          timezone_type: 3
          timezone: Asia/Riyadh
      x-tags:
        - Models
      properties:
        date:
          type: string
          format: date-time
          description: >-
            A specific point in time, typically expressed in terms of a calendar
            system, including the day, month, year, hour, minutes, seconds and
            nano seconds. For example: "2020-10-14 14:28:03.000000"
        timezone_type:
          type: number
          description: 'Timezone type of the date, for Middel East = 3 '
        timezone:
          type: string
          description: Timezone value "Asia/Riyadh"
      x-apidog-orders:
        - date
        - timezone_type
        - timezone
      required:
        - date
        - timezone_type
        - timezone
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    URLs:
      description: >-
        To help companies and merchants, Salla provides a “urls” attribute that
        has been added to different modules to guide the merchants to have the
        full URL of this module from both scopes, the dashboard scope as a store
        admin, and as a customer.
      type: object
      title: Urls
      x-examples:
        Example:
          customer: https://shtara.com/profile
          admin: https://shtara.com/profiles
      x-tags:
        - Models
      properties:
        customer:
          type: string
          description: Customer link directly to the order.
          examples:
            - https://salla.sa/StoreLink
        admin:
          type: string
          description: Admin dashboard link directly to the order.
          examples:
            - https://s.salla./YourStoreDashboard
        digital_content:
          type: string
          description: >-
            A direct URL link to the digital asset, such as an e-book, image,
            PDF, video, or any downloadable file linked to the order or product.
        rating:
          type: string
          description: >-
            Order Rating Link. <br> Note that the order has to be of either of
            the following statuses: `completed`, `delivered`, or `shipped`. The
            merchant has to allow the product to be rated from the [Store
            Settings](https://s.salla.sa/settings) > Rating Settings
        checkout:
          type: string
          description: >-
            Order Checkout URL. <br>Note that the variable will only be returned
            if the order is unpaid. If the order is already paid, the variable
            will not appear in the response.
      x-apidog-orders:
        - customer
        - admin
        - digital_content
        - rating
        - checkout
      required:
        - customer
        - admin
        - digital_content
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
