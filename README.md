# Commerce AI Feed Validator

Commerce AI Feed Validator is a Laravel app for uploading product feeds, finding feed-quality issues, applying Gemini-powered fixes, and exporting cleaned CSV files.

The app supports Google Merchant-style product feed validation and includes Magento-aware export handling for product import CSVs.

## Features

- GitHub OAuth login with Laravel Socialite
- CSV, TSV, TXT, and XML feed uploads
- Background feed processing through Laravel queues
- Product row validation for required fields, URLs, availability, price, identifiers, category, and condition
- Gemini AI fix suggestions per invalid row
- Apply AI fixes and revalidate rows in place
- Export cleaned CSV data
- Magento product import support for numeric `price` export values

## Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- SQLite, MySQL, or another Laravel-supported database
- Gemini API key for AI suggestions
- GitHub OAuth app credentials for login

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

For local development with Laravel's built-in server, queue listener, logs, and Vite:

```bash
composer run dev
```

If you are using Laragon, point your local site to the project root and make sure the document root resolves to `public/`.

## Environment

Configure these values in `.env`:

```env
APP_NAME="Commerce AI"
APP_URL=http://laravelfeed.test

DB_CONNECTION=sqlite

GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GITHUB_REDIRECT_URI=http://laravelfeed.test/auth/github/callback

GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash

QUEUE_CONNECTION=database
```

If using SQLite, create the database file before migrating:

```bash
type nul > database\database.sqlite
php artisan migrate
```

For queued processing, keep a worker running:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

## Usage

1. Open the app in your browser.
2. Sign in with GitHub.
3. Go to `My Feeds`.
4. Click `Upload Feed`.
5. Upload a CSV, TSV, TXT, or XML feed.
6. Wait for processing to finish.
7. Review rows with validation issues.
8. Click `Suggest Fix` to request a Gemini correction.
9. Review the suggested JSON and click `Apply AI Fix`.
10. Export the cleaned CSV when all rows are valid.

## Validation Rules

The validator checks:

- Required fields: `id`, `title`, `description`, `link`, `image_link`, `availability`, `price`, and `brand`
- URL format and HTTPS usage
- Image URL extension
- Accepted availability values
- Price format
- Product ID spacing and length
- Missing GTIN or MPN
- Missing Google product category
- Missing condition

Magento import rows are detected by the presence of columns such as `sku`, `attribute_set_code`, `product_type`, and `product_websites`.

## Magento Import Notes

Magento product import expects the `price` column to be numeric, for example:

```csv
price
14.00
```

Google Merchant feeds commonly use currency-suffixed prices:

```csv
price
14.00 USD
```

When exporting Magento-style rows, this app normalizes `price` values by removing currency suffixes, so Magento can import them.

## AI Fix Behavior

Gemini suggestions are stored on each row and can be reviewed before applying. After applying a fix:

- The row is revalidated
- Remaining issues are updated
- Valid rows turn green
- Rows with no issues show `All fixed`
- Feed counts and health score update in the UI

If Gemini returns a rate limit or temporary service error, the UI starts an automatic retry countdown. Invalid API key errors are shown as final errors.

## Testing

Run the full test suite:

```bash
php artisan test
```

Run a focused test:

```bash
php artisan test --filter=AiFixApplyTest
```

## Important Files

- `app/Services/FeedValidatorService.php` - feed validation rules
- `app/Services/GeminiService.php` - Gemini request and response handling
- `app/Http/Controllers/FeedController.php` - upload, status, export, and delete actions
- `app/Http/Controllers/AiFixController.php` - AI suggest/apply/manual fix actions
- `app/Jobs/ProcessFeedJob.php` - background feed parsing and validation
- `resources/views/dashboard.blade.php` - feed list and upload modal
- `resources/views/feeds/show.blade.php` - row review and AI fix UI

## Troubleshooting

### Upload button does not open

Hard refresh the dashboard so the latest Blade output is loaded. The top-right upload button dispatches a browser event that opens the dashboard upload modal.

### Feed stays in pending or processing

Start the queue worker:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

### Gemini fix does not return

Check:

- `GEMINI_API_KEY` is set
- Gemini quota is available
- `storage/logs/laravel.log` for API errors

### Magento says price is invalid

Use a cleaned export generated after the Magento price normalization change. Magento needs `14.00`, not `14.00 USD`, in the `price` column.
