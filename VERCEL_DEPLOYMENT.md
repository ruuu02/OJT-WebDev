# Vercel Deployment

This Laravel project needs PHP runtime configuration on Vercel. Without it, Vercel serves `public/index.php` as a static file and the browser downloads it.

The repository includes:

- `vercel.json` to enable the community PHP runtime, route public assets, and send app routes to Laravel.
- `api/index.php` as the Vercel serverless entry point.
- A build command that creates and seeds `database/database.sqlite` for showcase data.

After pushing these files, redeploy the project in Vercel. If Vercel asks for a framework preset, choose **Other**.

For a public showcase, the included defaults are enough. For a real production site, set these in Vercel Project Settings -> Environment Variables instead of relying on the fallback values:

```text
APP_KEY=base64:your-generated-key
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_DATABASE=your-database-name
DB_USERNAME=your-database-user
DB_PASSWORD=your-database-password
```

Vercel's filesystem is serverless, so admin uploads and persistent database edits need an external database/storage service. The free deployment is best for showcasing the public pages.
