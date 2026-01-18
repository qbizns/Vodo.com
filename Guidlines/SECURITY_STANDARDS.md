# Plugin System - Security Standards

## Document Version
- **Version**: 1.0.0
- **Last Updated**: December 2024
- **Scope**: Security Requirements for 30 Plugin Modules

---

## Table of Contents

1. [Security Principles](#1-security-principles)
2. [Authentication](#2-authentication)
3. [Authorization](#3-authorization)
4. [Input Validation](#4-input-validation)
5. [Output Encoding](#5-output-encoding)
6. [SQL Injection Prevention](#6-sql-injection-prevention)
7. [XSS Prevention](#7-xss-prevention)
8. [CSRF Protection](#8-csrf-protection)
9. [File Upload Security](#9-file-upload-security)
10. [API Security](#10-api-security)
11. [Sensitive Data Handling](#11-sensitive-data-handling)
12. [Logging & Monitoring](#12-logging--monitoring)
13. [Security Checklist](#13-security-checklist)

---

## 1. Security Principles

### 1.1 Core Principles

```
1. DEFENSE IN DEPTH: Multiple layers of security
2. LEAST PRIVILEGE: Minimum necessary permissions
3. FAIL SECURE: Deny access on errors
4. INPUT VALIDATION: Never trust user input
5. OUTPUT ENCODING: Escape all output
6. SECURE DEFAULTS: Security enabled by default
7. AUDIT EVERYTHING: Log security-relevant actions
```

### 1.2 Trust Boundaries

```
┌─────────────────────────────────────────────────────────────────┐
│                        UNTRUSTED ZONE                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Browser    │  │   Mobile     │  │   External   │          │
│  │   Client     │  │    App       │  │    APIs      │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
└─────────┼─────────────────┼─────────────────┼───────────────────┘
          │                 │                 │
          ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                     VALIDATION BOUNDARY                          │
│         Input Validation │ Sanitization │ Rate Limiting          │
└─────────────────────────────────────────────────────────────────┘
          │                 │                 │
          ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                     AUTHENTICATION BOUNDARY                      │
│              Session │ JWT │ API Key │ OAuth                    │
└─────────────────────────────────────────────────────────────────┘
          │                 │                 │
          ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                     AUTHORIZATION BOUNDARY                       │
│           Permissions │ Roles │ Policies │ Tenant Scope         │
└─────────────────────────────────────────────────────────────────┘
          │                 │                 │
          ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                        TRUSTED ZONE                              │
│              Services │ Models │ Database │ Cache               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Authentication

### 2.1 Password Requirements

```php
// config/auth.php or validation rules
'password' => [
    'min_length' => 12,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_special' => true,
    'max_age_days' => 90,
    'history_count' => 5, // Prevent reuse of last 5 passwords
];

// Validation rule
use Illuminate\Validation\Rules\Password;

'password' => [
    'required',
    'confirmed',
    Password::min(12)
        ->mixedCase()
        ->numbers()
        ->symbols()
        ->uncompromised(), // Check against breached passwords
],
```

### 2.2 Session Security

```php
// config/session.php
return [
    'driver' => 'database', // Not 'file' in production
    'lifetime' => 120,       // 2 hours
    'expire_on_close' => false,
    'encrypt' => true,
    'cookie' => 'platform_session',
    'secure' => true,        // HTTPS only
    'http_only' => true,     // No JavaScript access
    'same_site' => 'lax',    // CSRF protection
];

// Regenerate session on login
public function login(Request $request): Response
{
    // ... authenticate
    
    $request->session()->regenerate();
    
    // Log login event
    AuditLog::log('user.login', $user);
    
    return redirect()->intended('dashboard');
}

// Invalidate all sessions on password change
public function changePassword(Request $request): Response
{
    // ... change password
    
    // Invalidate all other sessions
    Auth::logoutOtherDevices($request->password);
    
    return redirect()->back()->with('success', 'Password changed');
}
```

### 2.3 Multi-Factor Authentication

```php
// MFA verification middleware
class VerifyMfaMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if ($user->mfa_enabled && !session('mfa_verified')) {
            return redirect()->route('mfa.verify');
        }
        
        return $next($request);
    }
}

// MFA verification
public function verifyMfa(Request $request): Response
{
    $request->validate([
        'code' => ['required', 'string', 'size:6'],
    ]);
    
    $user = $request->user();
    
    if (!$this->mfaService->verify($user, $request->code)) {
        // Rate limit failed attempts
        RateLimiter::hit('mfa:' . $user->id, 300);
        
        if (RateLimiter::tooManyAttempts('mfa:' . $user->id, 5)) {
            $user->lockAccount();
            throw new TooManyMfaAttemptsException();
        }
        
        return back()->withErrors(['code' => 'Invalid verification code']);
    }
    
    session(['mfa_verified' => true]);
    RateLimiter::clear('mfa:' . $user->id);
    
    return redirect()->intended('dashboard');
}
```

### 2.4 Login Throttling

```php
// Rate limiting for login attempts
class LoginController extends Controller
{
    protected function attemptLogin(Request $request): bool
    {
        $key = 'login:' . $request->ip() . ':' . $request->email;
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Try again in {$seconds} seconds."],
            ]);
        }
        
        if (Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::clear($key);
            return true;
        }
        
        RateLimiter::hit($key, 300); // 5 minutes
        return false;
    }
}
```

---

## 3. Authorization

### 3.1 Permission Model

```php
// Permission slug format: {module}.{resource}.{action}
// Examples:
'orders.view'           // View orders list
'orders.create'         // Create new orders
'orders.update'         // Update any order
'orders.update.own'     // Update own orders only
'orders.delete'         // Delete orders
'orders.*'              // All order permissions

// Check permission
if ($user->hasPermission('orders.create')) {
    // ...
}

// Check multiple
if ($user->hasAnyPermission(['orders.create', 'orders.update'])) {
    // ...
}

if ($user->hasAllPermissions(['orders.create', 'orders.update'])) {
    // ...
}
```

### 3.2 Policy Classes

```php
<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admins bypass all checks
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        return null; // Continue to specific checks
    }

    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('orders.view');
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Check permission
        if (!$user->hasPermission('orders.view')) {
            return false;
        }
        
        // Check tenant scope
        if ($order->tenant_id !== $user->tenant_id) {
            return false;
        }
        
        // Check ownership for restricted permission
        if ($user->hasPermission('orders.view.own')) {
            return $order->user_id === $user->id;
        }
        
        return true;
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Cannot update completed/cancelled orders
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return false;
        }
        
        // Check permission and scope
        if ($user->hasPermission('orders.update')) {
            return $order->tenant_id === $user->tenant_id;
        }
        
        if ($user->hasPermission('orders.update.own')) {
            return $order->user_id === $user->id;
        }
        
        return false;
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // Cannot delete completed orders
        if ($order->status === 'completed') {
            return false;
        }
        
        return $user->hasPermission('orders.delete') 
            && $order->tenant_id === $user->tenant_id;
    }
}
```

### 3.3 Authorization in Controllers

```php
// Using Form Requests
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('orders.create');
    }
}

// Using authorize() in controller
public function show(int $id): View
{
    $order = Order::findOrFail($id);
    
    $this->authorize('view', $order);
    
    return view('orders.show', compact('order'));
}

// Using middleware
Route::get('/orders/{id}', [OrderController::class, 'show'])
    ->middleware('permission:orders.view');

// Using Gate directly
if (Gate::denies('update', $order)) {
    abort(403, 'You cannot update this order.');
}
```

### 3.4 Tenant Isolation

```php
// Global scope for tenant isolation
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($tenantId = tenant()?->id) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}

// CRITICAL: Always verify tenant scope in sensitive operations
public function update(int $id, array $data): Order
{
    $order = Order::findOrFail($id);
    
    // Double-check tenant scope (defense in depth)
    if ($order->tenant_id !== tenant()->id) {
        throw new AuthorizationException('Access denied');
    }
    
    $order->update($data);
    return $order;
}
```

---

## 4. Input Validation

### 4.1 Validation Rules

```php
// Always validate ALL input
public function rules(): array
{
    return [
        // Required string with length limits
        'name' => ['required', 'string', 'min:2', 'max:255'],
        
        // Email validation
        'email' => ['required', 'email:rfc,dns', 'max:255'],
        
        // Integer with range
        'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        
        // Decimal with precision
        'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        
        // Enum/Status validation
        'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
        
        // Date validation
        'start_date' => ['required', 'date', 'after:today'],
        'end_date' => ['required', 'date', 'after:start_date'],
        
        // URL validation
        'website' => ['nullable', 'url', 'max:2048'],
        
        // File validation
        'document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        
        // Array validation
        'tags' => ['nullable', 'array', 'max:10'],
        'tags.*' => ['integer', 'exists:tags,id'],
        
        // JSON validation
        'settings' => ['nullable', 'json'],
        
        // Unique with tenant scope
        'slug' => [
            'required',
            'string',
            'max:100',
            Rule::unique('products', 'slug')
                ->where('tenant_id', tenant()->id)
                ->ignore($this->route('id')),
        ],
    ];
}
```

### 4.2 Custom Validation Rules

```php
// Prevent path traversal
Validator::extend('safe_path', function ($attribute, $value, $parameters) {
    return !preg_match('/\.\.|[\/\\\\]/', $value);
});

// Prevent XSS in HTML fields
Validator::extend('safe_html', function ($attribute, $value, $parameters) {
    $allowed = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a'];
    $cleaned = strip_tags($value, '<' . implode('><', $allowed) . '>');
    return $value === $cleaned;
});

// Validate slug format
Validator::extend('slug', function ($attribute, $value, $parameters) {
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value);
});

