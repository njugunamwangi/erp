<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Unicodeveloper\Paystack\Facades\Paystack;

class InvoicePaymentController extends Controller
{
    protected Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
         $this->invoice = $invoice;
    }
    /**
     * Redirect the User to Paystack Payment Page
     *
     * @return Url
     */
    public function redirectToGateway($invoice)
    {
        $data = [
            'email' => $invoice->user->email,
            'amount' => $invoice->total,
            'metadata' => json_encode($array = ['invoice_id' => $invoice->id]),
            'currency' => $invoice->currency->abbr,
            'reference' => Paystack::genTranxRef(),
            '_token' => csrf_token()
        ];

        try {
            return Paystack::getAuthorizationUrl($data)->redirectNow();
        } catch (\Exception $e) {
            return Redirect::back()->withMessage(['msg' => 'The paystack token has expired. Please refresh the page and try again.', 'type' => 'error']);
        }
    }

    /**
     * Obtain Paystack payment information
     *
     * @return void
     */
    public function handleGatewayCallback()
    {
        $paymentDetails = Paystack::getPaymentData();

        $data = $paymentDetails['data'];

        // dd($paymentDetails);

        $payment = Payment::create([
            'status' => $data['status'],
            'message' => $paymentDetails['message'],
            'paymentId' => $data['id'],
            'invoice_id' => $data['metadata']['invoice_id'],
            'domain' => $data['domain'],
            'gateway_response' => $data['gateway_response'],
            'reference' => $data['reference'],
            'currency' => $data['currency'],
            'ip_address' => $data['ip_address'],
            'amount' => $data['amount'] / 100,
            'channel' => $data['channel'],
        ]);

        if ($payment) {
            if ($data['status'] == 'success') {
                $model = Invoice::find($data['metadata']['invoice_id']);

                $model->update([
                    'payment_status' => InvoiceStatus::Paid,
                    'payment_method' => 'PayStack',
                ]);
            }

            return redirect()->to('payment-successful');
        }
    }
}
