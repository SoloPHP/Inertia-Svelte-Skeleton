<script>
    import { Link, page } from '@inertiajs/svelte'

    let { status } = $props()

    const titles = {
        400: 'Bad request',
        404: 'Page not found',
        405: 'Method not allowed',
        500: 'Server error',
    }
    const descriptions = {
        400: 'The request could not be understood.',
        404: 'The page you are looking for does not exist.',
        405: 'This page does not accept that kind of request.',
        500: 'Something went wrong on our side. Please try again later.',
    }
</script>

<svelte:head>
    <title>{status}: {titles[status] ?? 'Error'}</title>
</svelte:head>

<section class="error-page">
    <p class="status">{status}</p>
    <h1>{titles[status] ?? 'Something went wrong'}</h1>
    <p class="muted">{descriptions[status] ?? 'Please try again later.'}</p>
    <Link href={page.props.nav.home} class="button">Back home</Link>
</section>
