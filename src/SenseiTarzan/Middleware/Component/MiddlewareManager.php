<?php

/*
 *
 *            _____ _____         _      ______          _____  _   _ _____ _   _  _____
 *      /\   |_   _|  __ \       | |    |  ____|   /\   |  __ \| \ | |_   _| \ | |/ ____|
 *     /  \    | | | |  | |______| |    | |__     /  \  | |__) |  \| | | | |  \| | |  __
 *    / /\ \   | | | |  | |______| |    |  __|   / /\ \ |  _  /| . ` | | | | . ` | | |_ |
 *   / ____ \ _| |_| |__| |      | |____| |____ / ____ \| | \ \| |\  |_| |_| |\  | |__| |
 *  /_/    \_\_____|_____/       |______|______/_/    \_\_|  \_\_| \_|_____|_| \_|\_____|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author AID-LEARNING
 * @link https://github.com/AID-LEARNING
 *
 */

declare(strict_types=1);

namespace SenseiTarzan\Middleware\Component;

use Generator;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\Middleware\Class\AttributeMiddlewarePriority;
use SenseiTarzan\Middleware\Class\IMiddleWare;
use SenseiTarzan\Middleware\Class\MiddlewarePriority;
use SenseiTarzan\Middleware\Main;
use function array_keys;
use function array_map;
use function get_class;
use function var_dump;

final class MiddlewareManager
{
	use SingletonTrait;

	/** @var IMiddleWare[][][] */
	private array $listMiddleware = [];

	/** @var IMiddleWare[][] */
	private array $middlewareCache = [];

	public function getListMiddleware() : array
	{
		return $this->listMiddleware;
	}

	public function addMiddleware(IMiddleWare $middleware) : void
	{
		Main::getInstance()->getLogger()->info("Adding Middleware: " . get_class($middleware));
		$middlewarePriority = MiddlewarePriority::NORMAL;
		$reflectClass = new \ReflectionClass($middleware);
		$attributes = $reflectClass->getAttributes(AttributeMiddlewarePriority::class);
		if (!empty($attributes)) {
			/**
			 * @var AttributeMiddlewarePriority $priority
			 */
			$priority = $attributes[0]->newInstance();
			$middlewarePriority = $priority->getPriority();
		}
		if (!isset($this->listMiddleware[$middleware->onDetectPacket()][$middlewarePriority])) {
			$this->listMiddleware[$middleware->onDetectPacket()][$middlewarePriority] = [];
		}
		$this->listMiddleware[$middleware->onDetectPacket()][$middlewarePriority][] = $middleware;
	}

	/**
	 * @return IMiddleWare[][]
	 */
	public function getListMiddlewaresWithPacket(LoginPacket|SetLocalPlayerAsInitializedPacket $packet) : array
	{
		return $this->listMiddleware[$packet::class] ?? [];
	}

	/**
	 * @return Generator[]
	 */
	public function getPromiseWithPacket(LoginPacket|SetLocalPlayerAsInitializedPacket $packet, DataPacketReceiveEvent $event) : array
	{
		if (!isset($this->middlewareCache[$packet::class]))
		{
			$this->middlewareCache[$packet::class] = [];
			$listMiddlewares = $this->getListMiddlewaresWithPacket($packet);
			foreach (MiddlewarePriority::ALL as $priority)
			{
				if (!isset($listMiddlewares[$priority]))
					continue;
				foreach ($listMiddlewares[$priority] as $middleware)
					$this->middlewareCache[$packet::class][$middleware->getName()] = $middleware;
			}
		}
		return array_map(fn(IMiddleWare $middleware) => $middleware->getPromise($event), $this->middlewareCache[$packet::class]);
	}
}
