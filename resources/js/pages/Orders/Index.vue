<template>
  
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Orders" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Orders</h1>
                <p class="text-muted-foreground">
                Manage your e-commerce orders and customer data
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <Button @click="$inertia.visit(route('orders.create'))" class="flex items-center space-x-2">
                <Plus class="h-4 w-4" />
                <span>Add Order</span>
                </Button>
            </div>
            </div>

            <!-- Filters and Search -->
            <Card>
            <CardContent class="p-6">
                <div class="flex flex-col space-y-4 md:flex-row md:space-y-0 md:space-x-4">
                <div class="flex-1">
                    <Input
                    v-model="searchQuery"
                    placeholder="Search orders..."
                    class="max-w-sm"
                    @input="debouncedSearch"
                    />
                </div>
                <div class="flex space-x-2">
                    <Select v-model="statusFilter">
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="All Statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Statuses</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="processing">Processing</SelectItem>
                        <SelectItem value="shipped">Shipped</SelectItem>
                        <SelectItem value="delivered">Delivered</SelectItem>
                        <SelectItem value="cancelled">Cancelled</SelectItem>
                    </SelectContent>
                    </Select>
                    <Button @click="exportOrders" variant="outline">
                    <Download class="mr-2 h-4 w-4" />
                    Export
                    </Button>
                    <Button @click="showImportModal = true" variant="outline">
                    <Upload class="mr-2 h-4 w-4" />
                    Import
                    </Button>
                </div>
                </div>
            </CardContent>
            </Card>

            <!-- Orders Table -->
            <Card>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="h-12 px-4 text-left align-middle font-medium">Order #</th>
                        <th class="h-12 px-4 text-left align-middle font-medium">Customer</th>
                        <th class="h-12 px-4 text-left align-middle font-medium">Date</th>
                        <th class="h-12 px-4 text-left align-middle font-medium">Status</th>
                        <th class="h-12 px-4 text-left align-middle font-medium">Total</th>
                        <th class="h-12 px-4 text-left align-middle font-medium">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="order in orders.data" :key="order.id" class="border-b">
                        <td class="px-4 py-3">
                        <div class="font-medium">{{ order.order_number }}</div>
                        </td>
                        <td class="px-4 py-3">
                        <div>
                            <div class="font-medium">{{ order.customer_name }}</div>
                            <div class="text-sm text-muted-foreground">{{ order.customer_email }}</div>
                        </div>
                        </td>
                        <td class="px-4 py-3">
                        <div class="text-sm">{{ formatDate(order.order_date) }}</div>
                        </td>
                        <td class="px-4 py-3">
                        <Badge :variant="getStatusVariant(order.status)">
                            {{ order.status }}
                        </Badge>
                        </td>
                        <td class="px-4 py-3">
                        <div class="font-medium">${{ order.total_amount }}</div>
                        </td>
                        <td class="px-4 py-3">
                        <div class="flex items-center space-x-2">
                            <Button 
                            @click="$inertia.visit(route('orders.show', order.id))"
                            variant="ghost" 
                            size="sm"
                            >
                            <Eye class="h-4 w-4" />
                            </Button>
                            <Button 
                            @click="$inertia.visit(route('orders.edit', order.id))"
                            variant="ghost" 
                            size="sm"
                            >
                            <Edit class="h-4 w-4" />
                            </Button>
                            <Button 
                            @click="confirmDelete(order)"
                            variant="ghost" 
                            size="sm"
                            class="text-destructive hover:text-destructive"
                            >
                            <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                </div>

                <!-- Pagination -->
                <div v-if="orders.last_page > 1" class="p-4 border-t">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-muted-foreground">
                    Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} results
                    </div>
                    <div class="flex items-center space-x-2">
                    <Button
                        v-for="page in paginationPages"
                        :key="page"
                        @click="goToPage(page)"
                        :variant="page === orders.current_page ? 'default' : 'outline'"
                        size="sm"
                    >
                        {{ page }}
                    </Button>
                    </div>
                </div>
                </div>

                <!-- Empty State -->
                <div v-if="orders.data.length === 0" class="text-center py-12">
                <ShoppingCart class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                <h3 class="text-lg font-medium">No orders found</h3>
                <p class="text-muted-foreground mb-4">Get started by creating your first order</p>
                <Button @click="$inertia.visit(route('orders.create'))">
                    Create Order
                </Button>
                </div>
            </CardContent>
            </Card>

            <!-- Import Modal -->
            <Dialog v-model:open="showImportModal">
            <DialogContent>
                <DialogHeader>
                <DialogTitle>Import Orders</DialogTitle>
                <DialogDescription>
                    Upload a CSV file to import orders in bulk
                </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                <div>
                    <Label for="file">CSV File</Label>
                    <Input
                    id="file"
                    type="file"
                    accept=".csv"
                    @change="handleFileSelect"
                    class="mt-1"
                    />
                </div>
                <div class="text-sm text-muted-foreground">
                    <p>CSV should include columns: order_number, customer_name, customer_email, customer_phone, total_amount, order_date, status</p>
                </div>
                </div>
                <DialogFooter>
                <Button @click="showImportModal = false" variant="outline">
                    Cancel
                </Button>
                <Button @click="importOrders" :disabled="!selectedFile || importing">
                    <Loader2 v-if="importing" class="mr-2 h-4 w-4 animate-spin" />
                    Import
                </Button>
                </DialogFooter>
            </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                <DialogTitle>Delete Order</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete order {{ orderToDelete?.order_number }}? This action cannot be undone.
                </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                <Button @click="showDeleteDialog = false" variant="outline">
                    Cancel
                </Button>
                <Button @click="deleteOrder" variant="destructive" :disabled="deleting">
                    <Loader2 v-if="deleting" class="mr-2 h-4 w-4 animate-spin" />
                    Delete
                </Button>
                </DialogFooter>
            </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { 
  Plus, 
  Download, 
  Upload, 
  Eye, 
  Edit, 
  Trash2, 
  ShoppingCart,
  Loader2
} from 'lucide-vue-next'

