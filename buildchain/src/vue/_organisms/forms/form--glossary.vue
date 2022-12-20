<script setup lang="ts">
import { ref, computed, nextTick, onMounted } from 'vue'
import axios from 'axios'
import { configureApi, executeApi } from '~/js/api/api'

import FieldCheckboxSearchList from '~/vue/_atoms/fields/field--checkbox-search-list.vue'

interface Props {
    siteId: number|null,
    glossaryId: number|string|null,
    action: string,
    sections: any
}

const props = withDefaults(defineProps<Props>(), {
    siteId: null,
    glossaryId: null
})

const form = ref(null)
const termInput = ref('')
const termsInput = ref('')
const definitionInput = ref('')
const exposureInput = ref(['all'])
const variants = ref([])
const definitions = ref([])
const errors = ref({
    form: [],
    definition: null
})
const editDefinitionValue = ref(null)
const loading = ref(false)
const success = ref(false)
const id = ref(props.glossaryId !== 0 ? props.glossaryId : null)

const getErrors = (key) => {
    let keyedErrors = {}

    errors.value.form.forEach(err => {
        if (err[key] ?? null) {
            if (typeof keyedErrors[key] === 'undefined') {
                    keyedErrors[key] = []
            }

            keyedErrors[key].push(err[key][0])
        }
    })

    return keyedErrors[key]
}

const addVariant = () => {
    if (variants.value.indexOf(termsInput.value) === -1) {
        variants.value.push(termsInput.value)
    }

    termsInput.value = ''
}

const removeVariant = variant => {
    variants.value = variants.value.filter(termVar => termVar !== variant)
}

const addDefinition = () => {

    let existingExposure = false
    exposureInput.value.forEach(exposure => {
        if (!existingExposure) {
            existingExposure = definitions.value.filter(def => def.exposure === exposure).length > 0
        }
    })

    if (existingExposure) {
        errors.value.definition = "There's already a definition for the given exposure(s)"
    } else {
        if (definitionInput.value === '') {
            errors.value.definition = "The definition is required"
        } else {
            loading.value = true

            exposureInput.value.forEach((exposure, i) => {
                definitions.value.push({
                    id: 'new' + Date.now() + i,
                    definition: definitionInput.value,
                    exposure: exposure
                })
            })


            definitionInput.value = ''
            exposureInput.value = ['all']
            errors.value.definition = null

            nextTick(() => {
                loading.value = false
            })
        }
    }
}

const saveDefinition = () => {
    let definition = definitions.value.filter(def => def.definition === editDefinitionValue.value)
    console.log("save definition", definition)

    let existingExposure = false
    exposureInput.value.forEach(exposure => {
        if (!existingExposure) {
            existingExposure = definitions.value.filter(def => def.exposure === exposure && def.definition !== editDefinitionValue.value).length > 0
            console.log("existing", existingExposure)
        }
    })

    // if (definition.length > 0 && !existingExposure) {
    //     if (definitionInput.value === '') {
    //         errors.value.definition = "The definition is required"
    //     } else {
    //         loading.value = true

    //         exposureInput.value.forEach((exposure, i) => {
    //             console.log(definition.find(def => def.exposure === definition))
    //             // definitions.value.push({
    //             //     id: 'new' + Date.now() + i,
    //             //     definition: definitionInput.value,
    //             //     exposure: exposure
    //             // })
    //         })

    //         definitionInput.value = ''
    //         exposureInput.value = ['all']
    //         errors.value.definition = null

    //         nextTick(() => {
    //             loading.value = false
    //         })
    //     }
    // } else {
    //     errors.value.definition = "There's already a definition for the given exposure(s)"
    // }
}

const removeDefinition = exposure => {
    definitions.value = definitions.value.filter(def => def.exposure !== exposure)
}

const editDefinition = definition => {
    editDefinitionValue.value = definition.definition
    definitionInput.value = definition.definition
    exposureInput.value = definition.exposure
}

const cancelDefinition = () => {
    definitionInput.value = ''
    exposureInput.value = ['all']
    errors.value.definition = null
    editDefinitionValue.value = null
}

const onValidate = () => {
    let valid = true

    errors.value.form = []

    if (termInput.value.length === 0) {
        valid = false
        errors.value.form.push({'term' : ["Term cannot be blank"]})
    }

    if (definitions.value.length === 0) {
        valid = false
        errors.value.form.push({'definition' : ['There are no terms defined']})
    }

    return valid
}

const submitForm = () => {
    loading.value = true

    const data = {
        term: termInput.value,
        termVariants: variants.value,
        definition: definitions.value,
        siteId: props.siteId,
        id: id.value,
        action: props.action
    }

    data[window.api.csrf.name] = window.api.csrf.value

    const valid = onValidate()

    if (valid) {
        axios({
            method: 'post',
            url: `${window.api.url}${window.api.cp}/actions/${props.action}`,
            data: data
        })
        .then(function (response) {
            if (response?.data?.errors.length > 0) {
                errors.value.form = response?.data?.errors

                if (response?.data?.glossaryId) {
                    id.value = response?.data?.glossaryId
                }
            } else {
                window.location.href = `${window.api.url}${window.api.cp}/glossary-of-terms`
            }

            loading.value = false
        })
    } else {
        loading.value = false
    }
}

