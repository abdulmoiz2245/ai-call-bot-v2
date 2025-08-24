<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-4xl">
      <DialogHeader>
        <DialogTitle>Import {{ dataTypeLabel }}</DialogTitle>
        <DialogDescription>
          Upload a CSV or Excel file to import {{ dataTypeLabel.toLowerCase() }} data
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6">
        <!-- Step 1: File Upload -->
        <div v-if="step === 1" class="space-y-4">
          <div class="flex items-center space-x-2">
            <Badge variant="secondary">Step 1</Badge>
            <span class="text-sm font-medium">Upload File</span>
          </div>
          
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
            <input
              ref="fileInput"
              type="file"
              accept=".csv,.txt,.xlsx,.xls"
              @change="handleFileSelect"
              class="hidden"
            />
            
            <div v-if="!selectedFile" class="text-center">
              <Upload class="mx-auto h-12 w-12 text-gray-400" />
              <div class="mt-4">
                <Button @click="openFileInput" variant="outline">
                  Select File
                </Button>
                <p class="mt-2 text-sm text-gray-500">
                  CSV, TXT, XLSX, or XLS files up to 10MB
                </p>
              </div>
            </div>
            
            <div v-else class="text-center">
              <File class="mx-auto h-12 w-12 text-green-500" />
              <div class="mt-4">
                <p class="font-medium">{{ selectedFile.name }}</p>
                <p class="text-sm text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                <Button @click="parseFile" class="mt-2" :disabled="parsing">
                  <Loader2 v-if="parsing" class="mr-2 h-4 w-4 animate-spin" />
                  {{ parsing ? 'Parsing...' : 'Continue' }}
                </Button>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Column Mapping -->
        <div v-if="step === 2" class="space-y-4">
          <div class="flex items-center space-x-2">
            <Badge variant="secondary">Step 2</Badge>
            <span class="text-sm font-medium">Map Columns</span>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <h4 class="font-medium mb-2">File Columns</h4>
              <div class="border rounded-lg p-3 space-y-2 max-h-64 overflow-y-auto">
                <div v-for="(header, index) in fileHeaders" :key="index" class="text-sm p-2 bg-gray-50 rounded">
                  {{ header }}
                </div>
              </div>
            </div>
            
            <div>
              <h4 class="font-medium mb-2">Database Fields</h4>
              <div class="space-y-3">
                <div v-for="(label, field) in availableFields" :key="field" class="space-y-1">
                  <Label :for="field">{{ label }}</Label>
                  <Select v-model="mapping[field]">
                    <SelectTrigger>
                      <SelectValue :placeholder="`Select column for ${label}`" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">-- Skip this field --</SelectItem>
                      <SelectItem v-for="(header, index) in fileHeaders" :key="index" :value="index">
                        {{ header }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>
          </div>
          
          <div class="flex space-x-2">
            <Button @click="step = 1" variant="outline">Back</Button>
            <Button @click="performDryRun" :disabled="!hasMappedFields || validating">
              <Loader2 v-if="validating" class="mr-2 h-4 w-4 animate-spin" />
              {{ validating ? 'Validating...' : 'Validate Data' }}
            </Button>
          </div>
        </div>

        <!-- Step 3: Validation Results -->
        <div v-if="step === 3" class="space-y-4">
          <div class="flex items-center space-x-2">
            <Badge variant="secondary">Step 3</Badge>
            <span class="text-sm font-medium">Validation Results</span>
          </div>
          
          <div class="grid grid-cols-3 gap-4">
            <Card>
              <CardContent class="pt-6">
                <div class="text-2xl font-bold">{{ validationResult.total_rows }}</div>
                <p class="text-sm text-gray-500">Total Rows</p>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="pt-6">
                <div class="text-2xl font-bold text-green-600">{{ validationResult.valid_rows }}</div>
                <p class="text-sm text-gray-500">Valid Rows</p>
              </CardContent>
            </Card>
            <Card>
              <CardContent class="pt-6">
                <div class="text-2xl font-bold text-red-600">{{ validationResult.invalid_rows }}</div>
                <p class="text-sm text-gray-500">Invalid Rows</p>
              </CardContent>
            </Card>
          </div>
          
          <!-- Sample Data -->
          <div v-if="validationResult.sample_data?.length">
            <h4 class="font-medium mb-2">Sample Data Preview</h4>
            <div class="border rounded-lg overflow-hidden">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead v-for="(label, field) in availableFields" :key="field">
                      {{ label }}
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="(row, index) in validationResult.sample_data.slice(0, 3)" :key="index">
                    <TableCell v-for="(label, field) in availableFields" :key="field">
                      {{ row[field] || '-' }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </div>
          
          <!-- Errors -->
          <div v-if="validationResult.errors?.length">
            <h4 class="font-medium mb-2">Validation Errors (showing first 10)</h4>
            <div class="border rounded-lg p-3 max-h-48 overflow-y-auto">
              <div v-for="error in validationResult.errors" :key="error.row" class="mb-2 p-2 bg-red-50 rounded text-sm">
                <div class="font-medium">Row {{ error.row }}:</div>
                <ul class="list-disc list-inside">
                  <li v-for="msg in error.errors" :key="msg">{{ msg }}</li>
                </ul>
              </div>
            </div>
          </div>
          
          <div class="flex space-x-2">
            <Button @click="step = 2" variant="outline">Back</Button>
            <Button 
              @click="performImport" 
              :disabled="validationResult.valid_rows === 0 || importing"
              class="bg-green-600 hover:bg-green-700"
            >
              <Loader2 v-if="importing" class="mr-2 h-4 w-4 animate-spin" />
              {{ importing ? 'Importing...' : `Import ${validationResult.valid_rows} Records` }}
            </Button>
          </div>
        </div>

        <!-- Step 4: Import Complete -->
        <div v-if="step === 4" class="space-y-4">
          <div class="flex items-center space-x-2">
            <Badge variant="secondary">Step 4</Badge>
            <span class="text-sm font-medium">Import Complete</span>
          </div>
          
          <div class="text-center py-8">
            <CheckCircle class="mx-auto h-16 w-16 text-green-500" />
            <h3 class="mt-4 text-lg font-medium">Import Successful!</h3>
            <p class="mt-2 text-gray-500">
              {{ importResult.imported }} records imported successfully
              <span v-if="importResult.skipped > 0">
                ({{ importResult.skipped }} skipped)
              </span>
            </p>
          </div>
          
          <div class="flex justify-center">
            <Button @click="closeDialog">Close</Button>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Upload, File, Loader2, CheckCircle } from 'lucide-vue-next'

interface Props {
  open: boolean
  dataType: 'contacts' | 'orders' | 'leads'
  companyId: number
  campaignId?: number
}

interface Emits {
  (e: 'update:open', value: boolean): void
  (e: 'imported', result: any): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

const dataTypeLabel = computed(() => {
  switch (props.dataType) {
    case 'contacts': return 'Contacts'
    case 'orders': return 'Orders'
    case 'leads': return 'Leads'
    default: return 'Data'
  }
})

// State
const step = ref(1)
const selectedFile = ref<File | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const fileHeaders = ref<string[]>([])
const availableFields = ref<Record<string, string>>({})
const mapping = ref<Record<string, string | number>>({})
const validationResult = ref<any>(null)
const importResult = ref<any>(null)

// Loading states
const parsing = ref(false)
const validating = ref(false)
const importing = ref(false)

const hasMappedFields = computed(() => {
  return Object.values(mapping.value).some(value => value !== '')
})

// Watch for dialog open/close to reset state
watch(isOpen, (newValue) => {
  if (newValue) {
    resetState()
  }
})

function resetState() {
  step.value = 1
  selectedFile.value = null
  fileHeaders.value = []
  availableFields.value = {}
  mapping.value = {}
  validationResult.value = null
  importResult.value = null
  parsing.value = false
  validating.value = false
  importing.value = false
}

function handleFileSelect(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) {
    selectedFile.value = file
  }
}

function openFileInput() {
  fileInput.value?.click()
}

async function parseFile() {
  if (!selectedFile.value) return
  
  parsing.value = true
  
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('data_type', props.dataType)
    
    const response = await fetch('/import/parse-headers', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    const result = await response.json()
    
    if (result.success) {
      fileHeaders.value = result.headers
      availableFields.value = result.available_fields
      step.value = 2
    } else {
      alert(result.message || 'Failed to parse file')
    }
  } catch (error) {
    alert('Failed to parse file')
  } finally {
    parsing.value = false
  }
}

async function performDryRun() {
  if (!selectedFile.value) return
  
  validating.value = true
  
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('data_type', props.dataType)
    formData.append('company_id', props.companyId.toString())
    formData.append('mapping', JSON.stringify(mapping.value))
    
    const response = await fetch('/import/dry-run', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    const result = await response.json()
    
    if (result.success) {
      validationResult.value = result.result
      step.value = 3
    } else {
      alert(result.message || 'Validation failed')
    }
  } catch (error) {
    alert('Validation failed')
  } finally {
    validating.value = false
  }
}

async function performImport() {
  if (!selectedFile.value) return
  
  importing.value = true
  
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('data_type', props.dataType)
    formData.append('company_id', props.companyId.toString())
    formData.append('mapping', JSON.stringify(mapping.value))
    
    if (props.campaignId) {
      formData.append('campaign_id', props.campaignId.toString())
    }
    
    const response = await fetch('/import/import', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      }
    })
    
    const result = await response.json()
    
    if (result.success) {
      importResult.value = result.result
      step.value = 4
      emit('imported', result.result)
    } else {
      alert(result.message || 'Import failed')
    }
  } catch (error) {
    alert('Import failed')
  } finally {
    importing.value = false
  }
}

function closeDialog() {
  isOpen.value = false
}

function formatFileSize(bytes: number): string {
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  if (bytes === 0) return '0 Bytes'
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}
</script>
