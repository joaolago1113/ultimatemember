<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\admin\Users_Columns' ) ) {


	/**
	 * Class Users_Columns
	 *
	 * @package um\admin
	 */
	class Users_Columns {

		/**
		 * Users_Columns constructor.
		 */
		public function __construct() {
			add_filter( 'views_users', array( &$this, 'add_status_links' ) );
			add_filter( 'bulk_actions-users', array( &$this, 'add_bulk_actions' ), 10, 1 );
			add_filter( 'handle_bulk_actions-users', array( &$this, 'handle_bulk_actions' ), 10, 3 );
			add_action( 'manage_users_extra_tablenav', array( &$this, 'filter_by_status_action' ), 10, 1 );

			add_filter( 'user_row_actions', array( &$this, 'user_row_actions' ), 10, 2 );

			add_filter( 'users_list_table_query_args', array( &$this, 'hide_by_caps' ), 1, 1 );

			add_action( 'pre_user_query', array( &$this, 'sort_by_newest' ), 10, 1 );

			add_action( 'pre_user_query', array( &$this, 'filter_users_by_status' ), 10, 1 );

			add_action( 'admin_init', array( &$this, 'um_bulk_users_edit' ), 9 );

			add_action( 'um_admin_user_action_hook', array( &$this, 'user_action_hook' ), 10, 1 );
		}

		/**
		 * Add status links to WP Users List Table
		 *
		 * @param $views
		 * @return array
		 */
		public function add_status_links( $views ) {
			remove_action( 'pre_user_query', array( &$this, 'filter_users_by_status' ), 10 );

			$old_views = $views;
			$views     = array();

			if ( ! isset( $_REQUEST['role'] ) && ! isset( $_REQUEST['um_status'] ) ) {
				$views['all'] = '<a href="' . admin_url( 'users.php' ) . '" class="current">' . __( 'All', 'ultimate-member' ) . ' <span class="count">(' . UM()->query()->count_users() . ')</span></a>';
			} else {
				$views['all'] = '<a href="' . admin_url( 'users.php' ) . '">' . __( 'All', 'ultimate-member' ) . ' <span class="count">(' . UM()->query()->count_users() . ')</span></a>';
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_views_users
			 * @description Admin views array
			 * @input_vars
			 * [{"var":"$views","type":"array","desc":"User Views"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_views_users', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_views_users', 'my_admin_views_users', 10, 1 );
			 * function my_admin_views_users( $views ) {
			 *     // your code here
			 *     return $views;
			 * }
			 * ?>
			 */
			$views = apply_filters( 'um_admin_views_users', $views );

			// remove all filters
			unset( $old_views['all'] );

			// add separator
			$views['subsep'] = '<span></span>';

			// merge views
			foreach ( $old_views as $key => $view ) {
				$views[ $key ] = $view;
			}

			// hide filters with not accessible roles
			if ( ! current_user_can( 'administrator' ) ) {
				$wp_roles       = wp_roles();
				$can_view_roles = um_user( 'can_view_roles' );
				if ( ! empty( $can_view_roles ) ) {
					foreach ( $wp_roles->get_names() as $this_role => $name ) {
						if ( ! in_array( $this_role, $can_view_roles, true ) ) {
							unset( $views[ $this_role ] );
						}
					}
				}
			}

			return $views;
		}

		/**
		 * @return array
		 */
		public function get_user_bulk_actions() {
			$um_actions = apply_filters(
				'um_admin_bulk_user_actions_hook',
				array(
					'um_approve_membership' => __( 'Approve Membership', 'ultimate-member' ),
					'um_reject_membership'  => __( 'Reject Membership', 'ultimate-member' ),
					'um_put_as_pending'     => __( 'Put as Pending Review', 'ultimate-member' ),
					'um_resend_activation'  => __( 'Resend Activation E-mail', 'ultimate-member' ),
					'um_deactivate'         => __( 'Deactivate', 'ultimate-member' ),
					'um_reenable'           => __( 'Reactivate', 'ultimate-member' ),
				)
			);

			return $um_actions;
		}

		/**
		 * @return array
		 */
		public function get_user_statuses() {
			$statuses = apply_filters(
				'um_admin_get_user_statuses',
				array(
					'approved'                    => __( 'Approved', 'ultimate-member' ),
					'awaiting_admin_review'       => __( 'Pending review', 'ultimate-member' ),
					'awaiting_email_confirmation' => __( 'Waiting e-mail confirmation', 'ultimate-member' ),
					'inactive'                    => __( 'Inactive', 'ultimate-member' ),
					'rejected'                    => __( 'Rejected', 'ultimate-member' ),
				)
			);

			return $statuses;
		}

		/**
		 * @param array $actions
		 *
		 * @return array
		 */
		public function add_bulk_actions( $actions ) {
			$actions[ esc_html__( 'Ultimate Member', 'ultimate-member' ) ] = $this->get_user_bulk_actions();
			return $actions;
		}

		/**
		 * @param string $sendback
		 * @param string $current_action
		 * @param array $userids
		 *
		 * @return string
		 */
		public function handle_bulk_actions( $sendback, $current_action, $userids ) {
			$um_actions = $this->get_user_bulk_actions();
			if ( ! in_array( $current_action, $um_actions, true) ) {
				return $sendback;
			}

			switch ( $current_action ) {
				case 'um_approve_membership':
					break;
				case 'um_reject_membership':
					break;
				case 'um_put_as_pending':
					break;
				case 'um_resend_activation':
					break;
				case 'um_deactivate':
					break;
				case 'um_reenable':
					break;
				default:
					// hook for the handling custom UM actions added via 'um_admin_bulk_user_actions_hook' hook
					$sendback = apply_filters( "um_handle_bulk_actions-users-{$current_action}", $sendback, $userids );
					break;
			}

			return $sendback;
		}

		/**
		 * @param $which
		 */
		public function filter_by_status_action( $which ) {
			if ( 'bottom' === $which ) {
				return;
			}

			remove_action( 'pre_user_query', array( &$this, 'filter_users_by_status' ), 10 );

			$statuses = $this->get_user_statuses();
			?>
			<div class="alignleft actions">
				<label class="screen-reader-text" for="um_status"><?php _e( 'Filter by status', 'ultimate-member' ); ?></label>
				<select name="um_status" id="um_status">
					<option value=""><?php _e( 'Filter by status', 'ultimate-member' ); ?></option>
					<?php foreach ( $statuses as $k => $v ) { ?>
						<option value="<?php esc_attr( $v ) ?>" <?php selected( isset( $_GET['um_status'] ) && sanitize_key( $_GET['um_status'] ) === $k ) ?>><?php echo esc_html( $v . ' (' . UM()->query()->count_users_by_status( $k ) . ')' ); ?></option>
					<?php } ?>
				</select>
				<?php submit_button( __( 'Filter', 'ultimate-member' ), '', 'um-user-query-submit', false ); ?>
			</div>
			<?php
		}

		/**
		 * Does an action to user asap
		 *
		 * @param string $action
		 */
		function user_action_hook( $action ) {
			switch ( $action ) {
				default:
					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_admin_custom_hook_{$action}
					 * @description Integration hook on user action
					 * @input_vars
					 * [{"var":"$user_id","type":"int","desc":"User ID"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_admin_custom_hook_{$action}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_admin_custom_hook_{$action}', 'my_admin_custom_hook', 10, 1 );
					 * function my_admin_after_main_notices( $user_id ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( "um_admin_custom_hook_{$action}", UM()->user()->id );
					break;

				case 'um_put_as_pending':
					UM()->user()->pending();
					break;

				case 'um_approve_membership':
				case 'um_reenable':

					add_filter( 'um_template_tags_patterns_hook', array( UM()->password(), 'add_placeholder' ), 10, 1 );
					add_filter( 'um_template_tags_replaces_hook', array( UM()->password(), 'add_replace_placeholder' ), 10, 1 );

					UM()->user()->approve();
					break;

				case 'um_reject_membership':
					UM()->user()->reject();
					break;

				case 'um_resend_activation':

					add_filter( 'um_template_tags_patterns_hook', array( UM()->user(), 'add_activation_placeholder' ), 10, 1 );
					add_filter( 'um_template_tags_replaces_hook', array( UM()->user(), 'add_activation_replace_placeholder' ), 10, 1 );

					UM()->user()->email_pending();
					break;

				case 'um_deactivate':
					UM()->user()->deactivate();
					break;

				case 'um_delete':
					if ( is_admin() ) {
						wp_die( __( 'This action is not allowed in backend.', 'ultimate-member' ) );
					}
					UM()->user()->delete();
					break;
			}
		}

		/**
		 * Custom row actions for users page
		 *
		 * @param array $actions
		 * @param $user_object \WP_User
		 * @return array
		 */
		function user_row_actions( $actions, $user_object ) {
			$user_id = $user_object->ID;

			$actions['frontend_profile'] = '<a href="' . um_user_profile_url( $user_id ) . '">' . __( 'View profile', 'ultimate-member' ) . '</a>';

			$submitted = get_user_meta( $user_id, 'submitted', true );
			if ( ! empty( $submitted ) ) {
				$actions['view_info'] = '<a href="#" class="um-preview-registration" data-user_id="' . esc_attr( $user_id ) . '">' . esc_html__( 'Info', 'ultimate-member' ) . '</a>';
			}

			if ( ! current_user_can( 'administrator' ) ) {
				if ( ! um_can_view_profile( $user_id ) ) {
					unset( $actions['frontend_profile'] );
					unset( $actions['view_info'] );
					unset( $actions['view'] );
				}
			}

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_user_row_actions
			 * @description Admin views array
			 * @input_vars
			 * [{"var":"$actions","type":"array","desc":"User List Table actions"},
			 * {"var":"$user_id","type":"int","desc":"User ID"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_user_row_actions', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_user_row_actions', 'my_admin_user_row_actions', 10, 2 );
			 * function my_admin_user_row_actions( $actions, $user_id ) {
			 *     // your code here
			 *     return $actions;
			 * }
			 * ?>
			 */
			$actions = apply_filters( 'um_admin_user_row_actions', $actions, $user_id );

			return $actions;
		}

		/**
		 * Change default sorting at WP Users list table
		 *
		 * @param array $args
		 * @return array
		 */
		function hide_by_caps( $args ) {
			if ( ! current_user_can( 'administrator' ) ) {
				$can_view_roles = um_user( 'can_view_roles' );
				if ( um_user( 'can_view_all' ) && ! empty( $can_view_roles ) ) {
					$args['role__in'] = $can_view_roles;
				}
			}

			return $args;
		}

		/**
		 * Change default sorting at WP Users list table
		 *
		 * @param \WP_User_Query $query
		 */
		public function sort_by_newest( $query ) {
			global $pagenow;

			if ( is_admin() && 'users.php' === $pagenow ) {
				if ( ! isset( $_REQUEST['orderby'] ) ) {
					$query->query_vars['order'] = 'desc';
					$query->query_orderby       = ' ORDER BY user_registered ' . ( 'desc' === $query->query_vars['order'] ? 'desc ' : 'asc ' ); //set sort order
				}
			}
		}

		/**
		 * Filter WP users by UM Status
		 *
		 * @param \WP_User_Query $query
		 */
		public function filter_users_by_status( $query ) {
			global $wpdb, $pagenow;

			if ( is_admin() && 'users.php' === $pagenow && ! empty( $_REQUEST['um_status'] ) ) {
				$status = sanitize_key( $_REQUEST['um_status'] );

				$skip_status_filter = apply_filters( 'um_skip_filter_users_by_status', false, $status );
				if ( ! $skip_status_filter ) {
					$query->query_where = str_replace('WHERE 1=1',
						"WHERE 1=1 AND {$wpdb->users}.ID IN (
                                 SELECT {$wpdb->usermeta}.user_id FROM $wpdb->usermeta
                                    WHERE {$wpdb->usermeta}.meta_key = 'account_status'
                                    AND {$wpdb->usermeta}.meta_value = '{$status}')",
						$query->query_where
					);
				}
			}
		}

		/**
		 * Bulk user editing actions
		 */
		public function um_bulk_users_edit() {
			// bulk edit users
			if ( ! empty( $_REQUEST['users'] ) && ! empty( $_REQUEST['um_bulkedit'] ) && ! empty( $_REQUEST['um_bulk_action'] ) ) {

				$rolename = UM()->roles()->get_priority_user_role( get_current_user_id() );
				$role     = get_role( $rolename );

				if ( ! current_user_can( 'edit_users' ) && ! $role->has_cap( 'edit_users' ) ) {
					wp_die( esc_html__( 'You do not have enough permissions to do that.', 'ultimate-member' ) );
				}

				check_admin_referer( 'bulk-users' );

				$users       = array_map( 'absint', (array) $_REQUEST['users'] );
				$bulk_action = current( array_filter( $_REQUEST['um_bulk_action'] ) );

				foreach ( $users as $user_id ) {
					UM()->user()->set( $user_id );

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_admin_user_action_hook
					 * @description Action on bulk user action
					 * @input_vars
					 * [{"var":"$bulk_action","type":"string","desc":"Bulk Action"}]
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_admin_user_action_hook{$action}', 'function_name', 10, 1 );
					 * @example
					 * <?php
					 * add_action( 'um_admin_user_action_hook', 'my_admin_user_action', 10, 1 );
					 * function my_admin_user_action( $bulk_action ) {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( 'um_admin_user_action_hook', $bulk_action );

					/**
					 * UM hook
					 *
					 * @type action
					 * @title um_admin_user_action_{$bulk_action}_hook
					 * @description Action on bulk user action
					 * @change_log
					 * ["Since: 2.0"]
					 * @usage add_action( 'um_admin_user_action_{$bulk_action}_hook', 'function_name', 10 );
					 * @example
					 * <?php
					 * add_action( 'um_admin_user_action_{$bulk_action}_hook', 'my_admin_user_action', 10 );
					 * function my_admin_user_action() {
					 *     // your code here
					 * }
					 * ?>
					 */
					do_action( "um_admin_user_action_{$bulk_action}_hook" );
				}

				// Finished. redirect now
				//if ( $admin_err == 0 ) {

				$uri = $this->set_redirect_uri( admin_url( 'users.php' ) );
				$uri = add_query_arg( 'update', 'users_updated', $uri );

				wp_redirect( $uri );
				exit;

				/*} else {
					wp_redirect( admin_url( 'users.php?update=err_users_updated' ) );
					exit;
				}*/

			} elseif ( ! empty( $_REQUEST['um_bulkedit'] ) ) {

				$uri = $this->set_redirect_uri( admin_url( 'users.php' ) );
				wp_redirect( $uri );
				exit;

			}
		}

		/**
		 * Sets redirect URI after bulk action
		 *
		 * @param string $uri
		 * @return string
		 */
		function set_redirect_uri( $uri ) {
			if ( ! empty( $_REQUEST['s'] ) ) {
				$uri = add_query_arg( 's', sanitize_text_field( $_REQUEST['s'] ), $uri );
			}

			if ( ! empty( $_REQUEST['um_status'] ) ) {
				$uri = add_query_arg( 'um_status', sanitize_key( $_REQUEST['um_status'] ), $uri );
			}

			return $uri;
		}
	}
}
