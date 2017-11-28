<?php

namespace App\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Sofa\Eloquence\Eloquence;

class OrderDetail extends Model
{
    public $table = "orders_detail";
    use Eloquence;

    public $timestamps = false;
    protected $fillable = [
        "x_number", "price", "serve_at", "is_child", "status", "comment", "side_dish", "client_commission", "member_commission"
    ];

    public $primaryKey = "ID";

    public function requestParam()
    {
        return $this->hasOne(RequestParam::class, 'ID_orders_detail');
    }

    public function requestMenu()
    {
        return $this->belongsTo(RequestMenu::class, 'ID_request_menu');
    }
    
    public function menu_list(){
        return $this->belongsTo(MenuList::class, "ID_menu_list");
    }

    public function order(){
        return $this->belongsTo(Order::class, "ID_orders");
    }

    public function client()
    {
        return $this->belongsTo(Client::class, "ID_client");
    }

    public function scopeFilterByStatus($query, Request $request){
        if ($request->has("status")){
            $query->where('status', $request->status);
        }
    }

    public function scopeSearchByRestaurant($query, Request $request){
        if ($request->has('clientname')){
            return $query->search($request->clientname, ["order.restaurant.name", "order.newRequest.new_restaurant"]);
        }
    }

    public function scopeSearchAll($query, Request $request){
        if ($request->has('searchname')){
            return $query->search($request->searchname,
                [
                    "order.restaurant.name", // Restaurant name for menu lists
                    "order.newRequest.new_restaurant", // Restaurant name for requests
                    "client.user.name", // Client name
                    "client.user.email", // email
                    "client.user.phone", // Clinet Phone
                    "order.restaurant.phone", // Business phone
                    "order.newRequest.new_address", // Business address
                    "price", // Price
                    "order.restaurant.SMS_phone",
                ]);
        }
    }

    public function scopeFilterAll($query, Request $request) {
        return $query->filterByDistrict($request)
            ->filterByCountry($request)
            ->filterByCompany($request)
            ->filterByBooking($request)
            ->filterByRequest($request)
            ->filterByOrderStatus($request);
    }

    public function scopeFilterByDistrict($query, Request $request)
    {
        if ($request->has('district')) {
            $query->whereHas("order", function ($query) use ($request) {
                $query->whereHas("restaurant", function($query) use ($request){
                    $query->whereHas("district", function ($query) use ($request) {
                        return $query->where('ID', '=', $request->district);
                    });
                });
            });
        }

    }

    public function scopeFilterByCountry($query, Request $request)
    {
        if ($request->has('country')){
            $query->whereHas("order", function ($query) use ($request) {
                $query->whereHas("restaurant", function($query) use ($request){
                    $query->whereHas("district", function ($query) use ($request) {
                        return $query->where('country', '=', $request->country);
                    });
                });
            });
        }

    }

    public function scopeFilterByCompany($query, Request $request)
    {
        if ($request->has('company') and $request->company == 1){
            $query->whereHas("order", function ($query) use ($request) {
                $query->whereHas("restaurant", function($query) use ($request) {
                    return $query->where('ID_user_dealer', '=', $request->user_id);
                });
            });
        }
    }

    public function scopeFilterByBooking($query, Request $request)
    {
        if (!$request->has('booking') || $request->booking === false){
            return $query->where('ID_menu_list', '=', null);
        }
    }

    public function scopeFilterByRequest($query, Request $request)
    {
        if (!$request->has('orderRequest') || $request->orderRequest === false){
            return $query->where('ID_request_menu', '=', null);
        }

    }

    public function scopeFilterByOrderStatus($query, Request $request)
    {
        if ($request->has('orderStatus')){
            if ($request->orderStatus == 'new'){
                return $query->where('status', '=', 0);
            } else if ($request->orderStatus == 'processed'){
                return $query->whereIn('status', [1,2,3,4])->where('serve_at', '>=', Carbon::now());
            } else if ($request->orderStatus == 'old') {
                return $query->whereIn('status', [1,2,3,4])->where('serve_at', '<', Carbon::now());
            }
        }

    }

    public function sideDish()
    {
        return $this->hasMany(OrderDetail::class, 'side_dish', 'ID');
    }

    public function mainDish()
    {
        return $this->belongsTo(OrderDetail::class, 'side_dish', 'ID');
    }

    public function language()
    {
        return $this->belongsTo(Setting::class, 'currency', 'currency_short');
    }
}
