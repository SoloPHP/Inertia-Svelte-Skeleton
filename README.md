# Inertia-Svelte-Skeleton

**SoloPHP Framework Skeleton Application** for Inertia.js and Svelte, ready to start coding.

## Getting Started

Follow these steps to set up and run the application:

1. Install all Composer packages:
   ```bash
   composer install
   ```
2. Navigate to the `public` directory:
   ```bash
   cd public
   ```
3. Start the PHP server:
   ```bash
   php -S localhost:8000
   ```
4. Navigate to the `web` directory:
   ```bash
   cd web
   ```
5. Install all npm packages:
   ```bash
   npm install
   ```
6. Run the development server:
   ```bash
   npm run dev
   ```

## Building for Production

To create a production build, run:
```bash
npm run build
```

## Database Connection Configuration
To set up your database connection, you need to configure settings for both local and remote environments.
Fill in the required data for `config/settings.local.php` (for localhost) and `config/settings.php` (for remote host).

## Database Migrations
Use following command to view the list of all available commands.
```bash
php cli help
```

## Important Notes

- For the application to work properly, both the client server and the backend server must be running simultaneously.
- Styles are managed using SCSS syntax.
- The SCSS preprocessor does not require additional configuration.

---

Feel free to modify it according to your needs!