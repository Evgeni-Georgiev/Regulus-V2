<template>
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6">Exchange Connections</h2>
        
        <!-- Connection List -->
        <div v-if="connections.length > 0" class="mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-left">
                            <th class="py-3 px-4 font-semibold">Exchange</th>
                            <th class="py-3 px-4 font-semibold">API Key</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 font-semibold">Last Synced</th>
                            <th class="py-3 px-4 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="connection in connections" :key="connection.id" 
                            class="border-t border-gray-200 dark:border-gray-700">
                            <td class="py-3 px-4">
                                <div class="flex items-center">
                                    <img :src="`/images/exchanges/${connection.exchange_name.toLowerCase()}.png`" 
                                        alt="Exchange Logo" 
                                        class="w-6 h-6 mr-2" />
                                    <span class="capitalize">{{ formatExchangeName(connection.exchange_name) }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4">{{ connection.api_key }}</td>
                            <td class="py-3 px-4">
                                <span v-if="connection.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    Active
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    Inactive
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span v-if="connection.last_synced_at">
                                    {{ formatDate(connection.last_synced_at) }}
                                </span>
                                <span v-else class="text-gray-500 dark:text-gray-400">
                                    Never
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2">
                                    <button @click="syncConnection(connection)" 
                                        class="p-1 rounded-md text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900"
                                        :disabled="isSyncing === connection.id"
                                        :class="{'opacity-50': isSyncing === connection.id}">
                                        <span v-if="isSyncing === connection.id">Syncing...</span>
                                        <span v-else>Sync Now</span>
                                    </button>
                                    <button @click="editConnection(connection)" 
                                        class="p-1 rounded-md text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        Edit
                                    </button>
                                    <button @click="deleteConnection(connection)" 
                                        class="p-1 rounded-md text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900">
                                        Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            <p>No exchange connections found.</p>
        </div>
        
        <!-- Add Connection Button -->
        <button @click="showAddModal = true" 
            class="mt-4 px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors">
            Add Exchange Connection
        </button>
        
        <!-- Add/Edit Connection Modal -->
        <Modal :show="showAddModal || !!editingConnection" @close="cancelModal">
            <div class="p-6">
                <h3 class="text-xl font-semibold mb-4">
                    {{ editingConnection ? 'Edit Exchange Connection' : 'Add Exchange Connection' }}
                </h3>
                
                <div class="space-y-4">
                    <div v-if="!editingConnection" class="form-group">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Exchange
                        </label>
                        <select v-model="newConnection.exchange_name" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">Select Exchange</option>
                            <option value="binance">Binance</option>
                            <option value="bybit">Bybit</option>
                            <option value="gate_io">Gate.io</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            API Key
                        </label>
                        <input type="text" v-model="newConnection.api_key" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white" 
                            placeholder="Enter your API key" />
                    </div>
                    
                    <div class="form-group">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            API Secret
                        </label>
                        <input type="password" v-model="newConnection.api_secret" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white" 
                            placeholder="Enter your API secret" />
                    </div>
                    
                    <div v-if="editingConnection" class="form-group">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="newConnection.is_active" 
                                class="h-4 w-4 text-green-500 focus:ring-green-400 border-gray-300 rounded" />
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Connection active</span>
                        </label>
                    </div>
                    
                    <div v-if="errorMessage" class="text-red-500 text-sm mt-2">
                        {{ errorMessage }}
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button @click="cancelModal" 
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </button>
                    <button @click="saveConnection" 
                        class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors"
                        :disabled="isSaving"
                        :class="{'opacity-50': isSaving}">
                        {{ isSaving ? 'Saving...' : (editingConnection ? 'Update' : 'Connect') }}
                    </button>
                </div>
            </div>
        </Modal>
        
        <!-- Confirmation Modal -->
        <ConfirmationModal 
            :show="!!connectionToDelete" 
            @close="connectionToDelete = null"
            @confirm="confirmDelete"
            title="Remove Exchange Connection"
            message="Are you sure you want to remove this exchange connection? This will not delete any existing transactions that were previously imported."
        />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import Modal from '@/components/ui/Modal.vue';
import ConfirmationModal from '@/components/ui/ConfirmationModal.vue';
import axios from 'axios';

// Component state
const connections = ref([]);
const showAddModal = ref(false);
const editingConnection = ref(null);
const connectionToDelete = ref(null);
const errorMessage = ref('');
const isSaving = ref(false);
const isSyncing = ref(null);

const newConnection = reactive({
    exchange_name: '',
    api_key: '',
    api_secret: '',
    is_active: true
});

// Load connections when component mounts
onMounted(async () => {
    await loadConnections();
});

// Format helpers
const formatExchangeName = (name) => {
    if (name === 'gate_io') return 'Gate.io';
    return name.charAt(0).toUpperCase() + name.slice(1);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString();
};

// CRUD operations
const loadConnections = async () => {
    try {
        const response = await axios.get('/api/exchange-connections');
        connections.value = response.data.connections;
    } catch (error) {
        console.error('Failed to load connections', error);
    }
};

const saveConnection = async () => {
    errorMessage.value = '';
    isSaving.value = true;
    
    try {
        if (editingConnection.value) {
            // Update existing connection
            await axios.put(`/api/exchange-connections/${editingConnection.value.id}`, {
                api_key: newConnection.api_key,
                api_secret: newConnection.api_secret,
                is_active: newConnection.is_active
            });
        } else {
            // Create new connection
            await axios.post('/api/exchange-connections', {
                exchange_name: newConnection.exchange_name,
                api_key: newConnection.api_key,
                api_secret: newConnection.api_secret
            });
        }
        
        await loadConnections();
        resetForm();
    } catch (error) {
        console.error('Failed to save connection', error);
        errorMessage.value = error.response?.data?.message || 'Failed to save connection';
    } finally {
        isSaving.value = false;
    }
};

const editConnection = (connection) => {
    editingConnection.value = connection;
    newConnection.exchange_name = connection.exchange_name;
    newConnection.api_key = ''; // Don't pre-fill sensitive data
    newConnection.api_secret = '';
    newConnection.is_active = connection.is_active;
};

const deleteConnection = (connection) => {
    connectionToDelete.value = connection;
};

const confirmDelete = async () => {
    try {
        await axios.delete(`/api/exchange-connections/${connectionToDelete.value.id}`);
        await loadConnections();
    } catch (error) {
        console.error('Failed to delete connection', error);
    } finally {
        connectionToDelete.value = null;
    }
};

const syncConnection = async (connection) => {
    isSyncing.value = connection.id;
    
    try {
        await axios.post(`/api/exchange-connections/${connection.id}/sync`);
        await loadConnections();
    } catch (error) {
        console.error('Failed to sync transactions', error);
    } finally {
        isSyncing.value = null;
    }
};

const cancelModal = () => {
    resetForm();
};

const resetForm = () => {
    showAddModal.value = false;
    editingConnection.value = null;
    errorMessage.value = '';
    Object.assign(newConnection, {
        exchange_name: '',
        api_key: '',
        api_secret: '',
        is_active: true
    });
};
</script> 