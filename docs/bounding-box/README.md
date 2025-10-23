# BoundingBoxEditor Component

**Vue 3 component for interactive damage assessment marking on trading card images**

## Overview

The `BoundingBoxEditor` component provides a sophisticated canvas-based interface for creating and managing multiple bounding boxes on card images. Built specifically for PCR Card's damage assessment system, it allows technicians to visually mark damage areas with detailed metadata.

## Visual Example

![Damage Assessment with Bounding Box Overlay](./screenshots/bounding-box-overlay-example.png)

The image above demonstrates how bounding boxes appear overlaid on trading card images from the media library:

- **Green/Teal Box**: Indicates a damage area (Minor severity in this example)
- **Label "#1 Scratch"**: Shows assessment number and damage type
- **Semi-transparent Fill**: Allows viewing the underlying damage while clearly marking the area
- **Precise Positioning**: Bounding boxes scale correctly with the displayed image dimensions
- **Image Source**: Uses Laravel Media Library URLs for image display
- **Overlay Technique**: HTML `<div>` elements with absolute positioning (not canvas rendering)

The component automatically calculates scale factors between the original image dimensions and the displayed size, ensuring accurate positioning regardless of viewport size.

## Features

- **Multiple Bounding Boxes**: Draw unlimited damage areas on a single image
- **Interactive Canvas**: HTML5 Canvas API for precise drawing and selection
- **Damage Classification**: Assign damage type and severity per box
- **Color-Coded Boxes**: Visual differentiation by severity (green/yellow/red)
- **Edit & Delete**: Click to select, Delete key or button to remove
- **Readonly Mode**: Display-only mode for viewing existing assessments
- **Responsive Design**: Automatic scaling while preserving coordinate accuracy
- **Keyboard Support**: Delete key for quick box removal

## Component Props

### Required Props

| Prop | Type | Description |
|------|------|-------------|
| `imageUrl` | `String` | URL of the trading card image to annotate |
| `damageTypes` | `Array` | Available damage type options from DamageType constants |
| `severityLevels` | `Array` | Available severity level options from DamageSeverity constants |

### Optional Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `initialBoxes` | `Array` | `[]` | Pre-existing bounding boxes to display/edit |
| `readonly` | `Boolean` | `false` | Whether the editor is in readonly mode |

## Data Format

### Damage Type Options

Each damage type object should have:

```javascript
{
  value: 'scratch',        // Constant value from DamageType
  label: 'Scratch',        // Human-readable label
  color: '#EF4444',        // Hex color for visual coding
  icon: '⚡'               // Emoji icon for quick identification
}
```

### Severity Level Options

Each severity level object should have:

```javascript
{
  value: 'moderate',       // Constant value from DamageSeverity
  label: 'Moderate',       // Human-readable label
  color: '#EAB308',        // Hex color (green/yellow/red)
  priority: 2              // Weight for sorting (1-3)
}
```

### Bounding Box Structure

Each bounding box emitted by the component:

```javascript
{
  id: 'uuid-string',            // Unique identifier (auto-generated)
  x: 100,                       // Pixels from left edge
  y: 150,                       // Pixels from top edge
  width: 200,                   // Width in pixels
  height: 100,                  // Height in pixels
  image_width: 1000,            // Original image width
  image_height: 1400,           // Original image height
  rotation: 0,                  // Rotation in degrees (reserved)
  damageType: 'scratch',        // Selected damage type
  severity: 'moderate',         // Selected severity level
  notes: 'Deep scratch on...'   // Optional description
}
```

## Usage Examples

### Basic Usage

```vue
<template>
  <BoundingBoxEditor
    :image-url="cardImageUrl"
    :damage-types="damageTypeOptions"
    :severity-levels="severityLevelOptions"
    @update="handleBoxesUpdate"
  />
</template>

<script>
import BoundingBoxEditor from './BoundingBoxEditor.vue';

export default {
  components: { BoundingBoxEditor },

  data() {
    return {
      cardImageUrl: 'https://storage.example.com/cards/123-front.jpg',
      damageTypeOptions: [
        { value: 'scratch', label: 'Scratch', color: '#EF4444', icon: '⚡' },
        { value: 'crease', label: 'Crease', color: '#F59E0B', icon: '〰️' },
        // ... more types
      ],
      severityLevelOptions: [
        { value: 'minor', label: 'Minor', color: '#22C55E', priority: 1 },
        { value: 'moderate', label: 'Moderate', color: '#EAB308', priority: 2 },
        { value: 'severe', label: 'Severe', color: '#EF4444', priority: 3 },
      ],
    }
  },

  methods: {
    handleBoxesUpdate(boxes) {
      console.log('Bounding boxes updated:', boxes);
      // Save to backend, update parent state, etc.
    }
  }
}
</script>
```

