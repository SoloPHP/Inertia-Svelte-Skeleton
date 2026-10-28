<script>
    import { Link, useForm } from '@inertiajs/svelte'

    let { task, urls } = $props()

    // Precognitive, as on the task list: the title is checked against the server's rules
    // while the user types. It starts from the task as the server sent it
    const form = useForm('patch', () => task.urls.update, () => ({ title: task.title, done: task.done }))
        .setValidationTimeout(400)

    function save(event) {
        event.preventDefault()
        form.submit()
    }
</script>

<svelte:head>
    <title>Edit task</title>
</svelte:head>

<h1>Edit task</h1>

<form class="card stack" onsubmit={save} novalidate>
    <div>
        <label for="title">Title</label>
        <input
            id="title"
            bind:value={form.title}
            oninput={() => form.validate('title')}
            aria-invalid={form.errors.title ? 'true' : undefined}
            aria-describedby={form.errors.title ? 'title-error' : undefined}
        >
        {#if form.errors.title}
            <p class="field-error" id="title-error">{form.errors.title}</p>
        {:else if form.validating}
            <p class="field-hint">Checking…</p>
        {/if}
    </div>

    <label class="checkbox">
        <input type="checkbox" bind:checked={form.done}>
        Done
    </label>

    <div class="row">
        <button type="submit" disabled={form.processing || !form.isDirty}>Save</button>
        <Link href={urls.index} class="action">Cancel</Link>
    </div>
</form>
