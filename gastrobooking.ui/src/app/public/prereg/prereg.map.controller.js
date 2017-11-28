/**
 * Created by yonatom on 11/6/17.
 */
(function (angular) {
    'use strict';

    angular
        .module('app.prereg')
        .controller('PreregMapController', PreregMapController);

    /*@ngNoInject*/
    function PreregMapController($uibModalInstance, vars) {
        var vm = this;
        console.log(vars, vm.locationpickerOptions);


        vm.locationpickerOptions = {
            location: {
                latitude: vars.latitude,
                longitude: vars.longitude
            },
            radius: 300,
            zoom: 15,
            markerDraggable: false,
        };



        vm.cancelModal = function () {
            $uibModalInstance.dismiss('cancel');
        }
    }

})(window.angular);