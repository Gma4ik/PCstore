import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import './styles/global.css'
import App from './App.vue'

const app = createApp(App)
const pinia = createPinia()

// Initialize Pinia first
app.use(pinia)

// Initialize router
app.use(router)

// Mount the app
app.mount('#app')

// Load cart and user from localStorage on app start
import { useCartStore, useUserStore, useWishlistStore } from './store'

const cartStore = useCartStore(pinia)
const userStore = useUserStore(pinia)
const wishlistStore = useWishlistStore(pinia)

cartStore.loadCart()
userStore.loadUser()
wishlistStore.init()
