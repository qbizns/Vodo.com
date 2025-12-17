# Form Builder - UI Components

## Core Components

### Form Builder Canvas

```blade
{{-- resources/views/components/form-builder/canvas.blade.php --}}
@props(['form'])

<div x-data="formBuilderCanvas(@js($form->fields->toArray()))" class="form-builder-canvas">
    <div x-ref="fieldsContainer"
         class="form-builder-canvas__fields"
         @dragover.prevent
         @drop="handleDrop($event)">
        
        <template x-for="field in fields" :key="field.id">
            <div class="form-field-wrapper"
                 :class="{ 
                     'selected': selectedFieldId === field.id,
                     'w-full': field.width === 'full',
                     'w-1/2': field.width === 'half',
                     'w-1/3': field.width === 'third'
                 }"
                 :data-id="field.id"
                 draggable="true"
                 @dragstart="startDrag($event, field)"
                 @click="selectField(field)">
                
                <div class="form-field-wrapper__handle">≡</div>
                
                <div class="form-field-wrapper__content">
                    <label class="form-field-wrapper__label">
                        <span x-text="field.label"></span>
                        <span x-show="field.validation?.required" class="text-red-500">*</span>
                    </label>
                    <x-form-builder.field-preview x-bind:field="field" />
                </div>
                
                <button @click.stop="removeField(field.id)" class="form-field-wrapper__remove">
                    <x-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        </template>
        
        <div class="form-builder-canvas__empty" x-show="fields.length === 0">
            <p>Drag fields here to build your form</p>
        </div>
    </div>
</div>

<script>
function formBuilderCanvas(initialFields) {
    return {
        fields: initialFields,
        selectedFieldId: null,
        
        selectField(field) {
            this.selectedFieldId = field.id;
            this.$dispatch('field-selected', { field });
        },
        
        handleDrop(event) {
            const fieldType = event.dataTransfer.getData('fieldType');
            if (fieldType) {
                this.addField(fieldType);
            }
        },
        
        addField(type) {
            const newField = {
                id: Date.now(),
                key: `field_${Date.now()}`,
                label: this.getDefaultLabel(type),
                type: type,
                config: {},
                validation: {},
                position: this.fields.length,
                width: 'full',
            };
            this.fields.push(newField);
            this.selectField(newField);
        },
        
        removeField(id) {
            this.fields = this.fields.filter(f => f.id !== id);
            if (this.selectedFieldId === id) {
                this.selectedFieldId = null;
            }
        },
        
        getDefaultLabel(type) {
            const labels = {
                text: 'Text Field',
                email: 'Email',
                number: 'Number',
                textarea: 'Message',
                select: 'Select Option',
                checkbox: 'Checkbox',
                date: 'Date',
                file: 'File Upload',
            };
            return labels[type] || 'New Field';
        },
    };
}
</script>
```

### Field Type Palette

```blade
{{-- resources/views/components/form-builder/field-palette.blade.php --}}
@props(['fieldTypes'])

<div class="field-palette">
    @foreach($fieldTypes as $category => $types)
        <div class="field-palette__category">
            <h4 class="field-palette__category-title">{{ $category }}</h4>
            <div class="field-palette__items">
                @foreach($types as $type)
                    <div class="field-palette__item"
                         draggable="true"
                         @dragstart="$event.dataTransfer.setData('fieldType', '{{ $type['type'] }}')">
                        <x-icon :name="$type['icon']" class="w-5 h-5" />
                        <span>{{ $type['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
```

### Field Properties Panel

```blade
{{-- resources/views/components/form-builder/field-properties.blade.php --}}
<div x-data="fieldProperties()" 
     @field-selected.window="loadField($event.detail.field)"
     class="field-properties">
    
    <template x-if="field">
        <div>
            <h3 class="text-lg font-medium mb-4">Field Properties</h3>
            
            {{-- General --}}
            <div class="space-y-4">
                <div>
                    <label>Label</label>
                    <input type="text" x-model="field.label" @input="updateField()" class="form-input">
                </div>
                
                <div>
                    <label>Field Key</label>
                    <input type="text" x-model="field.key" @input="updateField()" class="form-input">
                </div>
                
                <div>
                    <label>Placeholder</label>
                    <input type="text" x-model="field.config.placeholder" @input="updateField()" class="form-input">
                </div>
                
                <div>
                    <label>Help Text</label>
                    <input type="text" x-model="field.config.help" @input="updateField()" class="form-input">
                </div>
            </div>
            
            {{-- Validation --}}
            <div class="mt-6">
                <h4 class="font-medium mb-2">Validation</h4>
                <div class="space-y-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" x-model="field.validation.required" @change="updateField()">
                        Required
                    </label>
                    
                    <div x-show="['text', 'textarea', 'email'].includes(field.type)">
                        <label>Min Length</label>
                        <input type="number" x-model="field.validation.min" @input="updateField()" class="form-input">
                    </div>
                    
                    <div x-show="['text', 'textarea'].includes(field.type)">
                        <label>Max Length</label>
                        <input type="number" x-model="field.validation.max" @input="updateField()" class="form-input">
                    </div>
                </div>
            </div>
            
            {{-- Layout --}}
            <div class="mt-6">
                <h4 class="font-medium mb-2">Layout</h4>
                <div class="flex gap-2">
                    <button @click="field.width = 'full'; updateField()" 
                            :class="{ 'btn-primary': field.width === 'full' }"
                            class="btn btn-sm">Full</button>
                    <button @click="field.width = 'half'; updateField()"
                            :class="{ 'btn-primary': field.width === 'half' }"
                            class="btn btn-sm">Half</button>
                    <button @click="field.width = 'third'; updateField()"
                            :class="{ 'btn-primary': field.width === 'third' }"
                            class="btn btn-sm">Third</button>
                </div>
            </div>
            
            {{-- Delete --}}
            <div class="mt-6 pt-4 border-t">
                <button @click="deleteField()" class="btn btn-danger btn-sm w-full">
                    Delete Field
                </button>
            </div>
        </div>
    </template>
    
    <template x-if="!field">
        <div class="text-gray-500 text-center py-8">
            Select a field to edit its properties
        </div>
    </template>
</div>
```

