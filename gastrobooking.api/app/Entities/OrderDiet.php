<?php
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class OrderDiet extends Model
{
    public $table = "orders_diet";

    public $primaryKey = "ID";

    public $timestamps = false;


    public function orderDetail()
    {
        return $this->hasMany(OrderDetail::class, 'ID_orders_detail');
    }

    public function diet()
    {
        return $this->hasMany(Diet::class, 'ID_diet');
    }

}