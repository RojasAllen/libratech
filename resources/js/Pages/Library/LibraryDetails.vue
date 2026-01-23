<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import libraryRoutes from '@/routes/library';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { toast } from 'vue-sonner';
import { BookOpen, Calendar, User as UserIcon, CheckCircle2, XCircle, Clock, Hash, GraduationCap, Bookmark, FileText, Pencil, Trash } from 'lucide-vue-next';

interface Book {
  id: number
  book_code: string
  title: string
  author: string
  subject: string
  description?: string
  grade_level: number
  competency?: string
  type: string
  file_path?: string
  is_available: boolean
  current_user_request_status: string | null
  current_user_has_book: boolean
}

interface User {
  id: number;
  name: string;
}

interface BorrowLog {
  id: number;
  user: User;
  borrowed_at: string;
  returned_at: string | null;
}

const props = defineProps<{
  book: Book;
  borrowHistory: BorrowLog[];
}>()

const page = usePage()

// current user role
const role = computed(() => page.props.auth.user.role)

// breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Library', href: libraryRoutes.index().url },
  { title: props.book.title, href: libraryRoutes.show(props.book.id).url },
]

const form = useForm({
  expected_return_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0] // default 1 week
})

const { confirm } = useConfirmDialog();

const handleReturn = async () => {
     const ok = await confirm({
        title: 'Return Book?',
        description: 'Are you sure you want to return this book to the library?',
        confirmText: 'Return Book',
    });

    if (!ok) return;

    form.post(libraryRoutes.return(props.book.id).url, {
        onSuccess: () => {
             toast.success('Book returned successfully');
        }
    });
};

const showBorrowDialog = ref(false);

