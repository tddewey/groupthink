(function (window, $, undefined) {

	/**
	 * Initialize. Specifically, event bindings.
	 */
	var init = function () {

		// Vote Up button
		$(document.querySelectorAll('.tdd-groupthink-yay')).on('click', function (e) {
			$(this).addClass('spinner');
			voteUp(e.target.getAttribute('data-postid'));
		});

		// Vote Down button
		$(document.querySelectorAll('.tdd-groupthink-nay')).on('click', function (e) {
			$(this).addClass('spinner');
			voteDown(e.target.getAttribute('data-postid'));
		});

		// Meh button
		$(document.querySelectorAll('.tdd-groupthink-meh')).on('click', function (e) {
			$(this).addClass('spinner');
			voteMeh(e.target.getAttribute('data-postid'));
		});


	};

	/**
	 * Wrapper around jQuery AJAX
	 * @param action string Wordpress action. Will determine what function gets run.
	 * @param data object Pass arbitrary object data back to the handler. Typically includes a postID
	 * @param callback string function name to pass the response back to.
	 */
	var ajax = function (action, data, callback) {

		var nonce = groupthink.nonce;

		$.post(groupthink.ajaxurl, {
			action     : action,
			data       : data,
			_ajax_nonce: nonce
		}, callback, 'json');

	};

	/**
	 * Add a vote up to postID
	 * @param postID integer
	 */
	var voteUp = function (postID) {
		addIDToCookie(postID);
		ajax('gt_vote_up', { postID: postID }, function () {
			showResults( postID );
			showNext( postID );
			$(document.querySelectorAll('.tdd-groupthink-yay').removeClass('spinner'));
		});

	};

	/**
	 * Add a vote down to postID
	 * @param postID integer
	 */
	var voteDown = function (postID) {
		addIDToCookie(postID);
		ajax('gt_vote_down', { postID: postID }, function(){
			showResults(postID);
			showNext( postID );
			$(document.querySelectorAll('.tdd-groupthink-nay').removeClass('spinner'));
		});
	};

	/**
	 * Add a 'meh' vote to postID
	 * @param postID
	 */
	var voteMeh = function (postID) {
		addIDToCookie(postID);
		ajax('gt_vote_meh', { postID: postID }, function(){
			showResults(postID);
			showNext( postID );
			$(document.querySelectorAll('.tdd-groupthink-meh').removeClass('spinner'));
		});
	};


	/**
	 * Makes an AJAX call that will populate the groupthink results div on page.
	 * No AJAX call made if there is no results div.
	 * Use the PHP template tag function tdd_groupthink_results()
	 * @see tdd_groupthink_results
	 */
	var showResults = function (postID) {
		var resultsDiv = $(document.querySelectorAll('.tdd-groupthink-results'));
		if (resultsDiv.length < 1) {
			return false;
		}

		ajax('gt_results', { postID: postID }, function(response){
			if ( response.status === 'success' ) {
				resultsDiv.html( response.status_msg );
			}
		});

	};

	/**
	 * Fill in the URL for the next post. Requires an AJAX call to see what's left.
	 * @param postID
	 */
	var showNext = function( postID ){
		// Get the post IDs that have already been voted on. we'll ignore those.
		var existingIds = getIdsFromCookie();

		// Make an ajax request for the next page, passing along this data.
		ajax('gt_next_page', {
			'existingIds': existingIds + ',' + postID
		}, function (result) {
			$(document.querySelectorAll('.tdd-groupthink-next')).attr('href',result.status_msg).show();
		});

	};

	/**
	 * Generic cookie handler
	 */
	var cookie = {

		/**
		 * Sets a browser cookie
		 *
		 * Path is automatically set.
		 *
		 * @param key string cookie key to store
		 * @param value mixed value to store
		 * @param expirationInSeconds integer when the cookie should expire
		 * @return bool
		 */
		set: function (key, value, expirationInSeconds) {
			// Default to 3 months (in seconds)
			expirationInSeconds = expirationInSeconds | 100;

			if (key === undefined || value === undefined) {
				return false;
			}

			// Set up the JS date
			var expires = new Date();
			expires.setDate(expires.getDate() + expirationInSeconds / 86400); // seconds in day

			window.console.log(encodeURIComponent(key) + '=' + value + '; expires=' + expires.toUTCString());

			document.cookie = encodeURIComponent(key) + '=' + encodeURIComponent(value) + '; expires=' + expires.toUTCString() + '; path=/';

			return true;

		},

		/**
		 * Given a key, get the associated browser cookie value
		 * @param key string cookie key
		 * @return string browser cookie string
		 */
		get: function (key) {

			var re = new RegExp(key + "=([^;]+)");
			var value = re.exec(document.cookie);
			return (value != null) ? decodeURIComponent(value[1]) : null;

		}

	};

	/**
	 * Adds the given postID to the cookie
	 * @param postID
	 * @return bool
	 */
	var addIDToCookie = function (postID) {
		var existingIds = getIdsFromCookie();
		// @todo If this postID is already in the cookies, let's bail.

		return cookie.set('groupthink', existingIds + ',' + postID, 15778463); // six months out
	};

	/**
	 * Gets a list of postIDs out of the cookie
	 * @return integer PostID
	 */
	var getIdsFromCookie = function () {
		return cookie.get('groupthink');
	};

	init();
})(window, jQuery);