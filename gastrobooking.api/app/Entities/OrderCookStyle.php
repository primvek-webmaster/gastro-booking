<?php
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class OrderCookStyle extends Model
{
    public $table = "orders_cook_style";

    public $primaryKey = "ID";

    public $timestamps = false;


    public function orderDetail()
    {
        return $this->hasMany(OrderDetail::class, 'ID_orders_detail');
    }

    public function cookStyle()
    {
        return $this->hasMany(CookStyle::class, 'ID_cook_style');
    }

}