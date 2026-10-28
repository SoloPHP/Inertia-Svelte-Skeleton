<script>
    import { Deferred, Link, router, useForm } from '@inertiajs/svelte'
    import { untrack } from 'svelte'

    let { tasks, filters, stats, urls } = $props()

    // After an action the server redirects back here: reload only what it changes, with the stats in
    // the same response rather than in a deferred request of their own
    const reload = { only: ['tasks', 'stats'], preserveScroll: true }

    // A method and a URL make the form precognitive: form.validate('title') checks the field
    // against the server's rules while the user types, without saving anything. On submit,
    // a failed post brings the server's messages back to form.errors too
    const form = useForm('post', () => urls.store, { title: '' }).setValidationTimeout(400)

    function create(event) {
        event.preventDefault()
        form.submit({ ...reload, onSuccess: () => form.reset() })
    }

    // Filters are a partial reload: the server resolves only these props, not the deferred stats.
    // The inputs start from the filters of the URL, then keep what the user types
    let q = $state(untrack(() => filters.q))
    let done = $state(untrack(() => filters.done))
    let timer

    // A search still waiting when the user leaves the page must not bring them back to it
    $effect(() => () => clearTimeout(timer))

    function filter() {
        clearTimeout(timer)
        timer = setTimeout(() => {
            const query = Object.fromEntries(Object.entries({ q, done }).filter(([, value]) => value !== ''))
            router.get(urls.index, query, {
                only: ['tasks', 'filters'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            })
        }, 250)
    }

    function toggle(task) {
        router.patch(task.urls.toggle, {}, reload)
    }

    function destroy(task) {
        if (confirm(`Delete "${task.title}"?`)) {
            router.delete(task.urls.destroy, reload)
        }
    }
</script>

<svelte:head>
    <title>Tasks</title>
</svelte:head>

<div class="page-head">
    <h1>Tasks</h1>
    <Deferred data="stats">
        {#snippet fallback()}
            <span class="muted">Counting…</span>
        {/snippet}
        <span class="muted">{stats.done} of {stats.total} done</span>
    </Deferred>
</div>

<form class="card" onsubmit={create} novalidate>
    <label for="title">New task</label>
    <div class="row">
        <input
            id="title"
            bind:value={form.title}
            oninput={() => form.validate('title')}
            placeholder="What needs doing?"
            aria-invalid={form.errors.title ? 'true' : undefined}
            aria-describedby={form.errors.title ? 'title-error' : undefined}
        >
        <button type="submit" disabled={form.processing}>Add</button>
    </div>
    {#if form.errors.title}
        <p class="field-error" id="title-error">{form.errors.title}</p>
    {:else if form.validating}
        <p class="field-hint">Checking…</p>
    {/if}
</form>

<div class="filters">
    <input
        type="search"
        maxlength="100"
        placeholder="Search tasks"
        aria-label="Search tasks"
        bind:value={q}
        oninput={filter}
    >
    <select aria-label="Show" bind:value={done} onchange={filter}>
        <option value="">All</option>
        <option value="0">To do</option>
        <option value="1">Done</option>
    </select>
</div>

{#if tasks.data.length === 0}
    <p class="empty">{filters.q || filters.done ? 'No tasks match.' : 'No tasks yet: add the first one above.'}</p>
{:else}
    <ul class="card tasks">
        {#each tasks.data as task (task.id)}
            <li class:done={task.done}>
                <input type="checkbox" checked={task.done} onchange={() => toggle(task)} aria-label="Done: {task.title}">
                <span class="task-title">{task.title}</span>
                <Link href={task.urls.edit} class="action">Edit</Link>
                <button type="button" class="action danger" onclick={() => destroy(task)}>Delete</button>
            </li>
        {/each}
    </ul>
{/if}

{#if tasks.pages > 1}
    <nav class="pagination" aria-label="Pages">
        {#if tasks.previous}
            <Link href={tasks.previous} preserveScroll>← Previous</Link>
        {:else}
            <span></span>
        {/if}
        <span class="muted">Page {tasks.page} of {tasks.pages}</span>
        {#if tasks.next}
            <Link href={tasks.next} preserveScroll>Next →</Link>
        {:else}
            <span></span>
        {/if}
    </nav>
{/if}
