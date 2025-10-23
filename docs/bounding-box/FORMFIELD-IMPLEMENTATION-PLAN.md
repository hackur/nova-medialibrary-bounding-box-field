# FormField Interactive Editor Implementation Plan

**Date**: October 23, 2025
**Component**: DamageAssessmentEditor FormField.vue
**Goal**: Complete interactive bounding box editor for live damage marking
**Total Tasks**: 36 (7 completed, 29 remaining)

---

## 📊 Current Status

### ✅ Already Implemented (FormField.vue exists with 745 lines)

**Core Drawing Functionality:**
- ✅ Canvas-based drawing layer
- ✅ Mouse event handlers (mousedown, mousemove, mouseup)
- ✅ Touch event handlers (touch devices)
- ✅ Drag-to-draw bounding box creation
- ✅ Box state management (boundingBoxes array)
- ✅ Box selection on click
- ✅ Delete functionality (button + Delete key)
- ✅ Box list sidebar
- ✅ Image scaling and coordinate mapping
- ✅ Keyboard shortcuts (Delete key)

**UI Components:**
- ✅ User instructions panel
- ✅ Damage type selector dropdown
- ✅ Severity level selector dropdown
- ✅ Box list with delete buttons
- ✅ Selected box indicator
- ✅ Drawing feedback ("Drawing box #...")

**Data Management:**
- ✅ JSON serialization
- ✅ UUID generation capability (commented out)
- ✅ Original image dimensions capture
- ✅ Scale factor calculations

**Special Modes:**
- ✅ "Addressed Mode" for marking damage as fixed
- ✅ Addressed API endpoint integration

### 🚧 Missing/Incomplete Features

**Critical Missing Features:**
1. ❌ Individual box damage type/severity editing
2. ❌ Notes field per bounding box
3. ❌ UUID generation for new boxes
4. ❌ Save button implementation
5. ❌ Cancel button implementation
6. ❌ Clear All button with confirmation
7. ❌ Loading state during save
8. ❌ Error handling and validation
9. ❌ Nova field backend save handling
10. ❌ Component registration in nova-components.js
11. ❌ Asset compilation testing

**Testing:**
- ❌ Test drawing new boxes
- ❌ Test editing individual box properties
- ❌ Test save/cancel/clear all
- ❌ Verify database saves

---

## 🎯 Implementation Plan (36 Tasks)

### **Phase 1: Foundation & Seeders** (7 tasks)

**Status**: 2 in progress, 5 pending

#### Task 1.1: Copy Screenshot to README ✅ IN PROGRESS
```bash
cp .playwright-mcp/damage-assessment-7-moderate-boxes-visible.png \
   resources/js/nova/fields/damage-assessment-editor/screenshots/bounding-box-overlay-example.png
```
**Status**: Executing now

#### Task 1.2: Enhance DevDamageAssessmentsSeeder
**File**: `database/seeders/Development/DevDamageAssessmentsSeeder.php`
**Changes**:
- Add more variety in box counts (0, 1, 2, 3, 5, 8 boxes)
- Add scenarios for all 15 damage types
- Add scenarios for all 3 severity levels
- Add mixed severity scenarios

**Current Implementation**:
```php
// Scenario 1: Pristine (0 boxes)
// Scenario 2: Light (1-2 boxes)
// Scenario 3: Moderate (2-3 boxes)
// Scenario 4: Heavy (3-5 boxes)
```

**New Implementation**:
```php
// Add Scenario 5: All damage types showcase
// Add Scenario 6: All severity levels showcase
// Add Scenario 7: Mixed boxes (different types/severities)
// Add Scenario 8: Edge case (8+ boxes)
```

#### Task 1.3: Add Pristine Card Seeder Enhancement
**Ensure**: Factory has `pristine()` state method
**Verify**: 0 boxes, notes = "No damage detected"

#### Task 1.4: Add Single Damage Seeder Enhancement
**Ensure**: Factory has `lightDamage()` state
**Verify**: 1 box exactly, minor severity

#### Task 1.5: Add Multiple Damage Seeder Enhancement
**Ensure**: Factory supports `withBoxCount(n)` method
**Verify**: Can specify exact box count

#### Task 1.6: Add All Severity Levels Seeder
**Create**: New method `createAllSeveritiesShowcase()`
**Action**: Create 3 assessments, one for each severity

