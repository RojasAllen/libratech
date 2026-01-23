<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea'
import { Pencil, Trash } from 'lucide-vue-next';
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import AppLayout from '@/layouts/AppLayout.vue'
import libraryRoutes from '@/routes/library'
import type { BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import { useConfirmDialog } from '@/composables/useConfirmDialog'

const { confirm } = useConfirmDialog()

const props = defineProps<{
  book: {
    id: number
    title: string
    author: string
    subject: string
    description?: string
    grade_level: number
    competency?: string
    file_path?: string
  }
}>()

// form (pre-filled)
const form = ref({ ...props.book })

const errors = ref<Record<string, string>>({})

const submit = async () => {
  const ok = await confirm({
    title: 'Save changes?',
    description: 'This will update the book information.',
    confirmText: 'Save',
  })

  if (!ok) return

  errors.value = {}

  router.put(libraryRoutes.update(form.value.id), form.value, {
    onError: (errs) => {
      errors.value = errs
      toast.error('Validation failed')
    },
    onSuccess: () => {
      toast.success('Book updated successfully')
    },
  })
}

const handleDelete = async () => {
  const ok = await confirm({
    title: 'Delete Book?',
    description: 'This will permanently remove the book.',
    confirmText: 'Delete',
    variant: 'destructive',
  })

  if (!ok) return

  router.delete(libraryRoutes.destroy(props.book.id), {
        onSuccess: () => {
             toast.success('Book deleted successfully');
             // Inertia should automatically redirect if controller returns redirect
        }
  })
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Library', href: libraryRoutes.index().url },
  { title: 'Edit Book', href: libraryRoutes.edit(props.book.id).url },
]
</script>

<template>
  <Head title="Edit Book" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6 md:px-8">
      <Card class="shadow-lg">
        <CardHeader>
          <CardTitle class="text-2xl font-bold flex items-center gap-2">
             <span class="bg-orange-100 text-orange-600 p-2 rounded-lg"><Pencil class="w-6 h-6" /></span>
            Edit Book
          </CardTitle>
          <CardDescription>Update the book details below.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
           <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
             <!-- Title -->
            <div class="md:col-span-2">
                <Label class="text-base font-semibold" for="title">Book Title</Label>
                <Input v-model="form.title" id="title" class="mt-2" placeholder="e.g. Introduction to Physics" />
                <div class="text-sm text-red-600 mt-1">{{ errors.title }}</div>
            </div>

            <!-- Author -->
            <div>
                <Label class="text-base font-semibold" for="author">Author</Label>
                <Input v-model="form.author" id="author" class="mt-2" placeholder="e.g. John Doe" />
                <div class="text-sm text-red-600 mt-1">{{ errors.author }}</div>
            </div>

            <!-- Subject -->
            <div>
                <Label class="text-base font-semibold" for="subject">Subject</Label>
                <Input v-model="form.subject" id="subject" class="mt-2" placeholder="e.g. Science" />
                <div class="text-sm text-red-600 mt-1">{{ errors.subject }}</div>
            </div>

             <!-- Grade Level -->
            <div>
                <Label class="text-base font-semibold" for="grade_level">Grade Level</Label>
                 <select
                    v-model="form.grade_level"
                    id="grade_level"
                   class="w-full mt-2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <option v-for="n in 6" :key="n" :value="n + 6">
                    Grade {{ n + 6 }}
                    </option>
                </select>
                <div class="text-sm text-red-600 mt-1">{{ errors.grade_level }}</div>
            </div>

            <!-- Competency -->
            <div>
                <Label class="text-base font-semibold" for="competency">Competency</Label>
                <Input v-model="form.competency" id="competency" class="mt-2" />
                <div class="text-sm text-red-600 mt-1">{{ errors.competency }}</div>
            </div>

             <!-- Description -->
            <div class="md:col-span-2">
                <Label class="text-base font-semibold" for="description">Description</Label>
                <Textarea v-model="form.description" id="description" class="mt-2 min-h-[100px]" />
                <div class="text-sm text-red-600 mt-1">{{ errors.description }}</div>
            </div>
          </div>
        </CardContent>
        <CardFooter class="flex justify-between items-center bg-muted/20 py-4 px-6 rounded-b-lg">
             <Button variant="outline" class="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700 cursor-pointer" @click="handleDelete">
                <Trash class="w-4 h-4 mr-2" />
                Delete Book
              </Button>
            <div class="flex gap-3">
                 <Button variant="outline" @click="router.visit(libraryRoutes.index().url)">Cancel</Button>
                <Button @click="submit" :disabled="form.processing" class="cursor-pointer bg-orange-500 hover:bg-orange-600 text-white">
                    <Spinner v-if="form.processing" class="mr-2" />
                    Save Changes
                </Button>
            </div>
        </CardFooter>
      </Card>

       <!-- Delete button moved to footer -->
    </div>
  </AppLayout>
</template>
