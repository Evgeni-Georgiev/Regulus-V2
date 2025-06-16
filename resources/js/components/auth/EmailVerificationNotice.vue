<template>
  <Card class="w-full max-w-md mx-auto">
    <CardHeader>
      <CardTitle class="text-2xl font-bold text-center">Verify Your Email</CardTitle>
      <CardDescription class="text-center">
        We've sent a verification link to your email address
      </CardDescription>
    </CardHeader>
    <CardContent class="text-center space-y-4">
      <div class="flex justify-center">
        <Mail class="h-16 w-16 text-primary" />
      </div>
      
      <div class="space-y-2">
        <p class="text-sm text-muted-foreground">
          Please check your email and click the verification link to activate your account.
        </p>
        <p class="text-sm text-muted-foreground">
          If you don't see the email, check your spam folder.
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
      
      <!-- Resend Button -->
      <Button 
        @click="handleResendEmail"
        variant="outline" 
        class="w-full" 
        :disabled="authStore.loading || !authStore.isAuthenticated"
        data-testid="resend-email-button"
      >
        <Loader2 v-if="authStore.loading" class="mr-2 h-4 w-4 animate-spin" />
        {{ authStore.loading ? 'Sending...' : 'Resend Verification Email' }}
      </Button>
      
      <!-- Links -->
      <div class="mt-6 space-y-2">
        <router-link 
          to="/dashboard" 
          class="block text-sm text-primary hover:underline"
        >
          Continue to Dashboard
        </router-link>
        <button 
          @click="handleLogout"
          class="text-sm text-muted-foreground hover:text-foreground"
        >
          Sign out
        </button>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { AlertCircle, CheckCircle, Loader2, Mail } from 'lucide-vue-next'

// Components
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import Alert from '@/components/ui/Alert.vue'

const router = useRouter()
const authStore = useAuthStore()

const successMessage = ref('')
const errorMessage = ref('')

const handleResendEmail = async () => {
  successMessage.value = ''
  errorMessage.value = ''
  
  const result = await authStore.resendVerificationEmail()
  
  if (result.success) {
    successMessage.value = result.message
  } else {
    errorMessage.value = result.message
  }
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/auth/login')
}

// Redirect if not authenticated
onMounted(() => {
  if (!authStore.isAuthenticated) {
    router.push('/auth/login')
  }
})
</script> 