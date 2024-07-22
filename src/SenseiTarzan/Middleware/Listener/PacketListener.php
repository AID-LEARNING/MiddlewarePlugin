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

namespace SenseiTarzan\Middleware\Listener;

use Error;
use Exception;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use SenseiTarzan\ExtraEvent\Class\EventAttribute;
use SenseiTarzan\Middleware\Component\MiddlewareManager;
use SOFe\AwaitGenerator\Await;

class PacketListener
{

	#[EventAttribute(EventPriority::MONITOR)]
	public function onDataReceive(DataPacketReceiveEvent $event) : void
	{
		$packet = $event->getPacket();
		if ($packet instanceof LoginPacket)
		{
			$event->cancel();
			$origin = $event->getOrigin();
			$handlerBefore = $origin->getHandler();
			$origin->setHandler(null);
			$listResolve = MiddlewareManager::getInstance()->getPromiseWithPacket($packet, $event);
			Await::g2c(Await::all($listResolve), function (array $allResolve) use ($origin, $handlerBefore, $packet) {
				/**
				 * @var Error|Exception $error
				 */
				$error = null;
				foreach ($allResolve as $resolve)
				{
					if ($resolve instanceof Error || $resolve instanceof Exception)
					{
						$error = $resolve;
						break ;
					}
				}
				if ($error)
				{
					$origin->disconnect($error->getMessage());
					return;
				}
				$origin->setHandler($handlerBefore);
				if (!$origin->getHandler()->handleLogin($packet))
					$origin->disconnect("Error handling Login");
			});
		}elseif ($packet instanceof SetLocalPlayerAsInitializedPacket)
		{
			$event->cancel();
			$origin = $event->getOrigin();
			$handlerBefore = $origin->getHandler();
			$origin->setHandler(null);
			$listResolve = MiddlewareManager::getInstance()->getPromiseWithPacket($packet, $event);
			Await::g2c(Await::all($listResolve), function (array $allResolve) use ($origin, $handlerBefore, $packet) {
				/**
				 * @var Error|Exception $error
				 */
				$error = null;
				foreach ($allResolve as $resolve)
				{
					if ($resolve instanceof Error || $resolve instanceof Exception)
					{
						$error = $resolve;
						break ;
					}
				}
				if ($error)
				{
					$origin->disconnect($error->getMessage());
					return;
				}
				$origin->setHandler($handlerBefore);
				if (!$origin->getHandler()->handleSetLocalPlayerAsInitialized($packet))
					$origin->disconnect("Error handling SetLocalPlayer");
			});
		}
	}
}
