/**
 * Created by yonatom on 8/31/16.
 */

(function () {
    'use strict';

    angular
        .module('app.auth')
        .controller('RegisterController', RegisterController);
    /*@ngNoInject*/
    function RegisterController($state, AuthService, appConstant, $rootScope, TokenRestangular) {
        var vm = this;
        vm.registrationError = "";
        vm.register = register;
        vm.closeAlert = closeAlert;
        vm.loading = false;
        $rootScope.currentState = "register";
        function register(isValid){
            if (isValid){
                vm.loading = true;
                var user = {
                    "user" : {
                        "name": vm.name,
                        "email": vm.email,
                        "password": vm.password,
                        "lang": localStorage.getItem('current_language_code'),
                    },
                    lang: localStorage.getItem('current_language')
                };
                AuthService.register(user).then(function(response){

                    if (response.user_exists){
                        vm.registrationError = "User already exists!";
                        vm.loading = false;
                    }
                    var user = JSON.stringify(response.data);
                    localStorage.setItem('user', user);
                    console.log(user);
                    $rootScope.currentUser = JSON.parse(localStorage.getItem('user'));
                    var data = {
                        "grant_type": appConstant.grant_type,
                        "client_id": appConstant.client_id,
                        "client_secret": appConstant.client_secret,
                        "username": vm.email,
                        "password": vm.password
                    };
                    AuthService.authorize(data).then(function (response) {


                        localStorage.setItem('access_token', response.access_token);
                        localStorage.setItem('refresh_token', response.refresh_token);
                        TokenRestangular.setDefaultHeaders({Authorization: 'Bearer ' + localStorage.getItem('access_token')});

                        vm.loading = false;
                        $rootScope.$broadcast('orders-detail-changed');
                        $state.go("main.home");

                    });

                }, function(error){

                    AuthService.userExists(vm.email).then(function (response) {

                        if (response.success){

                            var data = {
                                "grant_type": appConstant.grant_type,
                                "client_id": appConstant.client_id,
                                "client_secret": appConstant.client_secret,
                                "username": vm.email,
                                "password": vm.password
                            };
                            AuthService.authorize(data).then(function (response) {

                                localStorage.setItem('access_token', response.access_token);
                                localStorage.setItem('refresh_token', response.refresh_token);
                                TokenRestangular.setDefaultHeaders({Authorization: 'Bearer ' + localStorage.getItem('access_token')});

                                $rootScope.loginLoading = false;
                                AuthService.login().then(function (response) {

                                    var user = JSON.stringify(response.user);
                                    localStorage.setItem('user', user);
                                    $rootScope.currentUser = JSON.parse(localStorage.getItem('user'));
                                    $rootScope.$broadcast('orders-detail-changed');
                                    $state.go("main.home");
                                }, function (error) {

                                });

                                vm.loading = false;


                            }, function (error) {

                            });
                        }
                    });
                    vm.loading = false;
                });
            }
        }

        function closeAlert(){
            vm.registrationError = "";
        }

        $('input[name="name"]').focus();
    }

})();