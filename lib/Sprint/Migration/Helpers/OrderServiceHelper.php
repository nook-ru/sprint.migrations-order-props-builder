<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main;
use Bitrix\Sale;

use CUtil;
use Exception;

use Sprint\Migration;

/**
 * Работа с заказами
 *
 * @package Sprint\Migration\Helpers
 */
class OrderServiceHelper extends Migration\Helper
{
	/**
	 * Поля и значения свойства заказа по-умолчанию
	 */
	const DEFAULT_ORDER_PROPERTY_FIELDS = [
		"TYPE" => "STRING",
		"REQUIRED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 500,
		"USER_PROPS" => "N",
		"IS_LOCATION" => "N",
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_LOCATION4TAX" => "N",
		"IS_FILTRED" => "N",
		"IS_ZIP" => "N",
		"IS_PHONE" => "N",
		"ACTIVE" => "Y",
		"UTIL" => "N",
		"INPUT_FIELD_LOCATION" => 0,
		"MULTIPLE" => "N",
		"IS_ADDRESS" => "N",
		"ENTITY_REGISTRY_TYPE" => "ORDER",
	];

	/**
	 * OrderServiceHelper constructor.
	 */
	public function __construct()
	{
		$this->checkModules(["sale"]);
	}

	/**
	 * Сохранение `Тип плательщика`
	 *
	 * @param array $arFields
	 * @return array|bool|int|mixed|string
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Migration\Exceptions\HelperException
	 */
	public function savePersonType(array $arFields)
	{
		$exists = false;
		if (count($arFields) <= 0) {
			$this->throwException(__METHOD__, "Переданы некорректные данные для сохранения `Тип плательщика`");
		} elseif (isset($arFields["XML_ID"]) && strlen($arFields["XML_ID"]) > 0) {
			$exists = $this->getPersonTypeByXmlId($arFields["XML_ID"]);
		} elseif (isset($arFields["CODE"]) && strlen($arFields["CODE"]) > 0) {
			$exists = $this->getPersonTypeByCode($arFields["CODE"]);
		}

		if (false === $exists) {
			$ok = $this->getMode("test") ? true : $this->addPersonType($arFields);
			$this->outNoticeIf($ok, "Тип плательщика %s добавлен", $arFields["CODE"]);

			return $ok;
		}

		$prepareExist = $this->clearFields($exists);
		$prepareFields = $this->clearFields($arFields);
		if ($this->hasDiff($prepareExist, $arFields)) {
			$ok = $this->getMode("test") ? true : $this->updatePersonType($exists["ID"], $arFields);
			$this->outNoticeIf($ok, "Тип плательщика %s обновлен", $arFields["CODE"]);
			$this->outDiffIf($ok, $prepareExist, $prepareFields);

			return $ok;
		}

		$ok = $this->getMode("test") ? true : $exists["ID"];
		if ($this->getMode("out_equal")) {
			$this->outIf($ok, "Тип плательщика %s не требует обновления", $arFields["CODE"]);
		}

		return $ok;
	}

	/**
	 * Добавление `Тип плательщика`
	 *
	 * @param array $arFields
	 * @return array|int
	 * @throws Exception
	 */
	public function addPersonType(array $arFields)
	{
		$resAdd = Sale\PersonTypeTable::add($arFields);
		if (false === $resAdd->isSuccess()) {
			$this->throwException(__METHOD__, implode("; ", $resAdd->getErrorMessages()));
		}

		return $resAdd->getId();
	}

	/**
	 * Обновление данных о `Тип плательщика`
	 *
	 * @param int|string $id
	 * @param array $arFields
	 * @return array|int|string
	 * @throws Exception
	 */
	public function updatePersonType($id, array $arFields)
	{
		$resUpdate = Sale\PersonTypeTable::update($id, $this->clearFields($arFields));
		if (false === $resUpdate->isSuccess()) {
			$this->throwException(__METHOD__, implode("; ", $resUpdate->getErrorMessages()));
		}

		return true;
	}

