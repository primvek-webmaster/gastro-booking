<?php
/**
 * Created by Hamid Shafer, 2017-02-25
 */

namespace App\Http\Controllers;

use App\Entities\Client;
use App\Entities\Order;
use App\Entities\OrderCookStyle;
use App\Entities\OrderDetail;
use App\Entities\OrderDiet;
use App\Entities\OrderIngredient;
use App\Entities\Restaurant;
use App\Entities\Requests;
use App\Repositories\MenuListRepository;
use App\Repositories\OrderDetailRepository;
use App\Repositories\RestaurantRepository;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Transformers\OrderDetailTransformer;
use App\Transformers\OrderDetailTransformerOrder;
use App\Transformers\OrderDetailTransformerSimple;
use App\Transformers\OrderTransformer;
use App\Transformers\UserTransformer;
use App\Transformers\RestaurantTransformer;
use App\User;
// use UserController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use Webpatser\Uuid\Uuid;
use Dingo\Api\Routing\Helpers;
use Dingo\Api\Http\Response;
use Illuminate\Support\Arr;

class PreregistrationController extends Controller
{
    use Helpers;
    protected $restaurantRepository;
    protected $userRepository;
    protected $menuListRepository;
    public $perPage = 10;

    public function __construct(UserRepository $userRepository,
                                RestaurantRepository $restaurantRepository,
                                MenuListRepository $menuListRepository,
                                OrderDetailRepository $orderDetailRepository
    )
    {
        $this->restaurantRepository = $restaurantRepository;
        $this->userRepository = $userRepository;
        $this->menuListRepository = $menuListRepository;
        $this->orderDetailRepository = $orderDetailRepository;
    }

    public function queryEx($user_id = null)
    {
        $query = Restaurant::query()
            ->leftJoin('user', 'user.id', '=', 'restaurant.ID_user')
            ->leftJoin('district', 'district.id', '=', 'restaurant.ID_district')
            ->leftJoin('preregistrations', 'preregistrations.user_id', '=', 'restaurant.ID_user')
            ->where('restaurant.status', 'N')
            ->orderBy('restaurant.updated_at', 'DESC')
            ->select(
                'restaurant.id as restaurant_id',
                'restaurant.ID_user',
                'restaurant.ID_user_data',
                'restaurant.ID_user_acquire',
                'restaurant.ID_user_contract',
                'restaurant.ID_district',
                'district.country as country',
                'restaurant.name as restaurant_name',
                'restaurant.phone as restaurant_phone',
                'restaurant.email as restaurant_email',
                'restaurant.www as restaurant_www',
                'restaurant.dealer_note as restaurant_dealer_note',
                'user.name as owner_name',
                'user.phone as owner_phone',
                'user.email as owner_email',
                'preregistrations.password as owner_password'
            );
        if($user_id) {
            $query = $query->where('restaurant.ID_user_data', $user_id);
        }
        return $query;
    }

    public function all(Request $request)
    {
        $data = $this->queryEx()->get();

        return compact('data');
    }

    public function get_owner_restaurants(Request $request)
    {
        $user_id = isset($request->owner['id']) ? $request->owner['id'] : null;
        $data = $this->queryEx()->get($user_id);

        return compact('data');
    }

    public function districts(Request $request)
    {
        $districts = DB::table('district')->get();

        $countries = array_keys(Arr::pluck($districts, 'id', 'country'));

        return compact('countries', 'districts');
    }
    public function assignments(Request $request)
    {

        $result = $this->restaurantRepository->getAssignments($request);
        $result->current_page = $request->currentPage;
        return compact('result');
    }

    public function orders(Request $request)
    {

        $result = $this->restaurantRepository->getOrders($request);
        $response = $this->response->collection($result, new OrderDetailTransformerOrder($this->menuListRepository));
        return $response;
    }

    public function cancelClient($order, $orderDetails, $request)
    {
        $order->status = 3;
        $order->update_by = 'C';
        if ($request) {
            $request->status = 'C';
            $request->save();
        }
        $order->save();
        $orderDetails->update(['status' => 3]);
        return ['status' => 'done'];
    }

