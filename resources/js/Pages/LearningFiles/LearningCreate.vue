<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea'; // Assuming exists, checking imports
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { toast } from 'vue-sonner';

const form = useForm({
    title: '',
    description: '',
    grade_level: '',
    file: null as File | null,
});

const submit = () => {
    form.post('/learning-files', {
        onSuccess: () => {
            toast.success('File uploaded successfully');
            form.reset();
        },
        onError: () => {
            toast.error('Failed to upload file. Please check errors.');
        },
    });
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.file = target.files[0];
    }
};
</script>

<template>
    <Head title="Upload File" />

    <AppLayout :breadcrumbs="[
        { title: 'Learning Files', href: '/learning-files' },
        { title: 'Upload', href: '#' }
    ]">
        <div class="max-w-2xl mx-auto p-6 space-y-6">
            <div>
                <h2 class="text-2xl font-bold">Upload Learning Material</h2>
                <p class="text-muted-foreground">Share resources with students.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Title -->
                <div class="space-y-2">
                    <Label for="title">Title</Label>
                    <Input id="title" v-model="form.title" placeholder="e.g., Biology Chapter 1 Notes" required />
                    <p v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</p>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">Description (Optional)</Label>
                    <Input id="description" v-model="form.description" placeholder="Brief description of contents..." /> <!-- Input vs Textarea fallback -->
                    <p v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</p>
                </div>

                <!-- Grade Level -->
                <div class="space-y-2">
                    <Label for="grade_level">Grade Level</Label>
                    <Select v-model="form.grade_level" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Select Grade" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="7">Grade 7</SelectItem>
                            <SelectItem value="8">Grade 8</SelectItem>
                            <SelectItem value="9">Grade 9</SelectItem>
                            <SelectItem value="10">Grade 10</SelectItem>
                            <SelectItem value="11">Grade 11</SelectItem>
                            <SelectItem value="12">Grade 12</SelectItem>
                        </SelectContent>
                    </Select>
                     <p v-if="form.errors.grade_level" class="text-sm text-red-500">{{ form.errors.grade_level }}</p>
                </div>

                <!-- File -->
                <div class="space-y-2">
                    <Label for="file">File (PDF, DOCX, PPTX - Max 50MB)</Label>
                    <Input id="file" type="file" @change="handleFileChange" required accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.png" />
                    <p v-if="form.errors.file" class="text-sm text-red-500">{{ form.errors.file }}</p>
                </div>

                <div class="flex justify-end gap-2">
                     <Link href="/learning-files">
                        <Button variant="outline" type="button">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Uploading...' : 'Upload File' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
