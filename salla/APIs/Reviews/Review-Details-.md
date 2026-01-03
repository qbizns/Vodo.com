# Review Details 

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /reviews/{id}:
    get:
      summary: 'Review Details '
      deprecated: false
      description: >-
        This endpoint allows you to fetch a specific review by passing the `id`
        as a path parameter. 


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `reviews.read`- Reviews Read Only

        </Accordion>
      operationId: get-feedbacks-feedback
      tags:
        - Merchant API/APIs/Reviews
        - Reviews
      parameters:
        - name: id
          in: path
          description: >-
            Review ID. Get a list of Review IDs from
            [here](https://docs.salla.dev/api-16603963)
          required: true
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/review_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 1096828836
                  type: ask
                  rating: 0
                  content: A question
                  order_id: null
                  is_published: true
                  created_at: '2023-11-01 15:32:31'
                  customer:
                    id: 1227534533
                    name: Ahmed Ali
                    mobile: 565555555
                    email: ahmed@mail.com
                    avatar: >-
                      https://cdn.assets.salla.network/admin/cp/assets/images/avatar_male.png
                    country: السعودية
                    city: الرياض
          headers: {}
          x-apidog-name: Success
      security:
        - bearer: []
      x-salla-php-method-name: retrieve
      x-salla-php-return-type: Feedbacks
      x-apidog-folder: Merchant API/APIs/Reviews
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-16603964-run
components:
  schemas:
    review_response_body:
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
          $ref: '#/components/schemas/Reviews'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Reviews:
      type: object
      properties:
        id:
          type: number
          description: >-
            A unique identifier associated with a specific review. List of
            reviews can be found [here](https://docs.salla.dev/api-5394279).
        type:
          type: string
          description: The type of the review.
          enum:
            - rating
            - ask
            - shipping
            - testimonial
          x-apidog-enum:
            - value: rating
              name: ''
              description: Review for rating.
            - value: ask
              name: ''
              description: Review for asking.
            - value: shipping
              name: ''
              description: Review for shipping.
            - value: testimonial
              name: ''
              description: Review for testimonials.
        rating:
          type: number
          description: Review rating value.
        content:
          type: string
          description: Review content.
        order_id:
          description: >-
            Review order unique identifier. List of orders can be found
            [here](https://docs.salla.dev/api-5394146)
          type: number
          nullable: true
        is_published:
          type: boolean
          description: Whether or not the review is published publicly.
        created_at:
          type: string
          description: String representation of the review original creation date and time.
        customer:
          type: object
          properties:
            id:
              type: number
              description: >-
                Customer's unique identification assigned to an individual
                customer. List of customers can be found
                [here](https://docs.salla.dev/api-5394121).
            name:
              type: string
              description: Customer's given full name.
            mobile:
              type: number
              description: The numerical contact information belonging to a customer.
            email:
              type: string
              description: Email address of the customer used for electronic communication.
            avatar:
              type: string
              description: >-
                Image representing the customer, often a personal photograph or
                avatar.
            country:
              type: string
              description: >-
                The nation of origin or residence of the customer. List of
                countries can be found
                [here](https://docs.salla.dev/api-5394228).
            city:
              type: string
              description: >-
                The city where the customer resides. List of cities can be found
                [here](https://docs.salla.dev/api-5394230).
          x-apidog-orders:
            - id
            - name
            - mobile
            - email
            - avatar
            - country
            - city
          required:
            - id
            - name
            - mobile
            - email
            - avatar
            - country
            - city
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - id
        - type
        - rating
        - content
        - order_id
        - is_published
        - created_at
        - customer
      required:
        - id
        - type
        - rating
        - content
        - order_id
        - is_published
        - created_at
        - customer
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
