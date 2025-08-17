<template>

    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Contacts" />

        <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
            <h1 class="text-3xl font-bold tracking-tight">Contacts</h1>
            <p class="text-muted-foreground">
                Manage your contact database and track call history.
            </p>
            </div>
            <div class="flex items-center space-x-2">
            <Button @click="showImportDialog = true" variant="outline">
                <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                class="mr-2 h-4 w-4"
                >
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14,2 14,8 20,8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
                <polyline points="10,9 9,9 8,9" />
                </svg>
                Import CSV
            </Button>
            <Button @click="router.visit(route('contacts.create'))">
                <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                class="mr-2 h-4 w-4"
                >
                <circle cx="12" cy="12" r="10" />
                <path d="M8 12h8" />
                <path d="M12 8v8" />
                </svg>
                Add Contact
            </Button>
            </div>
        </div>

        <!-- Filters -->
        <Card>
            <CardContent class="pt-6">
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                <Input
                    v-model="localFilters.search"
                    placeholder="Search contacts..."
                    class="max-w-sm"
                    @input="debouncedFilter"
                />
                </div>
                <Select v-model="localFilters.status">
                <SelectTrigger class="w-40">
                    <SelectValue placeholder="All Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Status</SelectItem>
                    <SelectItem value="new">New</SelectItem>
                    <SelectItem value="contacted">Contacted</SelectItem>
                    <SelectItem value="interested">Interested</SelectItem>
                    <SelectItem value="voicemail">Voicemail</SelectItem>
                    <SelectItem value="failed">Failed</SelectItem>
                </SelectContent>
                </Select>
                <Select v-model="localFilters.segment">
                <SelectTrigger class="w-40">
                    <SelectValue placeholder="All Segments" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Segments</SelectItem>
                    <SelectItem
                    v-for="segment in segments"
                    :key="segment"
                    :value="segment"
                    >
                    {{ segment }}
                    </SelectItem>
                </SelectContent>
                </Select>
                <Select v-model="localFilters.dnc">
                <SelectTrigger class="w-40">
                    <SelectValue placeholder="DNC Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Contacts</SelectItem>
                    <SelectItem value="false">Callable</SelectItem>
                    <SelectItem value="true">Do Not Call</SelectItem>
                </SelectContent>
                </Select>
                <Button @click="applyFilters" variant="outline">
                Apply Filters
                </Button>
                <Button @click="clearFilters" variant="ghost">
                Clear
                </Button>
            </div>
            </CardContent>
        </Card>

        <!-- Bulk Actions Bar -->
        <div v-if="selectedContacts.length > 0" class="flex items-center justify-between p-4 bg-muted rounded-lg">
            <div class="text-sm font-medium">
            {{ selectedContacts.length }} contact{{ selectedContacts.length === 1 ? '' : 's' }} selected
            </div>
            <div class="flex items-center space-x-2">
            <Button @click="bulkAddToDnc" variant="outline" size="sm">
                Add to DNC
            </Button>
            <Button @click="bulkRemoveFromDnc" variant="outline" size="sm">
                Remove from DNC
            </Button>
            <Button @click="bulkExport" variant="outline" size="sm">
                Export Selected
            </Button>
            <Button @click="selectedContacts = []" variant="ghost" size="sm">
                Clear Selection
            </Button>
            </div>
        </div>

        <!-- Contacts Table -->
        <Card>
            <CardContent class="p-0">
            <div class="relative w-full overflow-auto">
                <Table>
                <TableHeader>
                    <TableRow>
                    <TableHead class="w-12">
                        <Checkbox
                        :checked="selectedContacts.length === contacts.data.length"
                        @update:checked="toggleAllContacts"
                        />
                    </TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>Phone</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Segment</TableHead>
                    <TableHead>DNC</TableHead>
                    <TableHead>Last Contact</TableHead>
                    <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="contact in contacts.data" :key="contact.id">
                    <TableCell>
                        <Checkbox
                        :checked="selectedContacts.includes(contact.id)"
                        @update:checked="toggleContact(contact.id)"
                        />
                    </TableCell>
                    <TableCell>
                        <div class="font-medium">
                        {{ contact.first_name }} {{ contact.last_name }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                        {{ contact.city }}, {{ contact.state }}
                        </div>
                    </TableCell>
                    <TableCell>
                        <div class="text-sm">{{ contact.email || 'No email' }}</div>
                    </TableCell>
                    <TableCell>
                        <div class="font-mono text-sm">{{ contact.phone }}</div>
                    </TableCell>
                    <TableCell>
                        <Badge :variant="getStatusVariant(contact.status)">
                        {{ contact.status }}
                        </Badge>
                    </TableCell>
                    <TableCell>
                        <div class="text-sm">{{ contact.segment || 'None' }}</div>
                    </TableCell>
                    <TableCell>
                        <Badge v-if="contact.is_dnc" variant="destructive">
                        DNC
                        </Badge>
                        <span v-else class="text-sm text-muted-foreground">Callable</span>
                    </TableCell>
                    <TableCell>
                        <div class="text-sm">
                        {{ contact.last_contacted ? formatDate(contact.last_contacted) : 'Never' }}
                        </div>
                    </TableCell>
                    <TableCell class="text-right">
                        <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" class="h-8 w-8 p-0">
                            <span class="sr-only">Open menu</span>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                class="h-4 w-4"
                            >
                                <circle cx="12" cy="12" r="1" />
                                <circle cx="19" cy="12" r="1" />
                                <circle cx="5" cy="12" r="1" />
                            </svg>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="router.visit(route('contacts.show', contact.id))">
                            View Details
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="router.visit(route('contacts.edit', contact.id))">
                            Edit Contact
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                            v-if="!contact.is_dnc"
                            @click="addToDnc(contact.id)"
                            >
                            Add to DNC
                            </DropdownMenuItem>
                            <DropdownMenuItem
                            v-if="contact.is_dnc"
                            @click="removeFromDnc(contact.id)"
                            >
                            Remove from DNC
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                            @click="deleteContact(contact.id)"
                            class="text-destructive"
                            >
                            Delete Contact
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                        </DropdownMenu>
                    </TableCell>
                    </TableRow>
                </TableBody>
                </Table>
            </div>
            </CardContent>
        </Card>

        <!-- Pagination -->
        <div class="flex items-center justify-between">
            <div class="text-sm text-muted-foreground">
            Showing {{ contacts.from }} to {{ contacts.to }} of {{ contacts.total }} results
            </div>
            <div class="flex items-center space-x-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="!contacts.prev_page_url"
                @click="contacts.prev_page_url && router.visit(contacts.prev_page_url)"
            >
                Previous
            </Button>
            <Button
                variant="outline"
                size="sm"
                :disabled="!contacts.next_page_url"
                @click="contacts.next_page_url && router.visit(contacts.next_page_url)"
            >
                Next
            </Button>
            </div>
        </div>

        <!-- Import Dialog -->
        <Dialog v-model:open="showImportDialog">
            <DialogContent>
            <DialogHeader>
                <DialogTitle>Import Contacts</DialogTitle>
                <DialogDescription>
                Upload a CSV file to import contacts into your database.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-4">
                <div class="space-y-2">
                <Label for="csv-file">CSV File</Label>
                <Input
                    id="csv-file"
                    type="file"
                    accept=".csv"
                    @change="handleFileSelect"
                />
                <div class="text-sm text-muted-foreground">
                    CSV should include columns: first_name, last_name, email, phone
                </div>
                </div>
                <div class="flex justify-end space-x-2">
                <Button @click="showImportDialog = false" variant="outline">
                    Cancel
                </Button>
                <Button @click="importContacts" :disabled="!selectedFile">
                    Import
                </Button>
                </div>
            </div>
            </DialogContent>
        </Dialog>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'

// Simple debounce function
function debounce<T extends (...args: any[]) => any>(func: T, wait: number): T {
  let timeout: number
  return ((...args: any[]) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => func(...args), wait)
  }) as T
}
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Card,
  CardContent,
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'

