<?php

namespace SenseiTarzan\Middleware\Class;

class MiddlewarePriority
{
	const LOWEST = 0;
	const LOW = 1;
	const NORMAL = 2;
	const HIGH = 3;
	const HIGHEST = 4;
	const MONITOR = 5;

	public const ALL = [
		self::LOWEST,
		self::LOW,
		self::NORMAL,
		self::HIGH,
		self::HIGHEST,
		self::MONITOR
	];
}