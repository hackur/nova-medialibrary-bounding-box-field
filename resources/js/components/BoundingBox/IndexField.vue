<template>
    <div class="text-center">
        <div v-if="hasAssessments" class="inline-flex items-center space-x-2">
            <span 
                v-if="severeCount > 0" 
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
            >
                {{ severeCount }} Severe
            </span>
            <span 
                v-if="majorCount > 0" 
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800"
            >
                {{ majorCount }} Major
            </span>
            <span 
                v-if="moderateCount > 0" 
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
            >
                {{ moderateCount }} Moderate
            </span>
            <span 
                v-if="minorCount > 0" 
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
            >
                {{ minorCount }} Minor
            </span>
            
            <span class="text-gray-500 text-sm">
                ({{ totalCount }} total)
            </span>
        </div>
        
        <span v-else class="text-gray-400 text-sm italic">
            No assessments
        </span>
    </div>
</template>

<script>
export default {
    props: ['resourceName', 'field'],
    
    computed: {
        assessments() {
            return this.field.damageAssessments || []
        },
        
        hasAssessments() {
            return this.assessments.length > 0
        },
        
        totalCount() {
            return this.assessments.length
        },
        
        severeCount() {
            return this.assessments.filter(a => a.severity === 'severe').length
        },
        
        majorCount() {
            return this.assessments.filter(a => a.severity === 'major').length
        },
        
        moderateCount() {
            return this.assessments.filter(a => a.severity === 'moderate').length
        },
        
        minorCount() {
            return this.assessments.filter(a => a.severity === 'minor').length
        }
    }
}
</script>