interface Contact {
  id: number
  first_name: string
  last_name: string
  email?: string
  phone: string
  city?: string
  state?: string
  segment?: string
  status: string
  is_dnc: boolean
  last_contacted?: string
}

interface PaginatedContacts {
  data: Contact[]
  from: number
  to: number
  total: number
  prev_page_url?: string
  next_page_url?: string
}

interface Props {
  contacts: PaginatedContacts
  segments: string[]
  filters: {
    search?: string
    status?: string
    segment?: string
    dnc?: string
    sort?: string
    direction?: string
  }
}

const props = defineProps<Props>()

// Breadcrumbs
const breadcrumbs = [
  { title: 'Dashboard', href: route('dashboard') },
  { title: 'Contacts', href: '#' }
]

const selectedContacts = ref<number[]>([])
const showImportDialog = ref(false)
const selectedFile = ref<File | null>(null)

const localFilters = reactive({
  search: props.filters.search || '',
  status: props.filters.status || 'all',
  segment: props.filters.segment || 'all',
  dnc: props.filters.dnc || 'all',
})

const debouncedFilter = debounce(applyFilters, 300)

function applyFilters() {
  const filters = {
    search: localFilters.search,
    status: localFilters.status === 'all' ? '' : localFilters.status,
    segment: localFilters.segment === 'all' ? '' : localFilters.segment,
    dnc: localFilters.dnc === 'all' ? '' : localFilters.dnc,
  }
  
  router.visit(route('contacts.index'), {
    data: filters,
    preserveState: true,
    preserveScroll: true,
  })
}

