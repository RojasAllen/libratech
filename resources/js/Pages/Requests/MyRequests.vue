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
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { Bell, ArrowUp, ArrowDown, Search, ChevronDown, X, ArrowRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { Badge } from '@/components/ui/badge';
import borrowRequestsData from '@/routes/borrow-requests'; // renamed to avoid conflict if any interface named same
import libraryRoutes from '@/routes/library';
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
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

interface Book {
    id: number;
    title: string;
    book_code: string;
}

interface BorrowRequest {
    id: number;
    book: Book;
    borrow_status: 'pending' | 'approved' | 'rejected' | 'returned';
    expected_return_date: string;
    created_at: string;
    reject_reason?: string;
}

const props = defineProps<{
    requests: BorrowRequest[];
}>();

const form = useForm({});
const { confirm } = useConfirmDialog();

const borrowForm = useForm({
  expected_return_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
});
const showBorrowDialog = ref(false);
const selectedBookForBorrow = ref<Book | null>(null);

const handleCancel = async (id: number) => {
    const ok = await confirm({
        title: 'Cancel Request?',
        description: 'Are you sure you want to cancel this pending request?',
        confirmText: 'Yes, Cancel',
        variant: 'destructive',
    });

    if (!ok) return;

    form.delete(borrowRequestsData.cancel(id).url, {
        onSuccess: () => {
             toast.success('Borrow request cancelled');
        },
        onError: () => {
            toast.error('Failed to cancel request');
        }
    });
};

const openBorrowAgain = (book: Book) => {
    selectedBookForBorrow.value = book;
    showBorrowDialog.value = true;
};

const submitBorrowAgain = () => {
    if (!selectedBookForBorrow.value) return;

    borrowForm.post(libraryRoutes.borrow(selectedBookForBorrow.value.id).url, {
        onSuccess: () => {
             showBorrowDialog.value = false;
             if (page.props.flash.error) {
                 toast.error(page.props.flash.error);
             } else {
                 toast.success('Borrow request sent successfully');
             }
        },
        onError: () => {
            toast.error('Failed to send borrow request');
        }
    });
};
const page = usePage<PagePropsWithFlash>();
const flashMessage = page.props.flash;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Requests', href: '#' },
];

const searchQuery = ref('');
const statusFilter = ref('all');
const currentPage = ref(1);
const itemsPerPage = 10;

type SortKey = 'book.title' | 'borrow_status' | 'created_at' | 'expected_return_date';
const sortKey = ref<SortKey>('created_at');
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

const filteredRequests = computed(() => {
    const requests = props.requests.filter((req) => {
        const matchesSearch = !searchQuery.value ||
            req.book.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            req.borrow_status.toLowerCase().includes(searchQuery.value.toLowerCase());
        
        const matchesStatus = statusFilter.value === 'all' || req.borrow_status === statusFilter.value;

        return matchesSearch && matchesStatus;
    });

    return requests.sort((a, b) => {
        const aVal = getNestedValue(a, sortKey.value) ?? '';
        const bVal = getNestedValue(b, sortKey.value) ?? '';

        if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });
});

const paginatedRequests = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredRequests.value.slice(start, start + itemsPerPage);
});

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'approved': return 'default'; // Greenish usually if configured, or I can add a class
        case 'rejected': return 'destructive';
        case 'pending': return 'secondary';
        case 'returned': return 'outline';
        default: return 'outline';
    }
};

