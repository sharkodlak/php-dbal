<?php

namespace Sharkodlak\FluentDb\Query;

trait MethodsTrait {
	abstract protected function getQueryBuilder();

	public function limit($limit) {
		$this->getQueryBuilder()->limit($limit);
		return $this;
	}

	public function offset($offset) {
		$this->getQueryBuilder()->offset($offset);
		return $this;
	}

	public function orderBy(...$args) {
		$this->getQueryBuilder()->orderBy(...$args);
		return $this;
	}

	public function where(...$args) {
		$this->getQueryBuilder()->where(...$args);
		return $this;
	}
}
