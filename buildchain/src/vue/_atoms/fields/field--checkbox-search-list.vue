<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
    options: any,
    translations: any
}

defineProps<Props>()
const emit = defineEmits(['set-field-value'])

const id = computed(() => Math.floor(Date.now() / 1000))
const checkboxes = ref(['all'])

const onChange = evt => {
    if (checkboxes.value.indexOf(evt.target.value) > -1) {
        checkboxes.value = checkboxes.value.filter(checkbox => checkbox !== evt.target.value)
    } else {
        checkboxes.value.push(evt.target.value)
    }
    
    emit('set-field-value', checkboxes.value)
}
</script>

<template>
    <section class="w-full">
        <div class="max-h-[400px] overflow-scroll">
            <div
                v-for="(option, i) in options"
                :key="option.value"
            >
                <label class="flex items-center p-1 cursor-pointer rounded-md hover:bg-slate-200">
                    <input
                        :id="`field-checkbox${id}-${i}`"
                        type="checkbox"
                        :name="`fields[${option.name}][]`"
                        :checked="option.checked"
                        :value="option.value"
                        @change="onChange"
                    >
                    <span>{{ option.label }}</span>
                </label>
            </div>
        </div>
    </section>
</template>