// Usage
'path' => ['required', 'string', 'safe_path'],
'content' => ['nullable', 'string', 'safe_html'],
'slug' => ['required', 'string', 'slug'],
```

### 4.3 Sanitization

```php
// Input sanitization middleware
class InputSanitizationMiddleware
{
    protected array $except = [
        'password',
        'password_confirmation',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value, $key) {
            if (!in_array($key, $this->except) && is_string($value)) {
                // Trim whitespace
                $value = trim($value);
                
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                
                // Normalize line endings
                $value = str_replace("\r\n", "\n", $value);
            }
        });
        
        $request->merge($input);
        
        return $next($request);
    }
}
```

---

## 5. Output Encoding

### 5.1 Blade Escaping

```blade
{{-- ✅ SAFE: Auto-escaped --}}
{{ $user->name }}
{{ $product->description }}

{{-- ⚠️ DANGEROUS: Unescaped - only use for TRUSTED HTML --}}
{!! $trustedHtml !!}

{{-- ✅ SAFE: Escape URLs --}}
<a href="{{ url($path) }}">Link</a>
<img src="{{ asset($image) }}">

{{-- ✅ SAFE: Escape attributes --}}
<input value="{{ old('name', $default) }}">

{{-- ✅ SAFE: JSON in JavaScript --}}
<script>
    var data = @json($data);
    var config = @json($config, JSON_HEX_TAG | JSON_HEX_APOS);
