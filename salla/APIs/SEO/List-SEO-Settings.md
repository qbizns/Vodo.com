# List SEO Settings

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /seo:
    get:
      summary: List SEO Settings
      deprecated: false
      description: >-
        This endpoint allows you to show your Store's SEO Settings, such as
        Title, Keywords, and Description.


        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `metadata.read`- Metadata Read Only

        </Accordion>
      operationId: get-seo
      tags:
        - Merchant API/APIs/SEO
        - SEO
      parameters: []
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/seo_response_body'
              example:
                status: 200
                success: true
                data:
                  title: Product for Health
                  keywords: Health
                  description: This product is for your health
                  url: https://salla.sa/testweb/sitemap.xml
                  friendly_urls_status: true
                  refersh_sitemap: https://salla.sa/testweb/sitemap/generate/1305146709
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
      x-salla-php-method-name: listSEO
      x-apidog-folder: Merchant API/APIs/SEO
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394262-run
components:
  schemas:
    seo_response_body:
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
          type: object
          properties:
            title:
              type: string
              description: SEO Title. 🌐 [Support multi-language](doc-421122)
              examples:
                - SEO 101
            keywords:
              type: string
              description: SEO Keywords. 🌐 [Support multi-language](doc-421122)
              examples:
                - SEO Marketing
            description:
              type: string
              description: SEO Description. 🌐 [Support multi-language](doc-421122)
              examples:
                - That is SEO Marketing
            url:
              type: string
              description: Sitemap URL
              examples:
                - https://salla.sa/testweb/sitemap.xml
            friendly_urls_status:
              type: boolean
              description: 'Whether or not the SEO enabled for friendly URLS '
              default: true
            refersh_sitemap:
              type: string
              description: Sitemap Refresh URL
              examples:
                - https://salla.sa/testweb/sitemap/generate/1305146709
          x-apidog-orders:
            - title
            - keywords
            - description
            - url
            - friendly_urls_status
            - refersh_sitemap
          x-apidog-ignore-properties: []
      x-apidog-orders:
        - status
        - success
        - data
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