	/**
	 * Получение идентифкатора `Тип плательщика` по символьному коду
	 *
	 * @param string|int $code
	 * @return bool|int
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getPersonTypeIdByCode($code)
	{
		$personType = $this->getPersonTypeByCode($code);

		return (is_array($personType) && isset($personType["ID"]) && $personType["ID"] > 0)
			? (int)$personType["ID"]
			: false;
	}

	/**
	 * Получение идентифкатора `Тип плательщика` по внешнему коду
	 *
	 * @param string|int $xmlId
	 * @return bool|int
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getPersonTypeIdByXmlId($xmlId)
	{
		$personType = $this->getPersonTypeByXmlId($xmlId);

		return (is_array($personType) && isset($personType["ID"]) && $personType["ID"] > 0)
			? (int)$personType["ID"]
			: false;
	}

	/**
	 * Получение `Тип плательщика` по ID
	 *
	 * @param int $id
	 * @param bool $clearFields - очистка полей от `лишних` значений
	 * @return bool|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getPersonTypeById($id, $clearFields = false)
	{
		if ((int)$id <= 0) {
			return false;
		}
		$arTypes = $this->getPersonTypes(["ID" => (int)$id], $clearFields);

		return count($arTypes) > 0 ? $arTypes[0] : false;
	}

	/**
	 * Получение `Тип плательщика` по символьному коду
	 *
	 * @param string $code
	 * @param bool $clearFields - очистка полей от `лишних` значений
	 * @return bool|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getPersonTypeByCode($code, $clearFields = false)
	{
		if (!is_scalar($code) || strlen($code) <= 0) {
			return false;
		}
		$arTypes = $this->getPersonTypes(["CODE" => (string)$code], $clearFields);

		return count($arTypes) > 0 ? $arTypes[0] : false;
	}

	/**
	 * Получение `Тип плательщика` по внешнему коду
	 *
	 * @param string $xmlId
	 * @param bool $clearFields - очистка полей от `лишних` значений
	 * @return bool|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getPersonTypeByXmlId($xmlId, $clearFields = false)
	{
		if (!is_scalar($xmlId) || strlen($xmlId) <= 0) {
			return false;
		}
		$arTypes = $this->getPersonTypes(["XML_ID" => (string)$xmlId], $clearFields);

		return count($arTypes) > 0 ? $arTypes[0] : false;
	}

	/**
	 * Получение списка всех типов покупателей
	 *
	 * @param array $filter
	 * @param bool $clearFields - очистка полей от `лишних` значений
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getPersonTypes(array $filter = [], $clearFields = false)
	{
		$resPersonTypes = Sale\PersonTypeTable::getList([
			"select" => ["*"],
			"filter" => $filter,
			"order" => ["SORT" => "ASC", "NAME" => "ASC"],
		]);
		$result = array();
		while ($personType = $resPersonTypes->fetch()) {
			$result[] = (true === $clearFields) ? $this->clearFields($personType) : $personType;
		}

		return $result;
	}

	/**
	 * Подготовка данных для экспорта `Тип плательщика`
	 *
	 * @param int $personTypeId
	 * @return array|bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function exportPersonType($personTypeId)
	{
		if ((int)$personTypeId <= 0) {
			return false;
		}

		return $this->getPersonTypeById($personTypeId, true);
	}

	/**
	 * Добавление `Группы свойств заказа`
	 *
	 * @param array $arFields
	 * @return array|int
	 * @throws Exception
	 */
	public function addOrderPropertyGroup(array $arFields)
	{
		$resAdd = Sale\Internals\OrderPropsGroupTable::add($arFields);
		if (false === $resAdd->isSuccess()) {
			$this->throwException(__METHOD__, implode("; ", $resAdd->getErrorMessages()));
		}

		return $resAdd->getId();
	}

	/**
	 * Обновление данных о `Группы свойств заказа`
	 *
	 * @param int $id
	 * @param array $arFields
	 * @return bool
	 * @throws Migration\Exceptions\HelperException
	 */
	public function updateOrderPropertyGroup($id, array $arFields)
	{
		$resUpdate = Sale\Internals\OrderPropsGroupTable::update($id, $this->clearFields($arFields));
		if (false === $resUpdate->isSuccess()) {
			$this->throwException(__METHOD__, implode("; ", $resUpdate->getErrorMessages()));
		}

		return true;
	}

