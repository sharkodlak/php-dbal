# FluentDB

Fluent DB provides high performance DB layer with easy to use fluent interface.
Inspired by NotORM and DibiFluent.

Use table film:

	$db->film

Select all columns and all rows from table language:

	$db->language->getQuery()->getResult()->fetchAll()

Select all rows from table actor, but let library to select only necessary columns:

	foreach ($db->actor as $actor) {
		$actorName = $actor['first_name'] . ' ' . $actor['last_name'];
	}

In previous example only columns actor_id (as table primary key), first_name and last_name will be automatically selected
in consecutive calls (in case of cache hit).

To get referenced table row, simply get table name from row. Library uses `Structure\Convention->foreignKey()` as source to match
referenced table primary key identified by `Structure\Convention->primaryKey()`.

	$row->language

To obtain referencing column value instead of referenced table, use column name.

	$row['language_id']

Get corresponding rows from referenced table found by specified column. Method `Table->via()` specifies which column use from source table
and optionaly from referenced table. This method returns one row.
To find which rows references this value, method `Table->backwards()` shall be used. Method accepts optional arguments to specify
column name from second table (defauts to foreign key referencing first table) and which column name to match in first table
(defaults to primary key) - so these arguments are in opposite order against arguments from `Table->via()`. This method
returns collection of corresponding rows.

	foreach ($db->directory as $directory) {
		$parentDirectory = $directory->directory->via('parent_id');
		$subDirectories = $directory->directory->backwards('parent_id');
	}

Row acts as an array, it can be iterated and method `toArray` returns all columns and values.

	$row['name']; // Array access to column named 'name'
	foreach ($row as $columnName => $value) { // Iterate over all columns
		$columnName . ': ' . $value;
	}
	$row->toArray(); // Get all columns as array

It's also possible to get one row from table defined by it's id.

	$row = $db->film[$id]

To filter fetching rows from DB, use method where.

	$db->film->where('release_year BETWEEN %s AND %s', 2001, 2008);


Features to be implemented later if it make sense
-------------------------------------------------

Filter rows by data from another table

	// Select all movies in German language
	// SELECT film.* FROM film JOIN language USING (language_id) WHERE language.name = 'German'
	$db->film->language->where('name = %s', 'German');
	// Select actors having at least 3 films
	// SELECT actor_id FROM film_actor GROUP BY actor_id HAVING COUNT(film_id) >= 30
	$actorsHavingALotOfFilms = $db->film_actor->groupBy(̈́'actor_id')->having('COUNT(film_id) >= 30');
	$db->actor->where(':id IN (%s)', $actorsHavingALotOfFilms);

It's possible to get referencing column value without knowledge of column name, the table name is required instead. Due to lazy loading
it has no performance impact. Select from referenced table will performed only if another than primary key column will be used.
This feature works only if DB has consistent Referential Integrity, because it mimics matching row in referenced table.

	// Typical use is $row['language_id']
	$row->language[':id']

Reference methods accepts array arguments to allow table referencing by compound key.

	$row->target->via(['lang_id', 'second_id'], ['lang_id', 'second_id']);
	$row->target->backwards(['lang_id', 'second_id'], ['lang_id', 'second_id']);

Reference methods also accept 3rd argument with comparison function. By default it's equality function, but more complex function
can be used.

	$between = function($rowColumnValue, $tableColumnValue) {
		...
		return $inBetween;
	}
	$row->target->via(null, null, $between);