</script>

{{-- ⚠️ CAREFUL: Inline event handlers (avoid if possible) --}}
<button onclick="handleClick('{{ e($value) }}')">Click</button>

{{-- ✅ BETTER: Use data attributes --}}
<button data-value="{{ $value }}" class="js-action">Click</button>
```

### 5.2 API Response Encoding

```php
// JSON responses are automatically encoded
return response()->json([
    'name' => $user->name,  // Properly encoded in JSON
    'html' => '<script>alert("xss")</script>',  // Encoded as string
]);

// For HTML in API responses
return response()->json([
    'html' => e($userInput),  // Explicitly escape
]);
```

---

## 6. SQL Injection Prevention

### 6.1 Safe Query Patterns

```php
// ✅ SAFE: Eloquent with parameter binding
User::where('email', $email)->first();
User::where('status', '=', $status)->get();

// ✅ SAFE: Query builder with bindings
DB::table('users')
    ->where('email', $email)
    ->where('status', $status)
    ->first();

// ✅ SAFE: whereIn with array
$ids = $request->input('ids', []);
User::whereIn('id', $ids)->get();

// ✅ SAFE: Raw with bindings
DB::select('SELECT * FROM users WHERE email = ?', [$email]);
DB::select('SELECT * FROM users WHERE id IN (:ids)', ['ids' => implode(',', $ids)]);

// ❌ DANGEROUS: String concatenation
DB::select("SELECT * FROM users WHERE email = '$email'");
User::whereRaw("email = '$email'")->first();

// ❌ DANGEROUS: Unvalidated column names
$column = $request->input('sort');
User::orderBy($column)->get();  // Attacker can inject SQL

// ✅ SAFE: Whitelist column names
$allowed = ['name', 'email', 'created_at'];
$column = in_array($request->input('sort'), $allowed) 
    ? $request->input('sort') 
    : 'created_at';