const updateSelected = exposure => {
    console.log(exposure)
    exposureInput.value = exposure
}

const exposureList = exposures => {
    return exposures.map(exposure => JSON.parse(props.sections).find(section => section.handle === exposure)?.name ?? 'Default')
}

const getSectionOptions = computed(() => {
    let options = JSON.parse(props.sections).map(section => {
        return {label: section.name, value: section.handle, checked: exposureInput.value.indexOf(section.handle) > -1}
    })

    options.unshift({
        label: 'Default',
        value: 'all',
        checked: exposureInput.value.indexOf('all') > -1
    })

    return options
})

const getDefinitionList = computed(() => {

    let groupedDefinitions = []

    // group definition by exposure
    definitions.value.forEach(def => {
        const filteredDefinitions = groupedDefinitions.filter(item => item.definition === def.definition)
        if (filteredDefinitions.length > 0) {
            filteredDefinitions[0].exposure.push(def.exposure)
        } else {
            groupedDefinitions.push({
                id: def.id,
                definition: def.definition,
                exposure: def.exposure ? [def.exposure] : ['all'],
            })
        }
    })

    return groupedDefinitions
})

onMounted(async () => {
    if (props.glossaryId && props.glossaryId > 0) {
        loading.value = true
        const api = axios.create(configureApi(window.api.url))

        await executeApi(api, 'glossary-of-terms/get-glossary', `?id=${props.glossaryId}`, (response) => {
            termInput.value = response?.term?.term ?? ''
            variants.value = response?.variants?.map(variant => variant.term)
            definitions.value = response?.definitions.map(definition => {
                return {
                    id: definition.id,
                    definition: definition.definition,
                    exposure: definition.sectionHandle ? definition.sectionHandle : 'all',
                }
            })

            loading.value = false
        })
    }
})
</script>

