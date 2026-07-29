<?php

namespace App\Services;

class FeedValidatorService
{
    private const REQUIRED_FIELDS = [
        'id'           => 'Product ID',
        'title'        => 'Title',
        'description'  => 'Description',
        'link'         => 'Link (URL)',
        'image_link'   => 'Image Link',
        'availability' => 'Availability',
        'price'        => 'Price',
        'brand'        => 'Brand',
    ];

    private const VALID_AVAILABILITY = [
        'in stock', 'out of stock', 'preorder', 'backorder',
        'in_stock', 'out_of_stock',
    ];

    public function validate(array $row, int $rowNumber): array
    {
        $errors   = [];
        $warnings = [];

        foreach (self::REQUIRED_FIELDS as $field => $label) {
            $value = trim($row[$field] ?? '');

            if ($value === '') {
                $errors[] = $this->issue($field, 'error', "Missing required field: {$label}");
                continue;
            }

            match ($field) {
                'link', 'image_link' => $this->validateUrl($value, $field, $label, $errors, $warnings),
                'price'              => $this->validatePrice($value, $field, $row, $errors, $warnings),
                'availability'       => $this->validateAvailability($value, $field, $errors),
                'title'              => $this->validateTitle($value, $field, $warnings),
                'description'        => $this->validateDescription($value, $field, $warnings),
                'id'                 => $this->validateId($value, $field, $warnings),
                default              => null,
            };
        }

        $hasGtin = ! empty(trim($row['gtin'] ?? ''));
        $hasMpn  = ! empty(trim($row['mpn'] ?? ''));

        if (! $hasGtin && ! $hasMpn) {
            $warnings[] = $this->issue('gtin', 'warning',
                'Missing both GTIN and MPN — one is strongly recommended for better ad performance.');
        }

        if (empty(trim($row['google_product_category'] ?? ''))) {
            $warnings[] = $this->issue('google_product_category', 'warning',
                'google_product_category is missing — helps Google classify your product correctly.');
        }

        if (empty(trim($row['condition'] ?? ''))) {
            $warnings[] = $this->issue('condition', 'warning',
                'Condition is missing. Accepted values: new, refurbished, used.');
        }

        $allIssues = array_merge($errors, $warnings);
        $status    = count($errors) > 0 ? 'error' : (count($warnings) > 0 ? 'warning' : 'valid');

        return ['status' => $status, 'issues' => $allIssues];
    }

    private function validateUrl(string $value, string $field, string $label, array &$errors, array &$warnings): void
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            $errors[] = $this->issue($field, 'error', "{$label} is not a valid URL: \"{$value}\"");
            return;
        }

        if (! str_starts_with($value, 'https://')) {
            $warnings[] = $this->issue($field, 'warning', "{$label} should use HTTPS for better trust signals.");
        }

        if ($field === 'image_link') {
            $ext = strtolower(pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'])) {
                $warnings[] = $this->issue($field, 'warning',
                    "Image URL doesn't end with a known image extension (jpg, png, webp, etc.).");
            }
        }
    }

    private function validatePrice(string $value, string $field, array $row, array &$errors, array &$warnings): void
    {
        if (! preg_match('/^\$?[\d,]+(\.\d{1,2})?(\s+[A-Z]{3})?$/', $value)) {
            $errors[] = $this->issue($field, 'error',
                "Price format is invalid: \"{$value}\". Expected format: \"19.99 USD\"");
            return;
        }

        $numeric = (float) preg_replace('/[^0-9.]/', '', $value);

        if ($numeric <= 0) {
            $errors[] = $this->issue($field, 'error', 'Price must be greater than 0.');
        }

        if (! $this->isMagentoRow($row) && ! preg_match('/[A-Z]{3}/', $value)) {
            $warnings[] = $this->issue($field, 'warning',
                'Price is missing a currency code (e.g., "19.99 USD"). Required for multi-country feeds.');
        }
    }

    private function isMagentoRow(array $row): bool
    {
        return isset($row['sku'], $row['attribute_set_code'], $row['product_type'], $row['product_websites']);
    }

    private function validateAvailability(string $value, string $field, array &$errors): void
    {
        if (! in_array(strtolower($value), self::VALID_AVAILABILITY)) {
            $valid = implode(', ', array_slice(self::VALID_AVAILABILITY, 0, 4));
            $errors[] = $this->issue($field, 'error',
                "Availability \"{$value}\" is not valid. Accepted values: {$valid}.");
        }
    }

    private function validateTitle(string $value, string $field, array &$warnings): void
    {
        if (strlen($value) > 150) {
            $warnings[] = $this->issue($field, 'warning', 'Title exceeds 150 characters — Google may truncate it in ads.');
        }
        if (strlen($value) < 10) {
            $warnings[] = $this->issue($field, 'warning', 'Title is very short (< 10 chars) — consider adding more descriptive detail.');
        }
    }

    private function validateDescription(string $value, string $field, array &$warnings): void
    {
        if (strlen($value) > 5000) {
            $warnings[] = $this->issue($field, 'warning', 'Description exceeds 5000 characters — only the first 5000 will be used.');
        }
        if (strlen($value) < 20) {
            $warnings[] = $this->issue($field, 'warning', 'Description is very short — a detailed description improves ad quality score.');
        }
    }

    private function validateId(string $value, string $field, array &$warnings): void
    {
        if (strlen($value) > 50) {
            $warnings[] = $this->issue($field, 'warning', 'Product ID exceeds 50 characters — some platforms may truncate it.');
        }
        if (preg_match('/\s/', $value)) {
            $warnings[] = $this->issue($field, 'warning', 'Product ID contains spaces — use hyphens or underscores instead.');
        }
    }

    private function issue(string $field, string $type, string $message): array
    {
        return compact('field', 'type', 'message');
    }
}
