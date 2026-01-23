<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { PagePropsWithFlash } from '@/types';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input'
import { Bell, ArrowUp, ArrowDown, BookOpenCheck, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import libraryRoutes from '@/routes/library';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

const { confirm } = useConfirmDialog();

interface Book {
    id: number;
    title: string;
    book_code: string;
    author: string;
}

interface BorrowLog {
    id: number;
    book: Book;
    borrowed_at: string;
    expected_return_date?: string; // If we want to show it, needed from somewhere. BorrowLog doesn't have it by default?
                                   // Actually BorrowRequest has it. The Controller sends BorrowLog.
                                   // BorrowLog has created_at (borrowed_at).
}

const props = defineProps<{
    books: BorrowLog[];
}>();

const page = usePage<PagePropsWithFlash>();
const flashMessage = page.props.flash;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Books', href: '#' },
];

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

type SortKey = 'book.title' | 'borrowed_at';
const sortKey = ref<SortKey>('borrowed_at');
const sortDirection = ref<'asc' | 'desc'>('desc');

const sortBy = (key: SortKey) => {
    if (sortKey.value === key) {
        sortDirection.value =
            sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
};

const getNestedValue = (obj: any, path: string) => {
    return path.split('.').reduce((acc, part) => acc && acc[part], obj);
};

const filteredBooks = computed(() => {
    const books = props.books.filter((log) => {
        return !searchQuery.value ||
            log.book.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            log.book.author.toLowerCase().includes(searchQuery.value.toLowerCase());
    });

    return books.sort((a, b) => {
        const aVal = getNestedValue(a, sortKey.value) ?? '';
        const bVal = getNestedValue(b, sortKey.value) ?? '';

        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const paginatedBooks = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredBooks.value.slice(start, start + itemsPerPage);
});

const form = useForm({});

const handleReturn = async (bookId: number) => {
     const ok = await confirm({
        title: 'Return Book?',
        description: 'Are you sure you want to return this book to the library?',
        confirmText: 'Return Book',
    });

    if (!ok) return;

    if (!ok) return;

    form.post(libraryRoutes.return(bookId).url, {
        onSuccess: () => {
             // Flash message handles notification
        }
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <Head title="My Books" />

    <AppLayout :breadcrumbs="breadcrumbs">
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

            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                     <h2 class="text-2xl font-bold tracking-tight">My Books</h2>
                     <p class="text-muted-foreground">
                        View books currently in your possession.
                    </p>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="flex items-center justify-between">
                <div class="flex flex-1 items-center gap-2">
                     <div class="relative w-full max-w-sm items-center">
                        <Input 
                            id="search" 
                            type="text" 
                            placeholder="Search title or author..." 
                            v-model="searchQuery" 
                            class="pl-10 h-9" 
                        />
                         <span class="absolute start-0 inset-y-0 flex items-center justify-center px-2">
                          <Search class="size-4 text-muted-foreground" />
                        </span>
                    </div>
                </div>
            </div>

            <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[50px]">#</TableHead>
                        <TableHead class="min-w-[120px]">Book Code</TableHead>
                        <TableHead class="min-w-[200px] cursor-pointer" @click="sortBy('book.title')">
                            <div class="flex items-center gap-1">
                                Book Title
                                <ArrowUp v-if="sortKey === 'book.title' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'book.title' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>
                        <TableHead class="min-w-[150px]">Author</TableHead>
                        <TableHead class="w-[150px] cursor-pointer" @click="sortBy('borrowed_at')">
                            <div class="flex items-center gap-1">
                                Borrowed On
                                <ArrowUp v-if="sortKey === 'borrowed_at' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'borrowed_at' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>
                        <TableHead class="w-[150px]">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow
                        v-for="(log, index) in paginatedBooks"
                        :key="log.id"
                        class="hover:bg-muted/50 transition"
                    >
                         <TableCell class="font-medium text-muted-foreground">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</TableCell>
                        <TableCell>{{ log.book.book_code }}</TableCell>
                        <TableCell class="font-medium">
                            <Link :href="libraryRoutes.show(log.book.id).url" class="hover:underline">
                                {{ log.book.title }}
                            </Link>
                        </TableCell>
                        <TableCell>{{ log.book.author }}</TableCell>
                        <TableCell>{{ formatDate(log.borrowed_at) }}</TableCell>
                        <TableCell>
                            <Button
                                size="sm"
                                variant="outline"
                                class="border-blue-600 text-blue-600 hover:bg-blue-50 cursor-pointer"
                                @click="handleReturn(log.book.id)"
                                :disabled="form.processing"
                            >
                                <BookOpenCheck class="w-4 h-4 mr-2" /> Return
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            </div>

            <div v-if="filteredBooks.length === 0" class="text-center py-10 text-muted-foreground">
                You have no books borrowed currently.
            </div>

            <Pagination
                v-if="filteredBooks.length > 0"
                v-model:page="currentPage"
                :items-per-page="itemsPerPage"
                :total="filteredBooks.length"
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
    </AppLayout>
</template>
