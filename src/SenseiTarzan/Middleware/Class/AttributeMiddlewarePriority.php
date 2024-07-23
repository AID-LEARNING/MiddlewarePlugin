<?php

namespace SenseiTarzan\Middleware\Class;

use Attribute;
#[Attribute(Attribute::TARGET_CLASS)]
class AttributeMiddlewarePriority
{
	public function __construct(private int $priority)
	{
	}

	/**
	 * @return int
	 */
	public function getPriority(): int
	{
		return $this->priority;
	}
}