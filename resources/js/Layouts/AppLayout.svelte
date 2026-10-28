<script>
    import { Link, page } from '@inertiajs/svelte'

    let { children } = $props()

    // "/" is active on the home page only; any other link also below its path, as /tasks/1/edit
    function active(url) {
        const path = page.url.split('?')[0]
        return url === '/' ? path === '/' : path === url || path.startsWith(`${url}/`)
    }
</script>

<header class="topbar">
    <div class="container topbar-inner">
        <Link href={page.props.nav.home} class="brand">{page.props.app.name}</Link>
        <nav>
            {#each page.props.nav.links as item (item.url)}
                <Link href={item.url} class={active(item.url) ? 'active' : ''}>{item.label}</Link>
            {/each}
        </nav>
    </div>
</header>

<main class="container">
    {#if page.flash.success}
        <p class="flash success" role="status">{page.flash.success}</p>
    {/if}
    {#if page.flash.error}
        <p class="flash error" role="alert">{page.flash.error}</p>
    {/if}

    {@render children()}
</main>