#### Task 1.7: Add All Damage Types Seeder
**Create**: New method `createAllDamageTypesShowcase()`
**Action**: Create 15 assessments, one for each damage type

---

### **Phase 2: FormField Enhancements** (12 tasks)

**Status**: All pending (component exists but missing features)

#### Task 2.1: Add Individual Box Property Editing
**Component**: FormField.vue
**Add**: Modal or sidebar for editing selected box
**Fields**:
- Damage type dropdown (per box)
- Severity selector (per box)
- Notes textarea (per box)

**Implementation**:
```vue
<div v-if="selectedBoxIndex !== null" class="mt-4 p-4 border rounded">
  <h5 class="font-semibold mb-2">Edit Box #{{ selectedBoxIndex + 1 }}</h5>

  <div class="space-y-3">
    <div>
      <label>Damage Type</label>
      <select v-model="boundingBoxes[selectedBoxIndex].damage_type">
        <option v-for="(label, value) in damageTypes" :value="value">
          {{ label }}
        </option>
      </select>
    </div>

    <div>
      <label>Severity</label>
      <select v-model="boundingBoxes[selectedBoxIndex].severity">
        <option v-for="(label, value) in severityLevels" :value="value">
          {{ label }}
        </option>
      </select>
    </div>

    <div>
      <label>Notes</label>
      <textarea
        v-model="boundingBoxes[selectedBoxIndex].notes"
        rows="3"
        placeholder="Describe this damage area..."
      ></textarea>
    </div>
  </div>
</div>
```

#### Task 2.2: Add UUID Generation for New Boxes
**File**: FormField.vue, method: `handleMouseUp()`
**Change**: Uncomment/implement UUID generation

**Current** (line 564-573):
```javascript
this.boundingBoxes.push({
  x: Math.round(x),
  y: Math.round(y),
  width: Math.round(width),
  height: Math.round(height),
  image_width: this.img.width,
  image_height: this.img.height,
  damage_type: this.defaultDamageType,
  severity: this.defaultSeverity
})
```

**New**:
```javascript
this.boundingBoxes.push({
  id: this.generateUUID(), // ← ADD THIS
  x: Math.round(x),
  y: Math.round(y),
  width: Math.round(width),
  height: Math.round(height),
  image_width: this.img.width,
  image_height: this.img.height,
  rotation: 0,
  damage_type: this.defaultDamageType,
  severity: this.defaultSeverity,
  notes: ''
})
```

**Add Method**:
```javascript
generateUUID() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
    const r = Math.random() * 16 | 0
    const v = c === 'x' ? r : (r & 0x3 | 0x8)
    return v.toString(16)
  })
}
```

#### Task 2.3: Add Save Button
**Location**: After box list, before closing `</DefaultField>`
**Implementation**:
```vue
<div class="mt-4 flex gap-2">
  <button
    @click="handleSave"
    type="button"
    :disabled="saving"
    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
  >
    {{ saving ? 'Saving...' : 'Save Changes' }}
  </button>

  <button
    @click="handleCancel"
    type="button"
    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
  >
    Cancel
  </button>

  <button
    @click="handleClearAll"
    type="button"
    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
  >
    Clear All
  </button>
</div>
```

#### Task 2.4: Implement Save Handler
**Add to data**:
```javascript
saving: false,
originalValue: null
```

**Add to mounted**:
```javascript
this.originalValue = JSON.parse(JSON.stringify(this.boundingBoxes))
```

**Add method**:
```javascript
async handleSave() {
  this.saving = true

  try {
    // Update form value
    this.updateValue()

    // Emit Nova form event
    this.$emit('save')

    // Update original value
    this.originalValue = JSON.parse(JSON.stringify(this.boundingBoxes))

    Nova.success('Damage areas saved successfully')
  } catch (error) {
    Nova.error('Failed to save damage areas')
    console.error(error)
  } finally {
    this.saving = false
  }
}
```

#### Task 2.5: Implement Cancel Handler
**Add method**:
```javascript
handleCancel() {
  if (confirm('Discard all changes?')) {
    this.boundingBoxes = JSON.parse(JSON.stringify(this.originalValue))
    this.selectedBoxIndex = null
    this.updateValue()
    this.redrawCanvas()
    Nova.info('Changes discarded')
  }
}
```

