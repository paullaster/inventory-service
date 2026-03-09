<template>
    <div class="min-h-screen bg-gray-100 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <header class="mb-8 flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">KK Wholesalers Inventory</h1>
                <div v-if="user" class="text-right">
                    <p class="font-semibold">{{ user.name }} ({{ user.role }})</p>
                    <p class="text-sm text-gray-600">{{ user.branch?.name }} / {{ user.store?.name ?? 'Main Office' }}</p>
                    <button @click="logout" class="text-sm text-red-600 hover:underline">Logout</button>
                </div>
            </header>

            <!-- Login Screen -->
            <div v-if="!user" class="flex justify-center mt-20">
                <div class="bg-white p-8 rounded shadow-md w-96">
                    <h2 class="text-xl font-bold mb-4">Login</h2>
                    <div class="space-y-4">
                        <input v-model="loginForm.email" type="email" placeholder="Email (e.g. admin@kkwholesalers.com)" class="w-full p-2 border rounded">
                        <input v-model="loginForm.password" type="password" placeholder="Password (default: password)" class="w-full p-2 border rounded">
                        <button @click="handleLogin" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Login</button>
                        <p v-if="error" class="text-red-500 text-sm mt-2">{{ error }}</p>
                    </div>
                </div>
            </div>

            <!-- Dashboard -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left: Actions & Forms -->
                <div class="space-y-8">
                    <div class="bg-white p-6 rounded shadow-md">
                        <h2 class="text-xl font-bold mb-4">Stock Movement</h2>
                        <div class="space-y-4">
                            <select v-model="form.product_id" class="w-full p-2 border rounded">
                                <option value="">Select Product</option>
                                <option v-for="inv in inventory" :key="inv.id" :value="inv.product.id">
                                    {{ inv.product.name }} (SKU: {{ inv.product.sku }})
                                </option>
                            </select>
                            
                            <!-- Simplified for demo: selects the store of the user or first store -->
                            <select v-model="form.store_id" class="w-full p-2 border rounded">
                                <option v-if="user.store" :value="user.store.id">{{ user.store.name }}</option>
                                <template v-else>
                                    <option v-for="s in stores" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </template>
                            </select>

                            <select v-model="form.type" class="w-full p-2 border rounded">
                                <option value="sale">Sale (-)</option>
                                <option value="adjustment">Adjustment (+/-)</option>
                                <option value="procurement">Procurement (+)</option>
                            </select>

                            <input v-model.number="form.quantity" type="number" placeholder="Quantity" class="w-full p-2 border rounded">
                            
                            <button @click="submitMovement" class="w-full bg-green-600 text-white p-2 rounded hover:bg-green-700">Submit Movement</button>
                        </div>
                    </div>

                    <!-- Transfer Component -->
                    <div class="bg-white p-6 rounded shadow-md">
                        <h2 class="text-xl font-bold mb-4">Inter-store Transfer</h2>
                        <p class="text-sm text-gray-500 mb-4">Moves stock between internal locations.</p>
                        <!-- Form omitted for brevity but logic is in backend -->
                        <p class="text-xs italic">Backend ready: InventoryService@transferStock</p>
                    </div>
                </div>

                <!-- Right: Inventory & Audit -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Inventory List -->
                    <div class="bg-white p-6 rounded shadow-md">
                        <div class="flex justify-between mb-4">
                            <h2 class="text-xl font-bold">Current Balances</h2>
                            <button @click="fetchData" class="text-blue-600 hover:underline">Refresh</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-2">Product</th>
                                        <th>Branch</th>
                                        <th>Store</th>
                                        <th class="text-right">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="inv in inventory" :key="inv.id" class="border-b hover:bg-gray-50">
                                        <td class="py-2">{{ inv.product.name }} <span class="text-xs text-gray-500">({{ inv.product.sku }})</span></td>
                                        <td>{{ inv.store.branch.name }}</td>
                                        <td>{{ inv.store.name }}</td>
                                        <td class="text-right font-mono font-bold">{{ inv.balance }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Audit Trail -->
                    <div class="bg-white p-6 rounded shadow-md">
                        <h2 class="text-xl font-bold mb-4">Audit Trail (Ledger)</h2>
                        <div class="overflow-x-auto text-xs">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-2">Time</th>
                                        <th>Store</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-right">Balance</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="m in history" :key="m.id" class="border-b hover:bg-gray-50">
                                        <td class="py-2">{{ new Date(m.created_at).toLocaleString() }}</td>
                                        <td>{{ m.store.name }}</td>
                                        <td>{{ m.product.name }}</td>
                                        <td><span class="px-1 rounded bg-gray-200 uppercase">{{ m.type }}</span></td>
                                        <td class="text-right" :class="m.quantity < 0 ? 'text-red-600' : 'text-green-600'">
                                            {{ m.quantity }}
                                        </td>
                                        <td class="text-right font-bold">{{ m.balance }}</td>
                                        <td>{{ m.user.name }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const user = ref(JSON.parse(localStorage.getItem('user')) || null);
const token = ref(localStorage.getItem('token') || null);
const inventory = ref([]);
const history = ref([]);
const stores = ref([]); // Only for admins
const error = ref(null);

const loginForm = ref({ email: '', password: 'password' });
const form = ref({ product_id: '', store_id: '', type: 'sale', quantity: 0 });

const handleLogin = async () => {
    error.value = null;
    try {
        const res = await fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(loginForm.value)
        });
        const data = await res.json();
        if (data.token) {
            user.value = data.user;
            token.value = data.token;
            localStorage.setItem('user', JSON.stringify(data.user));
            localStorage.setItem('token', data.token);
            fetchData();
        } else {
            error.value = data.message;
        }
    } catch (e) {
        error.value = 'Login failed.';
    }
};

const fetchData = async () => {
    if (!token.value) return;
    
    // Inventory
    const invRes = await fetch('/api/inventory', {
        headers: { 'Authorization': `Bearer ${token.value}` }
    });
    const invData = await invRes.json();
    inventory.value = invData.data;

    // History
    const histRes = await fetch('/api/inventory/history', {
        headers: { 'Authorization': `Bearer ${token.value}` }
    });
    const histData = await histRes.json();
    history.value = histData.data;
};

const submitMovement = async () => {
    if (!form.value.product_id || !form.value.store_id || !form.value.quantity) {
        alert('Please fill all fields');
        return;
    }
    
    try {
        const res = await fetch('/api/inventory/movement', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token.value}`
            },
            body: JSON.stringify(form.value)
        });
        const data = await res.json();
        if (res.ok) {
            alert(data.message);
            fetchData();
            form.value.quantity = 0;
        } else {
            alert(data.message || 'Error occurred');
        }
    } catch (e) {
        alert('Submission failed.');
    }
};

const logout = () => {
    user.value = null;
    token.value = null;
    localStorage.clear();
};

onMounted(() => {
    if (token.value) {
        fetchData();
        // Set default store for convenience
        if (user.value.store_id) {
            form.value.store_id = user.value.store_id;
        }
    }
});
</script>
