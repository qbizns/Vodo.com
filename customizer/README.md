# TailwindPlus Customizer

<div dir="rtl">

## مخصص الموقع

أداة احترافية لبناء وتخصيص صفحات المتاجر الإلكترونية بشكل مرئي مع دعم كامل للغة العربية واتجاه RTL.

</div>

---

## 🚀 Features

- **Visual Page Builder** - Drag and drop components to build pages
- **RTL Support** - Full right-to-left language support
- **Component Library** - Pre-built ecommerce components
- **Plugin System** - Extend functionality with plugins
- **Undo/Redo** - Full history management
- **Responsive Preview** - Desktop and mobile preview modes
- **Export** - Export pages as HTML or JSON

## 📁 Project Structure

```
customizer/
├── index.html                 # Main HTML entry point
├── package.json              # Project configuration
├── README.md                 # Documentation
│
├── assets/
│   ├── css/
│   │   ├── main.css          # Main CSS (imports all)
│   │   ├── base/
│   │   │   ├── variables.css # CSS variables & tokens
│   │   │   ├── reset.css     # CSS reset
│   │   │   └── utilities.css # Utility classes
│   │   └── components/
│   │       ├── toolbar.css   # Toolbar styles
│   │       ├── panel.css     # Panel styles
│   │       ├── layers.css    # Layer list styles
│   │       ├── modal.css     # Modal styles
│   │       ├── preview.css   # Preview styles
│   │       └── controls.css  # Form controls
│   │
│   ├── js/
│   │   ├── main.js           # Entry point
│   │   ├── Customizer.js     # Main app class
│   │   │
│   │   ├── core/
│   │   │   ├── EventBus.js       # Event system
│   │   │   ├── ComponentRegistry.js  # Component management
│   │   │   └── PageStateManager.js   # State & history
│   │   │
│   │   ├── ui/
│   │   │   ├── Toolbar.js    # Toolbar component
│   │   │   ├── Panel.js      # Panel component
│   │   │   ├── Layers.js     # Layers component
│   │   │   ├── Modal.js      # Modal component
│   │   │   └── Preview.js    # Preview component
│   │   │
│   │   ├── utils/
│   │   │   ├── helpers.js    # Utility functions
│   │   │   └── icons.js      # Icon utilities
│   │   │
│   │   ├── config/
│   │   │   └── config.js     # Configuration
│   │   │
│   │   └── data/
│   │       └── components.js # Sample components
│   │
│   └── icons/
│       └── icons.svg         # SVG icon sprite
│
├── plugins/
│   ├── Plugin.js             # Plugin base class
│   └── ExamplePlugin.js      # Example plugin
│
└── components/               # Additional components
```

## 🛠️ Installation

```bash
# Clone or download the project
cd customizer

# Install dependencies (optional, for dev server)
npm install

# Start development server
npm start
```

Open `http://localhost:3000` in your browser.

## 💻 Usage

### Basic Usage

```html
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <link rel="stylesheet" href="./assets/css/main.css">
</head>
<body>
    <div id="app"></div>
    <script type="module" src="./assets/js/main.js"></script>
</body>
</html>
```

### Programmatic API

```javascript
// Access the customizer instance
const customizer = window.customizer;

// Add a component
customizer.addComponent({
    id: 'my-component',
    category: 'promo-sections',
    name: { ar: 'مكون جديد', en: 'New Component' },
    html: '<div>...</div>'
});

// Undo/Redo
customizer.undo();
customizer.redo();

// Export
const html = customizer.exportHTML();
const json = customizer.exportJSON();

// Subscribe to events
customizer.on('block:added', (data) => {
    console.log('Block added:', data.block);
});
```

## 🔌 Creating Plugins

### Using Plugin Class

```javascript
import { Plugin } from './plugins/Plugin.js';

class MyPlugin extends Plugin {
    static id = 'my-plugin';
    static name = { ar: 'إضافتي', en: 'My Plugin' };
    static version = '1.0.0';

    getComponents() {
        return [
            {
                id: 'my-plugin/banner',
                category: 'promo-sections',
                name: { ar: 'بانر خاص', en: 'Custom Banner' },
                html: '<div class="banner">...</div>'
            }
        ];
    }
}

// Register plugin
customizer.registerPlugin(new MyPlugin().toConfig());
```

### Using Simple Object

```javascript
customizer.registerPlugin({
    id: 'simple-plugin',
    name: { ar: 'إضافة بسيطة', en: 'Simple Plugin' },
    version: '1.0.0',
    components: [
        {
            id: 'simple-plugin/header',
            category: 'store-navigation',
            name: { ar: 'رأس صفحة', en: 'Header' },
            html: '<header>...</header>'
        }
    ]
});
```

## 📦 Component Schema

```javascript
{
    id: 'category/component-name',     // Unique identifier
    category: 'promo-sections',        // Category ID
    name: {
        ar: 'اسم المكون',              // Arabic name
        en: 'Component Name'           // English name
    },
    description: {
        ar: 'وصف المكون',              // Arabic description
        en: 'Component description'    // English description
    },
    thumbnail: 'data:image/...',       // Base64 or URL
    tags: ['tag1', 'tag2'],            // Search tags
    html: '<div>...</div>',            // Component HTML
    fields: [],                        // Editable fields (future)
    constraints: {}                    // Constraints (future)
}
```

## 🎨 Available Categories

| ID | Arabic | English |
|----|--------|---------|
| `promo-sections` | أقسام ترويجية | Promo Sections |
| `product-lists` | قوائم المنتجات | Product Lists |
| `incentives` | الحوافز والمميزات | Incentives |
| `category-previews` | معاينة التصنيفات | Category Previews |
| `reviews` | التقييمات | Reviews |
| `store-navigation` | التنقل | Navigation |

## 📡 Events

| Event | Data | Description |
|-------|------|-------------|
| `block:added` | `{ block }` | Block was added |
| `block:removed` | `{ blockId, block }` | Block was removed |
| `block:moved` | `{ blockId, oldPosition, newPosition }` | Block was reordered |
| `block:selected` | `{ blockId }` | Block was selected |
| `page:changed` | `{ blocks }` | Page content changed |
| `plugin:registered` | `{ plugin }` | Plugin was registered |

## 🌐 Browser Support

- Chrome 90+
- Firefox 90+
- Safari 14+
- Edge 90+

## 📄 License

MIT License - © 2024 Macber LTD
