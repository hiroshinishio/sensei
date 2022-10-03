<?php
/**
 * File containing Sensei_Home_Promo_Banner_Provider class.
 *
 * @package sensei-lms
 * @since   $$next-version$$
 */

/**
 * Class that generates all the information relevant to the promotional banner in the Sensei Home screen.
 */
class Sensei_Home_Promo_Banner_Provider {

	/**
	 * Returns all the information for the promotional banner.
	 *
	 * @return Sensei_Home_Promo_Banner
	 */
	public function get(): Sensei_Home_Promo_Banner {

		/**
		 * Filter to disable the promotional banner in Sensei Home.
		 *
		 * @hook sensei_home_promo_banner_show
		 * @since $$next-version$$
		 *
		 * @param {bool} $show_promo_banner True if promotional banner must be shown.
		 *
		 * @return {bool}
		 */
		$is_visible = apply_filters( 'sensei_home_promo_banner_show', true );

		return new Sensei_Home_Promo_Banner( $is_visible );
	}
}
