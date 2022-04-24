<?php

class WooSquare_Modifier {

	public function __construct() {

	}


	private static $edited_modifier_id;


	public static function wsm_output() {

		$result = '';
		$action = '';

		if ( ! empty( $_POST['add_new_modifier'] ) ) {
			$action = 'add';
		} elseif ( ! empty( $_POST['save_modifier'] ) && ! empty( $_GET['edit'] ) ) {
			$action = 'edit';
		} elseif ( ! empty( $_GET['delete'] ) ) {
			$action = 'delete';
		}

		switch ( $action ) {
			case 'add':
				$result = self::wsm_process_add_modifier();
				break;
			case 'edit':
				$result = self::wsm_process_edit_modifier();
				break;
			case 'delete':
				$result = self::wsm_process_delete_modifier();
				break;
		}

		if ( is_wp_error( $result ) ) {
			echo '<div id="woocommerce_errors" class="error"><p>' . wp_kses_post( $result->get_error_message() ) . '</p></div>';
		}

		// Show admin interface.
		if ( ! empty( $_GET['edit'] ) ) {
			self::wsm_edit_modifier();
		} else {
			self::wsm_add_modifier();
		}
	}



	private static function wsm_get_posted_modifier() {
		$modifier = array(
				'modifier_set_name'    => isset( $_POST['modifier_set_name'] ) ? self::wsm_sanitize_taxonomy_name( wp_unslash( $_POST['modifier_set_name'] ) ) : '',
				'modifier_public'  => isset( $_POST['modifier_public'] ) ? 1 : 0,
				'modifier_option'    => !empty($_POST['modifier_option']) ? $_POST['modifier_option'] : 0 ,
		);



		if ( empty( $modifier['modifier_set_name'] ) ) {
			$modifier['modifier_set_name'] = ucfirst( $modifier['modifier_set_name'] );
		}
		if ( empty( $modifier['modifier_public'] ) ) {
			$modifier['modifier_public'] =  $modifier['modifier_public'] ;
		}

		if ( empty( $modifier['modifier_option'] ) ) {


			$modifier['modifier_option'] = $modifier['modifier_option'];
		}
		return $modifier;
	}

	private static function wsm_process_add_modifier() {
		check_admin_referer( 'woocommerce-add-new_modifier' );

		$modifier = self::wsm_get_posted_modifier();
		$args  = array(
				'modifier_set_name'         => $modifier['modifier_set_name'],
				'modifier_public'         => $modifier['modifier_public'],
				'modifier_option'         => $modifier['modifier_option']

		);

		;
		$id = self::wsm_create_modifier( $args );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		return true;
	}

