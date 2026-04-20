# Vercel Deployment

This repository deploys to Vercel as a static public showcase. The Laravel app is used locally to render the pages, then the generated HTML in `public/static` is deployed.

The repository includes:

- `public/static`, pre-rendered HTML for the public showcase pages.
- `vercel.json`, file-based Vercel project settings that force the **Other** framework preset, skip dependency installs/builds, serve `public` as the output directory, and map clean URLs to the generated static HTML.
- `scripts/export-static.ps1`, a local helper for regenerating the static HTML from Laravel.
- `.vercelignore`, which uploads only `public` and `vercel.json` to Vercel so PHP/Composer is never bundled.

After pushing these files, redeploy the project in Vercel. If Vercel asks for a framework preset, choose **Other**. Do not add a custom build command or install command; `vercel.json` already tells Vercel to deploy the `public` directory as static files.

To regenerate the static showcase locally after changing Laravel pages or seeded content:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/export-static.ps1
```

Vercel's free serverless function size limit is too small for this image-heavy Laravel project, so the deployed Vercel site is static. Admin pages, form submissions, PDF generation, uploads, and database edits require a PHP host or an external database/storage architecture.
