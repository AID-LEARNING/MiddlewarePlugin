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

namespace SenseiTarzan\Middleware;

use Closure;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\base\DataConnectorImpl;
use poggit\libasynql\libasynql;
use SenseiTarzan\ExtraEvent\Component\EventLoader;
use SenseiTarzan\libredis\libredis;
use SenseiTarzan\libredis\RedisManager;
use SenseiTarzan\Middleware\Listener\PacketListener;
use SenseiTarzan\Mongodm\Class\MongoConfig;
use SenseiTarzan\Mongodm\libmongodm;
use SenseiTarzan\Mongodm\MongodmManager;

class Main extends PluginBase
{
	private static Main $instance;
	private DataConnectorImpl|MongodmManager|null $dataConnector = null;
	private RedisManager|null $redisConnector = null;

	/**
	 * @var Closure[]
	 */
	private array $onDisableClosure = [];

	protected function onLoad() : void
	{
		self::$instance = $this;
		$cache = $this->getConfig()->get("redis", false);
		$database = $this->getConfig()->get("database", false);
		if ($database !== false) {
			ini_set("middleware_database_enable", true);
			define("MIDDLEWARE_DATABASE_ENABLE", true);
			if ($database["type"] === "mongodb"){
				$config = $database["mongodb"] ?? [];
				$this->dataConnector = libmongodm::create($this,
				new MongoConfig(
					$config["uri"] ?? "mongodb://localhost:27017",
						$config["database"],
						$config["uriOptions"] ?? [],
						$config["driverOptions"] ?? [],
						$config["dbOptions"] ?? [],
				),
					intval($database["worker-limit"])
				);
			}else {
				$this->dataConnector = libasynql::create($this, $database, [
					"mysql" => "mysql.sql",
					"sqlite" => "sqlite.sql"
				]);
			}
		}else {
			ini_set("middleware_database_enable", false);
		}
		if ($cache)
		{
			ini_set("middleware_redis_enable", true);
			$this->redisConnector = libredis::create($this,
				$cache["option"],
				intval($cache["worker-limit"])
			);
		}else{
			ini_set("middleware_redis_enable", false);
		}
		register_shutdown_function($this->onDisable(...));
	}

	protected function onEnable() : void
	{
		EventLoader::loadEventWithClass($this, new PacketListener());
	}

	/**
	 * @param Closure $closure
	 * @return void
	 */
	public function addOnDisableClosure(Closure $closure) : void
	{
		$this->onDisableClosure[] = $closure;
	}

	protected function onDisable(): void
	{
		$this->dataConnector?->waitAll();
		$this->dataConnector?->close();
		$this->redisConnector?->waitAll();
		$this->redisConnector?->close();
		foreach ($this->onDisableClosure as $function) {
			$function($this);
		}
	}

	/**
	 * Load Modal query libasynql
	 * @param Plugin $plugin
	 * @param string $file
	 * @return void
	 */
	public function loadMiddlewareQueryFile(Plugin $plugin, string $file) : void
	{
		$resource = $plugin->getResource($file);
		if($resource === null){
			throw new \InvalidArgumentException("resources/$file does not exist");
		}
		$this->dataConnector->loadQueryFile($resource);
	}

	/**
	 * @return DataConnectorImpl|MongodmManager|null
	 */
	public function getDataConnector(): DataConnectorImpl|MongodmManager|null
	{
		return $this->dataConnector;
	}

	/**
	 * @return RedisManager|null
	 */
	public function getRedisConnector(): ?RedisManager
	{
		return $this->redisConnector;
	}

	public static function getInstance() : Main
	{
		return self::$instance;
	}
}
