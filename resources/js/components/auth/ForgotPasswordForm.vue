<template>
  <Card class="w-full max-w-md mx-auto">
    <CardHeader>
      <CardTitle class="text-2xl font-bold text-center">Forgot Password</CardTitle>
      <CardDescription class="text-center">
        Enter your email address and we'll send you a link to reset your password
      </CardDescription>
    </CardHeader>
    <CardContent>
      <form @submit.prevent="handleForgotPassword" class="space-y-4">
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
        
        <!-- Success Alert -->
        <Alert v-if="successMessage" class="mt-4">
          <CheckCircle class="h-4 w-4" />
          <div>{{ successMessage }}</div>
        </Alert>
        
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
          data-testid="forgot-password-button"
        >
          <Loader2 v-if="authStore.loading" class="mr-2 h-4 w-4 animate-spin" />
          {{ authStore.loading ? 'Sending...' : 'Send Reset Link' }}
        </Button>
      </form>
      
      <!-- Links -->
      <div class="mt-6 text-center space-y-2">
        <router-link 
          to="/auth/login" 
          class="text-sm text-primary hover:underline"
        >
          Back to Sign In
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
import { useAuthStore } from '@/stores/auth'
import { AlertCircle, CheckCircle, Loader2 } from 'lucide-vue-next'

// Components
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import Alert from '@/components/ui/Alert.vue'

const authStore = useAuthStore()

const form = reactive({
  email: ''
})

const successMessage = ref('')
const errorMessage = ref('')

const handleForgotPassword = async () => {
  successMessage.value = ''
  errorMessage.value = ''
  authStore.clearErrors()
  
  const result = await authStore.forgotPassword(form.email)
  
  if (result.success) {
    successMessage.value = result.message
    form.email = '' // Clear form on success
  } else {
    errorMessage.value = result.message
  }
}
</script> 