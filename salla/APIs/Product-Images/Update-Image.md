# Update Image

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /products/images/{image-id}:
    post:
      summary: Update Image
      deprecated: false
      description: >-
        This endpoint allows you to update a specific product's image by passing
        the `image_id` as a path parameter. The updating of the image can be
        done either via providing a URL link to the image or uploading an image
        via `multipart/form-data` media type.


        :::tip[Note]

        You can only update **one** image per request.

        :::

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `products.read_write`- Products Read & Write

        </Accordion>
      operationId: Update-Image
      tags:
        - Merchant API/APIs/Product Images
        - Product Images
      parameters:
        - name: image-id
          in: path
          description: Unique identifier assigend to image.
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
                  description: >-
                    A specific location or directory on a computer's file system
                    that indicates the file's location.
                  example: '@"Users/Images/profile.png"'
                  type: string
                default:
                  description: Set the image as default
                  example: 1
                  type: integer
                sort:
                  description: Sorting order of the image
                  example: 1
                  type: number
                alt:
                  description: >-
                    Alternative text to appear when the image doesn't load
                    properly.
                  example: Alt Text
                  type: string
              required:
                - photo
      responses:
        '201':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/productImagesVideos_response_body'
              examples:
                '1':
                  summary: Example
                  value:
                    status: 201
                    success: true
                    data:
                      id: 713368802
                      image:
                        original:
                          url: >-
                            https://cdn.salla.sa/bYQEn/9oJoUzXfR8zzXAwAlTRHNIl1xBsuFpgcXdPnkAXd.png
                          width: 0
                          height: 0
                        standard_resolution:
                          url: >-
                            https://cdn.salla.sa/bYQEn/9oJoUzXfR8zzXAwAlTRHNIl1xBsuFpgcXdPnkAXd.png
                          width: 0
                          height: 0
                        low_resolution:
                          url: >-
                            https://cdn.salla.sa/bYQEn/9oJoUzXfR8zzXAwAlTRHNIl1xBsuFpgcXdPnkAXd.png
                          width: 0
                          height: 0
                        thumbnail:
                          url: >-
                            https://cdn.salla.sa/bYQEn/9oJoUzXfR8zzXAwAlTRHNIl1xBsuFpgcXdPnkAXd.png
                          width: 0
                          height: 0
                      sort: 5
                      default: true
                      alt_seo: image
                      video_url: ''
                      type: image
                '3':
                  summary: Example 2
                  value:
                    status: 201
                    success: true
                    data:
                      id: 2034175368
                      image:
                        original:
                          url: >-
                            https://cdn.salla.sa/bYQEn/xf2HIfmcjgFMTB5iTqnuHfVW0fouPYJeoWJGTAna.png
                          width: 0
                          height: 0
                        standard_resolution:
                          url: >-
                            https://cdn.salla.sa/bYQEn/zHsjZmIbNW9XvZUeJmFCBDPyCD7cHXMToauJookQ.png
                          width: 865.8008658008658
                          height: 1000
                        low_resolution:
                          url: >-
                            https://cdn.salla.sa/bYQEn/nHVR0xcIv8WRGTsADk1jqxh7oMdKnjPjhSRCYSbI.png
                          width: 432.9004329004329
                          height: 500
                        thumbnail:
                          url: >-
                            https://cdn.salla.sa/bYQEn/pTMADemQhQUd38ZH1EhIeErgVVSjHT5BFJVLZbd9.png
                          width: 103.89610389610388
                          height: 119.99999999999999
                      sort: 4
                      default: true
                      alt_seo: Dress image
                      video_url: ''
                      type: image
          headers: {}
          x-apidog-name: Updated Successfully
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
          x-apidog-name: Record Not Found
      security:
        - bearer: []
      x-salla-php-method-name: update
      x-salla-php-return-type: ProductImagesVideos
      x-apidog-folder: Merchant API/APIs/Product Images
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394188-run
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
