<template>
    <div class="bounding-box-editor">
        <!-- Image Container with Canvas Overlay -->
        <div class="editor-container" :class="{ 'readonly': isReadonly }">
            <!-- Base Image -->
            <div class="image-wrapper" ref="imageWrapper">
                <img
                    ref="baseImage"
                    :src="imageUrl"
                    @load="onImageLoad"
                    @error="onImageError"
                    class="base-image"
                    alt="Trading card"
                />

                <!-- Canvas Overlay for Drawing Bounding Boxes -->
                <canvas
                    v-if="imageLoaded"
                    ref="canvas"
                    class="bounding-box-canvas"
                    :class="{ 'drawing-mode': isDrawing }"
                    @mousedown="onMouseDown"
                    @mousemove="onMouseMove"
                    @mouseup="onMouseUp"
                    @mouseleave="onMouseLeave"
                    @click="onCanvasClick"
                ></canvas>
            </div>

            <!-- Toolbar (Edit Mode Only) -->
            <div v-if="!isReadonly && imageLoaded" class="editor-toolbar">
                <button
                    @click="addNewBox"
                    :disabled="isDrawing"
                    class="btn btn-primary btn-sm"
                    title="Click to start drawing a new damage area"
                >
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Damage Area
                </button>

                <button
                    v-if="selectedBox !== null"
                    @click="deleteSelectedBox"
                    class="btn btn-danger btn-sm ml-2"
                    title="Delete selected damage area"
                >
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Selected
                </button>

                <button
                    v-if="boundingBoxes.length > 0"
                    @click="clearAllBoxes"
                    class="btn btn-outline btn-sm ml-2"
                    title="Clear all damage areas"
                >
                    Clear All
                </button>

                <div class="ml-auto text-sm text-gray-600">
                    {{ boundingBoxes.length }} damage area(s)
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="!imageLoaded && !imageError" class="loading-state">
                <div class="spinner"></div>
                <p class="mt-2 text-sm text-gray-600">Loading image...</p>
            </div>

            <!-- Error State -->
            <div v-if="imageError" class="error-state">
                <svg class="w-12 h-12 text-red-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm text-red-600">{{ imageError }}</p>
            </div>
        </div>

        <!-- Bounding Boxes List -->
        <div v-if="boundingBoxes.length > 0" class="boxes-list mt-4">
            <h4 class="text-sm font-semibold mb-2">Damage Areas ({{ boundingBoxes.length }})</h4>

            <div class="grid grid-cols-1 gap-2">
                <div
                    v-for="(box, index) in boundingBoxes"
                    :key="box.id"
                    class="box-item"
                    :class="{
                        'selected': selectedBox === index,
                        'readonly': isReadonly
                    }"
                    @click="selectBox(index)"
                >
                    <!-- Box Header -->
                    <div class="box-header" :style="getBoxHeaderStyle(box)">
                        <span class="box-number">#{{ index + 1 }}</span>
                        <span class="box-severity">{{ getSeverityLabel(box.severity) }}</span>
                    </div>

                    <!-- Box Details -->
                    <div class="box-details">
                        <!-- Damage Type Selection -->
                        <div class="form-group">
                            <label class="form-label">Damage Type</label>
                            <select
                                v-model="box.damageType"
                                @change="updateBox(index)"
                                :disabled="isReadonly"
                                class="form-select"
                            >
                                <option value="">Select damage type</option>
                                <option
                                    v-for="type in damageTypes"
                                    :key="type.value"
                                    :value="type.value"
                                >
                                    {{ type.icon }} {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Severity Selection -->
                        <div class="form-group">
                            <label class="form-label">Severity</label>
                            <select
                                v-model="box.severity"
                                @change="updateBox(index)"
                                :disabled="isReadonly"
                                class="form-select"
                            >
                                <option value="">Select severity</option>
                                <option
                                    v-for="severity in severityLevels"
                                    :key="severity.value"
                                    :value="severity.value"
                                >
                                    {{ severity.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea
                                v-model="box.notes"
                                @input="updateBox(index)"
                                :disabled="isReadonly"
                                class="form-textarea"
                                rows="2"
                                placeholder="Describe the damage..."
                            ></textarea>
                        </div>

                        <!-- Box Coordinates (Readonly Info) -->
                        <div class="text-xs text-gray-500 mt-2">
                            Position: ({{ Math.round(box.x) }}, {{ Math.round(box.y) }})
                            • Size: {{ Math.round(box.width) }} × {{ Math.round(box.height) }}px
                        </div>
                    </div>

                    <!-- Delete Button (Edit Mode) -->
                    <button
                        v-if="!isReadonly"
                        @click.stop="deleteBox(index)"
                        class="box-delete-btn"
                        title="Delete this damage area"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="imageLoaded" class="empty-state">
            <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p class="text-sm text-gray-600">
                {{ isReadonly ? 'No damage areas marked' : 'Click "Add Damage Area" to start marking damage' }}
            </p>
        </div>
    </div>
</template>

<script>
/**
 * BoundingBoxEditor Component
 *
 * A Vue 3 component for creating and managing multiple bounding boxes on trading card images.
 * Built for PCR Card's damage assessment system, this component provides an intuitive
 * interface for marking damage areas with detailed metadata.
 *
 * Features:
 * - Multiple bounding box support (draw, edit, delete)
 * - Visual canvas-based drawing with mouse interaction
 * - Damage type classification per box
 * - Severity level assignment per box
 * - Color-coded boxes by severity (green/yellow/red)
 * - Readonly mode for viewing existing assessments
 * - Editable mode for creating/modifying assessments
 * - Automatic image scaling while preserving coordinate accuracy
 * - Responsive design for various screen sizes
 *
 * Data Format:
 * Each bounding box is an object with the following structure:
 * {
 *   id: string (UUID),
 *   x: number (pixels from left),
 *   y: number (pixels from top),
 *   width: number (pixels),
 *   height: number (pixels),
 *   image_width: number (original image width),
 *   image_height: number (original image height),
 *   rotation: number (degrees, default 0),
 *   damageType: string (from DamageType constants),
 *   severity: string (from DamageSeverity constants),
 *   notes: string (optional description)
 * }
 *
 * Usage:
 * <BoundingBoxEditor
 *   :image-url="cardImageUrl"
 *   :initial-boxes="existingBoxes"
 *   :damage-types="damageTypeOptions"
 *   :severity-levels="severityLevelOptions"
 *   :readonly="false"
 *   @update="handleBoxesUpdate"
 * />
 *
 * @component
 * @author PCR Card Development Team
 * @date October 2025
 */
export default {
    name: 'BoundingBoxEditor',

    props: {
        /**
         * The card image URL to display as canvas background
         * @type {String}
         * @required
         */
        imageUrl: {
            type: String,
            required: true,
        },

        /**
         * Initial bounding boxes to display/edit
         * @type {Array}
         * @default []
         */
        initialBoxes: {
            type: Array,
            default: () => [],
        },

        /**
         * Available damage type options (from DamageType constants)
         * @type {Array}
         * @required
         * Format: [{ value, label, color, icon }, ...]
         */
        damageTypes: {
            type: Array,
            required: true,
        },

        /**
         * Available severity level options (from DamageSeverity constants)
         * @type {Array}
         * @required
         * Format: [{ value, label, color, priority }, ...]
         */
        severityLevels: {
            type: Array,
            required: true,
        },

        /**
         * Whether the editor is in readonly mode
         * @type {Boolean}
         * @default false
         */
        readonly: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['update'],

    data() {
        return {
            // Image state
            imageLoaded: false,
            imageError: null,
            imageWidth: 0,
            imageHeight: 0,

            // Canvas state
            canvas: null,
            ctx: null,
            canvasScale: 1,

            // Bounding boxes
            boundingBoxes: [],
            selectedBox: null,

            // Drawing state
            isDrawing: false,
            drawStart: null,
            currentDrawBox: null,

            // Interaction state
            hoveredBox: null,
            isDragging: false,
            dragStart: null,
            dragBoxIndex: null,
        }
    },

    computed: {
        /**
         * Whether the editor is in readonly mode
         */
        isReadonly() {
            return this.readonly;
        },
    },

    mounted() {
        console.log('BoundingBoxEditor mounted', {
            imageUrl: this.imageUrl,
            initialBoxesCount: this.initialBoxes?.length || 0,
            damageTypesCount: this.damageTypes?.length || 0,
            severityLevelsCount: this.severityLevels?.length || 0,
            readonly: this.readonly,
        });

        // Initialize bounding boxes from props
        if (this.initialBoxes && this.initialBoxes.length > 0) {
            this.boundingBoxes = this.initialBoxes.map(box => ({
                id: box.id || this.generateUUID(),
                x: box.x || 0,
                y: box.y || 0,
                width: box.width || 0,
                height: box.height || 0,
                image_width: box.image_width || 0,
                image_height: box.image_height || 0,
                rotation: box.rotation || 0,
                damageType: box.damageType || '',
                severity: box.severity || '',
                notes: box.notes || '',
            }));
        }

        // Add keyboard event listener for Delete key
        if (!this.isReadonly) {
            window.addEventListener('keydown', this.onKeyDown);
        }
    },

    beforeUnmount() {
        // Clean up event listeners
        if (!this.isReadonly) {
            window.removeEventListener('keydown', this.onKeyDown);
        }
    },

    watch: {
        /**
         * Watch for changes to initialBoxes prop
         */
        initialBoxes: {
            handler(newBoxes) {
                if (newBoxes && newBoxes.length > 0) {
                    this.boundingBoxes = newBoxes.map(box => ({
                        id: box.id || this.generateUUID(),
                        x: box.x || 0,
                        y: box.y || 0,
                        width: box.width || 0,
                        height: box.height || 0,
                        image_width: box.image_width || 0,
                        image_height: box.image_height || 0,
                        rotation: box.rotation || 0,
                        damageType: box.damageType || '',
                        severity: box.severity || '',
                        notes: box.notes || '',
                    }));
                    this.redrawCanvas();
                }
            },
            deep: true,
        },
    },

    methods: {
        /**
         * Handle image load event
         */
        onImageLoad(event) {
            console.log('Image loaded', {
                url: this.imageUrl,
                naturalWidth: event.target.naturalWidth,
                naturalHeight: event.target.naturalHeight,
            });

            this.imageLoaded = true;
            this.imageWidth = event.target.naturalWidth;
            this.imageHeight = event.target.naturalHeight;

            // Initialize canvas after image loads
            this.$nextTick(() => {
                this.initializeCanvas();
            });
        },

        /**
         * Handle image load error
         */
        onImageError(event) {
            console.error('Image failed to load', {
                url: this.imageUrl,
                error: event,
            });

            this.imageError = 'Failed to load image. Please check the URL.';
        },

        /**
         * Initialize the canvas overlay
         */
        initializeCanvas() {
            if (!this.$refs.canvas || !this.$refs.baseImage) {
                console.warn('Canvas or image ref not available');
                return;
            }

            const canvas = this.$refs.canvas;
            const img = this.$refs.baseImage;

            // Set canvas dimensions to match displayed image size
            canvas.width = img.clientWidth;
            canvas.height = img.clientHeight;

            // Calculate scale factor
            this.canvasScale = img.clientWidth / this.imageWidth;

            // Get canvas context
            this.ctx = canvas.getContext('2d');

            console.log('Canvas initialized', {
                canvasWidth: canvas.width,
                canvasHeight: canvas.height,
                imageWidth: this.imageWidth,
                imageHeight: this.imageHeight,
                scale: this.canvasScale,
            });

            // Draw initial boxes
            this.redrawCanvas();
        },

        /**
         * Redraw all bounding boxes on the canvas
         */
        redrawCanvas() {
            if (!this.ctx) return;

            const canvas = this.$refs.canvas;
            if (!canvas) return;

            // Clear canvas
            this.ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw each bounding box
            this.boundingBoxes.forEach((box, index) => {
                this.drawBox(box, index === this.selectedBox, index === this.hoveredBox);
            });

            // Draw current drawing box
            if (this.isDrawing && this.currentDrawBox) {
                this.drawBox(this.currentDrawBox, false, false, true);
            }
        },

        /**
         * Draw a single bounding box on the canvas
         */
        drawBox(box, isSelected = false, isHovered = false, isDrawing = false) {
            if (!this.ctx || !box) return;

            // Scale coordinates to canvas size
            const x = box.x * this.canvasScale;
            const y = box.y * this.canvasScale;
            const width = box.width * this.canvasScale;
            const height = box.height * this.canvasScale;

            // Get severity color
            const color = this.getSeverityColor(box.severity);

            // Draw box background (semi-transparent)
            this.ctx.fillStyle = color + '20'; // 20 = ~12% opacity in hex
            this.ctx.fillRect(x, y, width, height);

            // Draw box border
            this.ctx.strokeStyle = color;
            this.ctx.lineWidth = isSelected ? 3 : (isHovered ? 2.5 : 2);
            this.ctx.strokeRect(x, y, width, height);

            // Draw selection handles if selected
            if (isSelected && !isDrawing) {
                this.drawSelectionHandles(x, y, width, height, color);
            }

            // Draw label with damage type and severity
            if (box.damageType || box.severity) {
                this.drawBoxLabel(x, y, box, color);
            }
        },

        /**
         * Draw selection handles for a selected box
         */
        drawSelectionHandles(x, y, width, height, color) {
            const handleSize = 8;
            const handles = [
                { x: x, y: y }, // Top-left
                { x: x + width, y: y }, // Top-right
                { x: x, y: y + height }, // Bottom-left
                { x: x + width, y: y + height }, // Bottom-right
            ];

            this.ctx.fillStyle = color;
            handles.forEach(handle => {
                this.ctx.fillRect(
                    handle.x - handleSize / 2,
                    handle.y - handleSize / 2,
                    handleSize,
                    handleSize
                );
            });
        },

        /**
         * Draw label for a bounding box
         */
        drawBoxLabel(x, y, box, color) {
            const icon = this.getDamageTypeIcon(box.damageType);
            const label = this.getDamageTypeLabel(box.damageType);
            const severity = this.getSeverityLabel(box.severity);

            const text = `${icon} ${label} (${severity})`;

            // Measure text
            this.ctx.font = '12px sans-serif';
            const textWidth = this.ctx.measureText(text).width;

            // Draw label background
            const padding = 4;
            const labelX = x + 4;
            const labelY = y + 4;
            const labelHeight = 16;

            this.ctx.fillStyle = color;
            this.ctx.fillRect(labelX, labelY, textWidth + padding * 2, labelHeight);

            // Draw label text
            this.ctx.fillStyle = '#FFFFFF';
            this.ctx.textBaseline = 'middle';
            this.ctx.fillText(text, labelX + padding, labelY + labelHeight / 2);
        },

        /**
         * Handle mouse down event (start drawing)
         */
        onMouseDown(event) {
            if (this.isReadonly) return;

            const rect = this.$refs.canvas.getBoundingClientRect();
            const x = (event.clientX - rect.left) / this.canvasScale;
            const y = (event.clientY - rect.top) / this.canvasScale;

            // Check if clicking on existing box
            const clickedBoxIndex = this.getBoxAtPosition(x, y);
            if (clickedBoxIndex !== null) {
                this.selectedBox = clickedBoxIndex;
                this.redrawCanvas();
                return;
            }

            // Start drawing new box if "Add Damage Area" was clicked
            if (this.isDrawing) {
                this.drawStart = { x, y };
                this.currentDrawBox = {
                    id: this.generateUUID(),
                    x,
                    y,
                    width: 0,
                    height: 0,
                    image_width: this.imageWidth,
                    image_height: this.imageHeight,
                    rotation: 0,
                    damageType: '',
                    severity: '',
                    notes: '',
                };
            }
        },

        /**
         * Handle mouse move event (update drawing)
         */
        onMouseMove(event) {
            if (this.isReadonly) return;

            const rect = this.$refs.canvas.getBoundingClientRect();
            const x = (event.clientX - rect.left) / this.canvasScale;
            const y = (event.clientY - rect.top) / this.canvasScale;

            // Update hovered box
            const hoveredBoxIndex = this.getBoxAtPosition(x, y);
            if (hoveredBoxIndex !== this.hoveredBox) {
                this.hoveredBox = hoveredBoxIndex;
                this.redrawCanvas();
            }

            // Update current drawing box
            if (this.isDrawing && this.drawStart) {
                this.currentDrawBox.x = Math.min(x, this.drawStart.x);
                this.currentDrawBox.y = Math.min(y, this.drawStart.y);
                this.currentDrawBox.width = Math.abs(x - this.drawStart.x);
                this.currentDrawBox.height = Math.abs(y - this.drawStart.y);
                this.redrawCanvas();
            }
        },

        /**
         * Handle mouse up event (finish drawing)
         */
        onMouseUp(event) {
            if (this.isReadonly || !this.isDrawing || !this.drawStart) return;

            // Only add box if it has minimum size
            if (this.currentDrawBox.width > 10 && this.currentDrawBox.height > 10) {
                this.boundingBoxes.push(this.currentDrawBox);
                this.selectedBox = this.boundingBoxes.length - 1;
                this.emitUpdate();
            }

            // Reset drawing state
            this.isDrawing = false;
            this.drawStart = null;
            this.currentDrawBox = null;
            this.redrawCanvas();
        },

        /**
         * Handle mouse leave event (cancel drawing)
         */
        onMouseLeave(event) {
            if (this.isDrawing) {
                this.isDrawing = false;
                this.drawStart = null;
                this.currentDrawBox = null;
                this.redrawCanvas();
            }
        },

        /**
         * Handle canvas click event
         */
        onCanvasClick(event) {
            if (this.isReadonly) return;

            const rect = this.$refs.canvas.getBoundingClientRect();
            const x = (event.clientX - rect.left) / this.canvasScale;
            const y = (event.clientY - rect.top) / this.canvasScale;

            // Select clicked box
            const clickedBoxIndex = this.getBoxAtPosition(x, y);
            if (clickedBoxIndex !== null) {
                this.selectedBox = clickedBoxIndex;
                this.redrawCanvas();
            } else {
                this.selectedBox = null;
                this.redrawCanvas();
            }
        },

        /**
         * Handle keyboard events (Delete key)
         */
        onKeyDown(event) {
            if (event.key === 'Delete' && this.selectedBox !== null) {
                this.deleteSelectedBox();
            }
        },

        /**
         * Get box index at a given position
         */
        getBoxAtPosition(x, y) {
            for (let i = this.boundingBoxes.length - 1; i >= 0; i--) {
                const box = this.boundingBoxes[i];
                if (x >= box.x && x <= box.x + box.width &&
                    y >= box.y && y <= box.y + box.height) {
                    return i;
                }
            }
            return null;
        },

        /**
         * Start drawing a new box
         */
        addNewBox() {
            if (this.isReadonly) return;
            this.isDrawing = true;
            this.selectedBox = null;
            this.redrawCanvas();
        },

        /**
         * Select a box
         */
        selectBox(index) {
            if (this.isReadonly) return;
            this.selectedBox = index;
            this.redrawCanvas();
        },

        /**
         * Update a box (when metadata changes)
         */
        updateBox(index) {
            if (this.isReadonly) return;
            this.redrawCanvas();
            this.emitUpdate();
        },

        /**
         * Delete a box
         */
        deleteBox(index) {
            if (this.isReadonly) return;
            this.boundingBoxes.splice(index, 1);
            if (this.selectedBox === index) {
                this.selectedBox = null;
            } else if (this.selectedBox !== null && this.selectedBox > index) {
                this.selectedBox--;
            }
            this.redrawCanvas();
            this.emitUpdate();
        },

        /**
         * Delete the selected box
         */
        deleteSelectedBox() {
            if (this.selectedBox !== null) {
                this.deleteBox(this.selectedBox);
            }
        },

        /**
         * Clear all boxes
         */
        clearAllBoxes() {
            if (this.isReadonly) return;
            if (confirm('Are you sure you want to clear all damage areas?')) {
                this.boundingBoxes = [];
                this.selectedBox = null;
                this.redrawCanvas();
                this.emitUpdate();
            }
        },

        /**
         * Emit update event with current boxes
         */
        emitUpdate() {
            this.$emit('update', this.boundingBoxes);
        },

        /**
         * Get severity color from constants
         */
        getSeverityColor(severity) {
            const severityObj = this.severityLevels.find(s => s.value === severity);
            return severityObj?.color || '#9CA3AF'; // Gray default
        },

        /**
         * Get severity label from constants
         */
        getSeverityLabel(severity) {
            const severityObj = this.severityLevels.find(s => s.value === severity);
            return severityObj?.label || 'Unknown';
        },

        /**
         * Get damage type icon from constants
         */
        getDamageTypeIcon(damageType) {
            const typeObj = this.damageTypes.find(t => t.value === damageType);
            return typeObj?.icon || '❓';
        },

        /**
         * Get damage type label from constants
         */
        getDamageTypeLabel(damageType) {
            const typeObj = this.damageTypes.find(t => t.value === damageType);
            return typeObj?.label || 'Unknown';
        },

        /**
         * Get damage type color from constants
         */
        getDamageTypeColor(damageType) {
            const typeObj = this.damageTypes.find(t => t.value === damageType);
            return typeObj?.color || '#64748B';
        },

        /**
         * Get box header style based on severity
         */
        getBoxHeaderStyle(box) {
            const color = this.getSeverityColor(box.severity);
            return {
                backgroundColor: color,
                color: '#FFFFFF',
            };
        },

        /**
         * Generate UUID for box IDs
         */
        generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },
    },
}
</script>

<style scoped>
/**
 * BoundingBoxEditor Styles
 *
 * Scoped styles for the damage assessment editor component.
 * Uses Tailwind-compatible classes and custom styles for canvas interaction.
 */

.bounding-box-editor {
    width: 100%;
}

/* Editor Container */
.editor-container {
    position: relative;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.editor-container.readonly {
    border-color: #d1d5db;
}

/* Image Wrapper */
.image-wrapper {
    position: relative;
    display: inline-block;
    max-width: 100%;
    margin: 0 auto;
}

/* Base Image */
.base-image {
    display: block;
    max-width: 100%;
    height: auto;
    border-radius: 4px;
}

/* Canvas Overlay */
.bounding-box-canvas {
    position: absolute;
    top: 0;
    left: 0;
    cursor: crosshair;
    pointer-events: auto;
}

.bounding-box-canvas.drawing-mode {
    cursor: crosshair;
}

/* Toolbar */
.editor-toolbar {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
}

.btn {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    font-size: 14px;
    font-weight: 500;
    border-radius: 4px;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-primary {
    background: #3b82f6;
    color: #ffffff;
    border-color: #3b82f6;
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
}

.btn-danger {
    background: #ef4444;
    color: #ffffff;
    border-color: #ef4444;
}

.btn-danger:hover:not(:disabled) {
    background: #dc2626;
}

.btn-outline {
    background: #ffffff;
    color: #6b7280;
    border-color: #d1d5db;
}

.btn-outline:hover:not(:disabled) {
    background: #f9fafb;
}

.btn-sm {
    padding: 4px 10px;
    font-size: 13px;
}

/* Loading State */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top-color: #3b82f6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Error State */
.error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

/* Boxes List */
.boxes-list {
    margin-top: 16px;
}

.box-item {
    position: relative;
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    padding: 0;
    cursor: pointer;
    transition: all 0.2s ease;
}

.box-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

.box-item.selected {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.box-item.readonly {
    cursor: default;
}

/* Box Header */
.box-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    font-weight: 600;
    font-size: 13px;
}

.box-number {
    font-weight: 700;
}

.box-severity {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Box Details */
.box-details {
    padding: 12px;
    background: #f9fafb;
}

/* Form Groups */
.form-group {
    margin-bottom: 12px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 4px;
}

.form-select,
.form-textarea {
    width: 100%;
    padding: 6px 8px;
    font-size: 13px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    background: #ffffff;
    color: #111827;
    transition: all 0.2s ease;
}

.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-select:disabled,
.form-textarea:disabled {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

.form-textarea {
    resize: vertical;
    font-family: inherit;
}

/* Box Delete Button */
.box-delete-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 4px;
    background: rgba(239, 68, 68, 0.9);
    color: #ffffff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.box-delete-btn:hover {
    background: #dc2626;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

/* Utility Classes */
.text-gray-400 { color: #9ca3af; }
.text-gray-500 { color: #6b7280; }
.text-gray-600 { color: #4b5563; }
.text-red-600 { color: #dc2626; }

.text-sm { font-size: 14px; }
.text-xs { font-size: 12px; }

.font-semibold { font-weight: 600; }

.mb-2 { margin-bottom: 8px; }
.mt-2 { margin-top: 8px; }
.mt-4 { margin-top: 16px; }
.ml-1 { margin-left: 4px; }
.ml-2 { margin-left: 8px; }
.ml-auto { margin-left: auto; }

.w-4 { width: 16px; }
.h-4 { height: 16px; }
.w-12 { width: 48px; }
.h-12 { height: 48px; }

.inline-block { display: inline-block; }
.mr-1 { margin-right: 4px; }

.grid { display: grid; }
.grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
.gap-2 { gap: 8px; }

/* Responsive */
@media (min-width: 768px) {
    .image-wrapper {
        max-width: 800px;
    }
}
</style>
