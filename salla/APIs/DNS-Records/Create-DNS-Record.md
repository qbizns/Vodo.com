# Create DNS Record

## OpenAPI Specification

```yaml
openapi: 3.0.1
info:
  title: ''
  description: ''
  version: 1.0.0
paths:
  /dns-records:
    post:
      summary: Create DNS Record
      deprecated: false
      description: >-
        This endpoint allows you to create DNS records such as A, CNAME, MX, and
        TXT records.

        :::info[Information]

        You can manage DNS records for a Salla store via the API using this
        endpoint.

        :::

        <Accordion title="Scopes" defaultOpen={true} icon="lucide-key-round">

        `dns-records.read_write`- DNS Records Read & Write

        </Accordion>
      operationId: post-dns_records
      tags:
        - Merchant API/APIs/DNS Records
        - DNS Records
      parameters: []
      requestBody:
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/DNSRecord_request_body'
            example:
              type: MX
              name: blog
              value: blog.yourwebsite.com
              priority: 0
      responses:
        '200':
          description: ''
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/DNSRecord_response_body'
              example:
                status: 200
                success: true
                data:
                  id: 358857001
                  type: MX
                  name: blog
                  content: blog.yourwebsite.com
                  priority: 0
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
                    dns-records.read_write
          headers: {}
          x-apidog-name: Unauthorized
      security:
        - bearer: []
      x-salla-php-method-name: create
      x-salla-php-return-type: DNS
      x-apidog-folder: Merchant API/APIs/DNS Records
      x-apidog-status: released
      x-run-in-apidog: https://app.apidog.com/web/project/451700/apis/api-5394252-run
components:
  schemas:
    DNSRecord_request_body:
      type: object
      properties:
        type:
          type: string
          enum:
            - A
            - ' AAAA '
            - 'CNAME '
            - 'SPF '
            - 'TXT '
            - MX
          description: 'Indicates the type of DNS record, such as the available enum ones '
        name:
          type: string
          description: Specifies the domain or subdomain to which the DNS record applies.
        value:
          type: string
          description: Refers to the data associated with a specific DNS record.
        priority:
          type: integer
          description: >-
            This is typically used for MX records to specify the order in which
            mail servers should be tried. Required if `type = MX`
      required:
        - type
        - name
        - value
      x-apidog-orders:
        - type
        - name
        - value
        - priority
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    DNSRecord_response_body:
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
          $ref: '#/components/schemas/DNS'
      x-apidog-orders:
        - status
        - success
        - data
      x-apidog-ignore-properties: []
      x-apidog-folder: ''
    DNS:
      type: object
      x-stoplight:
        id: 58075136603b8
      x-examples:
        Example:
          id: 358857001
          type: MX
          name: blog
          content: blog.yourwebsite.com
          priority: 0
      title: DNS
      properties:
        id:
          type: number
          description: A unique identifier for the DNS record within the DNS record.
        type:
          type: string
          description: Indicates the type of DNS record, such as A, CNAME, MX, or TXT.
          enum:
            - A
            - ' AAAA '
            - 'CNAME '
            - 'SPF '
            - 'TXT '
            - MX
          x-apidog-enum:
            - value: A
              name: ''
              description: ' Maps a domain to an IPv4 address.'
            - value: ' AAAA '
              name: ''
              description: ' Maps a domain to an IPv6 address.'
            - value: 'CNAME '
              name: ''
              description: ' Creates an alias for another domain name.'
            - value: 'SPF '
              name: ''
              description: ' Email authentication record to prevent spoofing.'
            - value: 'TXT '
              name: ''
              description: ' Stores arbitrary text'
            - value: MX
              name: ''
              description: ' Specifies mail servers for email delivery.'
        name:
          type: string
          description: Specifies the domain or subdomain to which the DNS record applies.
        content:
          type: string
          description: >-
            Contains the value associated with the DNS record. This could be an
            IP address for an A record, a domain name for a CNAME record, or
            other information depending on the record type.
          x-stoplight:
            id: tmmjthw881wt9
        priority:
          type: integer
          description: >-
            This is typically used for MX records to specify the order in which
            mail servers should be tried. Available if `type = MX`.
      x-tags:
        - Responses
      x-apidog-orders:
        - id
        - type
        - name
        - content
        - priority
      required:
        - id
        - type
        - name
        - content
        - priority
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
