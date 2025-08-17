<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Campaign" />
  
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                <h1 class="text-3xl font-bold tracking-tight">Create Campaign</h1>
                <p class="text-muted-foreground">
                    Set up a new call campaign with your specified configuration.
                </p>
                </div>
                <Button @click="router.visit(route('campaigns.index'))" variant="outline">
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
                Back to Campaigns
                </Button>
            </div>

            <!-- Campaign Form -->
            <Card>
                <CardHeader>
                <CardTitle>Campaign Details</CardTitle>
                <CardDescription>
                    Configure your campaign settings and parameters.
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
                        class="relative"
                        >
                        <input
                            :id="source.value"
                            v-model="form.data_source_type"
                            type="radio"
                            :value="source.value"
                            class="peer sr-only"
                        />
                        <label
                            :for="source.value"
                            class="flex flex-col items-center justify-center p-4 border rounded-lg cursor-pointer hover:bg-muted peer-checked:border-primary peer-checked:bg-primary/5"
                        >
                            <div class="text-2xl mb-2">{{ source.icon }}</div>
                            <div class="font-medium">{{ source.label }}</div>
                            <div class="text-sm text-muted-foreground text-center">
                            {{ source.description }}
                            </div>
                        </label>
                        </div>
                    </div>
                    <div v-if="errors.data_source_type" class="text-sm text-destructive">
                        {{ errors.data_source_type }}
                    </div>
                    </div>

                    <!-- Call Settings -->
                    <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-medium">Call Settings</h3>
                        <p class="text-sm text-muted-foreground">
                        Configure how calls should be handled
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="space-y-2">
                        <Label for="max_retries">Max Retries</Label>
                        <Input
                            id="max_retries"
                            v-model.number="form.max_retries"
                            type="number"
                            min="0"
                            max="10"
                            placeholder="3"
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
                            placeholder="5"
                        />
                        <div v-if="errors.max_concurrency" class="text-sm text-destructive">
                            {{ errors.max_concurrency }}
                        </div>
                        </div>

                        <div class="space-y-2">
                        <Label for="call_order">Call Order</Label>
                        <Select v-model="form.call_order">
                            <SelectTrigger>
                            <SelectValue placeholder="Sequential" />
                            </SelectTrigger>
                            <SelectContent>
                            <SelectItem value="sequential">Sequential</SelectItem>
                            <SelectItem value="random">Random</SelectItem>
                            <SelectItem value="priority">Priority</SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="errors.call_order" class="text-sm text-destructive">
                            {{ errors.call_order }}
                        </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="caller_id">Caller ID</Label>
                        <Input
                        id="caller_id"
                        v-model="form.caller_id"
                        placeholder="Phone number to display as caller ID"
                        />
                        <div v-if="errors.caller_id" class="text-sm text-destructive">
                        {{ errors.caller_id }}
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Checkbox
                        id="record_calls"
                        v-model="form.record_calls"
                        />
                        <Label for="record_calls">Record calls for quality assurance</Label>
                    </div>
                    </div>

                    <!-- Schedule Settings -->
                    <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-medium">Schedule</h3>
                        <p class="text-sm text-muted-foreground">
                        Set when the campaign should run
                        </p>
                    </div>

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
                        <Label for="ends_at">End Date & Time</Label>
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
                    </div>

                    <!-- Advanced Filters -->
                    <div class="space-y-4">
                    <div>
                        <h3 class="text-lg font-medium">Advanced Filters</h3>
                        <p class="text-sm text-muted-foreground">
                        Filter which contacts to include in this campaign
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                        <Label for="segment_filter">Contact Segment</Label>
                        <Input
                            id="segment_filter"
                            v-model="segmentFilter"
                            placeholder="e.g., premium, new_customers"
                        />
                        </div>

                        <div class="space-y-2">
                        <Label for="status_filter">Contact Status</Label>
                        <Select v-model="statusFilter">
                            <SelectTrigger>
                            <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="new">New</SelectItem>
                            <SelectItem value="contacted">Contacted</SelectItem>
                            <SelectItem value="interested">Interested</SelectItem>
                            </SelectContent>
                        </Select>
                        </div>
                    </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                    <Button
                        type="button"
                        variant="outline"
                        @click="router.visit(route('campaigns.index'))"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="processing">
                        <svg
                        v-if="processing"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        class="mr-2 h-4 w-4 animate-spin"
                        >
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                        </svg>
                        {{ processing ? 'Creating...' : 'Create Campaign' }}
                    </Button>
                    </div>
                </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

interface Agent {
  id: number
  name: string
}

interface Props {
  agents: Agent[]
}

const props = defineProps<Props>()

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

const segmentFilter = ref('')
const statusFilter = ref('')

const form = useForm({
  name: '',
  description: '',
  agent_id: '',
  data_source_type: 'contacts',
  max_retries: 3,
  max_concurrency: 5,
  call_order: 'sequential',
  record_calls: false,
  caller_id: '',
  starts_at: '',
  ends_at: '',
  filter_criteria: {}
})

const { data, processing, errors } = form

// Build filter criteria from individual filters
const filterCriteria = computed(() => {
  const criteria: Record<string, any> = {}
  
  if (segmentFilter.value) {
    criteria.segment = segmentFilter.value
  }
  
  if (statusFilter.value) {
    criteria.status = statusFilter.value
  }
  
  return criteria
})

function submitForm() {
  // Update filter criteria before submitting
  form.filter_criteria = filterCriteria.value
  
  form.post(route('campaigns.store'), {
    onSuccess: () => {
      // The controller will redirect to campaigns.show
    },
    onError: () => {
      // Errors are handled by the reactive errors object
    }
  })
}
</script>
