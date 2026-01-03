# List Special Offers

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /specialoffers:
    get:
      summary: List Special Offers
      deprecated: false
      description: >-
        This endpoint allows you to list all special offers related to the
        store.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `specialoffers.read`- Special Offers Read Only

        </Accordion>
      operationId: List-Special-Offers
      tags:
        - Merchant API/APIs/Special Offers
        - Special Offers
      parameters:
        - name: per_page
          in: query
          description: Products limit per page
          required: false
          schema:
            type: integer
        - name: page
          in: query
          description: The Pagination page number
          required: false
          schema:
            type: integer
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/specialOffers_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 613755483
                    name: new free
                    message: >-
                      اشتري one piece واحصل على
                      specialoffer::special_offers.messages.get-discount- من
                      المنتجات التالية
                    expiry_date: null
                    start_date: null
                    offer_type: ''
                    status: active
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: null
                    buy:
                      min_amount: 0
                      min_items: 0
                    get:
                      discount_amount: null
                  - id: 400525076
                    name: new offer
                    message: >-
                      First Offer Congratulations {offer_get_qty}
                      {offer_percent} Try our new offer {offer_buy_qty} and
                      apply the following code: AZ https://shalfa.net/m/?
                    expiry_date: '2024-09-01 01:00:00'
                    start_date: '2024-08-25 16:00:00'
                    offer_type: fixed_amount
                    status: active
                    show_price_after_discount: false
                    show_discounts_table_message: false
                    applied_to: category
                    buy:
                      min_amount: 0
                      min_items: 1
                      categories:
                        - id: 283675303
                          name: ماركة بيوديرما
                          avatar: ''
                          created_at: '2022-03-07T11:57:13.000000Z'
                          updated_at: '2022-04-19T11:25:45.000000Z'
                          deleted_at: null
                          store_id: 4934
                          parent_id: 2062595
                          products_counts: 0
                          show_in_menu: 1
                          sort_order: 1
                          status: active
                          has_hidden_products: 0
                          show_in_app: 1
                          extra_attributes: []
                          custom_url: null
                          mahly_category_id: null
                          images: null
                    get:
                      discount_amount: '1.00'
                  - id: 1259945644
                    name: test
                    message: >-
                      اشتري  -4 pieces واحصل على خصم 1900 SAR من التصنيفات
                      التالية
                    expiry_date: '2025-05-08 00:00:00'
                    start_date: null
                    offer_type: fixed_amount
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: category
                    buy:
                      min_amount: 0
                      min_items: -4
                      categories:
                        - id: 1794053675
                          name: Dress
                          avatar: ''
                          created_at: '2021-09-19T08:17:57.000000Z'
                          updated_at: '2023-12-30T11:41:48.000000Z'
                          deleted_at: null
                          store_id: 4934
                          parent_id: 677747738
                          products_counts: 15
                          show_in_menu: 0
                          sort_order: 5
                          status: active
                          has_hidden_products: 0
                          show_in_app: 1
                          extra_attributes:
                            metadata:
                              title: حلو
                              description: حلو و جميل ههاي ههاي
                          custom_url: order
                          mahly_category_id: null
                          images: >-
                            https://cdn.salla.sa/jKxK/t5kk3PVtwdw3xdYP7XHkaoWQ4punxq3mL1lJ3GvS.jpg
                    get:
                      discount_amount: '1900.00'
                  - id: 619077037
                    name: test 2
                    message: اشتري one piece واحصل على خصم 2 SAR من التصنيفات التالية
                    expiry_date: '2022-08-24 17:45:00'
                    start_date: null
                    offer_type: fixed_amount
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: category
                    buy:
                      min_amount: 0
                      min_items: 1
                      categories:
                        - id: 759876799
                          name: Others
                          avatar: ''
                          created_at: '2021-07-27T10:47:05.000000Z'
                          updated_at: '2022-04-13T14:07:36.000000Z'
                          deleted_at: null
                          store_id: 4934
                          parent_id: 0
                          products_counts: 1
                          show_in_menu: 0
                          sort_order: 37
                          status: active
                          has_hidden_products: 0
                          show_in_app: 1
                          extra_attributes: []
                          custom_url: null
                          mahly_category_id: null
                          images: null
                    get:
                      discount_amount: '2.00'
                  - id: 134547371
                    name: test 3
                    message: اشتري one piece واحصل على خصم 5 SAR من المنتجات التالية
                    expiry_date: '2021-02-28 00:00:00'
                    start_date: null
                    offer_type: fixed_amount
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      min_amount: 10
                      min_items: 0
                      products:
                        - id: 1373482600
                          sku: ''
                          name: cake
                          price: 219
                          currency: SAR
                          description: <p><br></p>
                          options: ''
                          type: food
                          store_id: 4934
                          brand_id: 0
                          quantity: 100
                          status: sale
                          rand_token: ''
                          views: 0
                          created_at: '2021-01-16T20:50:16.000000Z'
                          updated_at: '2022-08-06T16:53:57.000000Z'
                          minimum_order_time: '0'
                          maximum_daily_order: null
                          maximum_quantity_per_order: null
                          allow_attachments: 0
                          pinned: 0
                          pinned_date: '2021-01-16 23:50:16'
                          sale_price: 200
                          sale_end: null
                          source: '24'
                          require_shipping: 1
                          cost_price: 151
                          digital_download_limit: null
                          digital_download_expiry: null
                          from_instagram: 0
                          weight: 0.5
                          with_tax: false
                          hide_quantity: false
                          min_amount_donating: 0
                          max_amount_donating: 0
                          subtitle: null
                          active_advance: 0
                          enable_upload_image: false
                          searchable_at: null
                          promotion_title: null
                          extra_attributes:
                            metadata:
                              title: ''
                              description: ''
                          sort: 18
                          enable_note: false
                          thumbnail: >-
                            https://cdn.salla.sa/eFzBJX8LKYltVrtT7bMc7GqAHCKLd5UCto62Qk7y.jpg
                          thumbnail_alt_seo: ''
                          target_donating_enable: false
                          unlimited_quantity: false
                          managed_by_branches: 1
                          notify_quantity: null
                          show_in_app: false
                          child: 0
                          customized_sku_quantity: 1
                          starting_price: null
                          custom_url: ''
                          calories: null
                          minimum_notify_quantity: 15
                          subscribers_percentage: 100
                          mpn: null
                          gtin: null
                          real_weight: 0.5
                          weight_type: kg
                          show_in_mahly_app: 0
                          mahly_category_id: null
                          spam_status: 0
                          show_in_web: true
                          minimum_quantity_per_order: null
                    get:
                      discount_amount: '5.00'
                  - id: 1569519976
                    name: New life
                    message: >-
                      اشتري one piece واحصل على one piece خصم 20%  من المنتجات
                      التالية
                    expiry_date: '2021-03-31 00:00:00'
                    start_date: null
                    offer_type: buy_x_get_y
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: category
                    buy:
                      type: category
                      quantity: 1
                      categories:
                        '1':
                          id: 205564386
                          name: فواكه
                          avatar: ''
                          created_at: '2018-11-27T12:18:09.000000Z'
                          updated_at: '2022-04-13T14:07:36.000000Z'
                          deleted_at: null
                          store_id: 4934
                          parent_id: 0
                          products_counts: 4
                          show_in_menu: 1
                          sort_order: 39
                          status: active
                          has_hidden_products: 0
                          show_in_app: 1
                          extra_attributes: []
                          custom_url: null
                          mahly_category_id: null
                          images: null
                        '2':
                          id: 1255216786
                          name: فواكه صيفية
                          avatar: ''
                          created_at: '2020-12-26T16:30:42.000000Z'
                          updated_at: '2020-12-27T08:56:15.000000Z'
                          deleted_at: null
                          store_id: 4934
                          parent_id: 30535
                          products_counts: 1
                          show_in_menu: 1
                          sort_order: 1
                          status: active
                          has_hidden_products: 0
                          show_in_app: 1
                          extra_attributes: []
                          custom_url: null
                          mahly_category_id: 454
                          images: null
                    get:
                      type: product
                      discount_type: percentage
                      quantity: '1'
                      products:
                        - id: 1346261513
                          type: product
                          promotion:
                            title: null
                            sub_title: null
                          quantity: null
                          status: sale
                          is_available: true
                          sku: ''
                          name: Kiwi
                          price:
                            amount: 200
                            currency: SAR
                          sale_price:
                            amount: 200
                            currency: SAR
                          currency: SAR
                          url: https://salla.sa/teenzaytoon_est/XeRemw
                          thumbnail: >-
                            https://cdn.salla.sa/sArT8TSGYvmUO7t7D2yt3kg9f7gLMndsyXV330Ss.jpg
                          has_special_price: true
                          regular_price:
                            amount: 219
                            currency: SAR
                          calories: null
                          mpn: null
                          gtin: null
                          description: <p style=";text-align:left;direction:ltr"><br></p>
                          favorite: null
                          features:
                            availability_notify:
                              email: true
                              sms: true
                              mobile: false
                              whatsapp: false
                            show_rating: false
                          starting_price: null
                      discount_amount: '20.00'
                  - id: 1532290140
                    name: One pill and the second for free
                    message: >-
                      اشتري one piece واحصل على one piece مجاناً من المنتجات
                      التالية
                    expiry_date: '2021-09-30 00:00:00'
                    start_date: null
                    offer_type: buy_x_get_y
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      type: product
                      quantity: 1
                      products:
                        - id: 668975484
                          sku: '15662553'
                          name: Green Bag
                          price: 219
                          currency: SAR
                          description: ''
                          options: ''
                          type: product
                          store_id: 4934
                          brand_id: 47510
                          quantity: 85
                          status: sale
                          rand_token: ''
                          views: 0
                          created_at: '2021-09-05T14:43:25.000000Z'
                          updated_at: '2023-08-14T18:50:30.000000Z'
                          minimum_order_time: '0'
                          maximum_daily_order: null
                          maximum_quantity_per_order: 5
                          allow_attachments: 0
                          pinned: 0
                          pinned_date: null
                          sale_price: 200
                          sale_end: null
                          source: '24'
                          require_shipping: 1
                          cost_price: 151
                          digital_download_limit: null
                          digital_download_expiry: null
                          from_instagram: 0
                          weight: 1
                          with_tax: true
                          hide_quantity: false
                          min_amount_donating: 0
                          max_amount_donating: 0
                          subtitle: Green Bag {brand}
                          active_advance: 1
                          enable_upload_image: false
                          searchable_at: null
                          promotion_title: New
                          extra_attributes:
                            metadata:
                              title: ''
                              description: ''
                          sort: 0
                          enable_note: false
                          thumbnail: >-
                            https://cdn.salla.sa/jKxK/JyFkNWZI2OMQKxazlu6UZXxJI9tMmPPn9gBZJN3y.jpg
                          thumbnail_alt_seo: ''
                          target_donating_enable: false
                          unlimited_quantity: false
                          managed_by_branches: 1
                          notify_quantity: 2
                          show_in_app: false
                          child: 0
                          customized_sku_quantity: 1
                          starting_price: 0
                          custom_url: GreenBag
                          calories: null
                          minimum_notify_quantity: 15
                          subscribers_percentage: 100
                          mpn: ''
                          gtin: ''
                          real_weight: 1
                          weight_type: kg
                          show_in_mahly_app: 0
                          mahly_category_id: null
                          spam_status: 0
                          show_in_web: true
                          minimum_quantity_per_order: null
                    get:
                      type: product
                      discount_type: free-product
                      quantity: 1
                      products:
                        - id: 668975484
                          type: product
                          promotion:
                            title: New
                            sub_title: Green Bag {brand}
                          quantity: 85
                          status: sale
                          is_available: true
                          sku: '15662553'
                          name: Green Bag
                          price:
                            amount: 200
                            currency: SAR
                          sale_price:
                            amount: 200
                            currency: SAR
                          currency: SAR
                          url: https://salla.sa/teenzaytoon_est/edNVYd
                          thumbnail: >-
                            https://cdn.salla.sa/jKxK/JyFkNWZI2OMQKxazlu6UZXxJI9tMmPPn9gBZJN3y.jpg
                          has_special_price: true
                          regular_price:
                            amount: 219
                            currency: SAR
                          calories: null
                          mpn: ''
                          gtin: ''
                          description: <p style=";text-align:left;direction:ltr"><br></p>
                          favorite: null
                          features:
                            availability_notify:
                              email: true
                              sms: true
                              mobile: false
                              whatsapp: false
                            show_rating: false
                          starting_price: null
                  - id: 1127073518
                    name: Free phone
                    message: '{offer_percent}'
                    expiry_date: '2021-10-03 00:00:00'
                    start_date: null
                    offer_type: percentage
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: paymentMethod
                    buy:
                      min_amount: 0
                      min_items: 1
                      payment_methods:
                        - id: 1764372897
                          slug: bank
                          name: BankTransfer
                        - id: 40688814
                          slug: tabby_installment
                          name: TabbyInstallment
                        - id: 989286562
                          slug: cod
                          name: COD
                    get:
                      discount_amount: '2.50'
                  - id: 2035393793
                    name: Test Offer
                    message: >-
                      اشتري 5 pieces واحصل على 5 pieces خصم 25%  من المنتجات
                      التالية
                    expiry_date: '2021-12-30 00:00:00'
                    start_date: null
                    offer_type: buy_x_get_y
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      type: product
                      quantity: 5
                      products:
                        - id: 1301156580
                          sku: ''
                          name: Flower
                          price: 219
                          currency: SAR
                          description: >-
                            <p>Flower 1 <span style="color: rgb(89, 89,
                            89);">Flower 1  Flower 1  Flower 1  Flower 1  Flower
                            1 Flower 1 Flower 1 Flower 1 Flower 1 Flower 1
                            Flower 1 Flower 1 </span></p>
                          options: ''
                          type: product
                          store_id: 4934
                          brand_id: 0
                          quantity: null
                          status: sale
                          rand_token: ''
                          views: 0
                          created_at: '2021-11-03T05:15:35.000000Z'
                          updated_at: '2022-07-15T18:40:44.000000Z'
                          minimum_order_time: '0'
                          maximum_daily_order: null
                          maximum_quantity_per_order: null
                          allow_attachments: 0
                          pinned: 0
                          pinned_date: null
                          sale_price: 200
                          sale_end: null
                          source: '24'
                          require_shipping: 1
                          cost_price: 151
                          digital_download_limit: null
                          digital_download_expiry: null
                          from_instagram: 0
                          weight: 1
                          with_tax: true
                          hide_quantity: false
                          min_amount_donating: 0
                          max_amount_donating: 0
                          subtitle: null
                          active_advance: 0
                          enable_upload_image: false
                          searchable_at: null
                          promotion_title: null
                          extra_attributes:
                            metadata:
                              title: null
                              description: null
                          sort: 0
                          enable_note: false
                          thumbnail: >-
                            https://cdn.salla.sa/jKxK/a3vYHOCNWElUAjtaEkhBLgwbGhxJ6pylRV2g1u1A.jpg
                          thumbnail_alt_seo: ''
                          target_donating_enable: false
                          unlimited_quantity: true
                          managed_by_branches: 1
                          notify_quantity: null
                          show_in_app: false
                          child: 1
                          customized_sku_quantity: 1
                          starting_price: null
                          custom_url: null
                          calories: null
                          minimum_notify_quantity: 15
                          subscribers_percentage: 100
                          mpn: null
                          gtin: null
                          real_weight: 1
                          weight_type: kg
                          show_in_mahly_app: 0
                          mahly_category_id: null
                          spam_status: 0
                          show_in_web: true
                          minimum_quantity_per_order: null
                    get:
                      type: product
                      discount_type: percentage
                      quantity: '5'
                      products:
                        - id: 1373482600
                          type: food
                          promotion:
                            title: null
                            sub_title: null
                          quantity: 100
                          status: sale
                          is_available: true
                          sku: ''
                          name: cake
                          price:
                            amount: 200
                            currency: SAR
                          sale_price:
                            amount: 200
                            currency: SAR
                          currency: SAR
                          url: https://salla.sa/teenzaytoon_est/wqxzrW
                          thumbnail: >-
                            https://cdn.salla.sa/eFzBJX8LKYltVrtT7bMc7GqAHCKLd5UCto62Qk7y.jpg
                          has_special_price: true
                          regular_price:
                            amount: 219
                            currency: SAR
                          calories: null
                          mpn: null
                          gtin: null
                          description: <p><br></p>
                          favorite: null
                          features:
                            availability_notify:
                              email: true
                              sms: true
                              mobile: false
                              whatsapp: false
                            show_rating: false
                          starting_price: null
                      discount_amount: '25.00'
                  - id: 1262404610
                    name: second show
                    message: >-
                      اشتري 3 pieces واحصل على one piece مجاناً من المنتجات
                      التالية
                    expiry_date: '2021-12-31 00:00:00'
                    start_date: null
                    offer_type: buy_x_get_y
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      type: product
                      quantity: 3
                      products:
                        - id: 91502809
                          sku: S20-3200
                          name: S20
                          price: 890
                          currency: SAR
                          description: <p>هاتف سامسونج الرائد.</p>
                          options: ''
                          type: product
                          store_id: 4934
                          brand_id: 0
                          quantity: 0
                          status: out
                          rand_token: ''
                          views: 0
                          created_at: '2021-12-12T16:17:33.000000Z'
                          updated_at: '2023-04-02T10:19:34.000000Z'
                          minimum_order_time: '0'
                          maximum_daily_order: null
                          maximum_quantity_per_order: null
                          allow_attachments: 0
                          pinned: 0
                          pinned_date: '2021-12-12 19:17:33'
                          sale_price: 0
                          sale_end: null
                          source: '24'
                          require_shipping: 1
                          cost_price: 200
                          digital_download_limit: null
                          digital_download_expiry: null
                          from_instagram: 0
                          weight: 0.15
                          with_tax: true
                          hide_quantity: false
                          min_amount_donating: 0
                          max_amount_donating: 0
                          subtitle: null
                          active_advance: 1
                          enable_upload_image: false
                          searchable_at: null
                          promotion_title: null
                          extra_attributes:
                            metadata:
                              title: null
                              description: null
                          sort: 3
                          enable_note: false
                          thumbnail: >-
                            https://cdn.salla.sa/jKxK/j2nE0P1Avkgdh4rS45ivjdhkYrNjotHacuIOiEOH.jpg
                          thumbnail_alt_seo: ''
                          target_donating_enable: false
                          unlimited_quantity: false
                          managed_by_branches: 1
                          notify_quantity: null
                          show_in_app: false
                          child: 0
                          customized_sku_quantity: 1
                          starting_price: null
                          custom_url: S20-SEO-P-U
                          calories: null
                          minimum_notify_quantity: 15
                          subscribers_percentage: 100
                          mpn: null
                          gtin: null
                          real_weight: 0.15
                          weight_type: kg
                          show_in_mahly_app: 0
                          mahly_category_id: null
                          spam_status: 0
                          show_in_web: true
                          minimum_quantity_per_order: null
                    get:
                      type: product
                      discount_type: free-product
                      quantity: 1
                      products: []
                  - id: 1305045263
                    name: 1+1
                    message: >-
                      اشتري one piece واحصل على one piece مجاناً من التصنيفات
                      التالية
                    expiry_date: '2022-01-10 00:00:00'
                    start_date: null
                    offer_type: buy_x_get_y
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: category
                    buy:
                      type: category
                      quantity: 1
                      categories:
                        - id: 779444866
                          name: Samsung
                          avatar: ''
                          created_at: '2021-11-04T09:50:53.000000Z'
                          updated_at: '2022-07-06T08:14:04.000000Z'
                          deleted_at: null
                          store_id: 4934
                          parent_id: 0
                          products_counts: 1
                          show_in_menu: 0
                          sort_order: 22
                          status: active
                          has_hidden_products: 1
                          show_in_app: 1
                          extra_attributes: []
                          custom_url: null
                          mahly_category_id: null
                          images: null
                    get:
                      type: category
                      discount_type: free-product
                      quantity: 1
                      categories:
                        - id: 779444866
                          name: Samsung
                          image: null
                          urls:
                            customer: https://salla.sa/teenzaytoon_est/category/wWwmVj
                            admin: https://s.salla.sa/categories
                          parent_id: 0
                          sort_order: 22
                          status: active
                          show_in:
                            app: true
                            salla_points: false
                          has_hidden_products: true
                          update_at: '2022-07-06 11:14:04'
                          metadata:
                            title: null
                            description: null
                            url: null
                  - id: 1400287099
                    name: Arab Complex
                    message: ''
                    expiry_date: '2032-12-25 19:55:00'
                    start_date: null
                    offer_type: discounts_table
                    status: inactive
                    show_price_after_discount: true
                    show_discounts_table_message: false
                    applied_to: order
                    buy: []
                    discounts_table:
                      - quantity: '2'
                        discount_amount: '5'
                      - quantity: '4'
                        discount_amount: '10'
                    source: null
                  - id: 760467012
                    name: complex
                    message: >-
                      اشتري 20 pieces واحصل على 10 pieces خصم 20%  من المنتجات
                      التالية
                    expiry_date: '2022-01-22 00:00:00'
                    start_date: null
                    offer_type: buy_x_get_y
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      type: product
                      quantity: 20
                      products:
                        - id: 1414899111
                          sku: Mon1234
                          name: الموناليزا
                          price: 108
                          currency: SAR
                          description: <p>الموناليزا تيست تيست</p>
                          options: ''
                          type: product
                          store_id: 4934
                          brand_id: 0
                          quantity: 0
                          status: out
                          rand_token: ''
                          views: 0
                          created_at: '2022-01-04T06:51:30.000000Z'
                          updated_at: '2022-12-31T12:45:25.000000Z'
                          minimum_order_time: '0'
                          maximum_daily_order: null
                          maximum_quantity_per_order: null
                          allow_attachments: 0
                          pinned: 0
                          pinned_date: '2022-01-04 09:51:30'
                          sale_price: 0
                          sale_end: null
                          source: '24'
                          require_shipping: 1
                          cost_price: 15001
                          digital_download_limit: null
                          digital_download_expiry: null
                          from_instagram: 0
                          weight: null
                          with_tax: true
                          hide_quantity: false
                          min_amount_donating: 0
                          max_amount_donating: 0
                          subtitle: null
                          active_advance: 0
                          enable_upload_image: false
                          searchable_at: null
                          promotion_title: null
                          extra_attributes:
                            metadata:
                              title: ''
                              description: ''
                          sort: 0
                          enable_note: true
                          thumbnail: >-
                            https://cdn.salla.sa/jKxK/udjbSQVDfMpHqvL6yikocZfeVH6U5I9gZEGatiiW.jpg
                          thumbnail_alt_seo: ''
                          target_donating_enable: false
                          unlimited_quantity: false
                          managed_by_branches: 1
                          notify_quantity: null
                          show_in_app: false
                          child: 1
                          customized_sku_quantity: 1
                          starting_price: null
                          custom_url: ''
                          calories: null
                          minimum_notify_quantity: 15
                          subscribers_percentage: 100
                          mpn: null
                          gtin: null
                          real_weight: null
                          weight_type: kg
                          show_in_mahly_app: 0
                          mahly_category_id: null
                          spam_status: 0
                          show_in_web: true
                          minimum_quantity_per_order: null
                    get:
                      type: product
                      discount_type: percentage
                      quantity: '10'
                      products: []
                      discount_amount: '20.00'
                  - id: 1195638563
                    name: test offer again 5555
                    message: اشتري 3 pieces واحصل على خصم 5 SAR من المنتجات التالية
                    expiry_date: '2022-03-13 00:00:00'
                    start_date: null
                    offer_type: fixed_amount
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      min_amount: 10
                      min_items: 0
                      products: []
                    get:
                      discount_amount: '5.00'
                  - id: 287841836
                    name: test offer again 5555
                    message: اشتري 3 pieces واحصل على خصم 5 SAR من المنتجات التالية
                    expiry_date: '2022-03-13 00:00:00'
                    start_date: null
                    offer_type: fixed_amount
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      min_amount: 10
                      min_items: 0
                      products: []
                    get:
                      discount_amount: '5.00'
                  - id: 1660239149
                    name: test offer again 559999
                    message: اشتري 3 pieces واحصل على خصم 5 SAR من المنتجات التالية
                    expiry_date: '2022-03-13 00:00:00'
                    start_date: null
                    offer_type: fixed_amount
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      min_amount: 10
                      min_items: 0
                      products: []
                    get:
                      discount_amount: '5.00'
                  - id: 1020419118
                    name: test offer again 559999
                    message: >-
                      اشتري one piece واحصل على
                      specialoffer::special_offers.messages.get-discount- من
                      المنتجات التالية
                    expiry_date: null
                    start_date: null
                    offer_type: ''
                    status: inactive
                    show_price_after_discount: null
                    show_discounts_table_message: false
                    applied_to: null
                    buy:
                      min_amount: 0
                      min_items: 0
                    get:
                      discount_amount: null
                  - id: 2112384684
                    name: Abady
                    message: اشتري one piece واحصل على خصم %5 من المنتجات التالية
                    expiry_date: '2022-09-23 23:55:00'
                    start_date: null
                    offer_type: percentage
                    status: inactive
                    show_price_after_discount: false
                    show_discounts_table_message: false
                    applied_to: paymentMethod
                    buy:
                      min_amount: 10
                      min_items: 0
                      payment_methods:
                        - id: 1764372897
                          slug: bank
                          name: BankTransfer
                        - id: 989286562
                          slug: cod
                          name: COD
                    get:
                      discount_amount: '5.00'
                  - id: 693971405
                    name: my love is a Barcelona fan
                    message: ''
                    expiry_date: '2022-06-30 23:55:00'
                    start_date: '2022-06-02 14:40:00'
                    offer_type: discounts_table
                    status: inactive
                    show_price_after_discount: true
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      products:
                        - id: 599153700
                          sku: ''
                          name: Fa
                          price: 25
                          currency: SAR
                          description: >-
                            <p><strong style="color: black;">ماسك الترطيب العميق
                            للشعر من جوسي ايبر 50مل :</strong></p><ul><li>ماسك
                            جوسي ايبر، يساعد على جعل الشعر التالف والجاف
                            والمتطاير أملس وسهل التحكم.</li><li>يحتوي على
                            البروتينات الأساسية التي تساهم باستعادة صحة
                            الشعر.</li><li>محمل بزيت الأرغان من أجل لمعان ناعم
                            كالحرير.</li><li>شاهدي واشعري بالتأثيرات لمدة تصل
                            إلى 10 أيام.</li><li>يساعد على ترميم سريع للشعر لمدة
                            20 دقيقة، سهل الاستخدام في المنزل أو أثناء
                            التنقل.</li><li>مناسب لجميع أنواع
                            الشعر.</li><li>طريقة الاستخدام: اغسلي الشعر وحافظي
                            على رطوبته، افتحي قناع الشعر وافرديه. اسحبي قناع
                            الشعر فوق رأسك وثبتي شعرك بداخل القناع. تأكدي من أن
                            كل الشعر مدسوس داخل القناع – للشعر الطويل، قومي بلفه
                            وثنيه داخل القناع بإحكام. قشري الملصق وافتحي القناع
                            وثبتيه في مكانه، اضبطي القناع حتى يصبح في الموضع
                            المفضل. دلكي القناع على شعرك لمدة 2-3 دقائق. اتركي
                            القناع على شعرك لمدة 20 دقيقة، اشطفي الشعر
                            تماماً.</li><li><strong>SKU:
                            </strong>846146033775</li></ul>
                          options: ''
                          type: product
                          store_id: 4934
                          brand_id: 183096
                          quantity: 49
                          status: sale
                          rand_token: ''
                          views: 0
                          created_at: '2022-05-15T12:36:54.000000Z'
                          updated_at: '2022-10-12T14:30:52.000000Z'
                          minimum_order_time: '0'
                          maximum_daily_order: null
                          maximum_quantity_per_order: null
                          allow_attachments: 0
                          pinned: 0
                          pinned_date: '2022-04-13 17:20:41'
                          sale_price: 0
                          sale_end: null
                          source: '24'
                          require_shipping: 1
                          cost_price: 1
                          digital_download_limit: null
                          digital_download_expiry: null
                          from_instagram: 0
                          weight: 0.2
                          with_tax: true
                          hide_quantity: false
                          min_amount_donating: 0
                          max_amount_donating: 0
                          subtitle: null
                          active_advance: 0
                          enable_upload_image: false
                          searchable_at: null
                          promotion_title: null
                          extra_attributes:
                            metadata:
                              title: ''
                              description: ''
                          sort: 1
                          enable_note: false
                          thumbnail: >-
                            https://cdn.salla.sa/jKxK/8EmG86JQd2rmvHm91AsCGTViWi77eHvzUwMIHHe6.jpg
                          thumbnail_alt_seo: ''
                          target_donating_enable: false
                          unlimited_quantity: false
                          managed_by_branches: 1
                          notify_quantity: 1
                          show_in_app: false
                          child: 0
                          customized_sku_quantity: 1
                          starting_price: null
                          custom_url: ''
                          calories: null
                          minimum_notify_quantity: 1
                          subscribers_percentage: 100
                          mpn: null
                          gtin: null
                          real_weight: 0.2
                          weight_type: kg
                          show_in_mahly_app: 0
                          mahly_category_id: null
                          spam_status: 0
                          show_in_web: true
                          minimum_quantity_per_order: null
                    discounts_table:
                      - quantity: '2'
                        discount_amount: '10'
                      - quantity: '3'
                        discount_amount: '15'
                    source: null
                  - id: 848292565
                    name: Food offer
                    message: Food offer
                    expiry_date: '2022-10-31 23:00:00'
                    start_date: '2022-10-09 22:40:00'
                    offer_type: buy_x_get_y
                    status: inactive
                    show_price_after_discount: false
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      type: product
                      quantity: 1
                      products:
                        - id: 295567604
                          sku: '21212'
                          name: >-
                            مجموعة ادوات تصفيف الشعر من جوسي ايبر وردي زيبرا
                            25ملم
                          price: 100
                          currency: SAR
                          description: >-
                            <p><b style="color:black">مجموعة ادوات تصفيف الشعر
                            من جوسي ايبر وردي زيبرا 25ملم :</b><ul><li>مجموعة
                            جوسي ايبر، مكواة لف الشعر جوسي ايبر مقاس 25 ملم بدون
                            مشابك من مجموعة برو تعيد تعريف مكواة اللف
                            التقليدية.</li><li>فقد تم تصميم مكواة لف الشعر خوسيه
                            إيبر متعددة الاستعمالات مقاس 25 ملم بدون مشابك من
                            مجموعة برو خصيصاً للف الشعر من الجذور إلى الأطراف
                            مما يقلل التلف والأطراف المنثنية ويمنحك لفات أكثر
                            صحة وطبيعية المظهر وتستمر لمدة أطول بدون استخدام
                            بخاخ الشعر أو المنتجات الأخرى.</li><li>صممت مكواة
                            فرد السيراميك الصلبة جوسي ايبر مقاس 1.25 بوصة لأداء
                            ممتاز وراحة مطلقة في الاستخدام وتقدم مكواة فرد
                            السيراميك الخالص خوسيه إيبر نتائج
                            احترافية.</li><li>ألواح السيراميك الخالص مقاس 1.25
                            بوصة لن تنكسر أبداً أو تتقشر أو تتلف حيث تمنع التعرض
                            المضر للحرارة.</li><li>تمنح مكواة فرد جوسي ايبر
                            الصغيرة الخاصة بالسفر جودة أداء الصالون في أصغر مقاس
                            مريح متاح.</li><li>بإمكانك الآن الحصول على شعر ناعم
                            كالحرير ونتائج تصفيف احترافية في أي مكان وفي أي
                            وقت.</li><li>جميع المنتجات الثلاثة في مجموعة الهدية
                            هذه، مزدوجة الفولت (110-240).</li><li>مكواة جوسي
                            ايبر السيراميك المسطحة 1.25 بوصة (220 درجة مئوية /
                            450 درجة فهرنهايت).</li><li>مكواة لف الشعر جوسي ايبر
                            بدون مشابك 25 ملم  (430 درجة
                            فهرنهايت).</li><li>مكواة الشعر الصغيرة 0.5 انش جوسي
                            ايبر.</li><li><b>SKU: </b>846146032358</li></ul></p>
                          options: ''
                          type: product
                          store_id: 4934
                          brand_id: 183096
                          quantity: null
                          status: sale
                          rand_token: ''
                          views: 0
                          created_at: '2022-04-13T14:20:37.000000Z'
                          updated_at: '2022-07-15T18:40:43.000000Z'
                          minimum_order_time: '0'
                          maximum_daily_order: null
                          maximum_quantity_per_order: null
                          allow_attachments: 0
                          pinned: 0
                          pinned_date: '2022-04-13 17:20:37'
                          sale_price: 313.7
                          sale_end: null
                          source: '24'
                          require_shipping: 1
                          cost_price: 1
                          digital_download_limit: null
                          digital_download_expiry: null
                          from_instagram: 0
                          weight: 0.2
                          with_tax: true
                          hide_quantity: false
                          min_amount_donating: 0
                          max_amount_donating: 0
                          subtitle: null
                          active_advance: 0
                          enable_upload_image: false
                          searchable_at: null
                          promotion_title: null
                          extra_attributes:
                            metadata:
                              title: ''
                              description: ''
                          sort: 0
                          enable_note: false
                          thumbnail: >-
                            https://cdn.salla.sa/jKxK/diYOkX9FTWJFjTZc8w22eEzYd4fHyenQmAxE98eO.jpg
                          thumbnail_alt_seo: ''
                          target_donating_enable: false
                          unlimited_quantity: true
                          managed_by_branches: 1
                          notify_quantity: null
                          show_in_app: false
                          child: 0
                          customized_sku_quantity: 1
                          starting_price: null
                          custom_url: null
                          calories: null
                          minimum_notify_quantity: 15
                          subscribers_percentage: 100
                          mpn: null
                          gtin: null
                          real_weight: 0.2
                          weight_type: kg
                          show_in_mahly_app: 0
                          mahly_category_id: null
                          spam_status: 0
                          show_in_web: true
                          minimum_quantity_per_order: null
                    get:
                      type: product
                      discount_type: free-product
                      quantity: 1
                      products: []
                  - id: 1181257641
                    name: test
                    message: >-
                      اشتري one piece واحصل على one piece خصم 10%  من المنتجات
                      التالية
                    expiry_date: null
                    start_date: null
                    offer_type: buy_x_get_y
                    status: inactive
                    show_price_after_discount: false
                    show_discounts_table_message: false
                    applied_to: product
                    buy:
                      type: product
                      quantity: 1
                      products:
                        - id: 1091347324
                          sku: TESTgrP
                          name: test
                          price: 12
                          currency: SAR
                          description: ''
                          options: ''
                          type: group_products
                          store_id: 4934
                          brand_id: 0
                          quantity: null
                          status: sale
                          rand_token: ''
                          views: 0
                          created_at: '2023-03-05T13:19:58.000000Z'
                          updated_at: '2024-01-02T09:18:52.000000Z'
                          minimum_order_time: '0'
                          maximum_daily_order: null
                          maximum_quantity_per_order: null
                          allow_attachments: 0
                          pinned: 0
                          pinned_date: '2023-03-05 16:19:58'
                          sale_price: null
                          sale_end: null
                          source: null
                          require_shipping: 1
                          cost_price: 123
                          digital_download_limit: null
                          digital_download_expiry: null
                          from_instagram: 0
                          weight: 1
                          with_tax: true
                          hide_quantity: false
                          min_amount_donating: 0
                          max_amount_donating: 0
                          subtitle: null
                          active_advance: 0
                          enable_upload_image: false
                          searchable_at: null
                          promotion_title: null
                          extra_attributes:
                            metadata:
                              title: ''
                              description: ''
                          sort: 0
                          enable_note: false
                          thumbnail: >-
                            https://cdn.salla.sa/jKxK/bceSK4ZiNoQ3v9mEcExFaJIe721Q2xxSSMTnHFtV.png
                          thumbnail_alt_seo: ''
                          target_donating_enable: false
                          unlimited_quantity: true
                          managed_by_branches: 1
                          notify_quantity: null
                          show_in_app: false
                          child: 0
                          customized_sku_quantity: 0
                          starting_price: null
                          custom_url: ''
                          calories: null
                          minimum_notify_quantity: 15
                          subscribers_percentage: 100
                          mpn: ''
                          gtin: ''
                          real_weight: 1
                          weight_type: kg
                          show_in_mahly_app: 0
                          mahly_category_id: null
                          spam_status: 1
                          show_in_web: true
                          minimum_quantity_per_order: null
                    get:
                      type: product
                      discount_type: percentage
                      quantity: '1'
                      products:
                        - id: 1189362533
                          type: product
                          promotion:
                            title: null
                            sub_title: null
                          quantity: 16
                          status: sale
                          is_available: true
                          sku: ''
                          name: qoyod_test_options
                          price:
                            amount: 118
                            currency: SAR
                          sale_price:
                            amount: 0
                            currency: SAR
                          currency: SAR
                          url: https://salla.sa/teenzaytoon_est/RRQVBP
                          thumbnail: >-
                            https://cdn.salla.sa/jKxK/5KMQMUfLpIlWQcnM2NIRnk15O6A7RERwobdNjvvC.png
                          has_special_price: false
                          regular_price:
                            amount: 118
                            currency: SAR
                          calories: null
                          mpn: null
                          gtin: null
                          description: <p></p>
                          favorite: null
                          features:
                            availability_notify:
                              email: true
                              sms: true
                              mobile: false
                              whatsapp: false
                            show_rating: false
                          starting_price:
                            amount: 118
                            currency: SAR
                        - id: 1751601075
                          type: product
                          promotion:
                            title: null
                            sub_title: null
                          quantity: 5
                          status: sale
                          is_available: true
                          sku: IPHONE-X
                          name: new_test
                          price:
                            amount: 10
                            currency: SAR
                          sale_price:
                            amount: 0
                            currency: SAR
                          currency: SAR
                          url: https://salla.sa/teenzaytoon_est/RADWGQv
                          thumbnail: >-
                            https://cdn.salla.sa/jKxK/VMsAeQB3eW4j4MQ40GVx0pIBj2YpnDkgsx6x90TJ.jpg
                          has_special_price: false
                          regular_price:
                            amount: 10
                            currency: SAR
                          calories: null
                          mpn: ''
                          gtin: ''
                          description: >-
                            <p>السلام عليكم ورحمة الله
                            وبركاته</p><p><br></p><p>مساء الخير جميعاً</p>
                          favorite: null
                          features:
                            availability_notify:
                              email: true
                              sms: true
                              mobile: false
                              whatsapp: false
                            show_rating: false
                          starting_price: null
                      discount_amount: '10.00'
                  - id: 1171114975
                    name: alaa
                    message: اشتري 3 pieces واحصل على خصم 30 SAR من التصنيفات التالية
                    expiry_date: null
                    start_date: null
                    offer_type: fixed_amount
                    status: active
                    show_price_after_discount: false
                    show_discounts_table_message: false
                    applied_to: category
                    buy:
                      min_amount: 0
                      min_items: 3
                      categories:
                        - id: 1794053675
                          name: Dress
                          avatar: ''
                          created_at: '2021-09-19T08:17:57.000000Z'
                          updated_at: '2023-12-30T11:41:48.000000Z'
                          deleted_at: null
                          store_id: 4934
                          parent_id: 677747738
                          products_counts: 15
                          show_in_menu: 0
                          sort_order: 5
                          status: active
                          has_hidden_products: 0
                          show_in_app: 1
                          extra_attributes:
                            metadata:
                              title: حلو
                              description: حلو و جميل ههاي ههاي
                          custom_url: order
                          mahly_category_id: null
                          images: >-
                            https://cdn.salla.sa/jKxK/t5kk3PVtwdw3xdYP7XHkaoWQ4punxq3mL1lJ3GvS.jpg
                    get:
                      discount_amount: '30.00'
                  - id: 397077208
                    name: مشعل 2
                    message: اشتري 3 pieces واحصل على خصم 40 SAR من التصنيفات التالية
                    expiry_date: null
                    start_date: null
                    offer_type: fixed_amount
                    status: inactive
                    show_price_after_discount: false
                    show_discounts_table_message: false
                    applied_to: category
                    buy:
                      min_amount: 0
                      min_items: 3
                      categories:
                        - id: 1798176427
                          name: سلاسة
                          avatar: ''
                          created_at: '2022-03-07T11:57:28.000000Z'
                          updated_at: '2024-02-13T10:19:32.000000Z'
                          deleted_at: null
                          store_id: 4934
                          parent_id: 0
                          products_counts: 0
                          show_in_menu: 1
                          sort_order: 3
                          status: active
                          has_hidden_products: 0
                          show_in_app: 1
                          extra_attributes: []
                          custom_url: null
                          mahly_category_id: null
                          images: >-
                            https://cdn.salla.sa/jKxK/ZLA3dxb6uub2pEpxkQPFdA4faDUdRmTySYuDf2ZD.png
                    get:
                      discount_amount: '40.00'
                  - id: 261743038
                    name: عرض خاص 50٪
                    message: >-
                      اشتري 100 pieces واحصل على one piece مجاناً من المنتجات
                      التالية
                    expiry_date: '2024-10-10 10:35:00'
                    start_date: '2024-01-19 14:20:00'
                    offer_type: fixed_amount
                    status: inactive
                    show_price_after_discount: false
                    show_discounts_table_message: false
                    applied_to: order
                    buy:
                      min_amount: 0
                      min_items: 100
                    get:
                      discount_amount: '0.00'
                  - id: 893331800
                    name: خصم ٢٨
                    message: اشتري one piece واحصل على خصم 28 SAR من المنتجات التالية
                    expiry_date: null
                    start_date: null
                    offer_type: fixed_amount
                    status: active
                    show_price_after_discount: false
                    show_discounts_table_message: false
                    applied_to: order
                    buy:
                      min_amount: 0
                      min_items: 0
                    get:
                      discount_amount: '28.00'
                pagination:
                  count: 25
                  total: 25
                  perPage: 999999999999
                  currentPage: 1
                  totalPages: 1
                  links: {}
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
      security:
        - bearer: []
      x-salla-php-method-name: list
      x-salla-php-return-type: SpecialOffer
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Special Offers
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394218-run
components:
  schemas:
    specialOffers_response_body:
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
            id: diphxrhvmz2sa
          items:
            $ref: '#/components/schemas/SpecialOffer'
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
