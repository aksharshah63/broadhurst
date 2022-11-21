<?php

namespace WPFormsSurveys;

/**
 * WPForms Surveys Polls related helper methods.
 *
 * @since 1.9.0
 */
class Helpers {

	/**
	 * Format the Likert entry to a more readable format.
	 *
	 * This function will take `$value` with the following format.
	 *
	 * ```
	 * Item #1:
	 * Strongly Disagree
	 * Item #2:
	 * Disagree
	 * Item #3:
	 * Neutral
	 * ```
	 *
	 * and convert it to a more readable format
	 *
	 * ```
	 * Item #1: Strongly Disagree
	 * Item #2: Disagree
	 * Item #3: Neutral
	 * ```
	 *
	 * @since 1.9.0
	 *
	 * @param string $value     Likert entry value.
	 * @param string $separator String used as separator to each likert row.
	 *
	 * @return string
	 */
	public static function format_likert_scale_entry( $value, $separator ) {

		$value_arr       = explode( $separator, $value );
		$counter         = 0;
		$formatted_value = '';

		foreach ( $value_arr as $val ) {
			$formatted_value .= $val;

			if ( $counter % 2 === 0 ) {
				$formatted_value .= ' ';
			} else {
				$formatted_value .= $separator;
			}

			$counter++;
		}

		if ( ! empty( $formatted_value ) ) {
			return $formatted_value;
		}

		return $value;
	}
}
