<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { configureApi, executeApi } from '~/js/api/api'

const terms = ref(null)
const showModal = ref(false)
const deletableId = ref(null)
const loading = ref(true)
const pagination = ref({
        currentPage: 0,
        hitsPerPage: 50,
        total: 0
})
const alphabeth = ref("abcdefghijklmnopqrstuvwxyz".split(''))

const getEditUrl = (id) => {
    return `${ window.api.url }${ window.api.cp }/glossary/edit?id=${ id }`
}

const onToggleModal = (state, id = null) => {
    showModal.value = state
    deletableId.value = id
}

const onLoadMore = () => {
    loading.value = true
    onFetch()
}

const onFetch = async() => {
    loading.value = true
    const api = axios.create(configureApi(window.api.url))

    await executeApi(api, 'glossary/get-glossaries', `?limit=${pagination.value.hitsPerPage}&offset=${pagination.value.currentPage}&sort=4`, (response) => {
            terms.value = [...(terms.value || []), ...response.glossary]
            pagination.value.currentPage += 1
            pagination.value.total = Math.ceil(response.total / pagination.value.hitsPerPage)
            loading.value = false
    })
}

const onDelete = async(id) => {
    loading.value = true
    onToggleModal(false)
    const api = axios.create(configureApi(`${window.api.url}${window.api.cp}`))

    await executeApi(api, 'glossary/delete', `?id=${id}`, (response) => {
            if(response.success) {
                    terms.value = terms.value.filter(term => term.id !== id)
                    pagination.value.total -= 1
                    loading.value = false
            }
    })
}

const sorted = computed(() => {
    let sorted = {}

    if (terms.value) {
            alphabeth.value.forEach(letter => {
                    sorted[letter] = terms.value.filter(term => term.term.charAt(0) === letter)
            })
    }

    return sorted
})

onMounted(async () => {
    onFetch()
})
</script>

