import '../css/app.css'
import { createInertiaApp } from '@inertiajs/svelte'
import AppLayout from './Layouts/AppLayout.svelte'

// The @inertiajs/vite plugin resolves 'Tasks/Index' to ./Pages/Tasks/Index.svelte
createInertiaApp({
    layout: () => AppLayout,
    progress: { color: '#8b5cf6' },
})
