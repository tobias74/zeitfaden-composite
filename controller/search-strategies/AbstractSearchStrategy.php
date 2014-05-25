<?php

class AbstractSearchStrategy
{
	public function __construct($controller)
	{
		$this->controller = $controller;
	}
	
	protected function getMyControllerContext()
	{
		return $this->controller;
	}
}
