<?php
	namespace Fiets\Util;

	class Set {

		/**
		 * Get a single value specified by $path out of $data.
		 * Does not support the full dot notation feature set,
		 * but is faster for simple read operations.
		 *
		 * @param array $data Array of data to operate on.
		 * @param string|array $path The path being searched for. Either a dot
		 *   separated string, or an array of path segments.
		 * @return mixed The value fetched from the array, or null.
		 */
		public static function get(array $data, $path) {
			if (empty($data) || empty($path)) {
				return null;
			}
			if (is_string($path)) {
				$parts = explode('.', $path);
			} else {
				$parts = $path;
			}
			foreach ($parts as $key) {
				if (is_array($data) && isset($data[$key])) {
					$data =& $data[$key];
				} else {
					return null;
				}

			}
			return $data;
		}

		/**
		 * Gets the values from an array matching the $path expression.
		 * The path expression is a dot separated expression, that can contain a set
		 * of patterns and expressions:
		 *
		 * - `{n}` Matches any numeric key, or integer.
		 * - `{s}` Matches any string key.
		 * - `Foo` Matches any key with the exact same value.
		 *
		 * There are a number of attribute operators:
		 *
		 *  - `=`, `!=` Equality.
		 *  - `>`, `<`, `>=`, `<=` Value comparison.
		 *  - `=/.../` Regular expression pattern match.
		 *
		 * Given a set of User array data, from a `$User->find('all')` call:
		 *
		 * - `1.User.name` Get the name of the user at index 1.
		 * - `{n}.User.name` Get the name of every user in the set of users.
		 *
		 * @param array $data The data to extract from.
		 * @param string $path The path to extract.
		 * @return array An array of the extracted values.  Returns an empty array
		 *   if there are no matches.
		 */
		public static function extract(array $data, $path) {
			if (empty($path)) {
				return $data;
			}

			// Simple paths.
			if (!preg_match('/[{\[]/', $path)) {
				return (array)static::get($data, $path);
			}

			$tokens = explode('.', $path);
			$_key = '__set_item__';

			$context = array($_key => array($data));

			foreach ($tokens as $token) {
				$next = array();

				$conditions = false;
				$position = strpos($token, '[');
				if ($position !== false) {
					$conditions = substr($token, $position);
					$token = substr($token, 0, $position);
				}

				foreach ($context[$_key] as $item) {
					if(is_object($item)) {
						$item = (array)$item->toArray();
					}

					foreach ((array)$item as $k => $v) {
						if (static::_matchToken($k, $token)) {
							$next[] = $v;
						}
					}
				}

				// Filter for attributes.
				if ($conditions) {
					$filter = array();
					foreach ($next as $item) {
						if (static::_matches($item, $conditions)) {
							$filter[] = $item;
						}
					}
					$next = $filter;
				}
				$context = array($_key => $next);

			}
			return $context[$_key];
		}

		/**
		 * Check a key against a token.
		 *
		 * @param string $key The key in the array being searched.
		 * @param string $token The token being matched.
		 * @return boolean
		 */
		protected static function _matchToken($key, $token) {
			if ($token === '{n}') {
				return is_numeric($key);
			}
			if ($token === '{s}') {
				return is_string($key);
			}
			if (is_numeric($token)) {
				return ($key == $token);
			}
			return ($key === $token);
		}

		/**
		 * Checks whether or not $data matches the attribute patterns
		 *
		 * @param array $data Array of data to match.
		 * @param string $selector The patterns to match.
		 * @return boolean Fitness of expression.
		 */
		protected static function _matches(array $data, $selector) {
			preg_match_all(
				'/(\[ (?<attr>[^=><!]+?) (\s* (?<op>[><!]?[=]|[><]) \s* (?<val>[^\]]+) )? \])/x',
				$selector,
				$conditions,
				PREG_SET_ORDER
			);

			foreach ($conditions as $cond) {
				$attr = $cond['attr'];
				$op = isset($cond['op']) ? $cond['op'] : null;
				$val = isset($cond['val']) ? $cond['val'] : null;

				// Presence test.
				if (empty($op) && empty($val) && !isset($data[$attr])) {
					return false;
				}

				// Empty attribute = fail.
				if (!(isset($data[$attr]) || array_key_exists($attr, $data))) {
					return false;
				}

				$prop = isset($data[$attr]) ? $data[$attr] : null;

				// Pattern matches and other operators.
				if ($op === '=' && $val && $val[0] === '/') {
					if (!preg_match($val, $prop)) {
						return false;
					}
				} elseif (
					($op === '=' && $prop != $val) ||
					($op === '!=' && $prop == $val) ||
					($op === '>' && $prop <= $val) ||
					($op === '<' && $prop >= $val) ||
					($op === '>=' && $prop < $val) ||
					($op === '<=' && $prop > $val)
				) {
					return false;
				}

			}
			return true;
		}

		/**
		 * Regroup given array.
		 *
		 * @param   string   $array      Array that should be regrouped.
		 * @param   string   $fields     Comma separated list of fields to sort by.
		 * @param   string   $onlyThisKey  Instead of returning the entire array as value, only use this key's value.
		 * @return  array    Regrouped array
		 * @author  Joris
		 * @deprecated
		 * @see \Fiets\Util\Set::extract() -- more powerful brother.
		 */
		static function regroup($data, $fields, $onlyThisKey = false) {
			$fields = explode(',', $fields);
			$result = array();

			if(is_object($data)) $data = $data->toArray();
			if(!is_array($data)) return $result;

			$data = array_values($data);
			$array_count = count($data);
			for($i = 0; $i < $array_count; $i++) {
				$source = $data[$i];

				if($onlyThisKey) {
					if(is_object($source)) {
						if(method_exists($source, $onlyThisKey)) {
							$val = $source->$onlyThisKey();
						} else {
							$val = $source->$onlyThisKey;
						}
						$source = $source->toArray();
					} else {
						$val = $source[$onlyThisKey];
					}
				} else {
					if(is_object($source)) $source = $source->toArray();

					$val = Array();
					$keys = array_keys($source);
					$keys_count = count($keys);
					$val = $source;
				}

				if(!isset($fields[1]))     { $result[(string)$source[$fields[0]]] = $val; }
				elseif(!isset($fields[3])) { $result[(string)$source[$fields[0]]][(string)$source[$fields[1]]] = $val; }
				else                       { $result[(string)$source[$fields[0]]][(string)$source[$fields[1]]][(string)$source[$fields[2]]] = $val; }
			}

			return $result;
		}

		/**
		 * Find distinct values for key and their count
		 *
		 * @param   mixed $data Data: recursive array or collection of object
		 * @param   string $key The key to count distinct values and count for
		 * @return  array with all values encountered for key, and the number of times the occured
		 * @author  Joris Leker
		 */
		static function countKeyByValues($data, $key) {

			$result = array();
			if(is_object($data)) $data = $data->toArray();

			$array_count = count($data);
			for($i = 0; $i < $array_count; $i++) {
				$source = $data[$i];

				// Use various strategies to find value (as array-key, property or method)
				$value = NULL;
				if(is_array($source)) {
					if(isset($source[$key])) $value = $source[$key];
				} else if(is_object($source)) {
					if(isset($source->$key)) $value = $source->$key;
					else if(method_exists($source, $key)) $value = $source->$key();
				}

				if(!is_null($value) && $value != '') {
					if(array_key_exists($value, $result)) {
						$result[$value]++;
					} else {
						$result[$value] = 1;
					}
				}
			}
			return $result;
		}

		/**
		 * Gather some statistics for each keys.key present in collection data
		 *
		 * @param   mixed $data Data: recursive array or collection of object
		 * @param   string $key The key to count distinct values and count for
		 * @return  array with all values encountered for key, and the number of times the occured
		 * @author  Joris Leker
		 */
		static function statisticsForKeys($data, $keys = array()) {

			$results = array();
			foreach($keys as $key)
			{
				$results[$key]['countPerValue'] = static::countKeyByValues($data,$key);

				$weightedTotal = 0;
				$totalCount = 0;
				$values = array();
				foreach($results[$key]['countPerValue'] as $value => $count)
				{
					if(!empty($value)) {
						if(is_numeric($value)) {
							$weightedTotal += $value*$count;
							$totalCount += $count;
							$values = array_merge($values,array_fill(0,$count,$value));
						} else { // non-numeric key encountered: can't calculate average
							$weightedTotal = false;
							break;
						}
					}
				}
				// Appearantly we counted numeric values;
				// calucate average, min, max (only usefull if we have more then 1 value)
				if($weightedTotal !== false && $totalCount > 1) {
					ksort($results[$key]['countPerValue']); // numeric data: sort by value

					if(($totalCount%2) > 0) { // uneven number? Median = middle number
						$median = $values[floor($totalCount/2)-1];
					} else { // even number? Median = average of 2 middle values
						$median = ($values[($totalCount/2)-1] + $values[$totalCount/2]) / 2;
					}

					$results[$key]['median'] = $median;
					$results[$key]['average'] = $weightedTotal / $totalCount;
					$results[$key]['min'] = min($values);
					$results[$key]['max'] = max($values);
				} else { // non numeric values: sort by most frequent value
					arsort($results[$key]['countPerValue']);
				}
			}

			return $results;
		}

	}
