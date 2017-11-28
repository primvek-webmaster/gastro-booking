(function () {
    'use strict';
    angular
        .module('app.auth')
        .controller('RestaurantHandleUrlController', RestaurantHandleUrlController);
    /*@ngNoInject*/
    function RestaurantHandleUrlController($state, $location, $rootScope) {
        var infor = $location.absUrl().split("/");
        $state.go('main.restaurant_detail', {"restaurantId": infor[6], "type": infor[7]});
    }
})();