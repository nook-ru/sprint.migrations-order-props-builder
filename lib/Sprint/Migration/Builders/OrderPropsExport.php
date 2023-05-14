<?php

namespace Sprint\Migration\Builders;

use Bitrix\Main;
use Bitrix\Sale\Internals\OrderPropsTable;
use Sprint\Migration\Sale\OrderPropsGroupTable;
use Sprint\Migration\Helpers\OrderServiceHelper;
use Sprint\Migration;
use Sprint\Migration\Module;
use Sprint\Migration\HelperManager;
use Sprint\Migration\VersionConfig;

/**
 * Конфигурация миграции
 *
 * @package Sprint\Migration\Builders
 */
class OrderPropsExport extends Migration\VersionBuilder
{
	/**
	 * @var OrderServiceHelper
	 */
	private $orderHelper;

	/**
	 * OrderPropsExport constructor.
	 *
	 * @param VersionConfig $versionConfig
	 * @param $name
	 * @param array $params
	 */
	public function __construct(VersionConfig $versionConfig, $name, $params = array())
	{
		$this->orderHelper = new OrderServiceHelper();
		parent::__construct($versionConfig, $name, $params);
	}

	/**
	 * Проверка модулей перед подключением
	 *
	 * @return bool
	 * @throws Main\LoaderException
	 */
	protected function isBuilderEnabled()
	{
		return (Main\Loader::includeModule("sale"));
	}

	/**
	 * Первый шаг: инициализация миграции
	 */
	protected function initialize()
	{
		$this->setTitle("Создать миграцию для свойств заказа");
		$this->setDescription("Позволяет создать миграцию для всех полей заказа");
		$this->addField("prefix", array(
			"title" => GetMessage("SPRINT_MIGRATION_FORM_PREFIX"),
			"value" => $this->getVersionConfig()->getVal("version_prefix"),
			"width" => 250,
		));
		$this->addField("description", array(
			"title" => GetMessage("SPRINT_MIGRATION_FORM_DESCR"),
			"width" => 350,
			"height" => 40,
		));
	}

	/**
	 * Тонкие настройки миграции
	 *
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Migration\Exceptions\HelperException
	 * @throws Migration\Exceptions\RebuildException
	 */
	protected function execute()
	{
		$helper = HelperManager::getInstance();

		// Шаг 2: выбор `Тип плательщика`
		$exportPersonType = false;
		$this->addField("personTypeId", array(
			"title" => "Выберите `Тип плательщика`",
			"width" => 250,
			"multiple" => false,
			"select" => $this->getPersonTypesStructure()
		));
		$personTypeId = $this->getFieldValue("personTypeId");
		if (empty($personTypeId)) {
			$this->rebuildField("personTypeId");
		}
		$personTypeData = $helper->OrderService()->exportPersonType($personTypeId);

		// Шаг 3: что переносим
		$this->addField("what", array(
			"title" => "Что переносим",
			"width" => 250,
			"multiple" => true,
			"value" => array(),
			"select" => [
				[
					"title" => "Тип плательщика",
					"value" => "personType"
				],
				[
					"title" => "Группы свойств",
					"value" => "propertyGroups"
				],
				[
					"title" => "Свойства заказа",
					"value" => "orderProperties"
				]
			]
		));
		$what = $this->getFieldValue("what");
		if (empty($what)) {
			$this->rebuildField("what");
		} else {
			$what = is_array($what) ? $what : [$what];
		}
		if (in_array("personType", $what)) {
			$exportPersonType = true;
		}

		// Шаг 3: выбор `Группы свойств заказа`
		$exportOrderPropertyGroups = $orderPropertyGroups = false;
		if (in_array("propertyGroups", $what)) {
			$this->addField("propertyGroups", array(
				"title" => "Выберите `Группы свойств` заказа",
				"width" => 250,
				"multiple" => true,
				"value" => array(),
				"select" => $this->getOrderPropertyGroupsStructure($personTypeId)
			));
			$orderPropertyGroups = $this->getFieldValue("propertyGroups");
			if (empty($orderPropertyGroups)) {
				$this->rebuildField("propertyGroups");
			}
			$exportOrderPropertyGroups = $helper->OrderService()->exportOrderPropertyGroups($personTypeId);
		}

		// Шаг 4: выбор свойств заказа
		$exportOrderProperties = $orderPropertyIds = false;
		if (in_array("orderProperties", $what)) {
			$this->addField("orderProperties", array(
				"title" => "Выберите `Свойства заказа`",
				"width" => 250,
				"multiple" => true,
				"value" => array(),
				"select" => $this->getOrderPropsStructure($personTypeId, $orderPropertyGroups)
			));
			$orderPropertyIds = $this->getFieldValue("orderProperties");
			if (empty($orderPropertyIds)) {
				$this->rebuildField("orderProperties");
			} else {
				$orderPropertyIds = is_array($orderPropertyIds) ? $orderPropertyIds : [$orderPropertyIds];
			}
			$exportOrderProperties = $helper->OrderService()->exportOrderProperties($personTypeId, $orderPropertyGroups, $orderPropertyIds);
		}

		$this->createVersionFile(
			Module::getDocRoot() . $this->getTemplatesPath() . "OrderPropsExport.php",
			array(
				"personTypeData" => $personTypeData,                        // array
				"personTypeExport" => $exportPersonType,                    // bool
				"propertyGroupsExport" => $exportOrderPropertyGroups,       // array|bool
				"propertiesExport" => $exportOrderProperties                // array|bool
			)
		);
	}

