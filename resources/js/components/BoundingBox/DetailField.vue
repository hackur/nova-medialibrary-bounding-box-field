<template>
    <div class="px-4 py-3">
        <h4 class="text-sm font-semibold mb-2">Damage Assessment Visual Editor</h4>

        <!-- Image container with bounding box overlays -->
        <div v-if="field && field.imageUrl" class="damage-assessment-viewer">
            <div class="image-container" style="position: relative; display: inline-block; max-width: 100%;">
                <!-- Base card image -->
                <img
                    :src="field.imageUrl"
                    @load="onImageLoad"
                    @error="onImageError"
                    class="card-image"
                    alt="Trading card"
                />

                <!-- Bounding box overlays (only show after image loads) -->
                <div
                    v-if="imageLoaded"
                    v-for="(assessment, assessmentIndex) in field.damageAssessments"
                    :key="`assessment-${assessment.id || assessmentIndex}`"
                    class="assessment-boxes"
                >
                    <div
                        v-for="(box, boxIndex) in (assessment.bounding_boxes || [])"
                        :key="`box-${box.id || boxIndex}`"
                        class="bounding-box"
                        :style="getBoxStyle(box, assessment.severity)"
                    >
                        <!-- Box label -->
                        <div class="box-label" :style="getLabelStyle(assessment.severity)">
                            <span class="font-bold">
                                {{ boxIndex === 0 ? `#${assessmentIndex + 1}` : `${assessmentIndex + 1}.${boxIndex + 1}` }}
                            </span>
                            <span v-if="box.damage_type" class="ml-1 text-xs">
                                {{ formatDamageType(box.damage_type) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Damage assessment summary -->
            <div v-if="field.damageAssessments && field.damageAssessments.length > 0" class="mt-4">
                <h4 class="text-sm font-semibold mb-2">
                    Damage Areas ({{ field.damageAssessments.length }})
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div
                        v-for="(assessment, index) in field.damageAssessments"
                        :key="index"
                        class="bg-gray-50 p-3 rounded-lg border"
                        :class="getBorderClass(assessment.severity)"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-medium text-sm">
                                    {{ formatDamageType(assessment.damage_type) }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Severity:
                                    <span :class="getSeverityClass(assessment.severity)">
                                        {{ assessment.severity }}
                                    </span>
                                </div>
                                <div v-if="assessment.notes" class="text-xs text-gray-600 mt-1">
                                    {{ assessment.notes }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    Boxes: {{ assessment.bounding_boxes ? assessment.bounding_boxes.length : 0 }}
                                </div>
                            </div>
                            <div v-if="assessment.addressed" class="ml-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Addressed
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-gray-500 italic text-sm mt-4">
                No damage assessments recorded
            </div>
        </div>

        <div v-else class="text-gray-500 italic">
            No image available
        </div>
    </div>
</template>

<script>
/**
 * DamageAssessmentEditor DetailField - Nova Props Version
 *
 * Uses data from Nova field props instead of AJAX calls.
 * Nova automatically provides the data via the field prop.
 *
 * Uses HTML img element with absolutely positioned div overlays instead of canvas.
 *
 * Benefits:
 * - Simpler - no AJAX complexity
 * - Faster - no extra HTTP requests
 * - Consistent with Nova patterns
 * - No canvas timing issues
 */
export default {
    props: ['index', 'resource', 'resourceName', 'resourceId', 'field'],

    data() {
        return {
            imageLoaded: false,
            imageWidth: 0,
            imageHeight: 0,
        }
    },

    // Removed computed properties that might cause refs issues

    mounted() {
        console.log('DamageAssessmentEditor DetailField mounted', {
            resourceId: this.resourceId,
            resourceName: this.resourceName,
            imageUrl: this.field.imageUrl,
            damageAssessmentsCount: this.field.damageAssessments?.length || 0,
        })
    },

    methods: {

        onImageLoad(event) {
            console.log('Image loaded successfully', {
                url: this.field.imageUrl,
                width: event.target.clientWidth,
                height: event.target.clientHeight,
            })

            // Store image dimensions for box calculations
            this.imageWidth = event.target.clientWidth
            this.imageHeight = event.target.clientHeight
            this.imageLoaded = true
        },

        onImageError(event) {
            console.error('Image failed to load', {
                url: this.field.imageUrl,
                error: event,
            })
        },

        /**
         * Get CSS styles for bounding box div overlay
         *
         * FIXED: No longer uses $refs to avoid Vue refs error
         * Instead uses stored image dimensions from onImageLoad
         */
        getBoxStyle(box, severity) {
            if (!this.imageLoaded || !this.imageWidth || !this.imageHeight) {
                return {}
            }

            const displayWidth = this.imageWidth
            const displayHeight = this.imageHeight

            // Calculate scale factor between original and displayed image
            const scaleX = displayWidth / (box.image_width || displayWidth)
            const scaleY = displayHeight / (box.image_height || displayHeight)

            // Scale box coordinates to match displayed image size
            const scaledX = box.x * scaleX
            const scaledY = box.y * scaleY
            const scaledWidth = box.width * scaleX
            const scaledHeight = box.height * scaleY

            const color = this.getSeverityColorHex(severity)

            return {
                position: 'absolute',
                left: `${scaledX}px`,
                top: `${scaledY}px`,
                width: `${scaledWidth}px`,
                height: `${scaledHeight}px`,
                border: `2px solid ${color}`,
                backgroundColor: `${color}20`, // 20 = 12.5% opacity in hex
                pointerEvents: 'none',
                borderRadius: '2px',
            }
        },

        /**
         * Get CSS styles for box label
         */
        getLabelStyle(severity) {
            const color = this.getSeverityColorHex(severity)

            return {
                position: 'absolute',
                top: '4px',
                left: '4px',
                backgroundColor: color,
                color: 'white',
                padding: '2px 6px',
                borderRadius: '3px',
                fontSize: '11px',
                lineHeight: '1.2',
                fontWeight: '600',
                whiteSpace: 'nowrap',
            }
        },

        getSeverityColorHex(severity) {
            const colors = {
                minor: '#10b981',    // green
                moderate: '#f59e0b', // yellow
                major: '#f97316',    // orange
                severe: '#ef4444'    // red
            }
            return colors[severity] || '#6b7280' // gray default
        },

        getSeverityClass(severity) {
            const classes = {
                minor: 'text-green-600 font-medium',
                moderate: 'text-yellow-600 font-medium',
                major: 'text-orange-600 font-medium',
                severe: 'text-red-600 font-medium'
            }
            return classes[severity] || 'text-gray-600'
        },

        getBorderClass(severity) {
            const classes = {
                minor: 'border-green-200',
                moderate: 'border-yellow-200',
                major: 'border-orange-200',
                severe: 'border-red-200'
            }
            return classes[severity] || 'border-gray-200'
        },

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
.damage-assessment-viewer {
    max-width: 100%;
}

.image-container {
    position: relative;
    display: inline-block;
    max-width: 100%;
}

.card-image {
    display: block;
    max-width: 100%;
    height: auto;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.assessment-boxes {
    pointer-events: none;
}

.bounding-box {
    transition: opacity 0.2s ease;
}

.bounding-box:hover {
    opacity: 0.9;
}

.box-label {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}
</style>
