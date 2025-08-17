<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Add Contact" />
        
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center space-x-4">
            <Link :href="route('contacts.index')" as="button">
                <Button variant="ghost" size="sm">
                <ArrowLeft class="w-4 h-4 mr-2" />
                Back to Contacts
                </Button>
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Add New Contact</h1>
                <p class="text-muted-foreground">
                Add a contact to your database for campaigns
                </p>
            </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="p-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg border p-6">
                <h2 class="text-xl font-semibold mb-4">Contact Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label for="first_name">First Name</Label>
                    <Input
                    id="first_name"
                    v-model="form.first_name"
                    :class="{ 'border-red-500': errors.first_name }"
                    placeholder="John"
                    required
                    />
                    <div v-if="errors.first_name" class="text-red-500 text-sm">{{ errors.first_name }}</div>
                </div>

                <div class="space-y-2">
                    <Label for="last_name">Last Name</Label>
                    <Input
                    id="last_name"
                    v-model="form.last_name"
                    :class="{ 'border-red-500': errors.last_name }"
                    placeholder="Doe"
                    required
                    />
                    <div v-if="errors.last_name" class="text-red-500 text-sm">{{ errors.last_name }}</div>
                </div>
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label for="phone">Phone Number</Label>
                    <Input
                    id="phone"
                    v-model="form.phone"
                    :class="{ 'border-red-500': errors.phone }"
                    placeholder="+1 (555) 123-4567"
                    type="tel"
                    required
                    />
                    <div v-if="errors.phone" class="text-red-500 text-sm">{{ errors.phone }}</div>
                    <p class="text-sm text-muted-foreground">Include country code (e.g., +1 for US)</p>
                </div>

                <div class="space-y-2">
                    <Label for="email">Email Address</Label>
                    <Input
                    id="email"
                    v-model="form.email"
                    :class="{ 'border-red-500': errors.email }"
                    placeholder="john.doe@example.com"
                    type="email"
                    />
                    <div v-if="errors.email" class="text-red-500 text-sm">{{ errors.email }}</div>
                </div>
                </div>
            </div>

            <!-- Additional Details -->
            <div class="bg-white rounded-lg border p-6">
                <h2 class="text-xl font-semibold mb-4">Additional Details</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label for="company">Company</Label>
                    <Input
                    id="company"
                    v-model="form.company"
                    placeholder="ABC Corporation"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="job_title">Job Title</Label>
                    <Input
                    id="job_title"
                    v-model="form.job_title"
                    placeholder="Marketing Manager"
                    />
                </div>
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <Label for="timezone">Timezone</Label>
                    <Select v-model="form.timezone">
                    <SelectTrigger>
                        <SelectValue placeholder="Select timezone" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="America/New_York">Eastern Time (ET)</SelectItem>
                        <SelectItem value="America/Chicago">Central Time (CT)</SelectItem>
                        <SelectItem value="America/Denver">Mountain Time (MT)</SelectItem>
                        <SelectItem value="America/Los_Angeles">Pacific Time (PT)</SelectItem>
                        <SelectItem value="Europe/London">London (GMT)</SelectItem>
                        <SelectItem value="Europe/Paris">Paris (CET)</SelectItem>
                        <SelectItem value="Asia/Tokyo">Tokyo (JST)</SelectItem>
                        <SelectItem value="Australia/Sydney">Sydney (AEST)</SelectItem>
                    </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <Label for="language">Preferred Language</Label>
                    <Select v-model="form.language">
                    <SelectTrigger>
                        <SelectValue placeholder="Select language" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="en">English</SelectItem>
                        <SelectItem value="es">Spanish</SelectItem>
                        <SelectItem value="fr">French</SelectItem>
                        <SelectItem value="de">German</SelectItem>
                        <SelectItem value="it">Italian</SelectItem>
                        <SelectItem value="pt">Portuguese</SelectItem>
                    </SelectContent>
                    </Select>
                </div>
                </div>
            </div>

            <!-- Custom Fields -->
            <div class="bg-white rounded-lg border p-6">
                <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">Custom Fields</h2>
                <Button @click="addCustomField" type="button" variant="outline" size="sm">
                    <Plus class="w-4 h-4 mr-2" />
                    Add Field
                </Button>
                </div>
                
                <div v-if="customFields.length === 0" class="text-center py-6 text-muted-foreground">
                <Database class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">No custom fields added</p>
                <p class="text-xs">Add custom fields to store additional contact information</p>
                </div>

                <div v-else class="space-y-4">
                <div
                    v-for="(field, index) in customFields"
                    :key="index"
                    class="flex items-center space-x-3 p-3 border rounded-lg"
                >
                    <Input
                    v-model="field.key"
                    placeholder="Field name"
                    class="flex-1"
                    />
                    <Input
                    v-model="field.value"
                    placeholder="Field value"
                    class="flex-1"
                    />
                    <Button
                    @click="removeCustomField(index)"
                    type="button"
                    variant="ghost"
                    size="sm"
                    >
                    <X class="w-4 h-4" />
                    </Button>
                </div>
                </div>
            </div>

            <!-- Contact Preferences -->
            <div class="bg-white rounded-lg border p-6">
                <h2 class="text-xl font-semibold mb-4">Contact Preferences</h2>
                
                <div class="space-y-4">
                <div class="space-y-2">
                    <Label>Best Time to Call</Label>
                    <div class="grid grid-cols-2 gap-4">
                    <Select v-model="bestTimeStart">
                        <SelectTrigger>
                        <SelectValue placeholder="Start time" />
                        </SelectTrigger>
                        <SelectContent>
                        <SelectItem v-for="hour in hours" :key="hour.value" :value="hour.value">
                            {{ hour.label }}
                        </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="bestTimeEnd">
                        <SelectTrigger>
                        <SelectValue placeholder="End time" />
                        </SelectTrigger>
                        <SelectContent>
                        <SelectItem v-for="hour in hours" :key="hour.value" :value="hour.value">
                            {{ hour.label }}
                        </SelectItem>
                        </SelectContent>
                    </Select>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label>Available Days</Label>
                    <div class="flex flex-wrap gap-2">
                    <div
                        v-for="day in daysOfWeek"
                        :key="day.value"
                        class="flex items-center space-x-2"
                    >
                        <input
                        :id="`day_${day.value}`"
                        v-model="availableDays"
                        :value="day.value"
                        type="checkbox"
                        class="rounded"
                        />
                        <Label :for="`day_${day.value}`" class="text-sm">{{ day.label }}</Label>
                    </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <input
                    id="do_not_call"
                    v-model="form.do_not_call"
                    type="checkbox"
                    class="rounded"
                    />
                    <Label for="do_not_call" class="text-sm">
                    Do Not Call - This contact has opted out of phone calls
                    </Label>
                </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white rounded-lg border p-6">
                <h2 class="text-xl font-semibold mb-4">Notes</h2>
                <Textarea
                v-model="form.notes"
                placeholder="Add any notes about this contact..."
                rows="4"
                />
            </div>

            <!-- Submit Actions -->
            <div class="flex items-center justify-between">
                <Link :href="route('contacts.index')" as="button">
                <Button type="button" variant="outline">
                    Cancel
                </Button>
                </Link>
                
                <div class="flex space-x-3">
                <Button @click="saveAndAddAnother" type="button" variant="outline" :disabled="processing">
                    Save & Add Another
                </Button>
                <Button type="submit" :disabled="processing">
                    <UserPlus class="w-4 h-4 mr-2" />
                    {{ processing ? 'Creating...' : 'Create Contact' }}
                </Button>
                </div>
            </div>
            </form>
        </div>
    </AppLayout>    
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
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
import {
  ArrowLeft,
  Database,
  Plus,
  UserPlus,
  X,
} from 'lucide-vue-next'

