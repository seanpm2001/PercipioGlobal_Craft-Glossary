import { createApp } from 'vue'
import Glossary from '~/vue/Glossary.vue'

// App main
const main = async () => {
    const glossary = createApp(Glossary)
    const app = glossary.mount('#glossary')

    return app
}

// Execute async function
main().then(() => {
    console.log()
})