### Readonly Mode (Viewing Existing Assessment)

```vue
<BoundingBoxEditor
  :image-url="assessment.imageUrl"
  :initial-boxes="assessment.boundingBoxes"
  :damage-types="damageTypeOptions"
  :severity-levels="severityLevelOptions"
  :readonly="true"
/>
```

### Edit Mode with Existing Boxes

```vue
<BoundingBoxEditor
  :image-url="assessment.imageUrl"
  :initial-boxes="assessment.boundingBoxes"
  :damage-types="damageTypeOptions"
  :severity-levels="severityLevelOptions"
  :readonly="false"
  @update="saveChanges"
/>
```

## Integration with Nova Field

The component is designed to work with the `DamageAssessmentEditor` Nova field:

```php
// In Nova Resource (app/Nova/DamageAssessment.php)
use App\Nova\Fields\DamageAssessmentEditor;

DamageAssessmentEditor::make('Visual Editor', 'bounding_boxes')
    ->image(function() {
        return $this->submissionImage?->url;
    })
    ->damageAssessments(function() {
        return [$this->resource];
    })
    ->readonly(false)
    ->onlyOnDetail();
```

The Nova field automatically provides:
- Damage type options from `App\Constants\DamageType`
- Severity level options from `App\Constants\DamageSeverity`
- Proper data serialization for Vue component

## User Interaction Flow

### Creating New Damage Area

1. Click **"Add Damage Area"** button
2. Canvas cursor changes to crosshair
3. Click and drag on image to draw bounding box
4. Release mouse to finalize box
5. Box appears in list below canvas
6. Select damage type from dropdown
7. Select severity level from dropdown
8. Add optional notes
9. Component emits `update` event with all boxes

### Editing Existing Damage Area

1. Click on box in canvas or list
2. Box highlights with selection handles
3. Modify damage type, severity, or notes
4. Changes emit `update` event immediately

### Deleting Damage Area

**Method 1: Delete Button**
- Click box to select
- Click "Delete Selected" button in toolbar

**Method 2: Keyboard Shortcut**
- Click box to select
- Press `Delete` key

**Method 3: Individual Delete**
- Click X button in box list item

### Clearing All Damage Areas

1. Click **"Clear All"** button
2. Confirm in dialog
3. All boxes removed and `update` event emitted

## Canvas Rendering

### Coordinate System

The component uses a dual coordinate system:

1. **Storage Coordinates** (Original Image)
   - Bounding boxes stored with original image dimensions
   - Ensures accuracy when image is resized or rescaled
   - Used for backend persistence

2. **Display Coordinates** (Canvas)
   - Boxes scaled to match current canvas size
   - Calculated via `canvasScale = displayWidth / originalWidth`
   - Used for rendering and interaction

### Box Rendering Details

```javascript
// Color coding by severity
const colors = {
  minor: '#22C55E',      // Green (traffic light: safe)
  moderate: '#EAB308',   // Yellow (traffic light: caution)
  severe: '#EF4444'      // Red (traffic light: danger)
}

// Box styles
- Border: 2px solid (severity color)
- Background: severity color at 12% opacity
- Selected: 3px border + selection handles
- Hovered: 2.5px border
- Label: damage type icon + label + severity
```

## Browser Compatibility

- **Modern Browsers**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **HTML5 Canvas**: Required (all modern browsers)
- **ES6 Features**: Arrow functions, destructuring, template literals
- **Vue 3**: Composition API not used (Options API for compatibility)

## Performance Considerations

- Canvas redraws only on state changes (not on every frame)
- Efficient hit detection using bounding box coordinates
- Event listeners cleaned up on component unmount
- Image scaling calculations cached until image reload