function clearFilters() {
  localFilters.search = ''
  localFilters.status = 'all'
  localFilters.segment = 'all'
  localFilters.dnc = 'all'
  applyFilters()
}

function toggleContact(contactId: number) {
  const index = selectedContacts.value.indexOf(contactId)
  if (index > -1) {
    selectedContacts.value.splice(index, 1)
  } else {
    selectedContacts.value.push(contactId)
  }
}

function toggleAllContacts(checked: boolean) {
  if (checked) {
    selectedContacts.value = props.contacts.data.map(c => c.id)
  } else {
    selectedContacts.value = []
  }
}

function addToDnc(contactId: number) {
  router.post(route('contacts.dnc.add', contactId))
}

function removeFromDnc(contactId: number) {
  router.delete(route('contacts.dnc.remove', contactId))
}

function deleteContact(contactId: number) {
  if (confirm('Are you sure you want to delete this contact?')) {
    router.delete(route('contacts.destroy', contactId))
  }
}

function bulkAddToDnc() {
  // Implementation for bulk DNC addition
  console.log('Bulk add to DNC:', selectedContacts.value)
}

function bulkRemoveFromDnc() {
  // Implementation for bulk DNC removal
  console.log('Bulk remove from DNC:', selectedContacts.value)
}

function bulkExport() {
  // Implementation for bulk export
  console.log('Bulk export:', selectedContacts.value)
}

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement
  selectedFile.value = target.files?.[0] || null
}

function importContacts() {
  if (!selectedFile.value) return
  
  const formData = new FormData()
  formData.append('file', selectedFile.value)
  
  router.post(route('contacts.import'), formData, {
    onSuccess: () => {
      showImportDialog.value = false
      selectedFile.value = null
    }
  })
}

function getStatusVariant(status: string): "default" | "outline" | "secondary" | "destructive" {
  const variants: Record<string, "default" | "outline" | "secondary" | "destructive"> = {
    new: 'secondary',
    contacted: 'default',
    interested: 'default',
    voicemail: 'outline',
    failed: 'destructive'
  }
  return variants[status] || 'secondary'
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString()
}
</script>
