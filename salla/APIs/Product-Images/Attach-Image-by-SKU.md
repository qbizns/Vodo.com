# Attach Image by SKU

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /products/sku/{sku}/images:
    post:
      summary: Attach Image by SKU
      deprecated: false
      description: >-
        This endpoint allows you to attach an image by passing the `sku` as a
        path parameter. 



        :::check[Note]

        Make sure that you upload a file of image format by sending the body
        data as `multipart/form-data` 


        :::

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `products.read_write`- Products Read & Write

        </Accordion>
      operationId: post-products-sku-sku-images
      tags:
        - Merchant API/APIs/Product Images
        - Product Images
      parameters:
        - name: sku
          in: path
          description: >-
            The Product SKU. List of Product SKU can be found
            [here](https://docs.salla.dev/api-5394168).
          required: true
          example: ''
          schema:
            type: string
      requestBody:
        content:
          multipart/form-data:
            schema:
              type: object
              properties:
                photo:
                  description: Upload an image as file
                  example: '@/home/user/Pictures/sku-image.png'
                  type: string
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/productImagesVideos_response_body'
              example:
                status: 201
                success: true
                data:
                  id: 1908361139
                  image:
                    original:
                      url: https://i.ibb.co/jyqRQfQ/avatar-male.webp
                      width: 0
                      height: 0
                    standard_resolution:
                      url: https://i.ibb.co/jyqRQfQ/avatar-male.webp
                      width: 1000
                      height: 424.597364568082
                    low_resolution:
                      url: https://i.ibb.co/jyqRQfQ/avatar-male.webp
                      width: 500
                      height: 212.298682284041
                    thumbnail:
                      url: https://i.ibb.co/jyqRQfQ/avatar-male.webp
                      width: 120
                      height: 50.95168374816984
                  sort: 0
                  default: false
                  alt_seo: Product details
                  video_url: ''
                  type: image
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
                    products.read_write
          headers: {}
          x-apidog-name: Unauthorized
        '404':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Object%20Not%20Found(404)'
              example:
                status: 404
                success: false
                error:
                  code: error
                  message: المحتوى الذي تحاول الوصول اليه غير متوفر
          headers: {}
          x-apidog-name: Not Found
        '422':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/error_validation_422'
              example:
                status: 422
                success: false
                error:
                  code: error
                  message: alert.invalid_fields
                  fields:
                    photo:
                      - حقل photo مطلوب.
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-salla-php-method-name: attachImageBySKU
      x-salla-php-return-type: ProductImagesVideos
      x-apidog-folder: Merchant API/APIs/Product Images
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394184-run
components:
  schemas:
    productImagesVideos_response_body:
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
          $ref: '#/components/schemas/ProductImagesVideos'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    ProductImagesVideos:
      title: ProductImagesVideos
      type: object
      properties:
        id:
          type: number
          description: A unique identifier associated with the product image.
          examples:
            - 1908361139
        image:
          type: object
          properties:
            original:
              type: object
              properties:
                url:
                  type: string
                  description: Original image URL
                  examples:
                    - https://i.ibb.co/jyqRQfQ/avatar-male.webp
                width:
                  type: number
                  description: Original image width
                  examples:
                    - 0
                height:
                  type: number
                  description: Original image height
                  examples:
                    - 0
              x-apidog-orders:
                - url
                - width
                - height
              required:
                - url
                - width
                - height
              x-apidog-ignore-properties: []
            standard_resolution:
              type: object
              properties:
                url:
                  type: string
                  description: Standard resolution image URL
                  examples:
                    - https://i.ibb.co/jyqRQfQ/avatar-male.webp
                width:
                  type: number
                  description: Standard resolution image W\width
                  examples:
                    - 0
                height:
                  type: number
                  description: Standard resolution image height
                  examples:
                    - 0
              x-apidog-orders:
                - url
                - width
                - height
              required:
                - url
                - width
                - height
              x-apidog-ignore-properties: []
            low_resolution:
              type: object
              properties:
                url:
                  type: string
                  description: Low resolution image URL
                  examples:
                    - https://i.ibb.co/jyqRQfQ/avatar-male.webp
                width:
                  type: number
                  description: Low resolution image width
                  examples:
                    - 0
                height:
                  type: number
                  description: Low resolution image height
                  examples:
                    - 0
              x-apidog-orders:
                - url
                - width
                - height
              required:
                - url
                - width
                - height
              x-apidog-ignore-properties: []
            thumbnail:
              type: object
              properties:
                url:
                  type: string
                  description: Thumbnail image URL
                  examples:
                    - https://i.ibb.co/jyqRQfQ/avatar-male.webp
                width:
                  type: number
                  description: Thumbnail image width
                  examples:
                    - 0
                height:
                  type: number
                  description: Thumbnail image height
                  examples:
                    - 0
              x-apidog-orders:
                - url
                - width
                - height
              required:
                - url
                - width
                - height
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - original
            - standard_resolution
            - low_resolution
            - thumbnail
          required:
            - original
            - standard_resolution
            - low_resolution
            - thumbnail
          x-apidog-ignore-properties: []
        sort:
          type: number
          description: Sort order
          examples:
            - 0
        default:
          type: boolean
          description: Whether or not the image the default picture
          default: false
        alt_seo:
          type: string
          description: Alternative SEO text for the image
          nullable: true
        video_url:
          type: string
          description: Video web address.
        type:
          type: string
          description: Attachment type
          examples:
            - image
      x-apidog-orders:
        - id
        - image
        - sort
        - default
        - alt_seo
        - video_url
        - type
      required:
        - id
        - image
        - sort
        - default
        - alt_seo
        - video_url
        - type
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
        error:
          $ref: '#/components/schemas/Validation'
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
