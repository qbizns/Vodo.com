# Create Special Offer

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /specialoffers:
    post:
      summary: Create Special Offer
      deprecated: false
      description: |-
        This endpoint allows you to create a new special offer in the store.

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">
        `specialoffers.read_write`- Special Offers Read & Write
        </Accordion>
      operationId: Create-Special-Offer
      tags:
        - Merchant API/APIs/Special Offers
        - Special Offers
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/specialOffer_request_body'
            example:
              name: new offer
              message: Buy One Get One Free
              applied_channel: browser_and_application
              offer_type: buy_x_get_y
              applied_to: product
              start_date: '2024-12-30'
              expiry_date: '2024-12-31'
              min_purchase_amount: 100
              min_items_count: 2
              min_items: 0
              discounts_table:
                - quantity: 3
                  discount_amount: 5
                - quantity: 5
                  discount_amount: 10
              buy:
                type: product
                min_amount: 10
                quantity: 1
                products:
                  - 401511871
              get:
                type: product
                discount_type: percentage
                discount_amount: 5
                quantity: 1
                products:
                  - 401511871
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/specialOffer_response_body'
              examples:
                '1':
                  summary: Example Example | `offer_type = buy_x_get_y`
                  value:
                    status: 200
                    success: true
                    data:
                      id: 1935541690
                      name: new offer
                      message: >-
                        اشتري قطعة واحصل على قطعة واحدة خصم 5%  من المنتجات
                        التالية
                      start_date: '2024-12-08 16:00:00'
                      expiry_date: '2025-12-10 16:00:00'
                      offer_type: buy_x_get_y
                      status: inactive
                      show_price_after_discount: false
                      buy:
                        type: product
                        quantity: 1
                        products:
                          - id: 401511871
                            type: food
                            promotion:
                              title: Eid Alfitr Offer
                              sub_title: Special Promotion
                            status: sale
                            is_available: true
                            sku: ''
                            name: بيتزا
                            price:
                              amount: 40.25
                              currency: SAR
                            sale_price:
                              amount: 0
                              currency: SAR
                            currency: SAR
                            url: >-
                              https://salla.sa/dev-wofftr4xsra5xtlv/بيتزا/p401511871
                            thumbnail: >-
                              https://cdn.salla.sa/bYQEn/hG0U3oATexBxt4j5QjMt8jcNUi12v97KFw9Q1xTA.jpg
                            has_special_price: false
                            regular_price:
                              amount: 40.25
                              currency: SAR
                            calories: '500.00'
                            mpn: '677156713'
                            gtin: '76893972'
                            favorite: Product is Favourit
                      get:
                        type: product
                        discount_type: percentage
                        quantity: '1'
                        products: []
                '3':
                  summary: Example | `offer_type = fixed_amount`
                  value:
                    status: 200
                    success: true
                    data:
                      id: 843056940
                      name: new offer
                      message: اشتري قطعة واحصل على خصم 5 ر.س من المنتجات التالية
                      start_date: '2024-12-08 16:00:00'
                      expiry_date: '2025-12-10 16:00:00'
                      offer_type: fixed_amount
                      status: inactive
                      show_price_after_discount: false
                      show_discounts_table_message: false
                      applied_to: product
                      buy:
                        min_amount: 10
                        min_items: 0
                        products: []
                      get:
                        discount_amount: '5.00'
                '4':
                  summary: Example | `offer_type = percentage`
                  value:
                    status: 200
                    success: true
                    data:
                      id: 204285485
                      name: new offer
                      message: اشتري قطعة واحصل على خصم %5 من المنتجات التالية
                      start_date: '2024-12-08 16:00:00'
                      expiry_date: '2025-12-10 16:00:00'
                      offer_type: percentage
                      status: inactive
                      show_price_after_discount: false
                      show_discounts_table_message: false
                      applied_to: product
                      buy:
                        min_amount: 10
                        min_items: 0
                        products: []
                      get:
                        discount_amount: '5.00'
                '5':
                  summary: offer_type = discounts_table
                  value:
                    status: 200
                    success: true
                    data:
                      name: new discount table
                      applied_channel: application
                      offer_type: discounts_table
                      applied_to: product
                      start_date: '2024-12-08 16:00:00'
                      expiry_date: '2025-12-10 16:00:00'
                      min_purchase_amount: 100
                      min_items_count: 2
                      min_items: 0
                      discounts_table:
                        - quantity: 3
                          discount_amount: 5
                        - quantity: 5
                          discount_amount: 10
                      buy:
                        type: product
                        min_amount: 10
                        quantity: 3
                        products:
                          - 780490562
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
                    specialoffers.read_write
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: create
      x-salla-php-return-type: SpecialOffer
      x-apidog-folder: Merchant API/APIs/Special Offers
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394217-run
components:
  schemas:
    specialOffer_request_body:
      allOf:
        - type: object
          properties:
            name:
              type: string
              description: >-
                The title or label used to identify a specific promotional or
                discounted offer. 🌐 [Support
                multi-language](https://docs.salla.dev/doc-421122)
            applied_channel:
              type: string
              description: >-
                The specific platforms, channels, or methods through which a
                promotional or special offer is made available or applied.
              enum:
                - browser
                - browser_and_application
            offer_type:
              type: string
              description: >-
                The category or nature of a particular promotional or discount
                offer, such as buy-x get-y.
              enum:
                - buy_x_get_y
                - percentage
                - fixed_amount
                - discounts_table
              x-apidog-enum:
                - name: ''
                  value: buy_x_get_y
                  description: ''
                - name: ''
                  value: percentage
                  description: ''
                - name: ''
                  value: fixed_amount
                  description: ''
                - name: ''
                  value: discounts_table
                  description: ''
            applied_to:
              type: string
              description: >-
                The specific products, services, or items to which a particular
                promotional or discount offer is intended or allowed to be used
                or applied, specifying what the offer covers within a product or
                service catalog.
              enum:
                - order
                - product
                - category
                - paymentMethod
            start_date:
              type: string
              description: >-
                The date on which a promotional or discount offer start, before
                which it is not permitted to take advantage of the offer's
                benefits.


                **Ensure to follow on the format of the date.**
              format: date-time
            expiry_date:
              type: string
              description: >-
                The date on which a promotional or discount offer expires or
                becomes no longer valid, after which it is not permitted to take
                advantage of the offer's benefits.


                **Ensure to follow on the format of the date.**
              format: date-time
            min_purchase_amount:
              type: number
              description: >-
                The total minimum order amount to be purchased for this offer to
                be activated
            min_items_count:
              type: number
              description: The minimum order items count to activate this offer.
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
                quantity:
                  type: number
                  description: >-
                    Product Quantity to buy to be eligible for the special
                    offer. Required if `offer_type = buy_x_get_y`
                products:
                  type: array
                  uniqueItems: true
                  description: >-
                    The Products to be purchased that are included in the
                    special offer. Make sure to pass the Product IDs in an
                    array. This field is mandatory when `buy.type` is set to
                    `product`.
                  items: &ref_0
                    $ref: '#/components/schemas/ProductCard'
                categories:
                  type: array
                  description: >
                    The Categories included in the special offer. Make sure to
                    pass the Category IDs in an array. This field is mandatory
                    when `buy.type` is set to `category`.
                  items: &ref_1
                    $ref: '#/components/schemas/Category'
              x-apidog-orders:
                - type
                - quantity
                - products
                - categories
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
                    Discount Type to get if eligible for the special offer.
                    Required if `offer_type = buy_x_get_y`
                  enum:
                    - 'percentage '
                    - free-product
                quantity:
                  type: integer
                  description: >-
                    Product Quantity to get if eligible for the special offer.
                    Required if `offer_type = buy_x_get_y`
                products:
                  type: array
                  uniqueItems: true
                  description: >-
                    The Products to get if eligible for the special offer that
                    are included in the special offer. Make sure to pass the
                    Product IDs in an array. This field is mandatory when
                    `get.type` is set to `product`.
                  items: *ref_0
                categories:
                  type: array
                  description: >
                    The Categories included in the special offer. Make sure to
                    pass the Product IDs in an array. This field is mandatory
                    when `get.type` is set to `category`.
                  items: *ref_1
              x-apidog-orders:
                - type
                - discount_type
                - quantity
                - products
                - categories
              x-apidog-ignore-properties: []
            message:
              type: string
              description: >-
                A brief statement or communication that conveys the details or
                benefits of a specific promotional or discount offer. 🌐
                [Support multi-language](https://docs.salla.dev/doc-421122)
          x-apidog-orders:
            - name
            - applied_channel
            - offer_type
            - applied_to
            - start_date
            - expiry_date
            - min_purchase_amount
            - min_items_count
            - buy
            - get
            - message
          description: 'Other Offer Types '
          required:
            - name
            - applied_channel
            - offer_type
            - applied_to
          title: ''
          x-apidog-ignore-properties: []
        - type: object
          properties:
            name:
              type: string
              description: >-
                The title or label used to identify a specific promotional or
                discounted offer. 🌐 [Support multi-language](doc-421122)
            applied_channel:
              type: string
              description: >-
                The specific platforms, channels, or methods through which a
                promotional or special offer is made available or applied.
              enum:
                - browser
                - browser_and_application
            offer_type:
              type: string
              description: >-
                The category or nature of a particular promotional or discount
                offer, such as buy-x get-y.
              enum:
                - discounts_table
              x-apidog-enum:
                - name: ''
                  value: discounts_table
                  description: ''
            applied_to:
              type: string
              description: >-
                The specific products, services, or items to which a particular
                promotional or discount offer is intended or allowed to be used
                or applied, specifying what the offer covers within a product or
                service catalog.
              enum:
                - order
                - product
                - category
              x-apidog-enum:
                - name: ''
                  value: order
                  description: ''
                - name: ''
                  value: product
                  description: ''
                - name: ''
                  value: category
                  description: ''
            start_date:
              type: string
              description: >-
                The date on which a promotional or discount offer start, before
                which it is not permitted to take advantage of the offer's
                benefits.


                **Ensure to follow on the format of the date.**
              format: date-time
            expiry_date:
              type: string
              description: >-
                The date on which a promotional or discount offer expires or
                becomes no longer valid, after which it is not permitted to take
                advantage of the offer's benefits.


                **Ensure to follow on the format of the date.**
              format: date-time
            status:
              type: string
              enum:
                - active
                - inactive
              description: Special Offer's status, either `active` or `inactive`
              x-apidog-enum:
                - name: ''
                  value: active
                  description: Active Special Offer
                - name: ''
                  value: inactive
                  description: Inactive Special Offer
            applied_with_coupon:
              type: boolean
              description: Whether or not the offer should be applied with a valid coupon
            buy:
              type: object
              properties:
                categories:
                  type: array
                  items:
                    type: integer
                    description: >-
                      Category IDs. Get a list of Category IDs from
                      [here](https://docs.salla.dev/api-5394207)
                  description: Required if `applied_to` is set to `category`
                products:
                  type: array
                  items:
                    type: integer
                    description: >-
                      Product IDs. Get a list of Product IDs from
                      [here](https://docs.salla.dev/api-5394168)
                  description: Required if `applied_to` is set to `product`
              x-apidog-orders:
                - categories
                - products
              x-apidog-ignore-properties: []
            discounts_table:
              type: array
              items:
                type: object
                properties:
                  quantity:
                    type: string
                    description: Quantity to buy
                  discount:
                    type: string
                    description: Discount to get
                x-apidog-orders:
                  - quantity
                  - discount
                x-apidog-ignore-properties: []
              description: >-
                Tiered discount in the special offer. Required if `offer_type`
                is `discounts_table`
          x-apidog-orders:
            - name
            - applied_channel
            - offer_type
            - applied_to
            - start_date
            - expiry_date
            - status
            - applied_with_coupon
            - buy
            - discounts_table
          required:
            - name
            - applied_channel
            - offer_type
            - applied_to
            - expiry_date
          title: ''
          description: Discount Table Offer Type
          x-apidog-ignore-properties: []
      x-apidog-folder: ''
    Category:
      type: object
      title: Category
      x-tags:
        - Models
      x-examples: {}
      properties:
        id:
          type: number
          description: >-
            Category ID, is a unique identifier assigned to a specific product
            category, facilitating organized classification and efficient
            management of products within a similar group. List of categories
            can be found [here](https://docs.salla.dev/api-5394207).
        name:
          type: string
          description: >-
            Category name is a descriptive label assigned to a product category,
            aiding in clear identification and organization of related products.
            🌐 [Support multi-language](doc-421122)
        image:
          type: string
          description: The category image
        urls:
          $ref: '#/components/schemas/URLs'
        parent_id:
          type: integer
          description: >-
            Category Parent ID refers to the unique identifier assigned to the
            parent category of a subcategory, establishing a hierarchical
            relationship between different levels of product classification.
        sort_order:
          type: integer
          description: 'The sequence or arrangement of categories when displayed to users. '
          nullable: true
        status:
          type: string
          description: >-
            The category status indicates whether the category is currently
            visible and accessible to users `active` or intentionally concealed
            from view `hidden`. It essentially controls whether the category is
            publicly displayed or kept private within the system.
          enum:
            - active
            - hidden
          x-apidog-enum:
            - value: active
              name: ''
              description: The category is active and visible.
            - value: hidden
              name: ''
              description: The category is inactive and invisible.
        show_in:
          type: object
          properties:
            app:
              type: boolean
              description: Whether or not to show the category in the Salla Merchant App
            salla_points:
              type: boolean
              description: Whether or not to show the category in Salla Points
          x-apidog-orders:
            - app
            - salla_points
          required:
            - app
            - salla_points
          x-apidog-ignore-properties: []
        has_hidden_products:
          type: boolean
          description: Whether or not the category has hidden products.
        update_at:
          type: string
          description: The date where the category is updated in.
        metadata:
          type: object
          properties:
            title:
              type: string
              description: >-
                Category SEO Metadata Title which is a concise label used to
                optimize search engine results and enhance the visibility of a
                category page.
            description:
              type: string
              description: >-
                A succinct summary crafted to enhance search engine optimization
                and spotlight a brand's attributes within a category.
            url:
              type: string
              description: >-
                Metadata URL is a web address that contains information designed
                to improve a webpage's search engine visibility and shareability
                on social platforms.
          x-apidog-orders:
            - title
            - description
            - url
          required:
            - title
            - description
            - url
          x-apidog-ignore-properties: []
        sub_categories:
          type: array
          items:
            type: string
          description: The subcategories list of the main category.
        translations:
          type: object
          properties:
            en:
              type: object
              properties:
                name:
                  type: string
                  description: Translated category name
                metadata:
                  type: object
                  properties:
                    title:
                      type: string
                      description: >-
                        Translated Category SEO Metadata Title which is a
                        concise label used to optimize search engine results and
                        enhance the visibility of a category page.
                    description:
                      type: string
                      description: >-
                        A succinct summary crafted to enhance search engine
                        optimization and spotlight a brand's attributes within a
                        Translated category.
                    url:
                      type: string
                      description: >-
                        Translated Metadata URL is a web address that contains
                        information designed to improve a webpage's search
                        engine visibility and shareability on social platforms.
                  x-apidog-orders:
                    - title
                    - description
                    - url
                  required:
                    - title
                    - description
                    - url
                  x-apidog-ignore-properties: []
              x-apidog-orders:
                - name
                - metadata
              required:
                - name
                - metadata
              description: Translation in English language.
              x-apidog-ignore-properties: []
          x-apidog-orders:
            - en
          required:
            - en
          description: >-
            **You will get this object in the response if you use
            `with=translations` query parameter.** 


            Category translations are based on the store's enabled language
            locale. For instance, if the store supports both Arabic and English,
            the `translations` object will return two entries: `ar` for Arabic
            and `en` for English.
          x-apidog-ignore-properties: []
        items:
          type: array
          items: *ref_1
          description: >-
            **You will get this array in the response if you use `with=items`
            query parameter.**
      x-apidog-orders:
        - id
        - name
        - image
        - urls
        - parent_id
        - sort_order
        - status
        - show_in
        - has_hidden_products
        - update_at
        - metadata
        - sub_categories
        - translations
        - items
      required:
        - id
        - name
        - image
        - urls
        - parent_id
        - sort_order
        - status
        - show_in
        - has_hidden_products
        - update_at
        - metadata
        - sub_categories
        - translations
        - items
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
              items: *ref_0
            categories:
              type: array
              description: >
                The Categories included in the special offer. Make sure to pass
                the Category IDs in an array. List of Category IDs can be foun
                [here](https://docs.salla.dev/5394207e0) This field is mandatory
                when `buy.type` is set to `category`.
              items: &ref_2
                $ref: '#/components/schemas/Category1'
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
              items: *ref_2
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
    Category1:
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
