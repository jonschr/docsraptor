(function ($) {
	'use strict';

	if (typeof DocsRaptorLicense === 'undefined') {
		return;
	}

	var settings = DocsRaptorLicense;
	var state = settings.initialState || {};
	var busy = false;
	var inputDirty = false;

	var $page = $('#docsraptor-license-page');
	var $input = $('#docsraptor_license_key');
	var $spinner = $('#docsraptor-license-spinner');
	var $activity = $('#docsraptor-license-activity');
	var $noticeRoot = $('#docsraptor-license-notices');
	var $status = $('#docsraptor-license-status');
	var $statusLabel = $('#docsraptor-license-status-label');
	var $statusDescription = $('#docsraptor-license-status-description');
	var $instanceName = $('#docsraptor-license-instance-name');
	var $customerName = $('#docsraptor-license-customer-name');
	var $customerEmail = $('#docsraptor-license-customer-email');
	var $refreshButton = $('#docsraptor-license-refresh');
	var $deactivateButton = $('#docsraptor-license-deactivate');

	function setBusy(isBusy, message) {
		busy = isBusy;
		$page.find('button').prop('disabled', isBusy);
		$input.prop('disabled', isBusy);
		$spinner.toggleClass('is-active', isBusy);
		$activity.text(message || '').toggle(!!message);
	}

	function renderNotice(message, type) {
		if (!message) {
			$noticeRoot.empty();
			return;
		}

		$noticeRoot.html(
			'<div class="notice notice-' +
				(type === 'error' ? 'error' : 'success') +
				' is-dismissible"><p></p></div>'
		);
		$noticeRoot.find('p').text(message);
	}

	function toggleDescription($element, value) {
		$element.text(value || '').toggle(!!value);
	}

	function applyState(nextState) {
		state = nextState || {};
		inputDirty = false;

		$input.val(state.licenseKey || '');
		$input.attr('data-current-license-key', state.licenseKey || '');
		$status
			.removeClass('notice-success notice-warning')
			.addClass('notice-' + (state.statusClass || 'warning'));
		$statusLabel.text(state.statusLabel || '');
		toggleDescription($statusDescription, state.statusDescription || '');
		$instanceName.text(state.instanceName || '');
		$customerName.text(state.customerName || '');
		toggleDescription($customerEmail, state.customerEmail || '');
		$refreshButton.prop('disabled', busy || !state.canRefresh);
		$deactivateButton.prop('disabled', busy || !state.canDeactivate);
	}

	function handleResponse(response) {
		var payload = response && response.data ? response.data : {};

		if (payload.state) {
			applyState(payload.state);
		}

		if (response && response.success) {
			renderNotice('', 'success');
			toggleDescription($statusDescription, payload.message || '');
		} else {
			renderNotice(payload.message || '', 'error');
		}

		setBusy(false, '');
	}

	function request(action, extraData, busyMessage) {
		if (busy) {
			return;
		}

		setBusy(true, busyMessage);

		$.post(
			settings.ajaxUrl,
			$.extend(
				{
					action: action,
					nonce: settings.nonce,
				},
				extraData || {}
			)
		)
			.done(handleResponse)
			.fail(function () {
				setBusy(false, '');
				renderNotice(settings.strings.requestFailed, 'error');
			});
	}

	function maybeActivateCurrentKey() {
		var licenseKey = $.trim($input.val());
		var currentKey = $.trim($input.attr('data-current-license-key') || '');

		if (!licenseKey) {
			renderNotice(settings.strings.empty, 'error');
			return;
		}

		if (!inputDirty && licenseKey === currentKey) {
			return;
		}

		request(
			'docsraptor_license_activate',
			{ license_key: licenseKey },
			settings.strings.checking
		);
	}

	$input.on('input', function () {
		inputDirty =
			$.trim($input.val()) !==
			$.trim($input.attr('data-current-license-key') || '');
	});

	$input.on('blur change', maybeActivateCurrentKey);
	$input.on('keydown', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			maybeActivateCurrentKey();
		}
	});
	$input.on('paste', function () {
		window.setTimeout(maybeActivateCurrentKey, 250);
	});

	$refreshButton.on('click', function () {
		request('docsraptor_license_refresh', {}, settings.strings.refreshing);
	});
	$deactivateButton.on('click', function () {
		request('docsraptor_license_deactivate', {}, settings.strings.deactivating);
	});

	applyState(state);
})(jQuery);
