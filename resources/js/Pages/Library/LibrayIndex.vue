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
import libraryRoutes from '@/routes/library';
import type { PagePropsWithFlash } from '@/types';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { Bell, ArrowUp, ArrowDown, Pencil, Trash, Plus, Clock, XCircle, BookPlus, BookOpenCheck, Search, Upload, Download } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { toast } from 'vue-sonner';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger
} from '@/components/ui/tooltip'
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';

const { confirm } = useConfirmDialog();

interface Book {
    id: number;
    book_code: string;
    title: string;
    author?: string;
    grade_level: number;
    type: string;
    description?: string;
    file_path?: string;
    is_available: boolean;
    description?: string;
    file_path?: string;
    is_available: boolean;
    current_user_request_status: string | null;
    current_user_has_book: boolean;
}

const props = defineProps<{
    books: Book[];
    auth: { user: { role: string } };
}>();

const page = usePage<PagePropsWithFlash>();
const flashMessage = page.props.flash;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Library', href: libraryRoutes.index().url },
];

const searchQuery = ref('');
const filterGrade = ref<number | ''>('');

const currentPage = ref(1);
const itemsPerPage = 10;

type SortKey = 'book_code' | 'title' | 'author' | 'grade_level' | 'type';
const sortKey = ref<SortKey>('title');
const sortDirection = ref<'asc' | 'desc'>('asc');

