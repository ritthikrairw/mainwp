<?php

class MainWP_Pro_Reports {

	private static $pro_tokens           = array();
	private static $data_tokens          = array();
	private static $buffer               = array();
	public static $enabled_piwik         = null;
	public static $enabled_sucuri        = false;
	public static $enabled_ga            = null;
	public static $enabled_aum           = null;
	public static $enabled_woocomstatus  = null;
	public static $enabled_wordfence     = null;
	public static $enabled_maintenance   = null;
	public static $enabled_pagespeed     = null;
	public static $enabled_virusdie      = null;
	public static $enabled_vulnerable    = null;
	public static $enabled_lighthouse    = null;
	public static $enabled_domainmonitor = null;


	private static $count_sec_header = 0;
	private static $count_sec_body   = 0;
	private static $count_sec_footer = 0;

	private static $raw_sec_body     = false;
	private static $raw_section_body = array();

	public $update_version = '1.0';

	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Pro_Reports();
		}
		return self::$instance;
	}

	public function __construct() {

		add_action( 'admin_init', array( &$this, 'admin_init' ) );

		// for reference.
		self::$pro_tokens = array(
			'client'        => array(
				'nav_group_tokens' => array(
					'tokens' => 'Tokens',
				),
				'tokens'           => array(),
			),
			'plugins'       => array(
				'sections'         => array(
					array(
						'name' => 'section.plugins.installed',
						'desc' => 'Loops through Plugins Installed during the selected date range',
					),
					array(
						'name' => 'section.plugins.activated',
						'desc' => 'Loops through Plugins Activated during the selected date range',
					),
					array(
						'name' => 'section.plugins.edited',
						'desc' => 'Loops through Plugins Edited during the selected date range',
					),
					array(
						'name' => 'section.plugins.deactivated',
						'desc' => 'Loops through Plugins Deactivated during the selected date range',
					),
					array(
						'name' => 'section.plugins.updated',
						'desc' => 'Loops through Plugins Updated during the selected date range',
					),
					array(
						'name' => 'section.plugins.deleted',
						'desc' => 'Loops through Plugins Deleted during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'    => 'Sections',
					'installed'   => 'Installed',
					'activated'   => 'Activated',
					'edited'      => 'Edited',
					'deactivated' => 'Deactivated',
					'updated'     => 'Updated',
					'deleted'     => 'Deleted',
					'additional'  => 'Additional',
				),
				'installed'        => array(
					array(
						'name' => 'plugin.name',
						'desc' => 'Displays the Plugin Name',
					),
					array(
						'name' => 'plugin.installed.date',
						'desc' => 'Displays the Plugin Installation Date',
					),
					array(
						'name' => 'plugin.installed.time',
						'desc' => 'Displays the Plugin Installation Time',
					),
					array(
						'name' => 'plugin.installed.author',
						'desc' => 'Displays the User who Installed the Plugin',
					),
				),
				'activated'        => array(
					array(
						'name' => 'plugin.name',
						'desc' => 'Displays the Plugin Name',
					),
					array(
						'name' => 'plugin.activated.date',
						'desc' => 'Displays the Plugin Activation Date',
					),
					array(
						'name' => 'plugin.activated.time',
						'desc' => 'Displays the Plugin Activation Time',
					),
					array(
						'name' => 'plugin.activated.author',
						'desc' => 'Displays the User who Activated the Plugin',
					),
				),
				'edited'           => array(
					array(
						'name' => 'plugin.name',
						'desc' => 'Displays the Plugin Name',
					),
					array(
						'name' => 'plugin.edited.date',
						'desc' => 'Displays the Plugin Editing Date',
					),
					array(
						'name' => 'plugin.edited.time',
						'desc' => 'Displays the Plugin Editing time',
					),
					array(
						'name' => 'plugin.edited.author',
						'desc' => 'Displays the User who Edited the Plugin',
					),
				),
				'deactivated'      => array(
					array(
						'name' => 'plugin.name',
						'desc' => 'Displays the Plugin Name',
					),
					array(
						'name' => 'plugin.deactivated.date',
						'desc' => 'Displays the Plugin Deactivation Date',
					),
					array(
						'name' => 'plugin.deactivated.time',
						'desc' => 'Displays the Plugin Deactivation Time',
					),
					array(
						'name' => 'plugin.deactivated.author',
						'desc' => 'Displays the User who Deactivated the Plugin',
					),
				),
				'updated'          => array(
					array(
						'name' => 'plugin.old.version',
						'desc' => 'Displays the Plugin Version Before Update',
					),
					array(
						'name' => 'plugin.current.version',
						'desc' => 'Displays the Plugin Current Vesion',
					),
					array(
						'name' => 'plugin.name',
						'desc' => 'Displays the Plugin Name',
					),
					array(
						'name' => 'plugin.updated.date',
						'desc' => 'Displays the Plugin Update Date',
					),
					array(
						'name' => 'plugin.updated.time',
						'desc' => 'Displays the Plugin Update Time',
					),
					array(
						'name' => 'plugin.updated.author',
						'desc' => 'Displays the User who Updated the Plugin',
					),
				),
				'deleted'          => array(
					array(
						'name' => 'plugin.name',
						'desc' => 'Displays the Plugin Name',
					),
					array(
						'name' => 'plugin.deleted.date',
						'desc' => 'Displays the Plugin Deleting Date',
					),
					array(
						'name' => 'plugin.deleted.time',
						'desc' => 'Displays the Plugin Deleting Time',
					),
					array(
						'name' => 'plugin.deleted.author',
						'desc' => 'Displays the User who Deleted the Plugin',
					),
				),
				'additional'       => array(
					array(
						'name' => 'plugin.installed.count',
						'desc' => 'Displays the Number of Installed Plugins',
					),
					array(
						'name' => 'plugin.edited.count',
						'desc' => 'Displays the Number of Edited Plugins',
					),
					array(
						'name' => 'plugin.activated.count',
						'desc' => 'Displays the Number of Activated Plugins',
					),
					array(
						'name' => 'plugin.deactivated.count',
						'desc' => 'Displays the Number of Deactivated Plugins',
					),
					array(
						'name' => 'plugin.deleted.count',
						'desc' => 'Displays the Number of Deleted Plugins',
					),
					array(
						'name' => 'plugin.updated.count',
						'desc' => 'Displays the Number of Updated Plugins',
					),
				),
			),
			'themes'        => array(
				'sections'         => array(
					array(
						'name' => 'section.themes.installed',
						'desc' => 'Loops through Themes Installed during the selected date range',
					),
					array(
						'name' => 'section.themes.activated',
						'desc' => 'Loops through Themes Activated during the selected date range',
					),
					array(
						'name' => 'section.themes.edited',
						'desc' => 'Loops through Themes Edited during the selected date range',
					),
					array(
						'name' => 'section.themes.updated',
						'desc' => 'Loops through Themes Updated during the selected date range',
					),
					array(
						'name' => 'section.themes.deleted',
						'desc' => 'Loops through Themes Deleted during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'installed'  => 'Installed',
					'activated'  => 'Activated',
					'edited'     => 'Edited',
					'updated'    => 'Updated',
					'deleted'    => 'Deleted',
					'additional' => 'Additional',
				),
				'installed'        => array(
					array(
						'name' => 'theme.name',
						'desc' => 'Displays the Theme Name',
					),
					array(
						'name' => 'theme.installed.date',
						'desc' => 'Displays the Theme Installation Date',
					),
					array(
						'name' => 'theme.installed.time',
						'desc' => 'Displays the Theme Installation Time',
					),
					array(
						'name' => 'theme.installed.author',
						'desc' => 'Displays the User who Installed the Theme',
					),
				),
				'activated'        => array(
					array(
						'name' => 'theme.name',
						'desc' => 'Displays the Theme Name',
					),
					array(
						'name' => 'theme.activated.date',
						'desc' => 'Displays the Theme Activation Date',
					),
					array(
						'name' => 'theme.activated.time',
						'desc' => 'Displays the Theme Activation Time',
					),
					array(
						'name' => 'theme.activated.author',
						'desc' => 'Displays the User who Activated the Theme',
					),
				),
				'edited'           => array(
					array(
						'name' => 'theme.name',
						'desc' => 'Displays the Theme Name',
					),
					array(
						'name' => 'theme.edited.date',
						'desc' => 'Displays the Theme Editing Date',
					),
					array(
						'name' => 'theme.edited.time',
						'desc' => 'Displays the Theme Editing Time',
					),
					array(
						'name' => 'theme.edited.author',
						'desc' => 'Displays the User who Edited the Theme',
					),
				),
				'updated'          => array(
					array(
						'name' => 'theme.old.version',
						'desc' => 'Displays the Theme Version Before Update',
					),
					array(
						'name' => 'theme.current.version',
						'desc' => 'Displays the Theme Current Version',
					),
					array(
						'name' => 'theme.name',
						'desc' => 'Displays the Theme Name',
					),
					array(
						'name' => 'theme.updated.date',
						'desc' => 'Displays the Theme Update Date',
					),
					array(
						'name' => 'theme.updated.time',
						'desc' => 'Displays the Theme Update Time',
					),
					array(
						'name' => 'theme.updated.author',
						'desc' => 'Displays the User who Updated the Theme',
					),
				),
				'deleted'          => array(
					array(
						'name' => 'theme.name',
						'desc' => 'Displays the Theme Name',
					),
					array(
						'name' => 'theme.deleted.date',
						'desc' => 'Displays the Theme Deleting Date',
					),
					array(
						'name' => 'theme.deleted.time',
						'desc' => 'Displays the Theme Deleting Time',
					),
					array(
						'name' => 'theme.deleted.author',
						'desc' => 'Displays the User who Deleted the Theme',
					),
				),
				'additional'       => array(
					array(
						'name' => 'theme.installed.count',
						'desc' => 'Displays the Number of Installed Themes',
					),
					array(
						'name' => 'theme.edited.count',
						'desc' => 'Displays the Number of Edited Themes',
					),
					array(
						'name' => 'theme.activated.count',
						'desc' => 'Displays the Number of Activated Themes',
					),
					array(
						'name' => 'theme.deleted.count',
						'desc' => 'Displays the Number of Deleted Themes',
					),
					array(
						'name' => 'theme.updated.count',
						'desc' => 'Displays the Number of Updated Themes',
					),
				),
			),
			'posts'         => array(
				'sections'         => array(
					array(
						'name' => 'section.posts.created',
						'desc' => 'Loops through Posts Created during the selected date range',
					),
					array(
						'name' => 'section.posts.updated',
						'desc' => 'Loops through Posts Updated during the selected date range',
					),
					array(
						'name' => 'section.posts.trashed',
						'desc' => 'Loops through Posts Trashed during the selected date range',
					),
					array(
						'name' => 'section.posts.deleted',
						'desc' => 'Loops through Posts Deleted during the selected date range',
					),
					array(
						'name' => 'section.posts.restored',
						'desc' => 'Loops through Posts Restored during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'created'    => 'Created',
					'updated'    => 'Updated',
					'trashed'    => 'Trashed',
					'deleted'    => 'Deleted',
					'restored'   => 'Restored',
					'additional' => 'Additional',
				),
				'created'          => array(
					array(
						'name' => 'post.title',
						'desc' => 'Displays the Post Title',
					),
					array(
						'name' => 'post.created.date',
						'desc' => 'Displays the Post Creation Date',
					),
					array(
						'name' => 'post.created.time',
						'desc' => 'Displays the Post Creation Time',
					),
					array(
						'name' => 'post.created.author',
						'desc' => 'Displays the User who Created the Post',
					),
				),
				'updated'          => array(
					array(
						'name' => 'post.title',
						'desc' => 'Displays the Post Title',
					),
					array(
						'name' => 'post.updated.date',
						'desc' => 'Displays the Post Update Date',
					),
					array(
						'name' => 'post.updated.time',
						'desc' => 'Displays the Post Update Time',
					),
					array(
						'name' => 'post.updated.author',
						'desc' => 'Displays the User who Updated the Post',
					),
				),
				'trashed'          => array(
					array(
						'name' => 'post.title',
						'desc' => 'Displays the Post Title',
					),
					array(
						'name' => 'post.trashed.date',
						'desc' => 'Displays the Post Trashing Date',
					),
					array(
						'name' => 'post.trashed.time',
						'desc' => 'Displays the Post Trashing Time',
					),
					array(
						'name' => 'post.trashed.author',
						'desc' => 'Displays the User who Trashed the Post',
					),
				),
				'deleted'          => array(
					array(
						'name' => 'post.title',
						'desc' => 'Displays the Post Title',
					),
					array(
						'name' => 'post.deleted.date',
						'desc' => 'Displays the Post Deleting Date',
					),
					array(
						'name' => 'post.deleted.time',
						'desc' => 'Displays the Post Deleting Time',
					),
					array(
						'name' => 'post.deleted.author',
						'desc' => 'Displays the User who Deleted the Post',
					),
				),
				'restored'         => array(
					array(
						'name' => 'post.title',
						'desc' => 'Displays Post Title',
					),
					array(
						'name' => 'post.restored.date',
						'desc' => 'Displays the Post Restoring Date',
					),
					array(
						'name' => 'post.restored.time',
						'desc' => 'Displays the Post Restoring Time',
					),
					array(
						'name' => 'post.restored.author',
						'desc' => 'Displays the User who Restored the Post',
					),
				),
				'additional'       => array(
					array(
						'name' => 'post.created.count',
						'desc' => 'Displays the Number of Created Posts',
					),
					array(
						'name' => 'post.updated.count',
						'desc' => 'Displays the Number of Updated Posts',
					),
					array(
						'name' => 'post.trashed.count',
						'desc' => 'Displays the Number of Trashed Posts',
					),
					array(
						'name' => 'post.restored.count',
						'desc' => 'Displays the Number of Restored Posts',
					),
					array(
						'name' => 'post.deleted.count',
						'desc' => 'Displays the Number of Deleted Posts',
					),
				),
			),
			'pages'         => array(
				'sections'         => array(
					array(
						'name' => 'section.pages.created',
						'desc' => 'Loops through Pages Created during the selected date range',
					),
					array(
						'name' => 'section.pages.updated',
						'desc' => 'Loops through Pages Updated during the selected date range',
					),
					array(
						'name' => 'section.pages.trashed',
						'desc' => 'Loops through Pages Trashed during the selected date range',
					),
					array(
						'name' => 'section.pages.deleted',
						'desc' => 'Loops through Pages Deleted during the selected date range',
					),
					array(
						'name' => 'section.pages.restored',
						'desc' => 'Loops through Pages Restored during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'created'    => 'Created',
					'updated'    => 'Updated',
					'trashed'    => 'Trashed',
					'deleted'    => 'Deleted',
					'restored'   => 'Restored',
					'additional' => 'Additional',
				),
				'created'          => array(
					array(
						'name' => 'page.title',
						'desc' => 'Displays the Page Title',
					),
					array(
						'name' => 'page.created.date',
						'desc' => 'Displays the Page Createion Date',
					),
					array(
						'name' => 'page.created.time',
						'desc' => 'Displays the Page Createion Time',
					),
					array(
						'name' => 'page.created.author',
						'desc' => 'Displays the User who Created the Page',
					),
				),
				'updated'          => array(
					array(
						'name' => 'page.title',
						'desc' => 'Displays the Page Title',
					),
					array(
						'name' => 'page.updated.date',
						'desc' => 'Displays the Page Updating Date',
					),
					array(
						'name' => 'page.updated.time',
						'desc' => 'Displays the Page Updating Time',
					),
					array(
						'name' => 'page.updated.author',
						'desc' => 'Displays the User who Updated the Page',
					),
				),
				'trashed'          => array(
					array(
						'name' => 'page.title',
						'desc' => 'Displays the Page Title',
					),
					array(
						'name' => 'page.trashed.date',
						'desc' => 'Displays the Page Trashing Date',
					),
					array(
						'name' => 'page.trashed.time',
						'desc' => 'Displays the Page Trashing Time',
					),
					array(
						'name' => 'page.trashed.author',
						'desc' => 'Displays the User who Trashed the Page',
					),
				),
				'deleted'          => array(
					array(
						'name' => 'page.title',
						'desc' => 'Displays the Page Title',
					),
					array(
						'name' => 'page.deleted.date',
						'desc' => 'Displays the Page Deleting Date',
					),
					array(
						'name' => 'page.deleted.time',
						'desc' => 'Displays the Page Deleting Time',
					),
					array(
						'name' => 'page.deleted.author',
						'desc' => 'Displays the User who Deleted the Page',
					),
				),
				'restored'         => array(
					array(
						'name' => 'page.title',
						'desc' => 'Displays the Page Title',
					),
					array(
						'name' => 'page.restored.date',
						'desc' => 'Displays the Page Restoring Date',
					),
					array(
						'name' => 'page.restored.time',
						'desc' => 'Displays the Page Restoring Time',
					),
					array(
						'name' => 'page.restored.author',
						'desc' => 'Displays the User who Restored the Page',
					),
				),
				'additional'       => array(
					array(
						'name' => 'page.created.count',
						'desc' => 'Displays the Number of Created Pages',
					),
					array(
						'name' => 'page.updated.count',
						'desc' => 'Displays the Number of Updated Pages',
					),
					array(
						'name' => 'page.trashed.count',
						'desc' => 'Displays the Number of Trashed Pages',
					),
					array(
						'name' => 'page.restored.count',
						'desc' => 'Displays the Number of Restored Pages',
					),
					array(
						'name' => 'page.deleted.count',
						'desc' => 'Displays the Number of Deleted Pages',
					),
				),
			),
			'comments'      => array(
				'sections'         => array(
					array(
						'name' => 'section.comments.created',
						'desc' => 'Loops through Comments Created during the selected date range',
					),
					array(
						'name' => 'section.comments.updated',
						'desc' => 'Loops through Comments Updated during the selected date range',
					),
					array(
						'name' => 'section.comments.trashed',
						'desc' => 'Loops through Comments Trashed during the selected date range',
					),
					array(
						'name' => 'section.comments.deleted',
						'desc' => 'Loops through Comments Deleted during the selected date range',
					),
					array(
						'name' => 'section.comments.edited',
						'desc' => 'Loops through Comments Edited during the selected date range',
					),
					array(
						'name' => 'section.comments.restored',
						'desc' => 'Loops through Comments Restored during the selected date range',
					),
					array(
						'name' => 'section.comments.approved',
						'desc' => 'Loops through Comments Approved during the selected date range',
					),
					array(
						'name' => 'section.comments.spam',
						'desc' => 'Loops through Comments Spammed during the selected date range',
					),
					array(
						'name' => 'section.comments.replied',
						'desc' => 'Loops through Comments Replied during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'created'    => 'Created',
					'updated'    => 'Updated',
					'trashed'    => 'Trashed',
					'deleted'    => 'Deleted',
					'edited'     => 'Edited',
					'restored'   => 'Restored',
					'approved'   => 'Approved',
					'spam'       => 'Spam',
					'replied'    => 'Replied',
					'additional' => 'Additional',
				),
				'created'          => array(
					array(
						'name' => 'comment.title',
						'desc' => 'Displays the Title of the Post or the Page where the Comment is Created',
					),
					array(
						'name' => 'comment.created.date',
						'desc' => 'Displays the Comment Creating Date',
					),
					array(
						'name' => 'comment.created.time',
						'desc' => 'Displays the Comment Creating Time',
					),
					array(
						'name' => 'comment.created.author',
						'desc' => 'Displays the User who Created the Comment',
					),
				),
				'updated'          => array(
					array(
						'name' => 'comment.title',
						'desc' => 'Displays the Title of the Post or the Page where the Comment is Updated',
					),
					array(
						'name' => 'comment.updated.date',
						'desc' => 'Displays the Comment Updating Date',
					),
					array(
						'name' => 'comment.updated.time',
						'desc' => 'Displays the Comment Updating Time',
					),
					array(
						'name' => 'comment.updated.author',
						'desc' => 'Displays the User who Updated the Comment',
					),
				),
				'trashed'          => array(
					array(
						'name' => 'comment.title',
						'desc' => 'Displays the Title of the Post or the Page where the Comment is Trashed',
					),
					array(
						'name' => 'comment.trashed.date',
						'desc' => 'Displays the Comment Trashing Date',
					),
					array(
						'name' => 'comment.trashed.time',
						'desc' => 'Displays the Comment Trashing Time',
					),
					array(
						'name' => 'comment.trashed.author',
						'desc' => 'Displays the User who Trashed the Comment',
					),
				),
				'deleted'          => array(
					array(
						'name' => 'comment.title',
						'desc' => 'Displays the Title of the Post or the Page where the Comment is Deleted',
					),
					array(
						'name' => 'comment.deleted.date',
						'desc' => 'Displays the Comment Deleting Date',
					),
					array(
						'name' => 'comment.deleted.time',
						'desc' => 'Displays the Comment Deleting Time',
					),
					array(
						'name' => 'comment.deleted.author',
						'desc' => 'Displays the User who Deleted the Comment',
					),
				),
				'edited'           => array(
					array(
						'name' => 'comment.title',
						'desc' => 'Displays the Title of the Post or the Page where the Comment is Edited',
					),
					array(
						'name' => 'comment.edited.date',
						'desc' => 'Displays the Comment Editing Date',
					),
					array(
						'name' => 'comment.edited.time',
						'desc' => 'Displays the Comment Editing Time',
					),
					array(
						'name' => 'comment.edited.author',
						'desc' => 'Displays the User who Edited the Comment',
					),
				),
				'restored'         => array(
					array(
						'name' => 'comment.title',
						'desc' => 'Displays the Title of the Post or the Page where the Comment is Restored',
					),
					array(
						'name' => 'comment.restored.date',
						'desc' => 'Displays the Comment Restoring Date',
					),
					array(
						'name' => 'comment.restored.time',
						'desc' => 'Displays the Comment Restoring Time',
					),
					array(
						'name' => 'comment.restored.author',
						'desc' => 'Displays the User who Restored the Comment',
					),
				),
				'approved'         => array(
					array(
						'name' => 'comment.title',
						'desc' => 'Displays the Title of the Post or the Page where the Comment is Approved',
					),
					array(
						'name' => 'comment.approved.date',
						'desc' => 'Displays the Comment Approving Date',
					),
					array(
						'name' => 'comment.approved.time',
						'desc' => 'Displays the Comment Approving Time',
					),
					array(
						'name' => 'comment.approved.author',
						'desc' => 'Displays the User who Approved the Comment',
					),
				),
				'spam'             => array(
					array(
						'name' => 'comment.title',
						'desc' => 'Displays the Title of the Post or the Page where the Comment is Spammed',
					),
					array(
						'name' => 'comment.spam.date',
						'desc' => 'Displays the Comment Spamming Date',
					),
					array(
						'name' => 'comment.spam.time',
						'desc' => 'Displays the Comment Spamming Time',
					),
					array(
						'name' => 'comment.spam.author',
						'desc' => 'Displays the User who Spammed the Comment',
					),
				),
				'replied'          => array(
					array(
						'name' => 'comment.title',
						'desc' => 'Displays the Title of the Post or the Page where the Comment is Replied',
					),
					array(
						'name' => 'comment.replied.date',
						'desc' => 'Displays the Comment Replying Date',
					),
					array(
						'name' => 'comment.replied.time',
						'desc' => 'Displays the Comment Replying Time',
					),
					array(
						'name' => 'comment.replied.author',
						'desc' => 'Displays the User who Replied the Comment',
					),
				),
				'additional'       => array(
					array(
						'name' => 'comment.created.count',
						'desc' => 'Displays the Number of Created Comments',
					),
					array(
						'name' => 'comment.trashed.count',
						'desc' => 'Displays the Number of Trashed Comments',
					),
					array(
						'name' => 'comment.deleted.count',
						'desc' => 'Displays the Number of Deleted Comments',
					),
					array(
						'name' => 'comment.edited.count',
						'desc' => 'Displays the Number of Edited Comments',
					),
					array(
						'name' => 'comment.restored.count',
						'desc' => 'Displays the Number of Restored Comments',
					),
					array(
						'name' => 'comment.deleted.count',
						'desc' => 'Displays the Number of Deleted Comments',
					),
					array(
						'name' => 'comment.approved.count',
						'desc' => 'Displays the Number of Approved Comments',
					),
					array(
						'name' => 'comment.spam.count',
						'desc' => 'Displays the Number of Spammed Comments',
					),
					array(
						'name' => 'comment.replied.count',
						'desc' => 'Displays the Number of Replied Comments',
					),
				),
			),
			'users'         => array(
				'sections'         => array(
					array(
						'name' => 'section.users.created',
						'desc' => 'Loops through Users Created during the selected date range',
					),
					array(
						'name' => 'section.users.updated',
						'desc' => 'Loops through Users Updated during the selected date range',
					),
					array(
						'name' => 'section.users.deleted',
						'desc' => 'Loops through Users Deleted during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'created'    => 'Created',
					'updated'    => 'Updated',
					'deleted'    => 'Deleted',
					'additional' => 'Additional',
				),
				'created'          => array(
					array(
						'name' => 'user.name',
						'desc' => 'Displays the User Name',
					),
					array(
						'name' => 'user.created.date',
						'desc' => 'Displays the User Creation Date',
					),
					array(
						'name' => 'user.created.time',
						'desc' => 'Displays the User Creation Time',
					),
					array(
						'name' => 'user.created.author',
						'desc' => 'Displays the User who Created the new User',
					),
					array(
						'name' => 'user.created.role',
						'desc' => 'Displays the Role of the Created User',
					),
				),
				'updated'          => array(
					array(
						'name' => 'user.name',
						'desc' => 'Displays the User Name',
					),
					array(
						'name' => 'user.updated.date',
						'desc' => 'Displays the User Updating Date',
					),
					array(
						'name' => 'user.updated.time',
						'desc' => 'Displays the User Updating Time',
					),
					array(
						'name' => 'user.updated.author',
						'desc' => 'Displays the User who Updated the new User',
					),
					array(
						'name' => 'user.updated.role',
						'desc' => 'Displays the Role of the Updated User',
					),
				),
				'deleted'          => array(
					array(
						'name' => 'user.name',
						'desc' => 'Displays the User Name',
					),
					array(
						'name' => 'user.deleted.date',
						'desc' => 'Displays the User Deleting Date',
					),
					array(
						'name' => 'user.deleted.time',
						'desc' => 'Displays the User Deleting Time',
					),
					array(
						'name' => 'user.deleted.author',
						'desc' => 'Displays the User who Deleted the new User',
					),
				),
				'additional'       => array(
					array(
						'name' => 'user.created.count',
						'desc' => 'Displays the Number of Created Users',
					),
					array(
						'name' => 'user.updated.count',
						'desc' => 'Displays the Number of Updated Users',
					),
					array(
						'name' => 'user.deleted.count',
						'desc' => 'Displays the Number of Deleted Users',
					),
				),
			),
			'media'         => array(
				'sections'         => array(
					array(
						'name' => 'section.media.uploaded',
						'desc' => 'Loops through Media Uploaded during the selected date range',
					),
					array(
						'name' => 'section.media.updated',
						'desc' => 'Loops through Media Updated during the selected date range',
					),
					array(
						'name' => 'section.media.deleted',
						'desc' => 'Loops through Media Deleted during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'uploaded'   => 'Uploaded',
					'updated'    => 'Updated',
					'deleted'    => 'Deleted',
					'additional' => 'Additional',
				),
				'uploaded'         => array(
					array(
						'name' => 'media.name',
						'desc' => 'Displays the Media Name',
					),
					array(
						'name' => 'media.uploaded.date',
						'desc' => 'Displays the Media Uploading Date',
					),
					array(
						'name' => 'media.uploaded.time',
						'desc' => 'Displays the Media Uploading Time',
					),
					array(
						'name' => 'media.uploaded.author',
						'desc' => 'Displays the User who Uploaded the Media File',
					),
				),
				'updated'          => array(
					array(
						'name' => 'media.name',
						'desc' => 'Displays the Media Name',
					),
					array(
						'name' => 'media.updated.date',
						'desc' => 'Displays the Media Updating Date',
					),
					array(
						'name' => 'media.updated.time',
						'desc' => 'Displays the Media Updating Time',
					),
					array(
						'name' => 'media.updated.author',
						'desc' => 'Displays the User who Updted the Media File',
					),
				),
				'deleted'          => array(
					array(
						'name' => 'media.name',
						'desc' => 'Displays the Media Name',
					),
					array(
						'name' => 'media.deleted.date',
						'desc' => 'Displays the Media Deleting Date',
					),
					array(
						'name' => 'media.deleted.time',
						'desc' => 'Displays the Media Deleting Time',
					),
					array(
						'name' => 'media.deleted.author',
						'desc' => 'Displays the User who Deleted the Media File',
					),
				),
				'additional'       => array(
					array(
						'name' => 'media.uploaded.count',
						'desc' => 'Displays the Number of Uploaded Media Files',
					),
					array(
						'name' => 'media.updated.count',
						'desc' => 'Displays the Number of Updated Media Files',
					),
					array(
						'name' => 'media.deleted.count',
						'desc' => 'Displays the Number of Deleted Media Files',
					),
				),
			),
			'widgets'       => array(
				'sections'         => array(
					array(
						'name' => 'section.widgets.added',
						'desc' => 'Loops through Widgets Added during the selected date range',
					),
					array(
						'name' => 'section.widgets.updated',
						'desc' => 'Loops through Widgets Updated during the selected date range',
					),
					array(
						'name' => 'section.widgets.deleted',
						'desc' => 'Loops through Widgets Deleted during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'added'      => 'Added',
					'updated'    => 'Updated',
					'deleted'    => 'Deleted',
					'additional' => 'Additional',
				),
				'added'            => array(
					array(
						'name' => 'widget.title',
						'desc' => 'Displays the Widget Title',
					),
					array(
						'name' => 'widget.added.area',
						'desc' => 'Displays the Widget Adding Area',
					),
					array(
						'name' => 'widget.added.date',
						'desc' => 'Displays the Widget Adding Date',
					),
					array(
						'name' => 'widget.added.time',
						'desc' => 'Displays the Widget Adding Time',
					),
					array(
						'name' => 'widget.added.author',
						'desc' => 'Displays the User who Added the Widget',
					),
				),
				'updated'          => array(
					array(
						'name' => 'widget.title',
						'desc' => 'Displays the Widget Name',
					),
					array(
						'name' => 'widget.updated.area',
						'desc' => 'Displays the Widget Updating Area',
					),
					array(
						'name' => 'widget.updated.date',
						'desc' => 'Displays the Widget Updating Date',
					),
					array(
						'name' => 'widget.updated.time',
						'desc' => 'Displays the Widget Updating Time',
					),
					array(
						'name' => 'widget.updated.author',
						'desc' => 'Displays the User who Updated the Widget',
					),
				),
				'deleted'          => array(
					array(
						'name' => 'widget.title',
						'desc' => 'Displays the Widget Name',
					),
					array(
						'name' => 'widget.deleted.area',
						'desc' => 'Displays the Widget Deleting Area',
					),
					array(
						'name' => 'widget.deleted.date',
						'desc' => 'Displays the Widget Deleting Date',
					),
					array(
						'name' => 'widget.deleted.time',
						'desc' => 'Displays the Widget Deleting Time',
					),
					array(
						'name' => 'widget.deleted.author',
						'desc' => 'Displays the User who Deleted the Widget',
					),
				),
				'additional'       => array(
					array(
						'name' => 'widget.added.count',
						'desc' => 'Displays the Number of Added Widgets',
					),
					array(
						'name' => 'widget.updated.count',
						'desc' => 'Displays the Number of Updated Widgets',
					),
					array(
						'name' => 'widget.deleted.count',
						'desc' => 'Displays the Number of Deleted Widgets',
					),
				),
			),
			'menus'         => array(
				'sections'         => array(
					array(
						'name' => 'section.menus.created',
						'desc' => 'Loops through Menus Created during the selected date range',
					),
					array(
						'name' => 'section.menus.updated',
						'desc' => 'Loops through Menus Updated during the selected date range',
					),
					array(
						'name' => 'section.menus.deleted',
						'desc' => 'Loops through Menus Deleted during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'created'    => 'Created',
					'updated'    => 'Updated',
					'deleted'    => 'Deleted',
					'additional' => 'Additional',
				),
				'created'          => array(
					array(
						'name' => 'menu.title',
						'desc' => 'Displays the Menu Name',
					),
					array(
						'name' => 'menu.created.date',
						'desc' => 'Displays the Menu Creation Date',
					),
					array(
						'name' => 'menu.created.time',
						'desc' => 'Displays the Menu Creation Time',
					),
					array(
						'name' => 'menu.created.author',
						'desc' => 'Displays the User who Created the Menu',
					),
				),
				'updated'          => array(
					array(
						'name' => 'menu.title',
						'desc' => 'Displays the Menu Name',
					),
					array(
						'name' => 'menu.updated.date',
						'desc' => 'Displays the Menu Updating Date',
					),
					array(
						'name' => 'menu.updated.time',
						'desc' => 'Displays the Menu Updating Time',
					),
					array(
						'name' => 'menu.updated.author',
						'desc' => 'Displays the User who Updated the Menu',
					),
				),
				'deleted'          => array(
					array(
						'name' => 'menu.title',
						'desc' => 'Displays the Menu Name',
					),
					array(
						'name' => 'menu.deleted.date',
						'desc' => 'Displays the Menu Deleting Date',
					),
					array(
						'name' => 'menu.deleted.time',
						'desc' => 'Displays the Menu Deleting Time',
					),
					array(
						'name' => 'menu.deleted.author',
						'desc' => 'Displays the User who Deleted the Menu',
					),
				),
				'additional'       => array(
					array(
						'name' => 'menu.created.count',
						'desc' => 'Displays the Number of Created Menus',
					),
					array(
						'name' => 'menu.updated.count',
						'desc' => 'Displays the Number of Updated Menus',
					),
					array(
						'name' => 'menu.deleted.count',
						'desc' => 'Displays the Number of Deleted Menus',
					),
				),
			),
			'wordpress'     => array(
				'sections'         => array(
					array(
						'name' => 'section.wordpress.updated',
						'desc' => 'Loops through WordPress Updates during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'updated'    => 'Updated',
					'additional' => 'Additional',
				),
				'updated'          => array(
					array(
						'name' => 'wordpress.updated.date',
						'desc' => 'Displays the WordPress Update Date',
					),
					array(
						'name' => 'wordpress.updated.time',
						'desc' => 'Displays the WordPress Update Time',
					),
					array(
						'name' => 'wordpress.updated.author',
						'desc' => 'Displays the User who Updated the Site',
					),
				),
				'additional'       => array(
					array(
						'name' => 'wordpress.old.version',
						'desc' => 'Displays the WordPress Version Before Update',
					),
					array(
						'name' => 'wordpress.current.version',
						'desc' => 'Displays the Current WordPress Version',
					),
					array(
						'name' => 'wordpress.updated.count',
						'desc' => 'Displays the Number of WordPress Updates',
					),
				),
			),
			'backups'       => array(
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'created'    => 'Created',
					'additional' => 'Additional',
				),
				'sections'         => array(
					array(
						'name' => 'section.backups.created',
						'desc' => ' Loops through Backups Created during the selected date range',
					),
				),
				'created'          => array(
					array(
						'name' => 'backup.created.type',
						'desc' => ' Displays the Created Backup type (Full or Database)',
					),
					array(
						'name' => 'backup.created.date',
						'desc' => 'Displays the Backups Creation Date',
					),
					array(
						'name' => 'backup.created.time',
						'desc' => 'Displays the Backups Creation Time',
					),
					// array("name" => "backup.created.destination", "desc" => "Displays the Created Backup destination")
				),
				'additional'       => array(
					array(
						'name' => 'backup.created.count',
						'desc' => 'Displays the number of created backups during the selected date range',
					),
				),
			),
			'report'        => array(
				'nav_group_tokens' => array( 'report' => 'Report' ),
				'report'           => array(
					array(
						'name' => 'report.daterange',
						'desc' => 'Displays the report date range',
					),
					array(
						'name' => 'report.send.date',
						'desc' => 'Displays the report send date',
					),
				),
			),

			'sucuri'        => array(
				'sections'         => array(
					array(
						'name' => 'section.sucuri.checks',
						'desc' => 'Loops through Security Checks during the selected date range',
					),
				),
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'check'      => 'Checks',
					'additional' => 'Additional',
				),
				'check'            => array(
					array(
						'name' => 'sucuri.check.date',
						'desc' => 'Displays the Security Check date',
					),
					array(
						'name' => 'sucuri.check.time',
						'desc' => 'Displays the Security Check time',
					),
					array(
						'name' => 'sucuri.check.status',
						'desc' => 'Displays the Status info for the Child Site',
					),
					array(
						'name' => 'sucuri.check.webtrust',
						'desc' => 'Displays the Webtrust info for the Child Site',
					),
					// array("name" => "sucuri.check.results", "desc" => "Displays the Security Check details from the Security Scan Report"),
				),
				'additional'       => array(
					array(
						'name' => 'sucuri.checks.count',
						'desc' => 'Displays the number of performed security checks during the selected date range',
					),
				),
			),
			'ga'            => array(
				'nav_group_tokens' => array(
					'ga' => 'GA',
				),
				'ga'               => array(
					array(
						'name' => 'ga.visits',
						'desc' => 'Displays the Number Visits during the selected date range',
					),
					array(
						'name' => 'ga.pageviews',
						'desc' => 'Displays the Number of Page Views during the selected date range',
					),
					array(
						'name' => 'ga.pages.visit',
						'desc' => 'Displays the Number of Page visit during the selected date range',
					),
					array(
						'name' => 'ga.bounce.rate',
						'desc' => 'Displays the Bounce Rate during the selected date range',
					),
					array(
						'name' => 'ga.avg.time',
						'desc' => 'Displays the Average Visit Time during the selected date range',
					),
					array(
						'name' => 'ga.new.visits',
						'desc' => 'Displays the Number of New Visits during the selected date range',
					),
					array(
						'name' => 'ga.visits.chart',
						'desc' => 'Displays a chart for the activity over the past month',
					),
					array(
						'name' => 'ga.visits.maximum',
						'desc' => "Displays the maximum visitor number and it's day within the past month",
					),
					array(
						'name' => 'ga.startdate',
						'desc' => 'Displays the startdate for the chart',
					),
					array(
						'name' => 'ga.enddate',
						'desc' => 'Displays the enddate or the chart',
					),
				),
			),
			'piwik'         => array(
				'nav_group_tokens' => array(
					'piwik' => 'Piwik',
				),
				'piwik'            => array(
					array(
						'name' => 'piwik.visits',
						'desc' => 'Displays the Number Visits during the selected date range',
					),
					array(
						'name' => 'piwik.pageviews',
						'desc' => 'Displays the Number of Page Views during the selected date range',
					),
					array(
						'name' => 'piwik.pages.visit',
						'desc' => 'Displays the Number of Page visit during the selected date range',
					),
					array(
						'name' => 'piwik.bounce.rate',
						'desc' => 'Displays the Bounce Rate during the selected date range',
					),
					array(
						'name' => 'piwik.avg.time',
						'desc' => 'Displays the Average Visit Time during the selected date range',
					),
					array(
						'name' => 'piwik.new.visits',
						'desc' => 'Displays the Number of New Visits during the selected date range',
					),
				),
			),
			'aum'           => array(
				'nav_group_tokens' => array(
					'aum' => 'AUM',
				),
				'aum'              => array(
					array(
						'name' => 'aum.alltimeuptimeratio',
						'desc' => 'Displays the Uptime ratio from the moment the monitor has been created',
					),
					array(
						'name' => 'aum.uptime7',
						'desc' => 'Displays the Uptime ratio for last 7 days',
					),
					array(
						'name' => 'aum.uptime15',
						'desc' => 'Displays the Uptime ration for last 15 days',
					),
					array(
						'name' => 'aum.uptime30',
						'desc' => 'Displays the Uptime ration for last 30 days',
					),
					array(
						'name' => 'aum.uptime45',
						'desc' => 'Displays the Uptime ration for last 45 days',
					),
					array(
						'name' => 'aum.uptime60',
						'desc' => 'Displays the Uptime ration for last 60 days',
					),
					array(
						'name' => 'aum.stats',
						'desc' => 'Displays the Uptime Statistics',
					),
				),
			),
			'woocomstatus'  => array(
				'nav_group_tokens' => array(
					'woocomstatus' => 'WooCommerce Status',
				),
				'woocomstatus'     => array(
					array(
						'name' => 'wcomstatus.sales',
						'desc' => 'Displays total sales during the selected data range',
					),
					array(
						'name' => 'wcomstatus.topseller',
						'desc' => 'Displays the top seller product during the selected data range',
					),
					array(
						'name' => 'wcomstatus.awaitingprocessing',
						'desc' => 'Displays the number of products currently awaiting for processing',
					),
					array(
						'name' => 'wcomstatus.onhold',
						'desc' => 'Displays the number of orders currently on hold',
					),
					array(
						'name' => 'wcomstatus.lowonstock',
						'desc' => 'Displays the number of products currently low on stock',
					),
					array(
						'name' => 'wcomstatus.outofstock',
						'desc' => 'Displays the number of products currently out of stock',
					),
				),
			),
			'wordfence'     => array(
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'scan'       => 'Scan',
					'additional' => 'Additional',
				),
				'sections'         => array(
					array(
						'name' => 'section.wordfence.scan',
						'desc' => 'Loops through Wordfence scans during the selected date range',
					),
				),
				'scan'             => array(
					array(
						'name' => 'wordfence.scan.result',
						'desc' => 'Displays the Wordfence scan result',
					),
					array(
						'name' => 'wordfence.scan.date',
						'desc' => 'Displays the Wordfence scan date',
					),
					array(
						'name' => 'wordfence.scan.time',
						'desc' => 'Displays the Wordfence scan time',
					),
					array(
						'name' => 'wordfence.scan.details',
						'desc' => 'Displays the Wordfence scan details',
					),
				),
				'additional'       => array(
					array(
						'name' => 'wordfence.scan.count',
						'desc' => 'Displays the number of performed Wordfence scans during the selected date range',
					),
				),
			),
			'maintenance'   => array(
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'process'    => 'Process',
					'additional' => 'Additional',
				),
				'sections'         => array(
					array(
						'name' => 'section.maintenance.process',
						'desc' => 'Loops through performed Maintenance actions',
					),
				),
				'process'          => array(
					array(
						'name' => 'maintenance.process.result',
						'desc' => 'Displays the status of performed Maintenance',
					),
					array(
						'name' => 'maintenance.process.date',
						'desc' => 'Displays the date of performed Maintenance',
					),
					array(
						'name' => 'maintenance.process.time',
						'desc' => 'Displays the time of performed Maintenance',
					),
					array(
						'name' => 'maintenance.process.details',
						'desc' => 'Displays performed actions',
					),
				),
				'additional'       => array(
					array(
						'name' => 'maintenance.process.count',
						'desc' => 'Displays the number of performed Maintenance actions during the selected date range',
					),
				),
			),
			'pagespeed'     => array(
				'nav_group_tokens' => array(
					'pagespeed' => 'Page speed',
				),
				'pagespeed'        => array(
					array(
						'name' => 'pagespeed.average.desktop',
						'desc' => 'Displays the average desktop page-speed score at the moment of report generation',
					),
					array(
						'name' => 'pagespeed.average.mobile',
						'desc' => 'Displays the average mobile page-speed score at the moment of report creation',
					),
				),
			),
			'virusdie'      => array(
				'nav_group_tokens' => array(
					'sections'   => 'Sections',
					'scan'       => 'Scans',
					'additional' => 'Additional',
				),
				'sections'         => array(
					array(
						'name' => 'section.virusdie.scans',
						'desc' => 'Loops through scans during the selected period',
					),
				),
				'scan'             => array(
					array(
						'name' => 'virusdie.scan.time',
						'desc' => 'Returns the time of scan',
					),
					array(
						'name' => 'virusdie.scan.date',
						'desc' => 'Returns the date of scan',
					),
					array(
						'name' => 'virusdie.scan.status',
						'desc' => 'Returns scan status (there are a few available, Sync Error, Threats were found, and No Threats found)',
					),
					array(
						'name' => 'virusdie.scan.details',
						'desc' => 'Returns the scan details in a list like: Files scaned: xxx; Malicious: xxx; Suspicious: xxx; .....',
					),
				),
				'additional'       => array(
					array(
						'name' => 'virusdie.scans.count',
						'desc' => 'Displays the number of scans performed during the selected period',
					),
				),
			),
			'vulnerable'    => array(
				'nav_group_tokens' => array(
					'vulnerable' => 'Vulnerability',
				),
				'vulnerable'       => array(
					array(
						'name' => 'vulnerable.plugins',
						'desc' => 'Displays the vulnerable plugins score at the moment of report generation',
					),
					array(
						'name' => 'vulnerable.themes',
						'desc' => 'Displays the vulnerable themes score at the moment of report creation',
					),
					array(
						'name' => 'vulnerabilities.count',
						'desc' => 'Displays the vulnerabilities count score at the moment of report creation',
					),
				),
			),
			'lighthouse'    => array(
				'nav_group_tokens' => array(
					'lighthouse' => 'Lighthouse',
				),
				'lighthouse'       => array(
					array(
						'name' => 'lighthouse.performance.desktop',
						'desc' => 'Displays the average desktop performance score at the moment of report generation',
					),
					array(
						'name' => 'lighthouse.performance.mobile',
						'desc' => 'Displays the average mobile performance score at the moment of report creation',
					),
					array(
						'name' => 'lighthouse.accessibility.desktop',
						'desc' => 'Displays the average desktop accessibility score at the moment of report generation',
					),
					array(
						'name' => 'lighthouse.accessibility.mobile',
						'desc' => 'Displays the average mobile accessibility score at the moment of report creation',
					),
					array(
						'name' => 'lighthouse.bestpractices.desktop',
						'desc' => 'Displays the average desktop best practices score at the moment of report generation',
					),
					array(
						'name' => 'lighthouse.bestpractices.mobile',
						'desc' => 'Displays the average mobile best practices score at the moment of report creation',
					),
					array(
						'name' => 'lighthouse.seo.desktop',
						'desc' => 'Displays the average desktop seo score at the moment of report generation',
					),
					array(
						'name' => 'lighthouse.seo.mobile',
						'desc' => 'Displays the average mobile seo score at the moment of report creation',
					),
					array(
						'name' => 'lighthouse.audits.desktop',
						'desc' => 'Displays the average desktop audits at the moment of report generation',
					),
					array(
						'name' => 'lighthouse.audits.mobile',
						'desc' => 'Displays the average mobile audits at the moment of report creation',
					),
					array(
						'name' => 'lighthouse.lastcheck.desktop',
						'desc' => 'Displays the average desktop last check at the moment of report generation',
					),
					array(
						'name' => 'lighthouse.lastcheck.mobile',
						'desc' => 'Displays the average mobile last check at the moment of report creation',
					),
				),
			),
			'domainmonitor' => array(
				'nav_group_tokens' => array(
					'domainmonitor' => 'Domain monitor',
				),
				'domainmonitor'    => array(
					array(
						'name' => 'domain.monitor.domain.name',
						'desc' => 'Displays the domain name',
					),
					array(
						'name' => 'domain.monitor.registrar',
						'desc' => 'Displays the domain registrar',
					),
					array(
						'name' => 'domain.monitor.updated.date',
						'desc' => 'Displays the monitor updated date',
					),
					array(
						'name' => 'domain.monitor.creation.date',
						'desc' => 'Displays the domain monitor creation date',
					),
					array(
						'name' => 'domain.monitor.expiry.date',
						'desc' => 'Displays the domain monitor expiry date',
					),
					array(
						'name' => 'domain.monitor.expires',
						'desc' => 'Displays the domain monitor expires',
					),
					array(
						'name' => 'domain.monitor.status',
						'desc' => 'Displays the domain monitor status',
					),
					array(
						'name' => 'domain.monitor.last.check',
						'desc' => 'Displays the domain monitor last check',
					),
				),
			),
		);
		// used to fill data and support hide-if-empty
		self::$data_tokens = array(
			'[sucuri.check.date]'             => '',
			'[sucuri.check.time]'             => '',
			'[sucuri.check.status]'           => '',
			'[sucuri.check.webtrust]'         => '',
			'[sucuri.check.count]'            => '',

			'[ga.visits]'                     => '',
			'[ga.pageviews]'                  => '',
			'[ga.pages.visit]'                => '',
			'[ga.bounce.rate]'                => '',
			'[ga.avg.time]'                   => '',
			'[ga.new.visits]'                 => '',
			'[ga.visits.chart]'               => '',
			'[ga.visits.maximum]'             => '',
			'[ga.startdate]'                  => '',
			'[ga.enddate]'                    => '',

			'[piwik.visits]'                  => '',
			'[piwik.pageviews]'               => '',
			'[piwik.pages.visit]'             => '',
			'[piwik.bounce.rate]'             => '',
			'[piwik.avg.time]'                => '',
			'[piwik.new.visits]'              => '',

			'[aum.alltimeuptimeratio]'        => '',
			'[aum.uptime7]'                   => '',
			'[aum.uptime15]'                  => '',
			'[aum.uptime30]'                  => '',
			'[aum.uptime45]'                  => '',
			'[aum.uptime60]'                  => '',
			'[aum.stats]'                     => '',

			'[wcomstatus.sales]'              => '',
			'[wcomstatus.topseller]'          => '',
			'[wcomstatus.awaitingprocessing]' => '',
			'[wcomstatus.onhold]'             => '',
			'[wcomstatus.lowonstock]'         => '',
			'[wcomstatus.outofstock]'         => '',

			'[wordfence.scan.result]'         => '',
			'[wordfence.scan.date]'           => '',
			'[wordfence.scan.time]'           => '',
			'[wordfence.scan.details]'        => '',
			'[wordfence.scan.count]'          => '',

			'[maintenance.process.result]'    => '',
			'[maintenance.process.date]'      => '',
			'[maintenance.process.time]'      => '',
			'[maintenance.process.details]'   => '',
			'[maintenance.process.count]'     => '',

			'[pagespeed.average.desktop]'     => '',
			'[pagespeed.average.mobile]'      => '',
		);
	}

	public function admin_init() {

		add_action( 'wp_ajax_mainwp_pro_reports_delete_token', array( &$this, 'delete_token' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_save_token', array( &$this, 'save_token' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_do_action_report', array( &$this, 'ajax_do_action_report' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_load_sites', array( &$this, 'ajax_load_sites' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_generate_report', array( &$this, 'ajax_generate_report_content' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_email_message_preview', array( &$this, 'ajax_email_message_preview' ), 10, 3 );
		add_action( 'mainwp_added_new_site', array( &$this, 'update_site_update_tokens' ), 8, 1 );
		add_action( 'mainwp_update_site', array( &$this, 'update_site_update_tokens' ), 8, 1 );
		add_action( 'mainwp_delete_site', array( &$this, 'delete_site_delete_tokens' ), 8, 1 );
		add_action( 'mainwp_shortcuts_widget', array( &$this, 'shortcuts_widget' ), 10, 1 );
		add_filter( 'mainwp_managesites_column_url', array( &$this, 'managesites_column_url' ), 10, 2 );
		add_action( 'mainwp_managesite_backup', array( &$this, 'managesite_backup' ), 10, 3 );

		self::$enabled_piwik         = is_plugin_active( 'mainwp-piwik-extension/mainwp-piwik-extension.php' ) ? true : false;
		self::$enabled_sucuri        = is_plugin_active( 'mainwp-sucuri-extension/mainwp-sucuri-extension.php' ) ? true : false;
		self::$enabled_ga            = is_plugin_active( 'mainwp-google-analytics-extension/mainwp-google-analytics-extension.php' ) ? true : false;
		self::$enabled_aum           = is_plugin_active( 'advanced-uptime-monitor-extension/advanced-uptime-monitor-extension.php' ) ? true : false;
		self::$enabled_woocomstatus  = is_plugin_active( 'mainwp-woocommerce-status-extension/mainwp-woocommerce-status-extension.php' ) ? true : false;
		self::$enabled_wordfence     = is_plugin_active( 'mainwp-wordfence-extension/mainwp-wordfence-extension.php' ) ? true : false;
		self::$enabled_maintenance   = is_plugin_active( 'mainwp-maintenance-extension/mainwp-maintenance-extension.php' ) ? true : false;
		self::$enabled_pagespeed     = is_plugin_active( 'mainwp-page-speed-extension/mainwp-page-speed-extension.php' ) ? true : false;
		self::$enabled_virusdie      = is_plugin_active( 'mainwp-virusdie-extension/mainwp-virusdie-extension.php' ) ? true : false;
		self::$enabled_vulnerable    = is_plugin_active( 'mainwp-vulnerability-checker-extension/mainwp-vulnerability-checker-extension.php' ) ? true : false;
		self::$enabled_lighthouse    = is_plugin_active( 'mainwp-lighthouse-extension/mainwp-lighthouse-extension.php' ) ? true : false;
		self::$enabled_domainmonitor = is_plugin_active( 'mainwp-domain-monitor-extension/mainwp-domain-monitor-extension.php' ) ? true : false;

		self::$pro_tokens = apply_filters( 'mainwp_pro_reports_tokens_groups', self::$pro_tokens );

		add_filter( 'mainwp_pro_reports_get_tokens_value', array( $this, 'hook_get_tokens_value' ), 10, 5 );

	}

	public static function showMainWPMessage( $type, $notice_id ) {
		$status = get_user_option( 'mainwp_notice_saved_status' );
		if ( ! is_array( $status ) ) {
			$status = array();
		}
		if ( isset( $status[ $notice_id ] ) ) {
			return false;
		}
		return true;
	}

	function managesite_backup( $website, $args, $information ) {
		if ( empty( $website ) ) {
			return;
		}

		$type = isset( $args['type'] ) ? $args['type'] : '';

		if ( empty( $type ) ) {
			return;
		}

		global $mainWPProReportsExtensionActivator;

		$backup_type = ( 'full' == $type ) ? 'Full' : ( 'db' == $type ? 'Database' : '' );

		$message       = '';
		$backup_status = 'success';
		$backup_size   = 0;
		if ( isset( $information['error'] ) ) {
			$message       = $information['error'];
			$backup_status = 'failed';
		} elseif ( 'db' == $type && ! $information['db'] ) {
			$message       = 'Database backup failed.';
			$backup_status = 'failed';
		} elseif ( 'full' == $type && ! $information['full'] ) {
			$message       = 'Full backup failed.';
			$backup_status = 'failed';
		} elseif ( isset( $information['db'] ) ) {
			if ( false != $information['db'] ) {
				$message = 'Backup database success.';
			} elseif ( false != $information['full'] ) {
				$message = 'Full backup success.';
			}
			if ( isset( $information['size'] ) ) {
				$backup_size = $information['size'];
			}
		} else {
			$message       = 'Database backup failed due to an undefined error';
			$backup_status = 'failed';
		}

		// save results to child site stream
		$post_data = array(
			'mwp_action'  => 'save_backup_stream',
			'size'        => $backup_size,
			'message'     => $message,
			'destination' => 'Local Server',
			'status'      => $backup_status,
			'type'        => $backup_type,
		);
		apply_filters( 'mainwp_fetchurlauthed', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $website->id, 'client_report', $post_data );
	}

	public static function managesite_schedule_backup( $website, $args, $backupResult ) {

		if ( empty( $website ) ) {
			return; }

		$type = isset( $args['type'] ) ? $args['type'] : '';
		if ( empty( $type ) ) {
			return; }

		$destination = '';
		if ( is_array( $backupResult ) ) {
			$error = false;
			if ( isset( $backupResult['error'] ) ) {
				$destination .= $backupResult['error'] . '<br />';
				$error        = true;
			}

			if ( isset( $backupResult['ftp'] ) ) {
				if ( 'success' != $backupResult['ftp'] ) {
					$destination .= 'FTP: ' . $backupResult['ftp'] . '<br />';
					$error        = true;
				} else {
					$destination .= 'FTP: success<br />';
				}
			}

			if ( isset( $backupResult['dropbox'] ) ) {
				if ( 'success' != $backupResult['dropbox'] ) {
					$destination .= 'Dropbox: ' . $backupResult['dropbox'] . '<br />';
					$error        = true;
				} else {
					$destination .= 'Dropbox: success<br />';
				}
			}
			if ( isset( $backupResult['amazon'] ) ) {
				if ( 'success' != $backupResult['amazon'] ) {
					$destination .= 'Amazon: ' . $backupResult['amazon'] . '<br />';
					$error        = true;
				} else {
					$destination .= 'Amazon: success<br />';
				}
			}

			if ( isset( $backupResult['copy'] ) ) {
				if ( 'success' != $backupResult['copy'] ) {
					$destination .= 'Copy.com: ' . $backupResult['amazon'] . '<br />';
					$error        = true;
				} else {
					$destination .= 'Copy.com: success<br />';
				}
			}

			if ( empty( $destination ) ) {
				$destination = 'Local Server';
			}
		} else {
			$destination = $backupResult;
		}

		if ( 'full' == $type ) {
			$message     = 'Schedule full backup.';
			$backup_type = 'Full';
		} else {
			$message     = 'Schedule database backup.';
			$backup_type = 'Database';
		}

		global $mainWPProReportsExtensionActivator;

		// save results to child site stream
		$post_data = array(
			'mwp_action'  => 'save_backup_stream',
			'size'        => 'N/A',
			'message'     => $message,
			'destination' => $destination,
			'status'      => 'N/A',
			'type'        => $backup_type,
		);
		apply_filters( 'mainwp_fetchurlauthed', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $website->id, 'client_report', $post_data );
	}

	function mainwp_postprocess_backup_sites_feedback( $output, $unique ) {
		if ( ! is_array( $output ) ) {

		} else {
			foreach ( $output as $key => $value ) {
				$output[ $key ] = $value;
			}
		}

		return $output;
	}

	/**
	 * Get Time Stamp from $hh_mm.
	 *
	 * @param mixed $hh_mm Global time stamp variable.
	 *
	 * @return time Y-m-d 00:00:59.
	 */
	public static function get_timestamp_from_hh_mm( $hh_mm ) {
		$hh_mm = explode( ':', $hh_mm );
		$_hour = isset( $hh_mm[0] ) ? intval( $hh_mm[0] ) : 0;
		$_mins = isset( $hh_mm[1] ) ? intval( $hh_mm[1] ) : 0;
		if ( $_hour < 0 || $_hour > 23 ) {
			$_hour = 0;
		}
		if ( $_mins < 0 || $_mins > 59 ) {
			$_mins = 0;
		}
		return strtotime( date( 'Y-m-d' ) . ' ' . $_hour . ':' . $_mins . ':59' );
	}

	public static function cron_send_reports() {

		$send_local_time  = apply_filters( 'mainwp_pro_reports_send_local_time', false );
		$timestamp_offset = 0;
		if ( $send_local_time ) {
			$gmtOffset        = get_option( 'gmt_offset' );
			$timestamp_offset = $gmtOffset * HOUR_IN_SECONDS;
		}

		$now        = time();
		$time_check = $now + $timestamp_offset;

		$mainwpLastCheck = get_option( 'mainwp_reports_sendcheck_last' );
		if ( $mainwpLastCheck == date( 'd/m/Y', $time_check ) ) {
			return;
		}

		do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: Start check reports today', MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );

		/**
		* Fires before reports start checking to send.
		*
		* @since 4.0.1
		*/
		 do_action( 'mainwp_pro_reports_before_send', $mainwpLastCheck );

		$allReportsToSend = array();

		$allReadyReports = MainWP_Pro_Reports_DB::get_instance()->get_scheduled_reports_to_send( $timestamp_offset );

		if ( empty( $allReadyReports ) ) {
			do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: Checked reports today', MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
			update_option( 'mainwp_reports_sendcheck_last', date( 'd/m/Y', time() ) ); // UTC date.
		}

		foreach ( $allReadyReports as $report ) {
				// CHECK: to prevent auto send too quick.
			if ( $report->schedule_lastsend > time() - 6 * HOUR_IN_SECONDS ) { // 6 hours.
				continue;
			}

			$send_at_hh_mm = apply_filters( 'mainwp_pro_reports_send_at_time', false, $report );

			if ( ! empty( $send_at_hh_mm ) ) {
				$send_timestamp = self::get_timestamp_from_hh_mm( $send_at_hh_mm );
				if ( $time_check < $send_timestamp ) {
					continue;
				}
			}

			$cal_recurring = self::calc_recurring_date( $report->recurring_schedule, $report->recurring_day ); // to cron job, pass offset date time support local time

			if ( empty( $cal_recurring ) ) {
				do_action( 'mainwp_log_action', 'Pro Reports :: FAILED :: CRON :: Failed to calculate recurring date :: ' . $report->title, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
				continue;
			}

			$log_time = date( 'Y-m-d H:i:s', $cal_recurring['date_send'] );
			do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: found report id ' . $report->id . ', next send: ' . $log_time, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );

			// to fix: send current day/month/year... issue.
			$values    = array(
				'date_from_nextsend' => $cal_recurring['date_from'],
				'date_to_nextsend'   => $cal_recurring['date_to'],
				'schedule_nextsend'  => $cal_recurring['date_send'], // this field using to check if current time > schedule nextsend then prepare report to send
				'noticed'            => 0, // when current time - schedule_nextsend <= 24h then send notice to administrator
			);
			$date_from = $report->date_from_nextsend;
			$date_to   = $report->date_to_nextsend;
			if ( ! empty( $date_from ) ) {
					// using to generate report content to send now
					$values['date_from'] = $date_from;
					$values['date_to']   = $date_to;
			}
			MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, $values );
			$allReportsToSend[] = $report;
		}

		unset( $allReadyReports );

		do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: reports to send :: Found ' . count( $allReportsToSend ) . ' reports to send.', MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );

		foreach ( $allReportsToSend as $report ) {
			// update report to start sending
			MainWP_Pro_Reports_DB::get_instance()->update_reports_send( $report->id );
		}
	}

	public static function cron_continue_send_reports() {

		@ignore_user_abort( true );
		@set_time_limit( 0 );
		$mem = '512M';
		@ini_set( 'memory_limit', $mem );
		@ini_set( 'max_execution_time', 0 );

		$reports = MainWP_Pro_Reports_DB::get_instance()->get_scheduled_reports_to_continue_send();

		/**
		 * Fires before reports continue to send.
		 *
		 * $reports will continue to send.
		 *
		 * @since 4.0.1
		 */

		do_action( 'mainwp_pro_reports_before_continue_send', $reports );

		if ( empty( $reports ) ) {
			return;
		}

		// process one report
		$report = current( $reports );

		do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: continue send :: ' . $report->title, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );

		$sites  = unserialize( base64_decode( $report->sites ) );
		$groups = unserialize( base64_decode( $report->groups ) );

		if ( ! is_array( $sites ) ) {
			$sites = array();
		}

		if ( ! is_array( $groups ) ) {
			$groups = array();
		}

		$chunkSend = apply_filters( 'mainwp_pro_reports_chunk_send_number', 1 ); // 1 to fix.

		if ( $chunkSend > 3 || $chunkSend < 0 ) {
			$chunkSend = 1;
		}

		$countSend = 0;

		global $mainWPProReportsExtensionActivator;

		$dbwebsites_indexed = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $sites, $groups );

		$total_sites = ! empty( $dbwebsites_indexed ) ? count( $dbwebsites_indexed ) : 0;

		do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: Send total :: ' . $total_sites . ' sites', MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );

		if ( $total_sites > 0 ) {

			$dbwebsites  = array();
			$all_siteids = array();
			foreach ( $dbwebsites_indexed as $value ) {
				$dbwebsites[]  = $value;
				$all_siteids[] = $value->id;
			}
			unset( $dbwebsites_indexed );

			$sendme = true;

			$idx = 0;

			$completedSites = MainWP_Pro_Reports_DB::get_instance()->get_completed_websites( $report->id );

			while ( $sendme && ( $idx < $total_sites ) ) {

				$dbsite = $dbwebsites[ $idx ];

				$website = MainWP_Pro_Reports_Utility::map_site( $dbsite, array( 'id', 'name', 'url' ) );
				$site_id = $website['id'];

				$lasttime = time(); // UTC time.
				$values   = array(
					'lastsend' => $lasttime,  // to display last send time.
				);
				MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, $values );
				MainWP_Pro_Reports_DB::get_instance()->updateWebsiteOption( $site_id, 'creport_last_report', $lasttime );

				$idx++; // count to next site.

				if ( isset( $completedSites[ $site_id ] ) ) {
					continue;
				}

				$completedSites[ $site_id ] = 5; // preparing content.
				self::update_completed_websites( $report, $completedSites, $all_siteids );

				do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: preparing content sending report for site :: ' . $website['url'], MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );

				$data = self::prepare_content_report_email( $report, false, $website, true );

				// put this here.
				$countSend++;

				if ( ! is_array( $data ) ) {
					do_action( 'mainwp_log_action', 'Pro Reports :: ERROR :: CRON :: Generate content :: ' . $website['url'], MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
					$completedSites[ $site_id ] = 3; // generated content failed.
					self::update_completed_websites( $report, $completedSites, $all_siteids );
				} elseif ( empty( $data['to_email'] ) || ( false === stripos( $data['to_email'], '@' ) ) ) {
					do_action( 'mainwp_log_action', 'Pro Reports :: ERROR :: CRON :: Wrong send to email :: [' . $data['to_email'] . '] :: site id :: ' . $site_id, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
					$completedSites[ $site_id ] = 1;
					self::update_completed_websites( $report, $completedSites, $all_siteids );
				} else {
					$templ_email = $data['templ_email'];

					$disabled = false;

					/**
					*
					* Filters disabled send reports.
					*
					* @since 4.0.0
					*
					* @param bool  $disabled to disable send reports .
					* @param array $data            The report data.
					* @param html|text $templ_email     The report template
					* @param int $report_id         id of the report
					* @param int $site_id           id of site
					*/
					$disabled = apply_filters( 'mainwp_pro_reports_disable_send_scheduled_reports', $disabled, $data, $templ_email, $report->id, $site_id );

					/*
					* Perform send scheduled report email
					* see send_onetime_report_email()
					*/
					$email_subject = stripslashes( $data['subject'] );

					if ( $disabled !== true && wp_mail( $data['to_email'], $email_subject, $templ_email, $data['header'], $data['attachments'] ) ) {
							do_action( 'mainwp_log_action', 'Pro Reports :: SUCCESS :: CRON :: Send report success - website :: ' . $website['url'] . ' :: Subject :: ' . $email_subject, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
							$completedSites[ $site_id ] = 1;
							self::update_completed_websites( $report, $completedSites, $all_siteids );
					} else {
							do_action( 'mainwp_log_action', 'Pro Reports :: FAILED :: CRON :: Send report failed - website :: ' . $website['url'] . ' :: Subject :: ' . $email_subject, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
							/*
							* If failed send report
							* update completed sites to prevent re-send report multi-time
							*/
							$completedSites[ $site_id ] = 0;
							self::update_completed_websites( $report, $completedSites, $all_siteids );
					}
					/*
					* end send email
					*/

				}

				if ( $countSend >= $chunkSend ) {
					$sendme = false;
				}
				usleep( 200000 );
			}

			if ( $idx >= $total_sites ) {
				// check to finished or re-send failed for sites.
				self::update_completed_websites( $report, $completedSites, $all_siteids );
			}
		} else {
			$lastsend = time();
			$values   = array(
				'lastsend' => $lastsend, // Displays last send time only.
			);
			MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, $values );
			do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: scheduled report :: total sites :: 0', MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
			MainWP_Pro_Reports_DB::get_instance()->update_completed_report( $report->id ); // to fix reports with no sites.
		}

	}

	// checking and notice reports ready to send after a day
	public static function cron_notice_ready_reports() {

		@ignore_user_abort( true );
		@set_time_limit( 0 );
		$mem = '512M';
		@ini_set( 'memory_limit', $mem );
		@ini_set( 'max_execution_time', 0 );

		$send_local_time  = apply_filters( 'mainwp_pro_reports_send_local_time', false );
		$timestamp_offset = 0;
		if ( $send_local_time ) {
			$gmtOffset        = get_option( 'gmt_offset' );
			$timestamp_offset = $gmtOffset * HOUR_IN_SECONDS;
		}

		$reports = MainWP_Pro_Reports_DB::get_instance()->get_scheduled_reports_ready_to_notice( 3, $timestamp_offset );

		do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: Reports ready to send after a day :: Found ' . count( $reports ), MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );

		if ( empty( $reports ) ) {
			return;
		}

		foreach ( $reports as $report ) {
			self::do_send_ready_notice( $report );
			MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, array( 'noticed' => 1 ) );
		}
	}

	public static function do_send_ready_notice( $report ) {

		if ( empty( $report ) ) {
			return false;
		}

		if ( $report->noticed ) {
			return true;
		}

		$to_email = @apply_filters( 'mainwp_getnotificationemail', false );

		if ( empty( $to_email ) ) {
			return false;
		}

		do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: Sending ready notice : ' . $to_email, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );

		$link         = admin_url( 'admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=preview&id=' + $report->id );
		$send_subject = 'MainWP Pro Reports notification';
		$content      = sprintf( __( 'Tomorrow, reports will be sent to clients, click %shere% to preview the report for the past period.', 'mainwp-reports-extension' ), '<a href="' . $link . '">', '</a>' );
		$header       = array( 'content-type: text/html' );

		$format_content = '<table border="0" cellpadding="20" cellspacing="0" width="100%">
                   			<tr>
                       		<td valign="top" style="border-collapse: collapse;">
                          	<div style="color: #505050;font-family: Arial;font-size: 14px;line-height: 150%;text-align: left;"> Hi MainWP user, <br><br>
                           		<br>' . $content . '<br>
                           	</div>
                       		</td>
                   			</tr>
               				</table>';

		if ( wp_mail( $to_email, stripslashes( $send_subject ), $format_content, $header ) ) {
			do_action( 'mainwp_log_action', 'Pro Reports :: SUCCESS :: CRON :: Send ready notice success!', MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
			return true;
		} else {
			do_action( 'mainwp_log_action', 'Pro Reports :: FAILED :: CRON :: Send ready notice failed!', MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
		}
		return false;
	}

	public static function cal_days_in_month( $month, $year ) {
		if ( function_exists( 'cal_days_in_month' ) ) {
			$max_d = cal_days_in_month( CAL_GREGORIAN, $month, $year );
		} else {
			$max_d = date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
		}
		return $max_d;
	}

	public static function calc_recurring_date( $schedule, $recurring_day ) {

		if ( empty( $schedule ) ) {
			return false;
		}

			$the_time  = time(); // UTC time;
			$date_from = $date_to = $date_send = 0;

		if ( 'daily' == $schedule ) {
				$date_from = strtotime( date( 'Y-m-d', $the_time ) . ' 00:00:00' );
				$date_to   = strtotime( date( 'Y-m-d', $the_time ) . ' 23:59:59' );
				$date_send = $date_to + 2;
		} elseif ( 'weekly' == $schedule ) {
				// for strtotime()
				$day_of_week = array(
					1 => 'monday',
					2 => 'tuesday',
					3 => 'wednesday',
					4 => 'thursday',
					5 => 'friday',
					6 => 'saturday',
					7 => 'sunday',
				);

				$date_from = strtotime( date( 'Y-m-d', $the_time ) . ' 00:00:00' );
				$date_to   = $date_from + 7 * 24 * 3600 - 1;

				$date_send = strtotime( 'next ' . $day_of_week[ $recurring_day ] ) + 1;  // day of next week
				if ( $date_send < $date_to ) { // to fix
					$date_send += 7 * 24 * 3600;
				}
		} elseif ( 'monthly' == $schedule ) {
			$first_date = date( 'Y-m-01', $the_time ); // first day of the month
			$last_date  = date( 'Y-m-t', $the_time ); // Date t parameter return days number in current month.

			$date_from = strtotime( $first_date . ' 00:00:00' );
			$date_to   = strtotime( $last_date . ' 23:59:59' );

			$cal_month = date( 'm', $the_time ) + 1;
			$cal_year  = date( 'Y', $the_time );

			if ( $cal_month > 12 ) {
					$cal_month = $cal_month - 12;
					$cal_year += 1;
			}

				  $max_d = self::cal_days_in_month( $cal_month, $cal_year );
			if ( $recurring_day > $max_d ) {
				$recurring_day = $max_d;
			}
				  $date_send = mktime( 0, 0, 1, $cal_month, $recurring_day, $cal_year );
		}

			return array(
				'date_from' => $date_from,
				'date_to'   => $date_to,
				'date_send' => $date_send,
			);
	}

	public function shortcuts_widget( $website ) {
		if ( ! empty( $website ) ) {
			$found       = MainWP_Pro_Reports_DB::get_instance()->checked_if_site_have_report( $website->id );
			$reports_lnk = '';
			if ( $found ) {
				$reports_lnk = '<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&site=' . $website->id . '">' . __( 'Reports', 'mainwp-reports-extension' ) . '</a> | ';
			}
			?>
			<div class="mainwp-row">
				<div style="display: inline-block; width: 100px;"><?php _e( 'Client Reports:', 'mainwp-reports-extension' ); ?></div>
				<?php echo $reports_lnk; ?>
				<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=newreport&selected_site=<?php echo $website->id; ?>"><?php _e( 'New Report', 'mainwp-reports-extension' ); ?></a>
			</div>
			<?php
		}
	}

	public function managesites_column_url( $actions, $site_id ) {
		if ( ! empty( $site_id ) ) {
			$reports = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'site_id', $site_id );
			$link    = '';
			if ( is_array( $reports ) && count( $reports ) > 0 ) {
				$link = '<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&site=' . $site_id . '">' . __( 'Reports', 'mainwp-reports-extension' ) . '</a> ' .
						'( <a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=newreport&selected_site=' . $site_id . '">' . __( 'New', 'mainwp-reports-extension' ) . '</a> )';
			} else {
				$link = '<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=newreport&selected_site=' . $site_id . '">' . __( 'New Report', 'mainwp-reports-extension' ) . '</a>';
			}
			$actions['client_reports'] = $link;
		}
		return $actions;
	}

	/**
	 * Method hook_generate_content().
	 *
	 * @param int      $templ_content The content with tokens.
	 * @param int      $site_id The id of the site
	 * @param string|0 $from_date String of from date, date format 'Y-m-d H:i:s'
	 * @param string|0 $to_date String of to date, date format 'Y-m-d H:i:s'
	 * @param string   $type String of type.
	 *
	 * @return html content of generated content. False when something goes wrong.
	 */
	public static function hook_generate_content( $templ_content, $site_id, $from_date = 0, $to_date = 0, $type = '' ) {

		if ( empty( $site_id ) || empty( $from_date ) || empty( $to_date ) ) {
			return $templ_content;
		}

		global $mainWPProReportsExtensionActivator;

		$website = apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $site_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		} else {
			return $templ_content;
		}

		$filtered_reports = self::filter_report_website( $templ_content, false, $website, $from_date, $to_date, $type );

		// to avoid error.
		if ( is_array( $filtered_reports ) && isset( $filtered_reports['error'] ) ) {
			return $templ_content;
		}

		if ( $type == 'raw' ) {
			return $filtered_reports;
		} else {
			$content = self::gen_report_content( $filtered_reports );
		}

		return $content;
	}

	/**
	 * @param int      $report_id The id of the report
	 * @param int      $site_id The id of the site
	 * @param string|0 $from_date String of from date, date format 'Y-m-d H:i:s'
	 * @param string|0 $to_date String of to date, date format 'Y-m-d H:i:s'
	 *
	 * @return html content of generated report. False when something goes wrong.
	 */
	public static function hook_generate_report( $report_id, $site_id, $from_date = 0, $to_date = 0, $type = '' ) {
		if ( empty( $report_id ) || empty( $site_id ) ) {
			return false;
		}

		$report = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );

		if ( empty( $report ) ) {
			return false;
		}

		global $mainWPProReportsExtensionActivator;
		$website = apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $site_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( empty( $website ) ) {
			return false;
		}

		if ( ! empty( $from_date ) && ! empty( $to_date ) ) {
			$from_date = strtotime( $from_date );
			$to_date   = strtotime( $to_date );
		} else {
			$from_date = $to_date = 0;
		}

		$templ_content = MainWP_Pro_Reports_Template::get_instance()->get_template_file_content( $report, $website );

		$filtered_reports = self::filter_report_website( $templ_content, $report, $website, $from_date, $to_date, $type );
		if ( $type == 'raw' ) {
			return $filtered_reports;
		} else {
			$content = self::gen_report_content( $filtered_reports );
		}
		return $content;
	}

	public static function handle_report_saving() {

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'mainwp-pro-reports-nonce' ) ) {
				$messages             = $errors = array();
				$report               = array();
				$current_attach_files = '';

			// $current_attach_logo = $current_header_image = ''

				$recalculate_recurring_date = false;
				$current_recurring_schedule = '';
				$current_recurring_day      = '';

			if ( isset( $_REQUEST['id'] ) && ! empty( $_REQUEST['id'] ) ) {
				$report               = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $_REQUEST['id'], null, null, ARRAY_A );
				$current_attach_files = $report['attach_files'];

				// $current_attach_logo = $report['attach_logo'];
				// $current_header_image = $report['header_image'];

				if ( ! $report['scheduled'] ) {
					$recalculate_recurring_date = true;
				} else {
					$current_recurring_schedule = $report['recurring_schedule'];
					$current_recurring_day      = $report['recurring_day'];
				}
			} else {
				$recalculate_recurring_date = true;
			}

			if ( isset( $_POST['pro-report-title'] ) && ( $title = trim( $_POST['pro-report-title'] ) ) != '' ) {
				$report['title'] = $title;
			}

			if ( isset( $_POST['pro-report-template'] ) ) {
				$report['template'] = $_POST['pro-report-template'];
			}

			if ( isset( $_POST['pro-report-email-template'] ) ) {
				$report['template_email'] = $_POST['pro-report-email-template'];
			}

				$scheduled_report = false;
			if ( isset( $_POST['pro-report-type'] ) && ! empty( $_POST['pro-report-type'] ) ) {
				$report['scheduled'] = 1;
				$scheduled_report    = true;
			} else {
				$report['scheduled'] = 0;
			}

			if ( ! $scheduled_report ) {
					$start_time = $end_time = 0;
				if ( isset( $_POST['pro-report-from-date'] ) && ( $start_date = trim( $_POST['pro-report-from-date'] ) ) != '' ) {
					$start_time = strtotime( $start_date . ' ' . date( 'H:i:s' ) );
				}
				if ( isset( $_POST['pro-report-to-date'] ) && ( $end_date = trim( $_POST['pro-report-to-date'] ) ) != '' ) {
					$end_time = strtotime( $end_date . ' ' . date( 'H:i:s' ) );
				}
				if ( $start_time > $end_time ) {
					$tmp        = $start_time;
					$start_time = $end_time;
					$end_time   = $tmp;
				}

				if ( $start_time > 0 && $end_time > 0 ) {
					$start_time = mktime( 0, 0, 0, date( 'm', $start_time ), date( 'd', $start_time ), date( 'Y', $start_time ) );
					$end_time   = mktime( 23, 59, 59, date( 'm', $end_time ), date( 'd', $end_time ), date( 'Y', $end_time ) );
				}

					$report['date_from'] = $start_time;
					$report['date_to']   = $end_time;
			}

			// if ( isset( $_POST['pro-report-client-id'] ) ) {
			// $report['client_id'] = intval( $_POST['pro-report-client-id'] );
			// }

			if ( isset( $_POST['pro-report-from-name'] ) ) {
				$report['fname'] = trim( $_POST['pro-report-from-name'] );
			}

			$from_email = '';
			if ( ! empty( $_POST['pro-report-from-email'] ) ) {
				$from_email = trim( $_POST['pro-report-from-email'] );
				if ( ! empty( $from_email ) ) {
					if ( preg_match( '/\[[^\]]+\]/is', $from_email, $matches ) ) {
						$from_email = $matches[0]; // first token
					} elseif ( ! preg_match( '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/is', $from_email ) ) {
						$from_email = ''; // incorrect email
					} else {
						// ok, valid email
					}
				}
			}

			if ( $from_email == '' ) {
				$errors[] = __( 'Incorrect Email Address in the Send From filed.', 'mainwp-pro-reports-extension' );
			}

			$report['femail'] = $from_email;

			if ( isset( $_POST['pro-report-to-client'] ) ) {
				$report['send_to_name'] = trim( $_POST['pro-report-to-client'] );
			}

			$reply_email = '';
			if ( ! empty( $_POST['pro-report-reply-to'] ) ) {
				$reply_email = trim( $_POST['pro-report-reply-to'] );
				if ( ! empty( $reply_email ) ) {
					if ( preg_match( '/\[[^\]]+\]/is', $reply_email, $matches ) ) {
						$reply_email = $matches[0]; // first token
					} elseif ( ! preg_match( '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/is', $reply_email ) ) {
						$reply_email = ''; // incorrect email
					} else {
						// ok, valid email
					}
				}
			}

			$report['reply_to'] = $reply_email;

			if ( isset( $_POST['pro-report-reply-to-name'] ) ) {
				$report['reply_to_name'] = trim( $_POST['pro-report-reply-to-name'] );
			}

			$report['showhide_sections'] = json_encode( $_POST['pro-report-showhide-sections'] );

				$to_email     = '';
				$valid_emails = array();
			if ( ! empty( $_POST['pro-report-to-email'] ) ) {
				$to_emails = explode( ',', trim( $_POST['pro-report-to-email'] ) );
				if ( is_array( $to_emails ) ) {
					foreach ( $to_emails as $_email ) {
						$_email = trim( $_email );
						if ( ! preg_match( '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/is', $_email ) && ! preg_match( '/^\[.+\]/is', $_email ) ) {
							// $errors[] = __( 'Incorrect Email Address in the Send To field.', 'mainwp-pro-reports-extension' );
							// avoid incorrect email
						} else {
							$valid_emails[] = $_email;
						}
					}
				}
			}

			if ( count( $valid_emails ) > 0 ) {
				$to_email = implode( ',', $valid_emails );
			} else {
				$to_email = '';
				$errors[] = __( 'Incorrect Email Address in the Send To field.', 'mainwp-pro-reports-extension' );
			}

				$report['send_to_email'] = $to_email;

				$bcc_email = '';

			if ( ! empty( $_POST['pro-report-bcc-email'] ) ) {
				$bcc_email = trim( $_POST['pro-report-bcc-email'] );
				if ( ! empty( $from_email ) ) {
					if ( preg_match( '/\[[^\]]+\]/is', $bcc_email, $matches ) ) {
						$bcc_email = $matches[0]; // first token
					} elseif ( ! preg_match( '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/is', $bcc_email ) ) {
						$bcc_email = ''; // incorrect email
					} else {
						// ok, valid email
					}
				}
			}

				$report['bcc_email'] = $bcc_email;

			if ( isset( $_POST['pro-report-email-subject'] ) ) {
				$report['subject'] = trim( $_POST['pro-report-email-subject'] );
			}

			if ( isset( $_POST['pro-report-email-message'] ) ) {
				$report['message'] = trim( $_POST['pro-report-email-message'] );
			}

				$report['recurring_schedule'] = '';

			if ( isset( $_POST['pro-report-schedule'] ) ) {
				$report['recurring_schedule'] = trim( $_POST['pro-report-schedule'] );
			}

				$report['recurring_day'] = '';

			if ( $scheduled_report ) {
				if ( $report['recurring_schedule'] == 'monthly' ) {
					$report['recurring_day'] = intval( $_POST['pro-report-schedule-month-day'] );
				} elseif ( $report['recurring_schedule'] == 'weekly' ) {
					$report['recurring_day'] = intval( $_POST['pro-report-schedule-day'] );
				} elseif ( $report['recurring_schedule'] == 'daily' ) {
					// nothing, send everyday
				} else {
					$report['recurring_schedule'] = ''; // will not schedule send
				}

				if ( ( $current_recurring_schedule != $report['recurring_schedule'] ) || ( $current_recurring_day != $report['recurring_day'] ) ) {
					$recalculate_recurring_date = true;
				}

					// only update date when create new report
					// or change from one-time to scheduled report
					// or schedule settings changed
				if ( $recalculate_recurring_date ) {
					$cal_recurring = self::calc_recurring_date( $report['recurring_schedule'], $report['recurring_day'] );   // saving handle, do not need to pass offset data time
					if ( is_array( $cal_recurring ) ) {
						$report['date_from']          = $cal_recurring['date_from'];
						$report['date_to']            = $cal_recurring['date_to'];
						$report['date_from_nextsend'] = 0; // need to be 0, will recalculate when schedule send
						$report['date_to_nextsend']   = 0; // need to be 0, will recalculate when schedule send
						$report['schedule_nextsend']  = $cal_recurring['date_send'];
						$report['completed']          = $cal_recurring['date_send']; // to fix continue sending
					}
				}
			}

			if ( isset( $_POST['pro-report-schedule-send-email'] ) ) {
				$report['schedule_send_email'] = trim( $_POST['pro-report-schedule-send-email'] );
			}

			$report['schedule_bcc_me'] = isset( $_POST['pro-report-schedule-send-email-bcc-me'] ) ? 1 : 0;
			$creport_dir               = MainWP_Pro_Reports_Template::get_instance()->get_mainwp_sub_dir( 'report-attached' );

			if ( isset( $_POST['pro-report-heading'] ) ) {
				$report['heading'] = trim( $_POST['pro-report-heading'] );
			}

			if ( isset( $_POST['pro-report-intro'] ) ) {
				$report['intro'] = trim( $_POST['pro-report-intro'] );
			}

			if ( isset( $_POST['pro-report-outro'] ) ) {
				$report['outro'] = trim( $_POST['pro-report-outro'] );
			}

			if ( isset( $_POST['pro-report-text-color'] ) ) {
				$report['text_color'] = trim( $_POST['pro-report-text-color'] );
			}

			if ( isset( $_POST['pro-report-accent-color'] ) ) {
				$report['accent_color'] = trim( $_POST['pro-report-accent-color'] );
			}

			if ( isset( $_POST['pro-report-background-color'] ) ) {
				$report['background_color'] = trim( $_POST['pro-report-background-color'] );
			}

			$return                    = array();
			$report['logo_id']         = intval( $_POST['pro-report-logo'] );
			$report['header_image_id'] = intval( $_POST['pro-report-header-image'] );

			// attach files.
			$attach_files = 'NOTCHANGE';

			if ( isset( $_POST['pro-report-email-attachement-remove-files'] ) && '1' == $_POST['pro-report-email-attachement-remove-files'] ) {
				$attach_files = '';
				if ( ! empty( $current_attach_files ) ) {
					self::delete_attach_files( $current_attach_files, $creport_dir );
				}
			}

			if ( isset( $_FILES['pro-report-email-attachements'] ) && ! empty( $_FILES['pro-report-email-attachements']['name'][0] ) ) {
				if ( ! empty( $current_attach_files ) ) {
					self::delete_attach_files( $current_attach_files, $creport_dir );
				}

				$output = self::handle_upload_files( $_FILES['pro-report-email-attachements'], $creport_dir );
				// print_r($output);
				if ( isset( $output['error'] ) ) {
					$return['error'] = $output['error'];
				}

				if ( is_array( $output ) && isset( $output['filenames'] ) && ! empty( $output['filenames'] ) ) {
					$attach_files = implode( ', ', $output['filenames'] );
				}
			}

			if ( 'NOTCHANGE' !== $attach_files ) {
				$report['attach_files'] = $attach_files;
			}

				// end /////

				// $selected_site = 0;
				$selected_sites = $selected_groups = array();

			if ( isset( $_POST['select_by'] ) ) {
				if ( isset( $_POST['selected_sites'] ) && is_array( $_POST['selected_sites'] ) ) {
					foreach ( $_POST['selected_sites'] as $selected ) {
						$selected_sites[] = intval( $selected );
					}
				}

				if ( isset( $_POST['selected_groups'] ) && is_array( $_POST['selected_groups'] ) ) {
					foreach ( $_POST['selected_groups'] as $selected ) {
						$selected_groups[] = intval( $selected );
					}
				}
			}

			$report['type'] = 1;

			$report['sites']  = ! empty( $selected_sites ) ? base64_encode( serialize( $selected_sites ) ) : '';
			$report['groups'] = ! empty( $selected_groups ) ? base64_encode( serialize( $selected_groups ) ) : '';

			if ( 'schedule' === $_POST['pro-report-action'] ) {
				$report['scheduled'] = 1;
			}

			if ( 'save' === $_POST['pro-report-action'] ||
						'send' === $_POST['pro-report-action'] ||
						'save_pdf' === $_POST['pro-report-action'] ||
						'schedule' === $_POST['pro-report-action'] ||
						'preview' === (string) $_POST['pro-report-action'] ||
						'send_test' === (string) $_POST['pro-report-action']
				) {

				if ( $result = MainWP_Pro_Reports_DB::get_instance()->update_report( $report ) ) {
					$return['id'] = $result->id;
					$messages[]   = __( 'Report has been saved.', 'mainwp-pro-reports-extension' );
					MainWP_Pro_Reports_DB::get_instance()->delete_generated_report_content( $result->id ); // to clear reports generated content
				} else {
					$messages[] = __( 'Report has been saved without changes.', 'mainwp-pro-reports-extension' );
				}
					$return['saved'] = true;
			}

			if ( ! isset( $return['id'] ) && isset( $report['id'] ) ) {
				$return['id'] = $report['id'];
			}

			if ( count( $errors ) > 0 ) {
				$return['error'] = $errors;
			}

			if ( count( $messages ) > 0 ) {
				$return['message'] = $messages;
			}
				return $return;
		}

		return null;
	}

	static function delete_attach_files( $files, $dir ) {
		$files = explode( ',', $files );
		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				$file      = trim( $file );
				$file_path = $dir . $file;
				if ( file_exists( $file_path ) ) {
					@unlink( $file_path );
				}
			}
		}
	}

	public static function handle_upload_files( $file_input, $dest_dir ) {
		$output      = array();
		$attachFiles = array();

		$allowed_files = array( 'jpeg', 'jpg', 'gif', 'png', 'rar', 'zip', 'pdf' );

		$tmp_files = $file_input['tmp_name'];

		if ( is_array( $tmp_files ) ) {
			foreach ( $tmp_files as $i => $tmp_file ) {
				if ( ( UPLOAD_ERR_OK == $file_input['error'][ $i ] ) && is_uploaded_file( $tmp_file ) ) {
					$file_size = $file_input['size'][ $i ];
					$file_name = $file_input['name'][ $i ];
					$file_ext  = strtolower( end( explode( '.', $file_name ) ) );
					if ( ( $file_size > 5 * 1024 * 1024 ) ) {
						$output['error'][] = $file_name . ' - ' . __( 'File size too big' );
					} elseif ( ! in_array( $file_ext, $allowed_files ) ) {
						$output['error'][] = $file_name . ' - ' . __( 'File type are not allowed' );
					} else {
						$dest_file = $dest_dir . $file_name;
						$dest_file = dirname( $dest_file ) . '/' . wp_unique_filename( dirname( $dest_file ), basename( $dest_file ) );

						if ( move_uploaded_file( $tmp_file, $dest_file ) ) {
							$attachFiles[] = basename( $dest_file );
						} else {
							$output['error'][] = $file_name . ' - ' . __( 'Can not copy file' );
						}
					}
				}
			}
		}

		$output['filenames'] = $attachFiles;
		return $output;
	}

	public static function handle_upload_image( $file_input, $dest_dir, $max_height, $max_width = null ) {
		$output         = array();
		$processed_file = '';

		if ( UPLOAD_ERR_OK == $file_input['error'] ) {
			$tmp_file = $file_input['tmp_name'];

			if ( is_uploaded_file( $tmp_file ) ) {
				$file_size      = $file_input['size'];
				$file_type      = $file_input['type'];
				$file_name      = $file_input['name'];
				$file_extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

				if ( ( $file_size > 500 * 1025 ) ) { // 500KB
					$output['error'][] = 'File size is too large.';
				} elseif (
						( 'image/jpeg' != $file_type ) &&
						( 'image/jpg' != $file_type ) &&
						( 'image/gif' != $file_type ) &&
						( 'image/png' != $file_type )
				) {
					$output['error'][] = 'File Type is not allowed.';
				} elseif (
						( 'jpeg' != $file_extension ) &&
						( 'jpg' != $file_extension ) &&
						( 'gif' != $file_extension ) &&
						( 'png' != $file_extension )
				) {
					$output['error'][] = 'File Extension is not allowed.';
				} else {
					$dest_file = $dest_dir . $file_name;
					$dest_file = dirname( $dest_file ) . '/' . wp_unique_filename( dirname( $dest_file ), basename( $dest_file ) );

					if ( move_uploaded_file( $tmp_file, $dest_file ) ) {
						if ( file_exists( $dest_file ) ) {
							list( $width, $height, $type, $attr ) = getimagesize( $dest_file );
						}

						$resize = false;
						if ( $height > $max_height ) {
							$dst_height = $max_height;
							$dst_width  = $width * $max_height / $height;
							$resize     = true;
						}

						if ( $resize ) {
							$src          = $dest_file;
							$cropped_file = wp_crop_image( $src, 0, 0, $width, $height, $dst_width, $dst_height, false );
							if ( ! $cropped_file || is_wp_error( $cropped_file ) ) {
								$output['error'][] = __( 'Can not resize the image.' );
							} else {
								@unlink( $dest_file );
								$processed_file = basename( $cropped_file );
							}
						} else {
							$processed_file = basename( $dest_file );
						}
						$output['filename'] = $processed_file;
					} else {
						$output['error'][] = 'Can not copy the file.';
					}
				}
			}
		}

		return $output;
	}

	public static function has_tokens( $value ) {
		if ( empty( $value ) ) {
			return false;
		}
		return preg_match( '/\[[^\]]+\]/is', $value );
	}


	public static function find_and_replace_email_tokens( $email_token, $site_id ) {

		if ( ! self::has_tokens( $email_token ) ) {
			return $email_token;
		}

		$search_token = $replace_value = array();
		// find tokens in send to email to get token value
		if ( preg_match_all( '/\[[^\]]+\]/is', $email_token, $matches ) ) {
			$email_tokens = $matches[0];
			foreach ( $email_tokens as $tk ) {
				$token_val = '';
				$token     = MainWP_Pro_Reports_DB::get_instance()->get_tokens_by( 'token_name', $tk, $site_id );
				if ( is_object( $token ) && property_exists( $token, 'site_token' ) ) {
					$token_val = $token->site_token->token_value;
				}
				if ( $token_val != '' ) {
					$search_token[]  = $tk; // [token_name]
					$replace_value[] = $token_val; // the token value may be multi emails, separated by comma
				}
			}
		}

		if ( ! empty( $search_token ) ) {
			$email_token = str_replace( $search_token, $replace_value, $email_token );
		}

		$email_token = preg_replace( '/\[[^\]]+\]/is', '', $email_token ); // to remove other tokens

		$items  = explode( ',', $email_token );
		$items  = array_filter(
			$items,
			function( $value ) {
				$value = trim( $value );
				return ! empty( $value );
			}
		); // remove empty values
		$emails = implode( ',', $items );

		return $emails;
	}


	public static function prepare_content_report_email( $report, $send_test = false, $site = null, $generate_content = false ) {

		if ( ! is_object( $report ) ) {
			return false;
		}

		if ( empty( $site ) ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$site_id = $site['id'];

		// generate content if needed
		if ( $generate_content ) {
			  self::update_pro_report_site( $report, $site );
		}
		$send_content = '';
		$result       = MainWP_Pro_Reports_DB::get_instance()->get_pro_report_generated_content( $report->id, $site_id );

		if ( $result ) {
			$send_content = json_decode( $result->report_content );
		}

		// report content empty so return
		if ( empty( $send_content ) ) {
			do_action( 'mainwp_log_action', 'Pro Reports :: ERROR :: CRON :: Send report error - content empty', MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
			return false;
		} else {
			do_action( 'mainwp_log_action', 'Pro Reports :: SUCCESS :: CRON :: generated report content', MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
		}

		$sendto_email = $subject = $bcc_email_me = '';

		$noti_email = @apply_filters( 'mainwp_getnotificationemail', false );

		$send_to_me_review = false;
		if ( $send_test ) {
			if ( ! empty( $noti_email ) ) {
				$sendto_email = $noti_email;
				$subject      = 'Send Test Email';
			}
		} elseif ( ! empty( $report->scheduled ) ) {
			if ( $report->schedule_send_email == 'email_auto' ) {
				$sendto_email = ! empty( $report->send_to_email ) ? $report->send_to_email : '';
				if ( $report->schedule_bcc_me ) {
					$bcc_email_me = $noti_email;
				}
			} elseif ( $report->schedule_send_email == 'email_review' && ! empty( $noti_email ) ) {
				$sendto_email      = $noti_email;
				$subject           = 'Review report';
				$send_to_me_review = true;
			}
		} else {
			$sendto_email = $report->send_to_email;
		}

		// send to email empty so return
		if ( empty( $sendto_email ) ) {
			do_action( 'mainwp_log_action', 'Pro Reports :: ERROR :: CRON :: Send report error - email empty', MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
			return false;
		}

		$email_subject = '';
		if ( ! empty( $subject ) ) {
			$email_subject = $subject;
		}

		$email_subject = isset( $report->subject ) && ! empty( $report->subject ) ? $email_subject . ' - ' . $report->subject : $email_subject . ' - ' . 'Website Report';
		$email_subject = ltrim( $email_subject, ' - ' );

		$email_message = $report->message;
		$from_name     = $report->fname;
		$reply_to_name = $report->reply_to_name;

		// $email_message = nl2br($email_message); // to fix
		$templ_email = MainWP_Pro_Reports_Template::get_instance()->get_template_email_file_content( $report, $email_message );

		// find and replace tokens values
		if ( self::has_tokens( $email_subject )
			|| self::has_tokens( $from_name )
			|| self::has_tokens( $templ_email )
			|| self::has_tokens( $reply_to_name )
		) {

			$tokens_values = self::get_tokens_of_site( $report, $site_id );

			/**
			* Filters for custom values of site tokens before generate the report content
			*
			* @since 4.0.1
			*
			* @param array $replace_tokens_values The array of tokens
			* @param object $report        The report.
			* @param string $website       The website.
			*/
			$tokens_values = apply_filters( 'mainwp_pro_reports_custom_tokens', $tokens_values, $report, $site, $templ_email );

			if ( self::has_tokens( $email_subject ) ) {
				$email_subject = self::replace_site_tokens( $email_subject, $tokens_values );
			}

			if ( self::has_tokens( $from_name ) ) {
				$from_name = self::replace_site_tokens( $from_name, $tokens_values );
			}

			if ( self::has_tokens( $templ_email ) ) {
				$templ_email = self::replace_site_tokens( $templ_email, $tokens_values );
			}

			if ( self::has_tokens( $reply_to_name ) ) {
				$reply_to_name = self::replace_site_tokens( $reply_to_name, $tokens_values );
			}
		}

		// set header values
		$header = array( 'content-type: text/html' );

		$from_email = '';
		if ( ! empty( $report->femail ) ) {
			$from_email = self::find_and_replace_email_tokens( $report->femail, $site_id );
			$from_email = ' ' . '<' . $from_email . '>';
		}

		$header[] = 'From: ' . $from_name . $from_email;

		if ( ! empty( $report->bcc_email ) ) {
			$bcc_email = self::find_and_replace_email_tokens( $report->bcc_email, $site_id );
			$header[]  = 'Bcc: ' . $bcc_email;
		}

		if ( ! empty( $bcc_email_me ) ) {
			// do not replace tokens in bcc me email
			$header[] = 'Bcc: ' . $bcc_email_me;
		}

		if ( ! empty( $report->reply_to ) ) {
			$header[] = 'Reply-To: ' . $reply_to_name . '<' . $report->reply_to . '>';
		}

		$to_emails = $sendto_email;

		if ( ! $send_to_me_review ) {
			$to_emails = self::find_and_replace_email_tokens( $to_emails, $site_id );
		}

		$files       = $report->attach_files;
		$attachments = array();

		if ( ! empty( $files ) ) {
			$creport_dir = MainWP_Pro_Reports_Template::get_instance()->get_mainwp_sub_dir( 'report-attached' );
			$files       = explode( ',', $files );
			foreach ( $files as $file ) {
				$file          = trim( $file );
				$attachments[] = $creport_dir . $file;
			}
		}

		if ( ! empty( $to_emails ) ) {

			do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: Sending report to : ' . $to_emails . ' :: From: ' . $from_name, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
			$data = array(
				'header'      => $header,
				'to_email'    => $to_emails,
				'subject'     => $email_subject,
				'content'     => $send_content,
				'attachments' => $attachments,
			);

			$data = apply_filters( 'mainwp_pro_reports_send_mail_data', $data );

			$html_to_pdf = $data['content'];

			$data['attachments'] = apply_filters( 'mainwp_pro_reports_email_attachments', $data['attachments'], $html_to_pdf, $report, $site_id );

			$data['templ_email'] = $templ_email;
			return $data;
		}

		return false;
	}

	 /**
	  * Get other tokens value.
	  *
	  * @param array $tokens_values Section matches.
	  */
	public static function get_other_tokens_value( &$tokens_values, $site_id ) {
		if ( $site_id ) {
			$option = array(
				'plugins' => true,
				'themes'  => true,
			);
			global $mainWPProReportsExtensionActivator;
			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), array( $site_id ), array(), $option );
			if ( $dbwebsites ) {
				$website = current( $dbwebsites );

				if ( property_exists( $website, 'plugins' ) ) {
					$installed_plugins = $website->plugins;
					$installed_plugins = json_decode( $installed_plugins, true );
					$str               = '';
					if ( ! empty( $installed_plugins ) ) {
						foreach ( $installed_plugins as $item ) {
							$str .= '<p style="margin: 2px 0px 2px 0px">' . $item['name'] . '-' . $item['version'] . '</p>';
						}
					}
					$tokens_values['[installed.plugins]'] = $str;
				}

				if ( property_exists( $website, 'themes' ) ) {
					$installed_themes = $website->themes;
					$installed_themes = json_decode( $installed_themes, true );
					$str              = '';
					if ( ! empty( $installed_themes ) ) {
						foreach ( $installed_themes as $item ) {
							$str .= '<p style="margin: 2px 0px 2px 0px">' . $item['name'] . ' - ' . $item['version'] . '</p>';
						}
					}
					$tokens_values['[installed.themes]'] = $str;
				}
			}
		}
	}


	/**
	 * Hook get tokens value.
	 */
	public static function hook_get_tokens_value( $input, $website, $tokens, $date_from = false, $date_to = false ) {
		if ( empty( $date_to ) ) {
			$date_to = time();
		}
		if ( empty( $date_from ) ) {
			$date_from = strtotime( '-1 month', $date_to );
		}

		if ( ! is_array( $tokens ) ) {
			$tokens = array();
		}

		$other_tokens = array(
			'body' => $tokens,
		);

		if ( is_numeric( $website ) ) {
			$website = array( 'id' => $website );
		}

		$information = self::fetch_remote_data( $website, array(), $other_tokens, $date_from, $date_to );

		if ( is_array( $information ) && isset( $information['other_tokens_data'] ) && isset( $information['other_tokens_data']['body'] ) ) {
			return $information['other_tokens_data']['body'];
		}

		return $information;
	}

	/**
	 * Method hook_get_site_tokens().
	 *
	 * @param int $false input value.
	 * @param int $site_id The id of site
	 *
	 * @return array Site's tokens.
	 */
	public static function hook_get_site_tokens( $false, $site_id ) {
		return self::get_tokens_of_site( false, $site_id );
	}

	/**
	 * Method get_tokens_of_site().
	 *
	 * @param object $report The report.
	 * @param int    $site_id The id of site
	 *
	 * @return array Site's tokens.
	 */
	public static function get_tokens_of_site( $report, $site_id ) {
		// get tokens of the site
		$sites_token = MainWP_Pro_Reports_DB::get_instance()->get_site_tokens( $site_id, 'token_name' );
		if ( ! is_array( $sites_token ) ) {
			$sites_token = array();
		}
		$now           = time();
		$tokens_values = array();

		if ( $report && is_object( $report ) ) {
			$tokens_values['[report.daterange]'] = MainWP_Pro_Reports_Utility::format_timestamp( $report->date_from ) . ' - ' . MainWP_Pro_Reports_Utility::format_timestamp( $report->date_to );
			$tokens_values['[report.send.date]'] = MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $now ) );
		}

		foreach ( $sites_token as $token_name => $token ) {
			$tokens_values[ '[' . $token_name . ']' ] = $token->token_value;
		}
		return $tokens_values;
	}


	/**
	 * Method hook_replace_token_values().
	 *
	 * @param int        $string input value.
	 * @param int object $report report.
	 * @param int        $site_id The id of site
	 *
	 * @return array Site's tokens.
	 */
	public static function hook_replace_token_values( $string, $report = false, $site_id = false ) {
		if ( self::has_tokens( $string ) ) {
			if ( $site_id && $report ) {
				$tokens_values = self::get_tokens_of_site( $report, $site_id );
				if ( $tokens_values ) {
					$string = self::replace_site_tokens( $string, $tokens_values );
				}
			}
		}
		return $string;
	}

	/**
	 * Method replace_site_tokens().
	 *
	 * @param int       $string input value.
	 * @param int array $replace_tokens array of tokens.
	 *
	 * @return array Site's array of tokens.
	 */
	public static function replace_site_tokens( $string, $replace_tokens ) {
		$tokens = array_keys( $replace_tokens );
		$values = array_values( $replace_tokens );
		return str_replace( $tokens, $values, $string );
	}

	function send_onetime_report_email( $data, $report, $send_test = false, $website = null ) {

		$email_subject = stripslashes( $data['subject'] );
		$templ_email   = $data['templ_email'];

		$site_id = $website['id'];

		$success = false;

		if ( wp_mail( $data['to_email'], $email_subject, $templ_email, $data['header'], $data['attachments'] ) ) {
			if ( ! $send_test ) {
				$lasttime = time(); // UTC time.
				$values   = array(
					'lastsend' => $lasttime,  // to display last send time only
				);
				MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, $values );
				MainWP_Pro_Reports_DB::get_instance()->updateWebsiteOption( $site_id, 'creport_last_report', $lasttime );
			}
				do_action( 'mainwp_log_action', 'Pro Reports :: SUCCESS :: Send report success :: Subject :: ' . $email_subject, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
				$success = true;
		} else {
				do_action( 'mainwp_log_action', 'Pro Reports :: FAILED :: Send report failed :: Subject :: ' . $email_subject, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
		}

		return $success;
	}

	/*
	* update the completed sites for scheduled report.
	*/
	public static function update_completed_websites( $report, $pCompletedSites, $all_siteids ) {
		$total_sites = count( $all_siteids );
		if ( $report->scheduled ) {
			MainWP_Pro_Reports_DB::get_instance()->update_reports_completed_websites( $report->id, $pCompletedSites );
			// Update completed sites.
			if ( $total_sites > 0 && count( $pCompletedSites ) >= $total_sites ) {
				// check to resend failed reports.
				$filteredCompletedSites = array_filter(
					$pCompletedSites,
					function( $val ) {
						return ( $val > 0 && $val != 5 );  // successed or generated failed.
					}
				);
				$retried                = $report->retry_counter;
				if ( count( $filteredCompletedSites ) >= $total_sites || ( $retried >= 3 ) ) {
					do_action( 'mainwp_log_action', 'Pro Reports :: INFOR :: CRON :: Schedule reports :: completed sites :: ' . count( $filteredCompletedSites ), MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
					// update to finish sending this report.
					MainWP_Pro_Reports_DB::get_instance()->update_completed_report( $report->id );
				} else {
					$retried = $retried + 1; // increase process counter to re-try to send.
					$values  = array(
						'retry_counter' => $retried,
					);
					MainWP_Pro_Reports_DB::get_instance()->update_reports_with_values( $report->id, $values );
					MainWP_Pro_Reports_DB::get_instance()->update_reports_completed_websites( $report->id, $filteredCompletedSites ); // update so countinue to re-send failed reports.
				}
			}
		}
	}

	// Render extenison page
	public static function render() {
		self::render_tabs();
	}

	// Render extenison page tabs
	public static function render_tabs() {

		$current_tab = '';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'dashboard' ) {
				$current_tab = 'dashboard';
			} elseif ( $_GET['tab'] == 'reports' ) {
				$current_tab = 'reports';
			} elseif ( $_GET['tab'] == 'tokens' ) {
				$current_tab = 'tokens';
			} elseif ( $_GET['tab'] == 'report' ) {
				$current_tab = 'report';
			} elseif ( $_GET['tab'] == 'edit_report' ) {
				$current_tab = 'edit_report';
			}
		} else {
			$current_tab = 'reports';
		}

		?>

		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-pro-reports-menu">
			<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=dashboard" class="item <?php echo ( $current_tab == 'dashboard' ) ? 'active' : ''; ?>"><i class="tasks icon"></i> <?php _e( 'Child Reports Dashboard', 'mainwp-pro-reports-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=reports" class="item <?php echo ( $current_tab == 'reports' || $current_tab == '' ) ? 'active' : ''; ?>"><i class="file alternate outline icon"></i> <?php _e( 'Reports', 'mainwp-pro-reports-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report" class="item <?php echo ( $current_tab == 'report' ) ? 'active' : ''; ?>"><i class="file alternate outline icon"></i> <?php _e( 'Create Report', 'mainwp-pro-reports-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=tokens" class="item <?php echo ( $current_tab == 'tokens' ) ? 'active' : ''; ?>"><i class="code icon"></i> <?php _e( 'Tokens', 'mainwp-pro-reports-extension' ); ?></a>
		</div>

		<?php

		self::get_tabs_content( $current_tab );

	}

	// Render extenison page tabs content
	public static function get_tabs_content( $current_tab = '' ) {

		global $mainWPProReportsExtensionActivator;

		// to reduce query db
		if ( $current_tab == 'dashboard' ) {
			$websites = apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), null );

			$sites_ids = array();
			if ( is_array( $websites ) ) {
				foreach ( $websites as $website ) {
					$sites_ids[] = $website['id'];
				}
			}

			$option = array(
				'plugin_upgrades' => true,
				'plugins'         => true,
			);

			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $sites_ids, array(), $option );

			$sites_with_creport = array();

			foreach ( $dbwebsites as $website ) {
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( 'mainwp-child-reports/mainwp-child-reports.php' == $plugin['slug'] ) {
								if ( $plugin['active'] ) {
									$sites_with_creport[] = $website->id;
									break;
								}
							}
						}
					}
				}
			}

			$lastReports      = MainWP_Pro_Reports_DB::get_instance()->getOptionOfWebsites( $sites_with_creport, 'creport_last_report' );
			$lastReportsSites = array();
			if ( is_array( $lastReports ) ) {
				foreach ( $lastReports as $last ) {
					$lastReportsSites[ $last->wpid ] = $last->value;
				}
			}

			$filter_groups = '';
			if ( isset( $_GET['group'] ) && ! empty( $_GET['group'] ) ) {
				$filter_groups = $_GET['group'];
			}

			$dbwebsites_stream = MainWP_Pro_Reports_Plugin::get_instance()->get_websites_stream( $dbwebsites, $filter_groups, $lastReportsSites );

			unset( $dbwebsites );
		}

		if ( $current_tab == 'dashboard' ) {
			?>
			<?php MainWP_Pro_Reports_Plugin::gen_actions_bar( $dbwebsites_stream ); ?>
			<div class="ui segment" id="mainwp-pro-reports-dashboard">
				<?php MainWP_Pro_Reports_Plugin::gen_dashboard_tab( $dbwebsites_stream ); ?>
			</div>
			<?php
		} elseif ( $current_tab == '' || $current_tab == 'reports' ) {
			?>
			<div id="mainwp-pro-reports-tab" class="ui segment">
				<?php self::get_pro_reports(); ?>
			</div>
			<?php
		} elseif ( $current_tab == 'report' ) {
			?>
			<div id="mainwp-pro-reports-report-tab" class="ui alt segment">
				<?php self::render_report(); ?>
			</div>
			<?php
		} elseif ( $current_tab == 'tokens' ) {
			?>
			<div id="mainwp-pro-reports-custom-tokens-tab" class="ui segment">
				<?php self::get_pro_reports_custom_tokens(); ?>
			</div>
			<?php
		}
	}

	// Display reports table
	public static function get_pro_reports() {

		global $mainWPProReportsExtensionActivator;
		$websites = apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), null );

		$reports = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'all' );

		if ( ! is_array( $reports ) ) {
			$reports = array();
		}

		$client_reports = array();
		foreach ( $reports as $report ) {
			$client_reports[] = $report;
		}
		?>
		<table id="mainwp-client-reports-reports-table" class="ui single line table">
			<thead>
				<tr>
					<th><?php _e( 'Report', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Client', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Send To', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Last Sent', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Date Range', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Schedule', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php self::get_pro_reports_table_body( $client_reports ); ?>
			</tbody>
			<tfoot>
				<tr>
					<th><?php _e( 'Report', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Client', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Send To', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Last Sent', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Date Range', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Schedule', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
		jQuery( '#mainwp-client-reports-reports-table' ).DataTable( {
			"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
			"stateSave": true,
			"stateDuration": 0,
			"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
			"language": { "emptyTable": "No reports created yet. Go to the Create Report tab to create reports." },
			"drawCallback": function( settings ) {
				jQuery( '#mainwp-client-reports-reports-table .ui.dropdown').dropdown();
			},
		} );
		</script>
		<?php
	}

	// Display report table body
	public static function get_pro_reports_table_body( $reports ) {

		$recurring_schedule = array(
			'daily'   => __( 'Daily', 'mainwp-pro-reports-extension' ),
			'weekly'  => __( 'Weekly', 'mainwp-pro-reports-extension' ),
			'monthly' => __( 'Monthly', 'mainwp-pro-reports-extension' ),
		);

		$_show_completed_siteids = apply_filters( 'mainwp_pro_reports_table_show_completed_site_ids', false );

		foreach ( $reports as $report ) {

			$is_scheduled = $report->scheduled ? true : false;

			$sche_column = __( 'Manual', 'mainwp-pro-reports-extension' );

			if ( ! empty( $report->recurring_schedule ) && $is_scheduled ) {
				$sche_column = $recurring_schedule[ $report->recurring_schedule ];
				if ( ! empty( $report->schedule_nextsend ) ) {
					$sche_column .= '<br><em>Next Run: ' . MainWP_Pro_Reports_Utility::format_timestamp( $report->schedule_nextsend ) . '</em>'; // display time in UTC.
				}
			}

			$sel_sites  = unserialize( base64_decode( $report->sites ) );
			$sel_groups = unserialize( base64_decode( $report->groups ) );

			if ( ! is_array( $sel_sites ) ) {
				$sel_sites = array();
			}

			if ( ! is_array( $sel_groups ) ) {
				$sel_groups = array();
			}

			$disable_act_buttons = true;
			if ( count( $sel_sites ) > 0 || count( $sel_groups ) > 0 ) {
				$disable_act_buttons = false;
			}

			?>

			<tr report-id="<?php echo $report->id; ?>">
				<td><a href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&id=<?php echo $report->id; ?>"><?php echo stripslashes( $report->title ); ?></a></td>
				<td><?php echo esc_html( stripslashes( $report->send_to_name ) ); ?></td>
				<td><?php echo esc_html( stripslashes( $report->send_to_email ) ); ?></td>
				<td>
				<?php
				echo ! empty( $report->lastsend ) ? MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $report->lastsend ) ) : ''; // display local time.

				if ( $is_scheduled && $_show_completed_siteids ) {
						$comp_ids = ! empty( $report->completed_sites ) ? @json_decode( $report->completed_sites, true ) : false;

						$str_info = '';
					if ( ! empty( $comp_ids ) && is_array( $comp_ids ) ) {

						$failed_ids = $success_ids = $gen_failed_ids = array();
						foreach ( $comp_ids as $sid => $val ) {
							if ( $val == 1 ) {
								$success_ids[] = $sid;
							} elseif ( $val == 0 ) {
								$failed_ids[] = $sid;
							} elseif ( $val > 1 ) { // generated content failed
								$gen_failed_ids[] = $sid;
							}
						}

						if ( ! empty( $success_ids ) ) {
							$str_info = 'Success: ' . count( $success_ids );
						}

						if ( ! empty( $failed_ids ) ) {
							$str_info .= '<br/>';
							$str_info .= 'Send failed: ' . count( $failed_ids );
						}

						if ( ! empty( $gen_failed_ids ) ) {
							$str_info .= '<br/>';
							$str_info .= 'Generate failed: ' . count( $gen_failed_ids ) . ' (' . implode( ',', $gen_failed_ids ) . ')';
						}
					}

					if ( $str_info != '' ) {
						?>
						<br/>
						<em>
						<?php echo $str_info; ?>
						</em>
						<?php
					}
				}

				?>
				</td>
				<td>
					<?php
					if ( $is_scheduled ) {
						$date_from = $report->date_from_nextsend;
						$date_to   = $report->date_to_nextsend;
						if ( empty( $date_from ) && $report->date_from ) {
							$date_from = $report->date_from;
						}
						if ( empty( $date_to ) && $report->date_to ) {
							$date_to = $report->date_to;
						}
					} else {
						$date_from = $report->date_from;
						$date_to   = $report->date_to;
					}
					?>
					<?php echo ! empty( $date_from ) ? MainWP_Pro_Reports_Utility::format_datestamp( $date_from, true ) . ' - ' : ''; ?>
					<?php echo ! empty( $date_to ) ? MainWP_Pro_Reports_Utility::format_datestamp( $date_to, true ) : ''; ?>
				</td>
				<td><?php echo $sche_column; ?></td>
				<td>
					<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&id=<?php echo $report->id; ?>"><?php _e( 'Edit', 'mainwp-pro-reports-extension' ); ?></a>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=replicate&id=<?php echo $report->id; ?>"><?php _e( 'Duplicate', 'mainwp-pro-reports-extension' ); ?></a>
							<?php if ( ! $disable_act_buttons ) : ?>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=preview&id=<?php echo $report->id; ?>"><?php _e( 'Preview' ); ?></a>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=save_pdf&id=<?php echo $report->id; ?>" ><?php _e( 'Download PDF' ); ?></a>
							<?php endif; ?>
							<?php if ( ! $disable_act_buttons && ! $is_scheduled ) : ?>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=send&id=<?php echo $report->id; ?>"><?php _e( 'Send' ); ?></a>
							<?php endif; ?>
							<a href="#" action="delete" class="item reports_action_row_lnk"><?php _e( 'Delete', 'mainwp-pro-reports-extension' ); ?></a>
						</div>
					</div>
					<span class="status"></span>
				</td>
			</tr>
			<?php
		}
	}

	// Display New & Edit report tab content
	public static function render_report() {
		$messages      = $errors = array();
		$report_action = '';
		$report_id     = 0;
		$report        = false;

		if ( isset( $_GET['action'] ) ) {
			if ( 'send' === (string) $_GET['action'] ) {
				$report_action = 'send';
			} elseif ( 'preview' === (string) $_GET['action'] ) {
					$report_action = 'preview';
			} elseif ( 'preview_generated' === (string) $_GET['action'] ) {
					$report_action = 'preview_generated';
			} elseif ( 'replicate' === (string) $_REQUEST['action'] ) {
					$report_action = 'replicate';
			} elseif ( 'save_pdf' === (string) $_REQUEST['action'] ) {
					$report_action = 'get_save_pdf';
			} elseif ( 'download_pdf' === $_GET['action'] ) {
					$report_action = 'download_pdf';
			}
		} elseif ( isset( $_POST['pro-report-action'] ) ) {
			if ( 'send' === (string) $_POST['pro-report-action'] ) {
				$report_action = 'send';
			} elseif ( 'send_test' === (string) $_POST['pro-report-action'] ) {
				$report_action = 'send_test';
			} elseif ( 'save_pdf' === (string) $_POST['pro-report-action'] ) {
				$report_action = 'save_pdf';
			} elseif ( 'preview' === (string) $_POST['pro-report-action'] ) {
				$report_action = 'preview';
			}
		}

		if ( isset( $_REQUEST['id'] ) ) {
			$report_id = $_REQUEST['id'];
		}

		$result_save = array();

		if ( isset( $_POST['pro-report-action'] ) && ! empty( $_POST['pro-report-action'] ) ) {
			$result_save = self::handle_report_saving();
			$report_id   = isset( $result_save['id'] ) && $result_save['id'] ? $result_save['id'] : 0;
			if ( isset( $result_save['message'] ) ) {
				$messages = $result_save['message'];
			}
			if ( isset( $result_save['error'] ) ) {
				$errors = $result_save['error'];
			}
		}

		if ( isset( $_GET['message'] ) ) {
			if ( 1 == $_GET['message'] ) {
				$messages[] = __( 'Report(s) sent successfully.', 'mainwp-pro-reports-extension' );
			}
		}

		if ( $report_id && $report_action == 'download_pdf' ) {
			// !=== 1 - pdf report ===!//
			$report_pdf = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );
			if ( $report_pdf ) {
				$time = date( 'His' );
				// $contents_pdfs = MainWP_Pro_Reports::gen_content_pdf_to_download( $report_pdf );
				$report_contents = MainWP_Pro_Reports_DB::get_instance()->get_pro_report_generated_content( $report_id );

				if ( $report_contents ) {
					?>
					<script type="text/javascript">
						jQuery(document).ready( function ($) {
								<?php
								foreach ( $report_contents as $_content ) {
									$site_id = $_content->site_id;
									$content = $_content->report_content_pdf;
									set_transient( 'mainwp_report_pdf_' . $time . '_' . $site_id . '_' . $report_id, $content, 60 * 60 * 1 ); // 1 hour cache.
									unset( $report_pdf );
									?>
										window.open( 'admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report&action=saveaspdf&id=<?php echo esc_attr( $report_id ); ?>&time=<?php echo esc_attr( $time ); ?>&siteid=<?php echo intval( $site_id ); ?>&_nonce_savepdf=<?php echo wp_create_nonce( '_nonce_savepdf' ); ?>', '_blank' );
									<?php
								}
								?>
						});
					</script>
					<?php
				}
				$messages[] = __( 'Generating PDF document(s).', 'mainwp-pro-reports-extension' );
			}
		}

		if ( $report_id ) {
			if ( false == $report ) {
				$report = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );
			}
		}

		if ( $report_action == 'replicate' ) {
			$report->id           = $report_id = 0;
			$report->title        = '';
			$report->attach_files = '';
			// do not replicate client info
			$report->send_to_name  = '[client.name]';
			$report->send_to_email = '[client.email]';
			$report->bcc_email     = '';
			$report->client        = '[client.name]';
			// $report->subject = 'Report for [client.site.name]'; // replicate subject
			$report->client_id = 0;
		}

		$selected_site = 0;

		if ( isset( $_REQUEST['action'] ) ) {
			if ( 'newreport' == $_REQUEST['action'] ) {
				if ( isset( $_GET['selected_site'] ) && ! empty( $_GET['selected_site'] ) ) {
					$selected_site = $_GET['selected_site'];
				}
				$report_action = 'create_new';
			}
		}

		$sel_sites = $sel_groups = array();

		if ( $report_action == 'preview' || $report_action == 'send' || $report_action == 'send_test' ) {

				$check_valid = true;

			if ( empty( $report ) || ! is_object( $report ) ) {
				$errors[]    = __( 'Error report data' );
				$check_valid = false;
			} else {
				$sel_sites  = unserialize( base64_decode( $report->sites ) );
				$sel_groups = unserialize( base64_decode( $report->groups ) );
				if ( ( ! is_array( $sel_sites ) || count( $sel_sites ) == 0 ) && ( ! is_array( $sel_groups ) || count( $sel_groups ) == 0 ) ) {
					$errors[]    = __( 'Please select a website or group' );
					$check_valid = false;
				}
			}

			if ( ! $check_valid ) {
				if ( $report_action == 'send' || $report_action == 'preview' ) {
					$report_action = '';
				}
			}

			if ( $report_action == 'send' && empty( $report->send_to_email ) ) {
				$errors[]      = __( 'Send To Email Address field can not be empty' );
				$report_action = '';
			}

			if ( $report_action == 'send' && $report->scheduled ) {
				$errors[]      = __( 'Scheduled report not sent.', 'mainwp-client-reports-extension' );
				$report_action = '';
			}
		}

		$str_error   = ( count( $errors ) > 0 ) ? implode( '<br/>', $errors ) : '';
		$str_message = ( count( $messages ) > 0 ) ? implode( '<br/>', $messages ) : '';

		global $mainWPProReportsExtensionActivator;

		$websites  = apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), null );
		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
		}

		$option = array(
			'plugin_upgrades' => true,
			'plugins'         => true,
		);

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $sites_ids, array(), $option );

		$sites_with_creport = array();
		foreach ( $dbwebsites as $website ) {
			if ( $website && $website->plugins != '' ) {
				$plugins = json_decode( $website->plugins, 1 );
				if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
					foreach ( $plugins as $plugin ) {
						if ( 'mainwp-child-reports/mainwp-child-reports.php' == $plugin['slug'] ) {
							if ( $plugin['active'] ) {
								$sites_with_creport[] = $website->id;
								break;
							}
						}
					}
				}
			}
		}

		unset( $dbwebsites );

		$scheduled_creport = false;
		if ( ! empty( $report ) && ! empty( $report->scheduled ) ) {
			$scheduled_creport = true;
		}

		$scheduled_report = false;
		$selected_sites   = array();
		$selected_groups  = array();

		if ( ! empty( $report ) && ! empty( $report->scheduled ) ) {
			$scheduled_report = true;
		}

		if ( ! empty( $report ) ) {
			$selected_sites  = unserialize( base64_decode( $report->sites ) );
			$selected_groups = unserialize( base64_decode( $report->groups ) );
			if ( ! is_array( $selected_sites ) ) {
				$selected_sites = array();
			}
			if ( ! is_array( $selected_groups ) ) {
				$selected_groups = array();
			}
		}

		?>
		<form method="post" enctype="multipart/form-data" id="mainwp-pro-report-form" action="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=report<?php echo ! empty( $report_id ) ? '&id=' . $report_id : ''; ?>">
			<div class="mainwp-main-content">
				<div class="ui hidden fitted divider"></div>
				<?php if ( ! empty( $str_error ) ) : ?>
				<div class="ui red message" ><?php echo $str_error; ?><i class="close icon mainwp-notice-hide"></i></div>
				<?php endif; ?>
				<?php if ( ! empty( $str_message ) ) : ?>
				<div  class="ui green message" ><?php echo $str_message; ?><i class="close icon mainwp-notice-hide"></i></div>
				<?php endif; ?>
				<div class="ui yellow message" id="edit-reports-message-zone" style="display:none"></div>
				<h3 class="ui dividing header"><?php echo __( 'Report Settings', 'mainwp-pro-reports-extension' ); ?></h3>
				<div class="ui info message" <?php echo ! self::showMainWPMessage( 'notice', 'mainwp-pro-reports-new-report-info' ) ? 'style="display: none"' : ''; ?>>
					<?php echo __( 'The Create Reports page consists of two major sections the email sending the report and the PDF report that is attached to that email. ', 'mainwp-pro-reports-extension' ); ?>
					<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-new-report-info"></i>
				</div>
				<!-- Report options -->
				<?php self::new_report_setting( $report ); ?>
				<!-- End Report options -->
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<div class="mainwp-select-sites" id="mainwp-pro-reports-select-sites-box">
					<div class="ui header"><?php _e( 'Select Sites', 'mainwp-pro-reports-extension' ); ?></div>
					<?php do_action( 'mainwp_select_sites_box', '', 'checkbox', true, true, '', '', $selected_sites, $selected_groups ); ?>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-submit">
					<input type="submit" value="<?php _e( 'Preview Report', 'mainwp-pro-reports-extension' ); ?>" id="mainwp-pro-reports-preview-report-button" name="mainwp-pro-reports-preview-report-button" class="ui big green basic fluid button">
					<div class="ui hidden fitted divider"></div>
					<input type="button" value="<?php _e( 'Save Draft', 'mainwp-pro-reports-extension' ); ?>" id="mainwp-pro-reports-save-report-button" name="mainwp-pro-reports-save-report-button" class="ui big green basic fluid button">
					<div class="ui hidden fitted divider"></div>
					<input type="submit" value="<?php _e( 'Download PDF', 'mainwp-pro-reports-extension' ); ?>" id="mainwp-pro-reports-pdf-button" name="mainwp-pro-reports-pdf-button" class="ui big green basic fluid button">
					<div class="ui hidden fitted divider"></div>
					<input type="submit" value="<?php _e( 'Send Now', 'mainwp-pro-reports-extension' ); ?>" id="mainwp-pro-reports-send-report-button" name="mainwp-pro-reports-send-report-button" class="ui big green fluid button">
					<input type="submit" value="<?php _e( 'Schedule Report', 'mainwp-pro-reports-extension' ); ?>" id="mainwp-pro-reports-schedule-report-button" name="mainwp-pro-reports-schedule-report-button" class="ui big green fluid button">
				</div>
			</div>
			<div class="ui clearing hidden divider"></div>
			<input type="hidden" name="pro-report-action" id="pro-report-action" value="">
			<input type="hidden" name="report-id" value="<?php echo ( is_object( $report ) && isset( $report->id ) ) ? $report->id : '0'; ?>">
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'mainwp-pro-reports-nonce' ); ?>">
		</form>

		<div class="ui small modal" id="mainwp-pro-reports-generating-report-modal">
			<div class="header"><?php echo $report ? esc_html( $report->title ) : ''; ?></div>
			<div class="scrolling content" id="mainwp-pro-reports-generating-report-content">
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-client-reprots-extension' ); ?></div>
			</div>
		</div>
		<?php
		$show_preview = false;
		?>
		<div class="ui small modal" id="mainwp-pro-reports-preview-modal">
			<div class="header"><?php _e( 'Report Preview', 'mainwp-client-reports-extension' ); ?></div>
			<div class="scrolling content" style="padding:0" id="mainwp-pro-reports-preview-content">
				<?php
				if ( is_object( $report ) ) {
					if ( $report_action == 'preview_generated' ) {
						echo self::render_preview_report( $report );
						$show_preview = true;
					}
				}
				?>
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php _e( 'Close', 'mainwp-client-reports-extension' ); ?></div>
				<input type="button" <?php echo $scheduled_creport ? 'style="display:none"' : ''; ?> value="<?php _e( 'Send Now' ); ?>" class="ui button green" id="mainwp-pro-reports-preview-send-button"/>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				mainwp_pro_reports_remove_sites_without_reports_plugin('<?php echo implode( ',', $sites_with_creport ); ?>');
				<?php
				if ( $report_action != '' && in_array( $report_action, array( 'preview', 'send_test', 'save_pdf', 'get_save_pdf', 'send' ) ) ) {
					?>
					  mainwp_reports_to_load_sites( '<?php echo esc_html( $report_action ); ?>', '<?php echo intval( $report_id ); ?>' );
					<?php
				}
				if ( $report_action == 'create_new' && $selected_site ) {
					?>
					$('#selected_sites_<?php echo $selected_site; ?>').trigger('click');
					<?php
				}

				if ( $show_preview ) {
					?>
						jQuery( '#mainwp-pro-reports-preview-modal' ).modal({onHide: function(){
																			jQuery('#mainwp-pro-reports-preview-content').html('');
																}}).modal( 'show' );
					<?php
				}
				?>
			  });
		  </script>

		<?php
	}

	// Report options
	public static function new_report_setting( $report = null ) {
		$scheduled_report  = false;
		$recurringSchedule = '';

		if ( ! empty( $report ) ) {
			if ( ! empty( $report->scheduled ) ) {
				$scheduled_report = true;
			}
			$recurringSchedule = $report->recurring_schedule;
		}
		?>
					
			<div class="ui form <?php echo $recurringSchedule; ?>" id="mainwp-pro-report-settings">
			<?php self::get_pro_report_options( $report ); ?>
			</div>
			<div class="ui form">
			<?php self::get_pro_report_customization_options( $report ); ?>
			</div>
			<div class="ui form">
			<?php self::get_pro_report_email_options( $report ); ?>
			</div>
		<?php
	}

	public static function get_pro_report_options( $report = null ) {
		$title               = '';
		$from                = '';
		$to                  = '';
		$logo                = '';
		$recurring_schedule  = '';
		$recurring_date      = '';
		$recurring_month     = '';
		$recurring_day       = '';
		$current_template    = '';
		$schedule_send_email = 'email_auto';
		$schedule_bcc_me     = 0;
		$scheduled_report    = false;
		$send_on_style       = $send_on_day_of_week_style = $send_on_day_of_mon_style = $send_on_month_style = $monthly_style = 'style="display:none"';
		$messages            = array();

		$recurring_types = array(
			'daily'   => __( 'Daily', 'mainwp-pro-reports-extension' ),
			'weekly'  => __( 'Weekly', 'mainwp-pro-reports-extension' ),
			'monthly' => __( 'Monthly', 'mainwp-pro-reports-extension' ),
		);

		$day_of_week = array(
			1 => __( 'Monday', 'mainwp-pro-reports-extension' ),
			2 => __( 'Tuesday', 'mainwp-pro-reports-extension' ),
			3 => __( 'Wednesday', 'mainwp-pro-reports-extension' ),
			4 => __( 'Thursday', 'mainwp-pro-reports-extension' ),
			5 => __( 'Friday', 'mainwp-pro-reports-extension' ),
			6 => __( 'Saturday', 'mainwp-pro-reports-extension' ),
			7 => __( 'Sunday', 'mainwp-pro-reports-extension' ),
		);

		if ( ! empty( $report ) ) {
			$title               = $report->title;
			$from                = ! empty( $report->date_from ) ? date( 'Y-m-d', $report->date_from ) : '';
			$to                  = ! empty( $report->date_to ) ? date( 'Y-m-d', $report->date_to ) : '';
			$recurring_schedule  = $report->recurring_schedule;
			$recurring_day       = $report->recurring_day;
			$schedule_send_email = $report->schedule_send_email;
			$schedule_bcc_me     = isset( $report->schedule_bcc_me ) ? $report->schedule_bcc_me : 0;
			$scheduled_report    = isset( $report->scheduled ) && ! empty( $report->scheduled ) ? true : false;
			$current_template    = $report->template;

			if ( $scheduled_report && ( $recurring_schedule == 'weekly' || $recurring_schedule == 'monthly' ) ) {
				$send_on_style = '';
				if ( $recurring_schedule == 'weekly' ) {
					$send_on_day_of_week_style = '';
				} elseif ( $recurring_schedule == 'monthly' ) {
					$send_on_day_of_mon_style = $monthly_style = '';
					$recurring_date           = $recurring_day;
				}
			}
		}

		if ( $current_template == '' ) {
			$current_template = 'pro-report-default.php';
		}

		if ( $scheduled_report && ! empty( $recurring_schedule ) ) {
			$messages[] = esc_html__( 'This report has been scheduled', 'mainwp-pro-reports-extension' );
		}

		?>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo __( 'Report title', 'mainwp-pro-reports-extension' ); ?></label>
		  <div class="six wide column" data-tooltip="<?php esc_attr_e( 'Enter your report title. It is for internal use only.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<input type="text" name="pro-report-title" id="pro-report-title" placeholder="Required field" value="<?php echo esc_attr( stripslashes( $title ) ); ?>" />
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo __( 'Report type', 'mainwp-pro-reports-extension' ); ?></label>
		  <div class="ten wide column" data-tooltip="<?php esc_attr_e( 'If you need to send this report only once, select the "One-time" option. If you want to set automated reports, select "Recurring".', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<select name='pro-report-type' id="pro-report-type" class="ui dropdown">
					<option value="0" <?php echo ! $scheduled_report ? 'selected="selected"' : ''; ?>><?php _e( 'One-time', 'mainwp-pro-reports-extension' ); ?></option>
					<option value="1" <?php echo $scheduled_report ? 'selected="selected"' : ''; ?>><?php _e( 'Recurring', 'mainwp-pro-reports-extension' ); ?></option>
				</select>
			</div>
		</div>

		<div class="ui grid field" id="scheduled_schedule_selection_wrap">
			<label class="four wide column middle aligned"><?php echo __( 'Schedule', 'mainwp-pro-reports-extension' ); ?></label>
		  <div class="six wide column" data-tooltip="<?php esc_attr_e( 'Do you want to run Daily, Weekly or Monthly reports?', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<select name='pro-report-schedule' id="pro-report-schedule-select" class="ui dropdown">
					<option value=""><?php _e( 'Off', 'mainwp-pro-reports-extension' ); ?></option>
					<?php
					foreach ( $recurring_types as $value => $title ) {
						$_select = '';
						if ( $recurring_schedule == $value ) {
							$_select = 'selected';
						}
						echo '<option value="' . $value . '" ' . $_select . '>' . $title . '</option>';
					}
					?>
				</select>
			</div>
		</div>
		
		<div class="ui grid field" id="scheduled_send_on_day_of_week_wrap">
			<label class="four wide column middle aligned"><?php echo __( 'Send report on', 'mainwp-pro-reports-extension' ); ?></label>
		  <div class="six wide column" data-tooltip="<?php esc_attr_e( 'When do you want to send the report?', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<select name='pro-report-schedule-day' id="pro-report-schedule-day" class="ui dropdown">
					<?php
					foreach ( $day_of_week as $value => $title ) {
						$_select = '';
						if ( $recurring_day == $value ) {
							$_select = 'selected';
						}
						echo '<option value="' . $value . '" ' . $_select . '>' . $title . '</option>';
					}
					?>
				</select>
			</div>
		</div>

		<div class="ui grid field" id="scheduled_send_on_day_of_month_wrap">
			<label class="four wide column middle aligned"><?php echo __( 'Send on', 'mainwp-pro-reports-extension' ); ?></label>
		  <div class="six wide column" data-tooltip="<?php esc_attr_e( 'When do you want to send the report?', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<select name="pro-report-schedule-month-day" id="pro-report-schedule-month-day" class="ui dropdown">
				<?php
				$day_suffix = array(
					1 => 'st',
					2 => 'nd',
					3 => 'rd',
				);
				for ( $x = 1; $x <= 31; $x++ ) {
					$_select = '';
					if ( $recurring_date == $x ) {
						$_select = 'selected';
					}
					$remain = $x % 10;
					$day_sf = isset( $day_suffix[ $remain ] ) ? $day_suffix[ $remain ] : 'th';
					echo '<option value="' . $x . '" ' . $_select . '>' . $x . $day_sf . ' of the month</option>';
				}
				?>
				  </select>
			</div>
		</div>

		<div class="ui grid field" id="scheduled_date_range_wrap">
			<label class="four wide column middle aligned"><?php echo __( 'Report date range', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="three wide column" data-tooltip="<?php esc_attr_e( 'Select the date range for the report?', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<div class="ui calendar">
					<div class="ui input left icon">
						<i class="calendar icon"></i>
						<input type="text" placeholder="From (yyyy-m-d)" name="pro-report-from-date" id="pro-report-from-date" value="<?php echo $from; ?>"/>
					</div>
				</div>
			</div>
			<div class="three wide column">
				<div class="ui calendar">
					<div class="ui input left icon">
						<i class="calendar icon"></i>
						<input type="text" placeholder="To (yyyy-m-d)" name="pro-report-to-date" id="pro-report-to-date" value="<?php echo $to; ?>" />
					</div>
				</div>
			</div>
		</div>

		<div class="ui grid field" id="scheduled_additional_options_wrap">
			<label class="four wide column middle aligned"><?php echo __( 'Additional options', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column">
				<div class="ui radio checkbox" data-tooltip="<?php esc_attr_e( 'If selected, report will be sent to you for a review. Once you make sure that report is ok, you will need to send it to your client.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
					<input type="radio" name="pro-report-schedule-send-email" value="email_review" id="pro-report-schedule-send-email-me-review" <?php echo ( 'email_review' == $schedule_send_email ) ? 'checked' : ''; ?>/><label for="pro-report-schedule-send-email-me-review"><?php _e( 'Email me when report is complete so I can review', 'mainwp-pro-reports-extension' ); ?></label>
				</div>
				<br />
				<div class="ui radio checkbox" data-tooltip="<?php esc_attr_e( 'If selected, report will be sent to your client directly.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
					<input type="radio" name="pro-report-schedule-send-email" value="email_auto" id="pro-report-schedule-send-email-auto" <?php echo ( 'email_auto' == $schedule_send_email ) ? 'checked' : ''; ?>/><label for="pro-report-schedule-send-email-auto"><?php _e( 'Automatically email my client the report', 'mainwp-pro-reports-extension' ); ?></label>
				</div>
				<br />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<div class="ui checkbox" data-tooltip="<?php esc_attr_e( 'If selected, report will be sent to your client and you.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
					<input type="checkbox" name="pro-report-schedule-send-email-bcc-me" value="1" id="pro-report-schedule-send-email-bcc-me" <?php echo $schedule_bcc_me ? 'checked' : ''; ?> /><label for="pro-report-schedule-send-email-bcc-me"><?php _e( 'BCC me on report email', 'mainwp-pro-reports-extension' ); ?></label>
				</div>
			</div>
		</div>

		<script type="text/javascript">
				jQuery( document ).ready( function () {
						mainwp_pro_reports_date_selection_init();
				} );
		</script>

		<div class="ui hidden divider"></div>

		<h3 class="ui dividing header"><?php echo __( 'Report Template Selection', 'mainwp-pro-reports-extension' ); ?></h3>

		<?php
		$extraHeaders = array(
			'TemplateName'  => 'Template Name',
			'Description'   => 'Description',
			'Version'       => 'Version',
			'Author'        => 'Author',
			'ScreenshotURI' => 'Screenshot URI',
		);
		$tempHeaders  = array();

		$temp_files = MainWP_Pro_Reports_Template::get_instance()->get_template_files();
		foreach ( $temp_files as $template => $file_name ) {
			 $path                     = MainWP_Pro_Reports_Template::get_instance()->get_template_file_path( $template );
			 $tempHeaders[ $template ] = get_file_data( $path, $extraHeaders );
		}

		?>


		<div class="ui grid field">
			<label class="four wide column middle aligned"></label>
			<div class="twelve wide column">
				<div class="ui info message" id="mainwp-pro-reports-template-selection-info">
					<p><?php echo __( 'Select one of the available report templates. If needed, you can create a new custom template from scratch or copy and edit one of the existing templates.', 'mainwp-pro-reports-extension' ); ?></p>
					<p><?php echo __( 'To create a new template, download the copy of the extension from the My Account area and copy one of the default template located in the ../plugins/mainwp-pro-reports-extension/templates/reports/ folder of the extension and copy it to the ../wp-content/uploads/mainwp/report-templates/ directory and rename the file. Once copied, you can use your favorite code editor to edit it.', 'mainwp-pro-reports-extension' ); ?></p>
				</div>
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column"><?php echo __( 'Report template', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="four wide column" data-tooltip="<?php esc_attr_e( 'Select one of the available report templates.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<select name="pro-report-template" id="pro-report-template" class="ui dropdown not-auto-init" >
				<?php
				foreach ( $temp_files as $file => $template ) {
					$_select = '';
					if ( $current_template == $file ) {
						$_select = 'selected';
					}
					echo '<option value="' . $file . '" ' . $_select . ' tab-value="' . str_replace( "/", "-", $file ) . '">' . $template . '</option>';
				}
				?>
				</select>
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column"><?php echo __( 'Report template info', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="twelve wide column" id="reports-tab-template-details">
				<?php foreach ( $tempHeaders as $file => $header ) : ?>
					<div class="ui tab secondary segment <?php echo ( $file == $current_template ) ? 'active' : ''; ?>" data-tab="<?php echo esc_html( str_replace("/", "-", $file ) ); ?>">
						<div class="ui grid">
						<div class="four wide column">
								<?php if ( isset( $header['ScreenshotURI'] ) ) : ?>
								<a href="<?php echo WP_CONTENT_URL . '/' . esc_html( $header['ScreenshotURI'] ); ?>" target="_blank">
									<img src="<?php echo WP_CONTENT_URL . '/' . esc_html( $header['ScreenshotURI'] ); ?>" alt="Screenshot URI" style="width:230px">
								</a>
								<?php endif; ?>
						</div>
							<div class="twelve wide column">
								<p><strong><?php esc_html_e( 'Template name:', 'mainwp-pro-reports-extension' ); ?></strong> <?php echo isset( $header['TemplateName'] ) ? esc_html( $header['TemplateName'] ) : ''; ?></p>
								<p><strong><?php esc_html_e( 'Template author:', 'mainwp-pro-reports-extension' ); ?></strong> <?php echo isset( $header['Author'] ) ? esc_html( $header['Author'] ) : ''; ?></p>
								<p><strong><?php esc_html_e( 'Template version:', 'mainwp-pro-reports-extension' ); ?></strong> <?php echo isset( $header['Version'] ) ? esc_html( $header['Version'] ) : ''; ?></p>
								<p><strong><?php esc_html_e( 'Description:', 'mainwp-pro-reports-extension' ); ?></strong> <?php echo isset( $header['Description'] ) ? esc_html( $header['Description'] ) : ''; ?></p>
					</div>
			</div>
		</div>
				<?php endforeach; ?>
			</div>
		</div>

		<script type="text/javascript">
			jQuery( document ).ready( function ($) {
				jQuery('#pro-report-template').dropdown({
						onChange: function( val, text, $choice ) {
							var tab = $('#pro-report-template').find(":selected").attr('tab-value');
							$('#reports-tab-template-details').tab('change tab', tab);
						}
					}
				);
				if (mainwpParams.use_wp_datepicker == 1) {
					jQuery( '#mainwp-pro-reports-report-tab .ui.calendar input[type=text]' ).datepicker( { dateFormat: "yy-mm-dd" } );
				} else {
					$('#mainwp-pro-reports-report-tab .ui.calendar').calendar({
							type: 'date',
							monthFirst: false,
							formatter: {
								date: function ( date ) {
									if (!date) return '';
									var day = date.getDate();
									var month = date.getMonth() + 1;
									var year = date.getFullYear();

									if (month < 10) {
										month = '0' + month;
									}
									if (day < 10) {
										day = '0' + day;
									}
									return year + '-' + month + '-' + day;
								}
							}
					});
				}
			});
		</script>
		<?php
	}

	public static function get_pro_report_customization_options( $report = null ) {

		$heading = __( 'Website Care Report', 'mainwp-pro-reports-extension' );
		$intro   = __( 'Thank you for trusting us with your website. In this report, you can find a summary of your website condition and maintenance services provided in the period of [report.daterange].', 'mainwp-pro-reports-extension' );
		$outro   = __( 'If you have any further questions, please feel free to contact us.', 'mainwp-pro-reports-extension' );

		$logo              = $header_image = '';
		$logo_id           = $header_image_id = 0;
		$bg_color          = '#ffffff';
		$text_color        = '#444444';
		$accent_color      = '#666666';
		$showhide_sections = array();

		$enable_media     = apply_filters( 'mainwp_pro_reports_enable_media_editor', false );
		$enable_quicktags = apply_filters( 'mainwp_pro_reports_enable_quicktags_editor', false );
		$standard_editor  = apply_filters( 'mainwp_pro_reports_enable_standard_editor', false );

		if ( ! empty( $report ) ) {
			// $logo = $report->attach_logo;
			// $header_image = $report->header_image;

			$logo_id = $report->logo_id;
			if ( $logo_id ) {
				$logo = wp_get_attachment_url( $report->logo_id );
			}

			$header_image_id = $report->header_image_id;
			if ( $header_image_id ) {
				$header_image = wp_get_attachment_url( $report->header_image_id );
			}

			$heading      = $report->heading;
			$intro        = $report->intro;
			$outro        = $report->outro;
			$bg_color     = $report->background_color;
			$text_color   = $report->text_color;
			$accent_color = $report->accent_color;

			if ( ! empty( $report->showhide_sections ) ) {
				$showhide_sections = json_decode( $report->showhide_sections, 1 );
			}
		}

		if ( ! is_array( $showhide_sections ) ) {
			$showhide_sections = array();
		}

		?>
		<div class="ui hidden divider"></div>

		<h3 class="ui dividing header"><?php echo __( 'Report Content & Design Customization', 'mainwp-pro-reports-extension' ); ?></h3>

		<div id="mainwp-pro-reports-customizations-menu" class="ui inverted fluid four item large pointing menu">
			<a class="active item" data-tab="content">
				<i class="edit icon"></i>
				<?php echo __( 'Custom Content', 'mainwp-pro-reports-extension' ); ?>
			</a>
			<a class="item" data-tab="sections">
				<i class="check square outline icon"></i>
				<?php echo __( 'Report Data', 'mainwp-pro-reports-extension' ); ?>
			</a>
		  <a class="item" data-tab="images">
				<i class="image icon"></i>
				<?php echo __( 'Personal Branding', 'mainwp-pro-reports-extension' ); ?>
			</a>
		  <a class="item" data-tab="colors">
				<i class="paint brush icon"></i>
				<?php echo __( 'Custom Report Colors', 'mainwp-pro-reports-extension' ); ?>
			</a>
		</div>
		<div class="ui active tab secondary segment" data-tab="content" style="margin-top: -13px;">
			<div class="ui grid field">
				<label class="four wide column"><?php echo __( 'Report heading', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="twelve wide column parent-tokens-modal" input-id="pro-report-heading" data-tooltip="<?php esc_attr_e( 'Enter the report heading. It will be displayed in the top of the first page of the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="bottom left" data-inverted="">
					<div class="ui right aligned secondary segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo __( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
					<input type="text" name="pro-report-heading" id="pro-report-heading" placeholder="<?php echo __( 'Website Care Report', 'mainwp-pro-reports-extension' ); ?>" value="<?php echo esc_attr( stripslashes( $heading ) ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column"><?php echo __( 'Report introduction message', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="twelve wide column parent-tokens-modal" editor-id="pro-report-intro" data-tooltip="<?php esc_attr_e( 'Optionally, you can add introduction content here.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
					<div class="ui right aligned secondary segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo __( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
						<?php
						remove_editor_styles(); // stop custom theme styling interfering with the editor
						wp_editor(
							stripslashes( $intro ),
							'pro-report-intro',
							array(
								'textarea_name' => 'pro-report-intro',
								'textarea_rows' => 10,
								'teeny'         => $standard_editor ? false : true,
								'media_buttons' => $enable_media ? true : false,
								'quicktags'     => $enable_quicktags ? true : false,
							)
						);
						?>
					</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column"><?php echo __( 'Report closing message', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="twelve wide column">
					<div class="parent-tokens-modal" editor-id="pro-report-outro" data-tooltip="<?php esc_attr_e( 'Optionally, you can add closing content here.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
						<div class="ui right aligned secondary segment" style="padding:0px">
							<a href="#" class="mainwp-tokens-modal"><?php echo __( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
						</div>
						<?php
						wp_editor(
							stripslashes( $outro ),
							'pro-report-outro',
							array(
								'textarea_name' => 'pro-report-outro',
								'textarea_rows' => 10,
								'teeny'         => $standard_editor ? false : true,
								'media_buttons' => $enable_media ? true : false,
								'quicktags'     => $enable_quicktags ? true : false,
							)
						);
						?>
				</div>
			</div>
		</div>
		</div>
		<?php
		$default_val = 0;
		?>
		<div class="ui tab secondary segment" data-tab="sections" style="margin-top: -13px;">
			<div class="ui info message" <?php echo ! self::showMainWPMessage( 'notice', 'mainwp-pro-reports-sections' ) ? 'style="display: none"' : ''; ?>>
				<div class="ui list">
					<div class="item"><?php echo __( 'If selected "Show," the extension will always show the section in the report even if there is no recorded data for this section.', 'mainwp-pro-reports-extension' ); ?></div>
					<div class="item"><?php echo __( 'If selected "Hide," the section will always be hidden in reports regardless.', 'mainwp-pro-reports-extension' ); ?></div>
					<div class="item"><?php echo __( 'If selected "Hide if empty," the section will be included only in case there is data to be displayed.', 'mainwp-pro-reports-extension' ); ?></div>
				</div>
				<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-sections"></i>
			</div>
			<!-- SHOW -->
			<?php $wp_up_val = isset( $showhide_sections['wp-update'] ) ? intval( $showhide_sections['wp-update'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'WordPress updates', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[wp-update]">
						<option value="0" <?php echo $wp_up_val == 0 ? 'selected="selected"' : ''; ?> ><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of WP updates > 0 in selected time range -->
						<option value="1" <?php echo $wp_up_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $wp_up_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php $plugins_up_val = isset( $showhide_sections['plugins-updates'] ) ? intval( $showhide_sections['plugins-updates'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'Plugins updates', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[plugins-updates]">
						<option value="0" <?php echo $plugins_up_val == 0 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of Plugin updates > 0 in selected time range -->
						<option value="1" <?php echo $plugins_up_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $plugins_up_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php $themes_up_val = isset( $showhide_sections['themes-updates'] ) ? intval( $showhide_sections['themes-updates'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'Themes updates', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[themes-updates]">
						<option value="0" <?php echo $themes_up_val == 0 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of Theme updates > 0 in selected time range -->
						<option value="1" <?php echo $themes_up_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $themes_up_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<!-- ### -->
			<!-- SHOW ONLY IF AUM INSTALLED -->
			<?php if ( is_plugin_active( 'advanced-uptime-monitor-extension/advanced-uptime-monitor-extension.php' ) ) { ?>
				<?php $uptime_val = isset( $showhide_sections['uptime'] ) ? intval( $showhide_sections['uptime'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'Uptime monitoring', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown"  name="pro-report-showhide-sections[uptime]">
						<option value="0" <?php echo $uptime_val == 0 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if monitor is created for the site and uptimeratio is not empty or N/A -->
						<option value="1" <?php echo $uptime_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $uptime_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF SUCURI INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-sucuri-extension/mainwp-sucuri-extension.php' ) ) { ?>
				<?php $security_val = isset( $showhide_sections['security'] ) ? intval( $showhide_sections['security'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'Security', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown"  name="pro-report-showhide-sections[security]">
						<option value="0" <?php echo $security_val == 0 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="1" <?php echo $security_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $security_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF ANY Suported BAckup Extension INSTALLED -->
			<?php
			if ( is_plugin_active( 'mainwp-backwpup-extension/mainwp-backwpup-extension.php' )
						|| is_plugin_active( 'mainwp-backupwordpress-extension/mainwp-backupwordpress-extension.php' )
						|| is_plugin_active( 'mainwp-buddy-extension/mainwp-buddy-extension.php' )
						|| is_plugin_active( 'mainwp-updraftplus-extension/mainwp-updraftplus-extension.php' )
						|| is_plugin_active( 'mainwp-timecapsule-extension/mainwp-timecapsule-extension.php' )
			) {
				?>
				<?php $backups_val = isset( $showhide_sections['backups'] ) ? intval( $showhide_sections['backups'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'Backups', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[backups]">
						<option value="0" <?php echo $backups_val == 0 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if number of Theme updates > 0 in selected time range -->
						<option value="1" <?php echo $backups_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $backups_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF GA INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-google-analytics-extension/mainwp-google-analytics-extension.php' ) ) { ?>
				<?php $ga_val = isset( $showhide_sections['ga'] ) ? intval( $showhide_sections['ga'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'Analytics', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[ga]">
						<option value="0" <?php echo $ga_val == 0 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if there is any GA data from the site -->
						<option value="1" <?php echo $ga_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $ga_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF PIWIK INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-piwik-extension/mainwp-piwik-extension.php' ) ) { ?>
				<?php $matomo_val = isset( $showhide_sections['matomo'] ) ? intval( $showhide_sections['matomo'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'Analytics', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[matomo]">
						<option value="0" <?php echo $matomo_val == 0 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if there is any PIWIK data from the site -->
						<option value="1" <?php echo $matomo_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $matomo_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF PageSPeed INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-page-speed-extension/mainwp-page-speed-extension.php' ) ) { ?>
				<?php $pagespeed_val = isset( $showhide_sections['pagespeed'] ) ? intval( $showhide_sections['pagespeed'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'PageSpeed', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[pagespeed]">
						<option value="0" <?php echo $pagespeed_val == 0 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if there is any PS data for the site -->
						<option value="1" <?php echo $pagespeed_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $pagespeed_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF Lighthouse INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-lighthouse-extension/mainwp-lighthouse-extension.php' ) ) { ?>
				<?php $lighthouse_val = isset( $showhide_sections['lighthouse'] ) ? intval( $showhide_sections['lighthouse'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'Lighthouse', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[lighthouse]">
						<option value="0" <?php echo $lighthouse_val == 0 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- show the section in report if there is any LH data for the site -->
						<option value="1" <?php echo $lighthouse_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $lighthouse_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
			<!-- SHOW ONLY IF Maintenance INSTALLED -->
			<?php if ( is_plugin_active( 'mainwp-maintenance-extension/mainwp-maintenance-extension.php' ) ) { ?>
				<?php $maintenance_val = isset( $showhide_sections['maintenance'] ) ? intval( $showhide_sections['maintenance'] ) : $default_val; ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo __( 'Maintenance', 'mainwp-pro-reports-extension' ); ?></label>
			  <div class="three wide column" data-tooltip="<?php esc_attr_e( 'Do you want to show this in the report.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<select class="ui dropdown" name="pro-report-showhide-sections[maintenance]">
						<option value="0" <?php echo $maintenance_val == 0 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide if empty', 'mainwp-pro-reports-extension' ); ?></option> <!-- Show if number of scans is > 0 -->
						<option value="1" <?php echo $maintenance_val == 1 ? 'selected="selected"' : ''; ?>><?php echo __( 'Show', 'mainwp-pro-reports-extension' ); ?></option>
						<option value="2" <?php echo $maintenance_val == 2 ? 'selected="selected"' : ''; ?>><?php echo __( 'Hide', 'mainwp-pro-reports-extension' ); ?></option>
					</select>
				</div>
			</div>
			<?php } ?>
			<!-- ### -->
		</div>
		<div class="ui tab secondary segment" data-tab="images" style="margin-top: -13px;">
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo __( 'Your logo', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column uploader-row-wrapper" data-tooltip="<?php esc_attr_e( 'Upload your logo here.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<p class="image_wrapper">
					<?php if ( ! empty( $logo ) ) : ?>
						<img src="<?php echo $logo; ?>" style="max-width: 400px;"/>
					<?php endif; ?>
					</p>
					<input type="hidden" class="image_wp_media_post_id" value="<?php echo intval( $logo_id ); ?>" name="pro-report-logo">
					<input type="button" href="#" class="upload_image_button ui mini green button" value="<?php esc_html_e( 'Upload', 'mainwp-pro-reports-extension' ); ?>">
					<input type="button" href="#" class="remove_image_button ui mini button" value="<?php esc_html_e( 'Remove', 'mainwp-pro-reports-extension' ); ?>">
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo __( 'Header Image', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column uploader-row-wrapper" data-tooltip="<?php esc_attr_e( 'Upload your custom first page image here or leave blank to use default one.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<p class="image_wrapper">
					<?php if ( ! empty( $header_image ) ) : ?>
					<img src="<?php echo $header_image; ?>" style="max-width:100%;"/>
					<?php endif; ?>
					</p>
					<input type="hidden" class="image_wp_media_post_id" value="<?php echo intval( $header_image_id ); ?>" name="pro-report-header-image">
					<input type="button" href="#" class="upload_image_button ui mini green button" value="<?php esc_html_e( 'Upload', 'mainwp-pro-reports-extension' ); ?>">
					<input type="button" href="#" class="remove_image_button ui mini button" value="<?php esc_html_e( 'Remove', 'mainwp-pro-reports-extension' ); ?>">
				</div>
			</div>
		</div>
		<div class="ui tab secondary segment" data-tab="colors" style="margin-top: -13px;">
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo __( 'Background color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set page background color.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-background-color" data-default-color="#ffffff" class="pro-report-color-picker" id="pro-report-background-color"  value="<?php echo esc_html( $bg_color ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo __( 'Text color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set default text color.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-text-color" data-default-color="#444444" class="pro-report-color-picker" id="pro-report-text-color"  value="<?php echo esc_html( $text_color ); ?>" />
				</div>
			</div>
			<div class="ui grid field">
				<label class="four wide column middle aligned"><?php echo __( 'Accent color', 'mainwp-pro-reports-extension' ); ?></label>
				<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Set accent color. It will be applied to links, heading and other important data.', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted="">
					<input type="text" name="pro-report-accent-color" data-default-color="#666666" class="pro-report-color-picker" id="pro-report-accent-color"  value="<?php echo esc_html( $accent_color ); ?>" />
				</div>
			</div>
		</div>

	<script type="text/javascript">
		jQuery( document ).ready( function () {

			jQuery( '#mainwp-pro-reports-customizations-menu .item' ).tab();

				var formfield;
				jQuery(document).on("click", '.upload_image_button', function(e) {
							e.preventDefault();
							formfield = jQuery(this).closest('.uploader-row-wrapper');
							var file_frame;
							// media frame.
							file_frame = wp.media({
								title: 'Select a image to upload',
								button: {
									text: 'Use this image',
								},
								multiple: false
							});
							// Image is selected
							file_frame.on( 'select', function() {
								attachment = file_frame.state().get('selection').first().toJSON();
								formfield.val( attachment.url );
								if(formfield.find("img").length > 0)
									formfield.find("img").attr("src", attachment.url);
								else
									formfield.find(".image_wrapper").append('<img src="'+attachment.url+'" />');
								formfield.find(".image_wp_media_post_id").val( attachment.id );
							});

							file_frame.on('open',function() {
								var selection =  file_frame.state().get('selection');
								var selected_id = formfield.find(".image_wp_media_post_id").val();
								if (selected_id) {
									var attachment = wp.media.attachment( selected_id );
									attachment.fetch();
									selection.add( attachment ? [ attachment ] : [] );
								}
							});

							// open the modal
							file_frame.open();
				});

				jQuery(document).on( "click", '.remove_image_button', function(e) {
					e.preventDefault();
					formfield = jQuery(this).closest('.uploader-row-wrapper');
					if(formfield.find("img").length > 0)
						formfield.find("img").remove();
					formfield.find(".image_wp_media_post_id").val( 0 );
				});


				jQuery('#pro-report-background-color').wpColorPicker({
					hide: true,
					palettes: true
				});
				jQuery('#pro-report-content-background-color').wpColorPicker({
					hide: true,
					palettes: true
				});
				jQuery('#pro-report-text-color').wpColorPicker({
					hide: true,
					palettes: true
				});
				jQuery('#pro-report-accent-color').wpColorPicker({
					hide: true,
					palettes: true
				});
			} );

		 </script>
		<?php
	}

	public static function get_pro_report_email_options( $report = null ) {
		$from_name              = '';
		$from_email             = '';
		$to_client_name         = '[client.name]';
		$to_email               = '[client.email]';
		$email_subject          = 'Report for [client.site.name]';
		$email_message          = __( 'Hi, here is the website care report for the past month. Find it attached', 'mainwp-pro-reports-extension' );
		$bcc_email              = '';
		$current_email_template = '';
		$attachFiles            = '';
		$reply_to_email         = '';
		$reply_to_name          = '';

		if ( ! empty( $report ) ) {
			$from_name  = $report->fname;
			$from_email = $report->femail;

			$to_email               = $report->send_to_email;
			$bcc_email              = $report->bcc_email;
			$to_client_name         = $report->send_to_name;
			$email_subject          = $report->subject;
			$email_message          = $report->message;
			$attachFiles            = isset( $report->attach_files ) ? $report->attach_files : '';
			$current_email_template = $report->template_email;
			$reply_to_email         = $report->reply_to;
			$reply_to_name          = $report->reply_to_name;
		}

		if ( $current_email_template == '' ) {
			$current_email_template = 'pro-report-email-default.php';
		}

		$tokens = MainWP_Pro_Reports_DB::get_instance()->get_tokens();

		$enable_media     = apply_filters( 'mainwp_pro_reports_enable_media_editor', false );
		$enable_quicktags = apply_filters( 'mainwp_pro_reports_enable_quicktags_editor', false );
		$standard_editor  = apply_filters( 'mainwp_pro_reports_enable_standard_editor', false );
		?>
		<div class="ui hidden divider"></div>

		<h3 class="ui dividing header"><?php echo __( 'Email Settings', 'mainwp-pro-reports-extension' ); ?></h3>

		<div class="ui info message" <?php echo ! self::showMainWPMessage( 'notice', 'mainwp-pro-reports-email-settings-info' ) ? 'style="display: none"' : ''; ?>>
			<?php echo __( 'If you are having problems with your MainWP Dashboard site not sending emails, you can consider installing some SMTP plugin that will allow you to route emails through your favorite SMTP provider.', 'mainwp-pro-reports-extension' ); ?>
			<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-email-settings-info"></i>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo __( 'Send email from', 'mainwp-pro-reports-extension' ); ?></label>
		  <div class="six wide column" data-tooltip="<?php esc_attr_e( 'Enter the "Send from" email address. Usually, this will be your email address.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<input type="text" name="pro-report-from-email" id="pro-report-from-email" placeholder="Email (required)" value="<?php echo esc_attr( stripslashes( $from_email ) ); ?>" />
			</div>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Enter the "Send from" name. Usually, this will be your or your company name.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<input type="text" name="pro-report-from-name" id="pro-report-from-name" placeholder="Name" value="<?php echo esc_attr( stripslashes( $from_name ) ); ?>" />
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column"><?php echo __( 'Send email to', 'mainwp-pro-reports-extension' ); ?></label>
		  <div class="six wide column" data-tooltip="<?php esc_attr_e( 'Enter the recipient\'s email address or use the corresponding token. If needed, add multiple email addresses separated by a comma.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<div class="parent-tokens-modal" input-id="pro-report-to-email">
					<div class="ui right aligned segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo __( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
				<input type="text" name="pro-report-to-email" placeholder="Email (required)" value="<?php echo esc_attr( stripslashes( $to_email ) ); ?>" id="pro-report-to-email"/>
			</div>
			</div>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Enter the recipient\'s name or use the corresponding token.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<div class="parent-tokens-modal" input-id="pro-report-to-client">
					<div class="ui right aligned segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo __( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
					<input type="text" name="pro-report-to-client" placeholder="Client" value="<?php echo esc_attr( stripslashes( $to_client_name ) ); ?>" id="pro-report-to-client" />
				</div>
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo __( 'Reply-to', 'mainwp-client-reports-extension' ); ?></label>
			<div class="six wide column">
				<input type="text" name="pro-report-reply-to" placeholder="Reply-to Email address (optional)" value="<?php echo esc_attr( stripslashes( $reply_to_email ) ); ?>" />
			</div>
			<div class="six wide column">
				<input type="text" name="pro-report-reply-to-name" placeholder="Name" value="<?php echo esc_attr( stripslashes( $reply_to_name ) ); ?>" />
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column"><?php echo __( 'Subject', 'mainwp-pro-reports-extension' ); ?></label>
		  <div class="twelve wide column" data-tooltip="<?php esc_attr_e( 'Enter the email subject. Tokens are allowed.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<div class="parent-tokens-modal" input-id="pro-report-email-subject">
					<div class="ui right aligned segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo __( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
				<input type="text" name="pro-report-email-subject" value="<?php echo esc_attr( stripslashes( $email_subject ) ); ?>" id="pro-report-email-subject" />
			</div>
		</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column"><?php echo __( 'Email message', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="twelve wide column" data-tooltip="<?php esc_attr_e( 'Enter the email content. Tokens are allowed.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
			  <div class="parent-tokens-modal" editor-id="pro-report-email-message">
					<div class="ui right aligned segment" style="padding:0px">
						<a href="#" class="mainwp-tokens-modal"><?php echo __( 'Insert tokens', 'mainwp-pro-reports-extension' ); ?></a> | <a href="#" id="mainwp-email-preview-button"><?php echo __( 'Preview message', 'mainwp-pro-reports-extension' ); ?></a>
					</div>
					  <?php
						wp_editor(
							stripslashes( $email_message ),
							'pro-report-email-message',
							array(
								'textarea_name' => 'pro-report-email-message',
								'textarea_rows' => 10,
								'teeny'         => $standard_editor ? false : true,
								'media_buttons' => $enable_media ? true : false,
								'quicktags'     => $enable_quicktags ? true : false,
							)
						);
						?>
				</div>
			</div>
			</div>

		<?php	$temp_email_files = MainWP_Pro_Reports_Template::get_instance()->get_template_email_files(); ?>

		<div class="ui grid field">
			<label class="four wide column middle aligned"></label>
			<div class="twelve wide column">
				<div class="ui info message" id="mainwp-pro-reports-email-template-selection-info">
					<p><?php echo __( 'If needed, you can create a new custom template from scratch or copy and edit the existing template.', 'mainwp-pro-reports-extension' ); ?></p>
					<p><?php echo __( 'To create a new template, download the copy of the extension from the My Account area and copy one of the default template located in the /mainwp-pro-reports-extension/templates/emails/ folder of the extension and copy it to the ../wp-content/uploads/mainwp/report-email-templates/ directory and rename the file. Once copied, you can use your favorite code editor to edit it.', 'mainwp-pro-reports-extension' ); ?></p>
				</div>
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo __( 'Email template', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Select one of the available email templates.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">

				<select name="pro-report-email-template" id="pro-report-email-template" class="ui dropdown" >
				<?php
				foreach ( $temp_email_files as $file => $template ) {
					$_select = '';
					if ( $current_email_template == $file ) {
						$_select = 'selected';
					}
					echo '<option value="' . $file . '" ' . $_select . '>' . $template . '</option>';
				}
				?>
				</select>
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo __( 'Email BCC', 'mainwp-pro-reports-extension' ); ?></label>
		  <div class="six wide column" data-tooltip="<?php esc_attr_e( 'Optionally, enter the BCC email address.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<input type="text" name="pro-report-bcc-email" id="pro-report-bcc-email" placeholder="Email address (optional)" value="<?php echo esc_attr( stripslashes( $bcc_email ) ); ?>" />
			</div>
		</div>

		<div class="ui grid field">
			<label class="four wide column middle aligned"><?php echo __( 'Additional email attachment', 'mainwp-pro-reports-extension' ); ?></label>
			<div class="six wide column" data-tooltip="<?php esc_attr_e( 'Optionally, add another email attachment if needed.', 'mainwp-pro-reports-extension' ); ?>" data-position="top left" data-inverted="">
				<?php if ( ! empty( $attachFiles ) ) : ?>
						<div class="eight wide column">
						<div><?php echo $attachFiles; ?></div>
						<span class="ui checkbox">
							<input type="checkbox" value="1"  id="pro-report-email-attachement-remove-files" name="pro-report-email-attachement-remove-files">
							<label for="pro-report-email-attachement-remove-files"><?php _e( 'Delete attached files', 'mainwp-pro-reports-extension' ); ?></label>
						</span>
						</div>
					<?php endif; ?>
				<input type="file" name="pro-report-email-attachements[]"  id="pro-report-email-attachements[]" multiple="true">
			</div>
		</div>
		<input type="hidden" name="pro-report-client-id" value="0">

		<div class="ui mini modal" id="mainwp-pro-reports-insert-tokens-modal">
			<div class="header"><?php _e( 'Available Tokens', 'mainwp-pro-reports-extension' ); ?></div>
			<div class="scrolling content">
				<div class="ui info message">
					<?php echo __( 'Tokens let you use client data in your emails. Simply click one below to insert it.', 'mainwp-pro-reports-extension' ); ?>
				</div>
				<div class="ui hidden divider"></div>
				<?php if ( is_array( $tokens ) && count( $tokens ) > 0 ) : ?>
					<div class="ui relaxed divided list">
					<?php foreach ( (array) $tokens as $token ) : ?>
						<?php
						if ( ! $token ) {
							continue;}
						?>
						<a href="#" class="item pro-reports-edit-insert-token" token-id="<?php echo $token->id; ?>" token-value="[<?php echo stripslashes( $token->token_name ); ?>]">
						<div class="content">
						  <div class="header">[<?php echo stripslashes( $token->token_name ); ?>]</div>
						  <span style="font-size:1rem;color:rgba(0,0,0,.6);"><?php echo stripslashes( $token->token_description ); ?></span>
						</div>
					</a>
					<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php _e( 'Close', 'mainwp-pro-reports-extension' ); ?></div>
			</div>
		</div>

		<div class="ui small modal" id="mainwp-pro-reports-preview-email-modal">
			<div class="header"><?php _e( 'Message Preview', 'mainwp-pro-reports-extension' ); ?></div>
			<div class="scrolling content">
				<div class="ui segments">
				  <div class="ui secondary segment" id="mainwp-pro-email-subject-show"></div>
				  <div class="ui segment" id="mainwp-pro-email-message-show"></div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php _e( 'Close', 'mainwp-pro-reports-extension' ); ?></div>
			</div>
		</div>

		<script type="text/javascript">
			var current_InsertingInputId = false;
			var current_InsertingInputType = false;
			jQuery( '.mainwp-tokens-modal' ).on( 'click', function(e) {
					var parent = jQuery(this).closest('.parent-tokens-modal');
					current_InsertingInputId = parent.attr('editor-id');
					if (typeof current_InsertingInputId !== typeof undefined && current_InsertingInputId!== false) {
						current_InsertingInputType = 'editor';
					} else {
						current_InsertingInputId = parent.attr('input-id');
						current_InsertingInputType = 'text';
					}
					jQuery( '#mainwp-pro-reports-insert-tokens-modal' ).modal( 'show' );

					return false;
			} );

			jQuery( document ).on('click', '.pro-reports-edit-insert-token', function (e) {
				var replace_text = jQuery( this ).attr('token-value');
				console.log(replace_text);

				if (current_InsertingInputType == 'editor') { // editor input
					var edName = current_InsertingInputId;
					var editor = tinyMCE.get( edName );
				}

				var set_new_pos = replace_text.length;
				if (editor != null && typeof (editor) !== "undefined" && editor.isHidden() == false) {
//					if (replace_text.indexOf( "[section." ) !== -1) {
//						var end_section = replace_text.replace( "[section.", "[/section." );
//						replace_text = replace_text + '<br/><span id="crp_ed_cursor"></span><br/>' + end_section;
//					}
					editor.execCommand( 'mceInsertContent', false, replace_text );
					var cursor = editor.dom.select( 'span#crp_ed_cursor' );
					if (cursor != null && typeof (cursor[0]) !== "undefined") {
						editor.selection.select( cursor[0] ).remove();
					}
				} else {
//					if (replace_text.indexOf( "[section." ) !== -1) {
//						var end_section = replace_text.replace( "[section.", "[/section." );
//						set_new_pos++;
//						replace_text = replace_text + '\n\n' + end_section;
//					}
					var obj = jQuery( "#" + current_InsertingInputId );
					var str = obj.val();
					var pos = pro_reports_getPos( obj[0] );
					str = str.substring( 0, pos ) + replace_text + str.substring( pos, str.length )
					obj.val( str );
					set_new_pos += pos;
					pro_reports_setPos( obj[0], set_new_pos, set_new_pos );
				}
				jQuery( '#mainwp-pro-reports-insert-tokens-modal' ).modal( 'hide' );
				return false;
			});

			function pro_reports_getPos(obj) {
				var pos = 0;	// IE Support
				if (document.selection) {
					obj.focus();
					var range = document.selection.createRange();
					range.moveStart( 'character', -obj.value.length );
					pos = range.text.length;
				} // Firefox support
				else if (obj.selectionStart || obj.selectionStart == '0') {
					pos = obj.selectionStart;
				}
				return (pos);
			}

			function pro_reports_setPos(obj, selectionStart, selectionEnd) {
				if (document.selection) {
					obj.focus();
					var range = document.selection.createRange();
					range.collapse( true );
					range.moveEnd( 'character', selectionEnd );
					range.moveStart( 'character', selectionStart );
					range.select();
				} // Firefox support
				else {
					obj.focus();
					obj.setSelectionRange( selectionStart, selectionEnd );
				}
			}

		</script>
		<?php
	}

	// Display custom tokens table
	public static function get_pro_reports_custom_tokens() {
		$tokens = MainWP_Pro_Reports_DB::get_instance()->get_tokens();
		?>
		<div class="ui info message" <?php echo ! self::showMainWPMessage( 'notice', 'mainwp-pro-reports-manage-tokens' ) ? 'style="display: none"' : ''; ?>>
			<?php echo __( 'These tokens will allow you to display data you have set in the Child Site edit screen. For each child site, go to the site Edit page to set the token values. Once values are set, you will easily display data for the selected sites in reports.', 'mainwp-pro-reports-extension' ); ?>
			<i class="ui close icon mainwp-notice-dismiss" notice-id="mainwp-pro-reports-manage-tokens"></i>
		</div>
		<table id="mainwp-pro-reports-custom-tokens-table" class="ui selectable compact table" style="width:100%">
			<thead>
				<tr>
					<th><?php _e( 'Token Name', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Token Description', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( is_array( $tokens ) && count( $tokens ) > 0 ) : ?>
					<?php foreach ( (array) $tokens as $token ) : ?>
						<?php
						if ( ! $token ) {
							continue;}
						?>
						<tr class="mainwp-token" token-id="<?php echo $token->id; ?>">
							<td class="token-name">[<?php echo stripslashes( $token->token_name ); ?>]</td>
							<td class="token-description"><?php echo stripslashes( $token->token_description ); ?></td>
							<td>
								<div class="ui left pointing dropdown icon mini basic green button">
									<i class="ellipsis horizontal icon"></i>
									<div class="menu">
										<a class="item" id="mainwp-pro-reports-edit-custom-token" href="#"><?php _e( 'Edit', 'mainwp-pro-reports-extension' ); ?></a>
										<a class="item" id="mainwp-pro-reports-delete-custom-token" href="#"><?php _e( 'Delete', 'mainwp-pro-reports-extension' ); ?></a>
									</div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<th><a class="ui mini green button" href="#" id="mainwp-pro-reports-new-custom-token-button"><?php _e( 'New Token', 'mainwp-pro-reports-extension' ); ?></a></th>
					<th><?php _e( '', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</tfoot>
		</table>

		<script type="text/javascript">
		// Init datatables
		jQuery( '#mainwp-pro-reports-custom-tokens-table' ).DataTable( {
			"stateSave": true,
			"stateDuration": 0,
			"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
			"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
			"order": [ [ 0, "asc" ] ],
			"language": { "emptyTable": "No tokens found." },
			"drawCallback" : function( settings ) {
				jQuery( '#mainwp-pro-reports-custom-tokens-table .ui.dropdown').dropdown();
			},
		} );
		</script>

		<div class="ui modal" id="mainwp-pro-reports-new-custom-token-modal">
			<div class="header"><?php echo __( 'Custom Token', 'mainwp-pro-reports-extension' ); ?></div>
			<div class="content ui mini form">
				<div class="ui yellow message" style="display:none"></div>
				<div class="field">
					<label><?php _e( 'Token Name', 'mainwp-pro-reports-extension' ); ?></label>
					<input type="text" value="" class="token-name" name="token-name" placeholder="<?php esc_attr_e( 'Enter token name (without of square brackets)', 'mainwp-pro-reports-extension' ); ?>">
				</div>
				<div class="field">
					<label><?php _e( 'Token Description', 'mainwp-pro-reports-extension' ); ?></label>
					<input type="text" value="" class="token-description" name="token-description" placeholder="<?php esc_attr_e( 'Enter token description', 'mainwp-pro-reports-extension' ); ?>">
				</div>
			</div>
			<div class="actions">
				<input type="button" class="ui green button" id="mainwp-pro-reports-create-new-custom-token" value="<?php esc_attr_e( 'Save Token', 'mainwp-pro-reports-extension' ); ?>">
				<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-pro-reports-extension' ); ?></div>
			</div>
		</div>

		<div class="ui small modal" id="mainwp-pro-reports-update-token-modal">
			<div class="header"><?php echo __( 'Custom Token', 'mainwp-pro-reports-extension' ); ?></div>
			<div class="content ui mini form">
				<div class="ui yellow message" style="display:none"></div>
				<div class="field">
					<label><?php _e( 'Token Name', 'mainwp-pro-reports-extension' ); ?></label>
					<input type="text" value="" class="token-name" name="token-name" placeholder="<?php esc_attr_e( 'Enter token name (without of square brackets)', 'mainwp-pro-reports-extension' ); ?>">
				</div>
				<div class="field">
					<label><?php _e( 'Token Description', 'mainwp-pro-reports-extension' ); ?></label>
					<input type="text" value="" class="token-description" name="token-description" placeholder="<?php esc_attr_e( 'Enter token description', 'mainwp-pro-reports-extension' ); ?>">
				</div>
				<input type="hidden" value="" id="token-id" name="token-id">
			</div>
			<div class="actions">
				<input type="button"  class="ui green button" id="mainwp-save-pro-reports-custom-token" value="<?php esc_attr_e( 'Save Token', 'mainwp-pro-reports-extension' ); ?>">
				<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-pro-reports-extension' ); ?></div>
			</div>
		</div>
		<?php
	}


	public static function set_init_params() {
		@ignore_user_abort( true );
		$timeout = 10 * 60 * 60;
		@set_time_limit( $timeout );
		$mem = '1024M';
		@ini_set( 'memory_limit', $mem );
		@ini_set( 'max_execution_time', 0 );
	}

	public static function render_preview_report( $report ) {
		self::set_init_params();
		ob_start();
		if ( ! empty( $report ) ) {
			$report_contents = MainWP_Pro_Reports_DB::get_instance()->get_pro_report_generated_content( $report->id );
			if ( is_array( $report_contents ) ) {
				foreach ( $report_contents as $content ) {
					echo json_decode( $content->report_content );
					echo '<br /><hr /><br />';
				}
			}
		} else {
			?>
			<div class="ui yellow message"><?php _e( 'Preview could not be generated. Please try again.', 'mainwp-pro-reports-extension' ); ?></div>
			<?php
		}
		$output = ob_get_clean();
		return $output;
	}

	public static function gen_report_content( $report ) {
		$convert_nl2br = apply_filters( 'mainwp_client_reports_newline_break', false );
		ob_start();
		if ( is_array( $report ) && isset( $report['error'] ) ) {
				echo 'Error reporting data';
		} elseif ( is_object( $report ) ) {
			if ( $convert_nl2br ) {
				echo stripslashes( nl2br( $report->filtered_body ) );
			} else {
				echo stripslashes( $report->filtered_body );
			}
		}
		$output = ob_get_clean();
		return $output;
	}

	// not used
	public static function gen_content_pdf_to_download( $report ) {
		// to fix bug from mainwp
		if ( ! function_exists( 'wp_verify_nonce' ) ) {
			include_once ABSPATH . WPINC . '/pluggable.php';
		}

		self::set_init_params();

		if ( ! empty( $report ) && is_object( $report ) ) {
			// return non-array content for pdf
			$report_contents = MainWP_Pro_Reports_DB::get_instance()->get_pro_report_generated_content( $report->id );
			$content_pdfs    = array();
			if ( is_array( $report_contents ) ) {
				foreach ( $report_contents as $content ) {
					$content_pdf .= json_decode( $content->report_content_pdf );
				}
			}
			return $content_pdf;
		}
		return '';
	}

	public static function gen_report_content_pdf( $filtered_reports ) {

		$convert_nl2br = apply_filters( 'mainwp_client_reports_newline_break', false );
		ob_start();

		foreach ( $filtered_reports as $site_id => $report ) {
			if ( is_array( $report ) && isset( $report['error'] ) ) {
				echo 'Error reporting data';
			} elseif ( is_object( $report ) ) {
				if ( $convert_nl2br ) {
					echo stripslashes( nl2br( $report->filtered_body ) );
				} else {
					echo stripslashes( $report->filtered_body );
				}
			}
		}

		$output = ob_get_clean();
		return $output;
	}


	public static function get_addition_tokens( $site_id ) {
		$tokens_value = array();
		$site_info    = apply_filters( 'mainwp_getwebsiteoptions', false, $site_id, 'site_info' );
		if ( $site_info ) {
			$site_info = json_decode( $site_info, true );
			if ( is_array( $site_info ) ) {
				$map_site_tokens = array(
					'client.site.version' => 'wpversion',   // Displays the WP version of the child site,
					'client.site.theme'   => 'themeactivated', // Displays the currently active theme for the child site
					'client.site.php'     => 'phpversion', // Displays the PHP version of the child site
					'client.site.mysql'   => 'mysql_version', // Displays the MySQL version of the child site
				);
				foreach ( $map_site_tokens as $tok => $val ) {
					$tokens_value[ $tok ] = ( is_array( $site_info ) && isset( $site_info[ $val ] ) ) ? $site_info[ $val ] : '';
				}
			}
		}
		$get_issues                        = apply_filters( 'mainwp_getwebsiteoptions', false, $site_id, 'health_site_status' );
		$issue_counts                      = json_decode( $get_issues, true );
		$issues_total                      = $issue_counts['recommended'] + $issue_counts['critical'];
		$tokens_value['site.health.score'] = intval( $issues_total );
		return $tokens_value;
	}

	public static function filter_report_website( $templ_content, $report, $website, $cust_from_date = 0, $cust_to_date = 0, $type = '' ) {

		$templ_content = apply_filters( 'mainwp_pro_reports_filter_report_content', $templ_content, $report, $website, $cust_from_date, $cust_to_date, $type );

		$rep_from_date = 0;
		$rep_to_date   = 0;

		$report_id       = 0;
		$logo_id         = 0;
		$header_image_id = 0;

		if ( $report ) {

			$rep_from_date = $report->date_from;
			$rep_to_date   = $report->date_to;

			$report_id       = $report->id;
			$logo_id         = $report->logo_id;
			$header_image_id = $report->header_image_id;

		}

		$date_from = $cust_from_date ? $cust_from_date : $rep_from_date;
		$date_to   = $cust_to_date ? $cust_to_date : $rep_to_date;

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		// first, to remove section with [hide-section-data]
		$templ_content = preg_replace_callback( '/\[config-section-data\](.*?)\[\/config-section-data\]/is', array( 'MainWP_Pro_Reports', '_callback_hide_section' ), $templ_content );

		$attach_logo = $header_image = '';

		if ( $logo_id ) {
			$attach_logo = wp_get_attachment_url( $logo_id );
		}

		if ( $header_image_id ) {
			$header_image = wp_get_attachment_url( $header_image_id );
		}

		$output                = new stdClass();
		$output->filtered_body = $templ_content;

		$output->id = $report_id;

		$get_ga_tokens = ( strpos( $templ_content, '[ga.' ) !== false ) ? true : false;
		$get_ga_chart  = ( strpos( $templ_content, '[ga.visits.chart]' ) !== false ) ? true : false;
		$get_ga_chart  = $get_ga_chart || ( ( strpos( $templ_content, '[ga.visits.maximum]' ) !== false ) ? true : false );

		$get_piwik_tokens         = ( strpos( $templ_content, '[piwik.' ) !== false ) ? true : false;
		$get_aum_tokens           = ( strpos( $templ_content, '[aum.' ) !== false ) ? true : false;
		$get_woocom_tokens        = ( strpos( $templ_content, '[wcomstatus.' ) !== false ) ? true : false;
		$get_pagespeed_tokens     = ( strpos( $templ_content, '[pagespeed.' ) !== false ) ? true : false;
		$get_virusdie_tokens      = ( strpos( $templ_content, '[virusdie.' ) !== false ) ? true : false;
		$get_vulnerable_tokens    = ( strpos( $templ_content, '[vulnerable.' ) !== false || strpos( $templ_content, '[vulnerabilities.' ) !== false ) ? true : false;
		$get_lighthouse_tokens    = ( strpos( $templ_content, '[lighthouse.' ) !== false ) ? true : false;
		$get_domainmonitor_tokens = ( strpos( $templ_content, '[domain.' ) !== false ) ? true : false;

		$get_other_tokens = ( strpos( $templ_content, '[installed.plugins]' ) !== false ) || ( strpos( $templ_content, '[installed.themes]' ) !== false );

		if ( ! empty( $website ) ) {
				$tokens                = MainWP_Pro_Reports_DB::get_instance()->get_tokens();
				$site_tokens           = MainWP_Pro_Reports_DB::get_instance()->get_site_tokens( $website['id'] );
				$replace_tokens_values = array();
			foreach ( $tokens as $token ) {
				$replace_tokens_values[ '[' . $token->token_name . ']' ] = isset( $site_tokens[ $token->id ] ) ? $site_tokens[ $token->id ]->token_value : '';
			}
				$client_addition_tokens = self::get_addition_tokens( $website['id'] );
			if ( is_array( $client_addition_tokens ) ) {
				foreach ( $client_addition_tokens as $token => $value ) {
					$replace_tokens_values[ '[' . $token . ']' ] = $value;
				}
			}

			if ( $get_piwik_tokens ) {
				$piwik_tokens = self::get_ext_tokens_piwik( $website['id'], $date_from, $date_to );
				if ( is_array( $piwik_tokens ) ) {
					foreach ( $piwik_tokens as $token => $value ) {
						$replace_tokens_values[ '[' . $token . ']' ] = $value;
					}
				}
			}

			if ( $get_ga_tokens ) {
				$ga_tokens = self::get_ext_tokens_ga( $website['id'], $date_from, $date_to, $get_ga_chart );
				if ( is_array( $ga_tokens ) ) {
					foreach ( $ga_tokens as $token => $value ) {
						$replace_tokens_values[ '[' . $token . ']' ] = $value;
					}
				}
			}

			if ( $get_aum_tokens ) {
				$aum_tokens = self::get_ext_tokens_aum( $website['id'], $date_from, $date_to );
				if ( is_array( $aum_tokens ) ) {
					foreach ( $aum_tokens as $token => $value ) {
						$replace_tokens_values[ '[' . $token . ']' ] = $value;
					}
				}
			}

			if ( $get_woocom_tokens ) {
				$wcomstatus_tokens = self::get_ext_tokens_woocomstatus( $website['id'], $date_from, $date_to );
				if ( is_array( $wcomstatus_tokens ) ) {
					foreach ( $wcomstatus_tokens as $token => $value ) {
						$replace_tokens_values[ '[' . $token . ']' ] = $value;
					}
				}
			}

			if ( $get_pagespeed_tokens ) {
				$pagespeed_tokens = self::get_ext_tokens_pagespeed( $website['id'], $date_from, $date_to );
				if ( is_array( $pagespeed_tokens ) ) {
					foreach ( $pagespeed_tokens as $token => $value ) {
						$replace_tokens_values[ '[' . $token . ']' ] = $value;
					}
				}
			}

			if ( $get_vulnerable_tokens ) {
				$ext_tokens = self::get_ext_tokens_vulnerable( $website['id'], $date_from, $date_to );
				if ( is_array( $ext_tokens ) ) {
					foreach ( $ext_tokens as $token => $value ) {
						$replace_tokens_values[ '[' . $token . ']' ] = $value;
					}
				}
			}

			if ( $get_lighthouse_tokens ) {
				$ext_tokens = self::get_ext_tokens_lighthouse( $website['id'], $date_from, $date_to );
				if ( is_array( $ext_tokens ) ) {
					foreach ( $ext_tokens as $token => $value ) {
						$replace_tokens_values[ '[' . $token . ']' ] = $value;
					}
				}
			}

			if ( $get_domainmonitor_tokens ) {
				$ext_tokens = self::get_ext_tokens_domainmonitor( $website['id'], $date_from, $date_to );
				if ( is_array( $ext_tokens ) ) {
					foreach ( $ext_tokens as $token => $value ) {
						$replace_tokens_values[ '[' . $token . ']' ] = $value;
					}
				}
			}

			if ( ! empty( $attach_logo ) ) {
				$replace_tokens_values['[logo.url]'] = $attach_logo;
			} else {
				$replace_tokens_values['[logo.url]'] = '';
			}

			if ( ! empty( $header_image ) ) {
				$replace_tokens_values['[header.image.url]'] = $header_image;
			} else {
				$replace_tokens_values['[header.image.url]'] = plugins_url( 'images/background.png', dirname( __FILE__ ) );
			}

			$replace_tokens_values['[report.daterange]'] = MainWP_Pro_Reports_Utility::format_date( $date_from ) . ' - ' . MainWP_Pro_Reports_Utility::format_date( $date_to );
			$now = time();
			$replace_tokens_values['[report.send.date]'] = MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $now ) );

			if ( $get_other_tokens ) {
				self::get_other_tokens_value( $replace_tokens_values, $website['id'] );
			}

			/**
			* Filters for custom values of site tokens before generate the report content
			*
			* @since 4.0.1
			*
			* @param array $replace_tokens_values The array of tokens
			* @param object $report        The report.
			* @param string $website       The website.
			*/
			$replace_tokens_values = apply_filters( 'mainwp_pro_reports_custom_tokens', $replace_tokens_values, $report, $website, $templ_content );

			// replace empty/NA token value by [empty-report-data].
			foreach ( $replace_tokens_values as $token => $value ) {
				if ( $value == '' || $value == 'N/A' ) {
					$replace_tokens_values[ $token ] = '[empty-report-data]';
				}
			}

			$report_body = $templ_content;

			$result = self::parse_report_content( $report_body, $replace_tokens_values );

			self::$buffer['sections']['body'] = $sections['body'] = $result['sections']; // sections tokens
			$other_tokens['body']             = $result['other_tokens']; // other tokens
			$filtered_body                    = $result['filtered_content']; // filtered content: replaced addition tokens value

			unset( $result );

			$sections_data = $other_tokens_data = array();
			// gathering tokens data from child site
			// included: other_tokens_data and sections_data
			$information = self::fetch_remote_data( $website, $sections, $other_tokens, $date_from, $date_to );

			if ( is_array( $information ) && ! isset( $information['error'] ) ) {
				// proccess virusdie data.
				if ( $get_virusdie_tokens ) {
					$virusdie_sections     = array();
					$virusdie_other_tokens = array();
					$section_idx           = array();

					if ( isset( $sections['body'] ) ) {
						foreach ( $sections['body']['section_token'] as $idx => $sec ) {
							if ( false !== strpos( $sec, '[section.virusdie.' ) ) {
								$virusdie_sections['section_token'][]          = $sec;
								$virusdie_sections['section_content_tokens'][] = $sections['body']['section_content_tokens'][ $idx ];
								$section_idx[]                                 = $idx;
							}
						}
						foreach ( $other_tokens['body'] as $tok ) {
							if ( false !== strpos( $tok, '[virusdie.' ) ) {
								$virusdie_other_tokens[] = $tok;
							}
						}
					}

					$virusdie_data              = self::get_ext_tokens_virusdie( $website['id'], $date_from, $date_to, $virusdie_sections, $virusdie_other_tokens );
					$virusdie_sections_data     = isset( $virusdie_data['sections_data'] ) ? $virusdie_data['sections_data'] : array();
					$virusdie_other_tokens_data = isset( $virusdie_data['other_tokens_data'] ) ? $virusdie_data['other_tokens_data'] : array();

					$vir_idx = 0;
					foreach ( $section_idx as $idx ) {
						if ( isset( $virusdie_sections_data[ $vir_idx ] ) ) {
							$information['sections_data']['body'][ $idx ] = $virusdie_sections_data[ $vir_idx ];
						}
						$vir_idx++;
					}

					foreach ( $virusdie_other_tokens_data as $tok => $val ) {
						$information['other_tokens_data']['body'][ $tok ] = $val;
					}
				}
			}

			$information = self::fix_empty_logs_values( $information );

			if ( is_array( $information ) && ! isset( $information['error'] ) ) {
				$sections_data                 = isset( $information['sections_data'] ) ? $information['sections_data'] : array();
				self::$buffer['sections_data'] = $sections_data;
				$other_tokens_data             = isset( $information['other_tokens_data'] ) ? $information['other_tokens_data'] : array();
			} else {
				self::$buffer = array();
				return $information;
			}

			unset( $information );

			self::$count_sec_header = self::$count_sec_body = self::$count_sec_footer = 0;

			self::$raw_sec_body = false;

			if ( $type == 'raw' ) {
				// support get raw report data for body only
				self::$raw_sec_body     = true;
				self::$raw_section_body = array();
				$filtered_raw           = array();

				if ( is_array( $replace_tokens_values ) ) {
					foreach ( $replace_tokens_values as $token => $value ) {
						if ( strpos( $report_body, $token ) !== false ) {
							$filtered_raw[ $token ] = $value;
						}
					}
				}

				if ( isset( $other_tokens_data['body'] ) && is_array( $other_tokens_data['body'] ) ) {
					foreach ( $other_tokens_data['body'] as $token => $value ) {
						if ( in_array( $token, $other_tokens['body'] ) ) {
							$filtered_raw[ $token ] = $value;
						}
					}
				}

				if ( isset( $sections_data['body'] ) && is_array( $sections_data['body'] ) ) {
					$filtered_body = preg_replace_callback( '/(\[section\.[^\]]+\])(.*?)(\[\/section\.[^\]]+\])/is', array( 'MainWP_Pro_Reports', '_callback_replace_sections' ), $filtered_body );
					if ( is_array( self::$raw_section_body ) ) {
						foreach ( self::$raw_section_body as $sectoken => $values ) {
							$filtered_raw[ $sectoken ] = $values;
						}
					}
				}

				return $filtered_raw;
			}

			// if fetched sections data is correct then start replace
			if ( isset( $sections_data['body'] ) && is_array( $sections_data['body'] ) && count( $sections_data['body'] ) > 0 ) {
				// replace data in sections token
				$filtered_body = preg_replace_callback( '/(\[section\.[^\]]+\])(.*?)(\[\/section\.[^\]]+\])/is', array( 'MainWP_Pro_Reports', '_callback_replace_sections' ), $filtered_body );
			}

			// replace other tokens data
			if ( isset( $other_tokens_data['body'] ) && is_array( $other_tokens_data['body'] ) && count( $other_tokens_data['body'] ) > 0 ) {
				$search = $replace = array();
				foreach ( $other_tokens_data['body'] as $token => $value ) {
					if ( in_array( $token, $other_tokens['body'] ) ) {
						$search[] = $token;
						if ( $value === '' || $value == 'N/A' ) {
							if ( false !== strpos( $token, '.count]' ) ) {
								$replace[] = '<span token-control="[empty-section-data]">' . $value . '</span>';
							} else {
								$replace[] = '[empty-section-data]';
							}
						} else {
							$replace[] = $value;
						}
					}
				}
				// to fix
				foreach ( self::$data_tokens as $token => $val ) {
					if ( ! in_array( $token, $search ) ) {
						  $search[]  = $token;
						  $replace[] = '[empty-report-data]'; // to support remove-if-empty
					}
				}
				$filtered_body = str_replace( $search, $replace, $filtered_body );
			}

			// process [config-section-data] token, do this before process [hide-if-empty]
			$filtered_body = preg_replace_callback( '/\[config-section-data\](.*?)\[\/config-section-data\]/is', array( 'MainWP_Pro_Reports', '_callback_config_section' ), $filtered_body );

			// to compatible: remove [remove-if-empty] if data empty [empty-report-data]
			$filtered_body = preg_replace_callback( '/\[remove-if-empty\](.*?)\[\/remove-if-empty\]/is', array( 'MainWP_Pro_Reports', '_callback_remove_empty_content' ), $filtered_body ); // to compatible.

			// clear config-section-extra token
			$filtered_body = preg_replace_callback( '/\[config-section-extra[^\]]*\]/is', '__return_empty_string', $filtered_body );

			// clear tokens
			$filtered_body = str_replace( '[empty-report-data]', '', $filtered_body );
			$filtered_body = str_replace( '[empty-section-data]', '', $filtered_body );
			$filtered_body = str_replace( '[hide-if-empty]', '', $filtered_body );

			$output->filtered_body = $filtered_body;
			self::$buffer          = array();
		}
		return $output;
	}

	public static function fix_empty_logs_values( $infor ) {

		// only body sections in pro reports
		if ( isset( $infor['sections_data'] ) && isset( $infor['sections_data']['body'] ) ) {
			$sections_data     = $infor['sections_data']['body'];
			$other_tokens_data = $infor['other_tokens_data']['body'];

			$fix_section_count = array();
			$fix_sections_data = $sections_data;
			foreach ( $sections_data as $index1 => $sec_logs ) {
				foreach ( $sec_logs as $index2 => $log_records ) {

					$removed_empty = false;
					foreach ( $log_records as $token => $value ) {
						if ( empty( $value ) ) {
							unset( $fix_sections_data[ $index1 ][ $index2 ] );
							$removed_empty = true;
							break;
						}
					}

					if ( $removed_empty ) {
						foreach ( $log_records as $token => $value ) {
							$str_tmp   = str_replace( array( '[', ']' ), '', $token );
							$array_tmp = explode( '.', $str_tmp );

							if ( count( $array_tmp ) == 3 ) { // to able to get .count token
								if ( isset( $fix_section_count[ $token ] ) ) {
									$fix_section_count[ $token ]++;
								} else {
									$fix_section_count[ $token ] = 1;
								}
								break;
							}
						}
					}
				}
			}

			// fix count tokens value
			foreach ( $fix_section_count as $tk => $count ) {
				$str_tmp                         = str_replace( array( '[', ']' ), '', $tk );
				$array_tmp                       = explode( '.', $str_tmp );
				list( $context, $action, $data ) = $array_tmp;
				$count_token                     = '[' . $context . '.' . $action . '.count]';
				if ( isset( $other_tokens_data[ $count_token ] ) && ( $other_tokens_data[ $count_token ] >= $count ) ) {
					$other_tokens_data[ $count_token ] = $other_tokens_data[ $count_token ] - $count; // fix count value
				}
			}
			$infor['other_tokens_data']['body'] = $other_tokens_data;
			$infor['sections_data']['body']     = $fix_sections_data;
		}
		return $infor;
	}

	public static function _callback_replace_sections( $matches ) {
		$start_sec  = $matches[1];
		$index      = self::$count_sec_body;
		$tokens_sec = self::$buffer['sections']['body']['section_content_tokens'][ $index ]; // tokens in sections
		self::$count_sec_body++;
		$sec_content = trim( $matches[2] ); // content of section

		// fetched section data is correct
		if ( isset( self::$buffer['sections_data']['body'][ $index ] ) && ! empty( self::$buffer['sections_data']['body'][ $index ] ) ) {
			$data_rows        = self::$buffer['sections_data']['body'][ $index ];
			$replaced_content = '';
			$count            = 0;
			if ( is_array( $data_rows ) ) {
				foreach ( $data_rows as $tokens_value ) {
					$replaced          = self::replace_section_content( $sec_content, $tokens_sec, $tokens_value );
					$replaced_content .= $replaced;
					$count++;
					if ( self::$raw_sec_body == true ) {
						self::$raw_section_body[ $start_sec ][] = $replaced;
					}
				}
			}

			if ( $count == 0 ) {
				$replaced_content .= '[empty-section-data]';
			}

			return $replaced_content;
		}
		return '[empty-section-data]';
	}

	// to compatible.
	public static function _callback_remove_empty_content( $matches ) {
		$content = $matches[1]; // this is content in [remove-if-empty] section
		if ( ( strpos( $content, '[empty-report-data]' ) !== false ) ) { // to compatible.
			return '';
		}
		return $content;
	}

	public static function _callback_hide_section( $matches ) {
		$content = $matches[1]; // this is content in [config-section-data] section
		if ( strpos( $content, '[hide-section-data]' ) !== false ) {
			return ''; // hide this section
		}
		return '[config-section-data]' . $content . '[/config-section-data]'; // do not remove config tokens at the moment
	}

	public static function _callback_config_section( $matches ) {
		$content = $matches[1]; // this is content in [config-section-data] section
		if ( strpos( $content, '[hide-if-empty]' ) !== false ) {
			if ( strpos( $content, '[empty-section-data]' ) !== false ) {
				return ''; // empty section
			} elseif ( preg_match_all( '/\[config-section-extra[^\]]+\]/is', $content, $matches1 ) ) { // parse extra section config
				$max_empty_data = $matches1[0][0];
				$atts           = shortcode_parse_atts( $max_empty_data );
				$max_empty      = 0;
				if ( is_array( $atts ) && isset( $atts['max-empty'] ) ) {
					$max_empty = intval( $atts['max-empty'] );
				}
				if ( $max_empty ) {
					// if empty report data more that $max_empty then empty section
					if ( preg_match_all( '/\[empty-report-data\]/is', $content, $matches2 ) ) {
						$count_empty = $matches2[0];
						if ( is_array( $count_empty ) && ( count( $count_empty ) >= $max_empty ) ) {
							return ''; // empty section
						}
					}
				}
			}
		}
		return $content;
	}

	public static function replace_section_content( $content, $tokens, $replace_tokens ) {
		$count_empty = false;

		foreach ( $replace_tokens as $token => $value ) {

			if ( strpos( $token, '.count]' ) !== false ) {
				if ( $value == 0 ) {
					$count_empty = true;
				}
			}

			$value   = strip_tags( $value ); // to fix
			$content = str_replace( $token, $value, $content );
		}
		$content = str_replace( $tokens, array(), $content ); // clear others tokens

		if ( $count_empty ) {
			$content .= '[empty-section-data]';
		}

		return $content;
	}

	// not used
	function ajax_delete_client() {
		self::verify_nonce();
		$client_id = $_POST['client_id'];
		if ( $client_id ) {
			if ( MainWP_Pro_Reports_DB::get_instance()->delete_client( 'clientid', $client_id ) ) {
				die( 'SUCCESS' ); }
		}
		die( 'FAILED' );
	}

	// Function: parse report content to find sections and single tokens
	// Params:
	// $content: content of the report
	// $replaceTokensValues: addition tokens and replace values (tokens of extensions, etc ...)
	// Return: sections tokens, other tokens,  filtered content after replaced addition tokens value
	public static function parse_report_content( $content, $replaceTokensValues ) {
		$client_tokens  = array_keys( $replaceTokensValues );
		$replace_values = array_values( $replaceTokensValues );
		// to replace addition tokens value
		$filtered_content = $content = str_replace( $client_tokens, $replace_values, $content );
		$sections         = array();
		if ( preg_match_all( '/(\[section\.[^\]]+\])(.*?)(\[\/section\.[^\]]+\])/is', $content, $matches ) ) {
			for ( $i = 0; $i < count( $matches[1] ); $i++ ) {
				$sec         = $matches[1][ $i ]; // open token of the section
				$sec_content = $matches[2][ $i ]; // content of the section
				$sec_tokens  = array();
				if ( preg_match_all( '/\[[^\]]+\]/is', $sec_content, $matches2 ) ) {
					$sec_tokens = $matches2[0]; // to find token in the section
				}
				// $sections[$sec] = $sec_tokens;
				$sections['section_token'][]          = $sec; // do not remove
				$sections['section_content_tokens'][] = $sec_tokens;
			}
		}
		// remove sections token, to find other tokens in the report content
		$removed_sections = preg_replace_callback( '/(\[section\.[^\]]+\])(.*?)(\[\/section\.[^\]]+\])/is', '__return_empty_string', $content );
		$other_tokens     = array();
		// find other tokens
		if ( preg_match_all( '/\[[^\]]+\]/is', $removed_sections, $matches ) ) {
			$other_tokens = $matches[0];
		}

		// exclude config tokens
		$exclude_tks = array(
			'[config-section-data]',
			'[/config-section-data]',
			'[remove-if-empty]', // compatible.
			'[/remove-if-empty]', // compatible.
			'[hide-if-empty]',
		);

		$other_tokens2 = array();
		foreach ( $other_tokens as $tk ) {
			if ( ! in_array( $tk, $exclude_tks ) ) {
				$other_tokens2[] = $tk;
			}
		}
		return array(
			'sections'         => $sections,
			'other_tokens'     => $other_tokens2,
			'filtered_content' => $filtered_content,
		);
	}

	// to gathering GA tokens values
	static function get_ext_tokens_ga( $site_id, $start_date, $end_date, $chart = false ) {
		// fix bug cron job
		if ( null === self::$enabled_ga ) {
			self::$enabled_ga = is_plugin_active( 'mainwp-google-analytics-extension/mainwp-google-analytics-extension.php' ) ? true : false;
		}

		if ( ! self::$enabled_ga ) {
			return false; }

		// ===============================================================
		// enym new
		// $end_date = strtotime("-1 day", time());
		// $start_date = strtotime( '-31 day', time() ); //31 days is more robust than "1 month" and this must match steprange in MainWPGA.class.php
		// ===============================================================

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false; }
		$uniq = 'ga_' . $site_id . '_' . $start_date . '_' . $end_date;
		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ]; }

		$result = apply_filters( 'mainwp_ga_get_data', $site_id, $start_date, $end_date, $chart );
		$output = array(
			'ga.visits'         => 'N/A',
			'ga.pageviews'      => 'N/A',
			'ga.pages.visit'    => 'N/A',
			'ga.bounce.rate'    => 'N/A',
			'ga.new.visits'     => 'N/A',
			'ga.avg.time'       => 'N/A',
			'ga.visits.chart'   => 'N/A', // enym new
			'ga.visits.maximum' => 'N/A', // enym new
			'ga.users'   => 'N/A', // enym new
			'ga.new.users' => 'N/A', // enym new
			'ga.startdate' => 'N/A',
			'ga.enddate' => 'N/A',
		);

		if ( ! empty( $result ) && is_array( $result ) ) {
			$custom_date_format = apply_filters( 'mainwp-ga-chart-custom-date', false );

			if ( isset( $result['stats_int'] ) ) {
				$values                   = $result['stats_int'];
				$output['ga.visits']      = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:sessions'] ) ) ? $values['aggregates']['ga:sessions'] : 'N/A';
				$output['ga.pageviews']   = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:pageviews'] ) ) ? $values['aggregates']['ga:pageviews'] : 'N/A';
				$output['ga.pages.visit'] = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:pageviewsPerSession'] ) ) ? self::format_stats_values( $values['aggregates']['ga:pageviewsPerSession'], true, false ) : 'N/A';
				$output['ga.bounce.rate'] = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:bounceRate'] ) ) ? self::format_stats_values( $values['aggregates']['ga:bounceRate'], true, true ) : 'N/A';
				$output['ga.new.visits']  = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:percentNewSessions'] ) ) ? self::format_stats_values( $values['aggregates']['ga:percentNewSessions'], true, true ) : 'N/A';
				$output['ga.avg.time']    = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:avgSessionDuration'] ) ) ? self::format_stats_values( $values['aggregates']['ga:avgSessionDuration'], false, false, true ) : 'N/A';
				$output['ga.users']  = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:Users'] ) ) ? $values['aggregates']['ga:Users'] : 'N/A';
				$output['ga.new.users']    = ( isset( $values['aggregates'] ) && isset( $values['aggregates']['ga:newUsers'] ) ) ? $values['aggregates']['ga:newUsers'] : 'N/A';
			}

			// ===============================================================
			// enym new   requires change in mainWPGA.class.php in Ga extension [send pure graph data in array]
			// help: http://charts.streitenberger.net/#
			// if (isset($result['stats_graph'])) {
			if ( $chart && isset( $result['stats_graphdata'] ) ) {
				// INTERVALL chxr=1,1,COUNTALLVALUES
				$intervalls         = '1,1,' . count( $result['stats_graphdata'] );
				$maximum_value      = 0;
				$maximum_value_date = '';
				// MAX DIMENSIONS chds=0,HIGHEST*2
				foreach ( $result['stats_graphdata'] as $k => $v ) {
					if ( $v['1'] > $maximum_value ) {
						$maximum_value      = $v['1'];
						$maximum_value_date = $v['0'];
					}
				}

				$vertical_max = ceil( $maximum_value * 1.3 );
				$dimensions   = '0,' . $vertical_max;

				// DATA chd=t:1,2,3,4,5,6,7,8,9,10,11,12,13,14|
				$graph_values = '';
				foreach ( $result['stats_graphdata'] as $arr ) {
					$graph_values .= $arr['1'] . ',';
				}
				$graph_values = trim( $graph_values, ',' );

				// AXISLEGEND chd=t:1.1|2.1|3.1 ...
				$graph_dates = '';

				$step = 1;
				if ( count( $result['stats_graphdata'] ) > 20 ) {
					$step = 2;
				}
				$nro = 1;
				foreach ( $result['stats_graphdata'] as $arr ) {
					$nro = $nro + 1;
					if ( 0 == ( $nro % $step ) ) {

						$teile = explode( ' ', $arr['0'] );
						if ( 'Jan' == $teile[0] ) {
							$teile[0] = '1'; }
						if ( 'Feb' == $teile[0] ) {
							$teile[0] = '2'; }
						if ( 'Mar' == $teile[0] ) {
							$teile[0] = '3'; }
						if ( 'Apr' == $teile[0] ) {
							$teile[0] = '4'; }
						if ( 'May' == $teile[0] ) {
							$teile[0] = '5'; }
						if ( 'Jun' == $teile[0] ) {
							$teile[0] = '6'; }
						if ( 'Jul' == $teile[0] ) {
							$teile[0] = '7'; }
						if ( 'Aug' == $teile[0] ) {
							$teile[0] = '8'; }
						if ( 'Sep' == $teile[0] ) {
							$teile[0] = '9'; }
						if ( 'Oct' == $teile[0] ) {
							$teile[0] = '10'; }
						if ( 'Nov' == $teile[0] ) {
							$teile[0] = '11'; }
						if ( 'Dec' == $teile[0] ) {
							$teile[0] = '12'; }

						$format_date = '';

						if ( ! $custom_date_format ) {
							if ( isset( $arr[2] ) ) { // formated date by hook (filter)
								// $graph_dates .= $arr[2] . '|';  // default mainwp GA chart date format
								$format_date = $arr[2] . '|'; // default mainwp GA chart date format
							} else {
								// $graph_dates .= $teile[1] . '.' . $teile[0] . '.|';  // default mainwp GA chart date format
								$format_date = $teile[1] . '.' . $teile[0] . '.|';  // default mainwp GA chart date format
							}
						} else {
							// $graph_dates .= $teile[0] . '/' . $teile[1] . '.|';
							$format_date = $teile[0] . '/' . $teile[1] . '.|';
						}

						$format_date  = apply_filters( 'mainwp-reports-ga-chart-format-date', $format_date, $teile[0], $teile[1] );
						$graph_dates .= $format_date;
					}
				}

				$graph_dates = trim( $graph_dates, '|' );

				// SCALE chxr=1,0,HIGHEST*2
				$scale = '1,0,' . $vertical_max;

				// WIREFRAME chg=0,10,1,4
				$wire = '0,10,1,4';

				// COLORS
				$barcolor  = '508DDE'; // 4d89f9";
				$fillcolor = 'EDF5FF'; // CCFFFF";
				// LINEFORMAT chls=1,0,0
				$lineformat = '1,0,0';

				// TITLE
				// &chtt=Last+2+Weeks+Sales
				// LEGEND
				// &chdl=Sales

				$output['ga.visits.chart'] = '<img src="https://chart.apis.google.com/chart?cht=lc&chs=600x250&chd=t:' . $graph_values . '&chds=' . $dimensions . '&chco=' . $barcolor . '&chm=B,' . $fillcolor . ',0,0,0&chls=' . $lineformat . '&chxt=x,y&chxl=0:|' . $graph_dates . '&chxr=' . $scale . '&chg=' . $wire . '">';

				$date1 = explode( ' ', $maximum_value_date );
				if ( 'Jan' == $date1[0] ) {
					$date1[0] = '1'; }
				if ( 'Feb' == $date1[0] ) {
					$date1[0] = '2'; }
				if ( 'Mar' == $date1[0] ) {
					$date1[0] = '3'; }
				if ( 'Apr' == $date1[0] ) {
					$date1[0] = '4'; }
				if ( 'May' == $date1[0] ) {
					$date1[0] = '5'; }
				if ( 'Jun' == $date1[0] ) {
					$date1[0] = '6'; }
				if ( 'Jul' == $date1[0] ) {
					$date1[0] = '7'; }
				if ( 'Aug' == $date1[0] ) {
					$date1[0] = '8'; }
				if ( 'Sep' == $date1[0] ) {
					$date1[0] = '9'; }
				if ( 'Oct' == $date1[0] ) {
					$date1[0] = '10'; }
				if ( 'Nov' == $date1[0] ) {
					$date1[0] = '11'; }
				if ( 'Dec' == $date1[0] ) {
					$date1[0] = '12'; }

				$display_maximum_value_date = apply_filters( 'mainwp_client_reports_ga_visits_maximum_date', false, $date1[1], $date1[0] ); // day.month
				if ( empty( $display_maximum_value_date ) ) {
					$display_maximum_value_date = $date1[1] . '.' . $date1[0] . '.'; // day.month
				}

				// $maximum_value_date = $date1[1] . '.' . $date1[0] . '.'; // day.month
				$output['ga.visits.maximum'] = $maximum_value . ' (' . $display_maximum_value_date . ')';
			}

			$output['ga.startdate'] = MainWP_Pro_Reports_Utility::format_datestamp( $start_date, true );
			$output['ga.enddate']   = MainWP_Pro_Reports_Utility::format_datestamp( $end_date, true );
			// }
			// enym end
			// ===============================================================
		}
		self::$buffer[ $uniq ] = $output;
		return $output;
	}

	static function get_ext_tokens_piwik( $site_id, $start_date, $end_date ) {
		// fix bug cron job
		if ( null === self::$enabled_piwik ) {
			self::$enabled_piwik = is_plugin_active( 'mainwp-piwik-extension/mainwp-piwik-extension.php' );
		}

		if ( ! self::$enabled_piwik ) {
			return false;
		}

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'pw_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ]; }

		$values = apply_filters( 'mainwp_piwik_get_data', $site_id, $start_date, $end_date );

		$output                      = array();
		$output['piwik.visits']      = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['nb_visits'] ) ) ? $values['aggregates']['nb_visits'] : 'N/A';
		$output['piwik.pageviews']   = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['nb_actions'] ) ) ? $values['aggregates']['nb_actions'] : 'N/A';
		$output['piwik.pages.visit'] = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['nb_actions_per_visit'] ) ) ? $values['aggregates']['nb_actions_per_visit'] : 'N/A';
		$output['piwik.bounce.rate'] = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['bounce_rate'] ) ) ? $values['aggregates']['bounce_rate'] : 'N/A';
		$output['piwik.new.visits']  = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['nb_uniq_visitors'] ) ) ? $values['aggregates']['nb_uniq_visitors'] : 'N/A';
		$output['piwik.avg.time']    = ( is_array( $values ) && isset( $values['aggregates'] ) && isset( $values['aggregates']['avg_time_on_site'] ) ) ? self::format_stats_values( $values['aggregates']['avg_time_on_site'], false, false, true ) : 'N/A';
		self::$buffer[ $uniq ]       = $output;

		return $output;
	}

	static function get_ext_tokens_aum( $site_id, $start_date, $end_date ) {

		if ( null === self::$enabled_aum ) {
			self::$enabled_aum = is_plugin_active( 'advanced-uptime-monitor-extension/advanced-uptime-monitor-extension.php' ) ? true : false;
		}

		if ( ! self::$enabled_aum ) {
			return false; }

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false; }
		$uniq = 'aum_' . $site_id . '_' . $start_date . '_' . $end_date;
		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ]; }

		$values = apply_filters( 'mainwp_aum_get_data', $site_id, $start_date, $end_date );
		// print_r($values);
		$output                           = array();
		$output['aum.alltimeuptimeratio'] = ( is_array( $values ) && isset( $values['aum.alltimeuptimeratio'] ) ) ? $values['aum.alltimeuptimeratio'] . '%' : 'N/A';
		$output['aum.uptime7']            = ( is_array( $values ) && isset( $values['aum.uptime7'] ) ) ? $values['aum.uptime7'] . '%' : 'N/A';
		$output['aum.uptime15']           = ( is_array( $values ) && isset( $values['aum.uptime15'] ) ) ? $values['aum.uptime15'] . '%' : 'N/A';
		$output['aum.uptime30']           = ( is_array( $values ) && isset( $values['aum.uptime30'] ) ) ? $values['aum.uptime30'] . '%' : 'N/A';
		$output['aum.uptime45']           = ( is_array( $values ) && isset( $values['aum.uptime45'] ) ) ? $values['aum.uptime45'] . '%' : 'N/A';
		$output['aum.uptime60']           = ( is_array( $values ) && isset( $values['aum.uptime60'] ) ) ? $values['aum.uptime60'] . '%' : 'N/A';
		$output['aum.stats']              = ( is_array( $values ) && isset( $values['aum.stats'] ) ) ? $values['aum.stats'] : 'N/A';

		self::$buffer[ $uniq ] = $output;

		return $output;
	}

	static function get_ext_tokens_woocomstatus( $site_id, $start_date, $end_date ) {

		// fix bug cron job
		if ( null === self::$enabled_woocomstatus ) {
			self::$enabled_woocomstatus = is_plugin_active( 'mainwp-woocommerce-status-extension/mainwp-woocommerce-status-extension.php' ) ? true : false;
		}

		if ( ! self::$enabled_woocomstatus ) {
			return false;
		}

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false; }
		$uniq = 'wcstatus_' . $site_id . '_' . $start_date . '_' . $end_date;
		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ]; }

		$values     = apply_filters( 'mainwp_woocomstatus_get_data', $site_id, $start_date, $end_date );
		$top_seller = 'N/A';
		if ( is_array( $values ) && isset( $values['wcomstatus.topseller'] ) ) {
			$top = $values['wcomstatus.topseller'];
			if ( is_array( $top ) && isset( $top['name'] ) ) {
				$top_seller = $top['name'];
			}
		}

		// print_r($values);
		$output                                  = array();
		$output['wcomstatus.sales']              = ( is_array( $values ) && isset( $values['wcomstatus.sales'] ) ) ? $values['wcomstatus.sales'] : 'N/A';
		$output['wcomstatus.topseller']          = $top_seller;
		$output['wcomstatus.awaitingprocessing'] = ( is_array( $values ) && isset( $values['wcomstatus.awaitingprocessing'] ) ) ? $values['wcomstatus.awaitingprocessing'] : 'N/A';
		$output['wcomstatus.onhold']             = ( is_array( $values ) && isset( $values['wcomstatus.onhold'] ) ) ? $values['wcomstatus.onhold'] : 'N/A';
		$output['wcomstatus.lowonstock']         = ( is_array( $values ) && isset( $values['wcomstatus.lowonstock'] ) ) ? $values['wcomstatus.lowonstock'] : 'N/A';
		$output['wcomstatus.outofstock']         = ( is_array( $values ) && isset( $values['wcomstatus.outofstock'] ) ) ? $values['wcomstatus.outofstock'] : 'N/A';
		self::$buffer[ $uniq ]                   = $output;
		return $output;
	}

	static function get_ext_tokens_pagespeed( $site_id, $start_date, $end_date ) {

		// fix bug cron job
		if ( null === self::$enabled_pagespeed ) {
			self::$enabled_pagespeed = is_plugin_active( 'mainwp-page-speed-extension/mainwp-page-speed-extension.php' ) ? true : false;
		}

		if ( ! self::$enabled_pagespeed ) {
			return false;
		}

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'pagespeed_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_pagespeed_get_data', array(), $site_id, $start_date, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}
	/**
	 * Virusdie data.
	 *
	 * @param int    $site_id Child site ID.
	 * @param string $start_date Report start date.
	 * @param string $end_date Report end date.
	 *
	 * @return array|false|mixed Return Virusdie data or FALSE on failure.
	 */
	static function get_ext_tokens_virusdie( $site_id, $start_date, $end_date, $sections, $other_tokens ) {

		// Fixes cron job bug.
		if ( null === self::$enabled_virusdie ) {
			self::$enabled_virusdie = is_plugin_active( 'mainwp-virusdie-extension/mainwp-virusdie-extension.php' );
		}

		if ( ! self::$enabled_virusdie ) {
			return false;
		}

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'virusdie_' . $site_id . '_' . $start_date . '_' . $end_date;
		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_virusdie_get_data', array(), $site_id, $start_date, $end_date, $sections, $other_tokens );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}



	static function get_ext_tokens_vulnerable( $site_id, $start_date, $end_date ) {

		// fix bug cron job
		if ( null === self::$enabled_vulnerable ) {
			self::$enabled_vulnerable = is_plugin_active( 'mainwp-vulnerability-checker-extension/mainwp-vulnerability-checker-extension.php' ) ? true : false;
		}

		if ( ! self::$enabled_vulnerable ) {
			return false;
		}

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'vulnerable_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_vulnerable_get_data', array(), $site_id, $start_date, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}

	static function get_ext_tokens_lighthouse( $site_id, $start_date, $end_date ) {

		// fix bug cron job
		if ( null === self::$enabled_lighthouse ) {
			self::$enabled_lighthouse = is_plugin_active( 'mainwp-lighthouse-extension/mainwp-lighthouse-extension.php' ) ? true : false;
		}

		if ( ! self::$enabled_lighthouse ) {
			return false;
		}

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'lighthouse_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_lighthouse_get_data', array(), $site_id, $start_date, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}

	static function get_ext_tokens_domainmonitor( $site_id, $start_date, $end_date ) {

		// fix bug cron job
		if ( null === self::$enabled_domainmonitor ) {
			self::$enabled_domainmonitor = is_plugin_active( 'mainwp-domain-monitor-extension/mainwp-domain-monitor-extension.php' ) ? true : false;
		}

		if ( ! self::$enabled_domainmonitor ) {
			return false;
		}

		if ( ! $site_id || ! $start_date || ! $end_date ) {
			return false;
		}

		$uniq = 'domainmonitor_' . $site_id . '_' . $start_date . '_' . $end_date;

		if ( isset( self::$buffer[ $uniq ] ) ) {
			return self::$buffer[ $uniq ];
		}

		$data                  = apply_filters( 'mainwp_domain_monitor_get_data', array(), $site_id, $start_date, $end_date );
		self::$buffer[ $uniq ] = $data;
		return $data;
	}

	private static function format_stats_values( $value, $round = false, $perc = false, $showAsTime = false ) {
		if ( $showAsTime ) {
			$value = MainWP_Pro_Reports_Utility::sec2hms( $value );
		} else {
			if ( $round ) {
				$value = round( $value, 2 );
			}
			if ( $perc ) {
				$value = $value . '%';
			}
		}
		return $value;
	}

	public static function fetch_remote_data( $website, $sections, $tokens, $date_from, $date_to ) {
		global $mainWPProReportsExtensionActivator;

		$post_data   = array(
			'mwp_action'   => 'get_stream',
			'sections'     => base64_encode( serialize( $sections ) ),
			'other_tokens' => base64_encode( serialize( $tokens ) ),
			'date_from'    => $date_from,
			'date_to'      => $date_to,
		);
		$post_data   = apply_filters( 'mainwp_pro_reports_fetch_remote_post_data', $post_data );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $website['id'], 'client_report', $post_data );

		if ( is_array( $information ) && ! isset( $information['error'] ) ) {
			return $information;
		} else {
			if ( isset( $information['error'] ) ) {
				$error = $information['error'];
				if ( 'NO_CREPORT' == $error ) {
					$error = __( 'Error: No MainWP Client Reports plugin installed.' );
				}
			} else {
				$error = is_array( $information ) ? @implode( '<br>', $information ) : $information;
			}
			return array( 'error' => $error );
		}
	}

	public static function new_report_tab( $report = null ) {
		self::new_report_setting( $report );
	}

	public static function renderClientReportsSiteTokens() {

		$websiteid = isset( $_GET['id'] ) ? $_GET['id'] : null;
		if ( empty( $websiteid ) ) {
			return;
		}

		$tokens      = MainWP_Pro_Reports_DB::get_instance()->get_tokens();
		$site_tokens = MainWP_Pro_Reports_DB::get_instance()->get_site_tokens( $websiteid );
		$html        = '';
		if ( is_array( $tokens ) && count( $tokens ) > 0 ) {
			$html .= '
				<h3 class="ui dividing header">' . __( 'Pro Reports Tokens', 'boilerplate-extension' ) . '</h3>
				<div class="ui form">
			';
			foreach ( $tokens as $token ) {
				if ( ! $token ) {
					continue;
				}
				$token_value = '';
				if ( isset( $site_tokens[ $token->id ] ) && $site_tokens[ $token->id ] ) {
					$token_value = htmlspecialchars( stripslashes( $site_tokens[ $token->id ]->token_value ) );
				}

				$input_name = 'pro_reports_token_' . str_replace( array( '.', ' ', '-' ), '_', $token->token_name );

				$html .= '

				<div class="ui grid field">
					<label class="six wide column middle aligned">[' . stripslashes( $token->token_name ) . ']</label>
					<div class="ui six wide column">
						<div class="ui left labeled input">
							<input type="text" value="' . $token_value . '" class="regular-text" name="' . $input_name . '"/>
						</div>
					</div>
				</div>';
			}
			$html .= '</div>';
		}
		echo $html;
	}

	public function update_site_update_tokens( $websiteId ) {
		global $mainWPProReportsExtensionActivator;

		if ( $websiteId ) {
			$tokens = MainWP_Pro_Reports_DB::get_instance()->get_tokens();
			foreach ( $tokens as $token ) {
					$token_value = '';
					$input_name  = 'pro_reports_token_' . str_replace( array( '.', ' ', '-' ), '_', $token->token_name );
				if ( isset( $_POST[ $input_name ] ) ) {
					$token_value = $_POST[ $input_name ];
				} else { // to compatible
					$input_name = 'creport_token_' . str_replace( array( '.', ' ', '-' ), '_', $token->token_name );
					if ( isset( $_POST[ $input_name ] ) ) {
						$token_value = $_POST[ $input_name ];
					}
				}

				if ( ! empty( $token_value ) ) {
					$current = MainWP_Pro_Reports_DB::get_instance()->get_tokens_by( 'id', $token->id, $websiteId );
					if ( $current ) {
						MainWP_Pro_Reports_DB::get_instance()->update_token_site( $token->id, $token_value, $websiteId );
					} else {
						MainWP_Pro_Reports_DB::get_instance()->add_token_site( $token->id, $token_value, $websiteId );
					}
				}
			}
		}

	}

	public function delete_site_delete_tokens( $website ) {
		if ( $website ) {
			MainWP_Pro_Reports_DB::get_instance()->delete_site_tokens( false, $website->id );
		}
	}

	public function get_sites_with_reports( $websites ) {
		$sites = array();

		if ( is_array( $websites ) && count( $websites ) ) {
			foreach ( $websites as $website ) {
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( 'mainwp-child-reports/mainwp-child-reports.php' == $plugin['slug'] ) {
								if ( ! $plugin['active'] ) {
									break;
								}
								$site    = MainWP_Pro_Reports_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
								$sites[] = $site;
								break;
							}
						}
					}
				}
			}
		}
		return $sites;
	}

	public static function verify_nonce() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], '_wpnonce_mainwp_pro_reports' ) ) {
			die( json_encode( array( 'error' => __( 'Invalid nonce!', 'mainwp-pro-reports-extension' ) ) ) );
		}
	}

	public static function ajax_load_sites() {

		self::verify_nonce();

		global $mainWPProReportsExtensionActivator;
		$what      = $_POST['what'];
		$report_id = $_POST['report_id'];
		$websites  = array();

		if ( $report_id ) {
			$report = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );
			if ( $report ) {
				$sel_sites  = unserialize( base64_decode( $report->sites ) );
				$sel_groups = unserialize( base64_decode( $report->groups ) );
				if ( ! is_array( $sel_sites ) ) {
					$sel_sites = array(); }
				if ( ! is_array( $sel_groups ) ) {
					$sel_groups = array(); }
				$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $sel_sites, $sel_groups );
				if ( is_array( $dbwebsites ) ) {
					foreach ( $dbwebsites as $site ) {
						$websites[] = MainWP_Pro_Reports_Utility::map_site( $site, array( 'id', 'name', 'url' ) );
					}
				}
			}
		}

		$error = '';
		if ( empty( $report ) ) {
			$error = __( 'Report could not be found.', 'mainwp-reports-extension' );
		} elseif ( count( $websites ) == 0 ) {
			$error = __( 'There are no selected sites for the report. Please select your site(s) first.', 'mainwp-reports-extension' );
		}

		$html = '';

		if ( empty( $error ) ) {
			ob_start();
			?>
			<div class="ui relaxed divided list">
				<?php foreach ( $websites as $website ) : ?>
					<div class="item">
						<?php echo $website['name']; ?>
						<span class="siteItemProcess right floated" action="" site-id="<?php echo $website['id']; ?>" status="queue">
						<span class="status"><i class="clock outline icon"></i></span> <i style="display: none;" class="notched circle loading icon"></i></span>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
			$html = ob_get_clean();
		}

		if ( ! empty( $error ) ) {
			$error = '<div class="ui yellow message">' . $error . '</div>';
			die( $error );
		}

		die( $html );
	}

	public function ajax_generate_report_content() {

		self::verify_nonce();

		$report_id = $_POST['report_id'];
		$site_id   = $_POST['site_id'];
		$what      = $_POST['what'];

		if ( empty( $site_id ) || empty( $report_id ) ) {
			die( json_encode( array( 'error' => __( 'Invalid data.' ) ) ) );
		}

		$report = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );

		if ( empty( $report ) ) { // is not group report
			die( json_encode( array( 'error' => __( 'Report could not be found.', 'mainwp-reports-extension' ) ) ) );
		}

		global $mainWPProReportsExtensionActivator;

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), array( $site_id ), array() );

		$site = array();

		if ( is_array( $dbwebsites ) ) {

			$site           = current( $dbwebsites );
			$site           = MainWP_Pro_Reports_Utility::map_site( $site, array( 'id', 'name', 'url' ) );
			$cust_from_date = $cust_to_date = 0;

			if ( $what == 'preview' && $report->scheduled ) {

				$preview_recurring = self::calc_recurring_date( $report->recurring_schedule, $report->recurring_day ); // to preview, do not need to pass offset data time

				if ( is_array( $preview_recurring ) ) {
					if ( 'daily' == $report->recurring_schedule ) {
						$cust_from_date = $preview_recurring['date_from'] - 24 * 3600;
						$cust_to_date   = $preview_recurring['date_to'] - 24 * 3600;
					} elseif ( 'weekly' == $report->recurring_schedule ) {
						  $cust_from_date = $preview_recurring['date_from'] - 7 * 24 * 3600;
						  $cust_to_date   = $preview_recurring['date_to'] - 7 * 24 * 3600;
					} elseif ( 'monthly' == $report->recurring_schedule ) {
						$cust_from_date = strtotime( 'first day of last month' );
						$cust_from_date = strtotime( date( 'Y-m-d', $cust_from_date ) . ' 00:00:00' );
						$cust_to_date   = strtotime( 'last day of last month' );
						$cust_to_date   = strtotime( date( 'Y-m-d', $cust_to_date ) . ' 23:59:59' );
					}
				}
			}

			if ( self::update_pro_report_site( $report, $site, $cust_from_date, $cust_to_date ) ) {
				// to reload updated data.
				$report = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );
				if ( $what == 'send_test' ) {
					$data = self::prepare_content_report_email( $report, true, $site );
					if ( ! $data || ! $this->send_onetime_report_email( $data, $report, true, $site ) ) {
						die( json_encode( array( 'error' => __( 'Undefined error. Email could not be sent.', 'mainwp-pro-reports-extension' ) ) ) );
					}
				} elseif ( $what == 'send' ) {
					$data = self::prepare_content_report_email( $report, false, $site );
					if ( ! $data || ! $this->send_onetime_report_email( $data, $report, false, $site ) ) {
							  die( json_encode( array( 'error' => __( 'Undefined error. Email could not be sent.', 'mainwp-pro-reports-extension' ) ) ) );
					}
				}
				die( json_encode( array( 'result' => 'success' ) ) );
			} else {
				die( json_encode( array( 'error' => __( 'Data saved successfully.', 'mainwp-pro-reports-extension' ) ) ) );
			}
		}
		die( json_encode( array( 'error' => __( 'Invalid Site ID.', 'mainwp-pro-reports-extension' ) ) ) );
	}

	public static function ajax_email_message_preview() {

		self::verify_nonce();

		$site_id = isset( $_POST['site_id'] ) ? intval( $_POST['site_id'] ) : 0;
		$subject = $_POST['subject'];
		$message = $_POST['message'];

		if ( empty( $site_id ) ) {
			// get random site id
			global $mainWPProReportsExtensionActivator;
			$websites  = apply_filters( 'mainwp_getsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), null );
			$sites_ids = array();
			if ( is_array( $websites ) ) {
				foreach ( $websites as $website ) {
					$sites_ids[] = $website['id'];
				}
			}
			$site_id = $sites_ids[ array_rand( $sites_ids ) ];
		}

		// find and replace tokens values
		if ( $site_id && ( self::has_tokens( $subject ) || self::has_tokens( $message ) ) ) {
			$sites_token = MainWP_Pro_Reports_DB::get_instance()->get_site_tokens( $site_id, 'token_name' );
			if ( ! is_array( $sites_token ) ) {
				$sites_token = array();
			}

			$search_token = $replace_value = array();
			// to support report tokens
			// $search_token[] = '[report.daterange]';
			// $replace_value[] = MainWP_Pro_Reports_Utility::format_timestamp( $report->date_from ) . ' - ' . MainWP_Pro_Reports_Utility::format_timestamp( $report->date_to );
			$search_token[]  = '[report.send.date]';
			$now             = time();
			$replace_value[] = MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $now ) );

			foreach ( $sites_token as $token_name => $token ) {
				$search_token[]  = '[' . $token_name . ']';
				$replace_value[] = $token->token_value;
			}

			if ( self::has_tokens( $subject ) ) {
				$subject = str_replace( $search_token, $replace_value, $subject );
			}
			if ( self::has_tokens( $message ) ) {
				$message = str_replace( $search_token, $replace_value, $message );
			}
		}

		$message = nl2br( $message ); // to fix
		wp_send_json(
			array(
				'subject' => $subject,
				'message' => $message,
			)
		);
	}

	public static function update_pro_report_site( $report, $site, $cust_from_date = 0, $cust_to_date = 0 ) {
		if ( empty( $site ) || ! is_array( $site ) ) {
			return false;
		}
		$site_id = $site['id'];

		// fix bug
		if ( empty( $site_id ) ) {
			return false;
		}

		$option = array(
			'plugins' => true,
		);

		$enable_woocom = false;
		global $mainWPProReportsExtensionActivator;
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), array( $site_id ), array(), $option );
		if ( $dbwebsites ) {
			$website = current( $dbwebsites );
			$plugins = json_decode( $website->plugins, 1 );
			if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
				foreach ( $plugins as $plugin ) {
					if ( 'woocommerce/woocommerce.php' == $plugin['slug'] ) {
						if ( $plugin['active'] ) {
							$enable_woocom = true;
						}
						break;
					}
				}
			}
		}

		$templ_content = MainWP_Pro_Reports_Template::get_instance()->get_template_file_content( $report, $site, $enable_woocom );

		$filtered_reports = self::filter_report_website( $templ_content, $report, $site, $cust_from_date, $cust_to_date );

		$content     = self::gen_report_content( $filtered_reports );
		$content_pdf = self::gen_report_content_pdf( array( $site_id => $filtered_reports ) );

		$values = array(
			'report_id'          => $report->id,
			'site_id'            => $site_id,
			'report_content'     => json_encode( $content ),
			'report_content_pdf' => json_encode( $content_pdf ),
		);

		if ( MainWP_Pro_Reports_DB::get_instance()->update_pro_report_generated_content( $values ) ) {
			return true;
		} else {
			return false;
		}
	}

	// Delete Custom Tokens
	public function delete_token() {
		self::verify_nonce();
		$ret      = array( 'success' => false );
		$token_id = intval( $_POST['token_id'] );
		if ( MainWP_Pro_Reports_DB::get_instance()->delete_token_by( 'id', $token_id ) ) {
			$ret['success'] = true;
		}
		echo json_encode( $ret );
		exit;
	}

	// Save Custom Tokens
	public function save_token() {

		$return            = array(
			'success' => false,
			'error'   => '',
			'message' => '',
		);
		$token_name        = sanitize_text_field( $_POST['token_name'] );
		$token_name        = trim( $token_name, '[]' );
		$token_description = sanitize_text_field( $_POST['token_description'] );

		// update
		if ( isset( $_POST['token_id'] ) && $token_id = intval( $_POST['token_id'] ) ) {
			$current = MainWP_Pro_Reports_DB::get_instance()->get_tokens_by( 'id', $token_id );
			if ( $current && $current->token_name == $token_name && $current->token_description == $token_description ) {
				$return['success'] = true;
				$return['message'] = __( 'Token has been saved without changes.', 'mainwp-pro-reports-extension' );
			} elseif ( ( $current = MainWP_Pro_Reports_DB::get_instance()->get_tokens_by( 'token_name', $token_name ) ) && $current->id != $token_id ) {
				$return['error'] = __( 'Token already exists, try different token name.', 'mainwp-pro-reports-extension' );
			} elseif ( $token = MainWP_Pro_Reports_DB::get_instance()->update_token(
				$token_id,
				array(
					'token_name'        => $token_name,
					'token_description' => $token_description,
				)
			) ) {
				$return['success'] = true;
			}
		} else { // add new
			if ( $current = MainWP_Pro_Reports_DB::get_instance()->get_tokens_by( 'token_name', $token_name ) ) {
				$return['error'] = __( 'Token already exists, try different token name.', 'mainwp-pro-reports-extension' );
			} else {
				if ( $token = MainWP_Pro_Reports_DB::get_instance()->add_token(
					array(
						'token_name'        => $token_name,
						'token_description' => $token_description,
						'type'              => 0,
					)
				) ) {
					$return['success'] = true;
				} else {
					$return['error'] = __( 'Undefined error occurred. Please try again.', 'mainwp-pro-reports-extension' ); }
			}
		}
		echo json_encode( $return );
		exit;
	}

	public function ajax_do_action_report() {

		self::verify_nonce();

		$report_id = intval( $_POST['reportId'] );
		$action    = $_POST['what'];

		if ( empty( $report_id ) ) {
			die( json_encode( array( 'error' => __( 'Invalid report ID. Please, try again.', 'mainwp-pro-reports-extension' ) ) ) );
		}

		$ret     = array();
		$success = false;
		switch ( $action ) {
			case 'delete':
				if ( MainWP_Pro_Reports_DB::get_instance()->delete_report_by( 'id', $report_id ) ) {
					$success = true;
				}
				break;
			default:
				break;
		}

		if ( $success ) {
			$ret['status'] = 'success';
		}

		echo json_encode( $ret );
		exit;
	}

}
