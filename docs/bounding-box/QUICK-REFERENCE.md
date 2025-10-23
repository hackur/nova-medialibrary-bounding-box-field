# BoundingBoxEditor Quick Reference

**Fast lookup guide for common tasks and patterns**

---

## Component Import

```javascript
import BoundingBoxEditor from '@/nova/fields/damage-assessment-editor/BoundingBoxEditor.vue';
```

---

## Minimal Working Example

```vue
<template>
  <BoundingBoxEditor
    :image-url="cardImageUrl"
    :damage-types="damageTypes"
    :severity-levels="severityLevels"
    @update="handleUpdate"
  />
</template>

<script>
import BoundingBoxEditor from './BoundingBoxEditor.vue';

export default {
  components: { BoundingBoxEditor },

  data() {
    return {
      cardImageUrl: 'https://example.com/card.jpg',
      damageTypes: [
        { value: 'scratch', label: 'Scratch', color: '#EF4444', icon: '⚡' }
      ],
      severityLevels: [
        { value: 'minor', label: 'Minor', color: '#22C55E', priority: 1 }
      ]
    }
  },

  methods: {
    handleUpdate(boxes) {
      console.log('Boxes:', boxes);
    }
  }
}
</script>
```

---

## Props Cheat Sheet

| Prop | Type | Required | Default | Example |
|------|------|----------|---------|---------|
| `imageUrl` | String | ✅ | - | `'https://storage.example.com/card.jpg'` |
| `damageTypes` | Array | ✅ | - | `[{value, label, color, icon}]` |
| `severityLevels` | Array | ✅ | - | `[{value, label, color, priority}]` |
| `initialBoxes` | Array | ❌ | `[]` | `[{id, x, y, width, height, ...}]` |
| `readonly` | Boolean | ❌ | `false` | `true` |

---

## Events

| Event | Payload | When Fired | Example |
|-------|---------|------------|---------|
| `update` | `Array<Box>` | Box added/edited/deleted | `@update="handleUpdate"` |

---

## Bounding Box Object Structure

```javascript
{
  id: 'uuid-string',            // Auto-generated
  x: 100,                       // Pixels from left
  y: 150,                       // Pixels from top
  width: 200,                   // Width in pixels
  height: 100,                  // Height in pixels
  image_width: 1000,            // Original image width
  image_height: 1400,           // Original image height
  rotation: 0,                  // Degrees (reserved)
  damageType: 'scratch',        // From DamageType constants
  severity: 'moderate',         // From DamageSeverity constants
  notes: 'Description here'     // Optional text
}
```

---

## Common Patterns

### Pattern 1: Load Configuration from Backend

```javascript
async mounted() {
  const response = await axios.get('/api/damage-assessment/config');
  this.damageTypes = response.data.damageTypes;
  this.severityLevels = response.data.severityLevels;
}
```

### Pattern 2: Save to Backend on Update

```javascript
methods: {
  async handleUpdate(boxes) {
    await axios.patch(`/api/damage-assessments/${this.id}`, {
      bounding_boxes: boxes
    });
  }
}
```

### Pattern 3: Readonly View

```vue
<BoundingBoxEditor
  :image-url="assessment.imageUrl"
  :initial-boxes="assessment.boundingBoxes"
  :damage-types="types"
  :severity-levels="levels"
  :readonly="true"
/>
```

### Pattern 4: Auto-Save Draft

```javascript
methods: {
  handleUpdate(boxes) {
    localStorage.setItem(`draft-${this.cardId}`, JSON.stringify(boxes));
  }
}
```

---

## Damage Type Options Format

```javascript
const damageTypes = [
  {
    value: 'scratch',        // Constant from DamageType::SCRATCH
    label: 'Scratch',        // Human-readable display name
    color: '#EF4444',        // Hex color for box border
    icon: '⚡'               // Emoji for quick identification
  },
  // ... more types
];
```

**Get from Backend**:
```php
// In Controller
foreach (DamageType::all() as $type) {
    $types[] = [
        'value' => $type,
        'label' => DamageType::label($type),
        'color' => DamageType::color($type),
        'icon' => DamageType::icon($type),
    ];
}
```

---

## Severity Level Options Format

```javascript
const severityLevels = [
  {
    value: 'minor',          // Constant from DamageSeverity::MINOR
    label: 'Minor',          // Human-readable display name
    color: '#22C55E',        // Hex color (green/yellow/red)
    priority: 1              // Weight for sorting (1-3)
  },
  // ... more levels
];
```

**Get from Backend**:
```php
// In Controller
foreach (DamageSeverity::all() as $severity) {
    $levels[] = [
        'value' => $severity,
        'label' => DamageSeverity::label($severity),
        'color' => DamageSeverity::color($severity),
        'priority' => DamageSeverity::priorityWeight($severity),
    ];
}
```

---

## User Interaction

| Action | How |
|--------|-----|
| **Add box** | Click "Add Damage Area" → Drag on canvas |
| **Select box** | Click on box (canvas or list) |
| **Edit box** | Select → Change dropdowns/notes |
| **Delete box** | Select → Press Delete OR click X button |
| **Clear all** | Click "Clear All" button |

---

## Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `Delete` | Delete selected box |

---

