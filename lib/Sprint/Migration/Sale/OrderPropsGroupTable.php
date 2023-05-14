<?php

namespace Sprint\Migration\Sale;

use Bitrix\Sale\Internals;

/**
 * Класс-расширение для работы с таблицей `b_sale_order_props_group`
 *
 * @package Sprint\Migration\Sale
 */
class OrderPropsGroupTable extends Internals\OrderPropsGroupTable
{
	public static function getMap()
	{
		$arMap = parent::getMap();

		return array_replace($arMap, [
			'PERSON_TYPE' => array(
				'data_type' => 'Bitrix\Sale\Internals\PersonTypeTable',
				'reference' => array('=this.PERSON_TYPE_ID' => 'ref.ID'),
				'join_type' => 'LEFT',
			)
		]);
	}
}