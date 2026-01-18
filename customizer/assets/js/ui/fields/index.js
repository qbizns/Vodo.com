/**
 * TailwindPlus Customizer - Fields Index
 * =======================================
 * Registers all built-in field types
 * 
 * @module ui/fields/index
 * @version 1.0.0
 */

import { fieldRegistry, BaseField } from '../../core/FieldRegistry.js';

// Import field types
import { TextField, TextareaField } from './TextField.js';
import { ImageField } from './ImageField.js';
import { LinkField } from './LinkField.js';
import { SelectField } from './SelectField.js';
import { ColorField } from './ColorField.js';
import { ToggleField } from './ToggleField.js';
import { NumberField } from './NumberField.js';
import { IconField } from './IconField.js';
import { RepeaterField } from './RepeaterField.js';
import { GroupField } from './GroupField.js';
import { RichTextField } from './RichTextField.js';

// Register all built-in fields
export function registerBuiltInFields() {
    // Text fields
    fieldRegistry.register('text', TextField);
    fieldRegistry.register('textarea', TextareaField);
    fieldRegistry.register('richtext', RichTextField);
    
    // Media fields
    fieldRegistry.register('image', ImageField);
    fieldRegistry.register('icon', IconField);
    
    // Selection fields
    fieldRegistry.register('select', SelectField);
    fieldRegistry.register('color', ColorField);
    
    // Input fields
    fieldRegistry.register('link', LinkField);
    fieldRegistry.register('number', NumberField);
    fieldRegistry.register('toggle', ToggleField);
    
    // Container fields
    fieldRegistry.register('repeater', RepeaterField);
    fieldRegistry.register('group', GroupField);
    
    console.log('📝 Registered field types:', fieldRegistry.list().join(', '));
}

// Export all field classes for direct use
export {
    BaseField,
    TextField,
    TextareaField,
    RichTextField,
    ImageField,
    IconField,
    SelectField,
    ColorField,
    LinkField,
    NumberField,
    ToggleField,
    RepeaterField,
    GroupField,
};

// Export registry
export { fieldRegistry };

export default registerBuiltInFields;
