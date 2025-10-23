# BoundingBoxEditor Integration Guide

**Complete guide for integrating the BoundingBoxEditor component into PCR Card application**

## Table of Contents

1. [Quick Start](#quick-start)
2. [Nova Field Integration](#nova-field-integration)
3. [Standalone Vue Integration](#standalone-vue-integration)
4. [Backend API Integration](#backend-api-integration)
5. [Data Transformation Patterns](#data-transformation-patterns)
6. [Common Use Cases](#common-use-cases)
7. [Troubleshooting](#troubleshooting)

---

## Quick Start

### Step 1: Import Component

```javascript
import BoundingBoxEditor from '@/nova/fields/damage-assessment-editor/BoundingBoxEditor.vue';
```

### Step 2: Register Component

```javascript
export default {
  components: {
    BoundingBoxEditor
  }
}
```

### Step 3: Use in Template

```vue
<BoundingBoxEditor
  :image-url="cardImageUrl"
  :damage-types="damageTypeOptions"
  :severity-levels="severityLevelOptions"
  @update="handleUpdate"
/>
```

---

## Nova Field Integration

### Option 1: FormField.vue (Editable Form)

Update `/Volumes/JS-DEV/pcrcard/resources/js/nova/fields/damage-assessment-editor/FormField.vue`:

```vue
<template>
  <DefaultField :field="field" :errors="errors">
    <template #field>
      <BoundingBoxEditor
        :image-url="field.imageUrl"
        :initial-boxes="currentValue"
        :damage-types="field.damageTypes"
        :severity-levels="field.severityLevels"
        :readonly="field.readonly || false"
        @update="handleUpdate"
      />
    </template>
  </DefaultField>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova';
import BoundingBoxEditor from './BoundingBoxEditor.vue';

export default {
  components: { BoundingBoxEditor },

  mixins: [FormField, HandlesValidationErrors],

  data() {
    return {
      currentValue: [],
    }
  },

  mounted() {
    // Parse initial value from field
    if (this.field.value) {
      this.currentValue = typeof this.field.value === 'string'
        ? JSON.parse(this.field.value)
        : this.field.value;
    }
  },

  methods: {
    /**
     * Handle bounding boxes update from editor
     */
    handleUpdate(boxes) {
      this.currentValue = boxes;

      // Update Nova's form value
      this.value = JSON.stringify(boxes);

      // Emit change event for Nova
      this.$emit('field-changed');
    },

    /**
     * Fill the given FormData object with the field's internal value
     */
    fill(formData) {
      formData.append(this.field.attribute, JSON.stringify(this.currentValue));
    },
  },
}
</script>
```

### Option 2: DetailField.vue (Readonly View)

Current implementation at `/Volumes/JS-DEV/pcrcard/resources/js/nova/fields/damage-assessment-editor/DetailField.vue` already works well with HTML img + div overlays. For canvas-based rendering:

```vue
<template>
  <div class="px-4 py-3">
    <h4 class="text-sm font-semibold mb-2">{{ field?.label || 'Visual Editor' }}</h4>

    <BoundingBoxEditor
      v-if="imageUrl"
      :image-url="imageUrl"
      :initial-boxes="boundingBoxes"
      :damage-types="field.damageTypes || []"
      :severity-levels="field.severityLevels || []"
      :readonly="true"
    />

    <div v-else class="text-gray-500 italic">
      {{ loadingError || 'No image available' }}
    </div>
  </div>
</template>

<script>
import BoundingBoxEditor from './BoundingBoxEditor.vue';

export default {
  components: { BoundingBoxEditor },

  props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

  data() {
    return {
      imageUrl: null,
      boundingBoxes: [],
      loadingError: null,
    }
  },

  mounted() {
    console.log('DamageAssessmentEditor DetailField mounted', {
      hasField: !!this.field,
      imageUrl: this.field?.imageUrl,
      damageAssessmentsCount: this.field?.damageAssessments?.length || 0,
    });

    // Extract data from field prop
    this.imageUrl = this.field?.imageUrl;

    // Extract bounding boxes from first assessment (if available)
    if (this.field?.damageAssessments?.[0]?.bounding_boxes) {
      this.boundingBoxes = this.field.damageAssessments[0].bounding_boxes;
    }

    if (!this.imageUrl) {
      this.loadingError = 'No image URL provided';
    }
  },
}
</script>
```

### Nova Resource Configuration

In `/Volumes/JS-DEV/pcrcard/app/Nova/DamageAssessment.php`:

```php
use App\Nova\Fields\DamageAssessmentEditor;

/**
 * Get the fields displayed by the resource.
 */
public function fields(NovaRequest $request): array
{
    return [
        ID::make()->sortable(),

        // ... other fields

        // Visual Editor Field (Detail View)
        DamageAssessmentEditor::make('Visual Editor', 'bounding_boxes')
            ->image(function() {
                // Get image URL from submission image relation
                return $this->submissionImage?->url;
            })
            ->damageAssessments(function() {
                // Pass current assessment as array
                return $this->resource ? [$this->resource] : [];
            })
            ->readonly(false) // Allow editing on detail view
            ->onlyOnDetail()
            ->help('Mark damage areas by drawing bounding boxes'),

        // Or use on forms
        DamageAssessmentEditor::make('Visual Editor', 'bounding_boxes')
            ->image(function() {
                return $this->submissionImage?->url;
            })
            ->hideFromIndex()
            ->hideFromDetail()
            ->showOnCreating()
            ->showOnUpdating(),
    ];
}
```

---

## Standalone Vue Integration

### In Customer Dashboard (Vue 3 App)

```vue
<template>
  <div class="damage-assessment-form">
    <h2>Report Card Damage</h2>

    <BoundingBoxEditor
      :image-url="cardImage"
      :damage-types="damageTypes"
      :severity-levels="severityLevels"
      @update="saveDamageAreas"
    />

    <button @click="submitAssessment" :disabled="submitting">
      Submit Assessment
    </button>
  </div>
</template>

<script>
import BoundingBoxEditor from '@/components/BoundingBoxEditor.vue';
import axios from 'axios';

export default {
  components: { BoundingBoxEditor },

  data() {
    return {
      cardImage: '',
      damageTypes: [],
      severityLevels: [],
      boundingBoxes: [],
      submitting: false,
    }
  },

  async mounted() {
    // Fetch configuration from API
    await this.loadConfiguration();

    // Load card image
    this.cardImage = '/storage/cards/123-front.jpg';
  },

  methods: {
    /**
     * Load damage types and severity levels from backend
     */
    async loadConfiguration() {
      try {
        const response = await axios.get('/api/damage-assessment/config');
        this.damageTypes = response.data.damageTypes;
        this.severityLevels = response.data.severityLevels;
      } catch (error) {
        console.error('Failed to load configuration:', error);
      }
    },

    /**
     * Save damage areas (called on every update)
     */
    saveDamageAreas(boxes) {
      this.boundingBoxes = boxes;
      console.log('Damage areas updated:', boxes.length);
    },

    /**
     * Submit complete assessment to backend
     */
    async submitAssessment() {
      if (this.boundingBoxes.length === 0) {
        alert('Please mark at least one damage area');
        return;
      }

      this.submitting = true;

      try {
        const response = await axios.post('/api/damage-assessments', {
          submission_trading_card_id: this.$route.params.cardId,
          submission_image_id: this.$route.params.imageId,
          bounding_boxes: this.boundingBoxes,
          // Additional fields populated from first box
          damage_type: this.boundingBoxes[0].damageType,
          severity: this.boundingBoxes[0].severity,
          notes: this.boundingBoxes[0].notes,
        });

        console.log('Assessment created:', response.data);
        this.$router.push('/submissions');
      } catch (error) {
        console.error('Failed to submit assessment:', error);
        alert('Failed to submit assessment. Please try again.');
      } finally {
        this.submitting = false;
      }
    },
  },
}
</script>
```

---

## Backend API Integration

### API Endpoint for Configuration

```php
// routes/api.php
Route::get('/damage-assessment/config', [DamageAssessmentController::class, 'config']);
```

```php
// app/Http/Controllers/Api/DamageAssessmentController.php
use App\Constants\DamageType;
use App\Constants\DamageSeverity;

public function config(): JsonResponse
{
    return response()->json([
        'damageTypes' => $this->formatDamageTypes(),
        'severityLevels' => $this->formatSeverityLevels(),
    ]);
}

protected function formatDamageTypes(): array
{
    $types = [];

    foreach (DamageType::all() as $type) {
        $types[] = [
            'value' => $type,
            'label' => DamageType::label($type),
            'color' => DamageType::color($type),
            'icon' => DamageType::icon($type),
        ];
    }

    return $types;
}

protected function formatSeverityLevels(): array
{
    $levels = [];

    foreach (DamageSeverity::all() as $severity) {
        $levels[] = [
            'value' => $severity,
            'label' => DamageSeverity::label($severity),
            'color' => DamageSeverity::color($severity),
            'priority' => DamageSeverity::priorityWeight($severity),
        ];
    }

    return $levels;
}
```

### API Endpoint for Creating Assessment

```php
// routes/api.php
Route::post('/damage-assessments', [DamageAssessmentController::class, 'store'])
    ->middleware('auth:sanctum');
```

```php
// app/Http/Controllers/Api/DamageAssessmentController.php
use App\Models\DamageAssessment;
use Illuminate\Http\Request;

public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'submission_trading_card_id' => 'required|exists:submission_trading_cards,id',
        'submission_image_id' => 'required|exists:submission_images,id',
        'damage_type' => 'required|in:' . implode(',', DamageType::all()),
        'severity' => 'required|in:' . implode(',', DamageSeverity::all()),
        'bounding_boxes' => 'required|array|min:1',
        'bounding_boxes.*.x' => 'required|numeric|min:0',
        'bounding_boxes.*.y' => 'required|numeric|min:0',
        'bounding_boxes.*.width' => 'required|numeric|min:1',
        'bounding_boxes.*.height' => 'required|numeric|min:1',
        'notes' => 'nullable|string|max:1000',
    ]);

    $assessment = DamageAssessment::create([
        'submission_trading_card_id' => $validated['submission_trading_card_id'],
        'submission_image_id' => $validated['submission_image_id'],
        'damage_type' => $validated['damage_type'],
        'severity' => $validated['severity'],
        'bounding_boxes' => $validated['bounding_boxes'],
        'notes' => $validated['notes'] ?? null,
        'assessed_by_user_id' => $request->user()->id,
    ]);

    return response()->json($assessment->load(['submissionTradingCard', 'submissionImage']), 201);
}
```

---

## Data Transformation Patterns

### Converting Database Model to Component Props

```javascript
// From DamageAssessment model
const assessment = {
  id: 123,
  damage_type: 'scratch',
  severity: 'moderate',
  bounding_boxes: [
    {
      id: 'uuid-1',
      x: 100,
      y: 150,
      width: 200,
      height: 100,
      // ... more fields
    }
  ]
};

// To component props
const initialBoxes = assessment.bounding_boxes.map(box => ({
  id: box.id,
  x: box.x,
  y: box.y,
  width: box.width,
  height: box.height,
  image_width: box.image_width,
  image_height: box.image_height,
  rotation: box.rotation || 0,
  damageType: box.damageType || assessment.damage_type, // Fallback to parent
  severity: box.severity || assessment.severity,         // Fallback to parent
  notes: box.notes || assessment.notes,                  // Fallback to parent
}));
```

### Converting Component Output to Database Format

```javascript
// From component @update event
const boxes = [
  {
    id: 'uuid-1',
    x: 100,
    y: 150,
    width: 200,
    height: 100,
    damageType: 'scratch',
    severity: 'moderate',
    notes: 'Deep scratch...',
    // ... more fields
  }
];

// To database format
const payload = {
  submission_trading_card_id: cardId,
  submission_image_id: imageId,
  damage_type: boxes[0].damageType,        // Use first box's type
  severity: boxes[0].severity,              // Use first box's severity
  notes: boxes[0].notes,                    // Use first box's notes
  bounding_boxes: boxes.map(box => ({
    id: box.id,
    x: box.x,
    y: box.y,
    width: box.width,
    height: box.height,
    image_width: box.image_width,
    image_height: box.image_height,
    rotation: box.rotation,
    damageType: box.damageType,
    severity: box.severity,
    notes: box.notes,
  })),
};
```

---

## Common Use Cases

### Use Case 1: Create Assessment During Card Receiving

**Scenario**: Technician receives card and marks damage before restoration

```javascript
// Initial state: no boxes
<BoundingBoxEditor
  :image-url="beforeFrontImage"
  :initial-boxes="[]"
  :damage-types="damageTypes"
  :severity-levels="severityLevels"
  @update="saveDraft"
/>

// Save draft on every update
methods: {
  saveDraft(boxes) {
    // Auto-save to local storage
    localStorage.setItem(`draft-${cardId}`, JSON.stringify(boxes));
  }
}
```

### Use Case 2: Review Assessment During QA

**Scenario**: QA reviewer checks technician's damage marking

```javascript
// Readonly mode with existing boxes
<BoundingBoxEditor
  :image-url="beforeFrontImage"
  :initial-boxes="assessment.bounding_boxes"
  :damage-types="damageTypes"
  :severity-levels="severityLevels"
  :readonly="true"
/>
```

### Use Case 3: Update Assessment After Restoration

**Scenario**: Technician marks which damage areas have been addressed

```javascript
// Editable mode with existing boxes
<BoundingBoxEditor
  :image-url="beforeFrontImage"
  :initial-boxes="assessment.bounding_boxes"
  :damage-types="damageTypes"
  :severity-levels="severityLevels"
  :readonly="false"
  @update="updateAssessment"
/>

methods: {
  async updateAssessment(boxes) {
    await axios.patch(`/api/damage-assessments/${assessmentId}`, {
      bounding_boxes: boxes
    });
  }
}
```

### Use Case 4: Compare Before/After Images

**Scenario**: Side-by-side view of damage before and after restoration

```vue
<div class="comparison-view">
  <div class="before">
    <h3>Before Restoration</h3>
    <BoundingBoxEditor
      :image-url="beforeImage"
      :initial-boxes="originalBoxes"
      :damage-types="damageTypes"
      :severity-levels="severityLevels"
      :readonly="true"
    />
  </div>

  <div class="after">
    <h3>After Restoration</h3>
    <img :src="afterImage" alt="After restoration" />
  </div>
</div>
```

---

## Troubleshooting

### Issue 1: Boxes Not Displaying

**Problem**: Component loads but boxes don't appear on canvas

**Solutions**:
1. Check browser console for errors
2. Verify `initialBoxes` format matches expected structure
3. Ensure image loads successfully (check Network tab)
4. Verify `damageTypes` and `severityLevels` are populated
5. Check that box coordinates are within image bounds

```javascript
// Debug logging
mounted() {
  console.log('Debug Info:', {
    imageUrl: this.imageUrl,
    initialBoxes: this.initialBoxes,
    damageTypes: this.damageTypes,
    severityLevels: this.severityLevels,
  });
}
```

### Issue 2: Canvas Not Scaling Correctly

**Problem**: Boxes appear in wrong positions or sizes

**Solutions**:
1. Ensure `image_width` and `image_height` are set on each box
2. Check that canvas dimensions match image display size
3. Verify `canvasScale` calculation is correct
4. Force canvas redraw after image resize

```javascript
// Force redraw on window resize
mounted() {
  window.addEventListener('resize', this.redrawCanvas);
},
beforeUnmount() {
  window.removeEventListener('resize', this.redrawCanvas);
}
```

### Issue 3: Update Event Not Firing

**Problem**: Parent component doesn't receive updates

**Solutions**:
1. Verify `@update` listener is attached
2. Check that handler function is defined
3. Ensure handler receives `boxes` parameter
4. Test with console.log in handler

```vue
<BoundingBoxEditor
  @update="handleUpdate"
  v-bind="otherProps"
/>

<script>
methods: {
  handleUpdate(boxes) {
    console.log('Update received:', boxes);
    // Your logic here
  }
}
</script>
```

### Issue 4: Image CORS Error

**Problem**: Canvas contaminated by cross-origin image

**Solutions**:
1. Ensure image URL is same-origin or has CORS headers
2. Add `crossorigin="anonymous"` to image element
3. Serve images from same domain as application
4. Configure S3/storage to send proper CORS headers

```javascript
// Add CORS attribute
onImageLoad(event) {
  event.target.setAttribute('crossorigin', 'anonymous');
  // ... rest of logic
}
```

---

## Summary

The `BoundingBoxEditor` component provides a flexible, reusable solution for visual damage assessment. Key integration points:

1. **Nova Admin**: Use with `DamageAssessmentEditor` field for admin workflows
2. **Customer Dashboard**: Standalone component for customer-facing features
3. **API Integration**: RESTful endpoints for CRUD operations
4. **Data Flow**: Props down (initial state), Events up (updates)

For additional support, refer to:
- [Component README](./README.md)
- [DamageType Constants](/Volumes/JS-DEV/pcrcard/app/Constants/DamageType.php)
- [DamageSeverity Constants](/Volumes/JS-DEV/pcrcard/app/Constants/DamageSeverity.php)
- [DamageAssessment Model](/Volumes/JS-DEV/pcrcard/app/Models/DamageAssessment.php)
