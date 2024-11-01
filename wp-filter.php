<?php
/* {
Plugin Name: WordPress Filter
Plugin URI: http://mattwalters.net/projects/wordpress-filter/
Description: WordPress Filter is a comprehensive post filtering & templating system.
Author: Matt Walters
Version: 1.4.1
Author URI: http://mattwalters.net/
} */

load_plugin_textdomain('msw_wp-filter', '/'.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/i18n', basename(dirname(__FILE__)).'/i18n');

function _a($arrayData) { // Debugging function
	echo "<hr><pre>";
	print_r($arrayData);
	echo "</pre><hr>";
	return true;
}

function _d($text = null) { // Debugging function
	global $debugCtr;
	$debugCtr++;
	echo "Debug: " . $text . " - " . $debugCtr . "<br />";
	return true;
}

function wpfilter_menu() { // Define admin menu
	add_options_page(__('WordPress Filter', 'msw_wp-filter'), __('WordPress Filter', 'msw_wp-filter'), 8, __FILE__, 'wpfilter_options_page');
}

function wpfilter_admin_processing() { // Form & Ajax processing
	if (isset($_POST['wpfilter_action'])) {
		switch ($_POST['wpfilter_action']) {
			case 'wpfilter_bulk_filterset':
				wpfilter_bulk_operations();
				break;
			case 'wpfilter_add_filter':
				wpfilter_add_filter();
				break;
			case 'wpfilter_process_ajax':
				wpfilter_process_ajax();
				break;
			case 'wpfilter_import_filter':
				wpfilter_import_filter();
				break;
			default:
				break;
		}
	}
}

function wpfilter_process_ajax() { // Process Ajax request
	if ($_POST['process'] == 'new_Catch' && $_POST['count'] != '') {
		$count = $_POST['count'];
		$catches = wpfilter_get_catches(); // Get array of Catches
		?>
		<tr id="catch_<?php echo $count; ?>_<?php echo $_POST['view'] ?>">
			<td><a href="javascript: remove_CatchAction('catch', <?php echo $count; ?>, '<?php echo $_POST['view'] ?>');" title="<?php _e('Remove Catch', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/remove.gif" border="0" alt="<?php _e('Remove Catch', 'msw_wp-filter'); ?>"></a></td>
			<td valign="middle">
				<select class="selectValidate_<?php echo $_POST['view'] ?>" id="catchObject[]" name="catchObject[]">
					<?php foreach ($catches as $catchTag => $catchName) { ?>
					<option value="<?php echo $catchTag; ?>"><?php echo $catchName; ?></option>
					<?php } ?>
				</select>
			</td>
			<td>=&gt;</td>
			<td valign="middle"><input class="inputValidate_<?php echo $_POST['view'] ?>" name="catchData[]" /></td>
			<td width="60%">&nbsp;</td>
		</tr>
		<?php
		exit;
	}
	if ($_POST['process'] == 'new_Action' && $_POST['count'] != '') {
		$count = $_POST['count'];
		$actions = wpfilter_get_actions(); // Get array of Actions
		?>
		<tr id="action_<?php echo $count; ?>_<?php echo $_POST['view'] ?>">
			<td><a href="javascript: remove_CatchAction('action', <?php echo $count; ?>, '<?php echo $_POST['view'] ?>');" title="<?php _e('Remove Action', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/remove.gif" border="0" alt="<?php _e('Remove Action', 'msw_wp-filter'); ?>"></a></td>
			<td valign="middle">
				<select class="selectValidate_<?php echo $_POST['view'] ?>" id="actionObject[]" name="actionObject[]">
					<?php foreach ($actions as $actionTag => $actionName) { ?>
					<option value="<?php echo $actionTag; ?>"><?php echo $actionName; ?></option>
					<?php } ?>
				</select>
			</td>
			<td>=&gt;</td>
			<td valign="middle"><input class="inputValidate_<?php echo $_POST['view'] ?>" name="actionData[]" /></td>
			<td width="60%">&nbsp;</td>
		</tr>
		<?php
		exit;
	}
	if ($_POST['process'] == 'remove_Filter' && $_POST['id'] != '') {
		$filterSet = unserialize(get_option('wpfilter_filterSet'));
		if($filterSet) { $filterSet = wpfilter_order_FilterSet($filterSet); }
		update_option('wpfilter_filterSet', serialize($filterSet));
		exit;
	}
	if ($_POST['process'] == 'edit_Filter' && $_POST['id'] != '') {
		wpfilter_render_edit_form($_POST['id']);
		exit;
	}
}

function wpfilter_bulk_operations() { // Process bulk operations from Filter List view
	switch ($_POST['bulk_EditFilters']) {
		case 'delete':
			unset($filterSet);
			$filterSet = unserialize(get_option('wpfilter_filterSet'));
			$filterSet = wpfilter_order_FilterSet($filterSet);
			foreach ($_POST['filterSelector'] as $deleteFilter) { // Remove filters from array
				foreach ($filterSet as $k=>$filter){
					if ($filterSet[$k]['ID'] == $deleteFilter) { unset($filterSet[$k]); }
				}
			}
			$filterSet = wpfilter_order_FilterSet($filterSet);
			update_option('wpfilter_filterSet', serialize($filterSet));
			break;
		case 'export':
			unset($filterSet);
			$exportFilterSet = array();
			$filterSet = unserialize(get_option('wpfilter_filterSet'));
			$filterSet = wpfilter_order_FilterSet($filterSet);
			foreach ($_POST['filterSelector'] as $exportFilter) { // Create array of Filters to be exported
				foreach ($filterSet as $k=>$filter){
					if ($filterSet[$k]['ID'] == $exportFilter) {
						$exportFilterSet[$k] = $filterSet[$k];
					}
				}
			}
			$exportFilterSet = wpfilter_order_FilterSet($exportFilterSet);
			$wpfilter_exportFilterSet = unserialize(get_option('wpfilter_exportFilterSet'));
			if (!$wpfilter_exportFilterSet) { // If no option was returned, create the option
				add_option('wpfilter_exportFilterSet');
				$wpfilter_exportFilterSet = array();
			}
			update_option('wpfilter_exportFilterSet', serialize($exportFilterSet));
			break;
		default:
			break;
	}
}

function wpfilter_import_filter() { // Import filter from $_POST
	$new_FilterSet = array();
	$current_Filters = array();
	$filtersValid = true;
	$import_FilterSet = stripslashes($_POST['import_FilterSet']);
	$import_FilterSet = unserialize(unserialize($import_FilterSet));

	if(is_array($import_FilterSet)) {
		foreach ($import_FilterSet as $k=>$filter) {
			if (wpfilter_validate_filter($filter)) {
				unset($import_FilterSet[$k]['ID']);
				$new_FilterSet[$k] = $filter;
			} else {
				$filtersValid = false;
			}
		}
		$current_Filters = unserialize(get_option('wpfilter_filterSet'));
		$current_Filters = wpfilter_order_FilterSet($current_Filters);

		if (!$current_Filters) { // If no option was returned, create the option
			add_option('wpfilter_filterSet');
			$current_Filters = array();
		}
		$filterCount = count($current_Filters);
		foreach ($import_FilterSet as $k=>$filter) {
			$filterCount++;
			$current_Filters[$filterCount] = $filter;
			$current_Filters[$filterCount]['ID'] = time();
		}
		$current_Filters = wpfilter_order_FilterSet($current_Filters);
		update_option('wpfilter_filterSet', serialize($current_Filters));
	} else {
		$filtersValid = false;
	}
	if (!$filtersValid) {
		header('Location: ./options-general.php?page=wordpress-filter/wp-filter.php&action=import_filter&error=invalid');
		exit;
	}
}

function wpfilter_add_filter() { // Add filter from $_POST data
	unset($filterSet);
	$filterSet = unserialize(get_option('wpfilter_filterSet'));
	$filterSet = wpfilter_order_FilterSet($filterSet);

	if (!$filterSet) { // If no option was returned, create the option
		add_option('wpfilter_filterSet');
		$filterSet = array();
	}

	if ($_POST['wpfilter_ID'] != '') { // This is an edit request, so remove it and we'll add it back in
		foreach ($filterSet as $k=>$filter) {
			if ($filter['ID'] == $_POST['wpfilter_ID']) { unset($filterSet[$k]); }
		}
	}

	$filterCount = count($filterSet);
	$filterCount = $filterCount + 2;

	foreach ($_POST['catchObject'] as $k=>$catch) { // Set up key=>value for catches
		if ($_POST['catchData'][$k] != '') {
			$filterSet[$filterCount]['catch'][$catch] = $_POST['catchData'][$k];
		}
	}

	foreach ($_POST['actionObject'] as $k=>$action) { // Set up key=> value for actions
		if ($_POST['actionData'][$k] != '') {
			$filterSet[$filterCount]['action'][$action] = $_POST['actionData'][$k];
			if ($action == 'append_tags' || $action == 'remove_tags' || $action == 'append_cats' || $action == 'remove_cats') {
				$_POST['actionData'][$k] = str_replace(', ', ',', $_POST['actionData'][$k]);
				$_POST['actionData'][$k] = str_replace('&', '&amp;', $_POST['actionData'][$k]);
				$filterSet[$filterCount]['action'][$action] = explode(',', $_POST['actionData'][$k]);
			}
		}
	}

	if ($_POST['wpfilter_ID'] != '') { $filterSet[$filterCount]['ID'] =  $_POST['wpfilter_ID']; } // Give it back its ID
	if ($filterSet[$filterCount]['ID'] == '') { $filterSet[$filterCount]['ID'] = time(); } // Assign unique ID if needed

	update_option('wpfilter_filterSet', serialize($filterSet));
}

function wpfilter_render_edit_form($filterID) { // Render edit form for given filter ID
	$catches = wpfilter_get_catches();
	$actions = wpfilter_get_actions();
	$edit_Filter = array();
	$filterSet = unserialize(get_option('wpfilter_filterSet'));

	if ($filterSet) {
		foreach($filterSet as $filter) {
			if ($filter['ID'] == $filterID) { 
				$edit_Filter = $filter;
			}
		}
		wp_nonce_field('update-options');
		?>
		<h4><?php _e('If these conditions are met:', 'msw_wp-filter'); ?></h4>
		<table cellpadding="0" cellspacing="5px" border="0" class="form-table">
			<tr>
				<td>&nbsp;</td>
				<td><?php _e('Condition(s) to be met:', 'msw_wp-filter'); ?></td>
				<td>&nbsp;</td>
				<td><?php _e('Condition Data:', 'msw_wp-filter'); ?></td>
				<td></td>
			</tr>
			<?php 
			$catchCtr = 0;
			foreach($edit_Filter['catch'] as $catchObject=>$catchData) {
				$catchCtr++;
			?>
			<tr id="catch_<?php echo $catchCtr; ?>_editScreen">
				<td width="30px"><a href="javascript: remove_CatchAction('catch', <?php echo $catchCtr; ?>, 'editScreen');" title="<?php _e('Remove Catch', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/remove.gif" border="0" alt="<?php _e('Remove Catch', 'msw_wp-filter'); ?>"></a></td>
				<td valign="middle">
					<select class="selectValidate_editScreen" id="catchObject[]" name="catchObject[]">
						<?php foreach ($catches as $catchTag => $catchName) { ?>
						<option value="<?php echo $catchTag; ?>"<?php if ($catchTag == $catchObject) { echo ' selected="selected"'; } ?>><?php echo $catchName; ?></option>
						<?php } ?>
					</select>
				</td>
				<td>=&gt;</td>
				<td valign="middle"><input class="inputValidate_editScreen" name="catchData[]" value='<?php echo stripslashes($catchData); ?>' /></td>
				<td width="60%">&nbsp;</td>
			</tr>
			<?php } ?>
			<tr id="add_Catch_editScreen">
				<td width="30px" valign="middle"><a href="javascript: add_CatchAction('Catch', 'editScreen');" title="<?php _e('Add Filter', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/add.gif" border="0" alt="<?php _e('Add Filter', 'msw_wp-filter'); ?>"></a></td>
				<td valign="middle" colspan="3"><?php _e('Add Catch', 'msw_wp-filter'); ?></td>
				<td width="60%">&nbsp;</td>
			</tr>
		</table>
		<h4><?php _e('Then perform these actions:', 'msw_wp-filter'); ?></h4>
		<table cellpadding="0" cellspacing="5px" border="0" class="form-table">
			<tr>
				<td>&nbsp;</td>
				<td><?php _e('Action(s) to be performed:', 'msw_wp-filter'); ?></td>
				<td>&nbsp;</td>
				<td><?php _e('Action Data:', 'msw_wp-filter'); ?></td>
				<td></td>
			</tr>
			<?php
				$actionCtr = 0;
				foreach($edit_Filter['action'] as $actionObject=>$actionData) {
					$actionCtr++;
					if ($actionObject == 'append_tags' || $actionObject == 'remove_tags' || $actionObject == 'append_cats' || $actionObject == 'remove_cats') {
						$actionData = implode(',', $actionData);
					}
			?>
			<tr id="action_<?php echo $actionCtr; ?>_editScreen">
				<td width="30px"><a href="javascript: remove_CatchAction('action', <?php echo $actionCtr; ?>, 'editScreen');" title="<?php _e('Remove Action', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/remove.gif" border="0" alt="<?php _e('Remove Filter', 'msw_wp-filter'); ?>"></a></td>
				<td valign="middle">
					<select class="selectValidate_editScreen" id="actionObject[]" name="actionObject[]">
						<?php foreach ($actions as $actionTag => $actionName) { ?>
						<option value="<?php echo $actionTag; ?>"<?php if ($actionTag == $actionObject) { echo ' selected="selected"'; } ?>><?php echo $actionName; ?></option>
						<?php } ?>
					</select>
				</td>
				<td>=&gt;</td>
				<td valign="middle"><input class="inputValidate_editScreen" name="actionData[]" value='<?php echo stripslashes($actionData); ?>' /></td>
				<td width="60%">&nbsp;</td>
			</tr>
			<?php } ?>
			<tr id="add_Action_editScreen">
				<td width="30px" valign="middle"><a href="javascript: add_CatchAction('Action', 'editScreen');" title="<?php _e('Add Action', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/add.gif" border="0" alt="<?php _e('Add Action', 'msw_wp-filter'); ?>"></a></td>
				<td valign="middle" colspan="3"><?php _e('Add Action', 'msw_wp-filter'); ?></td>
				<td width="60%">&nbsp;</td>
			</tr>
		</table>
		<table cellpadding="0" cellspacing="5px" border="0" class="form-table">
			<tr>
				<td valign="middle"><hr/></td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td valign="middle">
					<?php _e('* All fields are required.', 'msw_wp-filter'); ?><br />
					<?php _e('* All conditions must be met for actions to be applied.', 'msw_wp-filter'); ?><br />
					<?php _e('* Fields requiring tags should have the tags comma separated (tag1, tag2, etc).', 'msw_wp-filter'); ?>
				</td>
				<td width="60%">&nbsp;</td>
			</tr>
		</table>
		<p>
			<input type="submit" name="save_filter" value="Save Filter" id="save_filter" />
			<input type="hidden" name="wpfilter_action" value="wpfilter_add_filter" id="wpfilter_action" />
			<input type="hidden" name="wpfilter_ID" value="<?php echo $edit_Filter['ID']; ?>" id="wpfilter_ID" />
		</p>
		<?php
	} else {
		_e('The specified filter could not be found.  Please try again.', 'msw_wp-filter');
	}
}

function wpfilter_options_page() { // Display admin menu
	echo '<div class="wrap">';
	echo '<h2>' . __('WordPress Filter Options', 'msw_wp-filter') . '</h2>';
	echo '</div>';
		$catches = wpfilter_get_catches(); // Get array of Catches
		$actions = wpfilter_get_actions(); // Get array of Actions
		$filterSet = unserialize(get_option('wpfilter_filterSet'));
	?>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.5.3/jquery-ui.min.js" type="text/javascript"></script>
	<script src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/js/wp-filter_admin.js" type="text/javascript"></script>
	<link rel="stylesheet" href="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/css/wp-filter_admin.css" type="text/css" />
	<div id="wpfilter_tab_wrapper">
		<ul class="tabs-nav">
			<li><a href="#wpf-tab1"><span><?php _e('Current Filters', 'msw_wp-filter'); ?></span></a></li>
			<li><a href="#wpf-tab2"><span><?php _e('Add Filter', 'msw_wp-filter'); ?></span></a></li>
			<li><a href="#wpf-tab3"><span><?php _e('Edit Filter', 'msw_wp-filter'); ?></span></a></li>
			<li><a href="#wpf-tab4"><span><?php _e('Import Filter', 'msw_wp-filter'); ?></span></a></li>
		</ul>
		<div id="tabs-content">
			<div id="wpf-tab1">
				<h3><?php _e('Current Filters', 'msw_wp-filter'); ?></h3>
				<?php
					if ($_GET['action'] == 'import_filter' and $_GET['error'] == 'invalid') {
						?>
						<div id="import_error"><?php _e('Sorry, the filter(s) you tried to import are not valid.', 'msw_wp-filter'); ?></div>
						<?php
					}
				?>
				<form name="form_FilterList" id="form_FilterList" method="post" action="./options-general.php?page=wordpress-filter/wp-filter.php#wpf-tab1" onsubmit="javascript: return validate_ListFilter();">
					<table cellpadding="0" cellspacing="0" border="0" class="filterSet_list">
						<tr>
							<td>&nbsp;</td>
							<td class="wpf_tableheading"><strong><?php _e('If these conditions are met:', 'msw_wp-filter'); ?></strong></td>
							<td class="wpf_tableheading"><strong><?php _e('Then perform these actions:', 'msw_wp-filter'); ?></strong></td>
							<td>&nbsp;</td>
						</tr>
					<?php
					$rowCount = 0;
					if (count($filterSet) > 0 && $filterSet != '') {
					foreach ($filterSet as $filter) {
						$rowCount++;
						?>
						<tr id="filter_<?php echo $filter['ID'] ?>">
							<td<?php if ($rowCount == count($filterSet)) { echo ' class="last_row"'; } ?> valign="top">
								<input type="checkbox" name="filterSelector[]" value="<?php echo $filter['ID']; ?>" class="filterSelector">
								<a href="javascript: remove_Filter(<?php echo $filter['ID'] ?>);" title="<?php _e('Remove Filter', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/remove.gif" border="0" alt="<?php _e('Remove Filter', 'msw_wp-filter'); ?>"></a>
								<a href="javascript: edit_Filter(<?php echo $filter['ID'] ?>);" title="<?php _e('Edit Filter', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/edit.gif" border="0" alt="<?php _e('Edit Filter', 'msw_wp-filter'); ?>"></a>
							</td>
							<td<?php if ($rowCount == count($filterSet)) { echo ' class="last_row"'; } ?> valign="top">
							<ul class="catch_List">
								<?php
									foreach ($filter['catch'] as $catchObject=>$catchData) {
										echo '<li>' . $catches[$catchObject] . ' &#8674; ' . $catchData . '</li>';
									}
								?>
							</ul>
							</td>
							<td<?php if ($rowCount == count($filterSet)) { echo ' class="last_row"'; } ?> valign="top">
								<ul class="action_List">
								<?php
									foreach ($filter['action'] as $actionObject=>$actionData) {
										if ($actionObject == 'append_tags' || $actionObject == 'remove_tags' || $actionObject == 'append_cats' || $actionObject == 'remove_cats') { // Implode tags to comma separated
											$actionData = implode(', ', $actionData);
										}
										echo '<li>' . $actions[$actionObject] . ' &#8674; ' . stripslashes(htmlentities(str_replace('&amp;','&',$actionData))) . '</li>';
									}
								?>
								</ul>
							</td>
							<td width="10%"<?php if ($rowCount == count($filterSet)) { echo ' class="last_row"'; } ?>>&nbsp;</td>
						</tr>
						<?php } ?>
						<tr>
							<td colspan="4" class="last_row">
								<select name="bulk_EditFilters" id="bulk_EditFilters">
									<option value=""><?php _e('Bulk Options', 'msw_wp-filter'); ?></option>
									<option value="delete"><?php _e('Delete', 'msw_wp-filter'); ?></option>
									<option value="export"><?php _e('Export', 'msw_wp-filter'); ?></option>
								</select>
								<input type="hidden" name="wpfilter_action" value="wpfilter_bulk_filterset" id="wpfilter_action" />
								<input type="image" src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/ok.gif" name="Submit" value="Submit" id="wpfm_Submit">
							</td>
						</tr>
					</table>
				</form><br/>
				<?php $wpfilter_exportFilterSet = get_option('wpfilter_exportFilterSet'); ?>
				<?php if ($wpfilter_exportFilterSet && $_POST['bulk_EditFilters'] == 'export') { ?>
				<?php _e('Below is your filter export:', 'msw_wp-filter') ?><br/>
				<textarea style="width: 400px; height: 100px;"><?php echo serialize($wpfilter_exportFilterSet); ?></textarea><br/>
				<?php _e('This can be copied into a text editor and saved to be sent to someone else or used on another blog running WordPress Filter.', 'msw_wp-filter') ?>
				<?php 
					} 
					} else {
						?>
						<tr>
							<td colspan="4"><?php _e('You do not currently have any filters defined.', 'msw_wp-filter'); ?>
						</tr>
					</table>
				</form>
				<?php
					}
				?>
			</div>
			<div id="wpf-tab2">
				<form id="add_Filter" name="add_Filter" method="post" action="./options-general.php?page=wordpress-filter/wp-filter.php#wpf-tab1" onsubmit="javascript: return validate_AddFilter();">
					<?php wp_nonce_field('update-options'); ?>
					<h3><?php _e('Add Filter', 'msw_wp-filter'); ?></h3>
					<h4><?php _e('If these conditions are met:', 'msw_wp-filter'); ?></h4>
					<table cellpadding="0" cellspacing="5px" border="0" class="form-table">
						<tr>
							<td>&nbsp;</td>
							<td><?php _e('Condition(s) to be met:', 'msw_wp-filter'); ?></td>
							<td>&nbsp;</td>
							<td><?php _e('Condition Data:', 'msw_wp-filter'); ?></td>
							<td></td>
						</tr>
						<tr id="catch_1_addScreen">
							<td width="30px"><a href="javascript: remove_CatchAction('catch', 1, 'addScreen');" title="<?php _e('Remove Catch', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/remove.gif" border="0" alt="<?php _e('Remove Catch', 'msw_wp-filter'); ?>"></a></td>
							<td valign="middle">
								<select class="selectValidate_addScreen" id="catchObject[]" name="catchObject[]">
									<?php foreach ($catches as $catchTag => $catchName) { ?>
									<option value="<?php echo $catchTag; ?>"><?php echo $catchName; ?></option>
									<?php } ?>
								</select>
							</td>
							<td>=&gt;</td>
							<td valign="middle"><input class="inputValidate_addScreen" name="catchData[]" /></td>
							<td width="60%">&nbsp;</td>
						</tr>
						<tr id="add_Catch_addScreen">
							<td width="30px" valign="middle"><a href="javascript: add_CatchAction('Catch', 'addScreen');" title="<?php _e('Add Filter', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/add.gif" border="0" alt="<?php _e('Add Filter', 'msw_wp-filter'); ?>"></a></td>
							<td valign="middle" colspan="3"><?php _e('Add Catch', 'msw_wp-filter'); ?></td>
							<td width="60%">&nbsp;</td>
						</tr>
					</table>
					<h4><?php _e('Then perform these actions:', 'msw_wp-filter'); ?></h4>
					<table cellpadding="0" cellspacing="5px" border="0" class="form-table">
						<tr>
							<td>&nbsp;</td>
							<td><?php _e('Action(s) to be performed:', 'msw_wp-filter'); ?></td>
							<td>&nbsp;</td>
							<td><?php _e('Action Data:', 'msw_wp-filter'); ?></td>
							<td></td>
						</tr>
						<tr id="action_1_addScreen">
							<td width="30px"><a href="javascript: remove_CatchAction('action', 1, 'addScreen');" title="<?php _e('Remove Action', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/remove.gif" border="0" alt="<?php _e('Remove Filter', 'msw_wp-filter'); ?>"></a></td>
							<td valign="middle">
								<select class="selectValidate_addScreen" id="actionObject[]" name="actionObject[]">
									<?php foreach ($actions as $actionTag => $actionName) { ?>
									<option value="<?php echo $actionTag; ?>"><?php echo $actionName; ?></option>
									<?php } ?>
								</select>
							</td>
							<td>=&gt;</td>
							<td valign="middle"><input class="inputValidate_addScreen" name="actionData[]" /></td>
							<td width="60%">&nbsp;</td>
						</tr>
						<tr id="add_Action_addScreen">
							<td width="30px" valign="middle"><a href="javascript: add_CatchAction('Action', 'addScreen');" title="<?php _e('Add Action', 'msw_wp-filter'); ?>"><img src="<?php echo WP_PLUGIN_URL; ?>/wordpress-filter/images/add.gif" border="0" alt="<?php _e('Add Action', 'msw_wp-filter'); ?>"></a></td>
							<td valign="middle" colspan="3"><?php _e('Add Action', 'msw_wp-filter'); ?></td>
							<td width="60%">&nbsp;</td>
						</tr>
					</table>
					<table cellpadding="0" cellspacing="5px" border="0" class="form-table">
						<tr>
							<td valign="middle"><hr/></td>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<td valign="middle">
								<?php _e('* All fields are required.', 'msw_wp-filter'); ?><br />
								<?php _e('* All conditions must be met for actions to be applied.', 'msw_wp-filter'); ?><br />
								<?php _e('* Fields requiring tags should have the tags comma separated (tag1, tag2, etc).', 'msw_wp-filter'); ?>
							</td>
							<td width="60%">&nbsp;</td>
						</tr>
					</table>
					<p>
						<input type="submit" name="save_filter" value="Save Filter" id="save_filter" />
						<input type="hidden" name="wpfilter_action" value="wpfilter_add_filter" id="wpfilter_action" />
					</p>
				</form>
			</div>
			<div id="wpf-tab3">
				<h3><?php _e('Edit Filter', 'msw_wp-filter'); ?></h3>
				<form id="edit_Filter" name="edit_Filter" method="post" action="./options-general.php?page=wordpress-filter/wp-filter.php#wpf-tab1" onsubmit="javascript: return validate_EditFilter();"></form>
			</div>
			<div id="wpf-tab4">
				<h3><?php _e('Import Filter', 'msw_wp-filter'); ?></h3>
				<form id="form_ImportFilterSet" name="form_ImportFilterSet" method="post" action="./options-general.php?page=wordpress-filter/wp-filter.php#wpf-tab1" onsubmit="">
					<textarea name="import_FilterSet" style="width: 400px; height: 100px;"></textarea><br/ >
					<input type="submit" name="import_filter" value="Import Filter" id="import_filter" />
					<input type="hidden" name="wpfilter_action" value="wpfilter_import_filter" id="wpfilter_action" />
				</form>
			</div>
		</div>
		<div id="debug_information"></div>
	</div>
	<?php
}

function wpfilter_get_tagbase() { // Return slug for Tags
	global $wpdb;
	$result = $wpdb->get_results("SELECT $wpdb->options.option_value FROM $wpdb->options WHERE $wpdb->options.option_name = 'tag_base' LIMIT 0, 1;");
	$tagBase = $result[0]->option_value;
	return $tagBase;
}

function wpfilter_user_content_replacements($text) { // Apply system defined substitutions
	$substitutions = wpfilter_get_Substitutions();
	foreach ($substitutions as $search=>$replace) {
		$text = str_replace($search, $replace, $text);
	}
	return $text;
}

function wpfilter_validate_filter($filter) { // Test to see if a filter array is valid
	$filterValid = true;
	$catches = wpfilter_get_catches();
	$actions = wpfilter_get_actions();
	if (!array_key_exists('catch', $filter)) { $filterValid = false; }
	if (!array_key_exists('action', $filter)) { $filterValid = false; }
	if ($filterValid) {
		foreach ($filter['catch'] as $catchObject=>$catchData) {
			if (!array_key_exists($catchObject, $catches) || $catchData == '') { $filterValid = false; }
		}
		foreach ($filter['action'] as $actionObject=>$actionData) {
			if (!array_key_exists($actionObject, $actions) || $actionData == '') { $filterValid = false; }
		}
	}
	return $filterValid;
}

function wpfilter_get_Substitutions() { // Return system substitutions
	$substitutions = array(
		'%%day-num%%' => date('j', time()),
		'%%day-text%%' => date('l', time()),
		'%%year%%' => date('Y', time()),
		'%%month-num%%' => date('n', time()),
		'%%month-text%%' => date('F', time())
	);
	return $substitutions;
}

function wpfilter_order_FilterSet($filterSet) { // Keep filters in order
	$new_filterSet = array();
	$filterCtr = 0;
	if (count($filterSet) > 0 && $filterSet != '') {
		foreach($filterSet as $filter) {
			if ($filter['ID'] != $_POST['id']) {
				$filterCtr++;
				$new_filterSet[$filterCtr] = $filter;
			}
		}
	}
	$filterSet = $new_filterSet;
	return $filterSet;
}

function wpfilter_get_catches() { // Return catchObject=>i18n Name for catches
	$catches = array(
		'post_title_equals'				=> __('Post Title: Equals', 'msw_wp-filter'),
		'post_title_contains'			=> __('Post Title: Contains', 'msw_wp-filter'),
		'post_content_equals'			=> __('Post Content: Equals', 'msw_wp-filter'),
		'post_content_contains'			=> __('Post Content: Contains', 'msw_wp-filter'),
		'post_content_doesnt_contain'	=> __("Post Content: Doesn't Contain", 'msw_wp-filter'),
		'post_excerpt_equals'			=> __('Post Excerpt: Equals', 'msw_wp-filter'),
		'post_excerpt_contains'			=> __('Post Excerpt: Contains', 'msw_wp-filter'),
		'post_excerpt_doesnt_contain'	=> __("Post Excerpt: Doesn't Contain", 'msw_wp-filter'),
		'post_tags'						=> __('Post: Has Tag', 'msw_wp-filter'),
		'post_cats'						=> __('Post: Has Category', 'msw_wp-filter'),
		'post_custom'					=> __('Custom Field: Exists', 'msw_wp-filter'),
		'post_author'					=> __('Post Author: Equals', 'msw_wp-filter'),
		'comment_status'				=> __('Comment Status: Equals', 'msw_wp-filter'),
		'ping_status'					=> __('Ping Status: Equals', 'msw_wp-filter'),
		'post_status'					=> __('Post Status: Equals', 'msw_wp-filter')
	);
	return $catches;
}

function wpfilter_get_actions() { // Return actionObject=>i18n Name for actions
	$actions = array(
		'prepend_post_title'				=> __('Title: Prepend', 'msw_wp-filter'),
		'replace_post_title'				=> __('Title: Replace', 'msw_wp-filter'),
		'replace_post_substring_title'		=> __('Title: Replace Substring', 'msw_wp-filter'),
		'append_post_title'					=> __('Title: Append', 'msw_wp-filter'),
		'prepend_content'					=> __('Content: Prepend', 'msw_wp-filter'),
		'replace_content'					=> __('Content: Replace', 'msw_wp-filter'),
		'replace_substring_content'			=> __('Content: Replace Substring', 'msw_wp-filter'),
		'append_content'					=> __('Content: Append', 'msw_wp-filter'),
		'prepend_excerpt'					=> __('Excerpt: Prepend', 'msw_wp-filter'),
		'replace_excerpt'					=> __('Excerpt: Replace', 'msw_wp-filter'),
		'replace_substring_excerpt'			=> __('Excerpt: Replace Substring', 'msw_wp-filter'),
		'append_excerpt'					=> __('Excerpt: Append', 'msw_wp-filter'),
		'append_tags'						=> __('Tag(s): Add', 'msw_wp-filter'),
		'remove_tags'						=> __('Tag(s): Remove', 'msw_wp-filter'),
		'append_cats'						=> __('Category(ies): Add', 'msw_wp-filter'),
		'remove_cats'						=> __('Category(ies): Remove', 'msw_wp-filter'),
		'comment_status'					=> __('Comment Status: Equals', 'msw_wp-filter'),
		'ping_status'						=> __('Ping Status: Equals', 'msw_wp-filter'),
		'post_status'						=> __('Post Status: Equals', 'msw_wp-filter')
	);
	return $actions;
}

function wpfilter_save_post($postId) { // Apply actions after a post is saved
	global $postFiltered; // Used to prevent endless recursion
	$msw_Maintenance = false;

	if (!wp_is_post_revision($postId) && !$postFiltered) { // Make sure we don't have recursion insanity and that we're applying this to the actual post (not a revision)
		$postData = get_post($postId); // Retrieve post information
		$filterSet = array(); // Make sure $filterSet is empty before beginning, reduce risk of a rule somehow being injected from somewhere else -- yeah, I'm paranoid.
		$filterSet = unserialize(get_option('wpfilter_filterSet'));

		if ($filterSet) { // Continue if filterSet was found

			$filterPosts = unserialize(get_option('wpfilter_filterPosts'));
			if (!$filterPosts) { // If no option was returned, create the option
				add_option('wpfilter_filterPosts');
				$filterPosts = array();
			}

			$postData->tags_input = wp_get_post_tags($postId, array('fields' => 'names'));
			if (count($postData->tags_input) < 1) { $postData->tags_input = array(); }
			$postData->post_category = wp_get_post_categories($postId);
			$postTags = $postData->tags_input;
			$postCats = wp_get_post_categories($postId);
			$postCustoms = get_post_custom($postId);
			$postAuthor = get_userdata($postData->post_author);
			$tagBase = wpfilter_get_tagbase();

			foreach ($filterSet as $filter) { // Loop through filters to see if any need to be applied
				$filterID = $filter['ID'];

				if (!$filterPosts[$postData->ID][$filterID] || $msw_Maintenance) { // If the post has not had this filter applied, try applying it
					$criteriaMatches = 0;
					$criteriaNeeded = count($filter['catch']);
					foreach ($filter['catch'] as $catchObject => $catchData) { // Check for catches
						if ($catchObject == 'post_title_equals' && strtolower($catchData) == strtolower($postData->post_title)) { $criteriaMatches++; }
						if ($catchObject == 'post_title_contains' && (strpos($postData->post_title,$catchData) !== false)) { $criteriaMatches++; }
						if ($catchObject == 'post_type' && strtolower($catchData) == strtolower($postData->post_type)) { $criteriaMatches++; }
						if ($catchObject == 'post_status' && $catchData == $postData->post_status) { $criteriaMatches++; }
						if ($catchObject == 'post_content_equals' && strtolower($catchData) == strtolower($postData->post_content)) { $criteriaMatches++; }
						if ($catchObject == 'post_content_contains' && (strpos($postData->post_content,$catchData) !== false)) { $criteriaMatches++; }
						if ($catchObject == 'post_content_doesnt_contain' && !strpos($postData->post_content,$catchData)) { $criteriaMatches++; }
						if ($catchObject == 'post_excerpt_equals' && strtolower($catchData) == strtolower($postData->post_excerpt)) { $criteriaMatches++; }
						if ($catchObject == 'post_excerpt_contains' && (strpos($postData->post_excerpt,$catchData) !== false)) { $criteriaMatches++; }
						if ($catchObject == 'post_excerpt_doesnt_contain' && !strpos($postData->post_excerpt,$catchData)) { $criteriaMatches++; }
						if ($catchObject == 'post_author' && strtolower($catchData) == strtolower($postAuthor->display_name)) { $criteriaMatches++; }
						if ($catchObject == 'comment_status' && strtolower($catchData) == strtolower($postData->comment_status)) { $criteriaMatches++; }
						if ($catchObject == 'ping_status' && strtolower($catchData) == strtolower($postData->ping_status)) { $criteriaMatches++; }
						if ($catchObject == 'post_custom') {
							foreach ($postCustoms as $customKey=>$customValue) {
								if (strtolower($catchData) == strtolower($customKey)) { $criteriaMatches++; }
							}
						}
						if ($catchObject == 'post_tags') {
							foreach ($postTags as $testTag) {
								if (strtolower($catchData) == strtolower($testTag)) { $criteriaMatches++; }
							}
						}
						if ($catchObject == 'post_cats') {
							foreach ($postCats as $testCat) {
								if (strtolower($catchData) == strtolower(get_cat_name($testCat))) { $criteriaMatches++; }
							}
						}
					}

					if ($criteriaMatches == $criteriaNeeded) { // Post needs actions applied
						foreach ($filter['action'] as $actionObject => $actionData) { // Apply actions
							$actionData = wpfilter_user_content_replacements($actionData);
							switch ($actionObject) { // Apply proper action
								case 'prepend_post_title':
									$postData->post_title = $actionData . $postData->post_title;
									break;
								case 'replace_post_title':
									$postData->post_title = $actionData;
									break;
								case 'replace_post_substring_title':
									$temp = explode('=', $actionData);
									$postData->post_title = str_replace($temp[0], $temp[1], $postData->post_title);
									break;
								case 'append_post_title':
									$postData->post_title = $postData->post_title . $actionData;
									break;
								case 'prepend_content':
									$postData->post_content = $actionData . $postData->post_content;
									break;
								case 'replace_content':
									$postData->post_content = $actionData;
									break;
								case 'replace_substring_content':
									$temp = explode('=', $actionData);
									$postData->post_content = str_replace($temp[0], $temp[1], $postData->post_content);
									break;
								case 'append_content':
									$postData->post_content = $postData->post_content . stripslashes($actionData);
									break;
								case 'prepend_excerpt':
									$postData->post_excerpt = $actionData . $postData->post_excerpt;
									break;
								case 'replace_excerpt':
									$postData->post_excerpt = $actionData;
									break;
								case 'replace_substring_excerpt':
									$temp = explode('=', $actionData);
									$postData->post_excerpt = str_replace($temp[0], $temp[1], $postData->post_excerpt);
									break;
								case 'append_excerpt':
									$postData->post_excerpt = $postData->post_excerpt . $actionData;
									break;
								case 'append_tags':
									$postData->tags_input = array_unique(array_merge($postData->tags_input, $actionData));
									break;
								case 'remove_tags':
									$postData->tags_input = array_unique(array_diff($postData->tags_input, $actionData));
									break;
								case 'append_cats':
									foreach ($actionData as $k=>$catName) {
										$actionData[$k] = get_cat_id($catName);
									}
									$postData->post_category = array_unique(array_merge($postData->post_category, $actionData));
									break;
								case 'remove_cats':
									foreach ($actionData as $k=>$catName) {
										$actionData[$k] = get_cat_id($catName);
									}
									$postData->post_category = array_unique(array_diff($postData->post_category, $actionData));
									break;
								case 'comment_status':
									$postData->comment_status = $actionData;
									break;
								case 'ping_status':
									$postData->ping_status = $actionData;
									break;
								case 'post_status':
									$postData->post_status = $actionData;
									break;
								default:
									break;
							}
						}
					}
				}
			}

			$postFiltered = true;
			$filterPosts[$postData->ID][$filterID] = true;

			// Update database
			wp_update_post($postData);
			update_option('wpfilter_filterPosts', serialize($filterPosts));

			return true;
		} else {
			// There appears to be a filterset option, but no Catches or Actions.
			return false;
		}
	}
} // </wpfilter_save_post>

// { 
add_action('init', 'wpfilter_admin_processing');
add_action('admin_menu', 'wpfilter_menu'); // Add menu to admin area
add_action('publish_post', 'wpfilter_save_post'); // Hook into the publish_post functionality so filters can be applied
// }
?>