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
import { Bell, ArrowUp, ArrowDown, CheckCircle, XCircle, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { Badge } from '@/components/ui/badge';
import { toast } from 'vue-sonner';
import borrowRequests from '@/routes/borrow-requests';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger
} from '@/components/ui/tooltip'
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';

interface User {
    id: number;
    name: string;
}

interface Book {
    id: number;
    title: string;
    book_code: string;
}

interface BorrowRequest {
    id: number;
    book: Book;
    user: User;
    borrow_status: 'pending' | 'approved' | 'rejected';
    expected_return_date: string;
    created_at: string;
}

const props = defineProps<{
    requests: BorrowRequest[];
}>();

const page = usePage<PagePropsWithFlash>();
const flashMessage = page.props.flash;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manage Requests', href: '#' },
];

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

type SortKey = 'book.title' | 'user.name' | 'created_at' | 'expected_return_date';
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
        return !searchQuery.value ||
            req.book.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            req.user.name.toLowerCase().includes(searchQuery.value.toLowerCase());
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

const form = useForm({});

import { useConfirmDialog } from '@/composables/useConfirmDialog';

const { confirm } = useConfirmDialog();

const handleApprove = async (id: number) => {
    const ok = await confirm({
        title: 'Approve Request?',
        description: 'Are you sure you want to approve this borrow request?',
        confirmText: 'Approve',
    });

    if (!ok) return;

    form.post(borrowRequests.approve(id).url, {
        onSuccess: () => {
             toast.success('Request approved');
        }
    });
};

const showRejectDialog = ref(false);
const selectedRequestId = ref<number | null>(null);
const rejectReason = ref('');
const rejectForm = useForm({
    reason: '',
});

const openRejectDialog = (id: number) => {
    selectedRequestId.value = id;
    rejectForm.reason = '';
    showRejectDialog.value = true;
};

const submitReject = () => {
    if (!selectedRequestId.value) return;
    
    rejectForm.post(borrowRequests.rejectReason(selectedRequestId.value).url, {
        onSuccess: () => {
            showRejectDialog.value = false;
            rejectForm.reset();
            toast.success('Request rejected');
        }
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const columnSizes = {
    user: 'min-w-[150px]',
    title: 'min-w-[200px]',
    created: 'w-[150px]',
    return: 'w-[150px]',
    action: 'w-[150px]',
} as const;
</script>

<template>
    <Head title="Manage Requests" />

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
                     <h2 class="text-2xl font-bold tracking-tight">Manage Requests</h2>
                     <p class="text-muted-foreground">
                        Approve or reject book borrow requests.
                    </p>
                </div>
            </div>

            <!-- Search -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex gap-2">
                     <div class="relative w-64 items-center">
                        <Input
                            type="text"
                            placeholder="Search user or book..."
                            v-model="searchQuery"
                            class="pl-10"
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
                        <TableHead :class="['cursor-pointer', columnSizes.user]" @click="sortBy('user.name')">
                            <div class="flex items-center gap-1">
                                User
                                <ArrowUp v-if="sortKey === 'user.name' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'user.name' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>

                        <TableHead :class="['cursor-pointer', columnSizes.title]" @click="sortBy('book.title')">
                            <div class="flex items-center gap-1">
                                Book Title
                                <ArrowUp v-if="sortKey === 'book.title' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'book.title' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>

                        <TableHead :class="['cursor-pointer', columnSizes.created]" @click="sortBy('created_at')">
                            <div class="flex items-center gap-1">
                                Requested On
                                <ArrowUp v-if="sortKey === 'created_at' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'created_at' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>

                        <TableHead :class="['cursor-pointer', columnSizes.return]" @click="sortBy('expected_return_date')">
                             <div class="flex items-center gap-1">
                                Exp. Return
                                <ArrowUp v-if="sortKey === 'expected_return_date' && sortDirection === 'asc'" class="h-4 w-4" />
                                <ArrowDown v-if="sortKey === 'expected_return_date' && sortDirection === 'desc'" class="h-4 w-4" />
                            </div>
                        </TableHead>

                        <TableHead :class="['text-left', columnSizes.action]">Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow
                        v-for="(req, index) in paginatedRequests"
                        :key="req.id"
                        class="hover:bg-muted/50 transition"
                    >
                         <TableCell class="font-medium text-muted-foreground">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</TableCell>
                        <TableCell class="font-medium">{{ req.user.name }}</TableCell>
                        <TableCell>{{ req.book.title }}</TableCell>
                        <TableCell>{{ formatDate(req.created_at) }}</TableCell>
                        <TableCell>{{ formatDate(req.expected_return_date) }}</TableCell>
                        <TableCell class="space-x-2">
                            <div class="flex items-center gap-2">
                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                size="sm"
                                                class="bg-green-600 hover:bg-green-700 cursor-pointer h-8 w-8 p-0"
                                                @click="handleApprove(req.id)"
                                                :disabled="form.processing"
                                            >
                                                <CheckCircle class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Approve Request</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>

                                <TooltipProvider>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                class="cursor-pointer h-8 w-8 p-0"
                                                @click="openRejectDialog(req.id)"
                                                :disabled="form.processing"
                                            >
                                                <XCircle class="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Reject Request</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            </div>

            <Dialog v-model:open="showRejectDialog">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Reject Borrow Request</DialogTitle>
                        <DialogDescription>
                            Please provide a reason for rejecting this request.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid w-full gap-1.5">
                            <Label for="reason">Reason</Label>
                            <Textarea 
                                id="reason" 
                                v-model="rejectForm.reason" 
                                placeholder="e.g. Book is damaged, User has overdue books..."
                            />
                            <p v-if="rejectForm.errors.reason" class="text-sm text-red-500">
                                {{ rejectForm.errors.reason }}
                            </p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="showRejectDialog = false">Cancel</Button>
                        <Button 
                            variant="destructive" 
                            @click="submitReject"
                            :disabled="rejectForm.processing"
                        >
                            Confirm Reject
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div v-if="filteredRequests.length === 0" class="text-center py-10 text-muted-foreground">
                No pending requests found.
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
    </AppLayout>
</template>
