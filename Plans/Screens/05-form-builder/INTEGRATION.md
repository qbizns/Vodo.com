# Form Builder - Integration Guide

## Registering Custom Field Types

### Via Plugin Class

```php
public function boot(): void
{
    app('form.fields')->register('rating', [
        'label' => 'Star Rating',
        'icon' => 'star',
        'category' => 'Custom',
        'component' => 'form.fields.rating',
        'preview_component' => 'form-builder.previews.rating',
        'config_schema' => [
            'max_stars' => ['type' => 'number', 'default' => 5],
            'allow_half' => ['type' => 'boolean', 'default' => false],
        ],
        'validation_rules' => fn($config) => "integer|min:1|max:{$config['max_stars']}",
    ]);
}
```

### Field Type Component

```blade
{{-- resources/views/components/form/fields/rating.blade.php --}}
@props(['field', 'value' => null])

<div x-data="ratingField({{ $field->config['max_stars'] ?? 5 }}, {{ $value ?? 0 }})" class="rating-field">
    <template x-for="star in maxStars" :key="star">
        <button type="button"
                @click="setRating(star)"
                @mouseenter="hoverRating = star"
                @mouseleave="hoverRating = 0"
                class="rating-star"
                :class="{ 'active': star <= (hoverRating || rating) }">
            ★
        </button>
    </template>
    <input type="hidden" name="{{ $field->key }}" :value="rating">
</div>
```

---

## Using Forms Programmatically

### Render Form in Blade

```blade
{{-- In any view --}}
<x-form-render slug="contact-form" />

{{-- With custom submit handler --}}
<x-form-render slug="feedback" :ajax="true" @submitted="handleSubmit" />
```

### Create Form via Code

```php
use App\Models\Form;
use App\Services\FormBuilder;

$form = app(FormBuilder::class)->create([
    'name' => 'Survey Form',
    'slug' => 'survey',
    'fields' => [
        ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'validation' => ['required' => true]],
        ['key' => 'rating', 'label' => 'Rating', 'type' => 'rating', 'config' => ['max_stars' => 5]],
        ['key' => 'feedback', 'label' => 'Feedback', 'type' => 'textarea'],
    ],
]);
```

### Process Submissions

```php
use App\Models\FormSubmission;

// Get submissions
$submissions = FormSubmission::where('form_id', $formId)
    ->where('status', 'new')
    ->latest()
    ->get();

// Process submission
$submission->update(['status' => 'processed']);
```

---

## Hooks

### Filter: Modify Form Before Render

```php
$hooks->filter('form.render.contact-form', function ($form) {
    // Add field dynamically
    $form->fields->push(new FormField([
        'key' => 'source',
        'type' => 'hidden',
        'config' => ['value' => request()->header('Referer')],
    ]));
    return $form;
});
```

### Filter: Validate Submission

```php
$hooks->filter('form.validate', function ($data, $form) {
    // Custom validation
    if ($form->slug === 'contact' && str_contains($data['message'], 'spam')) {
        throw new ValidationException(['message' => 'Spam detected']);
    }
    return $data;
});
```

### Action: After Submission

```php
$hooks->action('form.submitted', function ($submission, $form) {
    // Send notification
    if ($form->slug === 'contact') {
        Mail::to('admin@example.com')->send(new ContactFormSubmission($submission));
    }
    
    // Integrate with CRM
    if ($form->settings['crm_integration'] ?? false) {
        CrmService::createLead($submission->data);
    }
});
```

### Filter: Custom Field Rendering

```php
$hooks->filter('form.field.render.rating', function ($html, $field, $value) {
    // Customize rating field output
    return view('custom.rating-field', compact('field', 'value'))->render();
});
```

---

## Embedding Forms

### Via Shortcode

```blade
{!! form('contact-form') !!}
```

### Via JavaScript Widget

```html
<div data-form="contact-form"></div>
<script src="/js/form-embed.js"></script>
```

### Via API (Headless)

```javascript
// Fetch form schema
const form = await fetch('/api/v1/forms/contact-form').then(r => r.json());

// Submit form
await fetch('/api/v1/forms/contact-form', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(formData),
});
```

---

## Form Notifications

### Configure in Settings

```php
// Form settings
'notifications' => [
    'admin' => [
        'enabled' => true,
        'email' => 'admin@example.com',
        'template' => 'emails.form-submission',
    ],
    'user' => [
        'enabled' => true,
        'email_field' => 'email',
        'template' => 'emails.form-confirmation',
    ],
],
```

### Custom Notification Handler

```php
$hooks->action('form.submitted', function ($submission, $form) {
    // Slack notification
    if ($form->settings['slack_webhook'] ?? null) {
        Http::post($form->settings['slack_webhook'], [
            'text' => "New submission on {$form->name}",
            'attachments' => [['fields' => $submission->data]],
        ]);
    }
});
```

---

## Best Practices

1. **Unique Field Keys**: Use descriptive, unique keys
2. **Validate Server-Side**: Never trust client validation alone
3. **Sanitize Input**: Clean data before storage
4. **Rate Limit**: Prevent spam submissions
5. **CSRF Protection**: Always include CSRF tokens
6. **Accessible Forms**: Use proper labels and ARIA
