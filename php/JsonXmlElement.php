<?php

class JsonXmlElement extends SimpleXmlElement implements JsonSerializable
{
	const ATTRIBUTE_INDEX = "@attr";
	const CONTENT_NAME = "_text";

	function jsonSerialize()
	{
		$array = [];

		if ($this->count()) {
			// serialize children if there are children
			/**
			 * @var string $tag
			 * @var JsonSerializer $child
			 */
			foreach ($this as $tag => $child) {
				$temp = $child->jsonSerialize();
				$attributes = [];

				foreach ($child->attributes() as $name => $value) {
					$attributes["$name"] = (string) utf8_encode($value);
				}

				$array[$tag][] = array_merge($temp, $attributes);
			}
		} else {
			// serialize attributes and text for a leaf-elements
			$temp = (string) $this;

			// if only contains empty string, it is actually an empty element
			if (trim($temp) !== "") {
				$array[self::CONTENT_NAME] = $temp;
			}
		}

		if ($this->xpath('/*') == array($this)) {
			// the root element needs to be named
			$array = [$this->getName() => $array];
		}

		foreach($this->attributes() as $name => $value) $array[$name] = (string) $value;

		return $array;
	}

	function asJson() {
		return json_encode($this);
	}

	function asArray() {
		return json_decode(json_encode($this, JSON_UNESCAPED_UNICODE),true);
	}
}