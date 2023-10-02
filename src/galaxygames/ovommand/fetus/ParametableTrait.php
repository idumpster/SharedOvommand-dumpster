<?php
declare(strict_types=1);

namespace galaxygames\ovommand\fetus;

use galaxygames\ovommand\exception\ExceptionMessage;
use galaxygames\ovommand\exception\ParameterOrderException;
use galaxygames\ovommand\parameter\BaseParameter;
use galaxygames\ovommand\parameter\result\BrokenSyntaxResult;
use shared\galaxygames\ovommand\fetus\BaseResult;

trait ParametableTrait{
	/** @var BaseParameter[][] */
	protected array $overloads = [];

	public function registerParameters(int $overloadId, BaseParameter ...$parameters) : void{
		if ($overloadId < 0) {
			throw new ParameterOrderException(ExceptionMessage::MSG_PARAMETER_NEGATIVE_ORDER->getErrorMessage(["position" => (string) $overloadId]), ParameterOrderException::PARAMETER_NEGATIVE_ORDER_ERROR);
		}
		if ($overloadId > 0 && !isset($this->overloads[$overloadId - 1])) {
			throw new ParameterOrderException(ExceptionMessage::MSG_PARAMETER_DETACHED_ORDER->getErrorMessage(["position" => (string) $overloadId]), ParameterOrderException::PARAMETER_DETACHED_ORDER_ERROR);
		}
		foreach ($parameters as $parameter) {
			if (!$parameter->isOptional()) {
				foreach ($this->overloads[$overloadId] ?? [] as $para) {
					if ($para->isOptional()) {
						throw new ParameterOrderException(ExceptionMessage::MSG_PARAMETER_DESTRUCTED_ORDER->getRawErrorMessage(), ParameterOrderException::PARAMETER_DESTRUCTED_ORDER_ERROR);
					}
				}
			}

			$this->overloads[$overloadId][] = $parameter;
		}
	}

	public function registerParameter(int $overloadId, BaseParameter $parameter) : void{
		$this->registerParameters($overloadId, $parameter);
	}

	/**
	 * @param string[] $rawParams
	 *
	 * @return BaseResult[]
	 */
	public function parseParameters(array $rawParams) : array{
		$paramCount = count($rawParams);
		if ($paramCount !== 0 && !$this->hasOverloads()) {
			return [];
		}
		/** @var BaseResult[][] $resultContainers */
		$resultContainers = [];
		$finalId = 0;
		foreach ($this->overloads as $overloadId => $parameters) {
			$offset = 0;
			$results = [];
			foreach ($parameters as $parameter) {
				$params = array_slice($rawParams, $offset, $span = $parameter->getSpanLength());
				if ($offset === $paramCount - $span + 1 && $parameter->isOptional()) {
					break;
				}
				if (count($params) < $parameter->getSpanLength()) {
					continue;
				}
				$offset += $span;
				//TODO: Because the parser might choose the wrong overloads, so adding something to stop it from doing that?
				$result = $parameter->parse($params);
				$results[$parameter->getName()] = $result;
				if ($result instanceof BrokenSyntaxResult && $overloadId + 1 !== count($this->overloads)) {
					continue 2;
				}
			}
			if ($paramCount > ($pCount = count($parameters))) {
				$results["_error"] = BrokenSyntaxResult::create(array_slice($rawParams, $pCount, $pCount + 1)[0]);
			}
			$resultContainers[$finalId = $overloadId] = $results;
		}
		return $resultContainers[$finalId];
	}

	/**
	 * @return BaseParameter[][]
	 */
	public function getOverloads() : array{
		return $this->overloads;
	}

	public function hasOverloads() : bool{
		return !empty($this->overloads);
	}
}
