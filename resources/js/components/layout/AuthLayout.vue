<template>
  <div class="min-h-screen bg-background flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo/Brand -->
      <div class="text-center mb-8">
        <router-link to="/" class="inline-block">
          <h1 class="text-3xl font-bold text-primary">Regulus</h1>
          <p class="text-sm text-muted-foreground mt-1">Crypto Portfolio Manager</p>
        </router-link>
      </div>
      
      <!-- Auth Form Content -->
      <router-view />
      
      <!-- Footer -->
      <div class="text-center mt-8 text-xs text-muted-foreground">
        <p>&copy; 2025 Regulus. All rights reserved.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
// This layout is used for authentication pages (login, register, etc.)
</script> 