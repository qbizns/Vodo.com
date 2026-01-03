<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use VodoCommerce\Models\DigitalProductCode;
use VodoCommerce\Models\DigitalProductFile;
use VodoCommerce\Models\OrderItem;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Store;

class DigitalProductService
{
    public function __construct(protected Store $store)
    {
    }

    // Digital File Management

    public function attachFile(Product $product, UploadedFile $file, array $data = []): DigitalProductFile
    {
        $fileName = $data['name'] ?? $file->getClientOriginalName();
        $filePath = $file->store('digital-products/' . $product->id, 'private');

        $digitalFile = DigitalProductFile::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'name' => $fileName,
            'file_path' => $filePath,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'download_limit' => $data['download_limit'] ?? null,
        ]);

        do_action('commerce.digital_product.file_attached', $digitalFile, $product);

        return $digitalFile;
    }

    public function deleteFile(DigitalProductFile $file): void
    {
        // Delete physical file from storage
        if (Storage::disk('private')->exists($file->file_path)) {
            Storage::disk('private')->delete($file->file_path);
        }

        $file->delete();

        do_action('commerce.digital_product.file_deleted', $file);
    }

    public function getProductFiles(Product $product): Collection
    {
        return DigitalProductFile::where('store_id', $this->store->id)
            ->where('product_id', $product->id)
            ->get();
    }

    // Digital Code Management

    public function generateCodes(Product $product, int $quantity, ?string $prefix = null): Collection
    {
        $codes = new Collection();

        for ($i = 0; $i < $quantity; $i++) {
            $code = $this->generateUniqueCode($prefix);

            $digitalCode = DigitalProductCode::create([
                'store_id' => $this->store->id,
                'product_id' => $product->id,
                'code' => $code,
                'is_used' => false,
            ]);

            $codes->push($digitalCode);
        }

        do_action('commerce.digital_product.codes_generated', $codes, $product, $quantity);

        return $codes;
    }

    public function importCodes(Product $product, array $codes): Collection
    {
        $imported = new Collection();

        foreach ($codes as $codeString) {
            // Check if code already exists
            $existing = DigitalProductCode::where('code', $codeString)->first();
            if ($existing) {
                continue;
            }

            $digitalCode = DigitalProductCode::create([
                'store_id' => $this->store->id,
                'product_id' => $product->id,
                'code' => $codeString,
                'is_used' => false,
            ]);

            $imported->push($digitalCode);
        }

        do_action('commerce.digital_product.codes_imported', $imported, $product);

        return $imported;
    }

    public function assignCodeToOrder(OrderItem $orderItem): ?DigitalProductCode
    {
        $product = $orderItem->product;

        if (!$product || !$product->is_downloadable) {
            return null;
        }

        $code = DigitalProductCode::where('store_id', $this->store->id)
            ->where('product_id', $product->id)
            ->available()
            ->first();

        if (!$code) {
            return null;
        }

        $code->markAsUsed($orderItem);

        do_action('commerce.digital_product.code_assigned', $code, $orderItem);

        return $code;
    }

    public function getAvailableCodes(Product $product): Collection
    {
        return DigitalProductCode::where('store_id', $this->store->id)
            ->where('product_id', $product->id)
            ->available()
            ->get();
    }

    public function getUsedCodes(Product $product): Collection
    {
        return DigitalProductCode::where('store_id', $this->store->id)
            ->where('product_id', $product->id)
            ->used()
            ->with(['orderItem'])
            ->get();
    }

    public function deleteCode(DigitalProductCode $code): void
    {
        if ($code->is_used) {
            throw new \Exception('Cannot delete a code that has been used');
        }

        $code->delete();

        do_action('commerce.digital_product.code_deleted', $code);
    }

    protected function generateUniqueCode(?string $prefix = null): string
    {
        $prefix = $prefix ?? strtoupper(Str::random(4));

        do {
            $code = $prefix . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (DigitalProductCode::where('code', $code)->exists());

        return $code;
    }

    public function getCodeStatistics(Product $product): array
    {
        $total = DigitalProductCode::where('store_id', $this->store->id)
            ->where('product_id', $product->id)
            ->count();

        $available = DigitalProductCode::where('store_id', $this->store->id)
            ->where('product_id', $product->id)
            ->available()
            ->count();

        $used = DigitalProductCode::where('store_id', $this->store->id)
            ->where('product_id', $product->id)
            ->used()
            ->count();

        return [
            'total' => $total,
            'available' => $available,
            'used' => $used,
            'usage_percentage' => $total > 0 ? round(($used / $total) * 100, 2) : 0,
        ];
    }
}
