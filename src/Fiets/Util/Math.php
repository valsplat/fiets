<?php
	namespace Fiets\Util;

	class Math {

		/**
		 * Calculate the median of given array of numbers.
		 *
		 * In statistics and probability theory, median is described as the numerical value
		 * separating the higher half of a sample, a population, or a probability distribution,
		 * from the lower half.
		 *
		 * @param array $data
		 * @return integer|float Median
		 * @author Bjorn Post
		 */
		public static function median(array $data) {
			sort($data);

			$n = count($data);
			$h = intval($n / 2);

			if($n % 2 == 0) {
				$median = ($data[$h] + $data[$h-1]) / 2;
			} else {
				$median = $data[$h];
			}

			return (int)$median;
		}

		/**
		 * Calculate average for given array of numbers.
		 *
		 * @param array $data
		 * @return integer|float Average
		 * @author Bjorn Post
		 */
		public static function average(array $data) {
			if(!is_array($data)) {
				return false;
			}

		   return (array_sum($data)/count($data));
		}
	}