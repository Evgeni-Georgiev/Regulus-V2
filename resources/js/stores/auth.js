import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  const loading = ref(false)
  const errors = ref({})

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isEmailVerified = computed(() => user.value?.email_verified_at !== null)

  // Actions
  const setToken = (newToken) => {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('auth_token', newToken)
      axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
    } else {
      localStorage.removeItem('auth_token')
      delete axios.defaults.headers.common['Authorization']
    }
  }

  const setUser = (userData) => {
    user.value = userData
  }

  const setErrors = (errorData) => {
    errors.value = errorData
  }

  const clearErrors = () => {
    errors.value = {}
  }

  const register = async (formData) => {
    loading.value = true
    clearErrors()

    try {
      const response = await axios.post('/api/auth/register', formData)
      
      setToken(response.data.token)
      setUser(response.data.user)
      
      return { success: true, data: response.data }
    } catch (error) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors)
      }
      return { 
        success: false, 
        message: error.response?.data?.message || 'Registration failed' 
      }
    } finally {
      loading.value = false
    }
  }

  const login = async (credentials) => {
    loading.value = true
    clearErrors()

    try {
      const response = await axios.post('/api/auth/login', credentials)
      
      setToken(response.data.token)
      setUser(response.data.user)
      
      return { success: true, data: response.data }
    } catch (error) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors)
      }
      return { 
        success: false, 
        message: error.response?.data?.message || 'Login failed' 
      }
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    loading.value = true

    try {
      await axios.post('/api/auth/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      setToken(null)
      setUser(null)
      clearErrors()
      loading.value = false
    }
  }

  const fetchUser = async () => {
    if (!token.value) return

    loading.value = true

    try {
      const response = await axios.get('/api/auth/user')
      setUser(response.data.user)
      return { success: true, data: response.data }
    } catch (error) {
      if (error.response?.status === 401) {
        // Token is invalid, clear auth state
        setToken(null)
        setUser(null)
      }
      return { success: false, message: 'Failed to fetch user data' }
    } finally {
      loading.value = false
    }
  }

  const forgotPassword = async (email) => {
    loading.value = true
    clearErrors()

    try {
      const response = await axios.post('/api/auth/password/forgot', { email })
      return { success: true, message: response.data.message }
    } catch (error) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors)
      }
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to send reset email' 
      }
    } finally {
      loading.value = false
    }
  }

  const resetPassword = async (resetData) => {
    loading.value = true
    clearErrors()

    try {
      const response = await axios.post('/api/auth/password/reset', resetData)
      return { success: true, message: response.data.message }
    } catch (error) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors)
      }
      return { 
        success: false, 
        message: error.response?.data?.message || 'Password reset failed' 
      }
    } finally {
      loading.value = false
    }
  }

  const changePassword = async (passwordData) => {
    loading.value = true
    clearErrors()

    try {
      const response = await axios.post('/api/auth/password/change', passwordData)
      return { success: true, message: response.data.message }
    } catch (error) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors)
      }
      return { 
        success: false, 
        message: error.response?.data?.message || 'Password change failed' 
      }
    } finally {
      loading.value = false
    }
  }

  const resendVerificationEmail = async () => {
    loading.value = true

    try {
      const response = await axios.post('/api/auth/email/resend')
      return { success: true, message: response.data.message }
    } catch (error) {
      return { 
        success: false, 
        message: error.response?.data?.message || 'Failed to resend verification email' 
      }
    } finally {
      loading.value = false
    }
  }

  // Initialize auth state
  const initializeAuth = async () => {
    if (token.value) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      await fetchUser()
    }
  }

  return {
    // State
    user,
    token,
    loading,
    errors,
    
    // Getters
    isAuthenticated,
    isEmailVerified,
    
    // Actions
    register,
    login,
    logout,
    fetchUser,
    forgotPassword,
    resetPassword,
    changePassword,
    resendVerificationEmail,
    initializeAuth,
    clearErrors
  }
}) 