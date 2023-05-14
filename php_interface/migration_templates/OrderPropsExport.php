<?php

/**
 * @var $version
 * @var $description
 * @var $extendUse
 * @var $extendClass
 */

?><?php echo "<?php\n" ?>

namespace Sprint\Migration;

<?php echo $extendUse ?>

class <?php echo $version ?> extends <?php echo $extendClass ?>
{

	protected $description = "<?php echo $description ?>";

	protected $moduleVersion = "<?php echo $moduleVersion ?>";

	public function up()
	{
		$helper = new HelperManager();

<? if (isset($personTypeData) && is_array($personTypeData)):
	if (isset($personTypeExport) && true === $personTypeExport): ?>
		/**
		 * Export order person type
		 */
		$personTypeId = $helper->OrderService()->savePersonType(<?php echo var_export($personTypeData, 1) ?>);
			<? elseif (isset($personTypeData['CODE']) && strlen($personTypeData['CODE']) > 0):?>
		$personTypeId = $helper->OrderService()->getPersonTypeIdByCode('<?php echo $personTypeData['CODE'] ?>');
			<? elseif (isset($personTypeData['XML_ID']) && strlen($personTypeData['XML_ID']) > 0): ?>
		$personTypeId = $helper->OrderService()->getPersonTypeIdByXmlId('<?php echo $personTypeData['CODE'] ?>');
			<? else: ?>
		$personTypeId = <?php echo (int)$personTypeData['ID'] ?>;
	<? endif;
endif; ?>

<? if (isset($propertyGroupsExport) && is_array($propertyGroupsExport)):
	foreach ($propertyGroupsExport as $propertyGroup): ?>
		/**
		 * Export order property group `<?= $propertyGroup["NAME"] ?>`
		 */
		$propertyGroupId = $helper->OrderService()->saveOrderPropertyGroup($personTypeId, <?php echo var_export($propertyGroup, 1) ?>);
<?
	endforeach;
endif; ?>

<? if (isset($propertiesExport) && is_array($propertiesExport)):
	if (isset($propertiesExport) && is_array($propertiesExport)): ?>
		/**
		 * Export order properties for `<?= $personTypeData["NAME"] ?>`
		 */
		<?foreach ($propertiesExport as $property): ?>
		$helper->OrderService()->saveOrderProperty($personTypeId, <?php echo var_export($property, 1) ?>);
<?
		endforeach;
	endif;
endif; ?>

	}

	public function down() {
		$helper = new HelperManager();

		//your code ...

	}
}
