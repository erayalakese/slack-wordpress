jQuery(document).ready(function() {
	jQuery('input, select').prop('disabled', false);
	jQuery('.disabled> input, .disabled> select').prop('disabled', true);

	jQuery('.slack_admin_checkbox').change(function () {
	    if (this.checked) {
	        jQuery(this).next().next().next().find('input, select').prop('disabled', false);
	    } else {
	        jQuery(this).next().next().next().find('input, select').prop('disabled', true);
	    }
	});
});
