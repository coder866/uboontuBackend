<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Http\Controllers\Flash;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use session;


class FlutterWaveController extends ParentOrderController
{
    private $userPhone;
    
    public function __init()
    {


    }   
        /*
    * @return void
    */

   public function initialize(Request $request)
   {
        //This generates a payment reference
        //$reference = Flutterwave::generateReference();
        $this->userPhone = $request->get('user_phone');
        
       $user = $this->userRepository->findByField('api_token', $request->get('api_token'))->first();
       $coupon = $this->couponRepository->findByField('code', $request->get('coupon_code'))->first();
       $deliveryId = $request->get('delivery_address_id');

      //  dd($user);
       if (!empty($user)) {
           $this->order->user = $user;
           $this->order->user_id = $user->id;
           $this->order->delivery_address_id = $deliveryId;
           $this->coupon = $coupon;
           $flutterwaveCart = $this->getCheckoutData();
            
           
           try {
                // Enter the details of the payment
           //     dd($flutterwaveCart);
               $payment = Flutterwave::initializePayment($flutterwaveCart);

                //Brilliant Ideas

                if ($payment->status !== 'success') {
                    dd($payment);
                    // notify something went wrong
                    return $payment->message;
                }
               

                return redirect($payment->data->link);
                
           } catch (\Exception $e) {
                session()->flash("Error processing Flutterwave payment for your order :" . $e->getMessage());
           }
       }
       return redirect(route('payments.failed'));

       
   }

    /**
     * Set cart data for processing payment on PayPal.
     *
     *
     * @return array
     */
    private function getCheckoutData()
    {

        // Enter the details of the payment

        //dd('VONE' . $this->userPhone);
        
        
        $this->calculateTotal();
        $order_id = $this->paymentRepository->all()->count() + 1;
        $data = [
            'amount' => $this->total,
            'tx_ref' => $order_id,
            'email' => $this->order->user->email,
            'currency' => "KES",
            'payment_options' => 'mpesa,card,banktransfer',
            'customer' => [
                'name' => $this->order->user->name,
                'phone_number' => $this->userPhone,
                'email' => $this->order->user->email,
            ],
            'customizations' => [
            'title' => $this->order->user->cart[0]->product->market->name,
            "description" => "Checkout",
            ],
            'redirect_url' => url("payments/flutterwave/callback?user_id=" . $this->order->user_id . "&delivery_address_id=" . $this->order->delivery_address_id)
        ];
          
        return $data;
    }

   /**
    * Obtain Rave callback information
    * @return void
    */
   public function callback()
   {
       
       $status = request()->status;

       //if payment is successful
       if ($status ==  'successful') {
       
       $transactionID = Flutterwave::getTransactionIDFromCallback();
       $data = Flutterwave::verifyTransaction($transactionID);

        $token = request()->get('token');
 //       $PayerID = request()->get('PayerID');
        $this->order->user_id = request()->get('user_id', 0);
        $this->order->user = $this->userRepository->findWithoutFail($this->order->user_id);
        $this->coupon = $this->couponRepository->findByField('code', request()->get('coupon_code'))->first();
        $this->order->delivery_address_id = request()->get('delivery_address_id', 0);

        if (strtoupper($data['data']['status'])=='SUCCESSFUL') {

            $this->order->payment = new Payment();
            $this->order->payment->status = $data['data']['status'];
            $this->order->payment->method = $data['data']['payment_type'];
 
            $this->createOrder();
 
            return redirect(url('payments/flutterwave'));
        } else {
                session()->flash("Error processing FlutterWave payment for your order");
            return redirect(route('payments.failed'));
        }
 

       }
       elseif ($status ==  'cancelled'){

        return "Transaction Cancelled !";
           //Put desired action/code after transaction has been cancelled here
       }
       else{
           //Put desired action/code after transaction has failed here
       }
       // Get the transaction from your DB using the transaction reference (txref)
       // Check if you have previously given value for the transaction. If you have, redirect to your successpage else, continue
       // Confirm that the currency on your db transaction is equal to the returned currency
       // Confirm that the db transaction amount is equal to the returned amount
       // Update the db transaction record (including parameters that didn't exist before the transaction is completed. for audit purpose)
       // Give value for the transaction
       // Update the transaction to note that you have given value for the transaction
       // You can also redirect to your success page from here
   }

   

}