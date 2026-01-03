<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Traits\BelongsToStore;

class DigitalProductFile extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_digital_product_files';

    protected $fillable = [
        'store_id',
        'product_id',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'download_limit',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'download_limit' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
