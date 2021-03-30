/**
 * Novalnet admin
 *
 * @category  Novalnet admin
 * @package   gravityforms-novalnet
 * @copyright Novalnet (https://www.novalnet.de)
 * @license   GPLv2
 */

(function ( $ ) {

	novalnet_admin = {

		init : function () {
			$( '#gaddon-setting-row-novalnet_tariff' ).hide();
			if ($( '#novalnet_public_key' ).length) {
				if ( '' !== $.trim( $( '#novalnet_public_key' ).val() ) ) {
					novalnet_admin.fill_novalnet_details();
				}
				$( '#novalnet_public_key' ).on(
					'change',
					function() {
						if ( '' !== $.trim( $( '#novalnet_public_key' ).val() ) ) {
							novalnet_admin.fill_novalnet_details();
						} else {
							novalnet_admin.null_basic_params();
						}
					}
				);
				$( '#novalnet_public_key' ).closest( 'form' ).on(
					'submit',
					function( event ) {
						if ( undefined === novalnet_admin.ajax_complete ) {
							event.preventDefault();
							$( document ).ajaxComplete(
								function( event, xhr, settings ) {
									$( '#novalnet_public_key' ).closest( 'form' ).submit();
								}
							);
						}
					}
				);
			}
		},

		/* Vendor hash process */
		config_hash_response : function ( data ) {
				data = data.data;

			if (undefined !== data.error && '' !== data.error ) {

				alert( data.error );
				novalnet_admin.null_basic_params();
				return false;
			}

			$( '#gaddon-setting-row-novalnet_tariff' ).show();
			var saved_tariff_id = $( '#novalnet_tariff' ).val();

			if ($( '#novalnet_tariff' ).prop( 'type' ) == 'text') {
				$( '#novalnet_tariff' ).replaceWith( '<select id="novalnet_tariff" class="small gaddon-setting gaddon-select" style="width:25em;" name= "_gaddon_setting_novalnet_tariff" ></select>' );
			}
			$( '#novalnet_tariff' ).empty().append();

			for ( var tariff_id in data.tariff ) {
				var tariff_type  = data.tariff[ tariff_id ].type;
				var tariff_value = data.tariff[ tariff_id ].name;

				$( '#novalnet_tariff' ).append(
					$(
						'<option>',
						{
							value: $.trim( tariff_id ),
							text : $.trim( tariff_value )
						}
					)
				);

				// Assign tariff id.
				if (saved_tariff_id === $.trim( tariff_id ) ) {
					$( '#novalnet_tariff' ).val( $.trim( tariff_id ) );
				}
			}

			// Assign vendor details.
			$( '#novalnet_vendor' ).val( data.vendor );
			$( '#novalnet_auth_code' ).val( data.auth_code );
			$( '#novalnet_product' ).val( data.product );
			$( '#novalnet_payment_access_key' ).val( data.access_key );
			novalnet_admin.ajax_complete = 'true';
			return true;
		},

		/* Process to fill the vendor details */
		fill_novalnet_details : function () {
			var data = {
				'novalnet_api_key': $.trim( $( '#novalnet_public_key' ).val() ),
				'action': 'get_novalnet_vendor_details',
			};
			novalnet_admin.ajax_call( data );

		},

		/* Null config values */
		null_basic_params : function () {

			novalnet_admin.ajax_complete = 'true';
			$( '#gaddon-setting-row-novalnet_tariff' ).hide();
			$( '#novalnet_vendor, #novalnet_auth_code, #novalnet_product, #novalnet_payment_access_key, #novalnet_public_key' ).val( '' );
			$( '#novalnet_tariff' ).find( 'option' ).remove();
			$( '#novalnet_tariff' ).append(
				$(
					'<option>',
					{
						value: '',
						text : novalnet_admin.select_text,
					}
				)
			);
		},

		/* Initiate ajax call to server */
		ajax_call : function ( data ) {

			$.ajax(
				{
					type:     'post',
					url:      ajaxurl,
					data:     data,
					success:  function( response ) {
						return novalnet_admin.config_hash_response( response );
					},
				}
			);
		}
	};

	$( document ).ready(
		function () {
			novalnet_admin.init();
		}
	);
})( jQuery );
