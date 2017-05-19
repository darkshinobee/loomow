<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\ProductTransaction;
use App\Transaction;
use App\Product;
use App\Customer;
use Illuminate\Support\Facades\DB;
use Auth;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\MiscController;
use App\BuyTemp;
use App\Mail\PaySuccess;
use App\Mail\OrderSheet;
use App\Mail\GamePurchased;
use App\Mail\PaymentFailed;
use Illuminate\Support\Facades\Mail;

class ProductTransactionController extends Controller
{
  public function store($tr_id)
  {
    $customer = Auth::guard('customer')->user();

    //Store To DB
    $buyCartItems = Cart::instance('buyCart')->content();

    foreach ($buyCartItems as $bc) {

      $prodTrans = new ProductTransaction;

      $prodTrans->quantity = $bc->qty;
      $prodTrans->product_id = $bc->id;
      $prodTrans->transaction_id = $tr_id;
      $prodTrans->key = 1;
      $prodTrans->status = "Pending Delivery";
      $prodTrans->price = $bc->price * $bc->qty;

      $prodTrans->save();

      $cust = DB::table('sell_transactions')
      ->where('product_id', $bc->id)
      ->where('key', 2)
      ->first();

      $c_cust = Customer::find($cust->customer_id);
      $old_voucher = $c_cust->voucher_value;
      $c_cust->voucher_value += ($bc->price - 1000);
      $c_cust->save();

      Mail::to($c_cust->email)->send(new GamePurchased($c_cust, $bc, $old_voucher));

      DB::table('sell_transactions')
      ->where('id', $cust->id)
      ->update(['key' => 3, 'status' => 'Product Sold']);
    }

    $product = new ProductController;
    $product->update();

    $buytemp = DB::table('buy_temps')->where('customer_id', $customer->id)->first();

    $newVoucher = $buytemp->resultant_voucher;

    $misc = new MiscController;
    $misc->voucherUpdate($newVoucher);

    DB::table('buy_temps')->where('customer_id', $customer->id)->delete();

    return $this->orderSuccess($tr_id);
  }

  public function getOrders()
  {
    $customer = Auth::guard('customer')->user();
    $p = 'products';
    $pt = 'product_transactions';
    $t = 'transactions';

    $orders = DB::table($p)->select($t.'.reference_no', $t.'.created_at',
    $pt.'.price', $pt.'.quantity', $p.'.title', $p.'.platform', $pt.'.status', $p.'.image_name')
    ->join($pt, $p.'.id', '=', $pt.'.product_id')
    ->join($t, $pt.'.transaction_id', '=', $t.'.id')
    ->where($t.'.customer_id', $customer->id)
    ->orderBy($t.'.created_at', 'desc')
    ->paginate(5);

    return view('customer.orders', compact('orders'));
  }

  public function getUploads()
  {
    $customer = Auth::guard('customer')->user();
    $p = 'products';
    $st = 'sell_transactions';

    $uploads = DB::table($p)->select($st.'.key', $st.'.status', $st.'.created_at', $st.'.updated_at',
    $p.'.title', $p.'.platform', $p.'.price', $p.'.image_path')
    ->join($st, $p.'.id', '=', $st.'.product_id')
    ->where($st.'.customer_id', $customer->id)
    ->orderBy($st.'.created_at', 'desc')
    ->paginate(6);

    return view('customer.uploads', compact('uploads'));
  }

  public function orderSuccess($tr_id)
  {
    $buyCartItems = Cart::instance('buyCart')->content();
    $myCart = $buyCartItems;
    Cart::instance('buyCart')->destroy();

    $tref = DB::table('transactions')->where('id', $tr_id)->value('reference_no');

    $customer = Auth::guard('customer')->user();
    $p = 'products';
    $pt = 'product_transactions';
    $t = 'transactions';

    $obj = DB::table($p)->select($pt.'.price', $pt.'.quantity', $p.'.title', $p.'.platform')
    ->join($pt, $p.'.id', '=', $pt.'.product_id')
    ->join($t, $pt.'.transaction_id', '=', $t.'.id')
    ->where($t.'.customer_id', $customer->id)
    ->where($t.'.reference_no', $tref)
    ->get();

    Mail::to($customer->email)->send(new PaySuccess($obj, $tref, $customer));
    Mail::to('orders@loomow.com')->send(new OrderSheet($obj, $customer, $tref));

    return view('cart.orderSuccess', compact('myCart', 'tref', 'customer'));
  }

  public function orderFail($failRef)
  {
    $myCart = Cart::instance('buyCart')->content();
    return view('cart.orderFail', compact('myCart', 'failRef'));
  }

  public function storeFail($tr_id)
  {
    $customer = Auth::guard('customer')->user();

    //Store To DB
    $buyCartItems = Cart::instance('buyCart')->content();

    foreach ($buyCartItems as $bc) {

      $prodTrans = new ProductTransaction;

      $prodTrans->quantity = $bc->qty;
      $prodTrans->product_id = $bc->id;
      $prodTrans->transaction_id = $tr_id;
      $prodTrans->key = 0;
      $prodTrans->status = "Transaction Failed";
      $prodTrans->price = $bc->price * $bc->qty;
      $prodTrans->save();
    }

    $failRef = DB::table('transactions')->where('id', $tr_id)->value('reference_no');
    DB::table('buy_temps')->where('customer_id', $customer->id)->delete();

    Mail::to($customer->email)->send(new PaymentFailed($bc, $customer));

    return redirect()->action('ProductTransactionController@orderFail', $failRef);
  }

}
