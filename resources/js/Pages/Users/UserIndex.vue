<script setup lang="ts">
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import AppLayout from '@/layouts/AppLayout.vue'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { type BreadcrumbItem } from '@/types'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Pencil, Trash, UserPlus, Search, ArrowUp, ArrowDown, Upload, Download } from 'lucide-vue-next'
import { ref, watch, computed } from 'vue'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import { useConfirmDialog } from '@/composables/useConfirmDialog'
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
    PaginationEllipsis,
} from '@/components/ui/pagination';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    users: {
        data: Array<{
            id: number
            name: string
            email: string
            role: string
            grade_level: number | null
        }>
        links: any[]
        current_page: number
        last_page: number
        total: number
        per_page: number
    }
    filters: {
        search: string
        role: string
        grade: string
        sort: string
        direction: string
    }
}>()

const search = ref(props.filters.search || '')
const roleFilter = ref(props.filters.role || '')
const gradeFilter = ref(props.filters.grade || '')

// Sorting
const sortKey = ref(props.filters.sort || 'name')
const sortDirection = ref(props.filters.direction || 'asc')

const sortBy = (key: string) => {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortDirection.value = 'asc'
    }
}

// Pagination state
const currentPage = ref(props.users.current_page)

const { confirm } = useConfirmDialog()

// Debounce search and filters
let timeout: NodeJS.Timeout
watch([search, roleFilter, gradeFilter, sortKey, sortDirection, currentPage], ([newSearch, newRole, newGrade, newSort, newDirection, newPage], [oldSearch, oldRole, oldGrade, oldSort, oldDirection, oldPage]) => {
    
    // If filters change, reset page to 1
    if (newSearch !== oldSearch || newRole !== oldRole || newGrade !== oldGrade) {
        currentPage.value = 1
    }

    // Reset grade if role is not student
    if (newRole !== 'student' && newGrade) {
        gradeFilter.value = ''
        return // Watcher will trigger again
    }

    clearTimeout(timeout)
    timeout = setTimeout(() => {
        router.get('/users', { 
            search: newSearch, 
            role: newRole, 
            grade: newGrade,
            sort: newSort, 
            direction: newDirection,
            page: currentPage.value
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true
        })
    }, 300)
})

