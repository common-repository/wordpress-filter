var changeTracking = false;
jQuery(document).ready(function(){
	jQuery("#wpfilter_tab_wrapper > ul").tabs();
	jQuery(".tabs-nav").tabs("disable", 2);
	jQuery("input").change(function() {changeTracking = true;});
	window.onbeforeunload=confirm_Departure;
});

function add_CatchAction(CatchAction, View) {
	var returnData = '';
	jQuery('#count_' + CatchAction).val(parseInt(jQuery('#count_' + CatchAction).val()) + 1);
	var count_CatchAction = jQuery('#count_' + CatchAction).val();

	jQuery.ajax({
		type: "POST",
		url: "./options-general.php?page=wordpress-filter/wp-filter.php",
		data: "wpfilter_action=wpfilter_process_ajax&process=new_" + CatchAction + "&count=" + count_CatchAction + "&view=" + View,
		success: function(returnData) {
			jQuery('#add_' + CatchAction + '_' + View).before(returnData);
		}
	});
}

function remove_CatchAction(CatchAction, Count, View) {
	jQuery('#' + CatchAction + '_' + Count + '_' + View).remove();
}

function remove_Filter(filterID) {
	var confirmRemove = confirm("Are you sure you want to delete this filter?");
	if (confirmRemove) {
		jQuery('#filter_' + filterID).fadeTo("slow", 0.33);
		jQuery.ajax({
			type: "POST",
			url: "./options-general.php?page=wordpress-filter/wp-filter.php",
			data: "wpfilter_action=wpfilter_process_ajax&process=remove_Filter&id=" + filterID,
			success: function(response) {
				jQuery('#filter_' + filterID).remove();
			}
		});
	}
}

function edit_Filter(filterID) {
	jQuery(".tabs-nav").tabs("enable", 2);
	jQuery(".tabs-nav").tabs("select", 2);
	jQuery('#edit_Filter').html('<img src="../wp-content/plugins/wordpress-filter/images/loading.gif" />');
	jQuery.ajax({
		type: "POST",
		url: "./options-general.php?page=wordpress-filter/wp-filter.php",
		data: "wpfilter_action=wpfilter_process_ajax&process=edit_Filter&id=" + filterID,
		success: function(response) {
			jQuery('#edit_Filter').html(response);
			jQuery("input").change(function() {
				changeTracking = true;
			});
		}
	});
}

function confirm_Departure(e) {
	if (changeTracking) {
		if(!e) e = window.event;
		//e.cancelBubble is supported by IE - this will kill the bubbling process.
		e.cancelBubble = true;
		e.returnValue = 'You sure you want to leave?'; //This is displayed on the dialog

		//e.stopPropagation works in Firefox.
		if (e.stopPropagation) {
			e.stopPropagation();
			e.preventDefault();
		}
	}
}

function validate_AddFilter() {
	var emptyInput = false;
	var emptySelect = false;

	var i = 0;
	jQuery('.inputValidate_addScreen').each( function() {
		if (jQuery('.inputValidate_addScreen:eq(' + i + ')').val() == '') { emptyInput = true; }
		i++;
	});
	var i = 0;
	jQuery('.selectValidate_addScreen').each( function() {
		if (jQuery('.selectValidate_addScreen:eq(' + i + ')').val() == '') { emptySelect = true; }
		i++;
	});

	if (emptyInput || emptySelect) {
		alert('All fields are required.');
		return false;
	}
	changeTracking = false;
	return true;
}

function validate_EditFilter() {
	var emptyInput = false;
	var emptySelect = false;

	var i = 0;
	jQuery('.inputValidate_editScreen').each( function() {
		if (jQuery('.inputValidate_editScreen:eq(' + i + ')').val() == '') { emptyInput = true; }
		i++;
	});
	var i = 0;
	jQuery('.selectValidate_editScreen').each( function() {
		if (jQuery('.selectValidate_editScreen:eq(' + i + ')').val() == '') { emptySelect = true; }
		i++;
	});

	if (emptyInput || emptySelect) {
		alert('All fields are required.');
		return false;
	}
	changeTracking = false;
	return true;
}

function validate_ListFilter() {
	var emptyCheckbox = true;
	var emptySelect = false;

	var i = 0;
	jQuery('.filterSelector').each( function() {
		if (jQuery('.filterSelector:eq(' + i + ')').attr('checked')) { emptyCheckbox = false; }
		i++;
	});
	if (emptyCheckbox) {
		alert('At least one filter must be chosen.');
		return false;
	}

	var i = 0;
	if (jQuery('#bulk_EditFilters').val() == '') {
		alert('Please choose a bulk operation.');
		return false;
	}

	changeTracking = false;
	return true;
}