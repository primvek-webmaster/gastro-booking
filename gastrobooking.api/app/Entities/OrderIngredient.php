<?php
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class OrderIngredient extends Model
{
    public $table = "orders_ingredient";

    public $primaryKey = "ID";

    public $timestamps = false;

    public function orderDetail()
    {
        return $this->hasMany(OrderDetail::class, 'ID_orders_detail');
    }

    public function ingredient()
    {
        return $this->hasMany(Ingredient::class, 'ID_ingredient');
    }

}