#### Task 2.6: Implement Clear All Handler
**Add method**:
```javascript
handleClearAll() {
  if (!confirm(`Delete all ${this.boundingBoxes.length} damage areas?`)) {
    return
  }

  this.boundingBoxes = []
  this.selectedBoxIndex = null
  this.updateValue()
  this.redrawCanvas()
  Nova.info('All damage areas cleared')
}
```

#### Task 2.7: Add Loading State During Operations
**Add to data**:
```javascript
loading: false
```

**Add to template**:
```vue
<div v-if="loading" class="absolute inset-0 bg-white/80 flex items-center justify-center">
  <div class="text-center">
    <div class="spinner mb-2"></div>
    <p class="text-sm text-gray-600">Loading...</p>
  </div>
</div>
```

**Add to style**:
```css
.spinner {
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
```

#### Task 2.8: Add Validation
**Add method**:
```javascript
validateBoxes() {
  const errors = []

  this.boundingBoxes.forEach((box, index) => {
    if (!box.id) {
      errors.push(`Box #${index + 1}: Missing ID`)
    }

    if (box.width <= 0 || box.height <= 0) {
      errors.push(`Box #${index + 1}: Invalid dimensions`)
    }

    if (!box.damage_type || !this.damageTypes[box.damage_type]) {
      errors.push(`Box #${index + 1}: Invalid damage type`)
    }

    if (!box.severity || !this.severityLevels[box.severity]) {
      errors.push(`Box #${index + 1}: Invalid severity`)
    }
  })

  return errors
}
```

**Use in handleSave**:
```javascript
const errors = this.validateBoxes()
if (errors.length > 0) {
  Nova.error(`Validation failed:\\n${errors.join('\\n')}`)
  return
}
```

#### Task 2.9: Add Error Handling
**Wrap all async operations** in try/catch
**Show user-friendly messages** via Nova.error()
**Log errors** to console for debugging

#### Task 2.10: Improve Box List UI
**Add**:
- Color-coded boxes by severity
- Damage type icons
- Edit icon/button
- Hover effects

#### Task 2.11: Add Esc Key Handler
**Add to handleKeyDown**:
```javascript
if (e.key === 'Escape') {
  // Deselect box
  this.selectedBoxIndex = null
  this.redrawCanvas()

  // Cancel drawing if active
  if (this.drawingBox.active) {
    this.drawingBox.active = false
    this.isDrawing = false
    this.mouse.down = false
    this.redrawCanvas()
  }
}
```

#### Task 2.12: Add Undo/Redo (Bonus)
**Implementation**:
```javascript
data() {
  return {
    history: [],
    historyIndex: -1,
    maxHistory: 50
  }
}

methods: {
  pushHistory() {
    // Remove any future history
    this.history = this.history.slice(0, this.historyIndex + 1)

    // Add current state
    this.history.push(JSON.parse(JSON.stringify(this.boundingBoxes)))
    this.historyIndex++

    // Limit history size
    if (this.history.length > this.maxHistory) {
      this.history.shift()
      this.historyIndex--
    }
  },

  undo() {
    if (this.historyIndex > 0) {
      this.historyIndex--
      this.boundingBoxes = JSON.parse(JSON.stringify(this.history[this.historyIndex]))
      this.updateValue()
      this.redrawCanvas()
    }
  },

  redo() {
    if (this.historyIndex < this.history.length - 1) {
      this.historyIndex++
      this.boundingBoxes = JSON.parse(JSON.stringify(this.history[this.historyIndex]))
      this.updateValue()
      this.redrawCanvas()
    }
  }
}
```

---

### **Phase 3: Backend Integration** (4 tasks)

**Status**: All pending

#### Task 3.1: Update Nova Field Backend
**File**: `app/Nova/Fields/DamageAssessmentEditor.php`
**Verify**: `fillAttributeFromRequest()` handles JSON correctly

**Current Implementation** (lines 287-309):
```php
protected function fillAttributeFromRequest(
    \Laravel\Nova\Http\Requests\NovaRequest $request,
    $requestAttribute,
    $model,
    $attribute
) {
    if ($request->exists($requestAttribute)) {
        $value = $request[$requestAttribute];
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (! is_array($value)) {
            $value = [];
        }
        $model->{$attribute} = $value;
    }
}
```

**Verify**: This implementation is correct ✅

**Add Validation**:
```php
// Validate each box has required fields
$validatedBoxes = array_filter($value, function ($box) {
    return isset($box['id'], $box['x'], $box['y'], $box['width'], $box['height']);
});

