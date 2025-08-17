<template>
  
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Order" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Create Order</h1>
                <p class="text-muted-foreground">
                Add a new order to your system
                </p>
            </div>
            <Button @click="$inertia.visit(route('orders.index'))" variant="outline">
                <ArrowLeft class="mr-2 h-4 w-4" />
                Back to Orders
            </Button>
            </div>

            <!-- Order Form -->
            <Card>
            <CardHeader>
                <CardTitle>Order Information</CardTitle>
                <CardDescription>
                Enter the order details below
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submitForm" class="p-6">
                <!-- Customer Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                    <Label for="customer_name">Customer Name *</Label>
                    <Input
                        id="customer_name"
                        v-model="form.customer_name"
                        :class="{ 'border-destructive': errors.customer_name }"
                        placeholder="Enter customer name"
                    />
                    <p v-if="errors.customer_name" class="text-sm text-destructive">
                        {{ errors.customer_name }}
                    </p>
                    </div>

                    <div class="space-y-2">
                    <Label for="customer_email">Customer Email *</Label>
                    <Input
                        id="customer_email"
                        v-model="form.customer_email"
                        type="email"
                        :class="{ 'border-destructive': errors.customer_email }"
                        placeholder="customer@example.com"
                    />
                    <p v-if="errors.customer_email" class="text-sm text-destructive">
                        {{ errors.customer_email }}
                    </p>
                    </div>

                    <div class="space-y-2">
                    <Label for="customer_phone">Customer Phone</Label>
                    <Input
                        id="customer_phone"
                        v-model="form.customer_phone"
                        :class="{ 'border-destructive': errors.customer_phone }"
                        placeholder="+1 (555) 123-4567"
                    />
                    <p v-if="errors.customer_phone" class="text-sm text-destructive">
                        {{ errors.customer_phone }}
                    </p>
                    </div>

                    <div class="space-y-2">
                    <Label for="order_number">Order Number</Label>
                    <Input
                        id="order_number"
                        v-model="form.order_number"
                        :class="{ 'border-destructive': errors.order_number }"
                        placeholder="Auto-generated if empty"
                    />
                    <p v-if="errors.order_number" class="text-sm text-destructive">
                        {{ errors.order_number }}
                    </p>
                    </div>

                    <div class="space-y-2">
                    <Label for="total_amount">Total Amount *</Label>
                    <Input
                        id="total_amount"
                        v-model="form.total_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        :class="{ 'border-destructive': errors.total_amount }"
                        placeholder="0.00"
                    />
                    <p v-if="errors.total_amount" class="text-sm text-destructive">
                        {{ errors.total_amount }}
                    </p>
                    </div>

                    <div class="space-y-2">
                    <Label for="order_date">Order Date *</Label>
                    <Input
                        id="order_date"
                        v-model="form.order_date"
                        type="date"
                        :class="{ 'border-destructive': errors.order_date }"
                    />
                    <p v-if="errors.order_date" class="text-sm text-destructive">
                        {{ errors.order_date }}
                    </p>
                    </div>

                    <div class="space-y-2">
                    <Label for="status">Status *</Label>
                    <Select v-model="form.status">
                        <SelectTrigger :class="{ 'border-destructive': errors.status }">
                        <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="processing">Processing</SelectItem>
                        <SelectItem value="shipped">Shipped</SelectItem>
                        <SelectItem value="delivered">Delivered</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="errors.status" class="text-sm text-destructive">
                        {{ errors.status }}
                    </p>
                    </div>

                    <div class="space-y-2">
                    <Label for="contact_id">Link to Contact (Optional)</Label>
                    <Select v-model="form.contact_id">
                        <SelectTrigger>
                        <SelectValue placeholder="Select a contact" />
                        </SelectTrigger>
                        <SelectContent>
                        <SelectItem value="none">No contact</SelectItem>
                        <SelectItem v-for="contact in contacts" :key="contact.id" :value="contact.id">
                            {{ contact.first_name }} {{ contact.last_name }} ({{ contact.email }})
                        </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="errors.contact_id" class="text-sm text-destructive">
                        {{ errors.contact_id }}
                    </p>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Shipping Address</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="shipping_address">Street Address</Label>
                        <Input
                        id="shipping_address"
                        v-model="form.shipping_address"
                        placeholder="123 Main Street"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="shipping_city">City</Label>
                        <Input
                        id="shipping_city"
                        v-model="form.shipping_city"
                        placeholder="New York"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="shipping_state">State/Province</Label>
                        <Input
                        id="shipping_state"
                        v-model="form.shipping_state"
                        placeholder="NY"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="shipping_zip">ZIP/Postal Code</Label>
                        <Input
                        id="shipping_zip"
                        v-model="form.shipping_zip"
                        placeholder="10001"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="shipping_country">Country</Label>
                        <Input
                        id="shipping_country"
                        v-model="form.shipping_country"
                        placeholder="United States"
                        />
                    </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium">Order Items</h3>
                    <Button @click="addItem" type="button" variant="outline" size="sm">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Item
                    </Button>
                    </div>

                    <div v-if="form.items.length === 0" class="text-center py-8 border border-dashed rounded-lg">
                    <Package class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                    <p class="text-muted-foreground">No items added yet</p>
                    <Button @click="addItem" type="button" variant="outline" class="mt-2">
                        Add First Item
                    </Button>
                    </div>

                    <div v-else class="space-y-4">
                    <div v-for="(item, index) in form.items" :key="index" class="p-4 border rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div class="space-y-2">
                            <Label>Product Name *</Label>
                            <Input
                            v-model="item.product_name"
                            placeholder="Product name"
                            :class="{ 'border-destructive': errors[`items.${index}.product_name`] }"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>Quantity *</Label>
                            <Input
                            v-model="item.quantity"
                            type="number"
                            min="1"
                            placeholder="1"
                            :class="{ 'border-destructive': errors[`items.${index}.quantity`] }"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>Unit Price *</Label>
                            <Input
                            v-model="item.unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            :class="{ 'border-destructive': errors[`items.${index}.unit_price`] }"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>Total</Label>
                            <Input
                            :value="calculateItemTotal(item)"
                            readonly
                            class="bg-muted"
                            />
                        </div>

                        <div>
                            <Button @click="removeItem(index)" type="button" variant="outline" size="sm">
                            <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <div class="text-lg font-medium">
                        Total: ${{ calculateOrderTotal() }}
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">Order Notes</Label>
                    <Textarea
                    id="notes"
                    v-model="form.notes"
                    placeholder="Additional notes about this order..."
                    rows="3"
                    />
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4">
                    <Button @click="$inertia.visit(route('orders.index'))" type="button" variant="outline">
                    Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                    <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                    Create Order
                    </Button>
                </div>
                </form>
            </CardContent>
            </Card>
        </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { ArrowLeft, Plus, Trash2, Package, Loader2 } from 'lucide-vue-next'

