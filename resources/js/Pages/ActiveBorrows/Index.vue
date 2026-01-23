<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { 
    Pagination, 
    PaginationContent, 
    PaginationItem, 
    PaginationPrevious, 
    PaginationNext, 
    PaginationEllipsis 
} from '@/components/ui/pagination';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Search, RotateCcw, AlertCircle, ArrowUp, ArrowDown } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { toast } from 'vue-sonner';

const props = defineProps<{
    borrows: {
        data: Array<{
            id: number;
            user: { name: string; email: string };
            book: { title: string; book_code: string; id: number };
            expected_return_date: string;
            created_at: string;
        }>;
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search: string;
        filter: string;
        sort: string;
        direction: string;
    };
}>();

const search = ref(props.filters.search || '');
const filter = ref(props.filters.filter || '');
const currentPage = ref(props.borrows.current_page);
const sortKey = ref(props.filters.sort || 'created_at');
const sortDirection = ref(props.filters.direction || 'desc');

const { confirm } = useConfirmDialog();

const sortBy = (key: string) => {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
};

let timeout: NodeJS.Timeout;
watch([search, filter, sortKey, sortDirection, currentPage], ([newSearch, newFilter, newSort, newDirection, newPage], [oldSearch, oldFilter, oldSort, oldDirection, oldPage]) => {
    
    // Reset page if filters/sort change
    if (newSearch !== oldSearch || newFilter !== oldFilter || newSort !== oldSort || newDirection !== oldDirection) {
        currentPage.value = 1;
    }

    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get('/admin/active-borrows', { 
            search: newSearch, 
            filter: newFilter,
            sort: newSort,
            direction: newDirection,
            page: currentPage.value
        }, { preserveState: true, replace: true });
    }, 300);
});

const isOverdue = (date: string) => {
    return new Date(date) < new Date();
};

const handleReturn = async (bookId: number, userName: string) => {
    const ok = await confirm({
        title: 'Return Book?',
        description: `Mark book as returned for ${userName}? The book will be available for the library.`,
        confirmText: 'Return Book',
    });

    if (!ok) return;

    router.post(route('library.return', bookId), {}, {
        onSuccess: () => toast.success('Book returned successfully'),
    });
};
</script>

<template>
    <Head title="Active Borrows" />

    <AppLayout :breadcrumbs="[{ title: 'Active Borrows', href: '/admin/active-borrows' }]">
        <div class="px-4 py-6 md:px-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Active Borrows</h1>
                    <p class="text-muted-foreground">Monitor currently borrowed books.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-4 mb-4">
                 <div class="relative w-full max-w-sm items-center">
                    <Input 
                        type="text" 
                        placeholder="Search user or book..." 
                        v-model="search" 
                        class="pl-10 h-9" 
                    />
                     <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
                        <Search class="size-4 text-muted-foreground" />
                    </span>
                </div>
                
                <div class="relative w-[180px]">
                    <select
                        v-model="filter"
                         class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 appearance-none"
                    >
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="overdue">Overdue</option>
                    </select>
                    <ArrowDown class="absolute right-3 top-3 h-4 w-4 opacity-50 pointer-events-none" />
                </div>
            </div>

            <div class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-[50px]">#</TableHead>
                            <TableHead class="cursor-pointer" @click="sortBy('user')">
                                <div class="flex items-center gap-1">
                                    User
                                    <ArrowUp v-if="sortKey === 'user' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'user' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer" @click="sortBy('book')">
                                <div class="flex items-center gap-1">
                                    Book
                                    <ArrowUp v-if="sortKey === 'book' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'book' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer" @click="sortBy('created_at')">
                                <div class="flex items-center gap-1">
                                    Borrowed Date
                                    <ArrowUp v-if="sortKey === 'created_at' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'created_at' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>
                            <TableHead class="cursor-pointer" @click="sortBy('due_date')">
                                <div class="flex items-center gap-1">
                                    Due Date
                                    <ArrowUp v-if="sortKey === 'due_date' && sortDirection === 'asc'" class="h-4 w-4" />
                                    <ArrowDown v-if="sortKey === 'due_date' && sortDirection === 'desc'" class="h-4 w-4" />
                                </div>
                            </TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(item, index) in borrows.data" :key="item.id">
                            <TableCell class="font-medium text-muted-foreground">{{ (borrows.current_page - 1) * borrows.per_page + index + 1 }}</TableCell>
                            <TableCell class="font-medium">
                                <div>{{ item.user.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ item.user.email }}</div>
                            </TableCell>
                            <TableCell>
                                <div>{{ item.book.title }}</div>
                                <div class="text-xs text-muted-foreground">{{ item.book.book_code }}</div>
                            </TableCell>
                            <TableCell>{{ new Date(item.created_at).toLocaleDateString() }}</TableCell>
                            <TableCell>{{ new Date(item.expected_return_date).toLocaleDateString() }}</TableCell>
                            <TableCell>
                                <Badge v-if="isOverdue(item.expected_return_date)" variant="destructive" class="bg-red-100 text-red-700 hover:bg-red-200 border-0">
                                    <AlertCircle class="w-3 h-3 mr-1" /> Overdue
                                </Badge>
                                <Badge v-else variant="outline" class="bg-green-100 text-green-700 hover:bg-green-200 border-0">
                                    Active
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button 
                                    size="sm" 
                                    variant="outline" 
                                    class="h-8 border-blue-600 text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                                    @click="handleReturn(item.book.id, item.user.name)"
                                >
                                    <RotateCcw class="w-3 h-3 mr-1" /> Return
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="borrows.data.length === 0">
                            <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                No active borrows found.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                 <Pagination
                    v-model:page="currentPage"
                    :items-per-page="borrows.per_page"
                    :total="borrows.total"
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
    </AppLayout>
</template>
