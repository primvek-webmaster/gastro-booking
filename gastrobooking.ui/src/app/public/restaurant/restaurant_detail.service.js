/**
 * Created by Thomas on 10/10/2016.
 */

(function () {
    'use strict';

    angular
        .module('app.restaurant')
        .service('RestaurantDetailService', RestaurantDetailService);
    /*@ngNoInject*/
    function RestaurantDetailService($rootScope, $state, TokenRestangular) {
        var service = {
            getRestaurants: getRestaurants,
            getRestaurantDetail: getRestaurantDetail,
            getMenuTypes : getMenuTypes,
            addMenuListToCart: addMenuListToCart,
            getMenuOfTheDay: getMenuOfTheDay,
            getGroupAndSubGroupID: getGroupAndSubGroupID,
            setFoodImage: setFoodImage,
            deleteFoodImage: deleteFoodImage,
            confirmSelf:confirmSelf,
            getMenuList: getMenuList,
            getSetting: getSetting,
            currentRequests: currentRequests,
            countMissingBooking: countMissingBooking        
        };

        return service;


    

        function currentRequests(restaurant_id, serve_at){
          return TokenRestangular.all('restaurant/current-requests/' + restaurant_id+'/'+serve_at).customGET('');
        }

        function countMissingBooking(restaurant_id){
          return TokenRestangular.all('restaurant/count-missing-booking/' + restaurant_id).customGET('');
        }

        function deleteFoodImage(menulistId){
          var fd = new FormData();

          fd.append('file', null, null);
          var item_id=menulistId;console.log('me',item_id);
          var item_type='delete';
          return TokenRestangular.all('photo/' + item_id + '/' + item_type)
              .withHttpConfig({transformRequest: angular.identity})
              .customPOST(fd, '', undefined, {'Content-Type': undefined});
        }

        function setFoodImage(file,menulistId){
          var fd = new FormData();

          fd.append('file', file, file.name);
          console.log('me');
          var item_id=menulistId;
          var item_type='items';
          console.log('gggg',item_id);
          return TokenRestangular.all('photo/' + item_id + '/' + item_type)
              .withHttpConfig({transformRequest: angular.identity})
              .customPOST(fd, '', undefined, {'Content-Type': undefined});
        }

        function getRestaurants(restId) {
            return TokenRestangular.all('restaurant/'+ restId).customGET('');
        }
        function confirmSelf(restId) {
          return TokenRestangular.all('confirmSelf/'+restId).customGET('');
        }
        function getRestaurantDetail(restaurantId, type) {
            return TokenRestangular.all('restaurant/'+restaurantId+'/detail/'+type).customGET('');
        }

        function getMenuTypes(restaurantId){

            return TokenRestangular.all('restaurant/'+restaurantId+'/organized_menu').customGET('');
        }

        function getGroupAndSubGroupID(restId){

            return TokenRestangular.all('restaurant/' + restId + '/menu_group_and_subgroup_id').customGET('');
        }

        function addMenuListToCart(data){

            data.orders_detail.date = moment(data.orders_detail.date).format();
            return TokenRestangular.all('orders_detail').customPOST(data);
        }

        function getMenuOfTheDay(restaurantId, date, time){

            var hour = date.getHours().toString().length == 1 ? "0" + date.getHours().toString() : date.getHours().toString();
            var minutes = date.getMinutes().toString().length == 1 ? "0"  + date.getMinutes().toString() : date.getMinutes().toString();
            date = date.getDay() == 0 ? 7 : date.getDay();
            time = time = hour + ":" + minutes + ":00";

            return TokenRestangular.all('restaurant/'+restaurantId+'/menu_lists?is_day_menu=true&date=' + date + '&time=' + time).customGET('');
        }

        function getMenuList(id) {
            return TokenRestangular.all('restaurant/getMenuList').customPOST(id);
        }

        function getSetting(currency) {
            return TokenRestangular.all('restaurant/getSetting').customPOST(currency);
        }

    }

})();