User::orderBy($column)->get();
```

### 6.2 Safe Dynamic Queries

```php
// Safe dynamic where clauses
public function search(array $filters): Builder
{
    $query = Product::query();
    
    // Safe: validated input in where
    if (!empty($filters['category_id'])) {
        $query->where('category_id', (int) $filters['category_id']);
    }
    
    // Safe: LIKE with escaped wildcards
    if (!empty($filters['name'])) {
        $name = addcslashes($filters['name'], '%_');
        $query->where('name', 'LIKE', "%{$name}%");
    }
    
    // Safe: whitelisted sort column
    $sortColumn = in_array($filters['sort'] ?? '', ['name', 'price', 'created_at'])
        ? $filters['sort']
        : 'created_at';
    
    $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
    
    $query->orderBy($sortColumn, $sortDir);
    
    return $query;
}
```

---

## 7. XSS Prevention

### 7.1 Content Security Policy

```php
// Middleware to add CSP headers
class ContentSecurityPolicyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' cdn.tailwindcss.com code.jquery.com",
            "style-src 'self' 'unsafe-inline' cdn.tailwindcss.com",
            "img-src 'self' data: https:",
            "font-src 'self' fonts.gstatic.com",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        
        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        return $response;
    }
}
```

### 7.2 Safe JavaScript Patterns

```javascript
// ✅ SAFE: Use textContent for plain text
element.textContent = userInput;

// ✅ SAFE: Use setAttribute for attributes
element.setAttribute('data-value', userInput);

// ❌ DANGEROUS: innerHTML with user input
element.innerHTML = userInput;  // XSS vulnerability!

// ✅ SAFE: If HTML is needed, sanitize first
element.innerHTML = DOMPurify.sanitize(userInput);

// ❌ DANGEROUS: eval with user input
eval(userCode);  // Never do this!

// ❌ DANGEROUS: jQuery html() with user input
$('#element').html(userInput);  // XSS vulnerability!

// ✅ SAFE: jQuery text() for plain text
$('#element').text(userInput);
```

---

## 8. CSRF Protection

### 8.1 Laravel CSRF

```php
// Middleware (applied by default to web routes)
// VerifyCsrfToken middleware

// Form with CSRF token
<form method="POST" action="{{ route('orders.store') }}">
    @csrf
    <!-- form fields -->
</form>

// AJAX with CSRF token
$.ajax({
    url: '/api/orders',
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    data: { ... }
});

// Or set globally
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

### 8.2 SameSite Cookies

```php
// config/session.php
'same_site' => 'lax',  // or 'strict' for maximum protection

// Cookie configuration
'secure' => env('SESSION_SECURE_COOKIE', true),  // HTTPS only
'http_only' => true,  // No JavaScript access
```

---

## 9. File Upload Security

### 9.1 Validation Rules

```php
public function rules(): array
{
    return [
        'file' => [
            'required',
            'file',
            'max:10240',  // 10MB max
            'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif',
        ],
        'image' => [
            'required',
            'image',
            'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000',
            'max:5120',  // 5MB max
        ],
    ];
}
```

### 9.2 Secure File Handling

```php
public function upload(Request $request): JsonResponse
{
    $file = $request->file('document');
    
    // 1. Validate MIME type (don't trust extension)
    $mimeType = $file->getMimeType();
    $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
    
    if (!in_array($mimeType, $allowedMimes)) {
        throw ValidationException::withMessages([
            'document' => ['Invalid file type'],
        ]);
    }
    
    // 2. Generate safe filename
    $filename = Str::uuid() . '.' . $file->guessExtension();
    
    // 3. Store outside web root
    $path = $file->storeAs(
        'uploads/' . date('Y/m'),
        $filename,
        'private'  // Non-public disk
    );
    
    // 4. Scan for malware (if available)
    if (config('security.scan_uploads')) {
        $this->malwareScanner->scan(storage_path('app/' . $path));
    }
    
    // 5. Create database record
    $document = Document::create([
        'user_id' => auth()->id(),
        'filename' => $file->getClientOriginalName(),
        'path' => $path,
        'mime_type' => $mimeType,
        'size' => $file->getSize(),
    ]);
    
    return response()->json([
        'success' => true,
        'document' => $document,
    ]);
}

// Serve files through controller (not direct URL)
public function download(int $id): Response
{
    $document = Document::findOrFail($id);
    
    // Check authorization
    $this->authorize('download', $document);
    
    return Storage::disk('private')->download(
        $document->path,
        $document->filename,
        ['Content-Type' => $document->mime_type]
    );
}
```

