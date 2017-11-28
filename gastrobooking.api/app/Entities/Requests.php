<?php
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Requests extends Model
{
    public $table = "requests";

    public $primaryKey = "ID";

    public $timestamps = false;

    public function order()
    {
        return $this->belongsTo(Order::class, 'ID_orders');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'ID_restaurant', 'ID');
    }

}