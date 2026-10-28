<script>
    import { Link } from '@inertiajs/svelte'

    let { tasksUrl, php } = $props()

    const features = [
        ['Pages are Svelte components', 'A controller renders "Tasks/Index"; the client shows resources/js/Pages/Tasks/Index.svelte with the props it was given.'],
        ['No API to write', 'Routes, controllers, validation and the database stay on the server, as in a classic application.'],
        ['Forms redirect', 'A form posts, the server redirects, and errors or a message reach the next page.'],
        ['Props load lazily', 'Partial reloads fetch only the props they ask for, and deferred props load right after the page.'],
    ]
</script>

<svelte:head>
    <title>Welcome</title>
</svelte:head>

<section class="hero">
    <h1>Solo, Inertia and Svelte</h1>
    <p class="lead">Server-side routing and controllers with a single-page front end.</p>
    <Link href={tasksUrl} class="button">Open the task list</Link>
</section>

<section class="features">
    {#each features as [title, text] (title)}
        <article class="card">
            <h2>{title}</h2>
            <p>{text}</p>
        </article>
    {/each}
</section>

<p class="muted center">PHP {php} · Svelte 5 · Inertia 3</p>
