<script setup lang="ts">
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, Sparkles, Quote, ArrowRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();
</script>

<template>
  <Head title="Welcome to Libratech" />

  <div class="relative min-h-screen w-full overflow-hidden bg-[#0a0a0a] text-white selection:bg-white selection:text-black">
    
    <!-- Subtle Background Effects (Monochrome/Cool) -->
    <div class="absolute inset-0 z-0">
        <!-- Deep Glow -->
        <div class="absolute top-[50%] left-[50%] -translate-x-1/2 -translate-y-1/2 w-[70%] h-[70%] rounded-full bg-white/5 blur-[150px]"></div>
         <!-- Grid Pattern -->
        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 brightness-100 contrast-150 mix-blend-overlay"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 text-center">
        
        <div class="flex-1 flex flex-col items-center justify-center w-full max-w-2xl space-y-12">
            
            <!-- 1. Center Icon and Libratech -->
            <div class="flex flex-col items-center gap-4 animate-fade-in-up">
                <div class="p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-sm shadow-2xl">
                    <BookOpen class="h-10 w-10 text-white" />
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-white font-serif">
                    Libratech
                </h1>
            </div>

            <!-- 2. Small Short Quote -->
            <div class="max-w-md mx-auto animate-fade-in-up delay-200">
                <div class="relative">
                    <Quote class="absolute -top-3 -left-4 h-4 w-4 text-white/30" />
                    <p class="text-lg text-gray-400 font-serif italic text-center">
                        "A room without books is like a body without a soul."
                    </p>
                </div>
            </div>

            <!-- 3. Enter Library Button -->
            <div class="animate-fade-in-up delay-400">
                <template v-if="!$page.props.auth.user">
                    <Link :href="login()">
                        <Button size="lg" class="h-12 px-10 rounded-full bg-white text-black hover:bg-gray-200 cursor-pointer text-base font-semibold shadow-[0_0_20px_rgba(255,255,255,0.2)] hover:shadow-[0_0_35px_rgba(255,255,255,0.4)] transition-all duration-500">
                            Enter Library
                        </Button>
                    </Link>
                </template>
                 <template v-else>
                     <Link :href="dashboard()">
                        <Button size="lg" class="h-12 px-10 rounded-full bg-white text-black hover:bg-gray-200 cursor-pointer text-base font-semibold shadow-glow transition-all">
                            Enter Library
                        </Button>
                    </Link>
                </template>
            </div>

        </div>

        <!-- 4. Footer -->
        <footer class="pb-8 text-xs text-gray-600 tracking-widest uppercase animate-fade-in">
            &copy; 2026 Libratech
        </footer>
    </div>
  </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 1.5s ease-out forwards;
    opacity: 0;
}
.animate-fade-in-up {
    animation: fadeInUp 1s ease-out forwards;
    opacity: 0;
    transform: translateY(20px);
}
.delay-200 { animation-delay: 200ms; }
.delay-400 { animation-delay: 400ms; }

@keyframes fadeIn {
    to { opacity: 1; }
}

@keyframes fadeInUp {
    to { opacity: 1; transform: translateY(0); }
}
</style>
