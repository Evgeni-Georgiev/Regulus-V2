<template>
  <div class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-card rounded-lg border p-6">
      <h1 class="text-3xl font-bold mb-2">
        Welcome back, {{ authStore.user?.first_name }}!
      </h1>
      <p class="text-muted-foreground">
        Manage your cryptocurrency portfolio and track your investments.
      </p>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <Card class="p-6">
        <CardHeader class="p-0 mb-4">
          <CardTitle class="text-lg">Portfolio Overview</CardTitle>
          <CardDescription>
            View and manage your portfolios
          </CardDescription>
        </CardHeader>
        <router-link to="/portfolios">
          <Button class="w-full">
            View Portfolios
          </Button>
        </router-link>
      </Card>

      <Card class="p-6">
        <CardHeader class="p-0 mb-4">
          <CardTitle class="text-lg">Market Data</CardTitle>
          <CardDescription>
            Browse cryptocurrency prices
          </CardDescription>
        </CardHeader>
        <router-link to="/coins">
          <Button variant="outline" class="w-full">
            View Market
          </Button>
        </router-link>
      </Card>

      <Card class="p-6">
        <CardHeader class="p-0 mb-4">
          <CardTitle class="text-lg">Account Settings</CardTitle>
          <CardDescription>
            Manage your account preferences
          </CardDescription>
        </CardHeader>
        <Button variant="outline" class="w-full" disabled>
          Coming Soon
        </Button>
      </Card>
    </div>

    <!-- Email Verification Notice -->
    <Alert v-if="!authStore.isEmailVerified" class="border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20">
      <AlertCircle class="h-4 w-4" />
      <div>
        <h4 class="font-medium">Email Verification Required</h4>
        <p class="text-sm mt-1">
          Please verify your email address to access all features.
          <router-link to="/auth/verify-email" class="underline font-medium">
            Verify now
          </router-link>
        </p>
      </div>
    </Alert>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import { AlertCircle } from 'lucide-vue-next'

// Components
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import Button from '@/components/ui/Button.vue'
import Alert from '@/components/ui/Alert.vue'

const authStore = useAuthStore()
</script> 