interface Props {
  contacts: any[]
  errors: Record<string, string>
}

const props = defineProps<Props>()

const form = useForm({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  order_number: '',
  total_amount: '',
  order_date: new Date().toISOString().split('T')[0],
  status: 'pending',
  contact_id: '',
  shipping_address: '',
  shipping_city: '',
  shipping_state: '',
  shipping_zip: '',
  shipping_country: '',
  notes: '',
  items: [] as Array<{
    product_name: string
    quantity: number | string
    unit_price: number | string
  }>
})

const processing = ref(false)

const addItem = () => {
  form.items.push({
    product_name: '',
    quantity: 1,
    unit_price: 0
  })
}

const removeItem = (index: number) => {
  form.items.splice(index, 1)
}

const calculateItemTotal = (item: any) => {
  const quantity = parseFloat(String(item.quantity)) || 0
  const unitPrice = parseFloat(String(item.unit_price)) || 0
  return (quantity * unitPrice).toFixed(2)
}

const calculateOrderTotal = () => {
  const total = form.items.reduce((sum, item) => {
    const quantity = parseFloat(String(item.quantity)) || 0
    const unitPrice = parseFloat(String(item.unit_price)) || 0
    return sum + (quantity * unitPrice)
  }, 0)
  return total.toFixed(2)
}

const submitForm = () => {
  processing.value = true
  
  // Update total amount with calculated total
  form.total_amount = calculateOrderTotal()
  
  // Handle contact_id "none" value
  if (form.contact_id === 'none') {
    form.contact_id = ''
  }
  
  form.post(route('orders.store'), {
    onFinish: () => {
      processing.value = false
    }
  })
}

// Add first item by default
if (form.items.length === 0) {
  addItem()
}
</script>