    public function cancelBusiness($order, $orderDetails, $request)
    {
        $order->status = 3;
        $order->update_by = 'B';
        if ($request) {
            $request->status = 'E';
            $request->save();
        }
        $order->save();
        $orderDetails->update(['status' => 3]);
        return ['status' => 'done'];
    }

    public function confirmClient($order, $orderDetails, $request, $user)
    {
        $order->status = 2;
        $order->update_by = 'C';
        $order->ID_user = $user->ID;
        if ($request) {
            $request->status = 'A';
            $request->save();
        }
        $order->save();
        $orderDetails->update(['status' => 2]);
        return ['status' => 'done'];
    }

    public function confirmBusiness($order, $orderDetails, $request, $user)
    {
        $order->status = 2;
        $order->update_by = 'B';
        $order->ID_user = $user->ID;
        if ($request) {
            $request->status = 'A';
            $request->save();
        }
        $order->save();
        $orderDetails->update(['status' => 2]);
        return ['status' => 'done'];
    }

    public function updateStatus(Request $request)
    {
        $user = app('Dingo\Api\Auth\Auth')->user();
        $order = Order::find($request->id);
        $order->updated_at = Carbon::now();
        $orderDetails = OrderDetail::where('ID_orders', $request->id);
        $restaurant = Restaurant::find($order->ID_restaurant);
        $req = null;
        if ($request->type == 'request') {
            $req = Requests::find($request->type_id);
            $req->unread = 'N';
            $req->last_update = Carbon::now();
        }
        if ($request->action == 'cancelClient') {
            return $this->cancelClient($order, $orderDetails, $req);
        } else if ($request->action == 'cancelBusiness') {
            return $this->cancelBusiness($order, $orderDetails, $req);
        } else if ($request->action == 'confirmedClient') {
            return $this->confirmClient($order, $orderDetails, $req, $user);
        } else if ($request->action == 'confirmedBusiness') {
            return $this->confirmBusiness($order, $orderDetails, $req, $user);
        }
        $this->sendConfirmation($order, $restaurant, $request->lang);
        return ['status' => 'not done'];

    }


    public function changeRestaurant(Request $request)
    {
        $order = Order::find($request->order_id);

        if ($order->ID_restaurant != $request->restaurant_id) {

            $new_order = $order->replicate();
            $new_order->ID_restaurant = (int)$request->restaurant_id;
            $new_order->last_update = Carbon::now();
            $new_order->push();

            $orderDetails = OrderDetail::where('ID_orders', $order->ID)->get();

            foreach ($orderDetails as $orderDetail) {
                $new_order_detail = $orderDetail->replicate();
                $new_order_detail->ID_orders = $new_order->ID;
                $new_order_detail->last_update = Carbon::now();
                $new_order_detail->push();

                $orderIngredients = OrderIngredient::where('ID_orders_detail', $orderDetail->ID)->get();
                $orderCookStyles = OrderCookStyle::where('ID_orders_detail', $orderDetail->ID)->get();
                $orderDiets = OrderDiet::where('ID_orders_detail', $orderDetail->ID)->get();

                foreach ($orderIngredients as $orderIngredient) {
                    $new_o_ingredient = $orderIngredient->replicate();
                    $new_o_ingredient->ID_orders_detail = $new_order_detail->ID;
                    $new_o_ingredient->last_update = Carbon::now();
                    $new_o_ingredient->push();
                    $orderIngredient->status = 3;
                    $orderIngredient->save();
                }

                foreach ($orderCookStyles as $orderCookStyle) {
                    $new_o_cook_style = $orderCookStyle->replicate();
                    $new_o_cook_style->ID_orders_detail = $new_order_detail->ID;
                    $new_o_cook_style->last_update = Carbon::now();
                    $new_o_cook_style->push();
                    $orderCookStyle->status = 3;
                    $orderCookStyle->save();
                }

                foreach ($orderDiets as $orderDiet) {
                    $new_o_diet = $orderDiet->replicate();
                    $new_o_diet->ID_orders_detail = $new_order_detail->ID;
                    $new_o_diet->last_update = Carbon::now();
                    $new_o_diet->push();
                    $orderDiet->status = 3;
                    $orderDiet->save();
                }

                $orderDetail->status = 3;
                $orderDetail->save();
            }

            $order->status = 3; // Cancel old order
            $order->save();
            $this->orderDetailRepository->updateSyncServOwnTable($request->restaurant_id, 1);

            return ['status' => 'done', 'order_id' => $new_order->ID];
        }
        return ['status' => 'same restaurant'];

    }

