<template>
  
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Order Details" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Order Details</h1>
                <p class="text-muted-foreground">
                Order {{ order.order_number }}
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <Button @click="$inertia.visit(route('orders.edit', order.id))" variant="outline">
                <Edit class="mr-2 h-4 w-4" />
                Edit Order
                </Button>
                <Button @click="$inertia.visit(route('orders.index'))" variant="outline">
                <ArrowLeft class="mr-2 h-4 w-4" />
                Back to Orders
                </Button>
            </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Order Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Customer Information -->
                <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                    <User class="mr-2 h-5 w-5" />
                    Customer Information
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Name</Label>
                        <p class="text-sm">{{ order.customer_name }}</p>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Email</Label>
                        <p class="text-sm">{{ order.customer_email }}</p>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-muted-foreground">Phone</Label>
                        <p class="text-sm">{{ order.customer_phone || 'Not provided' }}</p>
                    </div>
                    <div v-if="order.contact">
                        <Label class="text-sm font-medium text-muted-foreground">Linked Contact</Label>
                        <Button 
                        @click="$inertia.visit(route('contacts.show', order.contact.id))"
                        variant="link" 
                        size="sm" 
                        class="p-0 h-auto text-sm"
                        >
                        {{ order.contact.first_name }} {{ order.contact.last_name }}
                        </Button>
                    </div>
                    </div>
                </CardContent>
                </Card>

                <!-- Shipping Address -->
                <Card v-if="hasShippingAddress">
                <CardHeader>
                    <CardTitle class="flex items-center">
                    <MapPin class="mr-2 h-5 w-5" />
                    Shipping Address
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-sm space-y-1">
                    <p v-if="order.shipping_address">{{ order.shipping_address }}</p>
                    <p>
                        <span v-if="order.shipping_city">{{ order.shipping_city }}</span>
                        <span v-if="order.shipping_state">, {{ order.shipping_state }}</span>
                        <span v-if="order.shipping_zip"> {{ order.shipping_zip }}</span>
                    </p>
                    <p v-if="order.shipping_country">{{ order.shipping_country }}</p>
                    </div>
                </CardContent>
                </Card>

                <!-- Order Items -->
                <Card>
                <CardHeader>
                    <CardTitle class="flex items-center">
                    <Package class="mr-2 h-5 w-5" />
                    Order Items
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="order.items && order.items.length > 0" class="space-y-4">
                    <div v-for="item in order.items" :key="item.id" class="flex items-center justify-between p-4 border rounded-lg">
                        <div class="flex-1">
                        <h4 class="font-medium">{{ item.product_name }}</h4>
                        <p class="text-sm text-muted-foreground">
                            Quantity: {{ item.quantity }} × ${{ item.unit_price }}
                        </p>
                        </div>
                        <div class="text-right">
                        <p class="font-medium">${{ (item.quantity * item.unit_price).toFixed(2) }}</p>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center text-lg font-semibold">
                        <span>Total:</span>
                        <span>${{ order.total_amount }}</span>
                        </div>
                    </div>
                    </div>
                    <div v-else class="text-center py-8 text-muted-foreground">
                    <Package class="mx-auto h-12 w-12 mb-4 opacity-50" />
                    <p>No items in this order</p>
                    </div>
                </CardContent>
                </Card>

                <!-- Order Notes -->
                <Card v-if="order.notes">
                <CardHeader>
                    <CardTitle class="flex items-center">
                    <FileText class="mr-2 h-5 w-5" />
                    Order Notes
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-sm whitespace-pre-wrap">{{ order.notes }}</p>
                </CardContent>
                </Card>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="p-6">
                <!-- Order Summary -->
                <Card>
                <CardHeader>
                    <CardTitle>Order Summary</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Order Number:</span>
                        <span class="font-medium">{{ order.order_number }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Order Date:</span>
                        <span>{{ formatDate(order.order_date) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Status:</span>
                        <Badge :variant="getStatusVariant(order.status)">
                        {{ order.status }}
                        </Badge>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Total Amount:</span>
                        <span class="font-semibold">${{ order.total_amount }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Created:</span>
                        <span>{{ formatDateTime(order.created_at) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Updated:</span>
                        <span>{{ formatDateTime(order.updated_at) }}</span>
                    </div>
                    </div>
                </CardContent>
                </Card>

                <!-- Quick Actions -->
                <Card>
                <CardHeader>
                    <CardTitle>Quick Actions</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2">
                    <Button @click="updateStatus('processing')" class="w-full" size="sm" variant="outline">
                    Mark as Processing
                    </Button>
                    <Button @click="updateStatus('shipped')" class="w-full" size="sm" variant="outline">
                    Mark as Shipped
                    </Button>
                    <Button @click="updateStatus('delivered')" class="w-full" size="sm" variant="outline">
                    Mark as Delivered
                    </Button>
                    <Button @click="showCancelDialog = true" class="w-full" size="sm" variant="outline">
                    Cancel Order
                    </Button>
                </CardContent>
                </Card>

                <!-- Create Campaign -->
                <Card v-if="order.contact">
                <CardHeader>
                    <CardTitle>Follow-up Actions</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2">
                    <Button @click="createFollowUpCampaign" class="w-full" size="sm">
                    <Phone class="mr-2 h-4 w-4" />
                    Create Follow-up Campaign
                    </Button>
                    <Button @click="$inertia.visit(route('contacts.show', order.contact.id))" class="w-full" size="sm" variant="outline">
                    <User class="mr-2 h-4 w-4" />
                    View Contact
                    </Button>
                </CardContent>
                </Card>
            </div>
            </div>

            <!-- Cancel Order Dialog -->
            <Dialog v-model:open="showCancelDialog">
            <DialogContent>
                <DialogHeader>
                <DialogTitle>Cancel Order</DialogTitle>
                <DialogDescription>
                    Are you sure you want to cancel order {{ order.order_number }}? This action can be reversed later.
                </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                <Button @click="showCancelDialog = false" variant="outline">
                    Keep Order
                </Button>
                <Button @click="cancelOrder" variant="destructive">
                    Cancel Order
                </Button>
                </DialogFooter>
            </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { 
  ArrowLeft, 
  Edit, 
  User, 
  MapPin, 
  Package, 
  FileText, 
  Phone 
} from 'lucide-vue-next'

interface Props {
  order: any
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = [
  { title: 'Dashboard', href: route('dashboard') },
  { title: 'Orders', href: route('orders.index') },
  { title: `Order ${props.order.order_number}`, href: '#' }
]

const showCancelDialog = ref(false)

const hasShippingAddress = computed(() => {
  return props.order.shipping_address || 
         props.order.shipping_city || 
         props.order.shipping_state || 
         props.order.shipping_zip || 
         props.order.shipping_country
})

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'delivered':
      return 'default'
    case 'shipped':
      return 'secondary'
    case 'processing':
      return 'outline'
    case 'pending':
      return 'secondary'
    case 'cancelled':
      return 'destructive'
    default:
      return 'secondary'
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const formatDateTime = (date: string) => {
  return new Date(date).toLocaleString()
}

const updateStatus = (status: string) => {
  router.patch(route('orders.update', props.order.id), {
    status: status
  })
}

const cancelOrder = () => {
  updateStatus('cancelled')
  showCancelDialog.value = false
}

const createFollowUpCampaign = () => {
  router.visit(route('campaigns.create'), {
    data: {
      contact_id: props.order.contact.id,
      name: `Follow-up for Order ${props.order.order_number}`,
      description: `Follow-up campaign for order ${props.order.order_number} placed on ${formatDate(props.order.order_date)}`
    }
  })
}
</script>