	private static	function wsm_create_modifier( $args ) {


		global $wpdb;

		$args   = wp_unslash( $args );
		$id     = ! empty( $args['modifier_id'] ) ? intval( $args['modifier_id'] ) : 0;
		$format = array( '%s', '%s', '%s', '%s', '%d' );

		// Name is required.
		if ( empty( $args['modifier_set_name'] ) ) {
			return new WP_Error( 'missing_modifier_set_name', __( 'Please, provide an modifier name.', 'woosquare_modifier' ), array( 'status' => 400 ) );
		}

		// Set the attribute slug.
		if ( empty( $args['modifier_set_name'] ) ) {
			$slug = self::wsm_sanitize_taxonomy_name( $args['modifier_set_name'] );
		} else {
			$slug = preg_replace( '/^pm\_/', '', self::wsm_sanitize_taxonomy_name( $args['modifier_set_name'] ) );
		}

		// Validate slug.
		if ( strlen( $slug ) >= 28 ) {
			/* translators: %s: attribute slug */
			return new WP_Error( 'invalid_product_attribute_slug_too_long', sprintf( __( 'Slug "%s" is too long (28 characters max). Shorten it, please.', 'woosquare_modifier' ), $slug ), array( 'status' => 400 ) );
		} elseif ( wc_check_if_attribute_name_is_reserved( $slug ) ) {
			/* translators: %s: attribute slug */
			return new WP_Error( 'invalid_product_modifier_slug_reserved_name', sprintf( __( 'Slug "%s" is not allowed because it is a reserved term. Change it, please.', 'woosquare_modifier' ), $slug ), array( 'status' => 400 ) );
		}// elseif ( ( 0 === $id && taxonomy_exists( self::wsm_modifier_set_name( $slug ) ) ) || ( isset( $args['old_slug'] ) && $args['old_slug'] !== $slug && taxonomy_exists( self::wsm_modifier_set_name( $slug ) ) ) ) {
		/* translators: %s: attribute slug */
		//	return new WP_Error( 'invalid_product_modifier_slug_already_exists', sprintf( __( 'Slug "%s" is already in use. Change it, please.', 'woosquare_modifier' ), $slug ), array( 'status' => 400 ) );
		//	}

		$data = array(
				'modifier_id'    => $args['modifier_id'],
				'modifier_set_name'    => $slug,
				'modifier_public'  => isset( $args['modifier_public'] ) ? (int) $args['modifier_public'] : 0,
				'modifier_option'  => isset( $args['modifier_option']) ? $args['modifier_option'] : 0
		);

		if ( 0 === $id ) {
			$results = $wpdb->insert(
					$wpdb->prefix . 'woosquare_modifier',
					$data,
					$format
			);

			if ( is_wp_error( $results ) ) {
				return new WP_Error( 'cannot_create_modifier', $results->get_error_message(), array( 'status' => 400 ) );
			}

			$id = $wpdb->insert_id;


			do_action( 'woocommerce_modifier_added', $id, $data );
		} else {

			$results = $wpdb->update( $wpdb->prefix . 'woosquare_modifier', $data,array( 'modifier_id' => $id ),$format,array( '%d' ));

			if ( false === $results ) {
				return new WP_Error( 'cannot_update_modifier', __( 'Could not update the modifier.', 'woosquare_modifier' ), array( 'status' => 400 ) );
			}

			$old_modifier_set = ! empty( $args['old_modifier_set'] ) ? self::wsm_sanitize_taxonomy_name( $args['old_modifier_set'] ) : $slug;


			do_action( 'woocommerce_modifier_updated', $id, $data, $old_modifier_set );

			if ( $old_modifier_set !== $slug ) {
				// Update taxonomies in the wp term taxonomy table.
				$wpdb->update(
						$wpdb->term_taxonomy,
						array( 'taxonomy' => wc_attribute_taxonomy_name( $data['modifier_set_name'].$data['modifier_id'] ) ),
						array( 'taxonomy' => 'pm_' . $old_modifier_set )
				);

				// Update taxonomy ordering term meta.
				$wpdb->update(
						$wpdb->termmeta,
						array( 'meta_key' => 'order_pm_' . sanitize_title( $data['modifier_set_name'].$data['modifier_id'] ) ),
						array( 'meta_key' => 'order_pm_' . sanitize_title( $old_modifier_set ) )
				);


				$old_taxonomy_name = 'pm_' . $old_modifier_set;
				$new_taxonomy_name = 'pm_' . $data['modifier_set_name'];
				$old_modifier_key = sanitize_title( $old_taxonomy_name ); // @see WC_Product::set_attributes().
				$new_modifier_key = sanitize_title( $new_taxonomy_name ); // @see WC_Product::set_attributes().
				$metadatas         = $wpdb->get_results(
						$wpdb->prepare(
								"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_product_modifier' AND meta_value LIKE %s",
								'%' . $wpdb->esc_like( $old_modifier_key ) . '%'
						),
						ARRAY_A
				);
				foreach ( $metadatas as $metadata ) {
					$product_id        = $metadata['post_id'];
					$unserialized_data = maybe_unserialize( $metadata['meta_value'] );

					if ( ! $unserialized_data || ! is_array( $unserialized_data ) || ! isset( $unserialized_data[ $old_modifier_key ] ) ) {
						continue;
					}

					$unserialized_data[ $new_modifier_key ] = $unserialized_data[ $old_modifier_key ];
					unset( $unserialized_data[ $old_modifier_key ] );
					$unserialized_data[ $new_modifier_key ]['name'] = $new_taxonomy_name;
					update_post_meta( $product_id, '_product_modifier', wp_slash( $unserialized_data ) );
				}


				$wpdb->update(
						$wpdb->postmeta,
						array( 'meta_key' => 'modifier_pm_' . sanitize_title( $data['modifier_set_name'].$data['modifier_id'] ) ), // WPCS: slow query ok.
						array( 'meta_key' => 'modifier_pm_' . sanitize_title( $old_modifier_set ) ) // WPCS: slow query ok.
				);
			}
		}

		wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
		delete_transient( 'wsm_modifier' );
		WC_Cache_Helper::invalidate_cache_group( 'woosquare-modifier' );
		return $id;
	}

	private static function wsm_process_delete_modifier() {


		$modifier_id = isset( $_GET['delete'] ) ? absint( $_GET['delete'] ) : 0;

		check_admin_referer( 'woocommerce-delete-modifier_'. $modifier_id );



		return self::wsm_delete_modifier( $modifier_id );
	}