	/**
	 * Получение `Группа свойств` по ID
	 *
	 * @param int $id
	 * @param bool $clearFields
	 * @return bool|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getOrderPropertyGroupById($id, $clearFields = false)
	{
		if ((int)$id <= 0) {
			return false;
		}
		$arGroups = $this->getOrderPropertyGroups(["ID" => $id], $clearFields);

		return count($arGroups) > 0 ? $arGroups[0] : false;
	}

	/**
	 * Получение `Группа свойств` по наименованию
	 *
	 * @param $name
	 * @param bool $clearFields
	 * @return bool|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getOrderPropertyGroupByName($name, $clearFields = false)
	{
		if (!is_scalar($name) || strlen($name) <= 0) {
			return false;
		}
		$arGroups = $this->getOrderPropertyGroups(["NAME" => $name], $clearFields);

		return count($arGroups) > 0 ? $arGroups[0] : false;
	}

	/**
	 * Получение списка `Группы свойств заказа`
	 *
	 * @param array $filter
	 * @param bool $clearFields - очистка полей от `лишних` значений
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getOrderPropertyGroups($filter = [], $clearFields = false)
	{
		$result = [];
		$resGroups = Sale\Internals\OrderPropsGroupTable::getList([
			"select" => ["*"],
			"filter" => $filter,
			"order" => ["SORT" => "ASC"],
		]);
		while ($arGroups = $resGroups->fetch()) {
			$result[] = (true === $clearFields) ? $this->clearFields($arGroups) : $arGroups;
		}

		return $result;
	}

	/**
	 * Подготовка данных для экспорта `Группы свойств заказа`
	 *
	 * @param array|bool $personType
	 * @return array|bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function exportOrderPropertyGroups($personType)
	{
		if (!$filterTypes = $this->prepareIds($personType)) {
			return false;
		}

		return $this->getOrderPropertyGroups(["PERSON_TYPE_ID" => $filterTypes], true);
	}

	/**
	 * Сохранение данных `Группы свойств заказа`
	 *
	 * @param int $personTypeId
	 * @param array $arFields
	 * @return array|bool|int
	 * @throws Exception
	 */
	public function saveOrderPropertyGroup($personTypeId, array $arFields)
	{
		$arFields["PERSON_TYPE_ID"] = (int)$personTypeId;
		$exists = false;
		if (in_array("NAME", $arFields)) {
			$exists = $this->getOrderPropertyGroupByName($arFields["NAME"], false);
		}

		// добавление новой строки
		if (false === $exists) {
			$ok = $this->getMode("test") ? true : $this->addOrderPropertyGroup($arFields);
			$this->outNoticeIf($ok, "Группа свойства заказа %s добавлена", $arFields["CODE"]);

			return $ok;
		}

		// обновление данных
		$prepareExist = $this->clearFields($exists);
		if ($this->hasDiff($prepareExist, $arFields)) {
			$ok = $this->getMode("test") ? true : $this->updateOrderProperty($exists["ID"], $arFields);
			$this->outNoticeIf($ok, "Группа свойства заказа %s обновлена", $arFields["CODE"]);
			$this->outDiffIf($ok, $prepareExist, $arFields);

			return $ok;
		}

		$ok = $this->getMode("test") ? true : $exists["ID"];
		if ($this->getMode("out_equal")) {
			$this->outIf($ok, "Группа свойства заказа %s не требует обновлений", $arFields["CODE"]);
		}

		return $ok;
	}

	/**
	 * Добавление `Свойство заказа`
	 *
	 * @param array $arFields
	 * @return array|int
	 * @throws Migration\Exceptions\HelperException
	 */
	public function addOrderProperty(array $arFields)
	{
		$resAdd = Sale\Internals\OrderPropsTable::add($arFields);
		if (false === $resAdd->isSuccess()) {
			$this->throwException(__METHOD__, implode("; ", $resAdd->getErrorMessages()));
		}

		return $resAdd->getId();
	}

	/**
	 * Добавление `Свойства заказа` с предварительной проверкой по внешнему и символьному коду
	 *
	 * @param array $arFields
	 * @return array|int|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Migration\Exceptions\HelperException
	 */
	public function addOrderPropertyIfExist(array $arFields)
	{
		// проверка сущестования по внешнему коду
		if (isset($arFields["XML_ID"]) && strlen($arFields["XML_ID"]) > 0) {
			if ($exist = $this->getOrderPropertyByXmlId($arFields["XML_ID"])) {
				return $exist["ID"];
			}
		}

		// проверка сущестования по символьному коду
		if (isset($arFields["CODE"]) && strlen($arFields["CODE"]) > 0) {
			if ($exist = $this->getOrderPropertyByCode($arFields["CODE"])) {
				return $exist["ID"];
			}
		}

		return $this->addOrderProperty($arFields);
	}