### 9.3 Path Traversal Prevention

```php
// Security exception for path traversal
class SecurityException extends Exception
{
    public static function pathTraversal(string $path, string $basePath): self
    {
        Log::warning('Path traversal attempt detected', [
            'attempted_path' => $path,
            'base_path' => $basePath,
            'ip' => request()->ip(),
            'user_id' => auth()->id(),
        ]);
        
        return new self('Invalid file path');
    }
}

// Safe path resolution
public function getFullPath(): string
{
    $basePath = storage_path('app/uploads');
    $realBase = realpath($basePath);
    
    // Check for traversal attempts
    if (str_contains($this->path, '..') || str_contains($this->path, './')) {
        throw SecurityException::pathTraversal($this->path, $realBase);
    }
    
    $fullPath = $basePath . '/' . $this->path;
    $realPath = realpath($fullPath);
    
    // Ensure path is within base directory
    if ($realPath === false || !str_starts_with($realPath, $realBase)) {
        throw SecurityException::pathTraversal($fullPath, $realBase);
    }
    
    return $realPath;
}
```

---

## 10. API Security

### 10.1 API Authentication

```php
// API Key authentication middleware
class ApiKeyAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') 
            ?? $request->bearerToken();
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'API key required',
            ], 401);
        }
        
        $key = ApiKey::where('key', hash('sha256', $apiKey))
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();
        
        if (!$key) {
            return response()->json([
                'error' => 'Invalid API key',
            ], 401);
        }
        
        // Update last used
        $key->update(['last_used_at' => now()]);
        
        // Set authenticated tenant/user
        app()->instance('api_key', $key);
        
        return $next($request);
    }
}
```

### 10.2 Rate Limiting

```php
// config/ratelimit.php
return [
    'api' => [
        'default' => [
            'limit' => 60,
            'window' => 60,
        ],
        'strict' => [
            'limit' => 10,
            'window' => 60,
        ],
    ],
];

// Rate limit middleware
class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $config = config("ratelimit.api.{$type}");
        $key = 'api:' . ($request->user()?->id ?? $request->ip());
        
        if (RateLimiter::tooManyAttempts($key, $config['limit'])) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', $seconds);
        }
        
        RateLimiter::hit($key, $config['window']);
        
        $response = $next($request);
        
        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $config['limit']);
        $response->headers->set('X-RateLimit-Remaining', 
            RateLimiter::remaining($key, $config['limit']));
        
        return $response;
    }
}
```

### 10.3 Request Logging

```php
// API request logging middleware
class ApiRequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $startTime;
        
        ApiRequestLog::create([
            'method' => $request->method(),
            'path' => $request->path(),
            'query' => $request->query(),
            'status_code' => $response->status(),
            'duration_ms' => round($duration * 1000),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'api_key_id' => app('api_key')?->id,
            'tenant_id' => tenant()?->id,
        ]);
        
        return $response;
    }
}
```

---

## 11. Sensitive Data Handling

### 11.1 Encryption

```php
// Encrypt sensitive data before storage
use Illuminate\Support\Facades\Crypt;

// Model with encrypted attribute
class Connection extends Model
{
    protected $casts = [
        'credentials' => 'encrypted:array',
    ];
}

// Or manual encryption
$encrypted = Crypt::encryptString($apiSecret);
$decrypted = Crypt::decryptString($encrypted);

// Environment-specific encryption keys
// .env
APP_KEY=base64:...

// Rotate encryption key
// Create new key, re-encrypt data, update APP_KEY
```

### 11.2 Hashing

```php
// Password hashing (automatic with make:auth)
use Illuminate\Support\Facades\Hash;

$user->password = Hash::make($request->password);

// Verify password
if (Hash::check($request->password, $user->password)) {
    // Valid
}

// API key hashing
$apiKey = Str::random(64);
$hashedKey = hash('sha256', $apiKey);

// Store hashed key, return plain key once
ApiKey::create(['key' => $hashedKey]);
return $apiKey;  // Only time plain key is available
```

### 11.3 Data Masking

