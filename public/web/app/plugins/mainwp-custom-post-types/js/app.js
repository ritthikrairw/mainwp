(function ($) {
	// MainWp Custom Post Type Translations
	function _mwcptt(text, _var1, _var2, _var3) {
		if (text == undefined || text == '') return text;
		var strippedText = text.replace(/ /g, '_');
		strippedText = strippedText.replace(/[^A-Za-z0-9_]/g, '');

		if (strippedText == '') return text.replace('%1', _var1).replace('%2', _var2).replace('%3', _var3);

		if (mainwp_custom_post_type_translations == undefined) return text.replace('%1', _var1).replace('%2', _var2).replace('%3', _var3);
		if (mainwp_custom_post_type_translations[strippedText] == undefined) return text.replace('%1', _var1).replace('%2', _var2).replace('%3', _var3);

		return mainwp_custom_post_type_translations[strippedText].replace('%1', _var1).replace('%2', _var2).replace('%3', _var3);
		return text;
	}

	// For all generic errors
	var mainwp_custom_post_type_generic_error = function (message) {
		return _mwcptt("Generic error: ") + message;
	};

	var mainwp_custom_post_type_display_error = function (message) {
		show_error('custom_post_type_error', message);
	};

	var mainwp_custom_post_type_clear_error = function () {
		mainwp_custom_post_type_hide_error('custom_post_type_error');
		mainwp_custom_post_type_hide_error('custom_post_type_message');
	}

	mainwp_custom_post_type_hide_error = function ( id ) {
		var idElement = jQuery( '#' + id );
		idElement.html( "" );
		idElement.hide();
	};
	
	var mainwp_custom_post_type_display_message = function (message) {
		show_error('custom_post_type_message', message);
	};

	/**
	 * Check each ajax request using this function
	 * We know if something goes wrong
	 **/
	var mainwp_custom_post_type_check_error_in_request = function (data, message, display) {
		if (data.constructor === {}.constructor) {
			if ('success' in data) {
				return true;
			} else if ('error' in data) {
				if (display === undefined) {
					mainwp_custom_post_type_display_error(data.error);
					return false;
				} else {
					return data.error
				}
			} else {
				if (display === undefined) {
					mainwp_custom_post_type_display_error(mainwp_custom_post_type_generic_error(message));
					return false;
				} else {
					return mainwp_custom_post_type_generic_error(message);
				}
			}
		}
		else {
			if (display === undefined) {
				mainwp_custom_post_type_display_error(mainwp_custom_post_type_generic_error(message));
				return false;
			} else {
				return mainwp_custom_post_type_generic_error(message);
			}
		}
	};

	// We need this in order to $_POST and $_GET work properly in PHP
	angular.module('httpPostFix', [], function ($httpProvider) {
		// Use x-www-form-urlencoded Content-Type
		$httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';

		// Override $http service's default transformRequest
		$httpProvider.defaults.transformRequest = [function (data) {
			/**
			 * The workhorse; converts an object to x-www-form-urlencoded serialization.
			 * @param {Object} obj
			 * @return {String}
			 */
			var param = function (obj) {
				var query = '';
				var name, value, fullSubName, subName, subValue, innerObj, i;

				for (name in obj) {
					value = obj[name];

					if (value instanceof Array) {
						for (i = 0; i < value.length; ++i) {
							subValue = value[i];
							fullSubName = name + '[' + i + ']';
							innerObj = {};
							innerObj[fullSubName] = subValue;
							query += param(innerObj) + '&';
						}
					}
					else if (value instanceof Object) {
						for (subName in value) {
							subValue = value[subName];
							fullSubName = name + '[' + subName + ']';
							innerObj = {};
							innerObj[fullSubName] = subValue;
							query += param(innerObj) + '&';
						}
					}
					else if (value !== undefined && value !== null) {
						query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
					}
				}

				return query.length ? query.substr(0, query.length - 1) : query;
			};

			return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
		}];
	});

	var app = angular.module('ngCustomPostyTypeApp', ['httpPostFix', 'ngSanitize', 'ngTasty']);

	app.factory("customPostTypeRequest", function ($http) {
		return {
			// Get all values for tables
			getAll: function (type, params) {
				return $http.post(ajaxurl + "?" + params, {
					'action': 'mainwp_custom_post_type_list',
					'wp_nonce': mainwp_custom_post_type_security_nonce ['list'],
					'type': type
				}).error(function () {
					mainwp_custom_post_type_display_error(mainwp_custom_post_type_generic_error("customPostTypeRequest.error"));
				});
			}
		};
	});

	app.controller('ngCustomPostTypeController', function ($scope, $http, $q, $sanitize, customPostTypeRequest, $compile) {
		angular.element('#ngCustomPostyTypeId').show();

		// Table init
		$scope.key_theme = {
			'loadOnInit': true
		};

		$scope.key_filter = {
			'name': ''
		}

		$scope.init_get_key = {
			'count': 1000,
			'page': 1,
			'sortBy': 'name',
			'sortOrder': 'dsc'
		};

		$scope.reload_key_callback = function () {
		};

		$scope.reload_key = function () {
			$scope.reload_key_callback();
		};

		$scope.get_key = function (params, paramsObj) {
			return customPostTypeRequest.getAll( 'entry', params).then( function (d) {
				d = d.data;
				var rows;
				if (mainwp_custom_post_type_check_error_in_request(d, "get_all_wrapper")) {
					if (d.data.entries.length == 0) {
						rows = [ { 'name' : 'No custom post types detected.' } ];
					}
					else {
						rows = d.data.entries;
					}

					return {
						'rows': rows,
						'header': [{"key": "checkbox", "name": "Custom Post Type"}, {"key": "add", "name": "Add New"}]
					}
				}
			});
		};

		$scope.send_to_child = function (post_id, selected_sites, selected_groups, selected_categories, post_only_existing) {
			mainwp_custom_post_type_clear_error();

			if (selected_sites.length == 0 && selected_groups.length == 0) {
				mainwp_custom_post_type_display_error(_mwcptt('Please select websites or a groups in the Select Sites box.'));
				return;
			}

			// Get websites id's and urls
			$http.post(ajaxurl, {
				'action': 'mainwp_custom_post_type_send_to_child',
				'sites': selected_sites,
				'groups': selected_groups,
				'id': post_id,
				'wp_nonce': mainwp_custom_post_type_security_nonce ['send_to_child']
			}).success(function (d) {
				if (mainwp_custom_post_type_check_error_in_request(d, "send_to_child")) {
					$scope.send_to_child_ids = d.data.ids;
					$scope.send_to_child_urls = d.data.urls;
					$scope.send_to_child_entries = d.data.entries;
					$scope.send_to_child_entries_names = d.data.entries_names;

					angular.element(document.getElementById('mainwp-cpt-syncing-message')).empty();

					if ($scope.send_to_child_ids.length == 0) {
						angular.element(document.getElementById('mainwp-cpt-syncing-message')).append('<div class="ui yellow message">' + _mwcptt('No websites to publish to.') + '</div>');
						return;
					}

					if ($scope.send_to_child_entries.length == 0) {
						angular.element(document.getElementById('mainwp-cpt-syncing-message')).append('<div class="ui yellow message">' + _mwcptt('No posts to publish.') + '</div>');
						return;
					}

					var syncing_total = $scope.send_to_child_ids.length * $scope.send_to_child_entries.length;

					var send_to_child_promise = $scope.send_to_child_step_2(0, selected_categories, post_only_existing);

					angular.element(document.getElementById('mainwp-cpt-syncing-message')).append('<div id="mainwp-cpt-syncing-message-list" class="ui divided list"></div>');

					for (i = 1; i < syncing_total; ++i) {
						send_to_child_promise = send_to_child_promise.then(function (ii) {
							return $scope.send_to_child_step_2(ii, selected_categories, post_only_existing);
						});
					}

					send_to_child_promise.then(function () {
						angular.element(document.getElementById('mainwp-cpt-syncing-message')).append('<div class="ui green message">' + _mwcptt('Child Sites have been updated') + '</div>');
					});

				}
			}).error(function () {
				mainwp_custom_post_type_display_error(mainwp_custom_post_type_generic_error("send_to_child.error"))
			});
		};

		$scope.send_to_child_step_2 = function (i, selected_categories, post_only_existing) {
			var website_i = i % $scope.send_to_child_ids.length;
			var website_id = $scope.send_to_child_ids[website_i];
			var entry_i = Math.trunc(i / $scope.send_to_child_ids.length);
			var entry_id = $scope.send_to_child_entries[entry_i];

			angular.element(document.getElementById('mainwp-cpt-synchronization-modal')).modal('show');

			angular.element(document.getElementById('mainwp-cpt-syncing-message-list')).append('<div class="item">' + $sanitize($scope.send_to_child_urls[website_i] + " - " + $scope.send_to_child_entries_names[entry_i]) + '<span class="right floated" id="send_to_child_tr_id_' + i + '" ><i class="notched circle loading icon"></i> ' + _mwcptt("Please wait...") + '</span></div>');

			var deferred = $q.defer();

			$http.post(ajaxurl, {
				'action': 'mainwp_custom_post_type_send_to_child_step_2',
				'wp_nonce': mainwp_custom_post_type_security_nonce ['send_to_child_step_2'],
				'selected_categories': selected_categories,
				'post_only_existing': post_only_existing,
				'website_id': website_id,
				'id': entry_id
			}).success(function (d) {
				var message = mainwp_custom_post_type_check_error_in_request(d, "send_to_child_step_2", 1);
				if (message !== true) {
					angular.element(document.getElementById('send_to_child_tr_id_' + i)).replaceWith('<span class="right floated"><i class="red times icon"></i> ' + $sanitize(message) + '</span>');
				} else {
					var output_content = "OK";
					angular.element(document.getElementById('send_to_child_tr_id_' + i)).replaceWith($compile(output_content)($scope));
				}
				deferred.resolve(i + 1);
			}).error(function () {
				var output_content = '<span class="right floated"><i class="red times icon"></i> send_to_child_step_2.error</span>';
				angular.element(document.getElementById('send_to_child_tr_id_' + i)).replaceWith($compile(output_content)($scope));
				deferred.resolve(i + 1);
			});

			return deferred.promise;
		};

		// END
	});


})(jQuery);