// Form state
const form = useForm({
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  company: '',
  job_title: '',
  timezone: '',
  language: 'en',
  custom_fields: {} as Record<string, string>,
  best_time_to_call: '',
  available_days: [] as string[],
  do_not_call: false,
  notes: '',
})

// Additional reactive state
const customFields = ref<Array<{ key: string; value: string }>>([])
const bestTimeStart = ref('')
const bestTimeEnd = ref('')
const availableDays = ref<string[]>([])

// Static data
const hours = [
  { value: '09:00', label: '9:00 AM' },
  { value: '10:00', label: '10:00 AM' },
  { value: '11:00', label: '11:00 AM' },
  { value: '12:00', label: '12:00 PM' },
  { value: '13:00', label: '1:00 PM' },
  { value: '14:00', label: '2:00 PM' },
  { value: '15:00', label: '3:00 PM' },
  { value: '16:00', label: '4:00 PM' },
  { value: '17:00', label: '5:00 PM' },
  { value: '18:00', label: '6:00 PM' },
]

const daysOfWeek = [
  { value: 'monday', label: 'Mon' },
  { value: 'tuesday', label: 'Tue' },
  { value: 'wednesday', label: 'Wed' },
  { value: 'thursday', label: 'Thu' },
  { value: 'friday', label: 'Fri' },
  { value: 'saturday', label: 'Sat' },
  { value: 'sunday', label: 'Sun' },
]

// Computed
const processing = computed(() => form.processing)
const errors = computed(() => form.errors)

// Methods
const addCustomField = () => {
  customFields.value.push({ key: '', value: '' })
}

const removeCustomField = (index: number) => {
  customFields.value.splice(index, 1)
}

const prepareFormData = () => {
  // Prepare custom fields
  const customFieldsObj: Record<string, string> = {}
  customFields.value.forEach(field => {
    if (field.key && field.value) {
      customFieldsObj[field.key] = field.value
    }
  })
  form.custom_fields = customFieldsObj

  // Prepare best time to call
  if (bestTimeStart.value && bestTimeEnd.value) {
    form.best_time_to_call = `${bestTimeStart.value}-${bestTimeEnd.value}`
  }

  // Prepare available days
  form.available_days = availableDays.value
}

const submit = () => {
  prepareFormData()
  
  form.post(route('contacts.store'), {
    onSuccess: () => {
      // Will redirect to contacts index
    },
  })
}

const saveAndAddAnother = () => {
  prepareFormData()
  
  form.post(route('contacts.store'), {
    onSuccess: () => {
      // Reset form for new contact
      form.reset()
      customFields.value = []
      bestTimeStart.value = ''
      bestTimeEnd.value = ''
      availableDays.value = []
    },
  })
}
</script>