const deleteUser = async (id: number) => {
    const ok = await confirm({
        title: 'Delete User?',
        description: 'Are you sure you want to delete this user? This action cannot be undone.',
        confirmText: 'Delete',
        cancelText: 'Cancel',
    })

    if (ok) {
        router.delete(`/users/${id}`)
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manage Users', href: '/users' },
]

const getRoleBadgeClass = (role: string) => {
    switch(role) {
        case 'librarian': return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
        case 'teacher': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
        case 'student': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        default: return 'bg-gray-100 text-gray-800'
    }
}

const showImportDialog = ref(false);
const importForm = useForm({
    file: null as File | null,
});

const handleImport = () => {
    if (!importForm.file) return;

    importForm.post('/users/import', {
        onSuccess: () => {
            showImportDialog.value = false;
            importForm.reset();
            toast.success('Users imported successfully');
        },
        onError: () => toast.error('Failed to import users. Check format.'),
    });
};

// Bulk Delete Logic
const selectedUsers = ref<number[]>([]);

const toggleSelectAll = (checked: boolean) => {
    // Only select currently visible users
    if (checked) {
        selectedUsers.value = props.users.data.map(user => user.id);
    } else {
        selectedUsers.value = [];
    }
};

const handleBulkDelete = async () => {
     const ok = await confirm({
        title: 'Delete Selected Users?',
        description: `Are you sure you want to delete ${selectedUsers.value.length} users? This cannot be undone.`,
        confirmText: 'Delete All',
        variant: 'destructive',
    });

    if (!ok) return;

    router.post('/users/bulk-delete', { ids: selectedUsers.value }, {
        onSuccess: () => {
            selectedUsers.value = [];
            toast.success('Selected users deleted successfully');
        }
    });
};
</script>

<template>
    <Head title="Manage Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6 md:px-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Manage Users</h1>
                    <p class="text-muted-foreground">
                        View, manage, and organize system users.
                    </p>
                </div>
            </div>

            <!-- Filters and Actions Bar -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-4">
                <div class="flex flex-1 items-center gap-2 w-full md:w-auto">
                    <!-- Search -->
                    <div class="relative w-full md:w-64 items-center">
                        <Input id="search" type="text" placeholder="Search users..." class="pl-10" v-model="search" />
                        <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
                            <Search class="size-4 text-muted-foreground" />
                        </span>
                    </div>
                    
                    <!-- Role Filter -->
                    <!-- Role Filter -->
                    <div class="relative w-[180px]">
                         <select
                            v-model="roleFilter"
                             class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 appearance-none"
                        >
                            <option value="">All Roles</option>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="librarian">Librarian</option>
                        </select>
                        <ArrowDown class="absolute right-3 top-3 h-4 w-4 opacity-50 pointer-events-none" />
                    </div>

                    <!-- Grade Filter -->
                    <div class="relative w-[180px]">
                        <select
                            v-model="gradeFilter"
                            :disabled="roleFilter !== 'student'"
                             class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 appearance-none"
                        >
                            <option value="">All Grades</option>
                            <option v-for="n in 6" :key="n" :value="n + 6">
                                Grade {{ n + 6 }}
                            </option>
                        </select>
                        <ArrowDown class="absolute right-3 top-3 h-4 w-4 opacity-50 pointer-events-none" />
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">

                    
                     <Button @click="showImportDialog = true" variant="outline" class="cursor-pointer">
                        <Upload class="mr-2 h-4 w-4" /> Import CSV
                    </Button>
                    <Button @click="router.visit('/users/create')" class="cursor-pointer">
                        <UserPlus class="mr-2 h-4 w-4" />
                        Add User
                    </Button>
                </div>
            </div>

            <!-- Table -->
             <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>

                            <TableHead class="w-[50px]">#</TableHead>
                            
                            <TableHead class="cursor-pointer" @click="sortBy('name')">
                                <div class="flex items-center gap-1">
                                    Name
                                    <ArrowUp v-if="sortKey === 'name' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'name' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>

                            <TableHead class="cursor-pointer" @click="sortBy('email')">
                                <div class="flex items-center gap-1">
                                    Email
                                    <ArrowUp v-if="sortKey === 'email' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'email' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>

                            <TableHead class="cursor-pointer" @click="sortBy('role')">
                                <div class="flex items-center gap-1">
                                    Role
                                    <ArrowUp v-if="sortKey === 'role' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'role' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>

                             <TableHead class="cursor-pointer" @click="sortBy('grade_level')">
                                <div class="flex items-center gap-1">
                                    Grade
                                    <ArrowUp v-if="sortKey === 'grade_level' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'grade_level' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>

                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                         <TableRow v-for="(user, index) in users.data" :key="user.id">

                            <TableCell class="font-medium">
                                {{ (users.current_page - 1) * users.per_page + index + 1 }}
                            </TableCell>
                            <TableCell class="font-medium">{{ user.name }}</TableCell>
                            <TableCell>{{ user.email }}</TableCell>
                            <TableCell>
                                <Badge variant="outline" class="font-medium capitalize border-0" :class="getRoleBadgeClass(user.role)">
                                    {{ user.role }}
                                </Badge>
                            </TableCell>
                             <TableCell>{{ user.grade_level ? `Grade ${user.grade_level}` : '-' }}</TableCell>
                            <TableCell class="text-right flex justify-end gap-2">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                class="h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50 cursor-pointer"
                                                @click="router.visit(`/users/${user.id}/edit`)"
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Edit User</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                class="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer"
                                                @click="deleteUser(user.id)"
                                            >
                                                <Trash class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Delete User</TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="users.data.length === 0">
                             <TableCell colspan="6" class="h-24 text-center">
                                No users found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Shadcn Pagination -->
             <div class="mt-4">
                <Pagination
                    v-model:page="currentPage"
                    :total="users.total"
                    :items-per-page="users.per_page"
                    :sibling-count="1"
                    show-edges
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
                            <PaginationEllipsis v-else :key="item.type" :index="index" />
                        </template>
                        <PaginationNext />
                    </PaginationContent>
                </Pagination>
            </div>
        </div>

        <!-- Import Dialog -->
        <Dialog v-model:open="showImportDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Import Users from CSV</DialogTitle>
                    <DialogDescription>
                        <ol class="list-decimal list-inside space-y-1 mt-2 text-sm text-muted-foreground">
                            <li>Download the template CSV below.</li>
                            <li>Add users to the file (Default password: 12345678).</li>
                            <li>Upload the file here.</li>
                            <li>Wait for the import to complete.</li>
                         </ol>
                    <div class="mt-3 bg-muted p-2 rounded text-xs font-mono">
                        Columns: first_name, middle_name, last_name, role, grade_level <br>
                        * Role: student, teacher, librarian <br>
                        * Grade: Optional for Student (Defaults to 7), Ignored for others. <br>
                        * <span class="text-red-500 font-bold">Important:</span> Rows with missing required fields (Name, Role) will be skipped.
                    </div>
                    </DialogDescription>
                    <div class="mt-4 flex justify-center">
                         <a href="/downloads/template/users" download class="w-full">
                            <Button variant="outline" class="w-full cursor-pointer text-blue-600 border-blue-200 bg-blue-50 hover:bg-blue-100 font-semibold shadow-sm">
                                <Download class="mr-2 h-4 w-4" /> Download Template
                            </Button>
                        </a>
                    </div>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                     <Label for="user_csv">Select CSV File</Label>
                    <Input
                        id="user_csv"
                        type="file"
                        accept=".csv"
                        @change="(e: any) => importForm.file = e.target.files[0]"
                    />
                </div>
                <DialogFooter>
                    <Button @click="handleImport" :disabled="importForm.processing">
                        <Upload class="mr-2 h-4 w-4" />
                        Upload & Import
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
