<?php
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . 'wp-admin/includes/template.php';

class MainWP_WPTC_List_Table extends WP_List_Table {
    
    private $display_rows;
    private $totalitems;
    private $perpage;

    /**
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	 */
	function __construct() {
		parent::__construct(array(
			'singular' => 'wp_list_text_contact', //Singular label
			'plural' => 'wp_list_test_contacts', //plural label, also this well be one of the table class
			'ajax' => false, //We won't support Ajax for this table
		));
	}

	/**
	 * Add extra markup in the toolbars before or after the list
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
	 */
	function extra_tablenav($which) {
	//if ( $which == "top" ){
		//			//The code that goes before the table is here
		//			//echo ($headername!="")?$headername:"Table Data <small>Database</small>";
		//		}
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
	
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
    
    function prepare_data($data) {        
        $this->items = $data['items'];        
        $this->totalitems = $data['totalitems'];
        $this->perpage = $data['perpage'];
        $display_rows = unserialize(base64_decode($data['display_rows']));  
        $this->display_rows = $display_rows;
	}
    
	function prepare_items() {
		$perpage = $this->perpage;
		$totalpages = ceil($this->totalitems / $perpage); //Total number of pages
	
        $this->set_pagination_args(array(
			"total_items" => $this->totalitems,
			"total_pages" => $totalpages,
			"per_page" => $perpage,
		));        
	}

	/**
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	function display_rows() {
        
		$records = $this->items;
        $display_rows = $this->display_rows;   
        
		echo "<thead style='background: none repeat scroll 0% 0% rgb(238, 238, 238);'><tr><td style='width:10%'>Time</td><td style='width:60%'>Task</td><td>Send Report</td></tr></thead>";
		if (count($records) > 0) {
			foreach ($records as $key => $rec) {                
				if (isset($display_rows[$key])) {
                    echo $display_rows[$key];                    
                }                
			}

		}
	}
    
	//Overwrite Pagination function
	function pagination($which) {

		if (empty($this->_pagination_args)) {
			return;
		}

		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];
		$infinite_scroll = false;
		if (isset($this->_pagination_args['infinite_scroll'])) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		$output = '<span class="displaying-num">' . sprintf(_n('1 log', '%s logs', $total_items, 'wptc'), number_format_i18n($total_items)) . '</span>';

		$current = $this->get_pagenum();

		$current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

		$current_url = remove_query_arg(array('hotkeys_highlight_last', 'hotkeys_highlight_first'), $current_url);

		$page_links = array();

		$disable_first = $disable_last = '';
		if ($current == 1) {
			$disable_first = ' disabled';
		}
		if ($current == $total_pages) {
			$disable_last = ' disabled';
		}
		$page_links[] = sprintf("<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__('Go to the first page'),
			esc_url(remove_query_arg('paged', $current_url)),
			'&laquo;'
		);

		$page_links[] = sprintf("<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__('Go to the previous page'),
			esc_url(add_query_arg('paged', max(1, $current - 1), $current_url)),
			'&lsaquo;'
		);

		if ('bottom' == $which) {
			$html_current_page = $current;
		} else {
			$html_current_page = sprintf("%s<input class='current-page' id='current-page-selector' title='%s' type='text' name='paged' value='%s' size='%d' />",
				'<label for="current-page-selector" class="screen-reader-text">' . __('Select Page') . '</label>',
				esc_attr__('Current page'),
				$current,
				strlen($total_pages)
			);
		}
		$html_total_pages = sprintf("<span class='total-pages'>%s</span>", number_format_i18n($total_pages));
		$page_links[] = '<span class="paging-input">' . sprintf(_x('%1$s of %2$s', 'paging'), $html_current_page, $html_total_pages) . '</span>';

		$page_links[] = sprintf("<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__('Go to the next page'),
			esc_url(add_query_arg('paged', min($total_pages, $current + 1), $current_url)),
			'&rsaquo;'
		);

		$page_links[] = sprintf("<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__('Go to the last page'),
			esc_url(add_query_arg('paged', $total_pages, $current_url)),
			'&raquo;'
		);

		$pagination_links_class = 'pagination-links';
		if (!empty($infinite_scroll)) {
			$pagination_links_class = ' hide-if-js';
		}
		$output .= "\n<span class='$pagination_links_class'>" . join("\n", $page_links) . '</span>';

		if ($total_pages) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;

	}

	function get_activity_log($sub_records){
		if (count($sub_records) < 1) {
			return false;
		}
		$detailed = '';
		$timezone = MainWP_WPTC_Factory::get('config')->get_option('wptc_timezone');
		foreach ($sub_records as $srec) {
			$Moredata = unserialize($srec->log_data);
			$user_tmz = new DateTime('@' . $Moredata['log_time'], new DateTimeZone(date_default_timezone_get()));
			$user_tmz->setTimeZone(new DateTimeZone($timezone));
			$user_tmz_now = $user_tmz->format("M d @ g:i:s a");
			$detailed .= '<tr><td>' . $user_tmz_now . '</td><td>' . $Moredata['msg'] . '</td><td></td></tr>';
		}
		return $detailed;
	}
}
function mainwp_lazy_load_activity_log_wptc(){
     
    MainWP_TimeCapsule::ajax_check_data();            
    $websiteId = intval($_REQUEST['timecapsuleSiteID']);
    global $mainwpWPTimeCapsuleExtensionActivator;

    if (!isset($_POST['data'])) {
		return false;
	}
	$data = $_POST['data'];
	if (!isset($data['action_id']) || !isset($data['limit'])) {
		return false;
	}
    
    $post_data = array(
        'mwp_action' => 'lazy_load_activity_log',
        'data' => $data
    );

    $information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );
    
    if ( is_array( $information )) {
        if (isset( $information['result'] ) ) {
            echo $information['result'];            
        } else if (isset($information['error'])) {
            echo $information['error'];            
        }
        die();
    } 
    
    echo 'Undefined error.';
    die();
}