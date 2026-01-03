# List Available Currencies

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /currencies/available:
    get:
      summary: List Available Currencies
      deprecated: false
      description: >-
        This endpoint allows you to fetch a list of available currencies
        alongside their details, such as `name`, `code`, `symbol` and `status`.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `metadata.read`- Metadata Read Only

        </Accordion>
      operationId: get-currencies-available
      tags:
        - Merchant API/APIs/Currencies
        - Currencies
      parameters: []
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/availableCurrencies_response_body'
              example:
                status: 200
                success: true
                data:
                  - id: 1745409636
                    country_name_en: Saudi Arabia
                    country_name_ar: السعودية
                    country_code: SA
                    currency_code: SAR
                    currency_name_en: Riyals
                    currency_name_ar: ريال سعودي
                  - id: 566146469
                    country_name_en: United Arab Emirates
                    country_name_ar: الامارات
                    country_code: AE
                    currency_code: AED
                    currency_name_en: ''
                    currency_name_ar: درهم اماراتي
                  - id: 1914793182
                    country_name_en: Kuwait
                    country_name_ar: الكويت
                    country_code: KW
                    currency_code: KWD
                    currency_name_en: Dinar
                    currency_name_ar: دينار كويتي
                  - id: 82390046
                    country_name_en: Qatar
                    country_name_ar: قطر
                    country_code: QA
                    currency_code: QAR
                    currency_name_en: Rials
                    currency_name_ar: ريال قطري
                  - id: 1688289458
                    country_name_en: Bahrain
                    country_name_ar: البحرين
                    country_code: BH
                    currency_code: BHD
                    currency_name_en: Dinar
                    currency_name_ar: دينار بحريني
                  - id: 1350008014
                    country_name_en: Iraq
                    country_name_ar: العراق
                    country_code: IQ
                    currency_code: IQD
                    currency_name_en: Dinar
                    currency_name_ar: دينار عراقي
                  - id: 889477903
                    country_name_en: Oman
                    country_name_ar: عمان
                    country_code: OM
                    currency_code: OMR
                    currency_name_en: Rials
                    currency_name_ar: ريال عماني
                  - id: 1587042020
                    country_name_en: Egypt
                    country_name_ar: مصر
                    country_code: EG
                    currency_code: EGP
                    currency_name_en: Pounds
                    currency_name_ar: جنيه مصري
                  - id: 1637823335
                    country_name_en: Sudan
                    country_name_ar: السودان
                    country_code: SD
                    currency_code: SDG
                    currency_name_en: Pounds
                    currency_name_ar: جنيه سوداني
                  - id: 642580259
                    country_name_en: Libya
                    country_name_ar: ليبيا
                    country_code: LY
                    currency_code: LYD
                    currency_name_en: Dinar
                    currency_name_ar: دينار ليبي
                  - id: 1627982233
                    country_name_en: Algeria
                    country_name_ar: الجزائر
                    country_code: DZ
                    currency_code: DZD
                    currency_name_en: Dinar
                    currency_name_ar: دينار جزائري
                  - id: 195201146
                    country_name_en: Tunisia
                    country_name_ar: تونس
                    country_code: TN
                    currency_code: TND
                    currency_name_en: Dinar
                    currency_name_ar: دينار تونسي
                  - id: 1881742892
                    country_name_en: Morocco
                    country_name_ar: المغرب
                    country_code: MA
                    currency_code: MAD
                    currency_name_en: Dirham
                    currency_name_ar: درهم مغربي
                  - id: 55124855
                    country_name_en: Syria
                    country_name_ar: سوريا
                    country_code: SY
                    currency_code: SYP
                    currency_name_en: Pounds
                    currency_name_ar: ليرة سورية
                  - id: 1168042202
                    country_name_en: Lebanon
                    country_name_ar: لبنان
                    country_code: LB
                    currency_code: LBP
                    currency_name_en: Pounds
                    currency_name_ar: ليرة لبنانية
                  - id: 773200552
                    country_name_en: Australia
                    country_name_ar: استراليا
                    country_code: AU
                    currency_code: AUD
                    currency_name_en: Dollars
                    currency_name_ar: دولار استرالي
                  - id: 1337421468
                    country_name_en: Germany
                    country_name_ar: المانيا
                    country_code: DE
                    currency_code: EUR
                    currency_name_en: Euro
                    currency_name_ar: يورو
                  - id: 1834070720
                    country_name_en: Indonesia
                    country_name_ar: اندونيسيا
                    country_code: ID
                    currency_code: IDR
                    currency_name_en: Rupiahs
                    currency_name_ar: روبية إندونيسية
                  - id: 1201022676
                    country_name_en: Jordan
                    country_name_ar: الأردن
                    country_code: JO
                    currency_code: JOD
                    currency_name_en: Dinar
                    currency_name_ar: دينار أردني
                  - id: 852895898
                    country_name_en: Ecuador
                    country_name_ar: الإكوادور
                    country_code: EC
                    currency_code: USD
                    currency_name_en: Dollars
                    currency_name_ar: دولار أمريكي
                  - id: 862212704
                    country_name_en: Sweden
                    country_name_ar: السويد
                    country_code: SE
                    currency_code: SEK
                    currency_name_en: Kronor
                    currency_name_ar: كرونة سويدية
                  - id: 1661028235
                    country_name_en: China
                    country_name_ar: الصين
                    country_code: CN
                    currency_code: CNY
                    currency_name_en: Yuan Renminbi
                    currency_name_ar: رنمينبي
                  - id: 819911400
                    country_name_en: United Kingdom of Great Britain and Northern Ireland
                    country_name_ar: بريطانيا
                    country_code: GB
                    currency_code: GBP
                    currency_name_en: Pounds
                    currency_name_ar: جنيه استرليني
                  - id: 885866188
                    country_name_en: India
                    country_name_ar: الهند
                    country_code: IN
                    currency_code: INR
                    currency_name_en: Rupees
                    currency_name_ar: روبية هندية
                  - id: 292767189
                    country_name_en: Japan
                    country_name_ar: اليابان
                    country_code: JP
                    currency_code: JPY
                    currency_name_en: Yen
                    currency_name_ar: ين ياباني
                  - id: 406521109
                    country_name_en: Pakistan
                    country_name_ar: باكستان
                    country_code: PK
                    currency_code: PKR
                    currency_name_en: Rupees
                    currency_name_ar: روبية باكستانية
                  - id: 928298564
                    country_name_en: Turkey
                    country_name_ar: تركيا
                    country_code: TR
                    currency_code: TRY
                    currency_name_en: Lira
                    currency_name_ar: ليرة تركية
                  - id: 880152961
                    country_name_en: Canada
                    country_name_ar: كندا
                    country_code: CA
                    currency_code: CAD
                    currency_name_en: Dollars
                    currency_name_ar: دولار كندي
                  - id: 924690745
                    country_name_en: Malaysia
                    country_name_ar: ماليزيا
                    country_code: MY
                    currency_code: MYR
                    currency_name_en: Ringgits
                    currency_name_ar: رينغيت ماليزي
                  - id: 1980874802
                    country_name_en: Mauritania
                    country_name_ar: موريتانيا
                    country_code: MR
                    currency_code: MRO
                    currency_name_en: Mauritanian ouguiya
                    currency_name_ar: أوقية موريتانية
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
                    metadata.read
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: listAvailable
      x-salla-php-return-type: AvailableCurrencies
      x-salla-php-return-base-type: array
      x-apidog-folder: Merchant API/APIs/Currencies
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394258-run
components:
  schemas:
    availableCurrencies_response_body:
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
            id: 2h74zygb0aiay
          items:
            $ref: '#/components/schemas/AvailableCurrencies%20'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    'AvailableCurrencies ':
      title: AvailableCurrencies
      type: object
      properties:
        id:
          type: number
          description: >-
            A unique code that identifies a specific currency. List of available
            currencies can be found [here](https://docs.salla.dev/api-5394258).
          examples:
            - 1745409636
        country_name_en:
          type: string
          description: Country name in English
          examples:
            - Saudi Arabia
        country_name_ar:
          type: string
          description: Country name in Arabic
          examples:
            - السعودية
        country_code:
          type: string
          description: >-
            A short alphanumeric identification code for countries and dependent
            areas.
          examples:
            - SA
        currency_code:
          type: string
          description: >-
            International Organization for Standardization (ISO) is a unique
            three-letter alphabetic code that identifies a specific currency
          examples:
            - SAR
        currency_name_en:
          type: string
          description: Currency name in English
          examples:
            - Riyals
          nullable: true
        currency_name_ar:
          type: string
          description: Currency name in Arabic
          examples:
            - ريال سعودي
      x-apidog-orders:
        - id
        - country_name_en
        - country_name_ar
        - country_code
        - currency_code
        - currency_name_en
        - currency_name_ar
      required:
        - id
        - country_name_en
        - country_name_ar
        - country_code
        - currency_code
        - currency_name_en
        - currency_name_ar
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
