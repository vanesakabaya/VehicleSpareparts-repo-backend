<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\Shop;

class VendorOrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $shop;

    public function __construct(Order $order, Shop $shop)
    {
        $this->order = $order;
        $this->shop = $shop;
    }

    public function build()
    {
        return $this->view('emails.vendor_order_created')
            ->subject('New Order Received');
    }
}