    public function sendEmailConfirmation(Request $request)
    {
        $order = Order::find($request->order_id);
        $restaurant = $order->restaurant;
        $lang = $request->lang;
        $this->sendConfirmation($order, $restaurant, $lang);
    }

    public function sendConfirmation($order, $restaurant, $lang)
    {
        $orders_detail_filtered = [];
        $order->orders_detail = $order->orders_detail->sortBy("serve_at");
        foreach ($order->orders_detail as $order_detail) {
            if ($order_detail->side_dish == 0 ){
                $orders_detail_filtered[] = $order_detail;
            }
        }
        $user = Client::find($order->ID_client)->user;

        $this->orderDetailRepository->sendEmailReminder('update', $user, $order,$restaurant,
            $lang ? $lang: 'cs', $orders_detail_filtered, 'user');
        $this->orderDetailRepository->sendEmailReminder('update', $user, $order, $restaurant,
            $restaurant->lang, $orders_detail_filtered, 'rest');

        $this->orderDetailRepository->sendSMSEmailReminder(
            'update_short',
            $user,
            $order,
            $restaurant,
            $restaurant->lang ?: 'cs',
            $orders_detail_filtered,
            'admin'
        );

    }


    public function getCompanyList(Request $request)
    {
        $result = $this->restaurantRepository->getCompanyList($request);
        return $result;
    }
    public function getDistrictList(Request $request)
    {
        $result = $this->restaurantRepository->getDistrictList($request);
        return $result;
    }


    public function userStatus(Request $request)
    {
        $result = DB::table('employee')->where('id_user', $request->id)->count();

        return $result;
    }
    public function turnovers(Request $request)
    {
        $result = $this->restaurantRepository->getTurnovers($request, false);
        return $result;
    }
    public function sumturnovers(Request $request)
    {
        $result = $this->restaurantRepository->getTurnovers($request, true);
        return $result;
    }

    public function updateDealerForAssignmet( Request $request){
        $response = [
            "success" => false,
            "message" => "",
        ];

        $res = Restaurant::where('id', $request->id)
            ->update( ['id_user_dealer' =>( $request->status == true ? $request->user_id : NULL )]);

        if ($res) $response["success"] = true;

        return $response;
    }
    public function updateContractForAssignmet( Request $request ){
        $response = [
            "success" => false,
            "message" => "",
        ];

        $res = Restaurant::where('id', $request->id)
            ->update( ['id_user_contract' =>( $request->status == true ? $request->user_id : NULL )]);
        if ($res) $response["success"] = true;

        return $response;
    }

    public function store(Request $request)
    {
        $response = [
            "success" => false,
            "message" => "",
        ];

        if (!$request->has("owner"))
        {
            $response['message'] = "Request field 'owner' is missing!";
            return $response;
        }
        if (!$request->has("restaurants"))
        {
            $response['message'] = "Request field 'restaurants' is missing!";
            return $response;
        }

        $update = isset($request->owner['id']);

        if (!$update && $this->userRepository->userExists($request->owner))
        {
            $response['message'] = "User already exists!";
            return $response;
        }

        DB::beginTransaction();

        // store owner user
        $owner = $this->userRepository->storePreregOwner($request->owner, "restaurant");
        // $this->userController->sendEmailReminder($owner);

        // store restaurant
        $user = app('Dingo\Api\Auth\Auth')->user();
        $saved_restaurants = [];
        foreach ($request->restaurants as $restaurant) {
            $restaurantObj = $this->restaurantRepository->saveAsPreregistration($restaurant, $owner, $user->id);
            $saved_restaurants[] = $restaurantObj->toArray();
        }

        DB::commit();

        $response['success'] = true;
        $response['message'] = $update ? "Preregistration update successful." : "Preregistration successful.";
        $response['owner'] = $owner->toArray();
        $response['restaurants'] = $saved_restaurants;

        return $response;
    }
}