<template>
  <section>
    <div class="rounded-xl border border-gray-200">
      <!-- heading -->
      <div class="grid grid-cols-6 rounded-tr-xl rounded-tl-xl bg-gray-100">
        <div class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Term
        </div>

        <div class="col-span-2 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Definition
        </div>

        <div class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Variants
        </div>

        <div class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Environment
        </div>

        <div class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Actions
        </div>
      </div>

      <!-- row -->
      <div
        v-for="(sortedTerms, letter) in sorted"
        :key="letter"
      >
        <div
          v-if="sortedTerms.length > 0"
          lass="grid grid-cols-6 relative"
        >
          <div class="col-span-5 px-6 py-2 bg-gray-300 sticky top-[53px] z-10">
            <span class="font-primary font-bold uppercase">{{ letter }}</span>
          </div>
          <div 
            v-for="term in sortedTerms"
            :key="term.term"
            class="col-span-6 grid grid-cols-6"
          >
            <div class="px-6 py-4 whitespace-nowrap flex items-center">
              {{ term.term }}
            </div>
            <div class="col-span-2 px-6 py-4">
              <span
                v-if="term?.definitions"
                class="whitespace-nowrap text-ellipsis overflow-hidden w-full block"
              >
                {{ term?.definitions.length === 1 ? term?.definitions[0].definition : term?.definitions.length }}
              </span>
            </div>
            <div class="px-6 py-4 flex items-center">
              <span 
                v-for="(variant, i) in term.variants"
                :key="variant.id"
              >
                {{ variant.term + (i < (term.variants.length - 1) ? ',' : '') }}
              </span>
              <span v-if="term?.variants && term?.variants.length === 0">-</span>
            </div>
            <div class="px-6 py-4">
              {{ term?.definitions.length === 1 ? (term?.definitions[0].sectionHandle ? term?.definitions[0].sectionHandle : 'all') : 'multiple' }}
            </div>
            <div class="px-6 py-4 whitespace-nowrap flex items-center space-x-4">
              <a
                :href="getEditUrl(term.id)"
                title="Edit"
                class="flex items-center"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 512 512"
                  class="w-[12px] fill-current"
                ><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M395.8 39.6c9.4-9.4 24.6-9.4 33.9 0l42.6 42.6c9.4 9.4 9.4 24.6 0 33.9L417.6 171 341 94.4l54.8-54.8zM318.4 117L395 193.6l-219 219V400c0-8.8-7.2-16-16-16H128V352c0-8.8-7.2-16-16-16H99.4l219-219zM66.9 379.5c1.2-4 2.7-7.9 4.7-11.5H96v32c0 8.8 7.2 16 16 16h32v24.4c-3.7 1.9-7.5 3.5-11.6 4.7L39.6 472.4l27.3-92.8zM452.4 17c-21.9-21.9-57.3-21.9-79.2 0L60.4 329.7c-11.4 11.4-19.7 25.4-24.2 40.8L.7 491.5c-1.7 5.6-.1 11.7 4 15.8s10.2 5.7 15.8 4l121-35.6c15.4-4.5 29.4-12.9 40.8-24.2L495 138.8c21.9-21.9 21.9-57.3 0-79.2L452.4 17zM331.3 202.7c6.2-6.2 6.2-16.4 0-22.6s-16.4-6.2-22.6 0l-128 128c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0l128-128z" /></svg>
                <span>Edit</span>
              </a>
              <button
                class="text-red-500 flex items-center"
                @click="(evt) => onToggleModal(true, term.id)"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 448 512"
                  class="w-[12px] fill-red-500"
                ><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M424 80C437.3 80 448 90.75 448 104C448 117.3 437.3 128 424 128H412.4L388.4 452.7C385.9 486.1 358.1 512 324.6 512H123.4C89.92 512 62.09 486.1 59.61 452.7L35.56 128H24C10.75 128 0 117.3 0 104C0 90.75 10.75 80 24 80H93.82L130.5 24.94C140.9 9.357 158.4 0 177.1 0H270.9C289.6 0 307.1 9.358 317.5 24.94L354.2 80H424zM177.1 48C174.5 48 171.1 49.34 170.5 51.56L151.5 80H296.5L277.5 51.56C276 49.34 273.5 48 270.9 48H177.1zM364.3 128H83.69L107.5 449.2C108.1 457.5 115.1 464 123.4 464H324.6C332.9 464 339.9 457.5 340.5 449.2L364.3 128z" /></svg>
                <span>Delete</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <button
      v-if="pagination.currentPage < pagination.total"
      class="cursor-pointer mx-auto mt-6 flex items-center justify-center rounded-md border border-transparent bg-indigo-600 disabled:bg-indigo-400 disabled:cursor-not-allowed px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto"
      @click="onLoadMore"
    >
      <span>Load more</span>
      <svg
        v-if="loading"
        class="animate-spin ml-1 h-3 w-3 text-white mb-0"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      ><circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      /><path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      /></svg>
    </button>

    <!-- delete modal -->
    <div
      v-if="showModal"
      class="relative z-10"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />

      <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end sm:items-center justify-center min-h-full p-4 text-center sm:p-0">
          <div class="relative bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
              <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 text-red-500 sm:mx-0 sm:h-10 sm:w-10">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 512 512"
                  class="fill-current w-[18px]"
                ><!--! Font Awesome Pro 6.2.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M256 32L0 480H512L256 32zm24 160v24V328v24H232V328 216 192h48zM232 384h48v48H232V384z" /></svg>
              </div>
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                <h3
                  id="modal-title"
                  class="text-lg leading-6 font-medium text-gray-900"
                >
                  Delete Term
                </h3>
                <div class="mt-2">
                  <p class="text-sm text-gray-500">
                    Are you sure you want to delete the term? This action cannot&nbsp;be&nbsp;undone.
                  </p>
                </div>
              </div>
            </div>
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse items-center">
              <button
                type="button"
                class="mt-3 cursor-pointer inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 disabled:bg-red-400 disabled:cursor-not-allowed px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:w-auto"
                @click="(evt) => onDelete(deletableId)"
              >
                Delete
              </button>
              <button
                type="button"
                class="mt-3 cursor-pointer inline-block bg-gray-300 text-gray-900 font-bold mr-2 py-2 px-3 text-sm rounded-lg"
                @click="(evt) => onToggleModal(false)"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="loading"
      class="bg-gray-50/50 absolute inset-0 w-full h-full flex items-center justify-center z-10"
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
  </section>
</template>