const handleBorrow = () => {
    form.post(libraryRoutes.borrow(props.book.id).url, {
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

const handleDeleteBook = async () => {
    const ok = await confirm({
        title: 'Delete Book?',
        description: 'This will permanently remove the book. This action cannot be undone.',
        confirmText: 'Delete',
        variant: 'destructive',
    });

    if (!ok) return;

    router.delete(libraryRoutes.destroy(props.book.id), {
        onSuccess: () => {
             toast.success('Book deleted successfully');
             // Router will likely redirect to index due to controller logic, or we rely on Inertia
        }
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
  <Head :title="book.title" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-4 py-6 md:px-8 space-y-8">
      
      <!-- Main Book Card -->
      <Card class="shadow-lg">
        <CardHeader class="pb-4">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">

                        <Badge :variant="book.is_available ? 'success' : 'destructive'" class="capitalize" :class="book.is_available ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200'">
                           {{ book.is_available ? 'Available' : 'Unavailable' }}
                        </Badge>
                    </div>
                    <CardTitle class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100 mt-2">
                        {{ book.title }}
                    </CardTitle>
                    <CardDescription class="text-lg font-medium text-blue-600">
                        by {{ book.author }}
                    </CardDescription>
                </div>
                
                <!-- Status Actions (Top Right) -->
                 <div class="flex items-center gap-3">
                     <template v-if="['student', 'teacher'].includes(role)">
                        <div v-if="book.current_user_has_book">
                             <Button 
                                v-if="book.current_user_has_book"
                                variant="outline"
                                class="gap-2 border-blue-600 text-blue-600 hover:bg-blue-50"
                                @click="handleReturn"
                            >
                                <BookOpenCheck class="h-4 w-4" />
                                Return Book
                            </Button>
                        </div>
                        <div v-else-if="book.current_user_request_status === 'pending'">
                             <Badge variant="secondary" class="px-4 py-2 text-sm bg-yellow-100 text-yellow-800">
                                <Clock class="w-4 h-4 mr-2" />
                                Pending Review
                             </Badge>
                        </div>
                         <div v-else-if="!book.is_available">
                             <Badge variant="outline" class="px-4 py-2 text-sm border-red-200 text-red-600">
                                <XCircle class="w-4 h-4 mr-2" />
                                Unavailable
                             </Badge>
                        </div>
                        <Dialog v-else v-model:open="showBorrowDialog">
                            <DialogTrigger as-child>
                                <Button variant="outline" class="border-green-600 text-green-600 hover:bg-green-50 shadow-sm transition-all cursor-pointer">
                                    <CheckCircle2 class="w-4 h-4 mr-2" />
                                    Borrow
                                </Button>
                            </DialogTrigger>
                            <DialogContent class="sm:max-w-[425px]">
                                <DialogHeader>
                                    <DialogTitle>Borrow Request</DialogTitle>
                                    <DialogDescription>
                                        Set an expected return date.
                                    </DialogDescription>
                                </DialogHeader>
                                <div class="grid gap-4 py-4">
                                     <div class="bg-muted/50 p-3 rounded-md space-y-1">
                                        <p class="font-medium text-sm">{{ book.title }}</p>
                                        <p class="text-xs text-muted-foreground">{{ book.author }} • {{ book.book_code }}</p>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label for="return_date">Expected Return Date</Label>
                                        <Input
                                            id="return_date"
                                            type="date"
                                            v-model="form.expected_return_date"
                                            required
                                        />
                                        <p v-if="form.errors.expected_return_date" class="text-sm text-red-500">
                                            {{ form.errors.expected_return_date }}
                                        </p>
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button type="submit" @click="handleBorrow" :disabled="form.processing">
                                        Submit Request
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </template>
                    
                    <!-- Librarian Actions -->
                     <template v-if="role === 'librarian'">
                        <div class="flex gap-2">
                              <Link :href="libraryRoutes.edit(book.id)">
                                <Button variant="outline" size="sm" class="text-blue-600 border-blue-200 hover:bg-blue-50 hover:text-blue-700 cursor-pointer">
                                    <Pencil class="mr-2 h-4 w-4" />
                                    Edit
                                </Button>
                              </Link>
                              <Button variant="outline" size="sm" class="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700 cursor-pointer" @click="handleDeleteBook">
                                  <Trash class="mr-2 h-4 w-4" />
                                  Delete
                              </Button>
                        </div>
                    </template>
                 </div>
            </div>
        </CardHeader>
        
        <Separator class="my-2" />

        <CardContent class="grid md:grid-cols-3 gap-8 pt-6">
            <!-- Metadata Column -->
            <div class="space-y-6 md:col-span-1 border-r pr-6">
                <div>
                     <h4 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3">Details</h4>
                     <div class="space-y-4">
                        <div class="flex items-center gap-3 text-sm">
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-2 rounded-md text-blue-600 dark:text-blue-400">
                                <Hash class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Book Code</p>
                                <p class="font-medium">{{ book.book_code }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <div class="bg-purple-50 dark:bg-purple-900/20 p-2 rounded-md text-purple-600 dark:text-purple-400">
                                <GraduationCap class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Grade Level</p>
                                <p class="font-medium">{{ book.grade_level }}</p>
                            </div>
                        </div>
                         <div class="flex items-center gap-3 text-sm">
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-2 rounded-md text-indigo-600 dark:text-indigo-400">
                                <Bookmark class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Subject</p>
                                <p class="font-medium">{{ book.subject }}</p>
                            </div>
                        </div>
                         <div v-if="book.competency" class="flex items-center gap-3 text-sm">
                            <div class="bg-orange-50 dark:bg-orange-900/20 p-2 rounded-md text-orange-600 dark:text-orange-400">
                                <BookOpen class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Competency</p>
                                <p class="font-medium">{{ book.competency }}</p>
                            </div>
                        </div>

                     </div>
                </div>
            </div>

            <!-- Description Column -->
            <div class="md:col-span-2 space-y-4">
                 <h4 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider">Synopsis</h4>
                 <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 leading-relaxed">
                    <p v-if="book.description">{{ book.description }}</p>
                    <p v-else class="italic text-muted-foreground">No description available.</p>
                 </div>
            </div>
        </CardContent>
      </Card>

      <!-- Borrow History -->
      <Card>
        <CardHeader>
            <CardTitle class="text-lg flex items-center gap-2">
                <Clock class="w-5 h-5 text-muted-foreground" />
                Borrow History
            </CardTitle>
        </CardHeader>
        <CardContent>
             <Table v-if="borrowHistory.length > 0">
                <TableHeader>
                    <TableRow class="bg-muted/50">
                       <TableHead>User</TableHead>
                       <TableHead>Borrowed</TableHead>
                       <TableHead>Returned</TableHead>
                       <TableHead class="text-right">Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="log in borrowHistory" :key="log.id" class="hover:bg-muted/50 transition-colors">
                        <TableCell class="font-medium">
                            <div class="flex items-center gap-2">
                                <div class="bg-gray-100 p-1 rounded-full"><UserIcon class="w-3 h-3 text-gray-500" /></div>
                                {{ log.user.name }}
                            </div>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ formatDate(log.borrowed_at) }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ log.returned_at ? formatDate(log.returned_at) : '-' }}</TableCell>
                        <TableCell class="text-right">
                            <Badge v-if="log.returned_at" variant="outline" class="bg-green-50 text-green-700 border-green-200">
                                 Returned
                            </Badge>
                            <Badge v-else variant="outline" class="bg-yellow-50 text-yellow-700 border-yellow-200">
                                Active
                            </Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <div v-else class="text-center py-10 text-muted-foreground bg-muted/20 rounded-lg border-dashed border-2">
                <p>No confirmed borrow history records found.</p>
            </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>
