<?php

namespace Sharkodlak\FluentDb;

class DbTest extends \PHPUnit_Framework_TestCase {
	private static $db;

	public static function setUpBeforeClass() {
		$pdo = new \PDO("uri:file:///etc/dbal/.dbconnect");
		$cache = new \Stash\Pool();
		self::$db = new Db($pdo, $cache);
	}

	public function testSetConvention() {
		$this->markTestSkipped('Not implemented yet.');
	}

	public function testQuery() {
		$expected = 'SELECT film.*, language.name, category.name
			FROM film
				JOIN language USING (language_id)
				LEFT JOIN film_category USING (film_id)
				LEFT JOIN category USING (category_id)
			WHERE film.id IN (SELECT id FORM film OFFSET 10 LIMIT 3)
			ORDER BY film.film_id DESC, category.name';
		$films = self::$db->film->where('id IN (SELECT id FORM film OFFSET 10 LIMIT 3)')->order('id DESC');
		$films->join('language')->select('name');
		$films->leftJoin('film_category')->reverse()->leftJoin('category')->select('name')->order('name');
		$this->assertEquals($expected, $films->getQuery());
	}

	public function testTable() {
		$expected = [];
		$actual = [];
		// SELECT * FROM film ORDER BY film_id DESC OFFSET 10 LIMIT 3
		// Calls fetchAll to obtain all ID's for use by derived tables. If all derived tables calls wholeTable() iteration will use fetchAssoc instead.
		foreach (self::$db->film->offset(10)->limit(3)->order('id DESC') as $filmId => $film) {
			$actual[$filmId] = $film->toArray();
			// SELECT language_id, name FROM langage WHERE language_id IN (1, 3) -- ids are known from film table
			$actual[$filmId]['language'] = $film->language['name'];
			// SELECT name FROM film_category JOIN category USING (category_id)
			foreach ($film->leftJoin('film_category')->category->order('name')->wholeTable() as $categoryId => $category) {
				$actual[$filmId]['categories'][$categoryId] = $category['name'];
			}
		}
		$this->assertEquals(array_keys($expected), array_keys($actual));
		$this->assertEquals($expected[12], $actual[12]);
		$this->assertEquals($expected[11], $actual[11]);
		$this->assertEquals($expected[10], $actual[10]);
	}

	public function testMultipleRuns() {
		for ($i = 0; $i < 3; ++$i) {
			// 1st run: SELECT * FROM film
			// 2nd run: SELECT film_id, title FROM film
			// 3rd run: SELECT film_id, title, description FROM film
			foreach (self::$db->film->order('id DESC')->offset($i * 10)->limit(3) as $filmId => $film) {
				$film['title'];
				if ($i >= 1) {
					// SELECT film_id, description FROM file WHERE film_id IN (...)
					$film['description'];
				}
			}
			// Can reuse same query result because it contains same columns and filters
			$films = self::$db->film->select('title')->order('id DESC')->offset($i * 10)->limit(3);
			foreach ($films as $filmId => $film) {
				$film['title'];
			}
		}
	}

	public function testCacheInvalidation() {
		for ($i = 0; $i < 3; ++$i) {
			// 1st run: SELECT * FROM film
			// 2nd run: SELECT film_id, title FROM film
			foreach (self::$db->film->order('id DESC')->offset(10)->limit(3) as $filmId => $film) {
				$film['title'];
			}
			$filmUpdate = ['id' => -1];
			// Because table is modified invalidate cache
			self::$db->film->update($filmUpdate)->where('id = 1');
		}
	}

	public function testCacheStillValid() {
		for ($i = 0; $i < 3; ++$i) {
			// 1st run: SELECT * FROM film
			// 2nd run: Reuse result from previous run
			foreach (self::$db->film->order('id')->offset(10)->limit(3) as $filmId => $film) {
				$film['title'];
			}
			$filmInsert = ['title' => 'new', 'language_id' => 1];
			// Because inserted id doesn't affect selected result, result will be still valid
			self::$db->film->insert($filmInsert);
		}
	}
}