	/**
	 * Обновление `Свойство заказа`
	 *
	 * @param int $id
	 * @param array $arFields
	 * @return bool
	 * @throws Migration\Exceptions\HelperException
	 */
	public function updateOrderProperty($id, array $arFields)
	{
		$resUpdate = Sale\Internals\OrderPropsTable::update($id, $this->clearFields($arFields));
		if (false === $resUpdate->isSuccess()) {
			$this->throwException(__METHOD__, implode("; ", $resUpdate->getErrorMessages()));
		}

		return true;
	}

	/**
	 * Получение `Свойство заказа` по ID
	 *
	 * @param int $id
	 * @param bool $convertSettings
	 * @return bool|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getOrderPropertyById($id, $convertSettings = false)
	{
		if ((int)$id <= 0) {
			return false;
		}
		$arProps = $this->getOrderProperties(["ID" => (int)$id], $convertSettings);

		return count($arProps) > 0 ? $arProps[0] : false;
	}

	/**
	 * Получение `Свойство зказа` по символьному коду
	 *
	 * @param string|int $code
	 * @param bool $convertSettings - unserialize `SETTINGS`
	 * @return bool|array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getOrderPropertyByCode($code, $convertSettings = false)
	{
		if (!is_scalar($code) || strlen($code) <= 0) {
			return false;
		}
		$arProps = $this->getOrderProperties(["CODE" => $code], $convertSettings);

		return count($arProps) > 0 ? $arProps[0] : false;
	}

	/**
	 * Получение `Свойство зказа` по внешнему коду
	 *
	 * @param string $xmlId
	 * @param bool $convertSettings - unserialize `SETTINGS`
	 * @return bool|array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getOrderPropertyByXmlId($xmlId, $convertSettings = false)
	{
		if (!is_scalar($xmlId) || strlen($xmlId) <= 0) {
			return false;
		}
		$arProps = $this->getOrderProperties(["XML_ID" => $xmlId], $convertSettings);

		return count($arProps) > 0 ? $arProps[0] : false;
	}

	/**
	 * Получение списка `Свойства заказа`
	 *
	 * @param array $filter
	 * @param bool $convertSettings - unserialize `SETTINGS`
	 * @param bool $clearFields - очистка полей от `лишних` значений
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getOrderProperties(array $filter = [], $convertSettings = false, $clearFields = false)
	{
		$resProperties = Sale\Internals\OrderPropsTable::getList([
			"select" => ["*"],
			"filter" => $filter,
			"order" => ["ID" => "ASC", "NAME" => "ASC"],
		]);
		$result = array();
		while ($arProperty = $resProperties->fetch()) {
			if (
				true === $convertSettings
				&& isset($arProperty["SETTINGS"])
				&& strlen($arProperty["SETTINGS"]) > 0) {
				$arProperty["SETTINGS"] = unserialize($arProperty["SETTINGS"]);
				if (!is_array($arProperty["SETTINGS"])) {
					$arProperty["SETTINGS"] = array();
				}
			}
			$result[] = (true === $clearFields) ? $this->clearFields($arProperty) : $arProperty;
		}

		return $result;
	}

	/**
	 * Подготовка и перепроверка данных о `Свойство заказа` перед какой-либо манипуляцией
	 * TODO: Подумать ещё!
	 *
	 * @param array $arFields
	 * @return array
	 * @throws Migration\Exceptions\HelperException
	 */
	private function prepareOrderProperty(array $arFields)
	{
		if (
			!isset($arFields["NAME"])
			|| !is_scalar($arFields["NAME"])
			|| strlen(trim($arFields["NAME"])) <= 0
		) {
			$this->throwException(__METHOD__, "Отсутствует поле `Наименование`");
		}
		if (
			!isset($arFields["PERSON_TYPE_ID"])
			|| 0 >= (int)$arFields["PERSON_TYPE_ID"]
		) {
			$this->throwException(__METHOD__, "Отсутствует поле `Тип плательщика`");
		}
		if (isset($arFields["ID"])) {
			unset($arFields["ID"]);
		}
		$arFields = array_replace(self::DEFAULT_ORDER_PROPERTY_FIELDS, $arFields);
		if (
			!isset($arFields["CODE"])
			|| !is_scalar($arFields["CODE"])
			|| strlen($arFields["CODE"]) <= 0) {
			$arFields["CODE"] = CUtil::translit($arFields["CODE"]);
		}
		if (
			!isset($arFields["XML_ID"])
			|| !is_scalar($arFields["XML_ID"])
			|| strlen($arFields["XML_ID"]) <= 0) {
			$arFields["XML_ID"] = "bx_" . substr(md5(microtime(true)), 0, 12);
		}
		if (isset($arFields["SETTINGS"]) && is_array($arFields["SETTINGS"])) {
			$arFields["SETTINGS"] = serialize($arFields["SETTINGS"]);
		}

		return $arFields;
	}