### Form Renderer

```blade
{{-- resources/views/components/form/render.blade.php --}}
@props(['form'])

<form x-data="formRenderer(@js($form->fields->toArray()))"
      @submit.prevent="submit()"
      action="{{ route('forms.submit', $form->slug) }}"
      method="POST"
      enctype="multipart/form-data"
      class="dynamic-form">
    @csrf
    
    @if($form->sections->count() > 1)
        {{-- Multi-step form --}}
        <div class="form-steps">
            @foreach($form->sections as $index => $section)
                <div class="form-step" 
                     :class="{ 'active': currentStep === {{ $index }}, 'completed': currentStep > {{ $index }} }">
                    <span class="form-step__number">{{ $index + 1 }}</span>
                    <span class="form-step__title">{{ $section->title }}</span>
                </div>
            @endforeach
        </div>
    @endif
    
    <div class="form-fields">
        @foreach($form->fields as $field)
            <div class="form-field form-field--{{ $field->width }}"
                 x-show="shouldShow('{{ $field->key }}')"
                 x-transition>
                
                <label for="{{ $field->key }}">
                    {{ $field->label }}
                    @if($field->validation['required'] ?? false)
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                
                <x-dynamic-component 
                    :component="'form.fields.' . $field->type"
                    :field="$field"
                    x-model="formData.{{ $field->key }}" />
                
                @if($field->config['help'] ?? null)
                    <p class="form-field__help">{{ $field->config['help'] }}</p>
                @endif
                
                <p x-show="errors['{{ $field->key }}']" 
                   x-text="errors['{{ $field->key }}']"
                   class="form-field__error"></p>
            </div>
        @endforeach
    </div>
    
    <div class="form-actions">
        @if($form->sections->count() > 1)
            <button type="button" @click="prevStep()" x-show="currentStep > 0" class="btn btn-secondary">
                Previous
            </button>
            <button type="button" @click="nextStep()" x-show="currentStep < {{ $form->sections->count() - 1 }}" class="btn btn-primary">
                Next
            </button>
        @endif
        
        <button type="submit" x-show="currentStep === {{ max(0, $form->sections->count() - 1) }}" class="btn btn-primary">
            {{ $form->settings['submit_text'] ?? 'Submit' }}
        </button>
    </div>
</form>
```

### Conditional Logic Builder

```blade
{{-- resources/views/components/form-builder/condition-builder.blade.php --}}
@props(['field', 'allFields'])

<div x-data="conditionBuilder(@js($field->conditions ?? []))" class="condition-builder">
    <h4 class="font-medium mb-2">Show this field when:</h4>
    
    <template x-for="(condition, index) in conditions" :key="index">
        <div class="condition-row">
            <select x-model="condition.field" class="form-select">
                <option value="">Select field...</option>
                @foreach($allFields as $f)
                    @if($f->id !== $field->id)
                        <option value="{{ $f->key }}">{{ $f->label }}</option>
                    @endif
                @endforeach
            </select>
            
            <select x-model="condition.operator" class="form-select">
                <option value="equals">equals</option>
                <option value="not_equals">does not equal</option>
                <option value="contains">contains</option>
                <option value="empty">is empty</option>
                <option value="not_empty">is not empty</option>
            </select>
            
            <input type="text" 
                   x-model="condition.value" 
                   x-show="!['empty', 'not_empty'].includes(condition.operator)"
                   placeholder="Value"
                   class="form-input">
            
            <button @click="removeCondition(index)" class="btn-icon text-red-500">
                <x-icon name="trash-2" class="w-4 h-4" />
            </button>
        </div>
    </template>
    
    <button @click="addCondition()" class="btn btn-sm btn-secondary mt-2">
        + Add Condition
    </button>
</div>
```