$model->{$attribute} = array_values($validatedBoxes);
```

#### Task 3.2: Add API Route for Save (if needed)
**File**: `routes/web.php` or `routes/api.php`
**Check**: Does Nova form submission handle this automatically?
**Answer**: Yes, Nova handles it via standard form POST

#### Task 3.3: Test Model Accessor/Mutator
**File**: `app/Models/DamageAssessment.php`
**Verify**: `getBoundingBoxesAttribute()` validates correctly
**Verify**: `setBoundingBoxesAttribute()` handles JSON

**Current Implementation** (lines 99-169):
```php
public function getBoundingBoxesAttribute($value): array
{
    // Validates each box has required fields
    // Filters out invalid boxes
    // Returns clean array
}

public function setBoundingBoxesAttribute($value): void
{
    // Auto-generates UUIDs for boxes missing IDs
    // Ensures numeric values
    // Stores as JSON
}
```

**Status**: ✅ Already implemented correctly

#### Task 3.4: Add Backend Validation Rules
**File**: `app/Nova/DamageAssessment.php`
**Add**: Validation rules for bounding_boxes field

**Implementation**:
```php
use Laravel\Nova\Fields\FormData;

DamageAssessmentEditor::make('Damage Areas', 'bounding_boxes')
    ->rules('array')
    ->rules(function ($attribute, $value, $fail) {
        if (!is_array($value)) {
            $fail('Damage areas must be an array');
        }

        foreach ($value as $index => $box) {
            if (!isset($box['id'])) {
                $fail("Box #{$index}: Missing ID");
            }

            if (!isset($box['x'], $box['y'], $box['width'], $box['height'])) {
                $fail("Box #{$index}: Missing coordinates");
            }

            if ($box['width'] <= 0 || $box['height'] <= 0) {
                $fail("Box #{$index}: Invalid dimensions");
            }
        }
    })
