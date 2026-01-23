<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { useForm } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = defineProps<{
  status?: string;
  canResetPassword: boolean;
  canRegister: boolean;
}>();

// Inertia form for login
const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    preserveScroll: true,
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <AuthBase
    title="Log in to your account"
    description="Enter your email and password below to log in"
  >
    <Head title="Log in" />

    <div v-if="props.status" class="mb-4 text-center text-sm font-medium text-green-600">
      {{ props.status }}
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-6">
      <div class="grid gap-6">
        <div class="grid gap-2">
          <Label for="email">Email address</Label>
          <Input 
            id="email" 
            name="email" 
            type="email" 
            v-model="form.email" 
            required 
            autofocus 
            autocomplete="email" 
            placeholder="email@libratech.com" 
           />
          <InputError :message="form.errors.email" />
        </div>

        <div class="grid gap-2">
          <div class="flex items-center justify-between">
            <Label for="password">Password</Label>
            
            <Dialog>
                <DialogTrigger as-child>
                    <button type="button" class="text-sm font-medium text-muted-foreground hover:text-primary hover:underline cursor-pointer">
                        Forgot password?
                    </button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reset Password</DialogTitle>
                        <DialogDescription>
                            To reset your password, please contact the <strong>Librarian</strong> or a <strong>Teacher</strong>.
                            <br><br>
                            They can update your credentials via the User Management dashboard.
                        </DialogDescription>
                    </DialogHeader>
                </DialogContent>
            </Dialog>
          </div>
          <Input 
            id="password" 
            name="password" 
            type="password" 
            v-model="form.password" 
            required 
            autocomplete="current-password" 
            placeholder="Password" 
           />
          <InputError :message="form.errors.password" />
        </div>

        <div class="flex items-center space-x-2">
          <Checkbox id="remember" v-model:checked="form.remember" />
          <Label for="remember">Remember me</Label>
        </div>

        <Button type="submit" :disabled="form.processing" class="w-full">
          <Spinner v-if="form.processing" />
          Log in
        </Button>
      </div>

      <div class="text-center text-sm text-muted-foreground">
        Don't have an account?
        <Dialog>
            <DialogTrigger as-child>
                <button type="button" class="font-medium text-muted-foreground hover:text-primary hover:underline ml-1 cursor-pointer">
                    Sign up
                </button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Create Account</DialogTitle>
                    <DialogDescription>
                        Registration is managed by the school administration.
                        <br><br>
                        Please visit the <strong>Library</strong> or ask your <strong>Teacher</strong> to create an account for you.
                    </DialogDescription>
                </DialogHeader>
            </DialogContent>
        </Dialog>
      </div>
    </form>
  </AuthBase>
</template>
