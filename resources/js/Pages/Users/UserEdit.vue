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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref, watchEffect } from 'vue' // update imports
import { Pencil, Trash } from 'lucide-vue-next';
import { toast } from 'vue-sonner'
import { useConfirmDialog } from '@/composables/useConfirmDialog'

const { confirm } = useConfirmDialog()

const props = defineProps<{
    user: {
        id: number
        name: string
        email: string
        role: string
        grade_level: number | null
    }
}>()

const parts = props.user.name.split(' ')
const firstName = ref(parts[0] || '')
const lastName = ref(parts.length > 1 ? parts[parts.length - 1] : '')
const middleName = ref(parts.length > 2 ? parts.slice(1, -1).join(' ') : '')

const form = useForm({
    name: props.user.name,
    email_prefix: props.user.email.split('@')[0],
    password: '',
    role: props.user.role,
    grade_level: props.user.grade_level,
})

// Auto-generate Email Prefix
watchEffect(() => {
    // Consolidated Name
    const full = [firstName.value, middleName.value, lastName.value].filter(Boolean).join(' ')
    form.name = full

    // Email Logic
    const f = firstName.value.toLowerCase().replace(/[^a-z0-9]/g, '')
    const l = lastName.value.toLowerCase().replace(/[^a-z0-9]/g, '')
    const mRaw = middleName.value.toLowerCase().trim()
    
    if (!f || !l) return // Wait for first and last
    
    // Only update if auto-generation is desired?
    // User said "email prefix must be generated from the name they create"
    // So we will overwrite it. If they manually edited it, it might get overwritten 
    // if they change the name again. This is standard behavior for "generated from name".
    
    let base = ''
    if (!mRaw) {
        // Case 3: first.last
        base = `${f}.${l}`
    } else {
        const mParts = mRaw.split(/\s+/).filter(Boolean)
        if (mParts.length === 1) {
            // Case 2: first.m.last
            const initial = mParts[0][0]
            base = `${f}.${initial}.${l}`
        } else {
            // Case 1: first + initials + .last
            const initials = mParts.map(p => p[0]).join('')
            base = `${f}${initials}.${l}`
        }
    }
    
    form.email_prefix = base
})

const submit = async () => {
    const ok = await confirm({
        title: 'Save changes?',
        description: 'This will update the user information.',
        confirmText: 'Save',
    })

    if (!ok) return

    form.put(`/users/${props.user.id}`, {
        onSuccess: () => toast.success('User updated successfully'),
        onError: () => toast.error('Check your input')
    })
}

const handleDelete = async () => {
    const ok = await confirm({
        title: 'Delete User?',
        description: 'Are you sure you want to delete this user? This action cannot be undone.',
        confirmText: 'Delete User',
        variant: 'destructive',
    })

    if (ok) {
        router.delete(`/users/${props.user.id}`)
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manage Users', href: '/users' },
    { title: 'Edit User', href: `/users/${props.user.id}/edit` },
]
</script>

<template>
    <Head title="Edit User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6 md:px-8">
             <Card class="shadow-lg">
                <CardHeader>
                    <CardTitle class="text-2xl font-bold flex items-center gap-2">
                        <span class="bg-orange-100 text-orange-600 p-2 rounded-lg"><Pencil class="w-6 h-6" /></span>
                        Edit User
                    </CardTitle>
                    <CardDescription>
                        Update account details. Leave password blank to keep it unchanged.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Name Fields (Atomic) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label for="first_name">First Name</Label>
                            <Input id="first_name" v-model="firstName" class="mt-2" />
                        </div>
                        <div>
                            <Label for="middle_name">Middle Name (Optional)</Label>
                            <Input id="middle_name" v-model="middleName" class="mt-2" />
                        </div>
                        <div>
                            <Label for="last_name">Last Name</Label>
                            <Input id="last_name" v-model="lastName" class="mt-2" />
                        </div>
                    </div>
                    <div v-if="form.errors.name" class="text-sm text-red-600 mt-1">{{ form.errors.name }}</div>

                    <!-- Email Prefix (Username) -->
                    <div>
                        <Label for="email_prefix">Username (Email Prefix)</Label>
                         <div class="flex items-center gap-2 mt-2">
                             <Input 
                                id="email_prefix" 
                                v-model="form.email_prefix" 
                                class="flex-1" 
                            />
                            <span class="bg-muted px-3 py-2 border rounded-md text-sm text-muted-foreground whitespace-nowrap h-10 flex items-center">
                                @{{ form.role }}.libratech.com
                            </span>
                        </div>
                        <div v-if="form.errors.email_prefix" class="text-sm text-red-600 mt-1">{{ form.errors.email_prefix }}</div>
                    </div>

                     <!-- Password -->
                    <div>
                        <Label for="password">Password (Optional)</Label>
                        <Input id="password" type="password" v-model="form.password" class="mt-2" placeholder="Leave blank to keep current password" />
                        <div v-if="form.errors.password" class="text-sm text-red-600 mt-1">{{ form.errors.password }}</div>
                    </div>

                     <!-- Role -->
                    <div>
                        <Label for="role">Role</Label>
                        <select
                            id="role"
                            v-model="form.role"
                            class="w-full mt-2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="librarian">Librarian</option>
                        </select>
                        <div v-if="form.errors.role" class="text-sm text-red-600 mt-1">{{ form.errors.role }}</div>
                    </div>

                    <!-- Grade Level (Student only) -->
                    <div v-if="form.role === 'student'">
                         <Label for="grade_level">Grade Level <span class="text-red-500">*</span></Label>
                        <select
                            id="grade_level"
                            v-model="form.grade_level"
                            class="w-full mt-2 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option :value="null">Select Grade</option>
                            <option v-for="n in 6" :key="n" :value="n + 6">
                                Grade {{ n + 6 }}
                            </option>
                        </select>
                        <div v-if="form.errors.grade_level" class="text-sm text-red-600 mt-1">{{ form.errors.grade_level }}</div>
                    </div>

                </CardContent>
                <CardFooter class="flex justify-between items-center bg-muted/20 py-4 px-6 rounded-b-lg">
                    <Button variant="outline" class="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700 cursor-pointer" @click="handleDelete">
                        <Trash class="w-4 h-4 mr-2" />
                        Delete User
                    </Button>
                    <div class="flex gap-3">
                         <Button variant="outline" @click="router.visit('/users')">Cancel</Button>
                        <Button :disabled="form.processing" @click="submit" class="bg-orange-500 hover:bg-orange-600 text-white cursor-pointer">
                            <Spinner v-if="form.processing" class="mr-2" />
                            Save Changes
                        </Button>
                    </div>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
