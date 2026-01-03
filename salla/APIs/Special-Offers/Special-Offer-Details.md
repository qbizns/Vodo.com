# Special Offer Details

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /specialoffers/{specialoffer}:
    get:
      summary: Special Offer Details
      deprecated: false
      description: >-
        This endpoint allows you to return the complete details for a specific
        special offer by passing the `specialoffer` as a path parameter. 



        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `specialoffers.read`- Special Offers Read Only

        </Accordion>
      operationId: Special-Offer-Details
      tags:
        - Merchant API/APIs/Special Offers
        - Special Offers
      parameters:
        - name: specialoffer
          in: path
          description: >-
            Unique identification number assigned to the Special Offer. List of
            Special Offers IDs can be found
            [here](https://docs.salla.dev/api-5394218)
          required: true
          example: 0
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/specialOffer_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 1410023903
                  name: نسبة من المشتريات
                  message: احصل على خصم  {offer_percent}
                  expiry_date: '2022-06-26 20:45:00'
                  start_date: '2022-06-21 09:50:00'
                  offer_type: percentage
                  status: active
                  show_price_after_discount: false
                  show_discounts_table_message: false
                  applied_to: order
                  buy:
                    min_amount: 200
                    min_items: 0
                  get:
                    discount_amount: '10.00'
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
                    specialoffers.read
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
      security:
        - bearer: []
      x-salla-php-method-name: retrieve
      x-salla-php-return-type: SpecialOffer
      x-apidog-folder: Merchant API/APIs/Special Offers
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394219-run
components:
  schemas:
    specialOffer_response_body:
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
          $ref: '#/components/schemas/SpecialOffer'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    SpecialOffer:
      description: >-
        Detailed structure of the special offer model object showing its fields
        and data types.
      type: object
      title: SpecialOffer
      x-tags:
        - Models
      properties:
        id:
          type: number
          description: >-
            A unique identifier associated with a particular promotional or
            discount offe.
        name:
          type: string
          description: >-
            A descriptive label or title given to a specific promotional offer
            to distinguish it from others. 🌐 [Support
            multi-language](doc-421122)
        message:
          type: string
          description: >-
            A brief statement or communication that conveys the details or
            benefits of a specific promotional or discount offer. 🌐 [Support
            multi-language](doc-421122)
        expiry_date:
          type: string
          description: >-
            The date when a specific promotional or discount offer expires or
            ends.
          examples:
            - '2025-01-01'
        start_date:
          type: string
          description: >-
            Special offer start date  is the date when a specific promotional or
            discount offer begins or becomes active.
        offer_type:
          type: string
          description: >-
            The category or classification that describes a particular
            promotion, discount, or deal.
          enum:
            - buy_x_get_y
            - fixed_amount
            - percentage
            - discounts_table
          x-apidog-enum:
            - name: Buy X Get Y
              value: buy_x_get_y
              description: >-
                A promotion where purchasing a specified quantity (X) qualifies
                the customer to receive another item (Y) for free or at a
                discounted price.
            - name: Fixed Amount Discount
              value: fixed_amount
              description: >-
                A discount that applies a fixed monetary reduction to the order
                total or specific items.
            - name: Percentage Discount
              value: percentage
              description: >-
                A discount calculated as a percentage of the order total or the
                price of specific items.
            - name: Discounts Table
              value: discounts_table
              description: >-
                A tiered discount structure where different discount rates are
                applied based on quantity thresholds or total spend.
        status:
          type: string
          description: >-
            The current condition of a specific discount offer, such as whether
            it is active, expired, or in a pending or inactive status.
        show_price_after_discount:
          type: boolean
          description: The option to show the price after discount.
        show_discounts_table_message:
          type: boolean
          description: >-
            Whether or not to show  information presented in a table format that
            displays various discounts.
        buy:
          type: object
          description: Specifics on items required for offer eligibility.
          properties:
            type:
              type: string
              description: >-
                Product Type to buy to be eligible for the special offer.
                Required if `offer_type = buy_x_get_y`
              enum:
                - category
                - product
              x-apidog-enum:
                - value: category
                  name: ''
                  description: 'Purchase by the type category '
                - value: product
                  name: ''
                  description: Purchase by the type product
            quantity:
              type: number
              description: >-
                Product Quantity to buy to be eligible for the special offer.
                Required if `offer_type = buy_x_get_y`
            products:
              type: array
              uniqueItems: true
              description: >-
                The Products to be purchased that are included in the special
                offer. Make sure to pass the Product IDs in an array. This field
                is mandatory when `buy.type` is set to `product`.
              items: &ref_0
                $ref: '#/components/schemas/ProductCard'
            categories:
              type: array
              description: >
                The Categories included in the special offer. Make sure to pass
                the Category IDs in an array. List of Category IDs can be foun
                [here](https://docs.salla.dev/5394207e0) This field is mandatory
                when `buy.type` is set to `category`.
              items: &ref_1
                $ref: '#/components/schemas/Category'
          x-apidog-orders:
            - type
            - quantity
            - products
            - categories
          required:
            - type
            - quantity
          x-apidog-ignore-properties: []
        get:
          type: object
          description: Specifics of the offer.
          properties:
            type:
              type: string
              description: The type of the offer.
            discount_type:
              type: string
              description: >-
                Discount Type to get if eligible for the special offer. Required
                if `offer_type = buy_x_get_y`
              enum:
                - 'percentage '
                - free-product
              x-apidog-enum:
                - value: 'percentage '
                  name: ''
                  description: >-
                    A discount calculated as a percentage of the order total or
                    the price of specific items.
                - value: free-product
                  name: ''
                  description: ' A promotion that allows the customer to receive a specific product for free as part of the deal'
            quantity:
              type: integer
              description: >-
                Product Quantity to get if eligible for the special offer.
                Required if `offer_type = buy_x_get_y`
            products:
              type: array
              uniqueItems: true
              description: >-
                The Products to get if eligible for the special offer that are
                included in the special offer. Make sure to pass the Product IDs
                in an array. This field is mandatory when `get.type` is set to
                `product`.
              items: *ref_0
            categories:
              type: array
              description: >
                The Categories included in the special offer. Make sure to pass
                the Product IDs in an array. This field is mandatory when
                `get.type` is set to `category`.
              items: *ref_1
          x-apidog-orders:
            - type
            - discount_type
            - quantity
            - products
            - categories
          required:
            - type
            - discount_type
            - quantity
            - products
            - categories
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - id
        - name
        - message
        - expiry_date
        - start_date
        - offer_type
        - status
        - show_price_after_discount
        - show_discounts_table_message
        - buy
        - get
      required:
        - id
        - name
        - message
        - expiry_date
        - start_date
        - offer_type
        - status
        - show_price_after_discount
        - show_discounts_table_message
        - buy
        - get
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Category:
      title: Category
      type: object
      properties:
        id:
          type: number
          description: ID of the category
        name:
          type: string
          description: Name of category.
        url:
          type: string
          description: Url link of the category.
      x-apidog-orders:
        - id
        - name
        - url
      required:
        - id
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    ProductCard:
      description: >-
        Detailed structure of the Product short payload model object showing its
        fields and data types.
      type: object
      title: ProductCard
      x-tags:
        - Models
      properties:
        id:
          type: number
          description: A unique identifier associated with a specific product.
        type:
          type: string
          description: >-
            The category or classification that a specific product belongs to
            based on its attributes, characteristics, or intended use.
          enum:
            - product
            - service
            - group_products
            - codes
            - digital
            - food
            - donating
          x-apidog-enum:
            - value: product
              name: ''
              description: Tangible and shippable products
            - value: service
              name: ''
              description: >-
                Servecable products, such as design, rsearch, printing, writing
                etc
            - value: group_products
              name: ''
              description: More than a product under one product
            - value: codes
              name: ''
              description: >-
                Chargable cards (PlayStation Cards), sellable account (Netflix)
                etc
            - value: digital
              name: ''
              description: Electronic books, Courses, Downloadable files etc
            - value: food
              name: ''
              description: Food and drinks that require special shipping
            - value: donating
              name: ''
              description: Only in case when the store is of type charity
        promotion:
          type: object
          description: Product promotion details.
          properties:
            title:
              type: string
              description: >-
                The name or label assigned to a specific marketing or
                promotional campaign, deal, or offer.
            sub_title:
              type: string
              description: >-
                The additional name or label assigned to a specific marketing or
                promotional campaign, deal, or offer. 
          x-apidog-orders:
            - title
            - sub_title
          required:
            - title
            - sub_title
          x-apidog-ignore-properties: []
        status:
          type: string
          description: The product status. available values 'hidden','sale','out'.
        is_available:
          type: boolean
          description: Check if the product is available to order or in-stock.
        sku:
          type: string
          description: >-
            A unique Stock Keeping Unit (SKU) identifier assigned to a specific
            variant of a product.
        name:
          type: string
          description: The name or title of a product.
        price:
          type: object
          description: Product price details
          properties:
            amount:
              type: number
              description: Product price amount
            currency:
              type: string
              description: Product price currency
          x-apidog-orders:
            - amount
            - currency
          x-apidog-ignore-properties: []
        sale_price:
          type: object
          description: Product sale price details
          properties:
            amount:
              type: number
              description: Product sale price amount
            currency:
              type: string
              description: Product sale price curren
          x-apidog-orders:
            - amount
            - currency
          required:
            - amount
            - currency
          x-apidog-ignore-properties: []
        url:
          type: string
          description: 'Product url '
        has_special_price:
          type: boolean
          description: Whether or not the product has a special price
        regular_price:
          type: object
          description: Product regular price details
          properties:
            amount:
              type: number
              description: Product regular price amount
            currency:
              type: string
              description: Product regular price currency
          x-apidog-orders:
            - amount
            - currency
          required:
            - amount
            - currency
          x-apidog-ignore-properties: []
        currency:
          type: string
          description: The specific currency of the product price.
        thumbnail:
          type: string
          description: Scaled-down image or visual representation of a product.
        calories:
          type: string
          description: Calories amount of the product.
          nullable: true
        mpn:
          type: string
          description: >-
            Manufacturer Part Number, a unique identifier assigned by a
            manufacturer to a specific product or component, which helps
            distinguish it from other similar products and facilitates inventory
            management, product tracking, and ordering processes.
          nullable: true
        gtin:
          type: string
          description: >-
            "Global Trade Item Number" (GTIN), a unique and standardized
            identifier used to uniquely represent products, items, or services
            in the global marketplace, to enable efficient tracking and
            management across supply chains and retail sectors.
          nullable: true
        favorite:
          type: string
          description: Product marked as favorite
          nullable: true
        starting_price:
          description: Product starting price
          type: string
          nullable: true
      x-apidog-orders:
        - id
        - type
        - promotion
        - status
        - is_available
        - sku
        - name
        - price
        - sale_price
        - url
        - has_special_price
        - regular_price
        - currency
        - thumbnail
        - calories
        - mpn
        - gtin
        - favorite
        - starting_price
      required:
        - id
        - type
        - promotion
        - status
        - is_available
        - sku
        - name
        - price
        - sale_price
        - url
        - has_special_price
        - regular_price
        - currency
        - thumbnail
        - calories
        - mpn
        - gtin
        - favorite
        - starting_price
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
