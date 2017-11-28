(function (angular) {
    'use strict';

    angular.module('directive.loading', []);

    angular
        .module('directive.loading')
        .directive('loading', loading);

    loading.$inject = [];

    /**
     * Display loading image
     *
     * @return {Object}
     */
    function loading() {
        return {
            restrict: 'E',
            replace: true,
            scope: {
                loading: '='
            },
            template: '<div ng-if="loading" class="col-md-12 text-center v-center-lg"> <img src="assets/images/loading.gif" alt="loading"> </div>'
        };
    }
})(window.angular);
