# Solo Inertia Svelte Skeleton

A starting point for a single-page application built from [SoloPHP](https://github.com/SoloPHP) packages, [Inertia.js 3](https://inertiajs.com) and [Svelte 5](https://svelte.dev).

Routing, controllers, validation and the database stay on the server, as in a classic application; the pages are Svelte components, and Inertia moves between them without full page loads. There is no API to design. The skeleton has one module, a task list with forms that are validated by the server's rules while the user types and show their errors after a redirect back, a redirect with a message, filters that reload part of the page, and props that load after the page. It runs on SQLite, so there is no database server to set up first.

## Requirements

- PHP 8.4 or later, with `pdo_sqlite` for the default database, or `pdo_mysql` / `pdo_pgsql`
- Composer
- Node.js 20.19+, 22.12+ or 24+, for Vite

## Quick start

```bash
composer create-project solophp/inertia-svelte-skeleton my-app
cd my-app
npm install
```

`create-project` copies `.env.example` to `.env`, fills in `APP_KEY` and runs the migrations. Then start PHP and Vite, each in its own terminal:

```bash
composer serve   # http://127.0.0.1:8000
npm run dev      # the Vite dev server, with hot module replacement
```

Open http://127.0.0.1:8000. While `npm run dev` runs, the pages load their scripts from Vite; without it, from the last `npm run build`.

## What is inside

| Package | Role here | Where |
|---|---|---|
| [solophp/inertia](https://solophp.github.io/Inertia/) | Pages, redirects with errors and messages, deferred and once props, the Inertia protocol | `InertiaServiceProvider`, controllers, `config/middleware.php` |
| [@inertiajs/svelte](https://inertiajs.com/docs/v3) | The client: pages, links, forms, partial reloads | `resources/js` |
| [Svelte 5](https://svelte.dev) and [Vite](https://vite.dev) | Components with runes, the dev server and the build | `resources/js`, `vite.config.js` |
| [solophp/application](https://solophp.github.io/Application/) | PSR-15 kernel: providers, middleware pipeline, routing, route cache | `bootstrap/app.php` |
| [solophp/router](https://solophp.github.io/Router/) | Routes, groups, `{id:\d+}` patterns; `UrlGenerator` makes every link and redirect from a route name | `config/routes.php`, `src/Task/` |
| [solophp/container](https://solophp.github.io/Container/) | PSR-11 container with autowiring | `src/Providers/` |
| [solophp/input](https://solophp.github.io/Input/) and [solophp/validator](https://solophp.github.io/Validator/) | Typed request DTOs and the rules behind `#[Validate]` | `src/Task/Inputs/` |
| [solophp/repository](https://solophp.github.io/Repository/) | Typed table repositories over Doctrine DBAL | `src/Task/TaskRepository.php` |
| [solophp/logger](https://github.com/SoloPHP/Logger), [solophp/dotenv](https://github.com/SoloPHP/Dotenv), [solophp/http-emitter](https://github.com/SoloPHP/HTTP-Emitter) | Logging, `.env`, sending the response | providers |

## Layout

```
bin/key                       fills APP_KEY in .env
bin/migrate                   applies database/migrations
bootstrap/app.php             builds the application; shared by the web, the CLI and the tests
config/
  providers.php               service providers, in order
  middleware.php              global middleware, outermost first
  routes.php                  every route, one line per module
database/migrations/          forward-only migrations, DBAL schema API
public/index.php              front controller, and the router of `composer serve`
resources/
  views/app.php               the HTML document of a first visit
  js/app.js                   the client's entry point
  js/Layouts/AppLayout.svelte the layout every page gets: navigation and messages
  js/Pages/                   one Svelte component per page: Home, Error, Tasks/Index, Tasks/Edit
  css/app.css
src/
  Http/
    Vite.php                  the script and style tags of the build or the dev server
    ValidationMessages.php    failed rules -> the messages a form shows
    InputValidator.php        validates a form while the user types, with its route's input class
    Middleware/               Error, Csrf, SharedProps, JsonBody
    Exceptions/               HttpException, NotFoundException
  Providers/                  Http, Logging, Database, Input, Inertia
  Task/                       one module: model, repository, inputs, controller, resource, routes
storage/                      SQLite database, logs, route cache
tests/Feature/                requests through the whole application, as the browser and the client send them
```

## How a request runs

1. The first visit is a plain browser request. The controller renders a page, such as `Tasks/Index` with its props, and the answer is `resources/views/app.php` with the page object in a script element.
2. The client mounts `resources/js/Pages/Tasks/Index.svelte` with those props. From then on, a link or a form is an XHR with `X-Inertia: true`, and the answer is the next page object as JSON.
3. A form posts JSON. The controller reads it into an input DTO: invalid input throws, and `ErrorMiddleware` redirects back to the form with the messages in its `errors` prop. Valid input is saved, and the controller redirects with a message, which the layout shows from `page.flash`.
4. While the user types, the form asks the server to validate a field with a Precognition request. `PrecognitionMiddleware`, on the task routes, checks it with the input class the route names, such as `->attribute('input', TaskStoreInput::class)`, and never runs the handler: the answer is a `204` or a `422` with the field's message.
5. `InertiaMiddleware` keeps the protocol for every response: a client built from older assets gets a `409` and reloads the page, and a redirect after a `PATCH` or `DELETE` becomes a `303`, so the browser follows it with a GET.

Errors and messages cross the redirect in a cookie signed with `APP_KEY`, so there is no session to configure.

## Adding a page

1. Add a route with a name to a module's `XxxRoutes`, and a controller method that returns `$this->inertia->render($request, $response, 'Xxx/Show', [...])`.
2. Add `resources/js/Pages/Xxx/Show.svelte`, which takes the props with `let { ... } = $props()`.
3. Pass the URLs the page needs as props, made with `UrlGenerator` from route names.
4. Add a feature test.

For a form, add an input class with `#[Validate]` rules and redirect from the handler; the errors find their way back by themselves. To validate it while the user types, name the input class on the route with `->attribute('input', ...)`, put the route under `PrecognitionMiddleware`, and make the form with `useForm(method, url, data)`. See `src/Task` and `resources/js/Pages/Tasks` for all of it.

## Configuration

| Variable | Default | |
|---|---|---|
| `APP_NAME` | `Solo` | shared with every page as `app.name` |
| `APP_ENV` | `production` | `production` caches the routes in `storage/cache/routes.php` |
| `APP_DEBUG` | `false` | exception details instead of the error page |
| `APP_KEY` | | signs the cookie of errors and messages; `composer key` makes one |
| `APP_TIMEZONE` | `UTC` | |
| `LOG_FILE`, `LOG_LEVEL` | `storage/logs/app.log`, `warning` | |
| `DB_DRIVER`, `DB_PATH` | `pdo_sqlite`, `storage/database/app.sqlite` | `pdo_mysql` and `pdo_pgsql` take `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` |

`.env.local` overrides `.env`. Variables set by the server or the shell override both.

## Tests and checks

```bash
composer test    # PHPUnit: every test boots the app on a fresh in-memory database
composer stan    # PHPStan, level max
composer cs      # PHP_CodeSniffer, PSR-12
composer check   # all three
npm run build    # the production build, into public/build
```

The tests do not need a build: they use a manifest of their own in `tests/Fixtures`.

## Production

- Run `npm run build` and deploy `public/build` with the code; set `APP_ENV=production` and `APP_DEBUG=false`.
- Point the web server's document root to `public/` and send every request for a file that does not exist to `public/index.php`.
- Delete `storage/cache/routes.php` on each deploy. The next request builds it again.
- Make `storage/` writable by PHP.

## License

MIT. See [LICENSE](LICENSE).