	private static function wsm_delete_modifier($id){

		global $wpdb;

		$name = $wpdb->get_var(
				$wpdb->prepare(
						"
			SELECT modifier_set_name
			FROM {$wpdb->prefix}woosquare_modifier
			WHERE modifier_id = %d
			",
						$id
				)
		);

		$taxonomy = self::wsm_modifier_set_name( $name.$id );


		do_action( 'woocommerce_before_modifier_delete', $id, $name, $taxonomy );

		if ( $name && $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woosquare_modifier WHERE modifier_id = %d", $id ) ) ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$terms = get_terms( $taxonomy, 'orderby=name&hide_empty=0' );
				foreach ( $terms as $term ) {
					wp_delete_term( $term->term_id, $taxonomy );
				}
			}

			/**
			 * After deleting an attribute.
			 *
			 * @param int    $id       Attribute ID.
			 * @param string $name     Attribute name.
			 * @param string $taxonomy Attribute taxonomy name.
			 */
			do_action( 'woocommerce_modifier_deleted', $id, $name, $taxonomy );
			wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
			delete_transient( 'wsm_modifier' );
			WC_Cache_Helper::invalidate_cache_group( 'woosquare-modifier' );

			return true;
		}

		return false;

	}

	public static function wsm_get_modifier() {
		$prefix      = WC_Cache_Helper::get_cache_prefix( 'woosquare-modifier' );
		$cache_key   = $prefix . 'modifier';
		$cache_value = wp_cache_get( $cache_key, 'woosquare-modifier' );

		if ( false !== $cache_value ) {
			return $cache_value;
		}

		$raw_modifier = get_transient( 'woosquare_modifier' );

		if ( false === $raw_modifier ) {
			global $wpdb;

			$raw_modifier = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woosquare_modifier WHERE modifier_set_name != '' ORDER BY modifier_set_name ASC;" );

			set_transient( 'wsm_modifier', $raw_modifier );
		}

		$raw_modifier = (array) array_filter( apply_filters( 'woocommerce_modifier_taxonomies', $raw_modifier ) );

		$modifier_taxonomies = array();

		foreach ( $raw_modifier as $result ) {
			$modifier_taxonomies[ 'id:' . $result->modifier_id ] = $result;
		}

		wp_cache_set( $cache_key, $modifier_taxonomies, 'woosquare-modifier' );

		return $modifier_taxonomies;
	}

	public static function wsm_add_modifier() {
		?>
		<div class="wrap woocommerce">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<br class="clear" />
			<div id="col-container">
				<div id="col-right">
					<div class="col-wrap">
						<table class="widefat attributes-table wp-list-table ui-sortable" style="width:100%">
							<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Modifier Group Name', 'woosquare_modifier' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Customer can only select one modifier', 'woosquare_modifier' ); ?></th>
								<th scope="col"><?php esc_html_e( 'Modifier', 'woosquare_modifier' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<?php
							$modifier_taxonomies = self::wsm_get_modifier();
							if ( $modifier_taxonomies ) :
								foreach ( $modifier_taxonomies as $modifier ) :
									?>
									<tr>
										<td>
											<strong><a href="edit-tags.php?taxonomy=<?php echo esc_attr( self::wsm_modifier_set_name( $modifier->modifier_set_name.$modifier->modifier_id) ); ?>&amp;post_type=product"> <?php echo  esc_html($modifier->modifier_set_name)  ?></a></strong>

											<div class="row-actions"><span class="edit"><a href="<?php echo esc_url( add_query_arg( 'edit', $modifier->modifier_id, 'edit.php?post_type=product&amp;page=woosquare_modifier' ) ); ?>"><?php esc_html_e( 'Edit', 'woosquare_modifier' ); ?></a> | </span><span class="delete"><a class="delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'delete', $modifier->modifier_id, 'edit.php?post_type=product&amp;page=woosquare_modifier' ), 'woocommerce-delete-modifier_' . $modifier->modifier_id ) ); ?>"><?php esc_html_e( 'Delete', 'woosquare_modifier' ); ?></a></span></div>
										</td>

										<?php if ( $modifier->modifier_public  ) { ?>
											<td> <?php echo  esc_html('Yes')  ?>  </td>
										<?php } else { ?>
											<td> <?php echo  esc_html('No')  ?>  </td>
										<?php } ?>

										<td class="attribute-terms">
											<?php

											$taxonomy = self::wsm_modifier_set_name( $modifier->modifier_set_name );
											$taxonomy = $taxonomy.$modifier->modifier_id;


											if ( taxonomy_exists( $taxonomy ) ) {
												$terms        = get_terms( $taxonomy, 'hide_empty=0' );


												$terms_string = implode( ', ', wp_list_pluck( $terms, 'name' ) );

												if ( $terms_string ) {
													echo esc_html( $terms_string );
												} else {
													echo '<span class="na">&ndash;</span>';
												}
											} else {
												echo '<span class="na">&ndash;</span>';
											}
											?>
											<br /><a href="edit-tags.php?taxonomy=<?php echo esc_attr( self::wsm_modifier_set_name( $modifier->modifier_set_name.$modifier->modifier_id) ); ?>&amp;post_type=product" class="configure-terms"><?php esc_html_e( 'Configure Modifier', 'woosquare_modifier' ); ?></a>
										</td>

									</tr>
									<?php
								endforeach;
							else :
								?>
								<tr>
									<td colspan="6"><?php esc_html_e( 'No Modifier currently exist.', 'woosquare_modifier' ); ?></td>
								</tr>
								<?php
							endif;
							?>
							</tbody>
						</table>
					</div>
				</div>
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">
							<h2><?php esc_html_e( 'Add new modifier', 'woosquare_modifier' ); ?></h2>
							<p><?php esc_html_e( 'Square Modifier Provide You Support of Modifier.', 'woosquare_modifier' ); ?></p>
							<form action="edit.php?post_type=product&amp;page=woosquare_modifier" method="post">

								<div class="form-field">
									<label for="modifier_set_name"><?php esc_html_e( 'Modifier Set Name', 'woosquare_modifier' ); ?></label>
									<input name="modifier_set_name" id="modifier_set_name" type="text" value="" />
								</div>

								<div class="form-field">
									<label for="modifier_public"><input name="modifier_public" id="modifier_public" type="checkbox" value="1" /> <?php esc_html_e( 'Customer can only select one modifier
                                     The first modifier in your set will become the default.', 'woosquare_modifier' ); ?></label>
								</div>
								<div class="form-field select_more">
									<label for="modifier_option" class="modifier_option_buttton"><input name="modifier_option" id="modifier_option" type="radio" value="radio" /> Radio Button </label>
									<label for="modifier_option" class="modifier_option_buttton"><input name="modifier_option" id="modifier_option" type="radio" value="select" /> Select Box </label>
								</div>

								<p class="submit"><button type="submit" name="add_new_modifier" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Add Modifier', 'woosquare_modifier' ); ?>"><?php esc_html_e( 'Add Modifier', 'woosquare_modifier' ); ?></button></p>
								<?php wp_nonce_field( 'woocommerce-add-new_modifier' ); ?>

							</form>
						</div>
					</div>
				</div>
			</div>
			<script type="text/javascript">
				jQuery( '.select_more' ).hide();
				jQuery( '#modifier_public' ).click( function() {
					if(jQuery('#modifier_public').is(':checked')){
						jQuery('.select_more').show();
					}else
					{
						jQuery('.select_more').hide();
					}
				});

				jQuery( 'a.delete' ).click( function() {
					if ( window.confirm( '<?php esc_html_e( 'Are you sure you want to delete this attribute?', 'woosqaure_modifier' ); ?>' ) ) {
						return true;
					}
					return false;
				});

				/* ]]> */
			</script>
		</div>
		<?php
	}

	private static function wsm_process_edit_modifier() {

		$modifier_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		check_admin_referer( 'woocommerce-save-modifier_' . $modifier_id );

		$modifier = self::wsm_get_posted_modifier();
		$args      = array(
				'modifier_set_name'         => $modifier['modifier_set_name'],
				'modifier_public'         => $modifier['modifier_public'],
				'modifier_option'         => $modifier['modifier_option']
		);

		$id = self::wsm_update_modifier( $modifier_id, $args );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		self::$edited_modifier_id = $id;

		return true;
	}


	private static function wsm_update_modifier( $id, $args ) {
		global $wpdb;

		$modifier = self::wsm_get_update_modifier( $id );

		$args['modifier_id'] = $modifier ? $modifier->id : 0;


		if ( $args['modifier_id'] && empty( $args['modifier_set_name'] ) ) {
			$args['modifier_set_name'] = $modifier->modifier_set_name;
		}

		$args['old_modifier_set'] = $wpdb->get_var(
				$wpdb->prepare(
						"
				SELECT modifier_set_name
				FROM {$wpdb->prefix}woosquare_modifier
				WHERE modifier_id = %d
			",
						$args['modifier_id']
				)
		);


		return self::wsm_create_modifier( $args );
	}

	private static function wsm_get_update_modifier( $id ) {
		$modifier = self::wsm_get_modifier();

		if ( ! isset( $modifier[ 'id:' . $id ] ) ) {
			return null;
		}

		$data                    = $modifier[ 'id:' . $id ];
		$modifier               = new stdClass();
		$modifier->id           = (int) $data->modifier_id;
		$modifier->modifier_set_name   = self::wsm_modifier_set_name($data->modifier_set_name);
		$modifier->modifier_public = (bool) $data->modifier_public;
		$modifier->modifier_option =  $data->modifier_option;
		return $modifier;
	}

	private static function wsm_edit_modifier() {
		global $wpdb;

		$edit = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;

		$modifier_to_edit = $wpdb->get_row(
				$wpdb->prepare(
						"
				SELECT modifier_set_name,modifier_option, modifier_public
				FROM {$wpdb->prefix}woosquare_modifier WHERE modifier_id = %d
				",
						$edit
				)
		);

		?>
		<div class="wrap woocommerce">
			<h1><?php esc_html_e( 'Edit modifier', 'woosquare_modifier' ); ?></h1>

			<?php
			if ( ! $modifier_to_edit ) {
				echo '<div id="woocommerce_errors" class="error"><p>' . esc_html__( 'Error: non-existing modifier ID.', 'woosquare_modifier' ) . '</p></div>';
			} else {
				if ( self::$edited_modifier_id > 0 ) {
					echo '<div id="message" class="updated"><p>' . esc_html__( 'Modifier updated successfully', 'woosquare_modifier' ) . '</p><p><a href="' . esc_url( admin_url( 'edit.php?post_type=product&amp;page=woosquare_modifier' ) ) . '">' . esc_html__( 'Back to Modifier', 'woosquare_modifier' ) . '</a></p></div>';
					self::$edited_modifier_id = null;
				}
				$modifier_set_name    = $modifier_to_edit->modifier_set_name;
				$modifier_public   = $modifier_to_edit->modifier_public;
				$modifier_option   = $modifier_to_edit->modifier_option;
				?>

				<form action="edit.php?post_type=product&amp;page=woosquare_modifier&amp;edit=<?php echo absint( $edit ); ?>" method="post">
					<table class="form-table">
						<tbody>
						<tr class="form-field form-required">
							<th scope="row" valign="top">
								<label for="modifier_set_name"><?php esc_html_e( 'Modifier Set Name', 'woosquare_modifier' ); ?></label>
							</th>
							<td>
								<input name="modifier_set_name" id="modifier_set_name" type="text" value="<?php echo esc_attr( $modifier_set_name ); ?>" />

							</td>
						</tr>
						<tr class="form-field form-required">
							<th scope="row" valign="top">
								<label for="modifier_public"><?php esc_html_e( 'Customer can only select one modifier?', 'woosquare_modifier' ); ?></label>
							</th>
							<td>
								<input name="modifier_public" id="modifier_public" type="checkbox" value="1" <?php checked( $modifier_public, 1 ); ?> " />
								<div class="form-field select_more">

									<label for="modifier_option" ><input name="modifier_option" id="modifier_option" type="radio" value="radio" <?php if( $modifier_option == 'radio' ){ ?> checked  <?php }  ?> /> Radio Button </label>

									<label for="modifier_option"><input name="modifier_option" id="modifier_option" type="radio" value="select" <?php if( $modifier_option == 'select' ){ ?> checked  <?php }  ?> /> Select Box </label>
								</div>
							</td>
						</tr>

						</tbody>
					</table>
					<p class="submit"><button type="submit" name="save_modifier" id="submit" class="button-primary" value="<?php esc_attr_e( 'Update', 'woosquare_modifier' ); ?>"><?php esc_html_e( 'Update', 'woocommerce' ); ?></button></p>
					<?php wp_nonce_field( 'woocommerce-save-modifier_' . $edit ); ?>
				</form>
			<?php } ?>
		</div>
		<script type="text/javascript">
			jQuery( '.select_more' ).hide();
			jQuery( '#modifier_public' ).click( function() {
				if(jQuery('#modifier_public').is(':checked')){
					jQuery('.select_more').show();
				}else
				{
					jQuery('.select_more').hide();
				}
			});
		</script>
		<?php
	}

	public static function wsm_modifier_set_name( $modifier_set_name ) {

		return $modifier_set_name ? 'pm_' . self::wsm_sanitize_taxonomy_name( $modifier_set_name ) : '';
	}
	private static function wsm_sanitize_taxonomy_name( $taxonomy ) {

		return apply_filters( 'sanitize_taxonomy_name', urldecode( sanitize_title( urldecode( $taxonomy ) ) ), $taxonomy );
	}


}