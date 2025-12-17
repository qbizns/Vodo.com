# 05 - Form Builder

## Overview

The Form Builder module provides a visual interface for creating dynamic forms with drag-and-drop field arrangement, validation rules, conditional logic, and plugin-extensible field types.

## Objectives

- Visual drag-and-drop form design
- Custom field types from plugins
- Conditional field visibility
- Multi-step forms support
- Validation rules configuration
- Form templates and reuse

## Screens

| Screen | Description | Route |
|--------|-------------|-------|
| Form List | All forms | `/admin/forms` |
| Form Builder | Visual editor | `/admin/forms/{id}/build` |
| Form Settings | Form config | `/admin/forms/{id}/settings` |
| Field Types | Available types | `/admin/forms/field-types` |
| Form Submissions | View responses | `/admin/forms/{id}/submissions` |
| Form Analytics | Usage stats | `/admin/forms/{id}/analytics` |

## Related Services

```
App\Services\
├── FormBuilder              # Form construction
├── FieldTypeRegistry        # Field type management
├── FormValidator            # Validation engine
├── ConditionalLogicEngine   # Show/hide logic
└── FormRenderer             # HTML generation
```

## Related Models

```
App\Models\
├── Form                     # Form definitions
├── FormField                # Field instances
├── FormSection              # Form sections/steps
├── FormSubmission           # User submissions
├── FormRule                 # Conditional rules
└── FieldType                # Custom field types
```

## Key Features

### 1. Visual Builder
- Drag-drop field placement
- Real-time preview
- Multi-column layouts
- Section/step management

### 2. Field Types
- Text, Email, Phone, URL
- Number, Currency, Percentage
- Date, Time, DateTime
- Select, Radio, Checkbox
- File Upload, Image
- Rich Text, Signature
- Plugin custom fields

### 3. Validation
- Required, Min/Max length
- Email, URL, Phone formats
- Custom regex patterns
- Cross-field validation

### 4. Conditional Logic
- Show/hide fields
- Based on other field values
- Multiple conditions (AND/OR)

### 5. Multi-step Forms
- Step navigation
- Progress indicator
- Step validation
- Save progress

## Dependencies

- **01-plugin-management**: Custom field types
- **04-entity-data-management**: Entity field integration
