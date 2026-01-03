# Store Information

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /store/info:
    get:
      summary: Store Information
      deprecated: false
      description: |
        This endpoint allows you to return the Store's detail information.
      operationId: get-store-info
      tags:
        - Merchant API/APIs/Merchant
        - Store
      parameters: []
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                type: object
                x-examples:
                  Example 1:
                    status: 200
                    success: true
                    data:
                      id: 1601633483
                      username: ataba
                      name: العتبة
                      entity: company
                      email: salama@salla.sa
                      mobile: '+966111112121'
                      phone: '00201025557999'
                      avatar: >-
                        https://salla-dev.s3.eu-central-1.amazonaws.com/mKZa/ByziCuUQgAstAtQckVYu6Km4ETu6EAu4pD3mNKKg.png
                      store_location: 30.0778,31.2852
                      plan: pro
                      status: active
                      verified: false
                      currency: SAR
                      domain: >-
                        https://web-e5982bee6d091dbad7d59ff119030e2b.salla.group/ar/ataba
                      about: متجر العتبة هو مجمع لكل ما ترغب فيه
                      created_at: '2021-08-11 12:15:24'
                      default_branch:
                        id: 1846327032
                        name: مركز الجمال
                        status: active
                        location:
                          lat: '30.0778'
                          lng: '31.2852'
                        street: الرحمة
                        address_description: 123 شارع الرحمة
                        additional_number: '6666'
                        building_number: '6666'
                        local: omm
                        postal_code: '66666'
                        contacts:
                          phone: '+966508265874'
                          whatsapp: '+966508265874'
                          telephone: '012526886'
                        preparation_time: '6'
                        is_open: true
                        closest_time: null
                        working_hours:
                          - name: السبت
                            times:
                              - from: '09:00'
                                to: '23:55'
                          - name: الأحد
                            times:
                              - from: '09:00'
                                to: '23:55'
                          - name: الإثنين
                            times:
                              - from: '09:00'
                                to: '23:55'
                          - name: الثلاثاء
                            times:
                              - from: '09:00'
                                to: '23:55'
                          - name: الأربعاء
                            times:
                              - from: '09:00'
                                to: '23:55'
                          - name: الخميس
                            times:
                              - from: '09:00'
                                to: '23:55'
                          - name: الجمعة
                            times:
                              - from: '19:00'
                                to: '23:55'
                        is_cod_available: true
                        is_default: true
                        type: branch
                        cod_cost: '5.00'
                        country:
                          id: 1723506348
                          name: مصر
                          name_en: Egypt
                          code: EG
                          mobile_code: '+20'
                          capital: null
                        city:
                          id: 1355786303
                          name: CAIRO
                          name_en: CAIRO
                          country_id: 1723506348
                      licenses:
                        tax_number: '454364654'
                        commercial_number: null
                        freelance_number: '0000000000'
                      social:
                        website: ''
                        telegram: https://t.me/engsalama
                        twitter: https://twitter.com/SallaApp
                        facebookb: https://facebook.com
                        maroof: https://maroof.sa/
                        youtube: https://www.youtube.com/c/SallaApp
                        snapchat: https://snapchat.com
                        whatsapp: '+201025557999'
                      owner:
                        id: 1649301559
                        name: Username
                        email: demo@user.sa
                        mobile: '+966523185265'
                        created_at: '2021-08-11 12:15:24'
                properties:
                  status:
                    type: integer
                    description: Response Status
                  success:
                    type: boolean
                    description: 'Whether or not the response was successfully returned '
                  data:
                    type: object
                    properties:
                      id:
                        type: number
                        description: Store ID
                      name:
                        type: string
                        description: Store Name
                      entity:
                        type: string
                        description: Store Entity
                        enum:
                          - person
                          - company
                          - charity
                          - firm
                      email:
                        type: string
                        description: Store Email
                      avatar:
                        type: string
                        description: 'Store Avatar '
                      plan:
                        type: string
                        description: 'Store Plan '
                        enum:
                          - basic
                          - plus
                          - pro
                          - special
                        x-stoplight:
                          id: 2ovorzwlmwv4w
                      type:
                        type: string
                        description: Store Type
                        enum:
                          - demo
                          - development
                          - live
                        x-stoplight:
                          id: xymmxl5o554m7
                      status:
                        type: string
                        description: 'Store '
                        enum:
                          - active
                          - inactive
                      verified:
                        type: boolean
                        description: Whether ot not the Store is verified
                      currency:
                        type: string
                        description: Store Currency
                      domain:
                        type: string
                        description: Store Domain Name
                      description:
                        type: string
                        description: Store Description
                      licenses:
                        type: object
                        properties:
                          tax_number:
                            type: string
                            description: 'License '
                            nullable: true
                          commercial_number:
                            description: License Commercial Number
                            type: string
                            nullable: true
                          freelance_number:
                            type: string
                            description: License Freelance Number
                            nullable: true
                        x-apidog-orders:
                          - tax_number
                          - commercial_number
                          - freelance_number
                      social:
                        type: object
                        properties:
                          telegram:
                            type: string
                            description: Store Telegram Account/Username
                            nullable: true
                          twitter:
                            type: string
                            description: Store Twitter Account/Username
                            nullable: true
                          facebook:
                            type: string
                            description: Store Facebook Account/Username
                            nullable: true
                          maroof:
                            type: string
                            description: Store Maroof Account/Username
                            nullable: true
                          youtube:
                            type: string
                            description: Store YouTube Account
                            nullable: true
                          snapchat:
                            type: string
                            description: Store Snapchat Account/Username
                            nullable: true
                          whatsapp:
                            type: string
                            description: Store Whats Account/UsernameNumber
                            nullable: true
                          appstore_link:
                            type: string
                            description: Apple Store Link
                            nullable: true
                          googleplay_link:
                            type: string
                            description: Google Play Link
                            nullable: true
                        x-apidog-orders:
                          - telegram
                          - twitter
                          - facebook
                          - maroof
                          - youtube
                          - snapchat
                          - whatsapp
                          - appstore_link
                          - googleplay_link
                    x-apidog-orders:
                      - id
                      - name
                      - entity
                      - email
                      - avatar
                      - plan
                      - type
                      - status
                      - verified
                      - currency
                      - domain
                      - description
                      - licenses
                      - social
                x-apidog-orders:
                  - status
                  - success
                  - data
              example:
                status: 200
                success: true
                data:
                  id: 1305146709
                  name: dev-wofftr4xsra5xtlv
                  entity: company
                  email: salama@salla.sa
                  avatar: >-
                    https://salla-dev.s3.eu-central-1.amazonaws.com/logo/logo-fashion.jpg
                  plan: pro
                  type: demo
                  status: active
                  verified: false
                  currency: SAR
                  domain: https://salla.sa/dev-wofftr4xsra5xtlv
                  description: متجر تجريبي
                  licenses:
                    tax_number: '65464645654'
                    commercial_number: '8765282634'
                    freelance_number: '42333222'
                  social:
                    telegram: www.telegram.com
                    twitter: https://twitter.com/SallaApp
                    facebook: https://facebook.com
                    maroof: https://maroof.sa/
                    youtube: https://www.youtube.com/c/SallaApp
                    snapchat: https://snapchat.com
                    whatsapp: '+966501806978'
                    appstore_link: https://www.youtube.com/c/SallaApp
                    googleplay_link: https://www.youtube.com/c/SallaApp
          headers: {}
          x-apidog-name: Success
      security:
        - bearer: []
      x-salla-php-method-name: retrieveStoreInfo
      x-salla-php-return-type: StoreInformation
      x-apidog-folder: Merchant API/APIs/Merchant
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394261-run
components:
  schemas: {}
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