<template>
    <form
        ref="form"
        class="md:grid md:grid-cols-4 gap-8"
        @submit.prevent="submitForm"
    >
        <div class="md:order-2 md:col-span-2 lg:col-span-1 mb-4 bg-gray-200 rounded-lg p-5">
            <h2>How does the glossary work?</h2>
            <p>You can assign a term to a certain definition. We provide extra options to extend upon the glossary creation.</p>
            <h3 class="pt-3">
                Term variants
            </h3>
            <p>In some cases you want variants of the term defined e.g. "Metacognition" and "Meta-cognition". Leave the variants blank if you don't want to provide any.</p>
            <h3 class="pt-3">
                Definitions
            </h3>
            <p>There's also a possibility that different sections of the website will have a different terminology. You could have a different definition for Toolkits and Early Years Toolkit for example. If you leave the section to "All". We will apply the definition on all places.</p>
        </div>
        <div class="md:order-1 md:col-span-2 lg:col-span-3">
            <label class="mb-5 block">
                <span class="text-gray-700 block font-bold">Term <span class="text-red-500">*</span></span>

                <ul
                    v-if="getErrors('term')"
                    class="list-disc ml-3 pl-px"
                >
                    <li
                        v-for="error in getErrors('term')"
                        :key="error"
                        class="text-red-500"
                    >{{ error }}</li>
                </ul>

                <div class="flex flex-nowrap mt-2">
                    <input
                        v-model="termInput"
                        type="text"
                        class="form-input block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                </div>
            </label>
            <label class="mb-5 block">
                <span class="text-gray-700 block font-bold">Term variants</span>
                <span class="text-gray-500 text-sm">Press enter to confirm your variant</span>

                <ul
                    v-if="getErrors('termVariants')"
                    class="list-disc ml-3 pl-px"
                >
                    <li
                        v-for="error in getErrors('termVariants')"
                        :key="error"
                        class="text-red-500"
                    >{{ error }}</li>
                </ul>

                <div
                    :class="[
                        'form-input block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 mt-2',
                        variants.length > 0 ? 'pb-1' : ''
                    ]"
                >
                    <span
                        v-for="variant in variants"
                        :key="variant"
                        class="bg-indigo-100 text-sm p-px pl-2 pr-1 text-indigo-500 rounded-md inline-flex flex-nowrap items-center mr-1 mb-1"
                    >
                        <span>{{ variant }}</span>
                        <span
                            class="w-4 h-4 -ml-1 cursor-pointer"
                            @click="() => removeVariant(variant)"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 320 512"
                                class="fill-current w-full h-full mt-px"
                            ><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M310.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L160 210.7 54.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L114.7 256 9.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L160 301.3 265.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L205.3 256 310.6 150.6z" /></svg>
                        </span>
                    </span>
                    <input
                        v-model="termsInput"
                        type="text"
                        placeholder="Add a variant"
                        class="focus:outline-none focus:ring-0 text-sm"
                        @keydown.enter.prevent="addVariant"
                    >
                </div>
            </label>

            <span class="text-gray-700 block font-bold">Definitions</span>

            <ul
                v-if="getErrors('definition')"
                class="list-disc ml-3 pl-px"
            >
                <li
                    v-for="error in getErrors('definition')"
                    :key="error"
                    class="text-red-500"
                >
                    {{ error }}
                </li>
            </ul>

            <div class="rounded-xl border border-gray-200 mt-2 overflow-hidden">
                <div class="grid grid-cols-4 bg-gray-100">
                    <div class="col-span-2 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Definition
                    </div>
                    <div class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Exposure
                    </div>
                    <div class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </div>
                </div>

                <div
                    v-for="definition in getDefinitionList"
                    :key="definition.id"
                    :class="[
                        'grid grid-cols-4',
                        editDefinitionValue ? 'pointer-events-none opacity-50' : ''
                    ]"
                >
                    <div class="col-span-2 px-6 py-3 text-left flex items-center">
                        {{ definition.definition }}
                    </div>
                    <div class="px-6 py-3 text-left capitalize flex items-center">
                        <!-- {{ definition.exposureName }} -->
                        {{ exposureList(definition.exposure).join(', ') }}
                    </div>
                    <div class="px-6 py-3 text-left">
                        <button
                            type="button"
                            class="cursor-pointer no-underline inline-block bg-gray-300 text-gray-800 mr-2 mb-1 lg:mb-0 py-1 px-2 text-sm font-bold rounded-lg"
                            @click="() => editDefinition(definition)"
                        >
                            Edit
                        </button>
                        <button
                            type="button"
                            class="cursor-pointer inline-block bg-red-300 text-red-800 font-bold py-1 px-2 text-sm rounded-lg"
                            @click="() => removeDefinition(definition.exposure)"
                        >
                            Delete
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-4 border-t border-t-gray-200 bg-gray-50">
                    <div class="col-span-2 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input
                            v-model="definitionInput"
                            type="text"
                            placeholder="Describe the definition"
                            class="form-textarea text-sm w-full col-span-2 block rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            @keydown.enter.prevent="() => editDefinitionValue ? saveDefinition() : addDefinition()"
                        >
                    </div>
                    <div class="px-6 py-3">
                        <FieldCheckboxSearchList
                            v-if="!loading"
                            :options="getSectionOptions"
                            :translations="{placeholder: 'Select exposures', noResults: 'There aren\'t any results'}"
                            @set-field-value="updateSelected"
                        />
                    </div>
                    <!-- <div class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <select
                            v-model="exposureInput"
                            class="form-select w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        >
                            <option
                                value="all"
                                default
                            >
                                Default
                            </option>
                            <option
                                v-for="section in JSON.parse(sections)"
                                :key="section.handle"
                                :value="section.handle"
                            >
                                {{ section.name }}
                            </option>
                        </select>
                    </div> -->
                    <div class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider flex items-center">
                        <button
                            v-if="!editDefinitionValue"
                            type="button"
                            class="cursor-pointer inline-block bg-indigo-500 text-white font-bold py-1 px-2 text-sm rounded-lg"
                            @click="addDefinition"
                        >
                            Add
                        </button>
                        <button
                            v-if="editDefinitionValue"
                            type="button"
                            class="cursor-pointer inline-block bg-indigo-500 text-white font-bold mr-2 py-1 px-2 text-sm rounded-lg"
                            @click="saveDefinition"
                        >
                            Save
                        </button>
                        <button
                            v-if="editDefinitionValue"
                            type="button"
                            class="cursor-pointer no-underline inline-block bg-gray-300 text-gray-800 py-1 px-2 text-sm font-bold rounded-lg"
                            @click="cancelDefinition"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="errors.definition"
                class="w-full p-3 bg-red-100 text-red-500 font-bold mt-2 rounded-lg"
            >
                <span>{{ errors.definition }}</span>
            </div>

            <button
                class="mt-5 ml-auto block bg-indigo-500 text-white font-bold py-2 px-3 text-sm rounded-lg cursor-pointer"
            >
                Submit
            </button>
        </div>

        <div
            v-if="loading"
            class="bg-gray-50/50 absolute inset-0 w-full h-full flex items-center justify-center"
        >
            <svg
                class="animate-spin h-8 w-8"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                />
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
            </svg>
        </div>

        <div
            :class="[
                'fixed top-0 left-1/2 -translate-x-1/2 px-4 py-1 rounded-b-lg bg-emerald-500 z-50 transition-all',
                success ? 'opacity-1' : 'opacity-0'
            ]"
        >
            <span class="text-emerald-900 font-bold font-primary">The glossary has been saved</span>
        </div>
    </form>
</template>
