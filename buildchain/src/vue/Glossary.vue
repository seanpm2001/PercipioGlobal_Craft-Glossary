<script lang="ts">
    import { defineComponent } from 'vue'
    import TableOverview from '~/vue/_organisms/tables/table--overview.vue'
    import FormGlossary from '~/vue/_organisms/forms/form--glossary.vue'
    // import ShortlinkField from '~/vue/organisms/fields/ShortlinkField.vue'

    export default defineComponent({
        components: {
            'table-overview': TableOverview,
            'form-glossary': FormGlossary
        }
    })
</script>
