<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import libraryRoutes from '@/routes/library';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { useConfirmDialog } from '@/composables/useConfirmDialog'

import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { BookPlus } from 'lucide-vue-next';
const form = ref({
    title: '',
    author: '',
    subject: '',
    description: '',
    grade_level: 7,
    competency: '',
    file_path: '',
    file_path: '',
});

const errors = ref<Record<string, string>>({});
const { confirm } = useConfirmDialog();

const submit = async () => {
    const ok = await confirm({
        title: 'Create book?',
        description: 'This will add a new book to the library.',
        confirmText: 'Create',
    })

    if (!ok) return

    errors.value = {};
    router.post(libraryRoutes.store(), form.value, {
        onError: (errs) => {
            errors.value = errs;
            toast.error('Validation failed');
        },
        onSuccess: () => {
            toast.success('Book created successfully');
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Library', href: libraryRoutes.index().url },
    { title: 'Add a Book', href: libraryRoutes.create().url },
];
</script>

<template>
    <Head title="Add Book" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6 md:px-8">
            <Card class="shadow-lg">
                <CardHeader>
                    <CardTitle class="text-2xl font-bold flex items-center gap-2">
                        <span class="bg-primary/10 text-primary p-2 rounded-lg"><BookPlus class="w-6 h-6" /></span>
                        Add New Book
                    </CardTitle>
                    <CardDescription>
                        Enter the details book you wish to add to the library application.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <!-- Title -->
                        <div class="md:col-span-2">
                            <Label class="text-base font-semibold" for="title">Book Title</Label>
                            <Input v-model="form.title" id="title" placeholder="e.g. Introduction to Physics" class="mt-2" />
                            <div class="text-sm text-red-600 mt-1">{{ errors.title }}</div>
                        </div>

                        <!-- Author -->
                        <div>
                            <Label class="text-base font-semibold" for="author">Author</Label>
                            <Input v-model="form.author" id="author" placeholder="e.g. John Doe" class="mt-2" />
                            <div class="text-sm text-red-600 mt-1">{{ errors.author }}</div>
                        </div>

                        <!-- Subject -->
                        <div>
                            <Label class="text-base font-semibold" for="subject">Subject</Label>
                            <Input v-model="form.subject" id="subject" maxlength="10" placeholder="e.g. Science" class="mt-2" />
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
                            <div class="text-sm text-red-600 mt-1">
                                {{ errors.grade_level }}
                            </div>
                        </div>

                         <!-- Competency -->
                        <div>
                            <Label class="text-base font-semibold" for="competency">Competency (Optional)</Label>
                            <Input v-model="form.competency" id="competency" placeholder="e.g. Reading Comprehension" class="mt-2" />
                            <div class="text-sm text-red-600 mt-1">
                                {{ errors.competency }}
                            </div>
                        </div>

                         <!-- Description -->
                        <div class="md:col-span-2">
                            <Label class="text-base font-semibold" for="description">Synopsis / Description</Label>
                            <Textarea v-model="form.description" id="description" placeholder="Enter a brief summary of the book..." class="mt-2 min-h-[100px]" />
                            <div class="text-sm text-red-600 mt-1">
                                {{ errors.description }}
                            </div>
                        </div>
                    </div>
                </CardContent>
                <CardFooter class="flex justify-end gap-3 bg-muted/20 py-4 px-6 rounded-b-lg">
                    <Button variant="outline" @click="router.visit(libraryRoutes.index().url)">Cancel</Button>
                    <Button :disabled="false" @click="submit">
                        <Spinner v-if="false" class="mr-2" />
                        Create Book
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