```

---

### **Phase 4: Component Registration & Build** (3 tasks)

**Status**: All pending

#### Task 4.1: Register FormField in nova-components.js
**File**: `resources/js/nova-components.js`
**Current** (lines 56-60):
```javascript
// Import Damage Assessment Editor field components
import DamageAssessmentEditorIndexField from './nova/fields/damage-assessment-editor/IndexField.vue';
import DamageAssessmentEditorDetailField from './nova/fields/damage-assessment-editor/DetailField.vue';
import DamageAssessmentEditorFormField from './nova/fields/damage-assessment-editor/FormField.vue';
```

**Check**: ✅ Already imported

**Current** (lines 138-141):
```javascript
// Register Damage Assessment Editor field components
Nova.component('index-damage-assessment-editor', DamageAssessmentEditorIndexField);
Nova.component('detail-damage-assessment-editor', DamageAssessmentEditorDetailField);
Nova.component('form-damage-assessment-editor', DamageAssessmentEditorFormField);
```

**Check**: ✅ Already registered

#### Task 4.2: Build Assets with Laravel Mix
**Command**:
```bash
npm run dev
```

**Verify**:
- No compilation errors
- File size reasonable (~600KB expected)
- Source maps generated

**Production Build**:
```bash
npm run build
```

#### Task 4.3: Test Asset Loading
**Steps**:
1. Clear browser cache
2. Navigate to Nova damage assessment edit page
3. Check browser console for errors
4. Verify component loads

---

### **Phase 5: Testing & Verification** (10 tasks)

**Status**: All pending

#### Task 5.1: Test Drawing New Boxes
**Steps**:
1. Navigate to damage assessment create/edit
2. Click and drag on image
3. Verify box appears
4. Verify box has default damage type/severity
5. Verify box added to list

**Test Cases**:
- Draw 1 box
- Draw 3 boxes
- Draw 10+ boxes
- Draw very small box (should reject < 10px)
- Draw very large box (full image)

#### Task 5.2: Test Box Selection
**Steps**:
1. Create 3 boxes
2. Click on each box
3. Verify selection highlight
4. Verify box details shown
5. Click outside to deselect

#### Task 5.3: Test Box Editing
**Steps**:
1. Create box
2. Select box
3. Change damage type
4. Change severity
5. Add notes
6. Verify changes reflected

#### Task 5.4: Test Box Deletion
**Steps**:
1. Create 5 boxes
2. Select box #3
3. Click delete button
4. Verify box removed
5. Verify remaining boxes renumbered

**Test Delete Key**:
1. Select box
2. Press Delete key
3. Verify box removed

#### Task 5.5: Test Save Functionality
**Steps**:
1. Create 3 boxes
2. Edit properties
3. Click Save
4. Verify success message
5. Reload page
6. Verify boxes persist

#### Task 5.6: Test Cancel Functionality
**Steps**:
1. Load existing assessment
2. Add 2 new boxes
3. Delete 1 existing box
4. Click Cancel
5. Verify changes discarded
6. Verify original state restored

#### Task 5.7: Test Clear All
**Steps**:
1. Create 5 boxes
2. Click Clear All
3. Confirm dialog
4. Verify all boxes removed
5. Test cancel dialog

#### Task 5.8: Test Keyboard Shortcuts
**Shortcuts to Test**:
- Delete key: Remove selected box
- Esc key: Cancel drawing / Deselect
- (Future) Ctrl+Z: Undo
- (Future) Ctrl+Shift+Z: Redo

#### Task 5.9: Test Error Handling
**Error Scenarios**:
1. No image URL
2. Invalid image URL
3. Network error during save
4. Invalid JSON in field value
5. Missing required fields

#### Task 5.10: Verify Database Saves
**Steps**:
1. Create damage assessment via Nova
2. Draw 3 boxes
3. Add notes to each
4. Save
5. Check database:
```sql
SELECT id, damage_type, severity, bounding_boxes
FROM damage_assessments
WHERE id = X;
```
6. Verify JSON structure
7. Verify all fields present

---

## 🎯 Execution Strategy

### Parallel Execution Groups

**Group A: Seeders** (can run independently)
- Task 1.2-1.7

**Group B: FormField Frontend** (dependent chain)
- Task 2.1-2.12

**Group C: Backend** (can run parallel to B)
- Task 3.1-3.4

**Group D: Build** (depends on B complete)
- Task 4.1-4.3

**Group E: Testing** (depends on all above)
- Task 5.1-5.10

### Execution Order

```
START
  ↓
[Group A: Seeders] ← Run in background
  ↓
[Group B: FormField] + [Group C: Backend] ← Run in parallel
  ↓
[Group D: Build]
  ↓
[Group E: Testing]
  ↓
DONE
```

---

## 📝 Success Criteria

### Functional Requirements
- [ ] Can draw unlimited bounding boxes on image
- [ ] Each box has UUID, coordinates, damage_type, severity, notes
- [ ] Can select and delete boxes
- [ ] Can edit individual box properties
- [ ] Save/Cancel/Clear All buttons work
- [ ] Keyboard shortcuts functional
- [ ] Changes persist to database
- [ ] Validation prevents invalid data

### Technical Requirements
- [ ] No Vue errors in console
- [ ] No compilation errors
- [ ] JSON validates on backend
- [ ] Model accessor/mutator work correctly
- [ ] Assets build successfully
- [ ] Performance acceptable (< 100ms canvas redraws)

### UX Requirements
- [ ] Clear instructions for users
- [ ] Intuitive drawing interface
- [ ] Visual feedback during operations
- [ ] Error messages helpful
- [ ] Loading states shown
- [ ] Responsive design works

---

## 📊 Estimated Timeline

- **Group A (Seeders)**: 1 hour
- **Group B (FormField)**: 3-4 hours
- **Group C (Backend)**: 1 hour
- **Group D (Build)**: 30 minutes
- **Group E (Testing)**: 2-3 hours

**Total**: 7-9 hours of development + testing

---

## 🚀 Next Steps

1. Execute Group A (Seeders) in background
2. Start Group B (FormField enhancements) - highest priority
3. Execute Group C (Backend validation) in parallel
4. Build and test
5. Document findings

**Ready to begin execution!**