interface Props {
  orders: any
  filters: any
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = [
  { title: 'Dashboard', href: route('dashboard') },
  { title: 'Orders', href: '#' }
]

const searchQuery = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || 'all')
const showImportModal = ref(false)
const showDeleteDialog = ref(false)
const orderToDelete = ref<any>(null)
const selectedFile = ref<File | null>(null)
const importing = ref(false)
const deleting = ref(false)

// Simple debounce function
const debounce = (func: Function, wait: number) => {
  let timeout: number
  return (...args: any[]) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => func.apply(null, args), wait)
  }
}

const debouncedSearch = debounce(() => {
  updateFilters()
}, 300)

const updateFilters = () => {
  router.get(route('orders.index'), {
    search: searchQuery.value,
    status: statusFilter.value === 'all' ? '' : statusFilter.value,
  }, {
    preserveState: true,
    replace: true,
  })
}

const paginationPages = computed(() => {
  const pages = []
  const currentPage = props.orders.current_page
  const lastPage = props.orders.last_page
  
  for (let i = Math.max(1, currentPage - 2); i <= Math.min(lastPage, currentPage + 2); i++) {
    pages.push(i)
  }
  
  return pages
})

const goToPage = (page: number) => {
  router.get(route('orders.index'), {
    page,
    search: searchQuery.value,
    status: statusFilter.value,
  })
}

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

const confirmDelete = (order: any) => {
  orderToDelete.value = order
  showDeleteDialog.value = true
}

const deleteOrder = async () => {
  if (!orderToDelete.value) return
  
  deleting.value = true
  
  router.delete(route('orders.destroy', orderToDelete.value.id), {
    onFinish: () => {
      deleting.value = false
      showDeleteDialog.value = false
      orderToDelete.value = null
    }
  })
}

const exportOrders = () => {
  window.location.href = route('orders.export')
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  selectedFile.value = target.files?.[0] || null
}

const importOrders = async () => {
  if (!selectedFile.value) return
  
  importing.value = true
  
  const formData = new FormData()
  formData.append('file', selectedFile.value)
  
  router.post(route('orders.import'), formData, {
    onFinish: () => {
      importing.value = false
      showImportModal.value = false
      selectedFile.value = null
    }
  })
}

watch(statusFilter, updateFilters)
</script>