const getStatusClass = (status: string) => {
     switch (status) {
        case 'approved': return 'bg-green-600 hover:bg-green-700'; 
        default: return '';
    }
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const columnSizes = {
    title: 'w-[200px]',
    status: 'w-[100px]',
    created: 'w-[150px]',
    return: 'w-[150px]',
    reason: 'w-[200px]', 
    action: 'w-[150px]',
} as const;
</script>

<template>
    <Head title="My Requests" />

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
                     <h2 class="text-2xl font-bold tracking-tight">My Requests</h2>
                     <p class="text-muted-foreground">
                        Track your book borrow requests.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex flex-1 items-center gap-2">
                    <div class="relative w-full max-w-sm items-center">
                        <Input 
                            id="search" 
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
                            v-model="statusFilter"
                            class="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 appearance-none"
                        >
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                         <ChevronDown class="absolute right-3 top-2.5 h-4 w-4 opacity-50 pointer-events-none" />
                    </div>
                </div>
            </div>

            <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[50px]">#</TableHead>
                        <TableHead class="min-w-[200px] cursor-pointer" @click="sortBy('book.title')">
                            <div class="flex items-center gap-1">
                                Book Title
                                <ArrowUp v-if="sortKey === 'book.title' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'book.title' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>

                        <TableHead class="w-[100px] cursor-pointer" @click="sortBy('borrow_status')">
                            <div class="flex items-center gap-1">
                                Status
                                <ArrowUp v-if="sortKey === 'borrow_status' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'borrow_status' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>

                         <TableHead class="w-[150px] cursor-pointer" @click="sortBy('created_at')">
                            <div class="flex items-center gap-1">
                                Requested On
                                <ArrowUp v-if="sortKey === 'created_at' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'created_at' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>

                         <TableHead class="w-[150px] cursor-pointer" @click="sortBy('expected_return_date')">
                            <div class="flex items-center gap-1">
                                Expected Return
                                <ArrowUp v-if="sortKey === 'expected_return_date' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'expected_return_date' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>
                        <TableHead class="w-[200px]">
                            Reject Reason
                        </TableHead>
                        <TableHead class="w-[150px]">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow
                        v-for="(req, index) in paginatedRequests"
                        :key="req.id"
                        class="hover:bg-muted/50 transition"
                    >
                         <TableCell class="font-medium text-muted-foreground">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</TableCell>
                         <TableCell class="font-medium">
                            <Link :href="libraryRoutes.show(req.book.id).url" class="hover:underline">
                                {{ req.book.title }}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="getStatusBadgeVariant(req.borrow_status)" :class="getStatusClass(req.borrow_status)">
                                {{ req.borrow_status.toUpperCase() }}
                            </Badge>
                        </TableCell>
                        <TableCell>{{ formatDate(req.created_at) }}</TableCell>
                        <TableCell>{{ formatDate(req.expected_return_date) }}</TableCell>
                         <TableCell class="text-sm text-muted-foreground">
                            {{ req.reject_reason || '-' }}
                        </TableCell>
                         <TableCell class="">
                            <div class="flex items-center gap-2">
                            <Button
                                v-if="req.borrow_status === 'pending'"
                                variant="outline"
                                size="sm"
                                class="h-8 border-red-600 text-red-600 hover:text-red-700 hover:bg-red-50"
                                @click="handleCancel(req.id)"
                            >
                                <X class="w-3 h-3 mr-1" /> Cancel
                            </Button>
                             <span
                                v-else-if="req.borrow_status === 'rejected'"
                                class="text-blue-600 hover:text-blue-700 font-medium cursor-pointer hover:underline underline-offset-4"
                                @click="openBorrowAgain(req.book)"
                            >
                                Borrow Again
                            </span>
        
                            <Link
                                v-else-if="req.borrow_status === 'approved'"
                                href="/my-books"
                            >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 border-blue-600 text-blue-600 hover:text-blue-700 hover:bg-blue-50"
                                >
                                    <ArrowRight class="w-3 h-3 mr-1" /> Go to
                                </Button>
                            </Link>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            </div>

             <div v-if="filteredRequests.length === 0" class="text-center py-10 text-muted-foreground">
                No requests found.
            </div>

            <Pagination
                v-if="filteredRequests.length > 0"
                v-model:page="currentPage"
                :items-per-page="itemsPerPage"
                :total="filteredRequests.length"
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
                        Request to borrow: <span class="font-bold">{{ selectedBookForBorrow?.title }}</span>
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
                            v-model="borrowForm.expected_return_date"
                            class="col-span-3"
                            required
                        />
                    </div>
                        <p v-if="borrowForm.errors.expected_return_date" class="text-sm text-red-500">
                        {{ borrowForm.errors.expected_return_date }}
                    </p>
                </div>
                <DialogFooter>
                    <Button type="submit" @click="submitBorrowAgain" :disabled="borrowForm.processing">
                        Submit Request
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
