<?php

namespace App\Transformers;

use App\Entities\MenuList;
use App\Entities\OrderDetail;
use App\Entities\Photo;
use App\Entities\Requests;
use App\Entities\Restaurant;
use App\Entities\Setting;
use App\Repositories\MenuListRepository;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class OrderDetailTransformerOrder extends TransformerAbstract
{
    private $initialTime = "0001-01-01 00:00:00";
    public $defaultIncludes = ['orders_details'];
    public $menuListRepository;
    public $days = array(
        "-1"=> "Scheduled",
        "0" =>"Cooked Every day",
        "1"=> "monday",
        "2"=> "tuesday",
        "3"=> "wednesday",
        "4"=> "thursday",
        "5"=> "friday",
        "6"=> "saturday",
        "7"=> "sunday"
    );

    private $default_warn_serve_minute = 60;

    public function __construct(MenuListRepository $menuListRepository)
    {
        $this->menuListRepository = $menuListRepository;
    }

    public function transform(OrderDetail $orderDetail)
    {
        $restaurant = $orderDetail->order->restaurant;
        $request = $this->getRequest($orderDetail);
        $res =  [
            "id" => (int)$orderDetail->ID_orders,
            'ID_orders_detail' => $orderDetail->id ? $orderDetail->id : $orderDetail->ID,
            "ID_orders" => (int)$orderDetail->ID_orders,
            'ID_menu_list' => $orderDetail->menu_list ? $orderDetail->menu_list->ID : null,
            'x_number' => (int)$orderDetail->x_number,
            'price' =>  (float)$orderDetail->price,
            'prices' => (float)$orderDetail->prices,
            'Quantity' => $orderDetail->quantity,
            'is_child' => $orderDetail->is_child,
            'status' => (int)$orderDetail->status,
            'comment' => $orderDetail->comment,
            'side_dish' => '' . $orderDetail->side_dish,
            "ID_client" => $orderDetail->ID_client,
            'can_cancel' => $orderDetail->can_cancel,
            "serve_at" => $orderDetail->serve_at,
            "visible" => 0,
            "recommended_side_dish" => $orderDetail->recommended_side_dish,
            'order_by_side_dish' => $orderDetail->order_by_side_dish,
            "currency" => $orderDetail->currency,
            "client_name" => $orderDetail->client->user->name,
            "email" => $orderDetail->client->user->email,
            "client_phone" => $orderDetail->client->user->phone,
            "menu_name" => $orderDetail->menu_list ? $orderDetail->menu_list->name : ($orderDetail->requestMenu ? $orderDetail->requestMenu->name : ''),
            "order_request_menu" => $orderDetail->ID_request_menu,
            "order_menu_list" => $orderDetail->ID_menu_list,
            "created_at" => (new Carbon($orderDetail->order->created_at))->toDateTimeString(),
            "update_by" => $orderDetail->order->update_by,
        ];

        if ($restaurant){
            $res = array_merge(
                $res,
                [
                    "business_id" => (int)$orderDetail->order->ID_restaurant,
                    "business_name" => $orderDetail->order->restaurant->name,
                    "business_latitude" => $orderDetail->order->restaurant->latitude,
                    "business_longitude" => $orderDetail->order->restaurant->longitude,
                    "business_type" => $orderDetail->order->restaurant->type ? $orderDetail->order->restaurant->type->name : null,
                    "buss_phone" => $orderDetail->order->restaurant->phone,
                    "SMS_phone" => $orderDetail->order->restaurant->SMS_phone,
                    "warn_serve_minute" => $this->getSetting($orderDetail->order->restaurant)->warn_serve_minute,
                    "sub_serve_at" => $this->getSubServedAt($orderDetail, $orderDetail->order->restaurant),
                    "sub_created_at" => $this->getSubCreatedAt($orderDetail->order, $orderDetail->order->restaurant)
                ]);
        } else if ($request) {
            $res = array_merge(
                $res,
                [
                    "request_id" => (int)$request->ID,
                    "business_id" => (int)$request->ID_restaurant,
                    "business_name" => $request->new_restaurant,
                    "buss_phone" => $request->new_address,
                    "business_latitude" => $orderDetail->client->latitude,
                    "business_longitude" => $orderDetail->client->longitude,
                    "warn_serve_minute" => $this->getWarnServeMinute($this->getRestaurant($request)),
                    "sub_serve_at" => $this->getSubServedAt($orderDetail, $this->getRestaurant($request)),
                    "sub_created_at" => $this->getSubCreatedAt($orderDetail->order, $this->getRestaurant($request))
                ]
            );
        }
        return $res;
    }

    public function includeOrdersDetails(OrderDetail $orderDetail)
    {
        return $this->collection($orderDetail->orders_details, new OrderDetailTransformerSimple($this->menuListRepository));
    }

    public function formatDate($date_time_str)
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $date_time_str)->format('d.m.Y H:i');
    }

    public function getRequest($orderDetail)
    {
        return Requests::where('ID_orders', '=', $orderDetail->ID_orders)->first();
    }

    public function getSetting($restaurant)
    {
        return Setting::where('lang', '=', $restaurant->lang)->first();
    }

    public function getRestaurant($request)
    {
        return Restaurant::find($request->ID_restaurant);
    }

    public function getWarnServeMinute($restaurant)
    {
        if ($restaurant) {
            return $this->getSetting($restaurant)->warn_serve_minute;
        } else {
            return $this->default_warn_serve_minute;
        }
    }

    public function getSubServedAt($orderDetail, $restaurant)
    {
        if ($restaurant) {
            $warn_serve_minute = $this->getSetting($restaurant)->warn_serve_minute;
        } else {
            $warn_serve_minute = $this->default_warn_serve_minute;
        }
        return (new Carbon($orderDetail->serve_at))->addMinutes(-$warn_serve_minute)->toDateTimeString();
    }

    public function getSubCreatedAt($order, $restaurant)
    {
        if ($restaurant) {
            $warn_serve_minute = $this->getSetting($restaurant)->warn_serve_minute;
        } else {
            $warn_serve_minute = $this->default_warn_serve_minute;
        }
        return (new Carbon($order->created_at))->addMinutes($warn_serve_minute)->toDateTimeString();
    }

}