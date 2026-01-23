<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { 
    BookOpen, 
    Clock, 
    CheckCircle, 
    AlertCircle, 
    FileText, 
    PlusCircle, 
    Users,
    ArrowRight,
    Bookmark,
    Activity
} from 'lucide-vue-next';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    stats: Record<string, number>;
    recentActivity: any[];
    role: string;
}>();

const user = usePage().props.auth.user;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const dashboardRoutes = {
    librarian: [
        { label: 'Manage Requests', route: '/manage-requests', icon: Clock },
        { label: 'Active Borrows', route: '/admin/active-borrows', icon: Activity },
        { label: 'Add New Book', route: '/library/create', icon: PlusCircle },
        { label: 'Browse Library', route: '/library', icon: BookOpen },
    ],
    student: [
        { label: 'Browse Library', route: '/library', icon: BookOpen },
        { label: 'My Books', route: '/my-books', icon: Bookmark },
        { label: 'My Requests', route: '/my-requests', icon: Clock },
        { label: 'Learning Files', route: '/learning-files', icon: FileText },
    ],
    teacher: [
        { label: 'Browse Library', route: '/library', icon: BookOpen },
        { label: 'My Requests', route: '/my-requests', icon: Clock },
        { label: 'Learning Files', route: '/learning-files', icon: FileText },
    ]
};

const getStatIcon = (key: string) => {
    if (key.includes('total_books')) return BookOpen;
    if (key.includes('active_borrows') || key.includes('my_active_books')) return Bookmark;
    if (key.includes('pending')) return Clock;
    if (key.includes('overdue')) return AlertCircle;
    if (key.includes('file')) return FileText;
    if (key.includes('returned')) return CheckCircle;
    return BookOpen;
};

const getStatLink = (key: string) => {
    if (key === 'total_books') return '/library';
    if (key === 'pending_requests') return '/manage-requests';
    if (key === 'active_borrows') return '/admin/active-borrows?filter=';
    if (key === 'overdue_books') return '/admin/active-borrows?filter=overdue';
    
    if (key === 'my_pending_requests') return '/my-requests';
    if (key === 'my_active_books') return '/my-books';
    
    if (key === 'uploaded_files' || key === 'learning_files') return '/learning-files';
    
    return '/dashboard';
};

const formatStatLabel = (key: string) => {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4">
            
            <!-- Welcome Section -->
            <div class="flex flex-col gap-2">
                <h1 class="text-3xl font-bold tracking-tight">Welcome back, {{ user.name }}!</h1>
                <p class="text-muted-foreground">
                    Here is an overview of your activity and quick actions.
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                 <Link 
                    v-for="(value, key) in stats" 
                    :key="key" 
                    :href="getStatLink(String(key))"
                    class="block transition-transform hover:scale-[1.02] cursor-pointer"
                >
                    <Card class="h-full hover:bg-slate-50 dark:hover:bg-slate-900">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium text-muted-foreground capitalize">
                                {{ formatStatLabel(String(key)) }}
                            </CardTitle>
                            <component :is="getStatIcon(String(key))" class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ value }}</div>
                             <p class="text-xs text-muted-foreground mt-1">
                                Click to view details
                            </p>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                
                <!-- Recent Activity -->
                <Card class="col-span-4">
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                        <CardDescription>
                            Your latest interactions with the system.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentActivity.length">
                            <div v-for="(item, index) in recentActivity" :key="index" class="mb-4 grid grid-cols-[25px_1fr] items-start pb-4 last:mb-0 last:pb-0">
                                <span class="flex h-2 w-2 translate-y-1 rounded-full bg-sky-500" />
                                <div class="space-y-1">
                                    <p class="text-sm font-medium leading-none">
                                        <span v-if="props.role === 'librarian'">
                                            <span v-if="item.status === 'pending'">
                                                <strong>{{ item.user }}</strong> requested <strong>{{ item.book }}</strong>
                                            </span>
                                            <span v-else>
                                                Request for <strong>{{ item.book }}</strong> by <strong>{{ item.user }}</strong> was {{ item.status }}
                                            </span>
                                        </span>
                                        <span v-else-if="props.role === 'teacher'">
                                            <span v-if="item.type === 'upload'">
                                                Uploaded <strong>{{ item.title }}</strong> for Grade {{ item.grade }}
                                            </span>
                                             <span v-else>
                                                Requested <strong>{{ item.title || 'Book' }}</strong>
                                            </span>
                                        </span>
                                        <span v-else>
                                            Requested <strong>{{ item.book }}</strong> (Status: {{ item.status }})
                                        </span>
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ item.date }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-muted-foreground text-center py-4">
                            No recent activity found.
                        </div>
                    </CardContent>
                </Card>

                <!-- Quick Actions -->
                <Card class="col-span-3">
                    <CardHeader>
                        <CardTitle>Quick Access</CardTitle>
                         <CardDescription>
                            Jump to frequent tasks.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4">
                        <Link 
                            v-for="(action, index) in dashboardRoutes[role as keyof typeof dashboardRoutes] || []" 
                            :key="index"
                            :href="action.route"
                        >
                            <Button variant="outline" class="w-full justify-start cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800">
                                <component :is="action.icon" class="mr-2 h-4 w-4" />
                                {{ action.label }}
                                <ArrowRight class="ml-auto h-4 w-4 opacity-50" />
                            </Button>
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
