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
import { ref, watchEffect } from 'vue'
import { UserPlus } from 'lucide-vue-next'
import { toast } from 'vue-sonner';

const firstName = ref('')
const middleName = ref('')
const lastName = ref('')

const form = useForm({
    name: '',
    email_prefix: '',
    password: '',
    role: 'student',
    grade_level: null,
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

const submit = () => {
    form.transform((data) => ({
        ...data,
        email: `${data.email_prefix}@${data.role}.libratech.com`,
    })).post('/users', {
        onSuccess: () => toast.success('User created successfully'),
        onError: () => toast.error('Check your input')
    })
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manage Users', href: '/users' },
    { title: 'Add User', href: '/users/create' },
]
</script>

<template>
    <Head title="Add User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6 md:px-8">
             <Card class="shadow-lg">
                <CardHeader>
                    <CardTitle class="text-2xl font-bold flex items-center gap-2">
                        <span class="bg-primary/10 text-primary p-2 rounded-lg"><UserPlus class="w-6 h-6" /></span>
                        Add New User
                    </CardTitle>
                    <CardDescription>
                        Create a new account for a student, teacher, or librarian.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Name Fields (Atomic) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Label for="first_name">First Name</Label>
                            <Input id="first_name" v-model="firstName" class="mt-2" placeholder="e.g. Maria" />
                        </div>
                        <div>
                            <Label for="middle_name">Middle Name (Optional)</Label>
                            <Input id="middle_name" v-model="middleName" class="mt-2" placeholder="e.g. Leonora" />
                        </div>
                        <div>
                            <Label for="last_name">Last Name</Label>
                            <Input id="last_name" v-model="lastName" class="mt-2" placeholder="e.g. Cruz" />
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
                                placeholder="e.g. juan.delacruz" 
                            />
                            <span class="bg-muted px-3 py-2 border rounded-md text-sm text-muted-foreground whitespace-nowrap h-10 flex items-center">
                                @{{ form.role }}.libratech.com
                            </span>
                        </div>
                        <div v-if="form.errors.email_prefix" class="text-sm text-red-600 mt-1">{{ form.errors.email_prefix }}</div>
                    </div>

                     <!-- Password -->
                    <div>
                        <Label for="password">Password</Label>
                        <Input id="password" type="password" v-model="form.password" class="mt-2" placeholder="Min. 8 characters" />
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
                <CardFooter class="flex justify-end gap-3 bg-muted/20 py-4 px-6 rounded-b-lg">
                    <Button variant="outline" @click="router.visit('/users')">Cancel</Button>
                    <Button :disabled="form.processing" @click="submit">
                        <Spinner v-if="form.processing" class="mr-2" />
                        Create User
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
