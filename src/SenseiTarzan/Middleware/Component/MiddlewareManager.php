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

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\utils\SingletonTrait;
use SenseiTarzan\Middleware\Class\IMiddleWare;
use SenseiTarzan\Middleware\Main;
use function array_filter;
use function array_map;
use function get_class;

final class MiddlewareManager
{
	use SingletonTrait;

	/** @var IMiddleWare[] */
	private array $listMiddleware = [];

	public function getListMiddleware() : array
	{
		return $this->listMiddleware;
	}

	public function addMiddleware(IMiddleWare $middleware) : void
	{
		Main::getInstance()->getLogger()->info("Adding Middleware: " . get_class($middleware));
		$this->listMiddleware[$middleware->getName()] = $middleware;
	}

	public function getListMiddlewareWithPacket(LoginPacket|SetLocalPlayerAsInitializedPacket $packet) : array
	{
		return array_filter(
			$this->listMiddleware,
			function (IMiddleWare $middleware) use ($packet) : bool {
				return $packet instanceof ($middleware->onDetectPacket());
			}
		);
	}

	/**
	 * @return array<\Generator>
	 */
	public function getPromiseWithPacket(LoginPacket|SetLocalPlayerAsInitializedPacket $packet, DataPacketReceiveEvent $event) : array
	{
		return array_map(function (IMiddleWare $middleware) use($event) {
			return $middleware->getPromise($event);
		}, $this->getListMiddlewareWithPacket($packet));
	}
}