## Accessibility

- Keyboard support for Delete key
- High contrast box colors for visibility
- Clear visual feedback on selection/hover
- Screen reader friendly form labels

## Future Enhancements

Potential improvements for future versions:

1. **Drag-and-Drop Boxes**: Move existing boxes by dragging
2. **Resize Handles**: Click and drag handles to resize
3. **Zoom Controls**: Zoom in/out for detailed marking
4. **Touch Support**: Mobile/tablet drawing with touch events
5. **Undo/Redo**: History stack for box operations
6. **Box Rotation**: Support rotated bounding boxes
7. **Multi-Select**: Select multiple boxes with Shift+Click
8. **Export/Import**: JSON export for external tools

## Component Files

| File | Purpose |
|------|---------|
| `BoundingBoxEditor.vue` | Main component (Vue 3 SFC) |
| `DetailField.vue` | Nova detail view (readonly) |
| `FormField.vue` | Nova form view (editable) |
| `IndexField.vue` | Nova index view (compact) |
| `README.md` | This documentation |

## Related Documentation

- [DamageType Constants](/Volumes/JS-DEV/pcrcard/app/Constants/DamageType.php)
- [DamageSeverity Constants](/Volumes/JS-DEV/pcrcard/app/Constants/DamageSeverity.php)
- [DamageAssessment Model](/Volumes/JS-DEV/pcrcard/app/Models/DamageAssessment.php)
- [DamageAssessmentEditor Field](/Volumes/JS-DEV/pcrcard/app/Nova/Fields/DamageAssessmentEditor.php)
- [Nova Admin Guide](/Volumes/JS-DEV/pcrcard/docs/development/NOVA-ADMIN-GUIDE.md)

## Support

For issues or questions:
1. Check console for error messages
2. Verify damage type/severity options format
3. Ensure image URL is accessible
4. Review component props vs. actual data
5. Check browser console network tab for image load errors

## Implementation Status

### ✅ Completed (October 23, 2025)

**DetailField.vue** - Read-only damage assessment viewer

**Status**: Production ready
**Testing**: Verified with 1-box and 3-box scenarios
**Severity Colors**: All three levels working (minor=green, moderate=yellow, severe=red)

**Features Working:**
- ✅ Image loading from Laravel Media Library URLs
- ✅ Multiple bounding box overlays with absolute positioning
- ✅ Color-coded boxes by severity level
- ✅ Semi-transparent fills (12.5% opacity)
- ✅ Box labels with assessment number and damage type
- ✅ Damage summary panel below image
- ✅ Responsive scaling (boxes scale with image dimensions)
- ✅ Readonly mode for viewing existing assessments

**Technical Approach:**
- Uses HTML `<div>` elements with absolute positioning (NOT canvas rendering)
- Event-driven dimension capture from `@load` event
- Reactive data storage instead of Vue template refs (critical for Nova compatibility)
- Scale factor calculations: `scaleX = displayWidth / originalWidth`

**Key Files:**
- `DetailField.vue` (290 lines) - Main readonly component
- `VUE-REFS-FIX.md` - Critical documentation on Vue refs issue
- Nova field backend: `/app/Nova/Fields/DamageAssessmentEditor.php`
- Nova resource: `/app/Nova/DamageAssessment.php`

**Test Coverage (October 23, 2025 Update):**
- 16 total damage assessments seeded
- Box count distribution: 1-10 boxes per assessment
- All 15 damage types represented
- All 3 severities represented (minor: 3, moderate: 9, severe: 4)
- Comprehensive testing checklist created: `TESTING-CHECKLIST.md`
- Bug fix verified: DetailField now shows per-box damage types

### ✅ Completed (October 22, 2025)

**FormField.vue** - Interactive damage assessment editor

**Status**: Production ready (pending manual testing)
**Component Size**: ~1000 lines
**Testing**: Comprehensive seeders created with 20+ scenarios

**Features Working:**
- ✅ Interactive HTML5 Canvas drawing layer
- ✅ Click-and-drag bounding box creation
- ✅ Box selection on click with visual feedback
- ✅ Delete boxes (Delete key or button)
- ✅ Individual box editor panel with:
  - Per-box damage type selector
  - Per-box severity level selector
  - Per-box notes textarea with character counter
  - Position and size display
