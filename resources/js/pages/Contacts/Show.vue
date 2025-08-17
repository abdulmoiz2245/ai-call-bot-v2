<template>

    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Contact: ${contact.first_name} ${contact.last_name}`" />

        <div class="p-6 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
            <Button @click="router.visit(route('contacts.index'))" variant="ghost" size="sm" class="mb-4">
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
                Back to Contacts
            </Button>
            <h1 class="text-3xl font-bold tracking-tight">
                {{ contact.first_name }} {{ contact.last_name }}
            </h1>
            <p class="text-muted-foreground">
                Contact details and call history
            </p>
            </div>
            <div class="flex items-center space-x-2">
            <Button @click="router.visit(route('contacts.edit', contact.id))" variant="outline">
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
                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                <path d="m15 5 4 4" />
                </svg>
                Edit Contact
            </Button>
            <Button @click="deleteContact" variant="destructive">
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
                <path d="M3 6h18" />
                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                </svg>
                Delete
            </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Contact Information -->
            <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <Card>
                <CardHeader>
                <CardTitle>Contact Information</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">Name</Label>
                    <div class="text-lg font-medium">{{ contact.first_name }} {{ contact.last_name }}</div>
                    </div>
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">Phone</Label>
                    <div class="font-mono">{{ contact.phone }}</div>
                    </div>
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">Email</Label>
                    <div>{{ contact.email || 'No email provided' }}</div>
                    </div>
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">Status</Label>
                    <div>
                        <Badge :variant="getStatusVariant(contact.status)">
                        {{ contact.status }}
                        </Badge>
                    </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">City</Label>
                    <div>{{ contact.city || 'Not provided' }}</div>
                    </div>
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">State</Label>
                    <div>{{ contact.state || 'Not provided' }}</div>
                    </div>
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">ZIP</Label>
                    <div>{{ contact.zip || 'Not provided' }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">Segment</Label>
                    <div>{{ contact.segment || 'No segment assigned' }}</div>
                    </div>
                    <div>
                    <Label class="text-sm font-medium text-muted-foreground">Timezone</Label>
                    <div>{{ contact.timezone || 'Not specified' }}</div>
                    </div>
                </div>

                <div v-if="contact.notes" class="space-y-2">
                    <Label class="text-sm font-medium text-muted-foreground">Notes</Label>
                    <div class="p-3 bg-muted rounded-md text-sm">{{ contact.notes }}</div>
                </div>
                </CardContent>
            </Card>

            <!-- Call History -->
            <Card>
                <CardHeader>
                <CardTitle>Call History</CardTitle>
                <CardDescription>Recent calls made to this contact</CardDescription>
                </CardHeader>
                <CardContent>
                <div v-if="calls && calls.length > 0" class="space-y-4">
                    <div
                    v-for="call in calls"
                    :key="call.id"
                    class="flex items-center justify-between p-4 border rounded-lg"
                    >
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                        <Badge :variant="getCallStatusVariant(call.status)">
                            {{ call.status }}
                        </Badge>
                        </div>
                        <div>
                        <div class="font-medium">{{ formatDateTime(call.created_at) }}</div>
                        <div class="text-sm text-muted-foreground">
                            Duration: {{ call.duration ? `${call.duration}s` : 'N/A' }}
                            <span v-if="call.cost" class="ml-2">• Cost: ${{ call.cost }}</span>
                        </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium">{{ call.campaign?.name || 'Manual Call' }}</div>
                        <div class="text-sm text-muted-foreground">{{ call.agent?.name || 'Unknown Agent' }}</div>
                    </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 text-muted-foreground">
                    No calls made to this contact yet.
                </div>
                </CardContent>
            </Card>
            </div>

            <!-- Sidebar -->
            <div class="p-6">
            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                <CardTitle>Quick Actions</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                <Button @click="callNow" class="w-full" variant="default">
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
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                    </svg>
                    Call Now
                </Button>

                <Button
                    @click="toggleDnc"
                    class="w-full"
                    :variant="contact.is_dnc ? 'outline' : 'secondary'"
                >
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
                    <path d="m4.9 4.9 14.2 14.2" />
                    </svg>
                    {{ contact.is_dnc ? 'Remove from DNC' : 'Add to DNC' }}
                </Button>

                <Button @click="addToCampaign" class="w-full" variant="outline">
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
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <line x1="19" y1="8" x2="19" y2="14" />
                    <line x1="22" y1="11" x2="16" y2="11" />
                    </svg>
                    Add to Campaign
                </Button>
                </CardContent>
            </Card>

            <!-- Contact Stats -->
            <Card>
                <CardHeader>
                <CardTitle>Contact Stats</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Total Calls</span>
                    <span class="font-medium">{{ calls?.length || 0 }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Last Contact</span>
                    <span class="font-medium">
                    {{ contact.last_contacted ? formatDate(contact.last_contacted) : 'Never' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">DNC Status</span>
                    <Badge v-if="contact.is_dnc" variant="destructive">DNC</Badge>
                    <span v-else class="text-green-600 font-medium">Callable</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Created</span>
                    <span class="font-medium">{{ formatDate(contact.created_at) }}</span>
                </div>
                </CardContent>
            </Card>
            </div>
        </div>
        </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'

interface Contact {
  id: number
  first_name: string
  last_name: string
  email?: string
  phone: string
  city?: string
  state?: string
  zip?: string
  segment?: string
  status: string
  timezone?: string
  notes?: string
  is_dnc: boolean
  last_contacted?: string
  created_at: string
}

interface Call {
  id: number
  status: string
  duration?: number
  cost?: string
  created_at: string
  campaign?: {
    name: string
  }
  agent?: {
    name: string
  }
}

interface Props {
  contact: Contact
  calls?: Call[]
}

const props = defineProps<Props>()

function deleteContact() {
  if (confirm(`Are you sure you want to delete ${props.contact.first_name} ${props.contact.last_name}?`)) {
    router.delete(route('contacts.destroy', props.contact.id), {
      onSuccess: () => {
        router.visit(route('contacts.index'))
      }
    })
  }
}

function toggleDnc() {
  if (props.contact.is_dnc) {
    router.delete(route('contacts.dnc.remove', props.contact.id))
  } else {
    router.post(route('contacts.dnc.add', props.contact.id))
  }
}

function callNow() {
  // Implementation for initiating a call
  console.log('Calling contact:', props.contact.phone)
}

function addToCampaign() {
  // Implementation for adding to campaign
  console.log('Adding to campaign:', props.contact.id)
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

function getCallStatusVariant(status: string): "default" | "outline" | "secondary" | "destructive" {
  const variants: Record<string, "default" | "outline" | "secondary" | "destructive"> = {
    pending: 'secondary',
    calling: 'outline',
    answered: 'default',
    voicemail: 'secondary',
    busy: 'outline',
    no_answer: 'outline',
    failed: 'destructive'
  }
  return variants[status] || 'secondary'
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString()
}

function formatDateTime(dateString: string): string {
  return new Date(dateString).toLocaleString()
}
</script>
