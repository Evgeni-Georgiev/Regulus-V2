<template>
  <Card class="w-full max-w-md mx-auto">
    <CardHeader>
      <CardTitle class="text-2xl font-bold text-center">Welcome Back</CardTitle>
      <CardDescription class="text-center">
        Sign in to your account to continue
      </CardDescription>
    </CardHeader>
    <CardContent>
      <form @submit.prevent="handleLogin" class="space-y-4">
        <!-- Email Field -->
        <div class="space-y-2">
          <Label for="email">Email Address</Label>
          <Input
            id="email"
            type="email"
            v-model="form.email"
            placeholder="Enter your email"
            :class="{ 'border-red-500': authStore.errors.email }"
            required
            data-testid="email-input"
          />
          <p v-if="authStore.errors.email" class="text-sm text-red-500">
            {{ authStore.errors.email[0] }}
          </p>
        </div>
        
        <!-- Password Field -->
        <div class="space-y-2">
          <Label for="password">Password</Label>
          <Input
            id="password"
            type="password"
            v-model="form.password"
            placeholder="Enter your password"
            :class="{ 'border-red-500': authStore.errors.password }"
            required
            data-testid="password-input"
          />
          <p v-if="authStore.errors.password" class="text-sm text-red-500">
            {{ authStore.errors.password[0] }}
          </p>
        </div>
        
        <!-- Remember Me -->
        <div class="flex items-center space-x-2">
          <Checkbox id="remember" v-model="form.remember" />
          <Label for="remember" class="text-sm font-medium">Remember me</Label>
        </div>
        
        <!-- Error Alert -->
        <Alert v-if="errorMessage" variant="destructive" class="mt-4">
          <AlertCircle class="h-4 w-4" />
          <div>{{ errorMessage }}</div>
        </Alert>
        
        <!-- Submit Button -->
        <Button 
          type="submit" 
          class="w-full" 
          :disabled="authStore.loading"
          data-testid="login-button"
        >
          <Loader2 v-if="authStore.loading" class="mr-2 h-4 w-4 animate-spin" />
          {{ authStore.loading ? 'Signing in...' : 'Sign In' }}
        </Button>
      </form>
      
      <!-- Links -->
      <div class="mt-6 text-center space-y-2">
        <router-link 
          to="/auth/forgot-password" 
          class="text-sm text-primary hover:underline"
        >
          Forgot your password?
        </router-link>
        <div class="text-sm text-muted-foreground">
          Don't have an account? 
          <router-link 
            to="/auth/register" 
            class="text-primary hover:underline font-medium"
          >
            Sign up
          </router-link>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { AlertCircle, Loader2 } from 'lucide-vue-next'

// Components
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import Checkbox from '@/components/ui/Checkbox.vue'
import Alert from '@/components/ui/Alert.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
  remember: false
})

const errorMessage = ref('')

const handleLogin = async () => {
  errorMessage.value = ''
  authStore.clearErrors()
  
  const result = await authStore.login(form)
  
  if (result.success) {
    // Redirect to dashboard or intended page
    const redirectTo = router.currentRoute.value.query.redirect || '/dashboard'
    router.push(redirectTo)
  } else {
    errorMessage.value = result.message
  }
}
</script> 