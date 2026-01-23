<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Bell, Download, Trash, Upload, CloudUpload, X, Pencil, ArrowUp, ArrowDown, Search } from 'lucide-vue-next';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
    PaginationFirst,
    PaginationLast,
    PaginationEllipsis,
} from '@/components/ui/pagination';
import { toast } from 'vue-sonner';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { ref, computed } from 'vue';
import axios from 'axios'; 

interface LearningFile {
    id: number;
    title: string;
    description?: string;
    grade_level: number;
    uploader: string;
    created_at: string;
    file_path: string;
    can_delete: boolean;
    download_url: string;
}



const props = defineProps<{
    files: LearningFile[];
}>();

const currentPage = ref(1);
const itemsPerPage = 10;
const searchQuery = ref('');
const filterGrade = ref<string>('');

type SortKey = 'title' | 'description' | 'grade_level' | 'uploader' | 'created_at';
const sortKey = ref<SortKey>('created_at');
const sortDirection = ref<'asc' | 'desc'>('desc');

const sortBy = (key: SortKey) => {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
};

const filteredFiles = computed(() => {
    const result = props.files.filter((file) => {
        const matchesSearch = !searchQuery.value ||
            file.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            file.description?.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const matchesGrade = !filterGrade.value || file.grade_level.toString() === filterGrade.value;
        
        return matchesSearch && matchesGrade;
    });

    return result.sort((a, b) => {
        const aVal = a[sortKey.value];
        const bVal = b[sortKey.value];

        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const paginatedFiles = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredFiles.value.slice(start, start + itemsPerPage);
});

const page = usePage();
const flashMessage = page.props.flash as any;
const userRole = page.props.auth.user.role;

const { confirm } = useConfirmDialog();

const handleDelete = async (file: LearningFile) => {
    const ok = await confirm({
        title: 'Delete File?',
        description: `Are you sure you want to delete "${file.title}"?`,
        variant: 'destructive',
    });

    if (!ok) return;

    router.delete(`/learning-files/${file.id}`, {
        onSuccess: () => toast.success('File deleted successfully'),
        onError: () => toast.error('Failed to delete file'),
    });
};

// Batch Upload Logic
const showUploadDialog = ref(false);
const globalGrade = ref('');
const isUploading = ref(false);
const filesQueue = ref<{
    file: File;
    title: string;
    description: string;
    status: 'pending' | 'uploading' | 'success' | 'error';
    error?: string;
}[]>([]);

// Generate default title from filename (remove extension)
const getDefaultTitle = (filename: string) => {
    return filename.replace(/\.[^/.]+$/, "");
};

// Check duplicate title in queue and append (1), (2) etc.
const getUniqueTitle = (baseTitle: string) => {
    let title = baseTitle;
    let count = 1;
    // Simple check against current queue
    while (filesQueue.value.some(f => f.title === title)) {
        title = `${baseTitle} (${count})`;
        count++;
    }
    return title;
};

const addFiles = (fileList: FileList | null) => {
    if (!fileList) return;
    
    Array.from(fileList).forEach(file => {
        const baseTitle = getDefaultTitle(file.name);
        const title = getUniqueTitle(baseTitle);
        
        filesQueue.value.push({
            file,
            title,
            description: '',
            status: 'pending'
        });
    });
};

const handleFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    addFiles(target.files);
    // Reset input so same files can be selected again if cleared
    target.value = ''; 
};

const onDrop = (e: DragEvent) => {
    addFiles(e.dataTransfer?.files || null);
};

const removeFile = (index: number) => {
    filesQueue.value.splice(index, 1);
};

const submitBatch = async () => {
    if (!globalGrade.value) {
        toast.error('Please select a Grade Level for these files.');
        return;
    }
    
    if (filesQueue.value.length === 0) {
        toast.error('No files to upload.');
        return;
    }

    isUploading.value = true;
    let successCount = 0;

    // Process files sequentially
    for (const item of filesQueue.value) {
        if (item.status === 'success') continue; 
        
        item.status = 'uploading';
        
        const formData = new FormData();
        formData.append('title', item.title);
        formData.append('description', item.description || '');
        formData.append('grade_level', globalGrade.value);
        formData.append('file', item.file);

        try {
            await axios.post('/learning-files', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            item.status = 'success';
            successCount++;
        } catch (error: any) {
            console.error(error);
            item.status = 'error';
            item.error = error.response?.data?.message || 'Upload failed';
        }
    }

    isUploading.value = false;

    if (successCount === filesQueue.value.length) {
        toast.success(`Successfully uploaded ${successCount} files.`);
        showUploadDialog.value = false;
        filesQueue.value = []; // Clear queue
        globalGrade.value = '';
        router.reload({ only: ['files'] }); // Refresh list
    } else {
        toast.warning(`Uploaded ${successCount} files. Some failed.`);
        router.reload({ only: ['files'] });
    }
};

// Edit Logic
const showEditDialog = ref(false);
const editingFile = ref<LearningFile | null>(null);
const editForm = useForm({
    title: '',
    description: '',
    grade_level: '',
});

const handleEdit = (file: LearningFile) => {
    editingFile.value = file;
    editForm.title = file.title;
    editForm.description = file.description || '';
    editForm.grade_level = file.grade_level.toString();
    showEditDialog.value = true;
};

// Pagination Logic - removed server side logic


const submitEdit = () => {
    if (!editingFile.value) return;

    editForm.put(`/learning-files/${editingFile.value.id}`, {
        onSuccess: () => {
             toast.success('File updated selected');
             showEditDialog.value = false;
             editingFile.value = null;
        },
        onError: () => toast.error('Failed to update file'),
    });
};
</script>

<template>
    <Head title="Learning Files" />

    <AppLayout :breadcrumbs="[{ title: 'Learning Files', href: '/learning-files' }]">
        <div class="space-y-4 p-6">
            <div v-if="flashMessage?.message">
                <Alert class="bg-blue-200">
                    <Bell class="h-4 w-4" />
                    <AlertTitle>Notification</AlertTitle>
                    <AlertDescription>
                        {{ flashMessage.message }}
                    </AlertDescription>
                </Alert>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Learning Materials</h2>
                    <p class="text-muted-foreground">
                        Files shared for Grade {{ props.auth?.user?.grade_level || '7-12' }}
                    </p>
                </div>

                </div>



                <!-- Edit Dialog -->
                 <Dialog v-model:open="showEditDialog">
                    <DialogContent class="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Edit File Details</DialogTitle>
                            <DialogDescription>
                                Update title, description, or grade level.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid grid-cols-4 items-center gap-4">
                                <Label for="edit-title" class="text-right">Title</Label>
                                <Input id="edit-title" v-model="editForm.title" class="col-span-3" required />
                            </div>
                            <div class="grid grid-cols-4 items-center gap-4">
                                <Label for="edit-description" class="text-right">Description</Label>
                                <Input id="edit-description" v-model="editForm.description" class="col-span-3" />
                            </div>
                            <div class="grid grid-cols-4 items-center gap-4">
                                <Label for="edit-grade" class="text-right">Grade</Label>
                                <select
                                    id="edit-grade"
                                    v-model="editForm.grade_level"
                                    class="col-span-3 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                    required
                                >
                                    <option value="7">Grade 7</option>
                                    <option value="8">Grade 8</option>
                                    <option value="9">Grade 9</option>
                                    <option value="10">Grade 10</option>
                                    <option value="11">Grade 11</option>
                                    <option value="12">Grade 12</option>
                                </select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="submit" @click="submitEdit" :disabled="editForm.processing">
                                Save Changes
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>


            <!-- Search and Filter -->
             <div class="flex items-center justify-between">
                <div class="flex flex-1 items-center gap-2">
                    <div class="relative w-full max-w-sm items-center">
                        <Input 
                            type="text" 
                            placeholder="Search title..." 
                            v-model="searchQuery" 
                            class="pl-10 h-9" 
                        />
                         <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
                          <Search class="size-4 text-muted-foreground" />
                        </span>
                    </div>
                    <div class="relative w-[180px]">
                        <select
                            v-model="filterGrade"
                             class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 appearance-none"
                        >
                            <option value="">All Grades</option>
                            <option v-for="n in 6" :key="n" :value="String(n + 6)">
                                Grade {{ n + 6 }}
                            </option>
                        </select>
                        <ArrowDown class="absolute right-3 top-3 h-4 w-4 opacity-50 pointer-events-none" />
                    </div>
                </div>

                <div v-if="['teacher', 'librarian'].includes(userRole)">
                    <Dialog v-model:open="showUploadDialog">
                        <DialogTrigger as-child>
                             <Button class="cursor-pointer">
                                <Upload class="w-4 h-4 mr-2" />
                                Upload Files
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-2xl">
                            <DialogHeader>
                                <DialogTitle>Upload Learning Materials</DialogTitle>
                                <DialogDescription>
                                    Upload multiple files. Max 50MB per file.
                                </DialogDescription>
                            </DialogHeader>
                            
                            <div class="space-y-4 py-4">
                                <!-- Global Grade Selection -->
                                <div class="grid grid-cols-4 items-center gap-4">
                                    <Label for="grade" class="text-right font-bold">Target Grade</Label>
                                    <select
                                        id="grade"
                                        v-model="globalGrade"
                                        class="col-span-3 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                        required
                                    >
                                        <option value="" disabled>Select Grade for All Files</option>
                                        <option value="7">Grade 7</option>
                                        <option value="8">Grade 8</option>
                                        <option value="9">Grade 9</option>
                                        <option value="10">Grade 10</option>
                                        <option value="11">Grade 11</option>
                                        <option value="12">Grade 12</option>
                                    </select>
                                </div>

                                <!-- Drag and Drop Zone -->
                                <div 
                                    class="border-2 border-dashed rounded-lg p-8 text-center hover:bg-muted/50 transition-colors cursor-pointer"
                                    :class="{'border-blue-500 bg-blue-50': false}" 
                                    @dragover.prevent
                                    @drop.prevent="onDrop"
                                    @click="$refs.fileInput.click()"
                                >
                                    <input 
                                        type="file" 
                                        ref="fileInput" 
                                        multiple 
                                        class="hidden" 
                                        @change="handleFileChange"
                                        accept=".pdf,.doc,.docx,.ppt,.pptx,.txt,.jpg,.png"
                                    >
                                    <CloudUpload class="w-12 h-12 mx-auto text-muted-foreground mb-2" />
                                    <p class="text-sm font-medium">Click or drag files here to upload</p>
                                    <p class="text-xs text-muted-foreground mt-1">PDF, DOCX, PPTX, Images (Max 50MB)</p>
                                </div>

                                <!-- File List -->
                                <div v-if="filesQueue.length > 0" class="max-h-[300px] overflow-y-auto space-y-2 border rounded-md p-2">
                                     <div v-for="(item, index) in filesQueue" :key="index" class="flex items-start gap-2 p-2 bg-card rounded border relative group">
                                        <div class="flex-1 space-y-2">
                                            <div class="grid grid-cols-2 gap-2">
                                                <div class="space-y-1">
                                                     <Label class="text-xs text-muted-foreground">Title</Label>
                                                     <Input v-model="item.title" class="h-8 text-sm" placeholder="File Title" />
                                                </div>
                                                <div class="space-y-1">
                                                     <Label class="text-xs text-muted-foreground">Description (Optional)</Label>
                                                     <Input v-model="item.description" class="h-8 text-sm" placeholder="Description" />
                                                </div>
                                            </div>
                                            <p class="text-xs text-muted-foreground truncate">{{ item.file.name }} ({{ (item.file.size / 1024 / 1024).toFixed(2) }} MB)</p>
                                            
                                            <div v-if="item.status === 'error'" class="text-xs text-red-500">
                                                Error: {{ item.error }}
                                            </div>
                                             <div v-if="item.status === 'success'" class="text-xs text-green-600 font-medium">
                                                Uploaded
                                            </div>
                                        </div>
                                        
                                        <Button 
                                            v-if="item.status !== 'uploading'"
                                            variant="ghost" 
                                            size="icon" 
                                            class="h-6 w-6 absolute top-1 right-1 text-muted-foreground hover:text-destructive"
                                            @click="removeFile(index)"
                                        >
                                            <X class="w-4 h-4" />
                                        </Button>
                                     </div>
                                </div>
                            </div>

                            <DialogFooter>
                                <Button type="submit" @click="submitBatch" :disabled="isUploading || filesQueue.length === 0">
                                    {{ isUploading ? 'Uploading...' : `Upload ${filesQueue.length} Files` }}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">#</TableHead>
                            <TableHead class="min-w-[200px] cursor-pointer" @click="sortBy('title')">
                                <div class="flex items-center gap-1">
                                    Title
                                    <ArrowUp v-if="sortKey === 'title' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'title' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer" @click="sortBy('description')">
                                <div class="flex items-center gap-1">
                                    Description
                                    <ArrowUp v-if="sortKey === 'description' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'description' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>
                            <TableHead class="w-[120px] cursor-pointer" @click="sortBy('grade_level')">
                                <div class="flex items-center gap-1">
                                    Grade Level
                                    <ArrowUp v-if="sortKey === 'grade_level' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'grade_level' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>
                            <TableHead class="w-[150px] cursor-pointer" @click="sortBy('uploader')">
                                <div class="flex items-center gap-1">
                                    Uploaded By
                                    <ArrowUp v-if="sortKey === 'uploader' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'uploader' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>
                            <TableHead class="w-[150px] cursor-pointer" @click="sortBy('created_at')">
                                <div class="flex items-center gap-1">
                                    Date
                                    <ArrowUp v-if="sortKey === 'created_at' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'created_at' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>
                            <TableHead class="w-[140px] text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(file, index) in paginatedFiles" :key="file.id">
                            <TableCell class="font-medium text-muted-foreground">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</TableCell>
                            <TableCell class="font-medium">
                                <a :href="file.file_path" target="_blank" class="hover:underline text-blue-600 font-semibold">
                                     {{ file.title }}
                                </a>
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{ file.description || '-' }}</TableCell>
                            <TableCell>Grade {{ file.grade_level }}</TableCell>
                            <TableCell>{{ file.uploader }}</TableCell>
                            <TableCell>{{ file.created_at }}</TableCell>
                            <TableCell>
                                <div class="flex items-center justify-end gap-2">
                                    <Button 
                                        v-if="file.can_delete" 
                                        size="sm" 
                                        variant="outline"
                                        @click="handleEdit(file)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Button>

                                    <Button 
                                        v-if="file.can_delete" 
                                        size="sm" 
                                        variant="destructive" 
                                        @click="handleDelete(file)"
                                    >
                                        <Trash class="h-4 w-4" />
                                    </Button>

                                    <a :href="`/learning-files/${file.id}/download`">
                                        <Button size="sm" variant="outline" class="cursor-pointer">
                                            <Download class="h-4 w-4 mr-1" />
                                            Download
                                        </Button>
                                    </a>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="filteredFiles.length === 0">
                            <TableCell colspan="7" class="text-center h-24 text-muted-foreground">
                                No files found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex justify-end" v-if="filteredFiles.length > 0">
                <Pagination 
                    v-model:page="currentPage" 
                    :total="filteredFiles.length" 
                    :items-per-page="itemsPerPage" 
                >
                    <PaginationContent v-slot="{ items }">
                        <PaginationPrevious />
                        <template v-for="(item, index) in items" :key="index">
                            <PaginationItem
                                v-if="item.type === 'page'"
                                :value="item.value"
                                :is-active="item.value === currentPage"
                            >
                                {{ item.value }}
                            </PaginationItem>
                        </template>
                        <PaginationNext />
                    </PaginationContent>
                </Pagination>
            </div>
        </div>
    </AppLayout>
</template>
