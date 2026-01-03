# Attach Youtube Video

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /products/{product}/video:
    post:
      summary: Attach Youtube Video
      deprecated: false
      description: >-
        This endpoint allows you to add unlimited videos to a specific product
        by passing the `product` as a path parameter. 



        :::caution[Alert]

        - Videos **must** be added as Youtube links.

        - You can only add one video per request.

        :::


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `products.read_write`- Products Read & Write

        </Accordion>
      operationId: Attach-Youtube-Video
      tags:
        - Merchant API/APIs/Product Images
        - Product Images
      parameters:
        - name: product
          in: path
          description: >-
            Unique identification number assigned to a product. List of products
            IDs can be found [here](https://docs.salla.dev/api-5394168).
          required: true
          example: 0
          schema:
            type: integer
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/productVideo_request_body'
            example:
              video_url: https://www.youtube.com/watch?v=dmqWuUeA5Ug
              default: true
              alt: things from Google
      responses:
        '201':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/productImagesVideos_response_body'
              example:
                status: 201
                success: true
                data:
                  id: 1749963998
                  image:
                    original:
                      url: http://img.youtube.com/vi/dmqWuUeA5Ug/maxresdefault.jpg
                      width: 1920
                      height: 1080
                    standard_resolution:
                      url: http://img.youtube.com/vi/dmqWuUeA5Ug/sddefault.jpg
                      width: 640
                      height: 480
                    low_resolution:
                      url: http://img.youtube.com/vi/dmqWuUeA5Ug/mqdefault.jpg
                      width: 320
                      height: 180
                    thumbnail:
                      url: http://img.youtube.com/vi/dmqWuUeA5Ug/default.jpg
                      width: 120
                      height: 90
                  default: true
                  alt_seo: Introducing a few new helpful things from Google
                  video_url: https://www.youtube.com/watch?v=dmqWuUeA5Ug
                  type: video
          headers: {}
          x-apidog-name: Attached Successfully
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
                success: false
                status: 404
                error:
                  code: 404
                  message: The content you are trying to access is no longer available
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
                  code: validation_failed
                  message: The validation process failed
                  fields:
                    '{field-name}':
                      - The {field-label} field is required.
          headers: {}
          x-apidog-name: Error Validation
      security:
        - bearer: []
      x-salla-php-method-name: attachYoutubeVideo
      x-salla-php-return-type: ProductImagesVideos
      x-apidog-folder: Merchant API/APIs/Product Images
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394186-run
components:
  schemas:
    productVideo_request_body:
      type: object
      properties:
        video_url:
          type: string
          description: >-
            A web address that links to a specific video file or content on the
            interne. | required
        default:
          type: boolean
          description: Set the video thumbnail image as default for product.
        alt:
          type: string
          description: >-
            Alternative description for the video thumbnail image to enhance
            SEO.
      required:
        - video_url
      x-apidog-orders:
        - video_url
        - default
        - alt
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
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