	/**
	 * Получение данных для экспорта свойств заказа
	 *
	 * @param int|string|array|bool $personTypeId - идентифкатор(ы) типов плательщика
	 * @param int|string|array|bool $propertyGroupIds - идентифкаторы групп свойств
	 * @param array|bool $propertyIds - идентифкаторы свойств заказа
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function exportOrderProperties($personTypeId, $propertyGroupIds = false, $propertyIds = false)
	{
		$arFilter = array();
		if ($personTypeId = $this->prepareIds($personTypeId)) {
			$arFilter["PERSON_TYPE_ID"] = $personTypeId;
		}
		if ($propertyIds = $this->prepareIds($propertyIds)) {
			$arFilter["ID"] = $propertyIds;
		}
		if ($propertyGroupIds = $this->prepareIds($propertyGroupIds)) {
			$arFilter["PROPS_GROUP_ID"] = $propertyGroupIds;
		}

		return $this->getOrderProperties($arFilter, true, true);
	}

	/**
	 * Сохранение данных `Свойства заказа`
	 *
	 * @param int $personTypeId
	 * @param array $arFields
	 * @return array|bool|int|mixed
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Migration\Exceptions\HelperException
	 */
	public function saveOrderProperty($personTypeId, array $arFields = [])
	{
		$arFields["PERSON_TYPE_ID"] = $personTypeId;
		$exists = false;
		if (isset($arFields["XML_ID"]) && strlen($arFields["XML_ID"]) > 0) {
			$exists = $this->getOrderPropertyByXmlId($arFields["XML_ID"], false);
		} elseif (isset($arFields["CODE"]) && strlen($arFields["CODE"]) > 0) {
			$exists = $this->getOrderPropertyByCode($arFields["CODE"], false);
		}

		// добавление новой строки
		if (false === $exists) {
			$ok = $this->getMode("test") ? true : $this->addOrderProperty($arFields);
			$this->outNoticeIf($ok, "Свойство заказа %s добавлено", $arFields["CODE"]);

			return $ok;
		}

		// обновление данных
		$prepareExist = $this->clearFields($exists);
		if ($this->hasDiff($prepareExist, $arFields)) {
			$ok = $this->getMode("test") ? true : $this->updateOrderProperty($exists["ID"], $arFields);
			$this->outNoticeIf($ok, "Свойство заказа %s обновлено", $arFields["CODE"]);
			$this->outDiffIf($ok, $prepareExist, $arFields);

			return $ok;
		}

		$ok = $this->getMode("test") ? true : $exists["ID"];
		if ($this->getMode("out_equal")) {
			$this->outIf($ok, "Свойство заказа %s совпадает", $arFields["CODE"]);
		}

		return $ok;
	}

	/**
	 * Базовая подготовка полей перед действием
	 * Происходит очистка массива полей от `ненужных` значений
	 *
	 * @param array $arFields
	 * @return array
	 */
	public function clearFields(array $arFields)
	{
		return array_filter($arFields, function ($k) {
			return !in_array($k, ["ID", "PERSON_TYPE_ID"]);
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * Предподготовка и фильтрация типов плательщика
	 *
	 * @param array|int $ids
	 * @return array|bool
	 */
	private function prepareIds($ids)
	{
		$result = array();
		if (is_scalar($ids) && (int)$ids > 0) {
			$result = [(int)$ids];
		} elseif (is_array($ids) && count($ids) > 0) {
			$result = array_filter($ids, function ($v) {
				return (int)$v > 0;
			});
		}

		return count($result) > 0 ? $result : false;
	}
}
