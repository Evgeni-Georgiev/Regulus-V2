<template>
  <div class="min-h-screen bg-background text-foreground">
    <!-- Header -->
    <header class="sticky top-0 z-40 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      <div class="container mx-auto px-4">
        <div class="flex h-16 items-center justify-between">
          <!-- Logo/Brand -->
          <div class="flex items-center space-x-4">
            <router-link to="/" class="flex items-center space-x-2">
              <h1 class="text-xl font-bold text-primary">Regulus</h1>
            </router-link>
          </div>

          <!-- Right side: Navigation + Auth -->
          <div class="flex items-center space-x-6">
            <!-- Navigation -->
            <nav class="hidden md:flex items-center space-x-1">
              <!-- Dashboard - First for authenticated users -->
              <template v-if="authStore.isAuthenticated">
                <router-link 
                  to="/dashboard" 
                  class="px-3 py-2 text-sm font-medium rounded-md transition-colors hover:bg-accent hover:text-accent-foreground"
                  :class="{ 
                    'bg-accent text-accent-foreground': $route.name === 'Dashboard',
                    'text-muted-foreground': $route.name !== 'Dashboard'
                  }"
                >
                  Dashboard
                </router-link>
              </template>
              
              <!-- Cryptocurrencies - Always visible -->
              <router-link 
                to="/coins" 
                class="px-3 py-2 text-sm font-medium rounded-md transition-colors hover:bg-accent hover:text-accent-foreground"
                :class="{ 
                  'bg-accent text-accent-foreground': $route.name === 'Coins' || $route.name === 'CoinsPage',
                  'text-muted-foreground': $route.name !== 'Coins' && $route.name !== 'CoinsPage'
                }"
              >
                Cryptocurrencies
              </router-link>
              
              <!-- Portfolios - Only for authenticated users -->
              <template v-if="authStore.isAuthenticated">
                <router-link 
                  to="/portfolios" 
                  class="px-3 py-2 text-sm font-medium rounded-md transition-colors hover:bg-accent hover:text-accent-foreground"
                  :class="{ 
                    'bg-accent text-accent-foreground': $route.name === 'Portfolio',
                    'text-muted-foreground': $route.name !== 'Portfolio'
                  }"
                >
                  Portfolios
                </router-link>
              </template>
            </nav>

            <!-- Auth Section -->
            <div class="flex items-center space-x-4">
              <!-- Theme Toggle -->
              <ThemeToggle />
              
              <!-- Show auth buttons for public pages or when not authenticated -->
              <template v-if="!authStore.isAuthenticated && showAuthButtons">
                <router-link to="/auth/login">
                  <Button variant="ghost" size="sm" class="text-muted-foreground hover:text-foreground">
                    Sign In
                  </Button>
                </router-link>
                <router-link to="/auth/register">
                  <Button size="sm">
                    Sign Up
                  </Button>
                </router-link>
              </template>

              <!-- Show user menu when authenticated -->
              <template v-else-if="authStore.isAuthenticated">
                <div class="relative" ref="userMenuRef">
                  <Button 
                    variant="ghost" 
                    size="sm"
                    @click="toggleUserMenu"
                    class="flex items-center space-x-2 text-muted-foreground hover:text-foreground"
                  >
                    <User class="h-4 w-4" />
                    <span>{{ authStore.user?.first_name }}</span>
                    <ChevronDown class="h-4 w-4" />
                  </Button>
                  
                  <!-- User Dropdown Menu -->
                  <div 
                    v-if="showUserMenu"
                    class="absolute right-0 mt-2 w-48 bg-card border border-border rounded-md shadow-lg z-50"
                  >
                    <div class="py-1">
                      <div class="px-4 py-2 text-sm text-muted-foreground border-b border-border">
                        {{ authStore.user?.full_name }}
                        <div class="text-xs">{{ authStore.user?.email }}</div>
                      </div>
                      
                      <router-link 
                        to="/dashboard" 
                        class="block px-4 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground"
                        @click="closeUserMenu"
                      >
                        Dashboard
                      </router-link>
                      
                      <router-link 
                        to="/portfolios" 
                        class="block px-4 py-2 text-sm text-foreground hover:bg-accent hover:text-accent-foreground"
                        @click="closeUserMenu"
                      >
                        My Portfolios
                      </router-link>
                      
                      <div class="border-t border-border">
                        <button 
                          @click="handleLogout"
                          class="block w-full text-left px-4 py-2 text-sm hover:bg-accent hover:text-accent-foreground text-destructive"
                        >
                          Sign Out
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- Mobile Navigation Button -->
            <div class="md:hidden">
              <Button 
                variant="ghost" 
                size="sm"
                @click="toggleMobileMenu"
              >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </Button>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile Navigation Menu -->
      <div 
        v-if="showMobileMenu"
        class="md:hidden border-t border-border bg-background"
      >
        <div class="px-4 py-2 space-y-1">
          <!-- Dashboard - First for authenticated users -->
          <template v-if="authStore.isAuthenticated">
            <router-link 
              to="/dashboard" 
              class="block px-3 py-2 text-sm font-medium rounded-md transition-colors hover:bg-accent hover:text-accent-foreground"
              :class="{ 
                'bg-accent text-accent-foreground': $route.name === 'Dashboard',
                'text-muted-foreground': $route.name !== 'Dashboard'
              }"
              @click="closeMobileMenu"
            >
              Dashboard
            </router-link>
          </template>
          
          <!-- Cryptocurrencies -->
          <router-link 
            to="/coins" 
            class="block px-3 py-2 text-sm font-medium rounded-md transition-colors hover:bg-accent hover:text-accent-foreground"
            :class="{ 
              'bg-accent text-accent-foreground': $route.name === 'Coins' || $route.name === 'CoinsPage',
              'text-muted-foreground': $route.name !== 'Coins' && $route.name !== 'CoinsPage'
            }"
            @click="closeMobileMenu"
          >
            Cryptocurrencies
          </router-link>
          
          <!-- Portfolios - Only for authenticated users -->
          <template v-if="authStore.isAuthenticated">
            <router-link 
              to="/portfolios" 
              class="block px-3 py-2 text-sm font-medium rounded-md transition-colors hover:bg-accent hover:text-accent-foreground"
              :class="{ 
                'bg-accent text-accent-foreground': $route.name === 'Portfolio',
                'text-muted-foreground': $route.name !== 'Portfolio'
              }"
              @click="closeMobileMenu"
            >
              Portfolios
            </router-link>
          </template>

          <!-- Mobile Auth Section -->
          <div class="pt-4 border-t border-border">
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm font-medium text-foreground">Theme</span>
              <ThemeToggle />
            </div>
            
            <template v-if="!authStore.isAuthenticated && showAuthButtons">
              <div class="space-y-2">
                <router-link to="/auth/login" @click="closeMobileMenu">
                  <Button variant="ghost" size="sm" class="w-full justify-start">
                    Sign In
                  </Button>
                </router-link>
                <router-link to="/auth/register" @click="closeMobileMenu">
                  <Button size="sm" class="w-full">
                    Sign Up
                  </Button>
                </router-link>
              </div>
            </template>

            <template v-else-if="authStore.isAuthenticated">
              <div class="space-y-2">
                <div class="px-3 py-2 text-sm text-muted-foreground border-b border-border">
                  {{ authStore.user?.full_name }}
                  <div class="text-xs">{{ authStore.user?.email }}</div>
                </div>
                <button 
                  @click="handleLogout"
                  class="block w-full text-left px-3 py-2 text-sm text-destructive hover:bg-accent rounded-md"
                >
                  Sign Out
                </button>
              </div>
            </template>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { User, ChevronDown } from 'lucide-vue-next'

// Components
import Button from '@/components/ui/Button.vue'
import ThemeToggle from '@/components/ui/ThemeToggle.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const showUserMenu = ref(false)
const showMobileMenu = ref(false)
const userMenuRef = ref(null)

// Show auth buttons on public pages
const showAuthButtons = computed(() => {
  return route.meta?.showAuthButtons || false
})

const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

const closeUserMenu = () => {
  showUserMenu.value = false
}

const toggleMobileMenu = () => {
  showMobileMenu.value = !showMobileMenu.value
}

const closeMobileMenu = () => {
  showMobileMenu.value = false
}

const handleLogout = async () => {
  await authStore.logout()
  closeUserMenu()
  closeMobileMenu()
  router.push('/auth/login')
}

// Close menus when clicking outside
const handleClickOutside = (event) => {
  if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
    closeUserMenu()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script> 