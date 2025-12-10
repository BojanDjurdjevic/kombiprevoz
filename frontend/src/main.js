//import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { VDateInput } from 'vuetify/labs/VDateInput'
import { VNumberInput } from 'vuetify/labs/VNumberInput'

import App from './App.vue'
import router from './router'
import { useUserStore } from './stores/user'


const vuetify = createVuetify({
    components,
    directives,
    defaults: {
        theme: {
            defaultTheme: "dark",
            // pitanje da li treba dirati...
            themes: {
                light: {
                    colors: {
                    primary: '#1976D2',
                    secondary: '#424242',
                    accent: '#82B1FF',
                    error: '#FF5252',
                    info: '#2196F3',
                    success: '#4CAF50',
                    warning: '#FFC107'
                    }
                }
            }
        }
    }
    
})

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(vuetify)

const user = useUserStore()
user.actions.checkSession().then(() => {
    app.mount('#app')
})