```php
// Mask sensitive data in logs/responses
class DataMasker
{
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'credit_card',
        'cvv',
        'ssn',
        'api_key',
        'secret',
        'token',
    ];

    public function mask(array $data): array
    {
        array_walk_recursive($data, function (&$value, $key) {
            if (in_array(strtolower($key), $this->sensitiveFields)) {
                $value = '********';
            }
            
            // Mask email partially
            if ($key === 'email' && is_string($value)) {
                $value = $this->maskEmail($value);
            }
        });
        
        return $data;
    }

    protected function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return '********';
        
        $name = $parts[0];
        $domain = $parts[1];
        
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
        
        return $maskedName . '@' . $domain;
    }
}
```

---

## 12. Logging & Monitoring

### 12.1 Security Event Logging

```php
// Security events to log
class SecurityLogger
{
    public function logLogin(User $user, bool $success, ?string $reason = null): void
    {
        Log::channel('security')->info('Login attempt', [
            'event' => 'login',
            'success' => $success,
            'user_id' => $user->id ?? null,
            'email' => $user->email ?? null,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
        ]);
    }

    public function logPermissionDenied(User $user, string $permission, ?Model $resource = null): void
    {
        Log::channel('security')->warning('Permission denied', [
            'event' => 'permission_denied',
            'user_id' => $user->id,
            'permission' => $permission,
            'resource_type' => $resource ? get_class($resource) : null,
            'resource_id' => $resource?->id,
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
        ]);
    }

    public function logSuspiciousActivity(string $type, array $context = []): void
    {
        Log::channel('security')->alert('Suspicious activity', [
            'event' => 'suspicious_activity',
            'type' => $type,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            ...$context,
        ]);
    }
}
```

### 12.2 Audit Trail

```php
// Audit logging service
class AuditService
{
    public function log(
        string $action,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        AuditLog::create([
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_type' => $this->getUserType(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'tenant_id' => tenant()?->id,
        ]);
    }

    protected function getUserType(): string
    {
        if (auth()->guard('console')->check()) return 'console';
        if (auth()->guard('admin')->check()) return 'admin';
        if (auth()->guard('api')->check()) return 'api';
        return 'guest';
    }
}

// Usage in service
public function update(int $id, array $data): Order
{
    $order = Order::findOrFail($id);
    $oldValues = $order->toArray();
    
    $order->update($data);
    
    $this->audit->log('order.updated', $order, $oldValues, $order->fresh()->toArray());
    
    return $order;
}
```

---

## 13. Security Checklist

### Pre-Deployment Checklist

```
Authentication:
[ ] Strong password requirements enforced
[ ] Session regeneration on login
[ ] Session timeout configured
[ ] Login throttling enabled
[ ] MFA available for sensitive accounts

Authorization:
[ ] All routes have permission checks
[ ] Policies defined for all resources
[ ] Tenant isolation verified
[ ] No privilege escalation paths

Input Validation:
[ ] All input validated server-side
[ ] File uploads validated and scanned
[ ] Path traversal protection in place
[ ] SQL injection prevention verified

Output Encoding:
[ ] All output escaped by default
[ ] CSP headers configured
[ ] X-Frame-Options set

CSRF Protection:
[ ] CSRF tokens on all forms
[ ] SameSite cookie attribute set

API Security:
[ ] API authentication required
[ ] Rate limiting enabled
[ ] Request logging enabled

Data Protection:
[ ] Sensitive data encrypted
[ ] Passwords properly hashed
[ ] PII handling compliant

Logging:
[ ] Security events logged
[ ] Audit trail enabled
[ ] Log injection prevented

Infrastructure:
[ ] HTTPS enforced
[ ] Security headers set
[ ] Error messages don't leak info
[ ] Debug mode disabled
```

### Code Review Security Focus

```
When reviewing code, check for:

1. SQL Injection
   - Raw queries with user input
   - Dynamic column/table names
   - Unparameterized queries

2. XSS
   - Unescaped output
   - innerHTML with user data
   - eval() usage

3. Authorization
   - Missing permission checks
   - Direct object references
   - Tenant scope bypasses

4. File Handling
   - Path traversal
   - Unrestricted uploads
   - Executable file storage

5. Authentication
   - Weak password rules
   - Missing rate limiting
   - Session fixation

6. Cryptography
   - Weak algorithms
   - Hardcoded secrets
   - Predictable tokens
```

---

**End of Security Standards**