## Debugging Checklist

```javascript
// 1. Check component mount
mounted() {
  console.log('Component mounted:', {
    imageUrl: this.imageUrl,
    damageTypes: this.damageTypes,
    severityLevels: this.severityLevels,
    initialBoxes: this.initialBoxes,
  });
}

// 2. Check update events
handleUpdate(boxes) {
  console.log('Update received:', {
    count: boxes.length,
    boxes: boxes,
  });
}

// 3. Check image load
onImageLoad(event) {
  console.log('Image loaded:', {
    width: event.target.naturalWidth,
    height: event.target.naturalHeight,
  });
}
```

---

## Common Issues & Quick Fixes

| Issue | Fix |
|-------|-----|
| Boxes not showing | Check `initialBoxes` format and image load |
| Canvas wrong size | Verify image dimensions and scale calculation |
| Update not firing | Check `@update` listener is attached |
| CORS error | Serve images from same domain or add CORS headers |
| Colors wrong | Verify severity/damage type values match constants |

---

## API Endpoints Pattern

### GET Configuration
```javascript
GET /api/damage-assessment/config
→ { damageTypes: [...], severityLevels: [...] }
```

### POST Create Assessment
```javascript
POST /api/damage-assessments
Body: {
  submission_trading_card_id: 123,
  submission_image_id: 456,
  damage_type: 'scratch',
  severity: 'moderate',
  bounding_boxes: [...],
  notes: 'Description'
}
→ 201 Created
```

### PATCH Update Assessment
```javascript
PATCH /api/damage-assessments/{id}
Body: {
  bounding_boxes: [...]
}
→ 200 OK
```

---

## Nova Field Usage

### In Nova Resource (Detail View)
```php
DamageAssessmentEditor::make('Visual Editor', 'bounding_boxes')
    ->image(fn() => $this->submissionImage?->url)
    ->damageAssessments(fn() => [$this->resource])
    ->readonly(false)
    ->onlyOnDetail();
```

### In Nova Resource (Forms)
```php
DamageAssessmentEditor::make('Visual Editor', 'bounding_boxes')
    ->image(fn() => $this->submissionImage?->url)
    ->hideFromIndex()
    ->hideFromDetail()
    ->showOnCreating()
    ->showOnUpdating();
```

---

## Color Codes Reference

### Severity Colors (Traffic Light System)
- **Minor**: `#22C55E` (Green) - Safe, minimal concern
- **Moderate**: `#EAB308` (Yellow) - Caution, needs attention
- **Severe**: `#EF4444` (Red) - Danger, immediate action

### Damage Type Colors (15 total)
See `App\Constants\DamageType::colors()` for full list

---

## File Locations

| File | Purpose |
|------|---------|
| `/resources/js/nova/fields/damage-assessment-editor/BoundingBoxEditor.vue` | Main component |
| `/resources/js/nova/fields/damage-assessment-editor/DetailField.vue` | Nova detail view |
| `/resources/js/nova/fields/damage-assessment-editor/FormField.vue` | Nova form view |
| `/app/Nova/Fields/DamageAssessmentEditor.php` | Nova field class |
| `/app/Constants/DamageType.php` | Damage type constants |
| `/app/Constants/DamageSeverity.php` | Severity constants |
| `/app/Models/DamageAssessment.php` | Database model |

---

## Testing Data

### Create Test Boxes
```javascript
const testBoxes = [
  {
    id: 'test-1',
    x: 100, y: 100, width: 200, height: 150,
    image_width: 1000, image_height: 1400,
    rotation: 0,
    damageType: 'scratch',
    severity: 'moderate',
    notes: 'Test box 1'
  },
  {
    id: 'test-2',
    x: 350, y: 200, width: 150, height: 100,
    image_width: 1000, image_height: 1400,
    rotation: 0,
    damageType: 'crease',
    severity: 'minor',
    notes: 'Test box 2'
  }
];
```

### Seed Database
```bash
php artisan db:seed --class=Database\\Seeders\\Development\\DevDamageAssessmentsSeeder
```

---

## Performance Tips

1. **Lazy Load Images**: Use `loading="lazy"` on image elements
2. **Debounce Updates**: Wrap `@update` handler in debounce for auto-save
3. **Canvas Redraw**: Component only redraws on state change (not every frame)
4. **Memory Cleanup**: Event listeners removed on unmount automatically

---

## Browser Requirements

- Modern browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- HTML5 Canvas API support
- ES6 JavaScript support
- Vue 3 compatible

---

## Support & Documentation

- [Component README](./README.md) - Detailed documentation
- [Integration Guide](./INTEGRATION-GUIDE.md) - Setup instructions
- [DamageType Constants](/Volumes/JS-DEV/pcrcard/app/Constants/DamageType.php) - Backend constants
- [DamageSeverity Constants](/Volumes/JS-DEV/pcrcard/app/Constants/DamageSeverity.php) - Backend constants
- [Nova Admin Guide](/Volumes/JS-DEV/pcrcard/docs/development/NOVA-ADMIN-GUIDE.md) - Nova patterns

---

**Last Updated**: October 2025
**Component Version**: 1.0.0
**Compatibility**: Vue 3, Laravel 12, Nova 5.x
