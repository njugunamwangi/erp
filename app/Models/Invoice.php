<?php

namespace App\Models;

use App\Enums\InvoiceSeries;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LaravelDaily\Invoices\Classes\Buyer;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Facades\Invoice as FacadesInvoice;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'items' => 'json',
            'status' => InvoiceStatus::class,
            'series' => InvoiceSeries::class,
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stks(): HasMany
    {
        return $this->hasMany(MpesaSTK::class);
    }

    public function savePdf()
    {
        $customer = new Buyer([
            'name' => $this->user->name,
            'custom_fields' => [
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ],
        ]);

        $items = [];

        foreach ($this->items as $item) {
            $items[] = (new InvoiceItem())
                ->title($item['description'])
                ->pricePerUnit($item['unit_price'])
                ->subTotalPrice($item['unit_price'] * $item['quantity'])
                ->quantity($item['quantity']);
        }

        FacadesInvoice::make()
            ->buyer($customer)
            ->status($this->status->name)
            ->taxRate($this->taxes)
            ->filename($this->serial)
            ->template('invoice')
            ->series($this->series->name)
            ->sequence($this->serial_number)
            ->delimiter('-')
            ->addItems($items)
            ->save('public');
    }
}