- ✅ Save/Cancel/Clear All buttons
- ✅ Comprehensive validation before save
- ✅ Keyboard shortcuts (Delete, Esc)
- ✅ UUID auto-generation for new boxes
- ✅ Coordinate scaling (original vs display)
- ✅ Change detection (prompts before discarding)
- ✅ Tailwind CSS styling with color-coded severity

**Technical Implementation:**
- HTML5 Canvas API for interactive drawing
- Event-driven mouse/touch handlers
- Reactive Vue data storage (no template refs)
- Nova FormField mixin integration
- JSON serialization with backend validation
- Model mutator supports both snake_case and camelCase fields

**Key Files:**
- `FormField.vue` (~1000 lines) - Main interactive editor
- `DamageAssessmentEditor.php` - Nova field backend with `fillAttributeFromRequest()`
- `DamageAssessment.php` - Model with accessor/mutator validation
- `DevDamageAssessmentsSeeder.php` - 20+ test scenarios

**Test Data Coverage:**
- Pristine cards (0 boxes)
- Light damage (1-2 boxes)
- Moderate damage (2-3 boxes)
- Heavy damage (3-5 boxes)
- All severity levels showcase (3 assessments)
- All damage types showcase (15 assessments)
- Mixed boxes (5 boxes, varied damage)
- Edge cases (10 boxes, stress test)

**Next Steps:**
- Manual UI testing in Nova admin
- Verify database saves correctly
- Add loading overlay during save operations (optional enhancement)

### 📋 Future Enhancements

**IndexField.vue** - Compact view for damage assessment list

**Status**: Placeholder implementation
**Display**: Show damage count badge or thumbnail preview

**Potential Features:**
- Thumbnail image with overlay count badge
- "3 areas marked" text indicator
- Color-coded severity badge
- Click to open detail view

## Critical Bug Fixes (October 23, 2025)

### DetailField Damage Type Display Bug

**Problem**: Bounding box labels in DetailField.vue were displaying the parent `DamageAssessment.damage_type` instead of each individual bounding box's `damage_type` field.

**Symptom**: User screenshot showed "Other" as damage type label, but JSON contained `"damage_type": "corner_wear"`. The label was pulling from the parent assessment's legacy `damage_type` field instead of the per-box field.

**Root Cause**: Template code at lines 35-37:
```vue
<!-- BROKEN -->
<span v-if="boxIndex === 0 && assessment.damage_type" class="ml-1 text-xs">
    {{ formatDamageType(assessment.damage_type) }}
</span>
```

**Fix Applied**: Changed to use individual box's damage_type:
```vue
<!-- FIXED -->
<span v-if="box.damage_type" class="ml-1 text-xs">
    {{ formatDamageType(box.damage_type) }}
</span>
```

**Impact**: Now each bounding box displays its own damage type correctly. Removed dependency on parent assessment's legacy `damage_type` field.

### Field Naming Standardization (snake_case only)

**Problem**: DamageAssessment model mutator supported both `damage_type` (snake_case) and `damageType` (camelCase) for backward compatibility.

**Decision**: Standardized on snake_case only (`damage_type`, `severity`, `notes`) to match Laravel conventions and eliminate confusion.

**Changes**:
- `DamageAssessment.php:162` - Removed camelCase fallback: `$box['damageType']`
- Now only accepts: `damage_type`, `severity`, `notes` (snake_case)
- Frontend components updated to use snake_case consistently

**Migration**: Database refreshed with `./scripts/dev.sh fresh` after standardization to ensure clean data.

## Critical Vue Refs Fix

⚠️ **IMPORTANT**: This component uses reactive data storage instead of Vue template refs.

**Problem**: Using `ref="cardImage"` and `this.$refs.cardImage` caused Vue rendering errors in Laravel Nova 5.7.6 + Vue 3.5.18:
```
TypeError: Cannot read properties of null (reading 'refs')
```

**Solution**: Store image dimensions in reactive `data()` properties, captured from the `@load` event handler.

**Full Documentation**: See [VUE-REFS-FIX.md](./VUE-REFS-FIX.md) for complete technical details and implementation patterns.

## License

Internal PCR Card Development - October 2025