	/**
	 * Получение подготовленного списка `Тип плательщика` (для второго шага)
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getPersonTypesStructure()
	{
		$arPersonTypes = $this->orderHelper->getPersonTypes();
		$structure = [];
		foreach ($arPersonTypes as $personType) {
			$title = $personType["NAME"];
			if (isset($personType["CODE"]) && strlen($personType["CODE"]) > 0) {
				$title .= " [" . $personType["CODE"] . "]";
			}
			$structure[] = array(
				"title" => $title,
				"value" => $personType["ID"]
			);
		}

		return $structure;
	}

	/**
	 * Получение подготовленного списка `Группы свойств` (для третьих шага)
	 *
	 * @param int|string|array|bool $personTypeId
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getOrderPropertyGroupsStructure($personTypeId = false)
	{
		$arFilter = $structure = array();
		if (
			(is_array($personTypeId) && count($personTypeId) > 0)
			|| $personTypeId > 0
		) {
			$arFilter["PERSON_TYPE_ID"] = $personTypeId;
		}
		$resGroups = OrderPropsGroupTable::getList([
			"select" => ["ID", "NAME", "PERSON_TYPE_NAME" => "PERSON_TYPE.NAME"],
			"filter" => $arFilter,
			"order" => ["ID" => "ASC", "NAME" => "ASC"]
		]);

		while ($group = $resGroups->fetch()) {
			$structure[] = array(
				"title" => $group["NAME"] . " [" . $group["PERSON_TYPE_NAME"] . "]",
				"value" => $group["ID"]
			);
		}

		return $structure;
	}

	/**
	 * Получение списка `Свойства заказа` (для четвертого шага)
	 *
	 * @param int|string|array|bool $personTypeId
	 * @param int|string|array|bool $propertyGroups
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getOrderPropsStructure($personTypeId, $propertyGroups = false)
	{
		$arFilter = $structure = array();
		if (
			(is_array($personTypeId) && count($personTypeId) > 0)
			|| $personTypeId > 0
		) {
			$arFilter["PERSON_TYPE_ID"] = $personTypeId;
		}
		if (
			(is_array($propertyGroups) && count($propertyGroups) > 0)
			|| $propertyGroups > 0
		) {
			$arFilter["PROPS_GROUP_ID"] = $propertyGroups;
		}
		$resOrderProps = OrderPropsTable::getList([
			"select" => ["ID", "CODE", "NAME", "PERSON_TYPE_NAME" => "PERSON_TYPE.NAME", "GROUP_NAME" => "GROUP.NAME"],
			"filter" => $arFilter,
			"order" => ["SORT" => "ASC", "NAME" => "ASC"]
		]);

		while ($property = $resOrderProps->fetch()) {
			$title = $property["NAME"];
			if (
				strlen($property["PERSON_TYPE_NAME"]) > 0
				|| strlen($property["GROUP_NAME"]) > 0
			) {
				$title .= " [" . implode(" | ", [$property["CODE"], $property["GROUP_NAME"],
						$property["PERSON_TYPE_NAME"]]) . "]";
			}
			$structure[] = [
				"title" => $title,
				"value" => $property["ID"]
			];
		}

		return $structure;
	}

	public function getTemplatesPath()
	{
		return "/local/php_interface/migration_templates/";
	}
}
