<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${campaign.name}`" />
  
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                <h1 class="text-3xl font-bold tracking-tight">Edit Campaign</h1>
                <p class="text-muted-foreground">
                    Modify your campaign settings and configuration.
                </p>
                </div>
                <Button @click="router.visit(route('campaigns.show', campaign.id))" variant="outline">
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
                    <path d="m12 19-7-7 7-7" />
                    <path d="M19 12H5" />
                </svg>
                Back to Campaign
                </Button>
            </div>

            <!-- Campaign Form -->
            <Card>
                <CardHeader>
                <CardTitle>Campaign Details</CardTitle>
                <CardDescription>
                    Update your campaign settings and parameters.
                </CardDescription>
                </CardHeader>
                <CardContent>
                <form @submit.prevent="submitForm" class="p-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <Label for="name">Campaign Name *</Label>
                        <Input
                        id="name"
                        v-model="form.name"
                        placeholder="Enter campaign name"
                        :class="{ 'border-destructive': errors.name }"
                        />
                        <div v-if="errors.name" class="text-sm text-destructive">
                        {{ errors.name }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="agent_id">AI Agent</Label>
                        <Select v-model="form.agent_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select an agent (optional)" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">No Agent</SelectItem>
                            <SelectItem
                            v-for="agent in agents"
                            :key="agent.id"
                            :value="agent.id.toString()"
                            >
                            {{ agent.name }}
                            </SelectItem>
                        </SelectContent>
                        </Select>
                        <div v-if="errors.agent_id" class="text-sm text-destructive">
                        {{ errors.agent_id }}
                        </div>
                    </div>
                    </div>

                    <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Describe the purpose of this campaign"
                        rows="3"
                    />
                    <div v-if="errors.description" class="text-sm text-destructive">
                        {{ errors.description }}
                    </div>
                    </div>

                    <!-- Data Source -->
                    <div class="space-y-4">
                    <div>
                        <Label>Data Source *</Label>
                        <p class="text-sm text-muted-foreground">
                        Choose what type of contacts to call
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div
                        v-for="source in dataSources"
                        :key="source.value"
                        @click="form.data_source_type = source.value"
                        class="cursor-pointer rounded-lg border p-4 transition-colors"
                        :class="{
                            'border-primary bg-primary/5': form.data_source_type === source.value,
                            'border-border hover:border-primary/50': form.data_source_type !== source.value
                        }"
                        >
                        <div class="flex items-start space-x-3">
                            <div class="text-2xl">{{ source.icon }}</div>
                            <div>
                            <h3 class="font-medium">{{ source.label }}</h3>
                            <p class="text-sm text-muted-foreground">{{ source.description }}</p>
                            </div>
                        </div>
                        </div>
                    </div>
                    
                    <!-- Import Data Section -->
                    <div class="mt-6 p-4 bg-muted/50 rounded-lg border">
                        <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="font-medium">Import Additional Data</h4>
                            <p class="text-sm text-muted-foreground">
                            Import more {{ getDataSourceLabel() }} data from CSV or Excel file
                            </p>
                        </div>
                        <Button @click="showImportDialog = true" variant="outline" size="sm">
                            <Upload class="mr-2 h-4 w-4" />
                            Import Data
                        </Button>
                        </div>
                        
                        <div v-if="importedDataCount > 0" class="flex items-center text-sm text-green-600">
                        <CheckCircle class="mr-2 h-4 w-4" />
                        {{ importedDataCount }} records imported successfully
                        </div>
                    </div>
                    </div>

                    <!-- Call Settings -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <Label for="max_retries">Max Retries</Label>
                        <Input
                        id="max_retries"
                        v-model.number="form.max_retries"
                        type="number"
                        min="0"
                        max="10"
                        />
                        <div v-if="errors.max_retries" class="text-sm text-destructive">
                        {{ errors.max_retries }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="max_concurrency">Max Concurrent Calls</Label>
                        <Input
                        id="max_concurrency"
                        v-model.number="form.max_concurrency"
                        type="number"
                        min="1"
                        max="20"
                        />
                        <div v-if="errors.max_concurrency" class="text-sm text-destructive">
                        {{ errors.max_concurrency }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="call_order">Call Order</Label>
                        <Select v-model="form.call_order">
                        <SelectTrigger>
                            <SelectValue placeholder="Select call order" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="sequential">Sequential</SelectItem>
                            <SelectItem value="random">Random</SelectItem>
                            <SelectItem value="priority">Priority-based</SelectItem>
                        </SelectContent>
                        </Select>
                        <div v-if="errors.call_order" class="text-sm text-destructive">
                        {{ errors.call_order }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="caller_id">Caller ID</Label>
                        <Input
                        id="caller_id"
                        v-model="form.caller_id"
                        placeholder="e.g., +1234567890"
                        />
                        <div v-if="errors.caller_id" class="text-sm text-destructive">
                        {{ errors.caller_id }}
                        </div>
                    </div>
                    </div>

                    <!-- Schedule Settings -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <Label for="starts_at">Start Date & Time</Label>
                        <Input
                        id="starts_at"
                        v-model="form.starts_at"
                        type="datetime-local"
                        />
                        <div v-if="errors.starts_at" class="text-sm text-destructive">
                        {{ errors.starts_at }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="ends_at">End Date & Time (Optional)</Label>
                        <Input
                        id="ends_at"
                        v-model="form.ends_at"
                        type="datetime-local"
                        />
                        <div v-if="errors.ends_at" class="text-sm text-destructive">
                        {{ errors.ends_at }}
                        </div>
                    </div>
                    </div>

                    <!-- Options -->
                    <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <input
                        id="record_calls"
                        v-model="form.record_calls"
                        type="checkbox"
                        class="rounded"
                        />
                        <Label for="record_calls">Record calls for quality assurance</Label>
                    </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between">
                    <div class="flex space-x-2">
                        <Button @click="router.visit(route('campaigns.show', campaign.id))" type="button" variant="outline">
                        Cancel
                        </Button>
                    </div>
                    <div class="flex space-x-2">
                        <Button type="submit" :disabled="processing">
                        <svg
                            v-if="processing"
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                            ></circle>
                            <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        {{ processing ? 'Updating...' : 'Update Campaign' }}
                        </Button>
                    </div>
                    </div>
                </form>
                </CardContent>
            </Card>
        </div>
        
        <!-- Import Dialog -->
        <ImportDialog
            v-model:open="showImportDialog"
            :data-type="form.data_source_type as 'contacts' | 'orders' | 'leads'"
            :company-id="props.campaign.company_id"
            :campaign-id="props.campaign.id"
            @imported="handleImportComplete"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import ImportDialog from '@/components/ImportDialog.vue'
import { Upload, CheckCircle } from 'lucide-vue-next'

interface Agent {
  id: number
  name: string
  description?: string
  is_active: boolean
}

interface Campaign {
  id: number
  company_id: number
  name: string
  description?: string
  agent_id?: number
  data_source_type: string
  max_retries: number
  max_concurrency: number
  call_order: string
  record_calls: boolean
  caller_id?: string
  starts_at?: string
  ends_at?: string
  filter_criteria?: Record<string, any>
  status: string
}

const props = defineProps<{
  campaign: Campaign
  agents: Agent[]
}>()

// Import dialog state
const showImportDialog = ref(false)
const importedDataCount = ref(0)

// Breadcrumbs
const breadcrumbs = [
  { title: 'Campaigns', href: route('campaigns.index') },
  { title: props.campaign.name, href: route('campaigns.show', props.campaign.id) },
  { title: 'Edit', href: '#' },
]

const dataSources = [
  {
    value: 'contacts',
    label: 'Contacts',
    icon: '👥',
    description: 'Call from your contact list'
  },
  {
    value: 'leads',
    label: 'Leads',
    icon: '🎯',
    description: 'Target qualified leads'
  },
  {
    value: 'orders',
    label: 'Orders',
    icon: '📦',
    description: 'Follow up on orders'
  }
]

// Format datetime for input (convert from UTC to local)
const formatDatetimeLocal = (dateString?: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toISOString().slice(0, 16)
}

const form = useForm({
  name: props.campaign.name,
  description: props.campaign.description || '',
  agent_id: props.campaign.agent_id?.toString() || '',
  data_source_type: props.campaign.data_source_type,
  max_retries: props.campaign.max_retries,
  max_concurrency: props.campaign.max_concurrency,
  call_order: props.campaign.call_order,
  record_calls: props.campaign.record_calls,
  caller_id: props.campaign.caller_id || '',
  starts_at: formatDatetimeLocal(props.campaign.starts_at),
  ends_at: formatDatetimeLocal(props.campaign.ends_at),
  filter_criteria: props.campaign.filter_criteria || {}
})

const { data, processing, errors } = form

function getDataSourceLabel() {
  const source = dataSources.find(s => s.value === form.data_source_type)
  return source ? source.label.toLowerCase() : 'data'
}

function handleImportComplete(result: any) {
  importedDataCount.value = result.imported
  showImportDialog.value = false
}

function submitForm() {
  form.put(route('campaigns.update', props.campaign.id), {
    onSuccess: () => {
      // The controller will redirect to campaigns.show
    },
    onError: () => {
      // Errors are handled by the reactive errors object
    }
  })
}
</script>
