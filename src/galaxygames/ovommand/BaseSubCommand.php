<?php
declare(strict_types=1);

namespace galaxygames\ovommand;

use galaxygames\ovommand\fetus\Ovommand;
use galaxygames\ovommand\fetus\ParametableTrait;
use pocketmine\lang\Translatable;
use pocketmine\plugin\Plugin;

abstract class BaseSubCommand extends Ovommand{
	use ParametableTrait;
	/** @var string[] */
	private array $hiddenAliases;

	/** @var string[] */
	private array $showAliases;
	protected ?Ovommand $parent = null;

	/**
	 * @param list<string>             $hiddenAliases
	 * @param list<string>             $showAliases
	 */
	public function __construct(
		string $name, protected string|Translatable $description = "", ?string $permission = null,
		Translatable|string|null $usageMessage = null, array $hiddenAliases = [], array $showAliases = []
	){
		parent::__construct($name, $this->description, $permission, $usageMessage);

		$this->hiddenAliases = array_unique($hiddenAliases);
		$this->showAliases = array_unique($showAliases);
	}

	public function isAliases(string $in) : bool{
		return $this->isHiddenAlias($in) || $this->isShowAlias($in);
	}

	public function isHiddenAlias(string $in) : bool{
		return in_array($in, $this->hiddenAliases, true);
	}

	public function isShowAlias(string $in) : bool{
		return in_array($in, $this->showAliases, true);
	}

	/**
	 * @return list<string>
	 */
	public function getHiddenAliases() : array{
		return $this->hiddenAliases;
	}

	/**
	 * @return list<string>
	 */
	public function getShowAliases() : array{
		return $this->showAliases;
	}

	public function getParent() : ?Ovommand{
		return $this->parent;
	}

	/**
	 * @internal Used to pass the parent context from the parent command
	 */
	public function setParent(Ovommand $parent) : self{
		$this->parent = $parent;
		$parentHeader = $parent->getName() . " " . $this->getName();
		while ($parent instanceof BaseSubCommand) {
			$newParent = $parent->getParent();
			if ($newParent === null) {
				return $this;
			}
			$parentHeader = $newParent->getName() . " " . $parentHeader;
			$parent = $newParent;
		}
		if ($parent instanceof BaseCommand) {
			foreach ($this->subCommands as $k => $subCommand) {
				if ($k !== $subCommand->getName()) {
					continue;
				}
				$subCommand->setParent($this);
			}
			$this->setUsage("/$parentHeader " . implode("\n/$parentHeader ", $this->generateUsageList()));
		}
		return $this;
	}
}
