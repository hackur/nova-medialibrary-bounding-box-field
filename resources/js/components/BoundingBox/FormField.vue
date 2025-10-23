<template>
    <div class="damage-assessment-editor-form-field">
        <div class="damage-assessment-editor-wrapper" v-if="imageUrl">
                <!-- User Instructions Panel -->
                <!-- Provides clear guidance for technicians on how to use the visual damage marking tool -->
                <div class="mb-4" v-if="!addressedMode">
                    <div class="p-3 bg-blue-50 rounded text-sm">
                        <p class="font-semibold text-blue-900 mb-1">How to mark damage:</p>
                        <ul class="text-blue-700 space-y-1">
                            <li>• Click and drag on the image to draw a box around damage</li>
                            <li>• Draw multiple boxes to mark all damaged areas</li>
                            <li>• Click on a box to select it, then press Delete to remove</li>
                            <li>• All boxes will be saved with the selected damage type and severity</li>
                        </ul>
                    </div>
                    
                    <!-- Damage Type and Severity Selection -->
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Default Damage Type
                            </label>
                            <select 
                                v-model="defaultDamageType"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option 
                                    v-for="(label, value) in damageTypes" 
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Default Severity
                            </label>
                            <select 
                                v-model="defaultSeverity"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option 
                                    v-for="(label, value) in severityLevels" 
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Addressed Mode Instructions -->
                <div class="mb-4 p-3 bg-green-50 rounded text-sm" v-if="addressedMode">
                    <p class="font-semibold text-green-900 mb-1">Mark damage as addressed:</p>
                    <ul class="text-green-700 space-y-1">
                        <li>• Click on damage markers to mark them as addressed</li>
                        <li>• Addressed damage will show in green</li>
                        <li>• Changes are saved automatically</li>
                    </ul>
                </div>

                <div class="canvas-container relative inline-block">
                    <canvas
                        :width="canvasWidth"
                        :height="canvasHeight"
                        @mousedown="handleMouseDown"
                        @mouseup="handleMouseUp"
                        @mousemove="handleMouseMove"
                        @touchstart="handleTouchStart"
                        @touchend="handleTouchEnd"
                        @touchmove="handleTouchMove"
                        @click="handleCanvasClick"
                        class="border border-gray-300 rounded cursor-crosshair"
                    ></canvas>
                    
                    <!-- Current Drawing Info -->
                    <div v-if="isDrawing" class="absolute top-2 left-2 bg-white/90 p-2 rounded shadow-lg pointer-events-none">
                        <p class="text-sm font-semibold">Drawing box #{{ boundingBoxes.length + 1 }}</p>
                    </div>
                </div>
                
                <!-- List of drawn boxes (normal mode) -->
                <div v-if="boundingBoxes.length > 0 && !addressedMode" class="mt-4">
                    <h4 class="text-sm font-semibold mb-2">
                        Marked Damage Areas ({{ boundingBoxes.length }} box{{ boundingBoxes.length !== 1 ? 'es' : '' }})
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        <div 
                            v-for="(box, index) in boundingBoxes" 
                            :key="index"
                            class="relative bg-gray-50 p-3 rounded border-2 transition-all cursor-pointer"
                            :class="selectedBoxIndex === index ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                            @click="selectBox(index)"
                        >
                            <div class="flex items-start justify-between">
                                <div class="text-sm">
                                    <span class="font-medium">Box #{{ index + 1 }}</span>
                                    <div class="text-xs text-gray-600 mt-1">
                                        Type: {{ formatDamageType(box.damage_type || 'scratch') }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Severity: {{ box.severity || 'moderate' }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Position: {{ Math.round(box.x) }}, {{ Math.round(box.y) }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Size: {{ Math.round(box.width) }} × {{ Math.round(box.height) }}px
                                    </div>
                                </div>
                                <button 
                                    @click.stop="removeBox(index)"
                                    type="button"
                                    class="text-red-500 hover:text-red-700 ml-2"
                                    title="Remove this box"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-2 text-xs text-gray-500">
                        <span v-if="selectedBoxIndex !== null">
                            Box #{{ selectedBoxIndex + 1 }} selected - Press Delete key to remove
                        </span>
                    </div>
                </div>

                <!-- Individual Box Editor Panel -->
                <div v-if="!addressedMode && selectedBoxIndex !== null && boundingBoxes[selectedBoxIndex]"
                     class="mt-4 p-4 border-2 border-blue-500 rounded-lg bg-blue-50">
                    <h5 class="font-semibold mb-3 text-blue-900 flex items-center justify-between">
                        <span>Edit Box #{{ selectedBoxIndex + 1 }}</span>
                        <button
                            @click="selectedBoxIndex = null; redrawCanvas()"
                            type="button"
                            class="text-gray-500 hover:text-gray-700 text-sm"
                            title="Close editor"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </h5>

                    <div class="space-y-3">
                        <!-- Damage Type Selector -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Damage Type
                            </label>
                            <select
                                v-model="boundingBoxes[selectedBoxIndex].damage_type"
                                @change="redrawCanvas"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option v-for="(label, value) in damageTypes" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <!-- Severity Selector -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Severity
                            </label>
                            <select
                                v-model="boundingBoxes[selectedBoxIndex].severity"
                                @change="redrawCanvas"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option v-for="(label, value) in severityLevels" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <!-- Notes Textarea -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Notes
                            </label>
                            <textarea
                                v-model="boundingBoxes[selectedBoxIndex].notes"
                                rows="3"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Describe this damage area in detail..."
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ (boundingBoxes[selectedBoxIndex].notes || '').length }} characters
                            </p>
                        </div>

                        <!-- Box Coordinates Info -->
                        <div class="pt-2 border-t border-blue-200">
                            <p class="text-xs text-gray-600">
                                <strong>Position:</strong> ({{ Math.round(boundingBoxes[selectedBoxIndex].x) }}, {{ Math.round(boundingBoxes[selectedBoxIndex].y) }})
                                <br>
                                <strong>Size:</strong> {{ Math.round(boundingBoxes[selectedBoxIndex].width) }} × {{ Math.round(boundingBoxes[selectedBoxIndex].height) }}px
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Damage Assessments List (addressed mode) -->
                <div v-if="addressedMode && damageAssessments.length > 0" class="mt-4">
                    <h4 class="text-sm font-semibold mb-2">
                        Damage Assessments ({{ damageAssessments.filter(a => a.addressed).length }}/{{ damageAssessments.length }} addressed)
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div 
                            v-for="(assessment, index) in damageAssessments" 
                            :key="assessment.id || index"
                            class="relative p-3 rounded border-2 transition-all cursor-pointer"
                            :class="assessment.addressed ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300'"
                            @click="toggleAddressed(assessment)"
                        >
                            <div class="flex items-start justify-between">
                                <div class="text-sm">
                                    <span class="font-medium">Assessment #{{ index + 1 }}</span>
                                    <div class="text-xs text-gray-600 mt-1">
                                        Type: {{ formatDamageType(assessment.damage_type) }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Severity: {{ assessment.severity }}
                                    </div>
                                    <div v-if="assessment.notes" class="text-xs text-gray-600 mt-1">
                                        {{ assessment.notes }}
                                    </div>
                                </div>
                                <div class="ml-2">
                                    <span v-if="assessment.addressed" class="text-green-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                    <span v-else class="text-red-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div v-if="!addressedMode" class="mt-6 flex gap-3">
                    <button
                        @click="handleSave"
                        type="button"
                        :disabled="saving"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md shadow-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                    >
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </button>

                    <button
                        @click="handleCancel"
                        type="button"
                        :disabled="saving"
                        class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md shadow-sm hover:bg-gray-400 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                    >
                        Cancel
                    </button>

                    <button
                        @click="handleClearAll"
                        type="button"
                        :disabled="saving || boundingBoxes.length === 0"
                        class="px-6 py-2 bg-red-600 text-white rounded-md shadow-sm hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
                    >
                        Clear All ({{ boundingBoxes.length }})
                    </button>
                </div>

                <input type="hidden" :value="encodedValue" />
            </div>

        <div v-else class="text-gray-500 italic">
            No image available for damage assessment
        </div>
    </div>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova'

/**
 * DamageAssessmentEditor Form Field Component
 * 
 * Interactive canvas-based damage marking tool for PCR Card's damage assessment system.
 * Allows technicians to visually mark multiple damage areas on card images by drawing
 * bounding boxes. Supports touch and mouse input for cross-device compatibility.
 * 
 * Key Features:
 * - Multi-box drawing: Mark multiple related damage areas in one assessment
 * - Interactive selection: Click boxes to select, Delete key to remove
 * - Responsive scaling: Automatically fits images to canvas while preserving coordinates
 * - Real-time feedback: Visual indicators for drawing and selection states
 * - Persistent storage: Saves box coordinates relative to original image dimensions
 * 
 * Data Structure:
 * Each bounding box contains:
 * - x, y: Top-left corner coordinates
 * - width, height: Box dimensions
 * - image_width, image_height: Original image dimensions for scaling
 * 
 * @component
 * @mixes FormField - Laravel Nova form field functionality
 * @mixes HandlesValidationErrors - Error handling capabilities
 */
export default {
    mixins: [FormField, HandlesValidationErrors],
    
    props: ['resourceName', 'resourceId', 'field'],
    
    data() {
        return {
            // Image data
            imageUrl: null,          // URL of the card image to annotate
            img: null,               // HTML Image element for canvas drawing
            
            // Canvas dimensions
            canvasWidth: 800,        // Maximum canvas width
            canvasHeight: 600,       // Maximum canvas height
            
            // Mouse tracking for drawing
            mouse: {
                current: { x: 0, y: 0 }, // Current mouse position
                down: false              // Mouse button state
            },
            
            // Current drawing box state
            drawingBox: {
                active: false,       // Whether currently drawing
                startX: 0,          // Starting X coordinate
                startY: 0,          // Starting Y coordinate
                width: 0,           // Current width
                height: 0           // Current height
            },
            
            // Damage area storage
            boundingBoxes: [],       // Array of completed bounding boxes
            isDrawing: false,        // UI state flag for drawing feedback
            selectedBoxIndex: null,  // Index of currently selected box
            scale: 1,               // Image scale factor for canvas fit
            
            // Addressed mode
            addressedMode: false,    // Whether in addressed marking mode
            addressedEndpoint: null, // API endpoint for marking as addressed
            damageAssessments: [],   // Existing damage assessments to display
            
            // Default damage values for new boxes
            defaultDamageType: 'scratch',
            defaultSeverity: 'moderate',

            // Save state management
            saving: false,
            originalValue: null,

            // Canvas element reference (stored in reactive data to avoid Vue refs incompatibility)
            canvasElement: null
        }
    },
    
    computed: {
        encodedValue() {
            return JSON.stringify(this.boundingBoxes)
        },
        
        damageTypes() {
            // Backend sends array of objects with {value, label, color, icon}
            // Convert to simple {value: label} object for v-for iteration and validation
            if (Array.isArray(this.field.damageTypes)) {
                return this.field.damageTypes.reduce((acc, type) => {
                    acc[type.value] = type.label
                    return acc
                }, {})
            }

            // Fallback to default values if not provided
            return this.field.damageTypes || {
                'scratch': 'Scratch',
                'crease': 'Crease',
                'bend': 'Bend',
                'indent': 'Indent',
                'edge_lift': 'Edge Lift',
                'dirt': 'Dirt/Grime'
            }
        },

        severityLevels() {
            // Backend sends array of objects with {value, label, color, priority}
            // Convert to simple {value: label} object for v-for iteration and validation
            if (Array.isArray(this.field.severityLevels)) {
                return this.field.severityLevels.reduce((acc, severity) => {
                    acc[severity.value] = severity.label
                    return acc
                }, {})
            }

            // Fallback to default values if not provided
            return this.field.severityLevels || {
                'minor': 'Minor',
                'moderate': 'Moderate',
                'severe': 'Severe'
            }
        }
    },
    
    mounted() {
        this.imageUrl = this.field.imageUrl
        this.addressedMode = this.field.addressedMode || false
        this.addressedEndpoint = this.field.addressedEndpoint || null
        this.damageAssessments = this.field.damageAssessments || []
        
        if (this.field.value && typeof this.field.value === 'string') {
            try {
                this.boundingBoxes = JSON.parse(this.field.value)
            } catch (e) {
                this.boundingBoxes = []
            }
        }
        
        // Capture canvas element reference (avoiding Vue template refs incompatibility)
        this.$nextTick(() => {
            this.canvasElement = this.$el.querySelector('canvas')
        })

        if (this.imageUrl) {
            this.loadImage()
        }

        // Store original value for cancel functionality
        this.originalValue = JSON.parse(JSON.stringify(this.boundingBoxes))

        // Add keyboard event listener for Delete key
        window.addEventListener('keydown', this.handleKeyDown)
    },
    
    beforeUnmount() {
        window.removeEventListener('keydown', this.handleKeyDown)
    },
    
    methods: {
        /**
         * Initialize field value from Nova
         * Sets up the initial bounding boxes array
         */
        setInitialValue() {
            this.value = this.field.value || '[]'
        },
        
        /**
         * Prepare data for form submission
         * Serializes bounding boxes array to JSON string
         * 
         * @param {FormData} formData - Nova form data object
         */
        fill(formData) {
            formData.append(this.field.attribute, this.value || '[]')
        },
        
        /**
         * Load and scale the card image for canvas display
         * Calculates appropriate scale factor to fit image within max dimensions
         * while preserving aspect ratio and coordinate accuracy
         */
        loadImage() {
            const img = new Image()
            img.onload = () => {
                this.img = img

                // Use original image proportions for high-res bounding box extraction
                // This ensures coordinates map correctly to the full-resolution image
                const preserveOriginal = this.field.preserveOriginalProportions !== false

                if (preserveOriginal) {
                    // No scaling - use original dimensions
                    this.scale = 1
                    this.canvasWidth = img.width
                    this.canvasHeight = img.height
                } else {
                    // Legacy scaling behavior
                    const maxWidth = 800
                    const maxHeight = 600

                    let scale = 1
                    if (img.width > maxWidth || img.height > maxHeight) {
                        scale = Math.min(maxWidth / img.width, maxHeight / img.height)
                    }

                    this.scale = scale
                    this.canvasWidth = img.width * scale
                    this.canvasHeight = img.height * scale
                }

                // Redraw canvas after DOM updates
                this.$nextTick(() => {
                    this.redrawCanvas()
                })
            }
            img.src = this.imageUrl
        },
        
        redrawCanvas() {
            const canvas = this.canvasElement
            if (!canvas || !this.img) return
            
            const ctx = canvas.getContext('2d')
            
            // Clear canvas
            ctx.clearRect(0, 0, this.canvasWidth, this.canvasHeight)
            
            // Draw image
            ctx.drawImage(this.img, 0, 0, this.canvasWidth, this.canvasHeight)
            
            // In addressed mode, draw damage assessments
            if (this.addressedMode && this.damageAssessments.length > 0) {
                this.damageAssessments.forEach((assessment, assessmentIndex) => {
                    const boxes = assessment.bounding_boxes || []
                    boxes.forEach((box, boxIndex) => {
                        if (!box) return
                        
                        // Different colors for addressed vs unaddressed
                        const color = assessment.addressed ? '#10b981' : '#ef4444' // green : red
                        
                        ctx.strokeStyle = color
                        ctx.lineWidth = 3
                        ctx.strokeRect(
                            box.x * this.scale,
                            box.y * this.scale,
                            box.width * this.scale,
                            box.height * this.scale
                        )
                        
                        // Semi-transparent fill
                        ctx.fillStyle = color + '20'
                        ctx.fillRect(
                            box.x * this.scale,
                            box.y * this.scale,
                            box.width * this.scale,
                            box.height * this.scale
                        )
                        
                        // Label
                        if (boxIndex === 0) {
                            ctx.fillStyle = color
                            ctx.font = 'bold 14px sans-serif'
                            ctx.fillText(
                                `#${assessmentIndex + 1} ${assessment.addressed ? '✓' : ''}`,
                                box.x * this.scale + 4,
                                box.y * this.scale + 18
                            )
                        }
                    })
                })
            } else {
                // Normal mode - draw bounding boxes for new damage marking
                this.boundingBoxes.forEach((box, index) => {
                    const isSelected = index === this.selectedBoxIndex
                    this.drawBox(ctx, box, false, isSelected, index)
                })
            }
            
            // Draw current rectangle if drawing
            if (this.drawingBox.active && !this.addressedMode) {
                this.drawBox(ctx, {
                    x: this.drawingBox.startX,
                    y: this.drawingBox.startY,
                    width: this.drawingBox.width,
                    height: this.drawingBox.height
                }, true)
            }
        },
        
        drawBox(ctx, box, isDrawing = false, isSelected = false, index = null) {
            // Use different color for selected boxes
            if (isSelected) {
                ctx.strokeStyle = '#3b82f6' // blue
                ctx.lineWidth = 3
            } else if (isDrawing) {
                ctx.strokeStyle = '#6b7280' // gray
                ctx.lineWidth = 2
                ctx.setLineDash([5, 5])
            } else {
                ctx.strokeStyle = '#374151' // dark gray
                ctx.lineWidth = 2
                ctx.setLineDash([])
            }
            
            ctx.strokeRect(
                box.x * this.scale,
                box.y * this.scale,
                box.width * this.scale,
                box.height * this.scale
            )
            
            // Draw box number if not drawing
            if (!isDrawing && index !== null) {
                // Draw background for number
                const text = `#${index + 1}`
                ctx.font = 'bold 12px sans-serif'
                const metrics = ctx.measureText(text)
                
                ctx.fillStyle = isSelected ? '#3b82f6' : '#374151'
                ctx.fillRect(
                    box.x * this.scale,
                    box.y * this.scale - 20,
                    metrics.width + 8,
                    18
                )
                
                // Draw number
                ctx.fillStyle = '#ffffff'
                ctx.fillText(
                    text,
                    box.x * this.scale + 4,
                    box.y * this.scale - 6
                )
            }
            
            // Reset line dash
            ctx.setLineDash([])
        },
        
        getMousePos(e) {
            const canvas = this.canvasElement
            const rect = canvas.getBoundingClientRect()
            
            const clientX = e.touches ? e.touches[0].clientX : e.clientX
            const clientY = e.touches ? e.touches[0].clientY : e.clientY
            
            return {
                x: (clientX - rect.left) / this.scale,
                y: (clientY - rect.top) / this.scale
            }
        },
        
        handleMouseDown(e) {
            // Don't allow drawing in addressed mode
            if (this.addressedMode) return
            
            const pos = this.getMousePos(e)

            // Check if clicking on the canvas (not on UI elements)
            if (e.target === this.canvasElement) {
                // Deselect any selected box
                this.selectedBoxIndex = null
                
                // Start drawing new box
                this.drawingBox = {
                    active: true,
                    startX: pos.x,
                    startY: pos.y,
                    width: 0,
                    height: 0
                }
                this.isDrawing = true
                this.mouse.down = true
            }
        },
        
        handleMouseMove(e) {
            if (!this.mouse.down || !this.drawingBox.active) return
            
            const pos = this.getMousePos(e)
            
            this.drawingBox.width = pos.x - this.drawingBox.startX
            this.drawingBox.height = pos.y - this.drawingBox.startY
            
            this.redrawCanvas()
        },
        
        handleMouseUp(e) {
            if (!this.mouse.down || !this.drawingBox.active) return
            
            this.mouse.down = false
            this.isDrawing = false
            
            // Only save if box has meaningful size
            if (Math.abs(this.drawingBox.width) > 10 && Math.abs(this.drawingBox.height) > 10) {
                // Normalize rectangle (handle negative width/height)
                const x = this.drawingBox.width < 0 ? 
                    this.drawingBox.startX + this.drawingBox.width : 
                    this.drawingBox.startX
                const y = this.drawingBox.height < 0 ? 
                    this.drawingBox.startY + this.drawingBox.height : 
                    this.drawingBox.startY
                const width = Math.abs(this.drawingBox.width)
                const height = Math.abs(this.drawingBox.height)
                
                this.boundingBoxes.push({
                    id: this.generateUUID(),
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
                
                this.updateValue()
            }
            
            // Reset drawing box
            this.drawingBox = {
                active: false,
                startX: 0,
                startY: 0,
                width: 0,
                height: 0
            }
            
            this.redrawCanvas()
        },
        
        // Touch event handlers
        handleTouchStart(e) {
            // Only prevent default if we're actually drawing (not just tapping)
            if (e.target === this.canvasElement && !this.addressedMode) {
                e.preventDefault()
            }
            this.handleMouseDown(e)
        },

        handleTouchMove(e) {
            // Only prevent default when actively drawing to avoid scroll blocking
            if (this.mouse.down && this.drawingBox.active) {
                e.preventDefault()
            }
            this.handleMouseMove(e)
        },

        handleTouchEnd(e) {
            // Only prevent default if we were drawing
            if (this.mouse.down && this.drawingBox.active) {
                e.preventDefault()
            }
            this.handleMouseUp(e)
        },
        
        removeBox(index) {
            this.boundingBoxes.splice(index, 1)
            if (this.selectedBoxIndex === index) {
                this.selectedBoxIndex = null
            } else if (this.selectedBoxIndex > index) {
                this.selectedBoxIndex--
            }
            this.updateValue()
            this.redrawCanvas()
        },
        
        selectBox(index) {
            this.selectedBoxIndex = index
            this.redrawCanvas()
        },
        
        handleCanvasClick(e) {
            const pos = this.getMousePos(e)
            
            // In addressed mode, check if we clicked on a damage assessment
            if (this.addressedMode && this.damageAssessments.length > 0) {
                for (let i = 0; i < this.damageAssessments.length; i++) {
                    const assessment = this.damageAssessments[i]
                    const boxes = assessment.bounding_boxes || []
                    
                    for (const box of boxes) {
                        if (!box) continue
                        
                        if (pos.x >= box.x && pos.x <= box.x + box.width &&
                            pos.y >= box.y && pos.y <= box.y + box.height) {
                            // Toggle addressed status
                            this.toggleAddressed(assessment)
                            return
                        }
                    }
                }
            } else {
                // Normal mode - check if we clicked on a box for selection
                for (let i = this.boundingBoxes.length - 1; i >= 0; i--) {
                    const box = this.boundingBoxes[i]
                    if (pos.x >= box.x && pos.x <= box.x + box.width &&
                        pos.y >= box.y && pos.y <= box.y + box.height) {
                        this.selectBox(i)
                        return
                    }
                }
                
                // If no box was clicked, deselect
                this.selectedBoxIndex = null
                this.redrawCanvas()
            }
        },
        
        handleKeyDown(e) {
            // Delete selected box when Delete key is pressed
            if (e.key === 'Delete' && this.selectedBoxIndex !== null && !this.addressedMode) {
                e.preventDefault()
                this.removeBox(this.selectedBoxIndex)
                return
            }

            // Escape key: Deselect box or cancel drawing
            if (e.key === 'Escape') {
                e.preventDefault()

                // Cancel active drawing
                if (this.drawingBox.active) {
                    this.drawingBox.active = false
                    this.isDrawing = false
                    this.mouse.down = false
                    this.redrawCanvas()
                    Nova.info('Drawing cancelled')
                    return
                }

                // Deselect box
                if (this.selectedBoxIndex !== null) {
                    this.selectedBoxIndex = null
                    this.redrawCanvas()
                    return
                }
            }
        },
        
        updateValue() {
            this.value = JSON.stringify(this.boundingBoxes)
            this.$emit('input', this.value)
        },
        
        /**
         * Toggle addressed status for a damage assessment
         * Makes API call to update the backend
         */
        async toggleAddressed(assessment) {
            if (!this.addressedEndpoint || !assessment.id) return
            
            // Optimistically update UI
            assessment.addressed = !assessment.addressed
            this.redrawCanvas()
            
            try {
                // Make API call to update addressed status
                const endpoint = this.addressedEndpoint.replace('{id}', assessment.id)
                const response = await Nova.request().put(endpoint, {
                    addressed_notes: assessment.addressed ? 'Marked as addressed via visual editor' : null
                })
                
                if (response.data && response.data.assessment) {
                    // Update with server response
                    assessment.addressed = response.data.assessment.addressed
                    assessment.addressed_at = response.data.assessment.addressed_at
                }
                
                Nova.success(assessment.addressed ? 'Damage marked as addressed' : 'Damage marked as unaddressed')
            } catch (error) {
                // Revert on error
                assessment.addressed = !assessment.addressed
                this.redrawCanvas()
                
                Nova.error('Failed to update damage status')
                // Error updating damage assessment
            }
        },
        
        /**
         * Generate UUID for new bounding boxes
         *
         * @returns {string} UUID v4 string
         */
        generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
                const r = Math.random() * 16 | 0
                const v = c === 'x' ? r : (r & 0x3 | 0x8)
                return v.toString(16)
            })
        },

        /**
         * Handle Save button click
         * Validates and saves all bounding boxes
         */
        async handleSave() {
            // Validate boxes before saving
            const errors = this.validateBoxes()
            if (errors.length > 0) {
                Nova.error(`Validation failed:\n${errors.join('\n')}`)
                return
            }

            this.saving = true

            try {
                // Update form value
                this.updateValue()

                // Update original value (mark as saved)
                this.originalValue = JSON.parse(JSON.stringify(this.boundingBoxes))

                Nova.success(`Saved ${this.boundingBoxes.length} damage area${this.boundingBoxes.length !== 1 ? 's' : ''}`)
            } catch (error) {
                Nova.error('Failed to save damage areas')
                console.error('Save error:', error)
            } finally {
                this.saving = false
            }
        },

        /**
         * Handle Cancel button click
         * Reverts all changes to original state
         */
        handleCancel() {
            const hasChanges = JSON.stringify(this.boundingBoxes) !== JSON.stringify(this.originalValue)

            if (!hasChanges) {
                Nova.info('No changes to discard')
                return
            }

            if (!confirm('Discard all unsaved changes?')) {
                return
            }

            this.boundingBoxes = JSON.parse(JSON.stringify(this.originalValue))
            this.selectedBoxIndex = null
            this.updateValue()
            this.redrawCanvas()
            Nova.info('Changes discarded')
        },

        /**
         * Handle Clear All button click
         * Removes all bounding boxes after confirmation
         */
        handleClearAll() {
            if (this.boundingBoxes.length === 0) {
                return
            }

            if (!confirm(`Delete all ${this.boundingBoxes.length} damage area${this.boundingBoxes.length !== 1 ? 's' : ''}?`)) {
                return
            }

            this.boundingBoxes = []
            this.selectedBoxIndex = null
            this.updateValue()
            this.redrawCanvas()
            Nova.info('All damage areas cleared')
        },

        /**
         * Validate all bounding boxes
         * Returns array of error messages
         */
        validateBoxes() {
            const errors = []

            this.boundingBoxes.forEach((box, index) => {
                if (!box.id) {
                    errors.push(`Box #${index + 1}: Missing ID`)
                }

                if (!box.x && box.x !== 0) {
                    errors.push(`Box #${index + 1}: Missing X coordinate`)
                }

                if (!box.y && box.y !== 0) {
                    errors.push(`Box #${index + 1}: Missing Y coordinate`)
                }

                if (!box.width || box.width <= 0) {
                    errors.push(`Box #${index + 1}: Invalid width`)
                }

                if (!box.height || box.height <= 0) {
                    errors.push(`Box #${index + 1}: Invalid height`)
                }

                if (!box.damage_type || !this.damageTypes[box.damage_type]) {
                    errors.push(`Box #${index + 1}: Invalid damage type "${box.damage_type}"`)
                }

                if (!box.severity || !this.severityLevels[box.severity]) {
                    errors.push(`Box #${index + 1}: Invalid severity "${box.severity}"`)
                }

                if (!box.image_width || !box.image_height) {
                    errors.push(`Box #${index + 1}: Missing original image dimensions`)
                }
            })

            return errors
        },

        /**
         * Format damage type for display
         */
        formatDamageType(type) {
            if (!type) return 'Unknown'
            return type.split('_').map(word =>
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ')
        }
    }
}
</script>

<style scoped>
.damage-assessment-editor-wrapper {
    max-width: 100%;
}

.canvas-container {
    display: inline-block;
    max-width: 100%;
}

canvas {
    max-width: 100%;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    /* Explicitly disable touch scrolling on canvas to prevent passive listener warnings */
    touch-action: none;
}
</style>