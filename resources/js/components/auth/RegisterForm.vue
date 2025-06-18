<template>
  <Card class="w-full max-w-md mx-auto">
    <CardHeader>
      <CardTitle class="text-2xl font-bold text-center">Create Account</CardTitle>
      <CardDescription class="text-center">
        Sign up to get started with your account
      </CardDescription>
    </CardHeader>
    <CardContent>
      <form @submit.prevent="handleRegister" class="space-y-4">
        <!-- First Name Field -->
        <div class="space-y-2">
          <Label for="first_name">First Name</Label>
          <Input
            id="first_name"
            type="text"
            v-model="form.first_name"
            placeholder="Enter your first name"
            :class="{ 'border-red-500': authStore.errors.first_name }"
            required
            data-testid="first-name-input"
          />
          <p v-if="authStore.errors.first_name" class="text-sm text-red-500">
            {{ authStore.errors.first_name[0] }}
          </p>
        </div>
        
        <!-- Last Name Field -->
        <div class="space-y-2">
          <Label for="last_name">Last Name</Label>
          <Input
            id="last_name"
            type="text"
            v-model="form.last_name"
            placeholder="Enter your last name"
            :class="{ 'border-red-500': authStore.errors.last_name }"
            required
            data-testid="last-name-input"
          />
          <p v-if="authStore.errors.last_name" class="text-sm text-red-500">
            {{ authStore.errors.last_name[0] }}
          </p>
        </div>
        
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
          <p class="text-xs text-muted-foreground">
            Password must be at least 8 characters with one lowercase letter and one number
          </p>
        </div>
        
        <!-- Confirm Password Field -->
        <div class="space-y-2">
          <Label for="password_confirmation">Confirm Password</Label>
          <Input
            id="password_confirmation"
            type="password"
            v-model="form.password_confirmation"
            placeholder="Confirm your password"
            :class="{ 'border-red-500': authStore.errors.password_confirmation }"
            required
            data-testid="password-confirmation-input"
          />
          <p v-if="authStore.errors.password_confirmation" class="text-sm text-red-500">
            {{ authStore.errors.password_confirmation[0] }}
          </p>
        </div>
        
        <!-- Terms & Conditions -->
        <div class="flex items-start space-x-2">
          <Checkbox 
            id="terms" 
            v-model="form.terms" 
            :class="{ 'border-red-500': authStore.errors.terms }"
            required
          />
          <div class="space-y-1">
            <Label for="terms" class="text-sm font-medium leading-none">
              I agree to the 
              <a href="/terms" target="_blank" class="text-primary hover:underline">
                Terms & Conditions
              </a>
              and 
              <a href="/privacy" target="_blank" class="text-primary hover:underline">
                Privacy Policy
              </a>
            </Label>
            <p v-if="authStore.errors.terms" class="text-sm text-red-500">
              {{ authStore.errors.terms[0] }}
            </p>
          </div>
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
          :disabled="authStore.loading || !form.terms"
          data-testid="register-button"
        >
          <Loader2 v-if="authStore.loading" class="mr-2 h-4 w-4 animate-spin" />
          {{ authStore.loading ? 'Creating Account...' : 'Create Account' }}
        </Button>
      </form>
      
      <!-- Links -->
      <div class="mt-6 text-center">
        <div class="text-sm text-muted-foreground">
          Already have an account? 
          <router-link 
            to="/auth/login" 
            class="text-primary hover:underline font-medium"
          >
            Sign in
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
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  terms: false
})

const errorMessage = ref('')

const handleRegister = async () => {
  errorMessage.value = ''
  authStore.clearErrors()
  
  const result = await authStore.register(form)
  
  if (result.success) {
    // Redirect to email verification notice or dashboard
    router.push('/auth/verify-email')
  } else {
    errorMessage.value = result.message
  }
}
</script> 