const sortBy = (key: SortKey) => {
    if (sortKey.value === key) {
        sortDirection.value =
            sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
};

const filteredBooks = computed(() => {
    const books = props.books.filter((book) => {
        const matchesSearch =
            !searchQuery.value ||
            book.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (book.author &&
                book.author
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()));

        const matchesGrade =
            !filterGrade.value || book.grade_level === filterGrade.value;

        return matchesSearch && matchesGrade;
    });

    return books.sort((a, b) => {
        const aVal = a[sortKey.value] ?? '';
        const bVal = b[sortKey.value] ?? '';

        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const paginatedBooks = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredBooks.value.slice(start, start + itemsPerPage);
});

const handleDelete = async (id: number) => {
    const ok = await confirm({
        title: 'Delete book?',
        description: 'This will permanently remove the book.',
        confirmText: 'Delete',
        variant: 'destructive',
    });

    if (!ok) return;

    router.delete(libraryRoutes.destroy(id), {
        onSuccess: () => toast.success('Book deleted'),
    });
};

const columnSizes = {
    book_code: 'w-[140px] min-w-[140px]',
    title: 'w-[260px] min-w-[260px]',
    author: 'w-[220px] min-w-[220px]',
    grade_level: 'w-[120px] min-w-[120px]',
    action: 'w-[180px] min-w-[180px]',
} as const;

const form = useForm({
  expected_return_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
})

const showBorrowDialog = ref(false);
const selectedBook = ref<Book | null>(null);

const openBorrowDialog = (book: Book) => {
    selectedBook.value = book;
    showBorrowDialog.value = true;
};

const handleBorrow = () => {
    if (!selectedBook.value) return;

    form.post(libraryRoutes.borrow(selectedBook.value.id).url, {
        onSuccess: () => {
             showBorrowDialog.value = false;
             if (page.props.flash.error) {
                 toast.error(page.props.flash.error);
             } else {
                 toast.success('Borrow request sent successfully');
             }
        }
    })
}

// Reuse return logic
const handleReturn = async (bookId: number) => {
     const ok = await confirm({
        title: 'Return Book?',
        description: 'Are you sure you want to return this book to the library?',
        confirmText: 'Return Book',
    });

    if (!ok) return;

    form.post(libraryRoutes.return(bookId).url, {
        onSuccess: () => {
             toast.success('Book returned successfully');
        }
    });
};

const showImportDialog = ref(false);
const importForm = useForm({
    file: null as File | null,
});

const handleImport = () => {
    if (!importForm.file) return;

    importForm.post('/library/import', {
        onSuccess: () => {
            showImportDialog.value = false;
            importForm.reset();
            toast.success('Books imported successfully');
        },
        onError: () => toast.error('Failed to import books. Check format.'),
    });
};

// Bulk Delete Logic
const selectedBooks = ref<number[]>([]);

const toggleSelectAll = (checked: boolean) => {
    if (checked) {
        selectedBooks.value = paginatedBooks.value.map(book => book.id);
    } else {
        selectedBooks.value = [];
    }
};

const handleBulkDelete = async () => {
     const ok = await confirm({
        title: 'Delete Selected Books?',
        description: `Are you sure you want to delete ${selectedBooks.value.length} books? This cannot be undone.`,
        confirmText: 'Delete All',
        variant: 'destructive',
    });

    if (!ok) return;

    router.post('/library/bulk-delete', { ids: selectedBooks.value }, {
        onSuccess: () => {
            selectedBooks.value = [];
            toast.success('Selected books deleted successfully');
        }
    });
};
</script>

<template>
    <Head title="Library" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-6">


            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                     <h2 class="text-2xl font-bold tracking-tight">Library Books</h2>
                     <p class="text-muted-foreground">
                        Browse and borrow books from the library.
                    </p>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-4">
                <div class="flex gap-2">
                    <div class="relative w-64 items-center">
                        <Input
                            type="text"
                            placeholder="Search title/author..."
                            v-model="searchQuery"
                            class="pl-10"
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
                            <option v-for="n in 6" :key="n" :value="n + 6">
                                Grade {{ n + 6 }}
                            </option>
                        </select>
                        <ArrowDown class="absolute right-3 top-3 h-4 w-4 opacity-50 pointer-events-none" />
                    </div>
                </div>

                <div v-if="props.auth.user.role === 'librarian'" class="flex gap-2">


                    <Button @click="showImportDialog = true" variant="outline" class="cursor-pointer">
                        <Upload class="mr-2 h-4 w-4" /> Import CSV
                    </Button>
                    <Link :href="libraryRoutes.create()">
                        <Button class="cursor-pointer">
                             <Plus class="mr-2 h-4 w-4" /> Add Book
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>

                        <TableHead class="w-[50px]">#</TableHead>
                        <TableHead class="w-[140px] cursor-pointer" @click="sortBy('book_code')">
                            <div class="flex items-center gap-1">
                                Book Code
                                <ArrowUp v-if="sortKey === 'book_code' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'book_code' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>

                        <TableHead class="min-w-[200px] cursor-pointer" @click="sortBy('title')">
                            <div class="flex items-center gap-1">
                                Title
                                <ArrowUp v-if="sortKey === 'title' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'title' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>

                        <TableHead class="w-[220px] cursor-pointer" @click="sortBy('author')">
                            <div class="flex items-center gap-1">
                                Author
                                <ArrowUp v-if="sortKey === 'author' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'author' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>

                        <TableHead class="w-[120px] cursor-pointer" @click="sortBy('grade_level')">
                            <div class="flex items-center gap-1">
                                Grade Level
                                <ArrowUp v-if="sortKey === 'grade_level' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'grade_level' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>



                        <TableHead class="w-[180px] text-right">Action</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow
                        v-for="(book, index) in paginatedBooks"
                        :key="book.id"
                        class="cursor-pointer hover:bg-muted/50 transition"
                        @click="router.visit(libraryRoutes.show(book.id))"
                    >

                        <TableCell class="font-medium text-muted-foreground">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</TableCell>
                        <TableCell>{{ book.book_code }}</TableCell>
                        <TableCell>{{ book.title }}</TableCell>
                        <TableCell>{{ book.author }}</TableCell>
                        <TableCell>{{ book.grade_level }}</TableCell>

                        <TableCell class="space-x-2 h-[52px] flex items-center justify-end">
                            <template v-if="['student', 'teacher'].includes(props.auth.user.role)">
                                
                                <div v-if="book.current_user_has_book">
                                    <Button
                                        size="sm"
                                        class="bg-blue-600 hover:bg-blue-700 cursor-pointer h-8"
                                        @click.stop="handleReturn(book.id)"
                                    >
                                        <BookOpenCheck class="h-4 w-4 mr-2" /> Return
                                    </Button>
                                </div>
                                <div v-else-if="book.current_user_request_status === 'pending'">
                                    <span class="text-yellow-600 font-semibold text-sm flex items-center gap-1">
                                        <Clock class="h-4 w-4" /> Pending
                                    </span>
                                </div>
                                <div v-else-if="!book.is_available">
                                     <span class="text-red-600 font-semibold text-sm flex items-center gap-1">
                                        <XCircle class="h-4 w-4" /> Unavailable
                                    </span>
                                </div>
                                <Button
                                    v-else
                                    size="sm"
                                    class="bg-green-600 hover:bg-green-700 cursor-pointer h-8"
                                    @click.stop="openBorrowDialog(book)"
                                    :disabled="form.processing"
                                >
                                    <BookPlus class="h-4 w-4 mr-2" /> Borrow
                                </Button>
                            </template>

                            <template v-if="props.auth.user.role === 'librarian'">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Link :href="libraryRoutes.edit(book.id)" @click.stop>
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    class="h-8 w-8 text-blue-600 hover:text-blue-700 hover:bg-blue-50 cursor-pointer"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Edit Book</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                             <Button
                                                variant="outline"
                                                size="icon"
                                                class="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-50 cursor-pointer"
                                                @click.stop="handleDelete(book.id)"
                                            >
                                                <Trash class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Delete Book</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </template>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            </div>

            <Pagination
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

        <Dialog v-model:open="showBorrowDialog">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Borrow Request</DialogTitle>
                    <DialogDescription>
                        Request to borrow: <span class="font-bold">{{ selectedBook?.title }}</span>
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="return_date" class="text-right">
                            Return Date
                        </Label>
                        <Input
                            id="return_date"
                            type="date"
                            v-model="form.expected_return_date"
                            class="col-span-3"
                            required
                        />
                    </div>
                        <p v-if="form.errors.expected_return_date" class="text-sm text-red-500">
                        {{ form.errors.expected_return_date }}
                    </p>
                </div>
                <DialogFooter>
                    <Button type="submit" @click="handleBorrow" :disabled="form.processing">
                        Submit Request
                    </Button>
                </DialogFooter>
            </DialogContent>

        </Dialog>

        <!-- Import Dialog -->
        <Dialog v-model:open="showImportDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Import Books from CSV</DialogTitle>
                    <DialogDescription>
                        <ol class="list-decimal list-inside space-y-1 mt-2 text-sm text-muted-foreground">
                            <li>Download the template CSV below.</li>
                            <li>Add your books to the file (keep the header).</li>
                            <li>Upload the file here.</li>
                            <li>Wait for the import to complete.</li>
                         </ol>
                    <div class="mt-3 bg-muted p-2 rounded text-xs font-mono">
                         Required Columns: title, author, grade_level, description, quantity, subject <br>
                         * <span class="text-red-500 font-bold">Important:</span> Rows with missing Title, Author, or Grade Level will be skipped.
                    </div>
                </DialogDescription>
                <div class="mt-4 flex justify-center">
                    <a href="/downloads/template/books" download class="w-full">
                        <Button variant="outline" class="w-full cursor-pointer text-blue-600 border-blue-200 bg-blue-50 hover:bg-blue-100 font-semibold shadow-sm">
                            <Download class="mr-2 h-4 w-4" /> Download Template
                        </Button>
                    </a>
                </div>
            </DialogHeader>
                <div class="grid gap-4 py-4">
                    <Label for="csv_file">Select CSV File</Label>
                    <Input
                        id="csv_file"
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
